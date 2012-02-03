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

    public function search($classes, array $conditions = array(), array $select = array(), $queryDefaultType = self::QUERY_TYPE_OR)
    {
        $qb = $this->getQueryBuilder($classes, $conditions, $select, $queryDefaultType);

        return $qb->getQuery()->getArrayResult();
    }

    public function getQueryBuilder($classes, array $conditions = array(), array $select = array(), $queryDefaultType = self::QUERY_TYPE_OR)
    {
        $qb = $this->createQueryBuilder(self::STORED_OBJECT_CLASS);

        $this->prepareSelectClause($classes, $qb, $select);
        $this->prepareWhereClause($classes, $qb, $conditions, $queryDefaultType);

        return $qb;
    }

    protected function prepareSelectClause($classes, QueryBuilder $qb, array $select)
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

    protected function prepareWhereClause($classes, QueryBuilder $qb, array $conditions, $queryDefaultType)
    {
        $queryDefaultType = $queryDefaultType === self::QUERY_TYPE_AND ? self::QUERY_TYPE_AND : self::QUERY_TYPE_OR;
        $expr = $queryDefaultType === self::QUERY_TYPE_AND ? $qb->expr()->andx() : $qb->expr()->orx();
        $valueAlias = self::INDEXED_TOKEN_ALIAS.'.';
        $classes = is_array($classes) ? $classes : array($classes);

        foreach ($conditions as $condition) {
            // For now we don't care about special operators
            if (!$this->isSpecialOperator($condition)) {
                $field = key($condition);
                $value = current($condition);
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
                    $fieldMapping = $meta->getFieldMapping($field);
                    $searchField = IndexedToken::getTokenFieldForORMType($fieldMapping['type']);

                    $tokenExpr = $qb->expr()->andx();
                    $tokenExpr->add($qb->expr()->eq(self::INDEXED_TOKEN_ALIAS.'.field', $qb->expr()->literal($field)));
                    $tokenExpr->add($qb->expr()->$method($valueAlias.$searchField, $literalValue));

                    $subExpr->add($tokenExpr);
                }

                $expr->add($subExpr);
            }
        }

        $qb->where($qb->expr()->in(sprintf('%s.class', self::STORED_OBJECT_ALIAS), $classes));

        if ($expr->__toString() !== '') {
            $qb->andWhere($expr);
        }
    }

    protected function isSpecialOperator(array $condition)
    {
        // TODO: Add support for special operators (to create subqueries, etc)

        return false;
    }
}