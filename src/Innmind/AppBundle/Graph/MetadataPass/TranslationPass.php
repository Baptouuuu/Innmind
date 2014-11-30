<?php

namespace Innmind\AppBundle\Graph\MetadataPass;

use Everyman\Neo4j\Node;
use Symfony\Component\HttpFoundation\ParameterBag;
use Innmind\AppBundle\Graph\MetadataPassInterface;
use Innmind\AppBundle\Graph;
use Innmind\AppBundle\Graph\NodeRelations;

/**
 * Look if there's a translation relation between the node and it's referer
 */

class TranslationPass implements MetadataPassInterface
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
        if (
            empty($referer) ||
            !$data->has('translations') ||
            !$data->has('language')
        ) {
            return;
        }

        $translations = $data->get('translations');

        if (!in_array($referer, $translations, true)) {
            return;
        }

        try {
            $referer = $this->graph->getNodeByProperty('uri', $referer);
        } catch (\Exception $e) {
            return;
        }

        $relations = $node->getRelationships(NodeRelations::TRANSLATE);

        foreach ($relations as $rel) {
            if (
                $rel->getProperty('language') === $data->get('language') &&
                $rel->getEndNode() === $referer
            ) {
                return;
            }
        }

        $this->graph
            ->createRelation(
                $node,
                $referer
            )
            ->setType(NodeRelations::TRANSLATE)
            ->setProperty('language', $data->get('language'))
            ->save();
    }
}
