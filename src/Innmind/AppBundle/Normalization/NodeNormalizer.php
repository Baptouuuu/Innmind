<?php

namespace Innmind\AppBundle\Normalization;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;

class NodeNormalizer implements NormalizerInterface
{
    public function normalize($node)
    {
        if (!($node instanceof Node)) {
            return [];
        }

        $data = [];

        foreach ($node->getProperties() as $property => $value) {
            $data[$property] = $value;
        }

        $data['labels'] = [];

        foreach ($node->getLabels() as $label) {
            $data['labels'][] = $label->getName();
        }

        $data['relations'] = [];

        foreach ($node->getRelationships() as $relation) {
            $r = [
                'direction' => $relation->getStartNode()->getProperty('uuid') === $node->getProperty('uuid') ?
                    Relationship::DirectionOut :
                    Relationship::DirectionIn,
                'endNode' => $relation->getEndNode()->getProperty('uuid'),
                'startNode' => $relation->getStartNode()->getProperty('uuid'),
                'uuid' => $relation->getProperty('uuid'),
                'type' => $relation->getType(),
                'properties' => [],
            ];

            foreach ($relation->getProperties() as $property => $value) {
                $r['properties'][$property] = $value;
            }

            $data['relations'][] = $r;
        }

        return $data;
    }
}
