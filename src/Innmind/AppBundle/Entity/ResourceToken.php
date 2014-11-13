<?php

namespace Innmind\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="resource_token")
 * @ORM\HasLifecycleCallbacks
 */
class ResourceToken
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=60)
     */
    protected $uuid;

    /**
     * @ORM\Column(type="string")
     */
    protected $uri;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $referer;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $date;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     * @return ResourceToken
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set uri
     *
     * @param string $uri
     * @return ResourceToken
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * Get uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Set referer
     *
     * @param string $referer
     * @return ResourceToken
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Check if a referer is set
     *
     * @return bool
     */
    public function hasReferer()
    {
        return (bool) $this->referer;
    }

    /**
     * Get referer
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * @ORM\PrePersist
     */
    public function updateDate()
    {
        if (!$this->date) {
            $this->date = new \DateTime;
        }
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return ResourceToken
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
