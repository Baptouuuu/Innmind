<?php

namespace Innmind\AppBundle\Listener;

use Innmind\AppBundle\Graph\NodeEvent;
use Innmind\AppBundle\UUID;
use Innmind\AppBundle\RabbitMQ;
use Innmind\AppBundle\Graph;
use Innmind\AppBundle\Entity\ResourceToken;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NodeEventListener
{
    protected $rabbit;
    protected $uuid;
    protected $em;
    protected $graph;
    protected $url;

    /**
     * Set the rabbit mq wrapper
     *
     * @param RabbitMQ $rabbit
     */

    public function setRabbit(RabbitMQ $rabbit)
    {
        $this->rabbit = $rabbit;
    }

    /**
     * Set the uuid generator
     *
     * @param UUID $generator
     */

    public function setGenerator(UUID $generator)
    {
        $this->uuid = $generator;
    }

    /**
     * Set the entity manager
     *
     * @param EntityManager $em
     */

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

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
     * Set the url generator
     *
     * @param UrlGeneratorInterface $generator
     */

    public function setUrlGenerator(UrlGeneratorInterface $generator)
    {
        $this->url = $generator;
    }

    /**
     * Send messages to rabbit to crawl refered resources
     *
     * @param NodeEvent $event
     */

    public function onPostCreate(NodeEvent $event)
    {
        $node = $event->getNode();

        if ($this->rabbit->hasProducer('worker.crawler')) {
            $crawler = $this->rabbit->getProducer('worker.crawler');
            $toCrawl = [];

            if (isset($node->canonical)) {
                $toCrawl[] = $node->getProperty('canonical');
            }

            foreach ($node->getProperties() as $property => $value) {
                if (
                    substr($property, 0, 5) === 'links' ||
                    substr($property, 0, 12) === 'translations'
                ) {
                    $toCrawl[] = $value;
                }
            }

            foreach ($toCrawl as $link) {
                $token = new ResourceToken;
                $token
                    ->setUri($link)
                    ->setUuid($this->uuid->generate())
                    ->setReferer($node->getProperty('uri'));

                $this->em->persist($token);
                $this->em->flush();

                $data = [
                    'uri' => $link,
                    'referer' => $node->getProperty('uri'),
                    'token' => $token->getUuid(),
                ];

                if (isset($node->language)) {
                    $data['language'] = $node->getProperty('language');
                }

                try {
                    $existingNode = $this->graph->getNodeByProperty(
                        'uri',
                        $link
                    );
                    $data['uuid'] = $existingNode->getProperty('uuid');
                    $data['publisher'] = $this->url->generate(
                        'api_node_update',
                        ['uuid' => $data['uuid']],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                } catch (\Exception $e) {
                    $data['publisher'] = $this->url->generate(
                        'api_node_create',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                }

                $crawler->publish(serialize($data));
            }
        }
    }
}
