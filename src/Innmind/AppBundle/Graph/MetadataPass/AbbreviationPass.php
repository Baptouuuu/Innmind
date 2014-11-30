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
                $result = $this->graph->query(
                    'MATCH (n:Abbreviation) WHERE n.abbreviation = {abbr} and n.description = {desc} RETURN n;',
                    [
                        'abbr' => $abbr,
                        'desc' => $desc,
                    ]
                );

                if ($result->count() === 0) {
                    throw new ZeroNodeFoundException;
                } else {
                    $abbr = $result[0]['n'];
                }
            } catch (ZeroNodeFoundException $e) {
                $abbr = $this->graph->createNode(
                    ['Abbreviation'],
                    [
                        'abbreviation' => $abbr,
                        'description' => $desc,
                    ]
                );
            }

            $abbrNodes[$abbr->getId()] = $abbr;
        }

        $relations = $node->getRelationships(NodeRelations::CONTAINS);
        $relationNodes = [];

        foreach ($relations as $rel) {
            $endNode = $rel->getEndNode();

            $relationNodes[$endNode->getId()] = $endNode;
        }

        foreach ($abbrNodes as $abbr) {
            if (!isset($relationNodes[$abbr->getId()])) {
                $this->graph
                    ->createRelation(
                        $node,
                        $abbr
                    )
                    ->setType(NodeRelations::CONTAINS)
                    ->save();
            }
        }
    }
}
