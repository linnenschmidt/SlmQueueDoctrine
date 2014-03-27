<?php

namespace SlmQueueDoctrineODM\Queue;

use DateTime;
use DateTimeZone;
use DateInterval;
use MongoDate;
use Doctrine\ODM\MongoDB\DocumentManager;
use SlmQueue\Queue\AbstractQueue;
use SlmQueue\Job\JobInterface;
use SlmQueue\Job\JobPluginManager;
use SlmQueueDoctrineODM\Exception;

class DoctrineODMQueue extends AbstractQueue implements DoctrineODMQueueInterface
{
    const STATUS_PENDING = 1;
    const STATUS_RUNNING = 2;
    const STATUS_DELETED = 3;
    const STATUS_BURIED  = 4;

    const LIFETIME_DISABLED  = 0;
    const LIFETIME_UNLIMITED = -1;

    /**
     * @var DocumentManager;
     */
    protected $dm;

    /**
     * How long to keep deleted (successful) jobs (in minutes)
     *
     * @var int
     */
    protected $deletedLifetime;

    /**
     * How long to keep buried (failed) jobs (in minutes)
     *
     * @var int
     */
    protected $buriedLifetime;

    /**
     * How long show we sleep when no jobs available for processing (in seconds)
     *
     * @var int
     */
    protected $sleepWhenIdle = 1;

    /**
     * Document class which should be used
     *
     * @var string
     */
    protected $document;

    /**
     * Constructor
     *
     * @param DocumentManager  $documentManager
     * @param string           $document
     * @param string           $name
     * @param JobPluginManager $jobPluginManager
     */
    public function __construct(DocumentManager $documentManager, $document, $name, JobPluginManager $jobPluginManager)
    {
        $this->dm = $documentManager;
        $this->document  = $document;

        $this->deletedLifetime = static::LIFETIME_DISABLED;
        $this->buriedLifetime  = static::LIFETIME_DISABLED;

        parent::__construct($name, $jobPluginManager);
    }

    /**
     * @param int $buriedLifetime
     */
    public function setBuriedLifetime($buriedLifetime)
    {
        $this->buriedLifetime = (int) $buriedLifetime;
    }

    /**
     * @param int
     */
    public function getBuriedLifetime()
    {
        return $this->buriedLifetime;
    }

    /**
     * @param int $deletedLifetime
     */
    public function setDeletedLifetime($deletedLifetime)
    {
        $this->deletedLifetime = (int) $deletedLifetime;
    }

    /**
     * @param int
     */
    public function getDeletedLifetime()
    {
        return $this->deletedLifetime;
    }

    /**
     * @param int $sleepWhenIdle
     */
    public function setSleepWhenIdle($sleepWhenIdle)
    {
        $this->sleepWhenIdle = (int) $sleepWhenIdle;
    }

    /**
     * @return int
     */
    public function getSleepWhenIdle()
    {
        return $this->sleepWhenIdle;
    }

