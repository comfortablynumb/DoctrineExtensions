<?php

namespace Gedmo\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * SearchableProcessor annotation for Searchable behavioral extension
 *
 * It defines an individual processor (filter, tokenizer, etc).
 *
 * @Annotation
 * @Target({"PROPERTY","ANNOTATION"})
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping.Annotation
 * @subpackage SearchableProcessor
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SearchableProcessor extends Annotation
{
    public $context;

    /** @var string @required */
    public $class;

    /** @var array */
    public $parameters = array();
}

