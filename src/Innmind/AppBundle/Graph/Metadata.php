<?php

namespace Innmind\AppBundle\Graph;

use Everyman\Neo4j\Node;
use Symfony\Component\HttpFoundation\ParameterBag;

class Metadata
{
    protected $passes = [];

    /**
     * Add a pass to compute node metadata
     *
     * @param MetadataPassInterface $pass
     */

    public function addPass(MetadataPassInterface $pass)
    {
        $this->passes[] = $pass;
    }

    /**
     * Compute all the metadata for the given node
     *
     * @param Node $node
     * @param ParameterBag $data
     * @param string $referer
     */

    public function compute(Node $node, ParameterBag $data, $referer)
    {
        foreach ($this->passes as $pass) {
            $pass->process($node, $data, $referer);
        }
    }
}
