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
                $existing = $this->graph->getNodeByProperty(
                    'uri',
                    $request->get('uri')
                );
                $this->graph->updateNode(
                    $existing->getProperty('uuid'),
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

        if ($referer !== null) {
            $referer = $this->graph->getNodeByProperty(
                'uri',
                $referer
            );

            $relations = $referer->getRelations();
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
