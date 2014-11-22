<?php

namespace Innmind\AppBundle\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Innmind\AppBundle\Security\Authentication\Token\ServerToken;

class ServerProvider implements AuthenticationProviderInterface
{
    private $serverProvider;

    public function __construct(UserProviderInterface $serverProvider)
    {
        $this->serverProvider = $serverProvider;
    }

    public function authenticate(TokenInterface $token)
    {
        $server = $this->serverProvider->loadUserByUsername($token->getUser());

        if (!$server) {
            throw new AuthenticationException('Server not registered');
        }

        $authToken = new ServerToken($server->getRoles());
        $authToken->setUser($server);
        return $authToken;
    }

    public function supports(TokenInterface $token)
    {
        return $token instanceof ServerToken;
    }
}
