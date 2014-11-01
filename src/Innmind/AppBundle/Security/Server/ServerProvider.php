<?php

namespace Innmind\AppBundle\Security\Server;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Innmind\AppBundle\Entity\Server;
use Doctrine\ORM\EntityManager;

class ServerProvider implements UserProviderInterface
{
    protected $em;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    public function loadUserByUsername($host)
    {
        $server = $this->em
            ->getRepository('InnmindAppBundle:Server')
            ->findOneByHost($host);

        if ($server) {
            return $server;
        }

        throw new UsernameNotFoundException(sprintf(
            'Server %s not registered',
            $host
        ));
    }

    public function refreshUser(UserInterface $server)
    {
        if (!$server instanceof Server) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($server))
            );
        }

        return $this->loadUserByUsername($server->getUsername());
    }

    public function supportsClass($class)
    {
        return $class === 'Innmind\\AppBundle\\Entity\\Server';
    }
}
