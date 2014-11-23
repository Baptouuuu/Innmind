<?php

namespace Innmind\AppBundle\Normalization;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;

class NodeNormalizer implements NormalizerInterface
{
    const HIERARCHY_PATTERN = '/(\w+\.\w+\.?)+/';

    public function normalize($node)
    {
        if (!($node instanceof Node)) {
            return [];
        }

        $data = [];

        foreach ($node->getProperties() as $property => $value) {
            if ((bool) preg_match(self::HIERARCHY_PATTERN, $property)) {
                $data = array_replace_recursive(
                    $data,
                    $this->expand($property, $value)
                );
            } else {
                $data[$property] = $value;
            }
        }

        $data = $this->cleanIndexes($data);

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

    /**
     * Transform dotted property into an array structure
     *
     * @param string $property
     * @param mixed $value
     *
     * @return array
     */

    protected function expand($string, $value)
    {
        $data = [];

        list($parent, $child) = explode('.', $string, 2);

        $data[$parent] = [];

        if ((bool) preg_match(self::HIERARCHY_PATTERN, $child)) {
            $data[$parent] = array_replace(
                $data[$parent],
                $this->expand($child, $value)
            );
        } else {
            if (is_numeric($child)) {
                $child = (int) $child;
            }

            $data[$parent][$child] = $value;
        }

        return $data;
    }

    /**
     * Prevent interpreting numerical indexes as string key
     *
     * @param array $data
     */

    protected function cleanIndexes(array $data)
    {
        $numerical = true;

        foreach ($data as $key => &$value) {
            if (!is_numeric($key)) {
                $numerical = false;
            }

            if (is_array($value)) {
                $value = $this->cleanIndexes($value);
            }
        }

        if ($numerical === true) {
            $data = array_values($data);
        }

        return $data;
    }
}
