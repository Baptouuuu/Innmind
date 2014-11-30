<?php

namespace Innmind\AppBundle\Graph\MetadataPass;

use Everyman\Neo4j\Node;
use Symfony\Component\HttpFoundation\ParameterBag;
use Innmind\AppBundle\Graph\MetadataPassInterface;
use Innmind\AppBundle\Graph;
use Innmind\AppBundle\Graph\NodeRelations;
use Innmind\AppBundle\Graph\Exception\ZeroNodeFoundException;

/**
 * Create dedicated nodes for each citation
 */

class CitationPass implements MetadataPassInterface
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
        if (!$data->has('citations')) {
            return;
        }

        $citations = $data->get('citations');
        $citationNodes = [];

        foreach ($citations as $cite) {
            try {
                $node = $this->graph->query(
                    'MATCH (n:Citation) WHERE n.content = {cite} RETURN n;',
                    ['cite' => $cite]
                );

                if ($node->count() === 0) {
                    throw new ZeroNodeFoundException;
                }
            } catch (ZeroNodeFoundException $e) {
                $node = $this->graph->createNode(
                    ['Citation'],
                    ['content' => $cite]
                );
            }

            $citationNodes[$node->getId()] = $node;
        }

        $relations = $node->getRelationships(NodeRelations::CONTAINS);

        foreach ($relations as $rel) {
            $endNodeId = $rel->getEndNode()->getId();

            if (!isset($citationNodes[$endNodeId])) {
                $this->graph
                    ->createRelation(
                        $node,
                        $citationNodes[$endNodeId]
                    )
                    ->setType(NodeRelations::CONTAINS)
                    ->save();
            }
        }
    }
}
