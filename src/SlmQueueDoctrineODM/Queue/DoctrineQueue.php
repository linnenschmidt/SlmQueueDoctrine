<?php

namespace SlmQueueDoctrineODM\Queue;

use DateInterval;
use DateTime;
use DateTimeZone;
use MongoDate;
use Doctrine\ODM\MongoDB\DocumentManager;
use SlmQueue\Job\JobInterface;
use SlmQueue\Job\JobPluginManager;
use SlmQueue\Queue\AbstractQueue;
use SlmQueueDoctrineODM\Exception;
use SlmQueueDoctrineODM\Options\DoctrineOptions;

class DoctrineQueue extends AbstractQueue implements DoctrineQueueInterface
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
     * Options for this queue
     *
     * @var DoctrineOptions $options
     */
    protected $options;

    /**
     * Constructor
     *
     * @param DocumentManager  $documentManager
     * @param DoctrineOptions  $options
     * @param string           $name
     * @param JobPluginManager $jobPluginManager
     */
    public function __construct(
        DocumentManager $documentManager,
        DoctrineOptions $options,
        $name,
        JobPluginManager $jobPluginManager
    ) {
        $this->dm = $documentManager;
        $this->options  = clone $options;

        parent::__construct($name, $jobPluginManager);
    }

    /**
     * @return DoctrineOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     *
     * Note : see DoctrineQueue::parseOptionsToDateTime for schedule and delay options
     */
    public function push(JobInterface $job, array $options = array())
    {
        $scheduled = $this->parseOptionsToDateTime($options);

        $queueClassName = $this->options->getDocument();
        $queue = new $queueClassName;

        $queue->setQueue($this->getName());
        $queue->setStatus(self::STATUS_PENDING);
        $queue->setCreated(new MongoDate);
        $queue->setData($this->serializeJob($job));
        $queue->setScheduled($scheduled);

        $this->dm->persist($queue);

        $this->dm->flush();
        $this->dm->clear();

        $job->setId($queue->getId());
    }

    /**
     * {@inheritDoc}
     */
    public function pop(array $options = array())
    {
        // First run garbage collection
        $this->purge();

        try {
            $document =  $this->dm->createQueryBuilder( $this->options->getDocument() )
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
            return null;
        }

        // Add job ID to meta data
        return $this->unserializeJob($document->data, array('__id__' => $document->id));
    }

    /**
     * {@inheritDoc}
     *
     * Note: When $deletedLifetime === 0 the job will be deleted immediately
     */
    public function delete(JobInterface $job, array $options = array())
    {
        if ($this->options->getDeletedLifetime() === static::LIFETIME_DISABLED) {
            $this->dm->createQueryBuilder( $this->options->getDocument() )
                ->remove()
                ->field('id')->equals($job->getId())

                ->getQuery()->execute();
        } else {
            $document =  $this->dm->createQueryBuilder( $this->options->getDocument() )
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
        if ($this->options->getBuriedLifetime() === static::LIFETIME_DISABLED) {
            $this->dm->createQueryBuilder( $this->options->getDocument() )
                ->remove()
                ->field('id')->equals($job->getId())

                ->getQuery()->execute();
        } else {
            $message = isset($options['message']) ? $options['message'] : null;
            $trace   = isset($options['trace']) ? $options['trace'] : null;

            $this->dm->createQueryBuilder( $this->options->getDocument() )
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

        $documents = $this->dm->createQueryBuilder( $this->options->getDocument() )
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
        $document = $this->dm->getRepository( $this->options->getDocument() )->find($id);

        $this->dm->clear();

        if (!$document) {
            throw new Exception\JobNotFoundException(sprintf("Job with id '%s' does not exists.", $id));
        }

        // Add job ID to meta data
        return $this->unserializeJob($document->data, array('__id__' => $document->id));
    }

    /**
     * Reschedules a specific running job
     *
     * Note : see DoctrineQueue::parseOptionsToDateTime for schedule and delay options
     *
     * @param  JobInterface             $job
     * @param  array                    $options
     * @throws Exception\LogicException
     */
    public function release(JobInterface $job, array $options = array())
    {
        $scheduled = $this->parseOptionsToDateTime($options);

        $document =  $this->dm->createQueryBuilder( $this->options->getDocument() )
            ->findAndUpdate()
            ->field('id')->equals($job->getId())
            ->field('status')->equals(static::STATUS_RUNNING)

            ->field('status')->set(static::STATUS_PENDING)
            ->field('finished')->set(new MongoDate)
            ->field('scheduled')->set($scheduled)
            ->field('data')->set($this->serializeJob($job))

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
                    $scheduled = new DateTime(
                        sprintf("@%d", (int) $options['scheduled']),
                        new DateTimeZone(date_default_timezone_get())
                    );
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
        if ($this->options->getBuriedLifetime() > static::LIFETIME_UNLIMITED) {
            $options = array('delay' => - ($this->options->getBuriedLifetime() * 60));
            $buriedLifetime = $this->parseOptionsToDateTime($options);

            $this->dm->createQueryBuilder( $this->options->getDocument() )
                ->remove()
                ->field('finished')->lt($buriedLifetime)
                ->field('status')->equals(static::STATUS_BURIED)
                ->field('queue')->equals($this->getName())
                ->field('finished')->exists(true)

                ->getQuery()->execute();
        }

        if ($this->options->getDeletedLifetime() > static::LIFETIME_UNLIMITED) {
            $options = array('delay' => - ($this->options->getDeletedLifetime() * 60));
            $deletedLifetime = $this->parseOptionsToDateTime($options);

            $this->dm->createQueryBuilder( $this->options->getDocument() )
                ->remove()
                ->field('finished')->lt($deletedLifetime)
                ->field('status')->equals(static::STATUS_DELETED)
                ->field('queue')->equals($this->getName())
                ->field('finished')->exists(true)

                ->getQuery()->execute();
        }
    }
}
