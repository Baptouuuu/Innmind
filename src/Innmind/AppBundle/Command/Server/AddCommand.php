<?php

namespace Innmind\AppBundle\Command\Server;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Innmind\AppBundle\Entity\Server;

class AddCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('innmind:server:add')
            ->setDescription('Register a new server allowed to talk to the innmind app')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Server name (work as an alias)'
            )
            ->addArgument(
                'host',
                InputArgument::REQUIRED,
                'Server host'
            )
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'The job being done on this server'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = new Server;
        $entity
            ->setName($input->getArgument('name'))
            ->setHost($input->getArgument('host'))
            ->setType($input->getArgument('type'));

        $errors = $this
            ->getContainer()
            ->get('validator')
            ->validate($entity);

        if ($errors->count() > 0) {
            foreach ($errors as $error) {
                $output->writeln(sprintf(
                    '<error>%s</error>',
                    (string) $error
                ));
            }
            return;
        }

        $em = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager();

        $em->persist($entity);
        $em->flush();

        $output->writeln(sprintf(
            '<info>Entity created (id=%s)</info>',
            $entity->getId()
        ));
    }
}
