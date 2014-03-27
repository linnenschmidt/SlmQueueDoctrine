<?php

return array(
    'service_manager' => array(
        'factories' => array(
            'SlmQueueDoctrineODM\Options\DoctrineODMOptions'  => 'SlmQueueDoctrineODM\Factory\DoctrineODMOptionsFactory',
            'SlmQueueDoctrineODM\Worker\DoctrineODMWorker'    => 'SlmQueueDoctrineODM\Factory\DoctrineODMWorkerFactory',
        )
    ),

    'controllers' => array(
        'factories' => array(
            'SlmQueueDoctrineODM\Controller\DoctrineODMWorkerController' => 'SlmQueueDoctrineODM\Factory\DoctrineODMWorkerControllerFactory',
        ),
    ),

    'console'   => array(
        'router' => array(
            'routes' => array(
                'slm-queue-doctrine-odm-worker' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine-odm <queue> [--timeout=] --start',
                        'defaults' => array(
                            'controller' => 'SlmQueueDoctrineODM\Controller\DoctrineODMWorkerController',
                            'action'     => 'process'
                        ),
                    ),
                ),
                'slm-queue-doctrine-odm-recover' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine-odm <queue> --recover [--executionTime=]',
                        'defaults' => array(
                            'controller' => 'SlmQueueDoctrineODM\Controller\DoctrineODMWorkerController',
                            'action'     => 'recover'
                        ),
                    ),
                ),
            ),
        ),
    ),

    'slm_queue' => array(
        'doctrine' => array(
            'documentmanager' => 'doctrine.documentmanager.odm_default',
            'document' => 'SlmQueueDoctrineODM\Document\Task',
            'buried_lifetime' => -1, // Stay alive
            'deleted_lifetime' => 60, // Delete after 60 minute
        ),
    ),
);
