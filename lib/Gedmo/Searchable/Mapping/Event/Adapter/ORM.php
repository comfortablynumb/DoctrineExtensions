<?php

namespace Gedmo\Searchable\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Searchable\Mapping\Event\SearchableAdapter;

/**
 * Doctrine event adapter for ORM adapted
 * for Loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Loggable\Mapping\Event\Adapter
 * @subpackage ORM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ORM extends BaseAdapterORM implements SearchableAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultIndexedTokenClass()
    {
        return 'Gedmo\\Searchable\\Entity\\IndexedToken';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultStoredObjectClass()
    {
        return 'Gedmo\\Searchable\\Entity\\StoredObject';
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultStoredObjectFieldValueClass()
    {
        return 'Gedmo\\Searchable\\Entity\\StoredObjectFieldValue';
    }
}