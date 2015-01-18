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
        $worker = $serviceLocator->getServiceLocator()
                                 ->get('SlmQueueDoctrineODM\Worker\DoctrineWorker');

        return new DoctrineWorkerController($worker);
    }
}
