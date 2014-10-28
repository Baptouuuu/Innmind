<?php

namespace Innmind\AppBundle\Command\ResourceToken;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Innmind\AppBundle\Entity\ResourceToken;

class CreateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('innmind:resourcetoken:create')
            ->setDescription(
                'Generate a token so a uri can be crawled. ' .
                'Though it does send a message to crawl the resource'
            )
            ->addArgument(
                'uri',
                InputArgument::REQUIRED,
                'URI to associate to the token'
            )
            ->addArgument(
                'referer',
                InputArgument::OPTIONAL,
                'URI refering to the one to crawl'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entity = new ResourceToken;
        $entity->setUri($input->getArgument('uri'));

        if ($input->getArgument('referer')) {
            $entity->setReferer($input->getArgument('referer'));
        }

        $entity->setUuid(
            $this
                ->getContainer()
                ->get('uuid')
                ->generate()
        );

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
            'Token generated: <info>%s</info>',
            $entity->getUuid()
        ));
    }
}
