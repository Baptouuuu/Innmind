<?php

namespace Innmind\AppBundle\Graph;

use Innmind\AppBundle\Graph as GraphWrapper;
use Innmind\AppBundle\LabelGuesser;
use Innmind\AppBundle\Graph\Exception\ZeroNodeFoundException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Helps to publish/update a node in the graph
 */

class NodePublisher
{
    protected $graph;
    protected $labelGuesser;
    protected $metadata;

    /**
     * Set the graph wrapper
     *
     * @param GraphWrapper $graph
     */

    public function setGraph(GraphWrapper $graph)
    {
        $this->graph = $graph;
    }

    /**
     * Set the label guesser
     *
     * @param LabelGuesser $guesser
     */

    public function setLabelGuesser(LabelGuesser $guesser)
    {
        $this->labelGuesser = $guesser;
    }

    /**
     * Set an helper to compute all metadata around a node
     *
     * @param Metadata $metadata
     */

    public function setMetadata(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Save the data to the graph
     *
     * @param ParameterBag $request
     * @param string $referer
     * @param string $uuid
     *
     * @return Node The node saved
     */

    public function save(ParameterBag $request, $referer = null, $uuid = null)
    {
        $labels = $this->labelGuesser->guess($request);

        if ($uuid === null) {
            try {
                $node = $this->graph->getNodeByProperty(
                    'uri',
                    $request->get('uri')
                );
                $this->graph->updateNode(
                    $node->getProperty('uuid'),
                    $labels,
                    $request->all()
                );
            } catch (ZeroNodeFoundException $e) {
                $node = $this->graph->createNode(
                    $labels,
                    $request->all()
                );
            }
        } else {
            $node = $this->graph->updateNode(
                $uuid,
                $labels,
                $request->all()
            );
        }

        $this->metadata->compute(
            $node,
            $request,
            $referer
        );

        if ($referer !== null) {
            $referer = $this->graph->getNodeByProperty(
                'uri',
                $referer
            );

            $relations = $referer->getRelationships();
            $hasRelation = false;

            foreach ($relations as $relation) {
                if (
                    $relation->getStartNode() === $referer &&
                    $relation->getEndNode() === $node &&
                    $relation->getType() === NodeRelations::REFERER
                ) {
                    $hasRelation = true;
                    $refererRelation = $relation;
                    break;
                }
            }

            if ($hasRelation === true) {
                if ($node->getProperty('domain') !== $referer->getProperty('domain')) {
                    $weight = $refererRelation->getProperty();
                    $refererRelation
                        ->setProperty('weight', $weight++)
                        ->save();
                }
            } else {
                $this->graph
                    ->createRelation(
                        $referer,
                        $node
                    )
                    ->setType(NodeRelations::REFERER)
                    ->setProperty('weight', 1)
                    ->save();
            }
        }

        return $node;
    }
}
