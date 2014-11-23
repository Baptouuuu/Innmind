<?php

namespace Innmind\AppBundle\Command\ResourceToken;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PublishCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('innmind:resourcetoken:publish')
            ->setDescription(
                'Send all the resource token through rabbitmq for crawl'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rabbit = $this->getContainer()->get('rabbit');

        if ($rabbit->hasProducer('worker.crawler')) {
            $producer = $rabbit->getProducer('worker.crawler');
            $graph = $this->getContainer()->get('graph');
            $url = $this->getContainer()->get('router');
            $tokens = $this->getContainer()
                ->get('doctrine')
                ->getManager()
                ->getRepository('InnmindAppBundle:ResourceToken')
                ->findAll();

            $output->writeln('URIs to crawl');

            foreach ($tokens as $token) {
                $data = [
                    'token' => $token->getUuid(),
                    'uri' => $token->getUri(),
                ];

                if ($token->hasReferer()) {
                    $data['referer'] = $token->getReferer();
                }

                try {
                    $node = $graph->getNodeByProperty(
                        'uri',
                        $token->getUri()
                    );

                    $data['uuid'] = $node->getProperty('uuid');
                    $data['publisher'] = $url->generate(
                        'api_node_update',
                        ['uuid' => $data['uuid']],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                } catch (\Exception $e) {
                    $data['publisher'] = $url->generate(
                        'api_node_create',
                        [],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );
                }

                $producer->publish(serialize($data));
                $output->writeln(sprintf(
                    '<fg=cyan>%s</fg=cyan>',
                    $data['uri']
                ));
            }
        } else {
            $output->writeln('<error>No producer set</error>');
        }
    }
}
