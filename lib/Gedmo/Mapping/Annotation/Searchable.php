<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Searchable annotation for Searchable behavioral extension
 *
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping.Annotation
 * @subpackage Searchable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class Searchable extends Annotation
{
    /** @var array */
    public $processors = array();

    /** @var array */
    public $indexTimeProcessors = array();

    /** @var array */
    public $queryTimeProcessors = array();

    /** @var boolean */
    public $indexed = true;

    /** @var boolean */
    public $stored = true;
}

