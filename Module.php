<?php

namespace SlmQueueDoctrineODM;

use Zend\Loader;
use Zend\Console\Adapter\AdapterInterface;
use Zend\ModuleManager\Feature;

/**
 * SlmQueueDoctrineODM
 */
class Module implements
    Feature\AutoloaderProviderInterface,
    Feature\ConfigProviderInterface,
    Feature\ConsoleBannerProviderInterface,
    Feature\ConsoleUsageProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            Loader\AutoloaderFactory::STANDARD_AUTOLOADER => array(
                Loader\StandardAutoloader::LOAD_NS => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * {@inheritDoc}
     */
    public function getConsoleBanner(AdapterInterface $console)
    {
        return 'SlmQueueDoctrineODM ' . Version::VERSION;
    }

    /**
     * {@inheritDoc}
     */
    public function getConsoleUsage(AdapterInterface $console)
    {
        return array(
            'queue doctrine-odm <queueName> --start' => 'Process the jobs',
            'queue doctrine-odm <queueName> --recover [--executionTime=]' => 'Recover long running jobs',

            array('<queueName>', 'Queue\'s name to process'),
            array('<executionTime>', 'Time (in minutes) after which the job gets recovered'),
        );
    }

    /**
     * This ModuleManager feature was introduced in ZF 2.1 to check if all the dependencies needed by a module
     * were correctly loaded. However, as we want to keep backward-compatibility with ZF 2.0, please DO NOT
     * explicitely implement Zend\ModuleManager\Feature\DependencyIndicatorInterface. Just write this method and
     * the module manager will automatically call it
     *
     * @return array
     */
    public function getModuleDependencies()
    {
        return array('DoctrineModule', 'DoctrineMongoODMModule', 'SlmQueue');
    }
}