    /**
     * {@inheritDoc}
     *
     * Note : see DoctrineODMQueue::parseOptionsToDateTime for schedule and delay options
     */
    public function push(JobInterface $job, array $options = array())
    {
        $scheduled = $this->parseOptionsToDateTime($options);

        $task = new $this->document();

        $task->setQueue($this->getName());
        $task->setStatus(self::STATUS_PENDING);
        $task->setCreated(new MongoDate);
        $task->setData($job->jsonSerialize());
        $task->setScheduled($scheduled);

        $this->dm->persist($task);

        $this->dm->flush();
        $this->dm->clear();

        $job->setId($task->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function pop(array $options = array())
    {
        // First run garbage collection
        $this->purge();

        try {
            $document =  $this->dm->createQueryBuilder( $this->document )
                ->findAndUpdate()
                ->returnNew()
                ->field('status')->equals(static::STATUS_PENDING)
                ->field('queue')->equals($this->getName())
                ->field('scheduled')->lt(new MongoDate)

                ->field('status')->set(static::STATUS_RUNNING)
                ->field('executed')->set(new MongoDate)

                ->getQuery()->execute();

            $this->dm->clear();

        } catch (Exception $e) {
            throw new Exception\RuntimeException($e->getMessage(), $e->getCode(), $e);
        }

        if (is_null($document)) {
            sleep($this->sleepWhenIdle);

            return null;
        }

        $data = json_decode($document->data, true);
        // Add job ID to meta data
        $data['metadata']['id'] = $document->id;

        return $this->createJob($data['class'], $data['content'], $data['metadata']);
    }

    /**
     * {@inheritDoc}
     *
     * Note: When $deletedLifetime === 0 the job will be deleted immediately
     */
    public function delete(JobInterface $job, array $options = array())
    {
        if ($this->getDeletedLifetime() === static::LIFETIME_DISABLED) {
            $this->dm->createQueryBuilder( $this->document )
                ->remove()
                ->field('id')->equals($job->getId())

                ->getQuery()->execute();
        } else {
            $document =  $this->dm->createQueryBuilder( $this->document )
                ->findAndUpdate()
                ->field('id')->equals($job->getId())
                ->field('status')->equals(static::STATUS_RUNNING)

                ->field('status')->set(static::STATUS_DELETED)
                ->field('finished')->set(new MongoDate)

                ->getQuery()->execute();

            $this->dm->clear();
        }
    }

    /**
     * {@inheritDoc}
     *
     * Note: When $buriedLifetime === 0 the job will be deleted immediately
     */
    public function bury(JobInterface $job, array $options = array())
    {
        if ($this->getBuriedLifetime() === static::LIFETIME_DISABLED) {
            $this->dm->createQueryBuilder( $this->document )
                ->remove()
                ->field('id')->equals($job->getId())

                ->getQuery()->execute();
        } else {
            $message = isset($options['message']) ? $options['message'] : null;
            $trace   = isset($options['trace']) ? $options['trace'] : null;

            $this->dm->createQueryBuilder( $this->document )
                ->findAndUpdate()
                ->field('id')->equals($job->getId())
                ->field('status')->equals(static::STATUS_RUNNING)

                ->field('status')->set(static::STATUS_BURIED)
                ->field('finished')->set(new MongoDate)
                ->field('message')->set($message)
                ->field('trace')->set($trace)

                ->getQuery()->execute();

            $this->dm->clear();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function recover($executionTime)
    {
        $executedLifetime = $this->parseOptionsToDateTime(array('delay' => - ($executionTime * 60)));

        $documents = $this->dm->createQueryBuilder( $this->document )
            ->update()
            ->multiple(true)
            ->field('executed')->lt($executedLifetime)
            ->field('status')->equals(static::STATUS_RUNNING)
            ->field('queue')->equals($this->getName())
            ->field('finished')->exists(false)

            ->field('status')->set(static::STATUS_PENDING)

            ->getQuery()->execute();

        return $documents['n'];
    }

    /**
     * Create a concrete instance of a job from the queue
     *
     * @param  int          $id
     * @return JobInterface
     * @throws Exception\JobNotFoundException
     */
    public function peek($id)
    {
        $document = $this->dm->getRepository( $this->document )->find($id);

        $this->dm->clear();

        if (!$document) {
            throw new Exception\JobNotFoundException(sprintf("Job with id '%s' does not exists.", $id));
        }

        $data = json_decode($document->data, true);
        // Add job ID to meta data
        $data['metadata']['id'] = $document->id;

        return $this->createJob($data['class'], $data['content'], $data['metadata']);
    }

    /**
     * Reschedules a specific running job
     *
     * Note : see DoctrineODMQueue::parseOptionsToDateTime for schedule and delay options
     *
     * @param  JobInterface             $job
     * @param  array                    $options
     * @throws Exception\LogicException
     */
    public function release(JobInterface $job, array $options = array())
    {
        $scheduled = $this->parseOptionsToDateTime($options);

        $document =  $this->dm->createQueryBuilder( $this->document )
            ->findAndUpdate()
            ->field('id')->equals($job->getId())
            ->field('status')->equals(static::STATUS_RUNNING)

            ->field('status')->set(static::STATUS_PENDING)
            ->field('finished')->set(new MongoDate)
            ->field('scheduled')->set($scheduled)
            ->field('data')->set($job->jsonSerialize())

            ->getQuery()->execute();

        $this->dm->clear();
    }

    /**
     * Parses options to a datetime object
     *
     * valid options keys:
     *
     * scheduled: the time when the job will be scheduled to run next
     * - numeric string or integer - interpreted as a timestamp
     * - string parserable by the DateTime object
     * - DateTime instance
     * delay: the delay before a job become available to be popped (defaults to 0 - no delay -)
     * - numeric string or integer - interpreted as seconds
     * - string parserable (ISO 8601 duration) by DateTimeInterval::__construct
     * - string parserable (relative parts) by DateTimeInterval::createFromDateString
     * - DateTimeInterval instance
     *
     * @see http://en.wikipedia.org/wiki/Iso8601#Durations
     * @see http://www.php.net/manual/en/datetime.formats.relative.php
     *
     * @param $options array
     * @return DateTime
     */
    protected function parseOptionsToDateTime($options)
    {
        $now       = new DateTime(null, new DateTimeZone(date_default_timezone_get()));
        $scheduled = clone ($now);

        if (isset($options['scheduled'])) {
            switch (true) {
                case is_numeric($options['scheduled']):
                    $scheduled = new DateTime(sprintf("@%d", (int) $options['scheduled']),
                        new DateTimeZone(date_default_timezone_get()));
                    break;
                case is_string($options['scheduled']):
                    $scheduled = new DateTime($options['scheduled'], new DateTimeZone(date_default_timezone_get()));
                    break;
                case $options['scheduled'] instanceof DateTime:
                    $scheduled = $options['scheduled'];
                    break;
            }
        }

        if (isset($options['delay'])) {
            switch (true) {
                case is_numeric($options['delay']):
                    $delay = new DateInterval(sprintf("PT%dS", abs((int) $options['delay'])));
                    $delay->invert = ($options['delay'] < 0) ? 1 : 0;
                    break;
                case is_string($options['delay']):
                    try {
                        // first try ISO 8601 duration specification
                        $delay = new DateInterval($options['delay']);
                    } catch (\Exception $e) {
                        // then try normal date parser
                        $delay = DateInterval::createFromDateString($options['delay']);
                    }
                    break;
                case $options['delay'] instanceof DateInterval:
                    $delay = $options['delay'];
                    break;
                default:
                    $delay = null;
            }

            if ($delay instanceof DateInterval) {
                $scheduled->add($delay);
            }
        }

        $mongoDate = new MongoDate( $scheduled->getTimestamp());

        return $mongoDate;
    }

    /**
     * Cleans old jobs in the table according to the configured lifetime of successful and failed jobs.
     */
    protected function purge()
    {
        if ($this->getBuriedLifetime() > static::LIFETIME_UNLIMITED) {
            $buriedLifetime = $this->parseOptionsToDateTime(array('delay' => - ($this->getBuriedLifetime() * 60)));

            $this->dm->createQueryBuilder( $this->document )
                ->remove()
                ->field('finished')->lt($buriedLifetime)
                ->field('status')->equals(static::STATUS_BURIED)
                ->field('queue')->equals($this->getName())
                ->field('finished')->exists(true)

                ->getQuery()->execute();
        }

        if ($this->getDeletedLifetime() > static::LIFETIME_UNLIMITED) {
            $deletedLifetime = $this->parseOptionsToDateTime(array('delay' => - ($this->getDeletedLifetime() * 60)));

            $this->dm->createQueryBuilder( $this->document )
                ->remove()
                ->field('finished')->lt($deletedLifetime)
                ->field('status')->equals(static::STATUS_DELETED)
                ->field('queue')->equals($this->getName())
                ->field('finished')->exists(true)

                ->getQuery()->execute();
        }
    }
}
