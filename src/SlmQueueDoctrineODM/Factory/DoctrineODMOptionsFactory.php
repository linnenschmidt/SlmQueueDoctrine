<?php

namespace SlmQueueDoctrineODM\Factory;

use SlmQueueDoctrineODM\Options\DoctrineODMOptions;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * DoctrineODMOptionsFactory
 */
class DoctrineODMOptionsFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');

        return new DoctrineODMOptions($config['slm_queue']['doctrine']);
    }
}
