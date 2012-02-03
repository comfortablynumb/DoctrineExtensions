<?php

namespace Gedmo\Searchable;

use Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Mapping\MappedEventSubscriber,
    Gedmo\Searchable\Mapping\Event\SearchableAdapter,
    Doctrine\Common\EventArgs,
    Doctrine\ORM\NoResultException,
    Gedmo\Searchable\Processor\ProcessorManager;

/**
 * Searchable listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Searchable
 * @subpackage SearchableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SearchableListener extends MappedEventSubscriber
{
    /**
     * Create action
     */
    const ACTION_CREATE = 'create';

    /**
     * Update action
     */
    const ACTION_UPDATE = 'update';

    /**
     * Remove action
     */
    const ACTION_REMOVE = 'remove';
    
    const INDEXED_TOKEN_CLASS = 'Gedmo\Searchable\Entity\IndexedToken';
    const STORED_OBJECT_CLASS = 'Gedmo\Searchable\Entity\StoredObject';
    const STORED_OBJECT_FIELD_VALUE_CLASS = 'Gedmo\Searchable\Entity\StoredObjectFieldValue';

    /**
     * List of stored object which do not have the
     * object id generated yet
     *
     * @var array
     */
    private $pendingStoredObjectsUpdate = array();

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'loadClassMetadata',
            'postPersist'
        );
    }

    /**
     * Get the IndexedToken class
     *
     * @param SearchableAdapter $ea
     * @param string $class
     * @return string
     */
    protected function getIndexedTokenClass(SearchableAdapter $ea, $class)
    {
        return isset($this->configurations[$class]['indexedTokenClass']) ?
            $this->configurations[$class]['indexedTokenClass'] :
            $ea->getDefaultIndexedTokenClass();
    }

    /**
     * Get the StoredObject class
     *
     * @param SearchableAdapter $ea
     * @param string $class
     * @return string
     */
    protected function getStoredObjectClass(SearchableAdapter $ea, $class)
    {
        return isset($this->configurations[$class]['storedObject']) ?
            $this->configurations[$class]['storedObject'] :
            $ea->getDefaultStoredObjectClass();
    }

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Checks for inserted object to update its objectId
     * foreign key
     *
     * @param EventArgs $args
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $object = $ea->getObject();
        $oid = spl_object_hash($object);

        if (!empty($this->pendingStoredObjectsUpdate) && array_key_exists($oid, $this->pendingStoredObjectsUpdate)) {
            $om = $ea->getObjectManager();
            $uow = $om->getUnitOfWork();
            $meta = $om->getClassMetadata(get_class($object));
            $identifierField = $ea->getSingleIdentifierFieldName($meta);
            $fieldMapping = $meta->getFieldMapping($identifierField);
            $id = $meta->getReflectionProperty($identifierField)->getValue($object);
            $storedObject = $this->pendingStoredObjectsUpdate[$oid];
            $storedObject->setObjectId($id);
            $newData = array_merge($storedObject->getData(), array('id' => $id));
            $storedObject->setData($newData);
            
            $uow->scheduleExtraUpdate($storedObject, array(
                'objectId'  => array(null, $id),
                'data'      => array(null, $newData)
            ));
            $ea->setOriginalObjectProperty($uow, $oid, 'objectId', $id);
            $ea->setOriginalObjectProperty($uow, $oid, 'data', $newData);

            // Finally, we need to add a token for the primary key, as it wasn't available before
            $indexedTokenClass = $this->getIndexedTokenClass($ea, $meta->name);
            $indexedToken = new $indexedTokenClass;

            $indexedToken->setField($identifierField)
                ->setTypeFromORMType($fieldMapping['type'])
                ->setToken($id)
                ->setStoredObject($storedObject);
            $om->persist($indexedToken);
            $om->flush();

            unset($this->pendingStoredObjectsUpdate[$oid]);
        }
    }

    /**
     * Looks for searchable objects being inserted or updated
     * for further processing
     *
     * @param EventArgs $args
     * @return void
     */
    public function onFlush(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $this->processSearchableObject(self::ACTION_CREATE, $object, $ea);
        }

        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $this->processSearchableObject(self::ACTION_UPDATE, $object, $ea);
        }
        
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $this->processSearchableObject(self::ACTION_REMOVE, $object, $ea);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Process searchable object
     *
     * @param string $action
     * @param object $object
     * @param SearchableAdapter $ea
     * @return void
     */
    private function processSearchableObject($action, $object, SearchableAdapter $ea)
    {
        switch ($action) {
            case self::ACTION_CREATE:
                $this->insertSearchData($object, $ea);

                break;
            case self::ACTION_UPDATE:
                $this->updateSearchData($object, $ea);

                break;
            case self::ACTION_REMOVE:
                $this->removeSearchData($object, $ea);

                break;
        }
    }

    private function insertSearchData($object, SearchableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        if (isset($config['searchable']) && $config['searchable']) {
            $identifierField = $ea->getSingleIdentifierFieldName($meta);
            $objectId = $meta->getReflectionProperty($identifierField)->getValue($object);
            $class = get_class($object);
            $uow = $om->getUnitOfWork();
            $indexedTokenClass = $this->getIndexedTokenClass($ea, $meta->name);
            $storedObjectClass = $this->getStoredObjectClass($ea, $meta->name);
            $indexedTokenMeta = $om->getClassMetadata($indexedTokenClass);
            $storedObjectMeta = $om->getClassMetadata($storedObjectClass);
            $storedObject = new $storedObjectClass;
            $storedObject->setClass($class);
            $storedData = $storedObject->getData();

            if ($objectId) {
                $storedObject->setObjectId($objectId);
            }

            $om->persist($storedObject);

            $processorManager = new ProcessorManager($config);
            
            foreach ($config['fields'] as $field => $fieldInfo) {
                if ($field === $identifierField && $objectId === null) {
                    continue;
                }

                if ($fieldInfo['indexed']) {
                    $refl = $meta->getReflectionProperty($field);
                    $refl->setAccessible(true);
                    $value = $refl->getValue($object);
                    $tokenizedValue = $processorManager->runIndexTimeProcessors($field, $value);
                    $fieldMapping = $meta->getFieldMapping($field);

                    foreach ($tokenizedValue as $token) {
                        $indexedToken = new $indexedTokenClass;

                        $indexedToken->setField($field)
                            ->setTypeFromORMType($fieldMapping['type'])
                            ->setToken($token)
                            ->setStoredObject($storedObject);
                        $storedObject->addToken($indexedToken);
                        
                        $om->persist($indexedToken);
                        $uow->computeChangeSet($indexedTokenMeta, $indexedToken);
                    }
                }

                if ($fieldInfo['stored']) {
                    $storedData[$field] = $value;
                }
            }

            $storedObject->setData($storedData);

            $uow->computeChangeSet($storedObjectMeta, $storedObject);

            $this->pendingStoredObjectsUpdate[spl_object_hash($object)] = $storedObject;
        }
    }

    public function updateSearchData($object, SearchableAdapter $ea)
    {
        $this->removeSearchData($object, $ea);
        $this->insertSearchData($object, $ea);
    }

    public function removeSearchData($object, SearchableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $identifierField = $ea->getSingleIdentifierFieldName($meta);
        $objectId = $meta->getReflectionProperty($identifierField)->getValue($object);
        $objectClass = get_class($object);

        $query = $om->createQuery(sprintf('SELECT so FROM %s so WHERE so.class = :class AND so.objectId = :objectId',
            self::STORED_OBJECT_CLASS));

        $query->setParameters(array(
            'class'             => $objectClass,
            'objectId'          => $objectId
        ));

        try {
            $storedObject = $query->getSingleResult();

            // There has to be a better way to do this. IndexedTokens are NOT removed by the DB
            $om->createQuery(sprintf('DELETE FROM %s it WHERE it.storedObject = :storedObject',
                self::INDEXED_TOKEN_CLASS))
                ->setParameter('storedObject', $storedObject)
                ->execute();
            $om->createQuery(sprintf('DELETE FROM %s so WHERE so.id = :id',
                self::STORED_OBJECT_CLASS))
                ->setParameter('id', $storedObject->getId())
                ->execute();
        } catch (NoResultException $e) {}
    }
}