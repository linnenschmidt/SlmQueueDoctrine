<?php

namespace SlmQueueDoctrineODM\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(db="Tasks",
 * collection={
 *   "name"="tasks"
 * })
 */
class Task {

    /** @ODM\Id */
    protected $id;

    /** @ODM\Int */
    protected $status;

    /** @ODM\Date */
    protected $created;

    /** @ODM\Date */
    protected $scheduled;

    /** @ODM\Date */
    protected $executed;

    /** @ODM\String */
    protected $queue;

    /** @ODM\String */
    protected $message;

    /** @ODM\String */
    protected $data;

    /**
     * Set status
     *
     * @param int $status
     */
    public function setStatus($status){
        $this->status = $status;
    }

     /**
     * Set created date
     *
     * @param \MongoDate $created
     */
    public function setCreated(\MongoDate $created){
        $this->created = $created;
    }

     /**
     * Set scheduled date
     *
     * @param \MongoDate $scheduled
     */
    public function setScheduled(\MongoDate $scheduled){
        $this->scheduled = $scheduled;
    }

    /**
     * Set executed date
     *
     * @param \MongoDate $executed
     */
    public function setExecuted(\MongoDate $executed){
        $this->executed = $executed;
    }

    /**
     * Set queue name
     *
     * @param strng $queue
     */
    public function setQueue($queue){
        $this->queue = $queue;
    }

    /**
     * Set the message
     *
     * @param $message
     */
    public function setMessage($message){
        $this->message = $message;
    }

    /**
     * Set serialized data
     *
     * @param $data
     */
    public function setData($data){
        $this->data = $data;
    }

    /**
     * Get the Task id
     */
    public function getId(){
        return $this->id;
    }

    /**
     * Magic getter to expose private properties
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property) {
        return $this->$property;
    }

     /**
     * Magic setter to save private properties
     *
     * @param string $property
     * @param mixed value
     */
    public function __set($property, $value) {
        $this->$property = $value;
    }
}
