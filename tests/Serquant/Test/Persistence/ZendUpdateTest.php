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

class ZendUpdateTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;

    private $em;

    private $persister;

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
        \Zend_Db_Table::setDefaultAdapter($this->db);
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
        $method = new \ReflectionMethod($this->persister, 'getWhereClause');
        $method->setAccessible(true);

        $entity = new \Serquant\Resource\Persistence\Zend\Message();
        $entity->language = 'fr';
        $entity->key = 'error';
        $where = $method->invoke($this->persister, $entity);
        $this->assertEquals(array('language = ?' => 'fr', 'key = ?' => 'error'), $where);
    }

    public function testUpdateOnEntityNotManaged()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $this->setExpectedException('Serquant\Persistence\Exception\RuntimeException');
        $this->persister->update($entity);
    }

    public function testUpdateNoEntityThrowsNoResultException()
    {
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('update')
              ->will($this->returnValue(0));
        $this->persister->setTableGateway($table);

        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $property = new \ReflectionProperty($this->persister, 'loadedEntities');
        $property->setAccessible(true);
        $loadedEntities = $property->getValue($this->persister);
        $loadedEntities->put($entity);

        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $this->persister->update($entity, array('id = ?' => 1));
    }

    public function testUpdateMultipleEntitiesThrowsNonUniqueResultException()
    {
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('update')
              ->will($this->returnValue(2));
        $this->persister->setTableGateway($table);

        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $property = new \ReflectionProperty($this->persister, 'loadedEntities');
        $property->setAccessible(true);
        $loadedEntities = $property->getValue($this->persister);
        $loadedEntities->put($entity);

        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $this->persister->update($entity, array('id = ?' => 1));
    }

    public function testUpdateEntity()
    {
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('update')
              ->will($this->returnValue(1));
        $this->persister->setTableGateway($table);

        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';
        $oid = spl_object_hash($entity);

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
