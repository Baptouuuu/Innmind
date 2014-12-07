<?php

namespace Innmind\AppBundle\Command\Graph\Host;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Innmind\AppBundle\Entity\InnmindInstance;

class InnmindApiCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('innmind:graph:host:innmind-api')
            ->setDescription('Check the existence of an innmind api for the indexed hosts')
            ->addOption(
                'span',
                null,
                InputOption::VALUE_OPTIONAL,
                'Time span for the indexed hosts',
                '1 day'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $span = $input->getOption('span');
        $date = new \DateTime;
        $date->modify(sprintf('-%s', $span));

        $graph = $this->getContainer()->get('graph');

        $results = $graph->query(
            'MATCH (n:Host) WHERE n.updated_at >= {date} RETURN n',
            ['date' => $date->format(\DateTime::W3C)]
        );
        $hosts = [];

        $analyzer = $this
            ->getContainer()
            ->get('innmind_analyzer');
        $em = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager();
        $repo = $em->getRepository('InnmindAppBundle:InnmindInstance');
        $self = $this
            ->getContainer()
            ->get('router')
            ->getContext()
            ->getHost();

        foreach ($results as $result) {
            $data = ['domain' => $result['n']->getProperty('domain')];

            if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
                $output->writeln(sprintf(
                    'Analyzing <fg=cyan>%s</fg=cyan>',
                    $data['domain']
                ));
            }

            if (
                $data['domain'] !== $self &&
                $analyzer->isInnmindInstance($data['domain'])
            ) {
                $data['status'] = true;
                $data['version'] = $analyzer->getInfo($data['domain'])['version'];

                if (!$repo->findOneByDomain($data['domain'])) {
                    $innmind = new InnmindInstance;
                    $innmind->setDomain($data['domain']);

                    $em->persist($innmind);
                }
            } else {
                $data['status'] = false;
            }

            $hosts[] = $data;
        }

        $em->flush();

        if ($output->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
            return;
        }

        foreach ($hosts as $host) {
            $output->writeln(sprintf(
                '%s: <fg=cyan>%s</fg=cyan>',
                $host['domain'],
                $host['status'] ?
                    sprintf(
                        'yes (version %s)',
                        $host['version']
                    ) :
                    'no'
            ));
        }
    }
}
