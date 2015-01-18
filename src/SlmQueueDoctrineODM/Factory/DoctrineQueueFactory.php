<?php

namespace SlmQueueDoctrineODM\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SlmQueueDoctrineODM\Queue\DoctrineQueue;

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

        /** @var $DoctrineOptions \SlmQueueDoctrineODM\Options\DoctrineOptions */
        $DoctrineOptions = $parentLocator->get('SlmQueueDoctrineODM\Options\DoctrineOptions');

        /** @var $dm \Doctrine\ODM\MongoDB\DocumentManager */
        $documentManager  = $parentLocator->get($DoctrineOptions->getDocumentManager());
        $document         = $DoctrineOptions->getDocument();
        $jobPluginManager = $parentLocator->get('SlmQueue\Job\JobPluginManager');

        $queue = new DoctrineQueue($documentManager, $document, $requestedName, $jobPluginManager);

        $config = $parentLocator->get('Config');
        $options = isset($config['slm_queue']['queues'][$requestedName]) ? $config['slm_queue']['queues'][$requestedName] : array();

        if (isset($options['sleep_when_idle'])) {
            $queue->setSleepWhenIdle($options['sleep_when_idle']);
        }

        $queue->setBuriedLifetime($DoctrineOptions->getBuriedLifetime());
        $queue->setDeletedLifetime($DoctrineOptions->getDeletedLifetime());

        return $queue;
    }
}
