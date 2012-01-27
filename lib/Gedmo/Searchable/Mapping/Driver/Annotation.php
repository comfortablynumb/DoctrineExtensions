<?php

namespace Gedmo\Searchable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException,
    Gedmo\Searchable\Processor\AbstractProcessor;

/**
 * This is an annotation mapping driver for Searchable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Searchable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Searchable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements AnnotationDriverInterface
{
    /**
     * Annotation to define that the object is searchable
     */
    const SEARCHABLE = 'Gedmo\\Mapping\\Annotation\\Searchable';

    /**
     * Annotation to define that the field is searchable
     */
    const SEARCHABLE_FIELD = 'Gedmo\\Mapping\\Annotation\\SearchableField';

    /**
     * Annotation reader instance
     *
     * @var object
     */
    private $reader;

    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;
    /**
     * {@inheritDoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if ($config && is_array($meta->identifier) && count($meta->identifier) > 1) {
            throw new InvalidMappingException("Searchable does not support composite identifiers in class - {$meta->name}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $meta->getReflectionClass();
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::SEARCHABLE)) {
            $config['searchable'] = true;
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            if (($meta->isMappedSuperclass && !$property->isPrivate()) ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            
            // searchable property
            $field = $property->getName();
            $fieldConfig = array(
                'indexed'               => true,
                'stored'                => true,
                'processors'            => array(),
                'indexTimeProcessors'   => array(),
                'queryTimeProcessors'   => array()
            );

            if ($searchable = $this->reader->getPropertyAnnotation($property, self::SEARCHABLE_FIELD)) {
                if ($meta->isCollectionValuedAssociation($field)) {
                    throw new InvalidMappingException("Cannot search [{$field}] as it is a collection in object - {$meta->name}");
                }

                if (!$searchable->indexed && !$searchable->stored) {
                    throw new InvalidMappingException("Searchable Field [{$field}] must be stored, indexed or both in object - {$meta->name}");
                }

                $config['searchable'] = true;
            }

            if (isset($config['searchable']) && $config['searchable']) {
                if (empty($fieldConfig['processors']) && empty($fieldConfig['indexTimeProcessors']) &&
                    empty($fieldConfig['queryTimeProcessors'])) {
                    $this->determineBestDefaultProcessorsForField($meta, $field, $fieldConfig['indexTimeProcessors'], $fieldConfig['queryTimeProcessors']);
                } else {
                    foreach ($fieldConfig['processors'] as $processor) {
                        $processor = (array) $processor;
                        $processorData = array(
                            'context'       => AbstractProcessor::CONTEXT_BOTH,
                            'class'         => $processor['class'],
                            'parameters'    => $processor['parameters']
                        );

                        $fieldConfig['indexTimeProcessors'][] = $processorData;
                        $fieldConfig['queryTimeProcessors'][] = $processorData;
                    }

                    foreach ($fieldConfig['indexTimeProcessors'] as $processor) {
                        $processor = (array) $processor;
                        $processorData = array(
                            'context'       => AbstractProcessor::CONTEXT_INDEX,
                            'class'         => $processor['class'],
                            'parameters'    => $processor['parameters']
                        );

                        $fieldConfig['indexTimeProcessors'][] = $processorData;
                    }

                    foreach ($fieldConfig['queryTimeProcessors'] as $processor) {
                        $processor = (array) $processor;
                        $processorData = array(
                            'context'       => AbstractProcessor::CONTEXT_QUERY,
                            'class'         => $processor['class'],
                            'parameters'    => $processor['parameters']
                        );

                        $fieldConfig['queryTimeProcessors'][] = $processorData;
                    }
                }
            }
            
            $config['fields'][$field] = array(
                'indexTimeProcessors'       => $fieldConfig['indexTimeProcessors'],
                'queryTimeProcessors'       => $fieldConfig['queryTimeProcessors'],
                'indexed'                   => $fieldConfig['indexed'],
                'stored'                    => $fieldConfig['stored']
            );
        }
    }

    protected function determineBestDefaultProcessorsForField($meta, $field, &$indexTimeProcessors, $queryTimeProcessors)
    {
        $mapping = $meta->getFieldMapping($field);

        switch ($mapping['type']) {
            case 'string':
            case 'text':
                $processors = array(
                    array(
                        'context'       => AbstractProcessor::CONTEXT_BOTH,
                        'class'         => 'Gedmo\Searchable\Processor\Filter\TrimFilter',
                        'parameters'    => array()
                    ),
                    array(
                        'context'       => AbstractProcessor::CONTEXT_BOTH,
                        'class'         => 'Gedmo\Searchable\Processor\Filter\LowerCaseFilter',
                        'parameters'    => array()
                    ),
                    array(
                        'context'       => AbstractProcessor::CONTEXT_BOTH,
                        'class'         => 'Gedmo\Searchable\Processor\Tokenizer\DelimiterTokenizer',
                        'parameters'    => array()
                    )
                );

                $indexTimeProcessors = array_merge($indexTimeProcessors, $processors);
                $queryTimeProcessors = array_merge($queryTimeProcessors, $processors);

                break;
        }
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param $driver
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }
}