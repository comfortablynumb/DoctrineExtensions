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
     * Annotation to define that this object is loggable
     */
    const SEARCHABLE = 'Gedmo\\Mapping\\Annotation\\Searchable';

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
        // property annotations
        foreach ($class->getProperties() as $property) {
            if (($meta->isMappedSuperclass && !$property->isPrivate()) ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            
            // searchable property
            if ($searchable = $this->reader->getPropertyAnnotation($property, self::SEARCHABLE)) {
                $field = $property->getName();
                if ($meta->isCollectionValuedAssociation($field)) {
                    throw new InvalidMappingException("Cannot search [{$field}] as it is a collection in object - {$meta->name}");
                }

                if (!$searchable->indexed && !$searchable->stored) {
                    throw new InvalidMappingException("Searchable Field [{$field}] must be stored, indexed or both in object - {$meta->name}");
                }

                $indexTimeProcessors = array();
                $queryTimeProcessors = array();

                foreach ($searchable->processors as $processor) {
                    $processor->context = AbstractProcessor::CONTEXT_BOTH;

                    $indexTimeProcessors[] = $processor;
                    $queryTimeProcessors[] = $processor;
                }

                foreach ($searchable->indexTimeProcessors as $processor) {
                    $processor->context = AbstractProcessor::CONTEXT_INDEX;
                }

                foreach ($searchable->queryTimeProcessors as $processor) {
                    $processor->context = AbstractProcessor::CONTEXT_QUERY;
                }

                $config['searchable'] = true;
                $config['fields'][$field] = array(
                    'indexTimeProcessors'       => $indexTimeProcessors,
                    'queryTimeProcessors'       => $queryTimeProcessors,
                    'indexed'                   => $searchable->indexed,
                    'stored'                    => $searchable->stored
                );
            }
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