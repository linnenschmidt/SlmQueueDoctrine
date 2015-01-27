<?php

namespace SlmQueueDoctrineODM\Factory;

use SlmQueueDoctrineODM\Controller\DoctrineWorkerController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * WorkerFactory
 */
class DoctrineWorkerControllerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $serviceLocator     = $serviceLocator->getServiceLocator();
        $worker             = $serviceLocator->get('SlmQueueDoctrineODM\Worker\DoctrineWorker');
        $queuePluginManager = $serviceLocator->get('SlmQueue\Queue\QueuePluginManager');

        return new DoctrineWorkerController($worker, $queuePluginManager);
    }
}
