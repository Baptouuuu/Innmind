<?php

namespace Innmind\AppBundle\Graph;

use Everyman\Neo4j\Node

class NodeEvent
{
    protected $node;
    protected $labels;
    protected $properties;

    public function __construct(Node $node, array $labels, array $properties)
    {
        $this->node = $node;
        $this->labels = $labels;
        $this->properties = $properties;
    }

    /**
     * Return the node being created or updated
     *
     * @return Node
     */

    public function getNode()
    {
        return $this->node;
    }

    /**
     * Return the array of labels names associated to the node
     *
     * @return array
     */

    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * Return the array of properties associated to the node
     *
     * @return array
     */

    public function getProperties()
    {
        return $this->properties;
    }
}
