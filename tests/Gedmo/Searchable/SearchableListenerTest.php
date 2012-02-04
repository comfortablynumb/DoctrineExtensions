<?php

namespace Gedmo\Searchable;

use Tool\BaseTestCaseORM;
use Doctrine\Common\EventManager;
use Doctrine\Common\Util\Debug,
    Searchable\Fixture\Entity\Article,
    Gedmo\Searchable\Entity\IndexedToken;

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
        $date = new \DateTime();
        $createdAt = $date->format('Y-m-d H:i:s');
        $rating = (double) 99.5;
        $art0 = new Article();
        $art0->setTitle($articleTitle);
        $art0->setCreatedAt($date);
        $art0->setRating($rating);

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
        $this->assertEquals(10, count($tokens));

        foreach ($tokens as $tokenData) {
            $token = $tokenData[IndexedToken::getTokenFieldForORMType($tokenData['type'])];

            switch ($tokenData['field']) {
                case self::ARTICLE.'.id':
                    $this->assertEquals(1, $token);
                    $this->assertEquals($token, $tokenData['integerToken']);

                    break;
                case self::ARTICLE.'.title':
                    $this->assertTrue($token === 'title' || $token === 'with' || $token === 'spaces');
                    $this->assertEquals($token, $tokenData['stringToken']);

                    break;
                case self::ARTICLE.'.category':
                    $this->assertTrue($token === 'default' || $token === 'category');
                    $this->assertEquals($token, $tokenData['stringToken']);

                    break;
                case self::ARTICLE.'.visits':
                    $this->assertEquals(0, $token);
                    $this->assertEquals($token, $tokenData['integerToken']);

                    break;
                case self::ARTICLE.'.isPublished':
                    $this->assertEquals(false, $token);
                    $this->assertEquals($token, $tokenData['booleanToken']);

                    break;
                case self::ARTICLE.'.createdAt':
                    $this->assertEquals($createdAt, $token->format('Y-m-d H:i:s'));
                    $this->assertEquals($token, $tokenData['dateTimeToken']);

                    break;
                case self::ARTICLE.'.rating':
                    $this->assertEquals($rating, $token);
                    $this->assertEquals($token, $tokenData['decimalToken']);

                    break;
                default:
                    $this->fail(sprintf('There was an indexed token for an invalid field "%s"', $tokenData['field']));
            }
        }

        // Now we check the stored object
        $this->assertEquals(array(
            'id'            => $art0->getId(),
            'title'         => $articleTitle,
            'category'      => 'Default Category',
            'visits'        => 0,
            'isPublished'   => false,
            'createdAt'     => $art0->getCreatedAt(),
            'rating'        => $rating
        ), $storedObject->getData());

        /** UPDATING OBJECTS  */
        $articleTitle = ' NEW    TITLE   ';
        $rating = (double) 80.34;
        $date = new \DateTime();
        $createdAt = $date->format('Y-m-d H:i:s');

        $art0->setTitle($articleTitle);
        $art0->setCreatedAt($date);
        $art0->setRating($rating);
        
        $this->em->persist($art0);
        $this->em->flush();

        $storedObject = $storedObjectsRepo->findOneBy($parameters);
        $tokens = $this->em->createQuery(sprintf('SELECT it FROM %s it WHERE it.storedObject = :storedObject',
            self::INDEXED_TOKEN_CLASS))
            ->setParameter('storedObject', $storedObject)
            ->getArrayResult();

        // First we check the indexed tokens
        $this->assertEquals(9, count($tokens));

        foreach ($tokens as $tokenData) {
            $token = $tokenData[IndexedToken::getTokenFieldForORMType($tokenData['type'])];

            switch ($tokenData['field']) {
                case self::ARTICLE.'.id':
                    $this->assertEquals(1, $token);
                    $this->assertEquals($token, $tokenData['integerToken']);

                    break;
                case self::ARTICLE.'.title':
                    $this->assertTrue($token === 'new' || $token === 'title');
                    $this->assertEquals($token, $tokenData['stringToken']);

                    break;
                case self::ARTICLE.'.category':
                    $this->assertTrue($token === 'default' || $token === 'category');
                    $this->assertEquals($token, $tokenData['stringToken']);

                    break;
                case self::ARTICLE.'.visits':
                    $this->assertEquals(0, $token);
                    $this->assertEquals($token, $tokenData['integerToken']);

                    break;
                case self::ARTICLE.'.isPublished':
                    $this->assertEquals(false, $token);
                    $this->assertEquals($token, $tokenData['booleanToken']);

                    break;
                case self::ARTICLE.'.createdAt':
                    $this->assertEquals($createdAt, $token->format('Y-m-d H:i:s'));
                    $this->assertEquals($token, $tokenData['dateTimeToken']);

                    break;
                case self::ARTICLE.'.rating':
                    $this->assertEquals($rating, $token);
                    $this->assertEquals($token, $tokenData['decimalToken']);

                    break;
                default:
                    $this->fail(sprintf('There was an indexed token for an invalid field "%s"', $tokenData['field']));
            }
        }

        // Now we check the stored object
        $this->assertEquals(array(
            'id'            => $art0->getId(),
            'title'         => $articleTitle,
            'category'      => 'Default Category',
            'visits'        => 0,
            'isPublished'   => false,
            'createdAt'     => $art0->getCreatedAt(),
            'rating'        => $rating
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
}