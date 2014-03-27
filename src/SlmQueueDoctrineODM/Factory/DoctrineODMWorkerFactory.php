<?php
namespace SlmQueueDoctrineODM\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SlmQueueDoctrineODM\Worker\DoctrineODMWorker;

/**
 * WorkerFactory
 */
class DoctrineODMWorkerFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $workerOptions      = $serviceLocator->get('SlmQueue\Options\WorkerOptions');
        $queuePluginManager = $serviceLocator->get('SlmQueue\Queue\QueuePluginManager');

        return new DoctrineODMWorker($queuePluginManager, $workerOptions);
    }
}
