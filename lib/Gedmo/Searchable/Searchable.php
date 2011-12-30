<?php

namespace Gedmo\Searchable;

/**
 * This interface is not necessary but can be implemented for
 * Domain Objects which in some cases needs to be identified as
 * Searchable
 * 
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Searchable
 * @subpackage Searchable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
interface Searchable
{
    // this interface is not necessary to implement
    
    /**
     * @gedmo:Searchable
     * to mark the class as searchable use class annotation @gedmo:Searchable
     * this object will contain now a history
     */
}