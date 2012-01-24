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

use Serquant\Persistence\Zend\Db\Table;

class ZendUpdateTestOrmlessStub extends \Serquant\Entity\Registry\Ormless
{
    public function commitChangeSet($entity)
    {
        $entity->id += 1; // Change the identifier
    }
}

class ZendUpdateTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;
    private $em;
    private $persister;

    private function setupDatabase()
    {
        $dataSets = array();
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/people.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/issues.yaml'
        );
        $data = new \PHPUnit_Extensions_Database_DataSet_CompositeDataSet(
            $dataSets
        );

        $this->db = $this->getTestAdapter();
        $connection = new \Zend_Test_PHPUnit_Db_Connection($this->db, null);
        $tester = new \Zend_Test_PHPUnit_Db_SimpleTester($connection);
        $tester->setupDatabase($data);
    }

    protected function setUp()
    {
        $this->setupDatabase();
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    public function testGetWhereClauseWithoutId()
    {
        $method = new \ReflectionMethod($this->persister, 'getWhereClause');
        $method->setAccessible(true);

        $entity = new \Serquant\Resource\Persistence\Zend\Person();
        $where = $method->invoke($this->persister, $entity);
        $this->assertEquals(array(), $where);
    }

    public function testGetWhereClauseWithScalarId()
    {
        $method = new \ReflectionMethod($this->persister, 'getWhereClause');
        $method->setAccessible(true);

        $entity = new \Serquant\Resource\Persistence\Zend\Person();
        $entity->id = 1;
        $where = $method->invoke($this->persister, $entity);
        $this->assertEquals(array('id = ?' => 1), $where);
    }

    public function testGetWhereClauseWithCompoundId()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\Message();
        $entity->language = 'fr';
        $entity->key = 'error';

        $method = new \ReflectionMethod($this->persister, 'getWhereClause');
        $method->setAccessible(true);
        $where = $method->invoke($this->persister, $entity);

        $this->assertEquals(array('language = ?' => 'fr', 'key = ?' => 'error'), $where);
    }

    public function testUpdateOnEntityNotManaged()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $entityName;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $this->setExpectedException('Serquant\Persistence\Exception\RuntimeException');
        $this->persister->update($entity);
    }

    public function testUpdateWithoutAnyChange()
    {
        // Create an entity
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $entityName;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        // Replace the original registry by a stub containing the test entity
        // that will change the identifier if a changeset is committed
        $stub = new ZendUpdateTestOrmlessStub($this->persister->getMetadataFactory());
        $stub->put($entity);
        $property = new \ReflectionProperty($this->persister, 'loadedEntities');
        $property->setAccessible(true);
        $property->setValue($this->persister, $stub);

        // Force Persister#update to tell everything is ok
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('update')
              ->will($this->returnValue(1));
        $this->persister->setTableGateway($entityName, $table);

        // Don't change anything to the entity before updating to have an empty changeset
        $this->persister->update($entity, array('id = ?' => 1));
        $this->assertEquals(1, $entity->id);
    }

    public function testUpdateNoEntityThrowsNoResultException()
    {
        // Create an entity
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $entityName;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        // Put it in the registry of managed entities
        $property = new \ReflectionProperty($this->persister, 'loadedEntities');
        $property->setAccessible(true);
        $loadedEntities = $property->getValue($this->persister);
        $loadedEntities->put($entity);

        // Change something to get a non-empty changeset on Service#update
        $entity->lastName = 'Bonaparte';

        // Force Persister#update to tell zero row was updated
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('update')
              ->will($this->returnValue(0));
        $this->persister->setTableGateway($entityName, $table);

        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $this->persister->update($entity, array('id = ?' => 1));
    }

    public function testUpdateMultipleEntitiesThrowsNonUniqueResultException()
    {
        // Create an entity
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $entityName;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        // Put it in the registry of managed entities
        $property = new \ReflectionProperty($this->persister, 'loadedEntities');
        $property->setAccessible(true);
        $loadedEntities = $property->getValue($this->persister);
        $loadedEntities->put($entity);

        // Change something to get a non-empty changeset on Service#update
        $entity->lastName = 'Bonaparte';

        // Force Persister#update to tell multiple rows were updated
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('update')
              ->will($this->returnValue(2));
        $this->persister->setTableGateway($entityName, $table);

        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $this->persister->update($entity, array('id = ?' => 1));
    }

    public function testUpdateEntity()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $entityName;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';
        $oid = spl_object_hash($entity);

        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('update')
              ->will($this->returnValue(1));
        $this->persister->setTableGateway($entityName, $table);

        $loadedEntitiesProp = new \ReflectionProperty($this->persister, 'loadedEntities');
        $loadedEntitiesProp->setAccessible(true);
        $loadedEntities = $loadedEntitiesProp->getValue($this->persister);
        $loadedEntities->put($entity);

        // When the entity is registered, a copy of it is registered too
        // for later use, to compute change set.
        $originalEntityDataProp = new \ReflectionProperty($loadedEntities, 'originalEntityData');
        $originalEntityDataProp->setAccessible(true);
        $originalEntityData = $originalEntityDataProp->getValue($loadedEntities);

        // This copy is a clone of the entity.
        $orgEntity1 = $originalEntityData[$oid];
        $orgHash1 = spl_object_hash($orgEntity1);

        // Then commit the changes and check if a new copy of the entity has been made.
        $entity->id = 2;
        $entity->firstName = 'John';
        $entity->lastName = 'Adams';
        $this->persister->update($entity);

        $originalEntityData = $originalEntityDataProp->getValue($loadedEntities);
        $orgEntity2 = $originalEntityData[$oid];
        $orgHash2 = spl_object_hash($orgEntity2);
        $this->assertNotEquals($orgHash1, $orgHash2);
        $this->assertEquals($entity, $orgEntity2);
    }
}
