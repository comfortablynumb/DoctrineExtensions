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
        $this->searchableRepository = new SearchableRepository($this->em, $this->em->getClassMetadata(self::ARTICLE));

        $this->populate();
    }
    
    public function testSearchableRepository()
    {
        $results = $this->searchableRepository->search();

        $this->assertEquals(4, count($results));

        $results = $this->searchableRepository->search(array(self::ARTICLE.'.title' => 'title wonderful article'));

        $this->assertEquals(1, count($results));

        $results = $this->searchableRepository->search(array(self::ARTICLE.'.visits' => array('>=' => 100)));

        $this->assertEquals(2, count($results));

        $results = $this->searchableRepository->search(array(self::ARTICLE.'.isPublished' => true));

        $this->assertEquals(1, count($results));

        $results = $this->searchableRepository->search(array(self::ARTICLE.'.createdAt' => array('<=' => '2009-02-16 00:00:00')));

        $this->assertEquals(1, count($results));

        $results = $this->searchableRepository->search(array(self::ARTICLE.'.rating' => array('>=' => 50)));

        $this->assertEquals(1, count($results));

        $results = $this->searchableRepository->search(array(
            self::ARTICLE.'.rating' => array('<=' => 50),
            self::ARTICLE.'.visits' => array('>=' => 3400)), SearchableRepository::QUERY_TYPE_AND);

        $this->assertEquals(1, count($results));
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
        $date = \DateTime::createFromFormat('j-M-Y', '15-Feb-2009');

        $art1 = $this->createArticle();
        $art1->setTitle('New title for this wonderful Article');
        $art1->setCategory('Computers');
        $art1->setVisits(100);
        $art1->setIsPublished(true);
        $art1->setCreatedAt($date);
        $art1->setRating((double) 60);

        $art2 = $this->createArticle();
        $art2->setTitle('Barcelona wins again vs Real Madrid');
        $art2->setCategory('Soccer');
        $art2->setVisits(3500);

        $art3 = $this->createArticle();
        $art3->setTitle('Girls in Mars detected!');
        $art3->setCategory('Science');
        $art3->setVisits(12);

        $art4 = $this->createArticle();
        $art4->setTitle('How to cook a chicken?');
        $art4->setCategory('Food');
        $art4->setVisits(42);

        $this->em->persist($art1);
        $this->em->persist($art2);
        $this->em->persist($art3);
        $this->em->persist($art4);

        $this->em->flush();
    }

    private function createArticle()
    {
        $class = self::ARTICLE;
        return new $class;
    }
}