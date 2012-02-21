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

// Can't use a mock object with a callback to change the entity on commit
// as the argument gets cloneed before it is passed to the callback. See
// http://stackoverflow.com/questions/4702132/modifing-objects-in-returncallback-of-phpunit-mocks
class ZendUpdateTestIdentityMapStub extends \Serquant\Entity\Registry\IdentityMap
{
    function commit($entity)
    {
        $entity->setId($entity->getId() + 1);
    }
}

class ZendUpdateTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;
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
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/roles.yaml'
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
        $evm = new \Doctrine\Common\EventManager();
        $this->persister = new \Serquant\Persistence\Zend(array(), $evm);
    }

    /**
     * @covers Serquant\Persistence\Zend::update
     */
    public function testUpdateOnEntityNotManaged()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $entityName;
        $entity->setId(1);
        $entity->setFirstName('Louis-Napoléon');
        $entity->setLastName('Bonaparte');

        $this->setExpectedException('Serquant\Persistence\Exception\RuntimeException');
        $this->persister->update($entity);
    }

    public function testUpdateOnBaseGatewayWithoutAnyChange()
    {
        // Force Table#update to say everything is ok
        $gateway = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\Role', array('update'));
        $gateway->expects($this->any())
              ->method('update')
              ->will($this->returnValue(1));

        // Create an entity
        $roleEntityClass = 'Serquant\Resource\Persistence\Zend\Role';
        $this->persister->setTableGateway($roleEntityClass, $gateway);

        $id = 1;
        $role = new $roleEntityClass;
        $role->setId($id);
        $role->setName('guest');

        // Replace the original registry by a stub containing the test entity
        // that will change the identifier if a changeset is committed
        $map = new ZendUpdateTestIdentityMapStub();
        $map->put($role, array($id));
        $property = new \ReflectionProperty($this->persister, 'loadedMap');
        $property->setAccessible(true);
        $property->setValue($this->persister, $map);

        // Don't change anything to the entity before updating
        // to have an empty changeset
        $this->persister->update($role);
        $this->assertEquals($id, $role->getId());
    }

    public function testUpdateOnCustomGatewayWithoutAnyChange()
    {
        // Force Table#update to say everything is ok
        $gateway = $this->getMock('Serquant\Persistence\Zend\Db\Table');
        $gateway->expects($this->any())
              ->method('update')
              ->will($this->returnValue(1));

        // Create an entity
        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $id = 1;
        $person = new $personEntityClass;
        $person->setId($id);
        $person->setFirstName('Louis-Napoléon');
        $person->setLastName('Bonaparte');

        // Replace the original registry by a stub containing the test entity
        // that will change the identifier if a changeset is committed
        $map = new ZendUpdateTestIdentityMapStub();
        $map->put($person, array($id));
        $property = new \ReflectionProperty($this->persister, 'loadedMap');
        $property->setAccessible(true);
        $property->setValue($this->persister, $map);

        // Don't change anything to the entity before updating
        // to have an empty changeset
        $this->persister->update($person);
        $this->assertEquals($id, $person->getId());
    }

    public function testUpdateNoEntityThrowsNoResultException()
    {
        // Force Table#update to say zero row was updated
        $gateway = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\Person', array('update'));
        $gateway->expects($this->any())
              ->method('update')
              ->will($this->returnValue(0));

        // Create an entity
        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $this->persister->setTableGateway($personEntityClass, $gateway);

        $id = 1;
        $person = new $personEntityClass;
        $person->setId($id);
        $person->setFirstName('Louis-Napoléon');
        $person->setLastName('Bonaparte');

        // Put it in the registry of managed entities
        $property = new \ReflectionProperty($this->persister, 'loadedMap');
        $property->setAccessible(true);
        $loadedMap = $property->getValue($this->persister);
        $loadedMap->put($person, array($id));

        // Change something to get a non-empty changeset on Persistence#update
        $person->setLastName('changed');

        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $this->persister->update($person);
    }

    public function testUpdateMultipleEntitiesThrowsNonUniqueResultException()
    {
        // Force Table#update to say multiple rows were updated
        $gateway = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\Person', array('update'));
        $gateway->expects($this->any())
              ->method('update')
              ->will($this->returnValue(2));

        // Create an entity
        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $this->persister->setTableGateway($personEntityClass, $gateway);

        $id = 1;
        $person = new $personEntityClass;
        $person->setId($id);
        $person->setFirstName('Louis-Napoléon');
        $person->setLastName('Bonaparte');

        // Put it in the registry of managed entities
        $property = new \ReflectionProperty($this->persister, 'loadedMap');
        $property->setAccessible(true);
        $loadedMap = $property->getValue($this->persister);
        $loadedMap->put($person, array($id));

        // Change something to get a non-empty changeset on Persistence#update
        $person->setLastName('changed');

        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $this->persister->update($person);
    }

    public function testUpdateEntity()
    {
        // Force Table#update to say everything is ok
        $gateway = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\Person', array('update'));
        $gateway->expects($this->any())
              ->method('update')
              ->will($this->returnValue(1));

        // Create an entity
        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $this->persister->setTableGateway($personEntityClass, $gateway);

        $id = 1;
        $person = new $personEntityClass;
        $person->setId($id);
        $person->setFirstName('Louis-Napoléon');
        $person->setLastName('Bonaparte');
        $oid = spl_object_hash($person);

        // Put it in the registry of managed entities (as if it was retrieved)
        $property = new \ReflectionProperty($this->persister, 'loadedMap');
        $property->setAccessible(true);
        $loadedMap = $property->getValue($this->persister);
        $loadedMap->put($person, array($id));

        // When the entity is registered, a copy of it is registered too
        // for later use, to compute change set.
        $originalEntitiesProp = new \ReflectionProperty($loadedMap, 'originalEntities');
        $originalEntitiesProp->setAccessible(true);
        $originalEntities = $originalEntitiesProp->getValue($loadedMap);
        // Should be the same
        $this->assertEquals($person, $originalEntities[$oid]);

        // Change something to get a non-empty changeset on Persistence#update
        $person->setLastName('changed');
        // Should be different now the entity has changed
        $this->assertNotEquals($person, $originalEntities[$oid]);

        $this->persister->update($person);
        $originalEntities = $originalEntitiesProp->getValue($loadedMap);
        // Should be the same now the changes have been committed
        $this->assertEquals($person, $originalEntities[$oid]);
    }
}
