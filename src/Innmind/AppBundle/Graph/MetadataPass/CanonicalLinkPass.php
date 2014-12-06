<?php

namespace Innmind\AppBundle\Graph\MetadataPass;

use Everyman\Neo4j\Node;
use Symfony\Component\HttpFoundation\ParameterBag;
use Innmind\AppBundle\Graph\MetadataPassInterface;
use Innmind\AppBundle\Graph;
use Innmind\AppBundle\Graph\NodeRelations;

/**
 * Look if there's a canonical link for the page
 */

class CanonicalLinkPass implements MetadataPassInterface
{
    protected $graph;

    /**
     * Set the graph wrapper
     *
     * @param Graph $graph
     */

    public function setGraph(Graph $graph)
    {
        $this->graph = $graph;
    }

    /**
     * {@inheritdoc}
     */

    public function process(Node $node, ParameterBag $data, $referer)
    {
        if (!$data->has('canonical')) {
            return;
        }

        try {
            $canonNode = $this->graph->getNodeByProperty(
                'uri',
                $data->get('canonical')
            );

            $this->graph
                ->createRelation(
                    $node,
                    $canonNode
                )
                ->setType(NodeRelations::CANONICAL)
                ->save();
        } catch (\Exception $e) {

        }
    }
}
