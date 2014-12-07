<?php

namespace Innmind\AppBundle;

use GuzzleHttp\Client;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Tool to check if a host is an instance of Innmind
 * and if so gather informations about it
 */

class InnmindAnalyzer
{
    protected $http;
    protected $generator;
    protected $analyzed = [];

    /**
     * Set a http client
     *
     * @param Client $client
     */

    public function setHttpClient(Client $client)
    {
        $this->http = $client;
    }

    /**
     * Set the url generator
     *
     * @param UrlGeneratorInterface $generator
     */

    public function setUrlGenerator(UrlGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Check if the host is an instance of innmind
     *
     * @param string $host
     *
     * @return bool
     */

    public function isInnmindInstance($host)
    {
        if (isset($this->analyzed[(string) $host])) {
            return $this->analyzed[(string) $host]['status'];
        }

        try {
            $response = $this->http->get(
                sprintf(
                    'http://%s%s',
                    $host,
                    $this->generator->generate('api_info')
                ),
                [
                    'headers' => [
                        'Accept' => 'application/json',
                    ],
                ]
            );
            $json = $response->json();

            $this->analyzed[(string) $host] = [
                'status' => true,
                'app' => $json['app'],
                'version' => $json['version'],
            ];
            return true;
        } catch (\Exception $e) {
            $this->analyzed[(string) $host] = [
                'status' => false,
            ];
            return false;
        }
    }

    /**
     * Return informations about the host
     *
     * @param string $host
     *
     * @return array
     */

    public function getInfos($host)
    {
        if (
            !isset($this->analyzed[(string) $host]) &&
            !$this->isInnmindInstance($host)
        ) {
            throw new \LogicException(sprintf(
                '"%s" is not an Innmind instance',
                $host
            ));
        }

        $data = $this->analyzed[(string) $host];
        unset($data['status']);

        return $data;
    }
}
