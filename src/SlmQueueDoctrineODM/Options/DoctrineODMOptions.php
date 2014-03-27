<?php

namespace SlmQueueDoctrineODM\Options;

use SlmQueueDoctrineODM\Queue\DoctrineODMQueue;
use Zend\Stdlib\AbstractOptions;

/**
 * DoctrineODMOptions
 */
class DoctrineODMOptions extends AbstractOptions
{
    /**
     * documentManager service which should be used
     *
     * @var string
     */
    protected $dm;

    /**
     * Document which should be used to store jobs
     *
     * @var string
     */
    protected $document;

    /**
     * how long to keep deleted (successful) jobs (in minutes)
     *
     * @var int
     */
    protected $deletedLifetime = DoctrineODMQueue::LIFETIME_DISABLED;

    /**
     * how long to keep buried (failed) jobs (in minutes)
     *
     * @var int
     */
    protected $buriedLifetime = DoctrineODMQueue::LIFETIME_DISABLED;

    /**
     * Set the name of the doctrine documentManager service
     *
     * @param  string $documentManager
     * @return void
     */
    public function setDocumentManager($documentManager)
    {
        $this->dm = $documentManager;
    }

    /**
     * Get the documentManager service name
     *
     * @return string
     */
    public function getDocumentManager()
    {
        return $this->dm;
    }

    /**
     * @param  int  $buriedLifetime
     * @return void
     */
    public function setBuriedLifetime($buriedLifetime)
    {
        $this->buriedLifetime = (int) $buriedLifetime;
    }

    /**
     * @return int
     */
    public function getBuriedLifetime()
    {
        return $this->buriedLifetime;
    }

    /**
     * @param  int  $deletedLifetime
     * @return void
     */
    public function setDeletedLifetime($deletedLifetime)
    {
        $this->deletedLifetime = (int) $deletedLifetime;
    }

    /**
     * @return int
     */
    public function getDeletedLifetime()
    {
        return $this->deletedLifetime;
    }

    /**
     * @param  string $document
     * @return void
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getDocument()
    {
        return $this->document;
    }
}
