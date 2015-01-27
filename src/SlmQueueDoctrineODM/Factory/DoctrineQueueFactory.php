<?php

namespace SlmQueueDoctrineODM\Factory;

use SlmQueueDoctrineODM\Options\DoctrineOptions;
use SlmQueueDoctrineODM\Queue\DoctrineQueue;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * DoctrineQueueFactory
 */
class DoctrineQueueFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $name = '', $requestedName = '')
    {
        $parentLocator = $serviceLocator->getServiceLocator();

        $config        = $parentLocator->get('Config');
        $queuesOptions = $config['slm_queue']['queues'];
        $options       = isset($queuesOptions[$requestedName]) ? $queuesOptions[$requestedName] : array();
        $queueOptions  = new DoctrineOptions($options);

        /** @var $documentManager \Doctrine\ODM\MongoDB\DocumentManager */
        $documentManager  = $parentLocator->get($queueOptions->getDocumentManager());
        $jobPluginManager = $parentLocator->get('SlmQueue\Job\JobPluginManager');

        $queue = new DoctrineQueue($documentManager, $queueOptions, $requestedName, $jobPluginManager);

        return $queue;
    }
}
