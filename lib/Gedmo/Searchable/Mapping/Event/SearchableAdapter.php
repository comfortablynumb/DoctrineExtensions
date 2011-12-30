<?php

namespace Gedmo\Searchable\Mapping\Event;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter interface
 * for Loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo\Loggable\Mapping\Event
 * @subpackage LoggableAdapter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface SearchableAdapter extends AdapterInterface
{
    /**
     * Get default IndexedToken class
     *
     * @return string
     */
    function getDefaultIndexedTokenClass();

    /**
     * Get default StoredObject class
     *
     * @return string
     */
    function getDefaultStoredObjectClass();
}