<?php

namespace Gedmo\Searchable\Processor;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Searchable\Processor\AbstractProcessor;
use Gedmo\Exception\InvalidArgumentException;

/**
 * ProcessorManager class
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Searchable.Processor
 * @subpackage ProcessorManager
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class ProcessorManager 
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function runIndexTimeProcessors($field, $value)
    {
        return $this->runProcessors($field, $value, AbstractProcessor::CONTEXT_INDEX);
    }

    public function runQueryTimeProcessors($field, $value)
    {
        return $this->runProcessors($field, $value, AbstractProcessor::CONTEXT_QUERY);
    }

    public function runProcessors($field, $value, $context)
    {
        $pos = strpos($field, '.');
        $field = $pos !== false ? substr($field, $pos + 1) : $field;


        if (!isset($this->config['fields'][$field])) {
            throw new InvalidArgumentException(sprintf('Can\'t run processors on field "%s". It\'s not searchable.', $field));
        }

        $fieldInfo = $this->config['fields'][$field];
        $processorsType = $context === AbstractProcessor::CONTEXT_INDEX ?
                'indexTimeProcessors' : 'queryTimeProcessors';
        $tokenizedValue = array($value);
        
        foreach ($fieldInfo[$processorsType] as $processorInfo) {
            $refl = new \ReflectionClass($processorInfo['class']);
            $processor = $refl->newInstanceArgs(array_merge(array($tokenizedValue), $processorInfo['parameters']));
            $tokenizedValue = $processor->process();
        }

        return $tokenizedValue;
    }
}
