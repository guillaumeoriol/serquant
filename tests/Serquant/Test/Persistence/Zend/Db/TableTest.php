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
namespace Serquant\Test\Persistence\Zend\Db;

use Serquant\Persistence\Zend\Configuration;

/**
 * Test class for Table
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class TableTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;

    private $persister;

    private function setupDatabase()
    {
        $dataSets = array();

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__)) . '/../../fixture/permissions.yaml'
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
        $config = new Configuration();
        $config->setEventManager($evm);
        $config->setProxyNamespace('Serquant\Resource\Persistence\Zend\Proxy');
        $this->persister = new \Serquant\Persistence\Zend\Persister($config);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::hasColumn
     */
    public function testHasColumn()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;

        $method = new \ReflectionMethod($table, 'hasColumn');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($table, 'firstName'));
        $this->assertFalse($method->invoke($table, 'invalid'));
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::getColumn
     */
    public function testGetColumn()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;

        $method = new \ReflectionMethod($table, 'getColumn');
        $method->setAccessible(true);

        $this->assertEquals('id', $method->invoke($table, 'id'));
        $this->assertEquals('first_name', $method->invoke($table, 'firstName'));
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::hasField
     */
    public function testHasField()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;

        $method = new \ReflectionMethod($table, 'hasField');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($table, 'first_name'));
        $this->assertFalse($method->invoke($table, 'invalid'));
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::getField
     */
    public function testGetField()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;

        $method = new \ReflectionMethod($table, 'getField');
        $method->setAccessible(true);

        $this->assertEquals('id', $method->invoke($table, 'id'));
        $this->assertEquals('firstName', $method->invoke($table, 'first_name'));
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::setPersister
     */
    public function testSetPersister()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $table->setPersister($this->persister);

        $method = new \ReflectionMethod($table, 'getPersister');
        $method->setAccessible(true);

        $this->assertSame($this->persister, $method->invoke($table));
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::getPersister
     */
    public function testGetPersisterMissing()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $this->setExpectedException('Serquant\Persistence\Exception\RuntimeException', null, 10);
        $method = new \ReflectionMethod($table, 'getPersister');
        $method->setAccessible(true);
        $method->invoke($table);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::selectPairs
     */
    public function testSelectPairsWithInvalidProperty()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $this->setExpectedException('Serquant\Persistence\Exception\RuntimeException', null, 20);
        $table->selectPairs('id', 'invalid');
    }

    public function testSelectPairsWithValidProperties()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $select = $table->selectPairs('id', 'lastName');
        $this->assertEquals('SELECT `people`.`id`, `people`.`last_name` FROM `people`', (string) $select);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::getDatabasePlatform
     */
    public function testGetDatabasePlatformWithWrongAdapter()
    {
        $table = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\User');
        $table->expects($this->any())
              ->method('getAdapter')
              ->will($this->returnValue(new \stdClass()));

        $this->setExpectedException('Serquant\Persistence\Exception\RuntimeException');

        $method = new \ReflectionMethod($table, 'getDatabasePlatform');
        $method->setAccessible(true);
        $platform = $method->invoke($table);
    }

    public function testGetDatabasePlatform()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $method = new \ReflectionMethod($table, 'getDatabasePlatform');
        $method->setAccessible(true);
        $platform = $method->invoke($table);
        $this->assertInstanceOf('Doctrine\DBAL\Platforms\AbstractPlatform', $platform);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::getProperties
     */
    public function testGetProperties()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Car;

        $method = new \ReflectionMethod($table, 'getProperties');
        $method->setAccessible(true);
        $properties = $method->invoke($table);
        $this->assertInternalType('array', $properties);
        foreach ($properties as $prop) {
            $this->assertInstanceOf('ReflectionProperty', $prop);
        }
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::newInstance
     */
    public function testNewInstance()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Car;
        $instance = $table->newInstance();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Zend\Car', $instance);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::newProxyInstance
     */
    public function testNewProxyInstance()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $table->setPersister($this->persister);

        $proxy = $table->newProxyInstance(array('id' => 1));
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $proxy);
        $this->assertInstanceOf('Serquant\Resource\Persistence\Zend\Person', $proxy);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::extractPrimaryKey
     */
    public function testExtractPrimaryKeyWithSimpleKey()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\User;

        $actual = $table->extractPrimaryKey(array('id' => 1, 'username' => 'a'));
        $this->assertEquals(array('id' => 1), $actual);
    }

    public function testExtractPrimaryKeyWithCompoundKey()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Permission;

        $actual = $table->extractPrimaryKey(array('resource' => 34, 'role' => 12));
        $this->assertEquals(array('role' => 12, 'resource' => 34), $actual);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::loadEntity
     */
    public function testLoadEntityWithoutCustomMapper()
    {
        $id = 1;
        $name = 'member';

        $entityName = 'Serquant\Resource\Persistence\Zend\Role';
        $expected = new $entityName;
        $expected->setId($id);
        $expected->setName($name);

        $row = array(
            'id' => $id,
            'name' => $name
        );

        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Role;
        $actual = $table->newInstance();
        $table->loadEntity($actual, $row);
        $this->assertEquals($expected, $actual);
    }

    public function testLoadEntityWithCustomMapperPerformingConversionOnly()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';

        $expected = new $entityName;
        $expected->setId(1);
        $expected->setStatus('offline');
        $expected->setUsername('a');
        $expected->setName('Alice');

        $row = array(
            'id' => '1',
            'status' => 'offline',
            'username' => 'a',
            'name' => 'Alice'
        );

        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $actual = $table->newInstance();
        $table->loadEntity($actual, $row);
        $this->assertEquals($expected, $actual);
    }

    public function testLoadEntityWithCustomMapperPerformingConversionAndMapping()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';

        $expected = new $entityName;
        $expected->setId(1);
        $expected->setFirstName('Louis-Napoléon');
        $expected->setLastName('Bonaparte');

        $row = array(
            'id' => '1',
            'first_name' => 'Louis-Napoléon',
            'last_name' => 'Bonaparte'
        );

        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $actual = $table->newInstance();
        $table->loadEntity($actual, $row);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::loadRow
     */
    public function testLoadRowWithDefaultImplementation()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';

        $entity = new $entityName;
        $entity->setId(1);
        $entity->setStatus('offline');
        $entity->setUsername('a');
        $entity->setName('Alice');

        $row = array(
            'id' => '1',
            'status' => 'offline',
            'username' => 'a',
            'name' => 'Alice'
        );

        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\User;
        $actual = $table->loadRow($entity);
        $this->assertInternalType('array', $actual);
        $this->assertEquals($row, $actual);
    }

    public function testLoadRowWithCustomCode()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';

        $entity = new $entityName;
        $entity->setId(1);
        $entity->setFirstName('Louis-Napoléon');
        $entity->setLastName('Bonaparte');

        $row = array(
            'id' => '1',
            'first_name' => 'Louis-Napoléon',
            'last_name' => 'Bonaparte'
        );

        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $actual = $table->loadRow($entity);
        $this->assertInternalType('array', $actual);
        $this->assertEquals($row, $actual);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::computeChangeSet
     */
    public function testComputeChangeSetOnBaseGateway()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Role';

        $old = new $entityName;
        $old->setId(1);
        $old->setName('guest');

        $new = clone $old;
        $new->setName('changed');

        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Role;
        $changeSet = $table->computeChangeSet($old, $new);
        $this->assertInternalType('array', $changeSet);
        $this->assertEquals(array('name' => 'changed'), $changeSet);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::getWhereClause
     */
    public function testGetWhereClauseWithoutId()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $method = new \ReflectionMethod($table, 'getWhereClause');
        $method->setAccessible(true);

        $where = $method->invoke($table, array());
        $this->assertEquals(array(), $where);
    }

    public function testGetWhereClauseWithSimpleId()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $method = new \ReflectionMethod($table, 'getWhereClause');
        $method->setAccessible(true);

        $where = $method->invoke($table, array('id' => 1));
        $this->assertEquals(array('id = ?' => 1), $where);
    }

    public function testGetWhereClauseWithCompoundId()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Permission;
        $method = new \ReflectionMethod($table, 'getWhereClause');
        $method->setAccessible(true);

        $where = $method->invoke($table, array('role' => 1, 'resource' => 2));
        $this->assertEquals(array('role = ?' => 1, 'resource = ?' => 2), $where);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::updateEntityIdentifier
     */
    public function testUpdateEntityIdentifier()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $entityName;
        $entity->setFirstName('Louis-Napoléon');
        $entity->setLastName('Bonaparte');

        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $table->updateEntityIdentifier($entity, array('id' => 1));
        $this->assertEquals(1, $entity->getId());
    }

    /**
     * The entity identifier shall not be updated when Table#_sequence is FALSE
     */
    public function testUpdateEntityIdentifierOnTableHavingApplicationAssignedKey()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\Permission';
        $entity = new $entityName;

        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Permission;
        $table->updateEntityIdentifier($entity, array('role' => 1, 'resource' => 2));
        $this->assertNull($entity->getRole());
        $this->assertNull($entity->getResource());
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::insert
     */
    public function testInsertWithSimpleKey()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $table->setPersister($this->persister);
        $pk = $table->insert(array('first_name' => 'Charles', 'last_name' => 'de Gaulle'));
        $this->assertInternalType('array', $pk);
    }

    public function testInsertWithCompoundKey()
    {
        $table = new \Serquant\Resource\Persistence\Zend\Db\Table\Permission;
        $table->setPersister($this->persister);
        $pk = $table->insert(array('role' => 1, 'resource' => 9));
        $this->assertInternalType('array', $pk);
        // The primary key is returned even if _sequence is FALSE
        $this->assertEquals(array('role' => 1, 'resource' => 9), $pk);
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::update
     */
    public function testUpdate()
    {
        // Throw an exception when getWhereClause is invoked...
        $table = $this->getMock('Serquant\Persistence\Zend\Db\Table', array('getWhereClause'));
        $table->expects($this->any())
              ->method('getWhereClause')
              ->will($this->throwException(new \DomainException));

        // ...so we can be sure it has been called
        $this->setExpectedException('DomainException');
        $table->update(array(), array(1));
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::delete
     */
    public function testDelete()
    {
        // Throw an exception when getWhereClause is invoked...
        $table = $this->getMock('Serquant\Persistence\Zend\Db\Table', array('getWhereClause'));
        $table->expects($this->any())
              ->method('getWhereClause')
              ->will($this->throwException(new \DomainException));

        // ...so we can be sure it has been called
        $this->setExpectedException('DomainException');
        $table->delete(array(1));
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::retrieve
     */
    public function testRetrieveNoEntityThrowsNoResultException()
    {
        $gateway = $this->getMock('Serquant\Persistence\Zend\Db\Table', array('find'));
        $gateway->expects($this->any())
                ->method('find')
                ->will($this->returnValue(array()));

        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $gateway->retrieve(array('id' => 1));
    }

    public function testRetrieveMultipleEntitiesThrowsNonUniqueResultException()
    {
        $gateway = $this->getMock('Serquant\Persistence\Zend\Db\Table', array('find'));
        $gateway->expects($this->any())
                ->method('find')
                ->will($this->returnValue(array(1, 2, 3)));

        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $gateway->retrieve(array('id' => 1));
    }

    /**
     * @covers Serquant\Persistence\Zend\Db\Table::getPrimaryKey
     */
    public function testGetPrimaryKeyWithScalarValueOnIntegralKey()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $pk = $gateway->getPrimaryKey(1);
        $this->assertEquals(array('id' => 1), $pk);
    }

    public function testGetPrimaryKeyWithIndexedArrayOnIntegralKey()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $pk = $gateway->getPrimaryKey(array(1));
        $this->assertEquals(array('id' => 1), $pk);
    }

    public function testGetPrimaryKeyWithAssociativeArrayOnIntegralKey()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person;
        $pk = $gateway->getPrimaryKey(array('id' => 1));
        $this->assertEquals(array('id' => 1), $pk);
    }

    public function testGetPrimaryKeyWithAssociativeArrayOnIntegralKeyHavingInflection()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\RoleWithInflection;
        $pk = $gateway->getPrimaryKey(array('roleId' => 1));
        $this->assertEquals(array('id' => 1), $pk);
    }

    public function testGetPrimaryKeyWithIndexedArrayOnCompoundKey()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Permission;
        $pk = $gateway->getPrimaryKey(array(1, 2));
        $this->assertEquals(array('role' => 1, 'resource' => 2), $pk);
    }

    public function testGetPrimaryKeyWithAssociativeArrayOnCompoundKey()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Permission;
        $pk = $gateway->getPrimaryKey(array('resource' => 1, 'role' => 2));
        $this->assertEquals(array('role' => 2, 'resource' => 1), $pk);
    }

    public function testGetPrimaryKeyWithAssociativeArrayOnCompoundKeyHavingInflection()
    {
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\PermissionWithInflection;
        $pk = $gateway->getPrimaryKey(array('resourceId' => 1, 'roleId' => 2));
        $this->assertEquals(array('role' => 2, 'resource' => 1), $pk);
    }
}
