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
                $result = $this->graph->query(
                    'MATCH (n:Citation) WHERE n.content = {cite} RETURN n;',
                    ['cite' => $cite]
                );

                if ($result->count() === 0) {
                    throw new ZeroNodeFoundException;
                } else {
                    $cite = $result[0]['n'];
                }
            } catch (ZeroNodeFoundException $e) {
                $cite = $this->graph->createNode(
                    ['Citation'],
                    ['content' => $cite]
                );
            }

            $citationNodes[$cite->getId()] = $cite;
        }

        $relations = $node->getRelationships(NodeRelations::CONTAINS);
        $relationNodes = [];

        foreach ($relations as $rel) {
            $endNode = $rel->getEndNode();

            $relationNodes[$endNode->getId()] = $endNode;
        }

        foreach ($citationNodes as $cite) {
            if (!isset($relationNodes[$cite->getId()])) {
                $this->graph
                    ->createRelation(
                        $node,
                        $cite
                    )
                    ->setType(NodeRelations::CONTAINS)
                    ->save();
            }
        }
    }
}
