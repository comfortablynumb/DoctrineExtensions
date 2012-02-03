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
    const TYPE_INTEGER = 'integer';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_STRING = 'string';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATETIME = 'datetime';

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
     * @Column(type="string")
     */
    protected $type;

    /**
     * @Column(type="bigint", nullable=true)
     */
    protected $integerToken;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $booleanToken;

    /**
     * @Column(type="decimal", nullable=true)
     */
    protected $decimalToken;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $dateTimeToken;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $stringToken;

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

    /**
     * Set boolean token
     *
     * @param bool $booleanToken
     * @return IndexedToken
     */
    public function setBooleanToken($booleanToken)
    {
        $this->booleanToken = $booleanToken;

        $this->stringToken = null;
        $this->dateTimeToken = null;
        $this->decimalToken = null;
        $this->integerToken = null;

        return $this;
    }

    /**
     * Get boolean token
     *
     * @return bool
     */
    public function getBooleanToken()
    {
        return $this->booleanToken;
    }
    
    public function setDateTimeToken($dateTimeToken)
    {
        $this->dateTimeToken = $dateTimeToken;

        $this->stringToken = null;
        $this->booleanToken = null;
        $this->decimalToken = null;
        $this->integerToken = null;

        return $this;
    }

    public function getDateTimeToken()
    {
        return $this->dateTimeToken;
    }

    public function setDecimalToken($decimalToken)
    {
        $this->decimalToken = $decimalToken;

        $this->stringToken = null;
        $this->dateTimeToken = null;
        $this->booleanToken = null;
        $this->integerToken = null;

        return $this;
    }

    public function getDecimalToken()
    {
        return $this->decimalToken;
    }

    public function setIntegerToken($integerToken)
    {
        $this->integerToken = $integerToken;

        $this->stringToken = null;
        $this->dateTimeToken = null;
        $this->decimalToken = null;
        $this->booleanToken = null;

        return $this;
    }

    public function getIntegerToken()
    {
        return $this->integerToken;
    }

    public function setStringToken($stringToken)
    {
        $this->stringToken = $stringToken;

        $this->booleanToken = null;
        $this->dateTimeToken = null;
        $this->decimalToken = null;
        $this->integerToken = null;

        return $this;
    }

    public function getStringToken()
    {
        return $this->stringToken;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setTypeFromORMType($ormType)
    {
        $this->type = self::convertORMType($ormType);

        return $this;
    }

    public function setToken($token)
    {
        $type = $this->getType();

        if (is_null($type)) {
            if (is_object($token) && $token instanceof \DateTime) {
                $type = self::TYPE_DATETIME;
            } else if (is_double($token) || is_float($token)) {
                $type = self::TYPE_DECIMAL;
            } else if (is_bool($type)) {
                $type = self::TYPE_BOOLEAN;
            } else if (is_int($type)) {
                $type = self::TYPE_INTEGER;
            } else {
                $type = self::TYPE_STRING;
            }

            $this->setType($type);
        }

        switch ($type) {
            case self::TYPE_BOOLEAN:
                $this->setBooleanToken($token);

                break;
            case self::TYPE_DATETIME:
                $this->setDateTimeToken($token);

                break;
            case self::TYPE_DECIMAL:
                $this->setDecimalToken($token);

                break;
            case self::TYPE_INTEGER:
                $this->setIntegerToken($token);

                break;
            default:
                $this->setStringToken($token);

                break;
        }

        $this->token = $token;

        return $this;
    }

    public function getToken()
    {
        switch ($this->getType()) {
            case self::TYPE_DATETIME:
                return $this->getDateTimeToken();
            case self::TYPE_DECIMAL:
                return $this->getDecimalToken();
            case self::TYPE_INTEGER:
                return $this->getIntegerToken();
            case self::TYPE_BOOLEAN:
                return $this->getBooleanToken();
            default:
                return $this->getStringToken();
        }
    }

    public static function convertORMType($ormType)
    {
        switch ($ormType) {
            case 'bigint':
            case 'integer':
            case 'smallint':
                return self::TYPE_INTEGER;
            case 'decimal':
            case 'float':
                return self::TYPE_DECIMAL;
            case 'boolean':
                return self::TYPE_BOOLEAN;
            case 'date':
            case 'time':
            case 'datetime':
                return self::TYPE_DATETIME;
            default:
                return self::TYPE_STRING;
        }
    }

    public static function getTokenFieldForORMType($ormType)
    {
        switch ($ormType) {
            case 'bigint':
            case 'integer':
            case 'smallint':
                return 'integerToken';
            case 'decimal':
            case 'float':
                return 'decimalToken';
            case 'boolean':
                return 'booleanToken';
            case 'date':
            case 'time':
            case 'datetime':
                return 'dateTimeToken';
            default:
                return 'stringToken';
        }
    }
}