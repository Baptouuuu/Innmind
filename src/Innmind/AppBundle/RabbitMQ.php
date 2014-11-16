<?php

namespace Innmind\AppBundle;

use OldSound\RabbitMqBundle\RabbitMq\Producer;

/**
 * Old sound rabbitmq wrapper
 */
class RabbitMQ
{
    protected $producers = [];

    /**
     * Add a producer
     *
     * @param string $name
     * @param Producer $producer
     */

    public function addProducer($name, Producer $producer)
    {
        $this->producers[(string) $name] = $producer;
    }

    /**
     * Check if a producer is set
     *
     * @param string $name
     *
     * @return bool
     */

    public function hasProducer($name)
    {
        return array_key_exists($name, $this->producers);
    }

    /**
     * Return a producer
     *
     * @param string $name
     *
     * @return Producer
     */

    public function getProducer($name)
    {
        return $this->producers[$name];
    }
}
