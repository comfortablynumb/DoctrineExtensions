<?php

namespace Gedmo\Searchable;

use Tool\BaseTestCaseORM;
use Doctrine\Common\EventManager;
use Doctrine\Common\Util\Debug,
    Searchable\Fixture\Entity\Article;

/**
 * These are tests for searchable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Searchable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SearchableListenerTest extends BaseTestCaseORM
{
    const ARTICLE = 'Searchable\Fixture\Entity\Article';
    const INDEXED_TOKEN_CLASS = 'Gedmo\Searchable\Entity\IndexedToken';
    const STORED_OBJECT_CLASS = 'Gedmo\Searchable\Entity\StoredObject';

    private $searchableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->searchableListener = new SearchableListener();
        $evm->addEventSubscriber($this->searchableListener);

        $this->em = $this->getMockSqliteEntityManager($evm);
    }

    public function testSearchableIndexing()
    {
        $indexedTokensRepo = $this->em->getRepository(self::INDEXED_TOKEN_CLASS);
        $storedObjectsRepo = $this->em->getRepository(self::STORED_OBJECT_CLASS);
        $articleTitle = '  Title    with   spaces      ';
        $art0 = new Article();
        $art0->setTitle($articleTitle);

        $this->em->persist($art0);
        $this->em->flush();

        $parameters = array(
            'class'         => get_class($art0),
            'objectId'      => $art0->getId()
        );
        $storedObject = $storedObjectsRepo->findOneBy($parameters);
        $tokens = $this->em->createQuery(sprintf('SELECT it FROM %s it WHERE it.storedObject = :storedObject',
            self::INDEXED_TOKEN_CLASS))
            ->setParameter('storedObject', $storedObject)
            ->getArrayResult();

        /** INSERTING OBJECTS  */

        // First we check the indexed tokens
        $this->assertEquals(4, count($tokens));

        foreach ($tokens as $tokenData) {
            $token = $tokenData['token'];

            switch ($tokenData['field']) {
                case 'id':
                    $this->assertEquals(1, $token);

                    break;
                case 'title':
                    $this->assertTrue($token === 'Title' || $token === 'with' || $token === 'spaces');

                    break;
                default:
                    $this->fail(sprintf('There was an indexed token for an invalid field "%s"', $tokenData['field']));
            }
        }

        // Now we check the stored object
        $this->assertEquals(array(
            'id'            => $art0->getId(),
            'title'         => $articleTitle
        ), $storedObject->getData());

        /** UPDATING OBJECTS  */
        $articleTitle = ' NEW    TITLE   ';
        $art0->setTitle($articleTitle);
        $this->em->persist($art0);
        $this->em->flush();

        $storedObject = $storedObjectsRepo->findOneBy($parameters);
        $tokens = $this->em->createQuery(sprintf('SELECT it FROM %s it WHERE it.storedObject = :storedObject',
            self::INDEXED_TOKEN_CLASS))
            ->setParameter('storedObject', $storedObject)
            ->getArrayResult();

        // First we check the indexed tokens
        $this->assertEquals(3, count($tokens));

        foreach ($tokens as $tokenData) {
            $token = $tokenData['token'];

            switch ($tokenData['field']) {
                case 'id':
                    $this->assertEquals(1, $token);

                    break;
                case 'title':
                    $this->assertTrue($token === 'NEW' || $token === 'TITLE');

                    break;
                default:
                    $this->fail(sprintf('There was an indexed token for an invalid field "%s"', $tokenData['field']));
            }
        }

        // Now we check the stored object
        $this->assertEquals(array(
            'id'            => $art0->getId(),
            'title'         => $articleTitle
        ), $storedObject->getData());

        /** REMOVING OBJECTS  */
        $this->em->remove($art0);
        $this->em->flush();

        $storedObject = $storedObjectsRepo->findOneBy($parameters);
        $tokens = $indexedTokensRepo->findBy(array());

        $this->assertEmpty($tokens);
        $this->assertNull($storedObject);
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