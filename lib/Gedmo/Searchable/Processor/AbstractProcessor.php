<?php

/**
 * Abstract Processor class
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @package Gedmo.Searchable.Processor.Processor
 * @subpackage Processor
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

namespace Gedmo\Searchable\Processor;

abstract class AbstractProcessor
{
    const CONTEXT_BOTH = 'both';
    const CONTEXT_INDEX = 'index';
    const CONTEXT_QUERY = 'query';

    protected $context = self::CONTEXT_BOTH;
    protected $subject;
    protected $parameters = array();

    public function __construct($subject, array $parameters = array())
    {
        if (!is_array($subject)) {
            $subject = (array) $subject;
        }

        $this->subject = $subject;

        $this->validateParameters($parameters);

        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * Process the subject
     *
     * @return $subject
     */
    abstract public function process();

    /**
     * Validates the parameters array
     *
     * @param array
     * @return void
     */
    public function validateParameters($parameters)
    {
        
    }
}
