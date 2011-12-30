<?php

namespace Gedmo\Searchable\Entity\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\QueryBuilder;

class SearchableRepository extends EntityRepository
{
    const INDEXED_TOKEN_CLASS = 'Gedmo\Searchable\Entity\IndexedToken';
    const QUERY_TYPE_AND = 'AND';
    const QUERY_TYPE_OR = 'OR';
    const INDEXED_TOKEN_ALIAS = 'it';
    const STORED_OBJECT_ALIAS = 'so';
    const ID_FIELD = 'objectId';
    const CLASS_FIELD = 'class';
    const DATA_FIELD = 'data';

    public function search(array $filters = array(), array $select = array(), $queryDefaultType = self::QUERY_TYPE_OR)
    {
        $qb = $this->prepareQueryBuilder($filters, $select, $queryDefaultType);

        return $qb->getQuery()->getArrayResult();
    }

    public function prepareQueryBuilder(array $conditions = array(), array $select = array(), $queryDefaultType = self::QUERY_TYPE_OR)
    {
        $qb = $this->createQueryBuilder(self::INDEXED_TOKEN_CLASS);

        $this->prepareSelectClause($qb, $select);
        $this->prepareWhereClause($qb, $conditions);

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

            $selectedFields[] = ($selectedField === 'objectId' ? self::INDEXED_TOKEN_ALIAS : self::STORED_OBJECT_ALIAS).'.'.$selectedField;
        }

        $qb->select(implode(', ', $select))
            ->from(self::INDEXED_TOKEN_CLASS, self::INDEXED_TOKEN_ALIAS);

        if (in_array(self::STORED_OBJECT_ALIAS.'.data', $select)) {
            $qb->join(self::INDEXED_TOKEN_ALIAS.'.storedObject', self::STORED_OBJECT_ALIAS);
        }
    }

    protected function prepareWhereClause(QueryBuilder $qb, array $conditions)
    {
        
    }
}