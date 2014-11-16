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
use Psr\Log\LoggerInterface;

class Graph
{
    protected $client;
    protected $generator;
    protected $dispatcher;
    protected $logger;

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
     * Set the logger
     *
     * @param LoggerInterface $logger
     */

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        $node = $this->client->makeNode();
        $node->setProperty('uuid', $this->generator->generate());

        foreach ($properties as $property => $value) {
            $node->setProperty(strtolower($property), $value);
        }

        $event = new NodeEvent($node, $labels, $properties);
        $this->dispatcher->dispatch(NodeEvents::PRE_CREATE, $event);

        $node->save();

        $l = [];
        foreach ($labels as $label) {
            $l[] = $this->client->makeLabel($label);
        }

        $node->addLabels($l);
        $node->save();

        $this->dispatcher->dispatch(NodeEvents::POST_CREATE, $event);

        $this->logger->info('Resource added to the graph', [
            'uri' => $node->getProperty('uri'),
            'uuid' => $node->getProperty('uuid'),
        ]);

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

    /**
     * Put the properties to the node with the given uuid
     *
     * @param string $uuid
     * @param array $labels
     * @param array $properties
     *
     * @return Node
     */

    public function updateNode($uuid, array $labels, array $properties)
    {
        $node = $this->getNodeByUUID($uuid);

        foreach ($node->getProperties() as $property => $value) {
            if (!in_array($property, ['updated_at', 'uuid'], true)) {
                $node->removeProperty($property);
            }
        }

        foreach ($properties as $property => $value) {
            $node->setProperty($property, $value);
        }

        $l = [];
        foreach ($labels as $label) {
            $l[] = $this->client->makeLabel($label);
        }

        $node->addLabels($l);

        $event = new NodeEvent($node, $labels, $properties);
        $this->dispatcher->dispatch(NodeEvents::PRE_UPDATE, $event);

        $node->save();

        $this->dispatcher->dispatch(NodeEvents::POST_UPDATE, $event);

        $this->logger->info('Resource updated in the graph', [
            'uri' => $node->getProperty('uri'),
            'uuid' => $node->getProperty('uuid'),
        ]);

        return $node;
    }
}
