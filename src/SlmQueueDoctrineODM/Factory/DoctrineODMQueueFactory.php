<?php

namespace SlmQueueDoctrineODM\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SlmQueueDoctrineODM\Queue\DoctrineODMQueue;

/**
 * DoctrineODMQueueFactory
 */
class DoctrineODMQueueFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $name = '', $requestedName = '')
    {
        $parentLocator = $serviceLocator->getServiceLocator();

        /** @var $DoctrineODMOptions \SlmQueueDoctrineODM\Options\DoctrineODMOptions */
        $DoctrineODMOptions = $parentLocator->get('SlmQueueDoctrineODM\Options\DoctrineODMOptions');

        /** @var $dm \Doctrine\ODM\MongoDB\DocumentManager */
        $documentManager  = $parentLocator->get($DoctrineODMOptions->getDocumentManager());
        $document         = $DoctrineODMOptions->getDocument();
        $jobPluginManager = $parentLocator->get('SlmQueue\Job\JobPluginManager');

        $queue = new DoctrineODMQueue($documentManager, $document, $requestedName, $jobPluginManager);

        $config = $parentLocator->get('Config');
        $options = isset($config['slm_queue']['queues'][$requestedName]) ? $config['slm_queue']['queues'][$requestedName] : array();

        if (isset($options['sleep_when_idle'])) {
            $queue->setSleepWhenIdle($options['sleep_when_idle']);
        }

        $queue->setBuriedLifetime($DoctrineODMOptions->getBuriedLifetime());
        $queue->setDeletedLifetime($DoctrineODMOptions->getDeletedLifetime());

        return $queue;
    }
}
