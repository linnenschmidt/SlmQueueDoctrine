<?php

namespace SlmQueueDoctrineODM\Factory;

use SlmQueueDoctrineODM\Controller\DoctrineODMWorkerController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * WorkerFactory
 */
class DoctrineODMWorkerControllerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $worker = $serviceLocator->getServiceLocator()
                                 ->get('SlmQueueDoctrineODM\Worker\DoctrineODMWorker');

        return new DoctrineODMWorkerController($worker);
    }
}
