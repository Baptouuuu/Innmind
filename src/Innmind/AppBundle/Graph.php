<?php

namespace Innmind\AppBundle;

use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
use Everyman\Neo4j\Client;
use Everyman\Neo4j\Cypher\Query;
use Everyman\Neo4j\Query\ResultSet;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Innmind\AppBundle\Graph\Exception\ZeroNodeFoundException;
use Innmind\AppBundle\Graph\Exception\MoreThanOneNodeFoundException;
use Innmind\AppBundle\Graph\NodeEvents;
use Innmind\AppBundle\Graph\NodeEvent;

class Graph
{
    protected $client;
    protected $generator;
    protected $dispatcher;

    /**
     * Set the neo4j client
     *
     * @param Client $client
     */

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Set a uuid generator
     *
     * @param UUID $generator
     */

    public function setGenerator(UUID $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Set the event dispatcher
     *
     * @param EventDispatcherInterface $dispatcher
     */

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Create a new node in the graph with the specified data
     *
     * @param array $labels
     * @param array $properties
     *
     * @return Node
     */

    public function createNode(array $labels, array $properties)
    {
        $existing = $this->query(
            'MATCH (n) WHERE n.uri = {uri} RETURN n',
            ['uri' => $properties['uri']]
        );

        if ($existing->count() !== 0) {
            $node = $existing[0]['n'];
        } else {
            $node = $this->client->makeNode();
            $uuid = $this->generator->generate();
            $exist = true;

            while ($exist === true) {
                try {
                    $this->getNodeByUUID($uuid);
                    $uuid = $this->generator->generate();
                } catch (ZeroNodeFoundException $e) {
                    $exist = false;
                } catch (\Exception $e) {
                    $uuid = $this->generator->generate();
                }
            }

            $node->setProperty('uuid', $uuid);
        }

        foreach ($properties as $property => $value) {
            $node->setProperty(strtolower($property), $value);
        }

        $event = new NodeEvent($node, $labels, $properties);
        $this->dispatcher->dispatch(NodeEvents::PRE_SAVE, $event);

        $node->save();

        $l = [];
        foreach ($labels as $label) {
            $l[] = $this->client->makeLabel($label);
        }

        $node->addLabels($l);
        $node->save();

        $this->dispatcher->dispatch(NodeEvents::POST_SAVE, $event);

        return $node;
    }

    /**
     * Retrieve a node by its uuid
     *
     * @param string $uuid
     *
     * @return Node
     */

    public function getNodeByUUID($uuid)
    {
        $results = $this->query('MATCH (n) WHERE n.uuid = {uuid} RETURN n;', ['uuid' => $uuid]);

        if ($results->count() === 1) {
            return $results[0]['n'];
        }

        if ($results->count() === 0) {
            throw new ZeroNodeFoundException();
        } else {
            throw new MoreThanOneNodeFoundException();
        }
    }

    /**
     * Return node by property
     *
     * @param string $property
     * @param mixed $value
     *
     * @return Node
     */

    public function getNodeByProperty($property, $value)
    {
        $results = $this->query(
            sprintf(
                'MATCH (n) WHERE n.%s = {value} RETURN n;',
                (string) $property
            ),
            ['value' => $value]
        );

        if ($results->count() === 1) {
            return $results[0]['n'];
        }

        if ($results->count() === 0) {
            throw new ZeroNodeFoundException();
        } else {
            throw new MoreThanOneNodeFoundException();
        }
    }

    /**
     * Send a cypher query to the client
     *
     * @param string $query
     * @param array $params
     *
     * @return ResultSet
     */

    public function query($query, array $params = [])
    {
        return (new Query($this->client, $query, $params))->getResultSet();
    }

    /**
     * Create a new relationship (but does not save it)
     *
     * @param Node $from
     * @param Node $to
     *
     * @return Relationship
     */

    public function createRelation(Node $from, Node $to)
    {
        return $this
            ->client
            ->makeRelationship()
            ->setStartNode($from)
            ->setEndNode($to)
            ->setProperty('uuid', $this->generator->generate());
    }
}
