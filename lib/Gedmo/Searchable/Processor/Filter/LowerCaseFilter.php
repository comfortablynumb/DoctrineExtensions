<?php

/**
 * Trims the value
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @package Gedmo.Searchable.Processor.Filter
 * @subpackage Filter
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Gedmo\Searchable\Processor\Filter;

use Gedmo\Searchable\Processor\AbstractProcessor,
    Gedmo\Exception\InvalidMappingException;

class LowerCaseFilter extends AbstractProcessor
{
    /**
     * @inheritDoc
     */
    public function process()
    {
        foreach ($this->subject as $key => $value) {
            $this->subject[$key] = strtolower($value);
        }

        return $this->subject;
    }
}
