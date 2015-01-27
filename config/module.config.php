<?php

return array(
    'service_manager' => array(
        'factories' => array(
            'SlmQueueDoctrineODM\Worker\DoctrineWorker'    => 'SlmQueue\Factory\WorkerFactory',
        )
    ),

    'controllers' => array(
        'factories' => array(
            'SlmQueueDoctrineODM\Controller\DoctrineWorkerController' => 'SlmQueueDoctrineODM\Factory\DoctrineWorkerControllerFactory',
        ),
    ),

    'console'   => array(
        'router' => array(
            'routes' => array(
                'slm-queue-doctrine-worker' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queue> [--timeout=] --start',
                        'defaults' => array(
                            'controller' => 'SlmQueueDoctrineODM\Controller\DoctrineWorkerController',
                            'action'     => 'process'
                        ),
                    ),
                ),
                'slm-queue-doctrine-recover' => array(
                    'type'    => 'Simple',
                    'options' => array(
                        'route'    => 'queue doctrine <queue> --recover [--executionTime=]',
                        'defaults' => array(
                            'controller' => 'SlmQueueDoctrineODM\Controller\DoctrineWorkerController',
                            'action'     => 'recover'
                        ),
                    ),
                ),
            ),
        ),
    ),
    'slm_queue' => array(

        /**
         * Queues
         */
        'queues' => array(),

        /**
         * Worker Strategies
         */
        'worker_strategies' => array(
            'default' => array(
                'SlmQueueDoctrineODM\Strategy\IdleNapStrategy' => array('nap_duration' => 1),
                'SlmQueueDoctrineODM\Strategy\ClearObjectManagerStrategy'
            ),
            'queues' => array(
            ),
        ),
        /**
         * Strategy manager configuration
         */
        'strategy_manager' => array(
            'invokables' => array(
                'SlmQueueDoctrineODM\Strategy\IdleNapStrategy' => 'SlmQueueDoctrineODM\Strategy\IdleNapStrategy',
                'SlmQueueDoctrineODM\Strategy\ClearObjectManagerStrategy'
                => 'SlmQueueDoctrineODM\Strategy\ClearObjectManagerStrategy'
            )
        ),
    ),
);
