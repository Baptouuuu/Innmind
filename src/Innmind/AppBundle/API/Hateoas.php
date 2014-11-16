<?php

namespace Innmind\AppBundle\API;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Everyman\Neo4j\Relationship;

/**
 * Adds links to related resources
 */

class Hateoas
{
    protected $generator;

    /**
     * Set the url generator
     *
     * @param UrlGeneratorInterface $generator
     */

    public function setGenerator(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Add resource links for a node
     *
     * @param array $node
     *
     * @return array
     */

    public function handleNode(array $node)
    {
        $links = [];

        $links[] = [
            'rel' => 'self',
            'href' => $this->generator->generate(
                'api_node_get',
                ['uuid' => $node['uuid']],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
        ];

        foreach ($node['relations'] as $relation) {
            if ($relation['direction'] === Relationship::DirectionOut) {
                $links[] = [
                    'rel' => $relation['type'],
                    'href' => $this->generator->generate(
                        'api_node_get',
                        $relation['endNode'],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    )
                ];
            }
        }

        $node['_links'] = $links;

        return $node;
    }
}
