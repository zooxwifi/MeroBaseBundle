<?php
namespace Mero\BaseBundle\Entity\Field;

/**
 * @package Mero\BaseBundle\Entity\Field
 * @author Rafael Mello <merorafael@gmail.com>
 */
trait Modified
{

    /**
     * @var \DateTime Date modified
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $modified;

    /**
     * Returns registry modification date if any.
     *
     * @return null|\DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Sets modified date.
     *
     * @param \DateTime $modified Date modified
     *
     * @return Modified
     */
    public function setModified(\DateTime $modified)
    {
        $this->modified = $modified;
        return $this;
    }

    /**
     * @ORM\PreUpdate
     */
    public function updated()
    {
        $this->updated = new \DateTime();
    }

}
