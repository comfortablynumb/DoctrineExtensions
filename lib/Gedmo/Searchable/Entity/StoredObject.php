<?php

namespace Gedmo\Searchable\Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\OneToMany;
use Gedmo\Searchable\Entity\IndexedToken;

/**
 * @Table(
 *     name="ext_searchable_stored_object"
 * )
 * @Entity
 */
class StoredObject
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(name="object_id", length=32, nullable=true)
     */
    protected $objectId;


    /**
     * @Column(type="string")
     */
    protected $class;

    /**
     * @Column(type="array")
     */
    protected $data = array();

    /**
     * @OneToMany(targetEntity="IndexedToken", mappedBy="storedObject", cascade={"remove"})
     */
    protected $tokens;



    public function __construct()
    {
        $this->tokens = new ArrayCollection();
    }

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
     * Set class
     *
     * @param string $objectId
     * @return IndexedToken
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
        return $this;
    }

    /**
     * Get object id
     *
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Set class
     *
     * @param string $class
     * @return StoredObject
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * Get class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set data
     *
     * @param array $data
     * @return StoredObject
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Adds a token to the collection
     *
     * @param IndexedToken $token
     * @return StoredObject
     */
    public function addToken(IndexedToken $token)
    {
        $this->tokens[] = $token;
        return $this;
    }

    /**
     * Set tokens
     *
     * @param ArrayCollection $tokens
     * @return StoredObject
     */
    public function setTokens(ArrayCollection $tokens)
    {
        $this->tokens = $tokens;
        return $this;
    }

    /**
     * Get tokens
     *
     * @return ArrayCollection
     */
    public function getTokens()
    {
        return $this->tokens;
    }
}