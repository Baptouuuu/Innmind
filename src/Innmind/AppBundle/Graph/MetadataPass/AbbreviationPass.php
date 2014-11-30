<?php

namespace Innmind\AppBundle\Graph\MetadataPass;

use Everyman\Neo4j\Node;
use Symfony\Component\HttpFoundation\ParameterBag;
use Innmind\AppBundle\Graph\MetadataPassInterface;
use Innmind\AppBundle\Graph;
use Innmind\AppBundle\Graph\NodeRelations;
use Innmind\AppBundle\Graph\Exception\ZeroNodeFoundException;

/**
 * Create dedicated nodes for each abbreviation
 */

class AbbreviationPass implements MetadataPassInterface
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
        if (!$data->has('abbreviations')) {
            return;
        }

        $abbrs = $data->get('abbreviations');
        $abbrNodes = [];

        foreach ($abbrs as $abbr => $desc) {
            try {
                $node = $this->graph->query(
                    'MATCH (n:Abbreviation) WHERE n.abbreviation = {abbr} and n.description = {desc} RETURN n;',
                    [
                        'abbr' => $abbr,
                        'desc' => $desc,
                    ]
                );

                if ($node->count() === 0) {
                    throw new ZeroNodeFoundException;
                }
            } catch (ZeroNodeFoundException $e) {
                $node = $this->graph->createNode(
                    ['Abbreviation'],
                    [
                        'abbreviation' => $abbr,
                        'description' => $desc,
                    ]
                );
            }

            $abbrNodes[$node->getId()] = $node;
        }

        $relations = $node->getRelationships(NodeRelations::CONTAINS);

        foreach ($relations as $rel) {
            $endNodeId = $rel->getEndNode()->getId();

            if (!isset($abbrNodes[$endNodeId])) {
                $this->graph
                    ->createRelation(
                        $node,
                        $abbrNodes[$endNodeId]
                    )
                    ->setType(NodeRelations::CONTAINS)
                    ->save();
            }
        }
    }
}
