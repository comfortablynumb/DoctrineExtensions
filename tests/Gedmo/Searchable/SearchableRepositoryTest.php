<?php

namespace Gedmo\Searchable;

use Tool\BaseTestCaseORM;
use Doctrine\Common\EventManager;
use Doctrine\Common\Util\Debug,
    Searchable\Fixture\Entity\Article,
    Gedmo\Searchable\Entity\Repository\SearchableRepository;

/**
 * These are tests for searchable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Searchable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SearchableRepositoryTest extends BaseTestCaseORM
{
    const ARTICLE = 'Searchable\Fixture\Entity\Article';
    const INDEXED_TOKEN_CLASS = 'Gedmo\Searchable\Entity\IndexedToken';
    const STORED_OBJECT_CLASS = 'Gedmo\Searchable\Entity\StoredObject';

    private $searchableListener;
    private $searchableRepository;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->searchableListener = new SearchableListener();
        $evm->addEventSubscriber($this->searchableListener);

        $this->em = $this->getMockSqliteEntityManager($evm);
        $this->searchableRepository = new SearchableRepository($this->em, $this->em->getClassMetadata(self::INDEXED_TOKEN_CLASS));
    }
    
    public function testSearchableRepository()
    {
        
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::INDEXED_TOKEN_CLASS,
            self::STORED_OBJECT_CLASS
        );
    }

    private function populate()
    {

    }
}