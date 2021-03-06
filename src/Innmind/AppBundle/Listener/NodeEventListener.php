<?php

namespace Innmind\AppBundle\Listener;

use Innmind\AppBundle\Graph\NodeEvent;
use Innmind\AppBundle\UUID;
use Innmind\AppBundle\RabbitMQ;
use Innmind\AppBundle\Graph;
use Innmind\AppBundle\Entity\ResourceToken;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Pdp\Parser;

class NodeEventListener
{
    protected $rabbit;
    protected $uuid;
    protected $em;
    protected $graph;
    protected $domainParser;
    protected $url;
    protected $toCrawl = [];

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
     * Set a domain parser
     *
     * @param Pdp\Parser $parser
     */

    public function setDomainParser(Parser $parser)
    {
        $this->domainParser = $parser;
    }

    /**
     * Send messages to rabbit to crawl refered resources
     *
     * @param NodeEvent $event
     */

    public function onPostCreate(NodeEvent $event)
    {
        $node = $event->getNode();

        if (!$this->rabbit->hasProducer('worker.crawler')) {
            return;
        }

        $crawler = $this->rabbit->getProducer('worker.crawler');
        $uris = [];

        if (isset($node->canonical)) {
            $uris[] = $node->getProperty('canonical');
        }

        $uri = $node->getProperty('uri');

        foreach ($node->getProperties() as $property => $value) {
            if (
                (
                    substr($property, 0, 5) === 'links' ||
                    substr($property, 0, 12) === 'translations' ||
                    (bool) preg_match('/^images.*uri$/', $property)
                ) &&
                !$this->isSameResources($value, $uri)
            ) {
                $uris[] = $value;
            }
        }

        $this->toCrawl[] = [
            'uris' => $uris,
            'node' => $node,
        ];
    }

    /**
     * Send the amqp messages on kernel terminate
     *
     * @param PostResponseEvent $event
     */

    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (!$this->rabbit->hasProducer('worker.crawler')) {
            return;
        }

        $crawler = $this->rabbit->getProducer('worker.crawler');

        foreach ($this->toCrawl as $resource) {
            foreach ($resource['uris'] as $uri) {
                $token = $this->em
                    ->getRepository('InnmindAppBundle:ResourceToken')
                    ->findOneBy([
                        'uri' => $uri,
                        'referer' => $resource['node']->getProperty('uri'),
                    ]);

                if (!empty($token)) {
                    continue;
                }

                $token = new ResourceToken;
                $token
                    ->setUri($uri)
                    ->setUuid($this->uuid->generate())
                    ->setReferer($resource['node']->getProperty('uri'));

                $this->em->persist($token);

                $data = [
                    'uri' => $uri,
                    'referer' => $resource['node']->getProperty('uri'),
                    'token' => $token->getUuid(),
                ];

                if (isset($resource['node']->language)) {
                    $data['language'] = $resource['node']->getProperty('language');
                }

                try {
                    $existingNode = $this->graph->getNodeByProperty(
                        'uri',
                        $uri
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

        $this->em->flush();
    }

    /**
     * Check if both uris represent the same resource
     *
     * @param string $a
     * @param string $b
     *
     * @return bool
     */

    protected function isSameResources($a, $b)
    {
        $a = $this->domainParser->parseUrl($a);
        $b = $this->domainParser->parseUrl($b);

        if (
            (string) $a->host === (string) $b->host &&
            $a->port === $b->port &&
            $a->path === $b->path &&
            $a->query === $b->query
        ) {
            return true;
        }

        return false;
    }
}
