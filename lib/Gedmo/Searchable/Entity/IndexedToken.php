<?php

namespace Gedmo\Searchable\Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Gedmo\Searchable\Entity\StoredObject;

/**
 * @Table(
 *     name="ext_searchable_indexed_token"
 * )
 * @Entity
 */
class IndexedToken
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="string")
     */
    protected $field;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $token;

    /**
     * @ManyToOne(targetEntity="StoredObject", inversedBy="tokens")
     * @JoinColumn(name="stored_object_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $storedObject;

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
     * Set field
     *
     * @param string $field
     * @return IndexedToken
     */
    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    /**
     * Get field
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return IndexedToken
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set StoredObject
     *
     * @param StoredObject $storedObject
     * @return IndexedToken
     */
    public function setStoredObject(StoredObject $storedObject)
    {
        $this->storedObject = $storedObject;
        return $this;
    }

    /**
     * Get StoredObject
     *
     * @return StoredObject
     */
    public function getStoredObject()
    {
        return $this->storedObject;
    }
}