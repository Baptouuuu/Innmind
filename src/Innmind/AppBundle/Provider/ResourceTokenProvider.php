<?php

namespace Innmind\AppBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Innmind\AppBundle\Entity\ResourceToken;

class ResourceTokenProvider
{
    protected $em;
    protected $token;
    protected $uri;
    protected $entity;

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
     * Set the resource token string
     *
     * @param string $token
     *
     * @return ResourceTokenProvider self
     */

    public function setToken($token)
    {
        $this->token = (string) $token;
        $this->entity = null;

        return $this;
    }

    /**
     * Set the URI associated to the token
     *
     * @param string $uri
     *
     * @return ResourceTokenProvider self
     */

    public function setURI($uri)
    {
        $this->uri = (string) $uri;
        $this->entity = null;

        return $this;
    }

    /**
     * Check if the resource token exist
     *
     * @return bool
     */

    public function hasToken()
    {
        if ($this->entity instanceof ResourceToken) {
            return true;
        }

        try {
            $this->entity = $this->getToken();

            if (!($this->entity instanceof ResourceToken)) {
                throw new EntityNotFoundException();

            }

            return true;
        } catch (EntityNotFoundException $e) {
            return false;
        }
    }

    /**
     * Return the resource token entity
     *
     * @throws EntityNotFoundException If the token doesn't exist
     *
     * @return ResourceToken
     */

    public function getToken()
    {
        return $this->em
            ->getRepository('InnmindAppBundle:ResourceToken')
            ->findOneBy([
                'uuid' => $this->token,
                'uri' => $this->uri
            ]);
    }

    /**
     * Remove the token from the database
     *
     * @return ResourceTokenProvider self
     */

    public function clearToken()
    {
        if ($this->entity !== null) {
            $this->em->remove($this->entity);
            $this->em->flush();
            $this->entity = null;
            $this->token = null;
            $this->uri = null;
        }

        return $this;
    }
}
