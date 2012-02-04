<?php

namespace Gedmo\Searchable\Entity\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\QueryBuilder,
    Gedmo\Searchable\SearchableListener,
    Gedmo\Searchable\Processor\ProcessorManager,
    Gedmo\Searchable\Entity\IndexedToken;

class SearchableRepository extends EntityRepository
{
    const INDEXED_TOKEN_CLASS = 'Gedmo\Searchable\Entity\IndexedToken';
    const STORED_OBJECT_CLASS = 'Gedmo\Searchable\Entity\StoredObject';
    const QUERY_TYPE_AND = 'AND';
    const QUERY_TYPE_OR = 'OR';
    const INDEXED_TOKEN_ALIAS = 'it';
    const STORED_OBJECT_ALIAS = 'so';
    const ID_FIELD = 'objectId';
    const CLASS_FIELD = 'class';
    const DATA_FIELD = 'data';

    protected $processorManager;

    public function __construct($em, ClassMetadata $class)
    {
        parent::__construct($em, $class);

        // TODO: Is there a better way to obtain the configuration?
        foreach ($em->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof SearchableListener) {
                    $this->processorManager = new ProcessorManager($listener->getConfiguration($em, $class->name));
                }
            }
        }
    }

    public function search(array $conditions = array(), $queryDefaultType = self::QUERY_TYPE_OR, array $select = array())
    {
        return $this->getQuery($conditions, $queryDefaultType, $select)->getArrayResult();
    }

    public function getQuery(array $conditions = array(), $queryDefaultType = self::QUERY_TYPE_OR, array $select = array())
    {
        return $this->getQueryBuilder($conditions, $queryDefaultType, $select)->getQuery();
    }

    public function getQueryBuilder(array $conditions = array(), $queryDefaultType = self::QUERY_TYPE_OR, array $select = array())
    {
        $qb = $this->createQueryBuilder(self::STORED_OBJECT_CLASS);

        $this->prepareSelectClause($qb, $select);
        $this->prepareWhereClause($qb, $conditions, $queryDefaultType);

        return $qb;
    }

    protected function prepareSelectClause(QueryBuilder $qb, array $select)
    {
        $select = empty($select) ? array(self::ID_FIELD, self::CLASS_FIELD, self::DATA_FIELD) : $select;
        $selectedFields = array();
        
        foreach ($select as $selectedField) {
            if ($selectedField !== 'objectId' && $selectedField !== 'class' && $selectedField !== 'data') {
                throw new \InvalidArgumentException(sprintf('Field "%s" is invalid for SELECT statement in searchable query.',
                    $selectedField));
            }

            $selectedFields[] = self::STORED_OBJECT_ALIAS.'.'.$selectedField;
        }

        $qb->select('DISTINCT '.implode(', ', $selectedFields))
            ->from(self::STORED_OBJECT_CLASS, self::STORED_OBJECT_ALIAS)
            ->join(self::STORED_OBJECT_ALIAS.'.tokens', self::INDEXED_TOKEN_ALIAS);
    }

    protected function prepareWhereClause(QueryBuilder $qb, array $conditions, $queryDefaultType)
    {
        $queryDefaultType = $queryDefaultType === self::QUERY_TYPE_AND ? self::QUERY_TYPE_AND : self::QUERY_TYPE_OR;
        $expr = $qb->expr()->orx();
        $indexedTokenAlias = self::INDEXED_TOKEN_ALIAS;
        $indexedTokenAlias2 = $indexedTokenAlias.'_2';
        $storedObjectAlias = self::STORED_OBJECT_ALIAS;
        $storedObjectAlias2 = $storedObjectAlias.'_2';
        $expectedConditions = 0;
        
        foreach ($conditions as $fieldOrOperator => $condition) {
            // For now we don't care about special operators
            if (!$this->isSpecialOperator($fieldOrOperator)) {
                ++$expectedConditions;
                $field = $fieldOrOperator;
                $value = $condition;
                $operator = '=';
                
                if (is_array($value)) {
                    $operator = is_array($value) ? key($value) : '=';
                    $value = current($value);
                }

                $tokens = $this->processorManager->runQueryTimeProcessors($field, $value);

                $subExpr = $qb->expr()->orx();
                
                foreach ($tokens as $token) {
                    $literalValue = $qb->expr()->literal($token);

                    switch ($operator) {
                        case '>':
                            $method = 'gt';

                            break;
                        case '>=':
                            $method = 'gte';

                            break;
                        case '<':
                            $method = 'lt';

                            break;
                        case '<=':
                            $method = 'lte';

                            break;
                        case '!=':
                            $method = 'neq';

                            break;
                        default:
                            $method = 'eq';

                            break;
                    }

                    $meta = $this->getClassMetadata();
                    $fieldMapping = $meta->getFieldMapping(substr($field, strpos($field, '.') + 1));
                    $searchField = IndexedToken::getTokenFieldForORMType($fieldMapping['type']);

                    $tokenExpr = $qb->expr()->andx();
                    $tokenExpr->add($qb->expr()->eq($indexedTokenAlias2.'.field', $qb->expr()->literal($field)));
                    $tokenExpr->add($qb->expr()->$method($indexedTokenAlias2.'.'.$searchField, $literalValue));

                    $subExpr->add($tokenExpr);
                }

                $expr->add($subExpr);
            }
        }

        if ($expr->__toString() !== '') {
            $subQuery = $queryDefaultType === self::QUERY_TYPE_AND ?
                $expectedConditions.' = (SELECT COUNT('.$indexedTokenAlias2.') FROM '.self::INDEXED_TOKEN_CLASS.' '.$indexedTokenAlias2.' JOIN '.$indexedTokenAlias2.'.storedObject '.$storedObjectAlias2.' WHERE ('.$expr.') AND '.$storedObjectAlias.'.id = '.$storedObjectAlias2.'.id)' :
                $indexedTokenAlias.' IN (SELECT '.$indexedTokenAlias2.' FROM '.self::INDEXED_TOKEN_CLASS.' '.$indexedTokenAlias2.' WHERE '.$expr.')';
            $qb->where($subQuery);
        }
    }

    protected function isSpecialOperator($operator)
    {
        // TODO: Add support for special operators (to create subqueries, etc)

        return false;
    }
}