SlmQueueDoctrineODM
================

Migrated from SlmQueueDoctrine by Florian Linnenschmidt

Version
------------
0.2.0-dev

Requirements
------------
* [Zend Framework 2](https://github.com/zendframework/zf2)
* [SlmQueue](https://github.com/juriansluiman/SlmQueue)
* [Doctrine MongoDB ODM Module for Zend Framework 2](https://github.com/doctrine/DoctrineMongoODMModule)

Information
------------
This is a very early version of a ODM implementation. It is not performance oriented!

Installation
------------

First, install SlmQueue ([instructions here](https://github.com/juriansluiman/SlmQueue/blob/master/README.md)).

Then copy SlmQueueDoctrineODM into the project and enable the module by adding `SlmQueueDoctrineODM` in your application.config.php file.

Documentation
-------------

Before reading SlmQueueDoctrineODM documentation, please read [SlmQueue documentation](https://github.com/juriansluiman/SlmQueue).

### Configuring the connection

You need to register a doctrine connection which SlmQueueDoctrineODM will use to access the database into the document manager. Here is some more [information](https://github.com/doctrine/DoctrineMongoODMModule#connection-section).

Connection parameters can be defined in module.doctrine-mongo-odm.local.php:

```
<?php
return array(
    'doctrine' => array(

        'connection' => array(
            'odm_default' => array(
//                'server'           => 'localhost',
//                'port'             => '27017',
//                'connectionString' => null,
//                'user'             => null,
//                'password'         => null,
//                'dbname'           => null,
//                'options'          => array()
            ),
        ),

        'configuration' => array(
            'odm_default' => array(
//                'metadata_cache'     => 'array',
//
//                'driver'             => 'odm_default',
//
//                'generate_proxies'   => true,
//                'proxy_dir'          => 'data/DoctrineMongoODMModule/Proxy',
//                'proxy_namespace'    => 'DoctrineMongoODMModule\Proxy',
//
//                'generate_hydrators' => true,
//                'hydrator_dir'       => 'data/DoctrineMongoODMModule/Hydrator',
//                'hydrator_namespace' => 'DoctrineMongoODMModule\Hydrator',
//
//                'default_db'         => null,
//
//                'filters'            => array(),  // array('filterName' => 'BSON\Filter\Class'),
//
//                'logger'             => null // 'DoctrineMongoODMModule\Logging\DebugStack'
            )
        ),

        'driver' => array(
            'odm_default' => array(
//                'drivers' => array()
            )
        ),

        'documentmanager' => array(
            'odm_default' => array(
//                'connection'    => 'odm_default',
//                'configuration' => 'odm_default',
//                'eventmanager' => 'odm_default'
            )
        ),

        'eventmanager' => array(
            'odm_default' => array(
                'subscribers' => array()
            )
        ),
    ),
);
```

### Adding queues

```php
return array(
  'slm_queue' => array(
    'queue_manager' => array(
      'factories' => array(
        'foo' => 'SlmQueueDoctrineODM\Factory\DoctrineODMQueueFactory'
      )
    )
  )
);
```
### Adding jobs

```php
return array(
  'slm_queue' => array(
    'job_manager' => array(
      'factories' => array(
        'My\Job' => 'My\JobFactory'
      )
    )
  )
);

```
### Configuring queues

The following options can be set per queue ;

- document_manager (defaults to 'doctrine.documentmanager.odm_default') : Name of the registered doctrine connection service
- document (defaults to 'SlmQueueDoctrineODM\Document\QueueDefault') : Document class which should be used to store jobs
- delete_lifetime (defaults to 0) : How long to keep deleted (successful) jobs (in minutes)
- buried_lifetime (defaults to 0) : How long to keep buried (failed) jobs (in minutes)
- sleep_when_idle (defaults to 1) : How long show we sleep when no jobs available for processing (in seconds)


```php
return array(
  'slm_queue' => array(
    'queues' => array(
      'foo' => array(
        'sleep_when_idle' => 1
      )
    )
  )
);
 ```


### Operations on queues

#### push

Valid options are:

* scheduled: the time when the job will be scheduled to run next
	* numeric string or integer - interpreted as a timestamp
	* string parserable by the DateTime object
	* DateTime instance
* delay: the delay before a job become available to be popped (defaults to 0 - no delay -)
	* numeric string or integer - interpreted as seconds
	* string parserable (ISO 8601 duration) by DateTimeInterval::__construct
	* string parserable (relative parts) by DateTimeInterval::createFromDateString
	* DateTimeInterval instance

Examples:

	// scheduled for execution asap
    $queue->push($job);

	// scheduled for execution 2015-01-01 00:00:00 (system timezone applies)
    $queue->push($job, array(
        'scheduled' => 1420070400
    ));

    // scheduled for execution 2015-01-01 00:00:00 (system timezone applies)
    $queue->push($job, array(
        'scheduled' => '2015-01-01 00:00:00'
    ));

    // scheduled for execution at 2015-01-01 01:00:00
    $queue->push($job, array(
        'scheduled' => '2015-01-01 00:00:00',
        'delay' => 3600
    ));

    // scheduled for execution at now + 300 seconds
    $queue->push($job, array(
        'delay' => 'PT300S'
    ));

    // scheduled for execution at now + 2 weeks (1209600 seconds)
    $queue->push($job, array(
        'delay' => '2 weeks'
    ));

    // scheduled for execution at now + 300 seconds
    $queue->push($job, array(
        'delay' => new DateInterval("PT200S"))
    ));


### Worker actions

Interact with workers from the command line from within the public folder of your Zend Framework 2 application

#### Starting a worker
Start a worker that will keep monitoring a specific queue for jobs scheduled to be processed. This worker will continue until it has reached certain criteria (exceeds a memory limit or has processed a specified number of jobs).

`php index.php queue doctrine <queueName> --start`

A worker will exit when you press cntr-C *after* it has finished the current job it is working on. (PHP doesn't support signal handling on Windows)

You can let your script run indefinitely. While this was not possible in PHP versions previous to 5.3, it is now
not a big deal. This has the other benefit of not needing to bootstrap the application every time, which is good
for performance.
*

#### Recovering jobs

To recover jobs which are in the 'running' state for prolonged period of time (specified in minutes) use the following command.

`php index.php queue doctrine-odm <queueName> --recover [--executionTime=]`

*Note : Workers that are processing a job that is being recovered are NOT stopped.*
)
