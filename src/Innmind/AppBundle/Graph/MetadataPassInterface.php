<?php

namespace Innmind\AppBundle\Graph;

use Everyman\Neo4j\Node;
use Symfony\Component\HttpFoundation\ParameterBag;

interface MetadataPassInterface
{
    /**
     * Look for valuable information from the node data
     * to be inserted in the graph
     *
     * @param Node $node
     * @param ParameterBag $data
     * @param string $referer
     */

    public function process(Node $node, ParameterBag $data, $referer);
}
