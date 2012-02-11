<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\Persistence;

use Serquant\Persistence\Doctrine;

/**
 * Test class for Doctrine
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class DoctrineTest extends \Serquant\Resource\Persistence\OrmFunctionalTestCase
{
    private $em;
    private $persister;
    private $entityName;

    private function setupDatabase()
    {
        $this->entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';

        $dataSet = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/users.yaml'
        );

        $this->db = $this->getTestAdapter();
        $connection = new \Zend_Test_PHPUnit_Db_Connection($this->db, null);
        $tester = new \Zend_Test_PHPUnit_Db_SimpleTester($connection);
        $tester->setupDatabase($dataSet);
    }

    protected function setUp()
    {
        $this->setupDatabase();
        $this->em = $this->getTestEntityManager();
        $this->persister = new Doctrine($this->em);
    }

    public function testFetchAll()
    {
        $entities = $this->persister->fetchAll($this->entityName, array());
        $this->assertInternalType('array', $entities);
        foreach ($entities as $entity) {
            $this->assertInstanceOf($this->entityName, $entity);
        }
    }

    public function testFetchOneThrowingNoResultException()
    {
        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $entity = $this->persister->fetchOne(
            $this->entityName,
            array('status' => 'missing')
        );
    }

    public function testFetchOneThrowingNonUniqueResultException()
    {
        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $entity = $this->persister->fetchOne(
            $this->entityName,
            array('status' => 'available')
        );
    }

    public function testFetchOne()
    {
        $entity = $this->persister->fetchOne(
            $this->entityName,
            array('username' => 'a')
        );
        $this->assertInstanceOf($this->entityName, $entity);
        $this->assertEquals('a', $entity->username);
    }

    public function testFetchPageWithoutLimit()
    {
        $paginator = $this->persister->fetchPage(
            $this->entityName,
            array('username' => 'a')
        );
        $this->assertInstanceOf('\Zend_Paginator', $paginator);
    }

    public function testFetchPageWithLimit()
    {
        $paginator = $this->persister->fetchPage(
            $this->entityName,
            array('status' => 'available', 'limit(0,10)')
        );
        $this->assertInstanceOf('\Zend_Paginator', $paginator);
    }

    public function testFetchPairs()
    {
        $pairs = $this->persister->fetchPairs(
            $this->entityName,
            'username',
            'name',
            array('status' => 'available')
        );
        $this->assertInternalType('array', $pairs);
        $this->assertCount(2, $pairs);
        $diff = array_diff_assoc(array('a' => 'Alice', 'b' => 'Bob'), $pairs);
        $this->assertTrue(empty($diff));
    }

    public function testCreate()
    {
        $entity = new $this->entityName;
        $entity->status = 'temporary';
        $entity->username = 'thomas';
        $entity->name = 'Jefferson';
        $this->persister->create($entity);

        $actual = $this->em->find($this->entityName, $entity->id);
        $this->assertEquals($entity, $actual);

        $this->em->remove($entity);
        $this->em->flush();
    }

    public function testRetrieve()
    {
        $actual = $this->persister->retrieve($this->entityName, 1);
        $this->assertInstanceOf($this->entityName, $actual);
        $this->assertEquals(1, $actual->id);
        $this->assertEquals('available', $actual->status);
        $this->assertEquals('a', $actual->username);
        $this->assertEquals('Alice', $actual->name);
    }

    public function testUpdate()
    {
        $expected = $this->persister->retrieve($this->entityName, 1);
        $expected->status = 'updated';
        $this->persister->update($expected);

        $actual = $this->em->find($this->entityName, 1);
        $this->assertEquals($expected, $actual);
    }

    public function testDelete()
    {
        // Persist an entity
        $entity = new $this->entityName;
        $entity->status = 'temporary';
        $entity->username = 'e';
        $entity->name = 'Emma';
        $this->em->persist($entity);
        $this->em->flush();

        // Check it was successful
        $found = true;
        try {
            $actual = $this->em->find($this->entityName, $entity->id);
        } catch (\Exception $e) {
            $found = false;
        }
        $this->assertTrue($found);

        // Delete it
        $deletedId = $entity->id;
        $this->persister->delete($entity);

        // And check that no result is found now
        $this->assertNull($this->em->find($this->entityName, $deletedId));
    }
}