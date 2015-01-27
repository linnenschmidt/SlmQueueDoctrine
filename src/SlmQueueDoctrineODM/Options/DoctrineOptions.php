<?php

namespace SlmQueueDoctrineODM\Options;

use SlmQueueDoctrineODM\Queue\DoctrineQueue;
use Zend\Stdlib\AbstractOptions;

/**
 * DoctrineOptions
 */
class DoctrineOptions extends AbstractOptions
{
    /**
     * documentManager service which should be used
     *
     * @var string
     */
    protected $documentManager = 'doctrine.documentmanager.odm_default';

    /**
     * Document which should be used to store jobs
     *
     * @var string
     */
    protected $document = 'SlmQueueDoctrineODM\Document\QueueDefault';

    /**
     * how long to keep deleted (successful) jobs (in minutes)
     *
     * @var int
     */
    protected $deletedLifetime = DoctrineQueue::LIFETIME_DISABLED;

    /**
     * how long to keep buried (failed) jobs (in minutes)
     *
     * @var int
     */
    protected $buriedLifetime = DoctrineQueue::LIFETIME_DISABLED;

    /**
     * Set the name of the doctrine documentManager service
     *
     * @param  string $documentManager
     * @return void
     */
    public function setDocumentManager($documentManager)
    {
        $this->documentManager = $documentManager;
    }

    /**
     * Get the documentManager service name
     *
     * @return string
     */
    public function getDocumentManager()
    {
        return $this->documentManager;
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
