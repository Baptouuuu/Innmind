<?php

namespace Innmind\AppBundle\Graph\MetadataPass;

use Everyman\Neo4j\Node;
use Symfony\Component\HttpFoundation\ParameterBag;
use Innmind\AppBundle\Graph\MetadataPassInterface;
use Innmind\AppBundle\Graph;
use Innmind\AppBundle\Graph\NodeRelations;

/**
 * Look if there's a host node refering to this resource
 */

class HostPass implements MetadataPassInterface
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
        $domain = $data->get('domain');

        $hostNode = $this->graph->query(
            'MATCH (n:Host) WHERE n.domain = {domain} RETURN n;',
            ['domain' => $domain]
        );

        if ($hostNode->count() === 0) {
            $host = $this->graph->createNode(
                ['Host'],
                [
                    'domain' => $data->get('domain'),
                    'tld' => $data->get('tld'),
                ]
            );

            $relation = $this->graph->createRelation(
                $node,
                $host
            );

            $relation
                ->setType(NodeRelations::BELONGS_TO)
                ->save();
        }
    }
}
