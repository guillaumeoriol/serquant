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
namespace Serquant\Test\Persistence\Zend;

class PersisterTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;
    private $persister;

    private function setupDatabase()
    {
        $dataSets = array();

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/people.yaml')
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/issues.yaml')
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/cars.yaml')
        );

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/cms_accounts.yaml')
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/cms_users.yaml')
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/cms_addresses.yaml')
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/cms_phonenumbers.yaml')
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
        $this->persister = new \Serquant\Persistence\Zend\Persister(array(), $evm);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::normalizeEntityName
     * @covers Serquant\Persistence\Exception\InvalidArgumentException
     */
    public function testNormalizeEntityName()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';

        $method = new \ReflectionMethod($this->persister, 'normalizeEntityName');
        $method->setAccessible(true);

        $result = $method->invoke($this->persister, $entityName);
        $this->assertEquals($entityName, $result);

        $object = new $entityName;
        $result = $method->invoke($this->persister, $object);
        $this->assertEquals($entityName, $result);

        $this->setExpectedException('InvalidArgumentException', null, 10);
        $result = $method->invoke($this->persister, 1);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::setTableGateway
     */
    public function testSetTableGatewayFromInstanceOfWrongClass()
    {
        $this->setExpectedException('RuntimeException', null, 20);
        $this->persister->setTableGateway('whatever', new \stdClass());
    }

    public function testSetTableGatewayFromInstanceOfRightClass()
    {
        $name = 'whatever';
        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $gateway = new $gatewayClass;

        // Build a new persister to have an empty gateway map
        $evm = new \Doctrine\Common\EventManager();
        $persister = new \Serquant\Persistence\Zend\Persister(array(), $evm);
        $persister->setTableGateway($name, $gateway);

        // Check that the given gateway can now been retrieved...
        $actual = $persister->getTableGateway($name);
        $this->assertInstanceOf($gatewayClass, $actual);
        // ...and now has a reference to the persister
        $this->assertAttributeSame($persister, 'persister', $actual);
    }

    public function testSetTableGatewayFromClass()
    {
        $name = 'whatever';
        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';

        // Build a new persister to have an empty gateway map
        $evm = new \Doctrine\Common\EventManager();
        $persister = new \Serquant\Persistence\Zend\Persister(array(), $evm);
        $persister->setTableGateway($name, $gatewayClass);

        // Check that the given gateway can now been retrieved...
        $actual = $persister->getTableGateway($name);
        $this->assertInstanceOf($gatewayClass, $actual);
        // ...and now has a reference to the persister
        $this->assertAttributeSame($persister, 'persister', $actual);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::getTableGateway
     * @covers Serquant\Persistence\Exception\InvalidArgumentException
     */
    public function testGetTableGatewayFromClassWithoutGateway()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $this->setExpectedException('InvalidArgumentException', null, 30);
        $gateway = $this->persister->getTableGateway($entityName);
    }

    public function testGetTableGatewayFromInstanceWithoutGateway()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $entityName;
        $this->setExpectedException('InvalidArgumentException', null, 30);
        $gateway = $this->persister->getTableGateway($entityName);
    }

    public function testGetTableGatewayFromClass()
    {
        $name = 'whatever';
        $gatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';

        // Build a new persister to have an empty gateway map
        $evm = new \Doctrine\Common\EventManager();
        $persister = new \Serquant\Persistence\Zend\Persister(array($name => $gatewayClass), $evm);

        // Check that the given gateway can now been retrieved...
        $actual = $persister->getTableGateway($name);
        $this->assertInstanceOf($gatewayClass, $actual);
        // ...and now has a reference to the persister
        $this->assertAttributeSame($persister, 'persister', $actual);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::loadEntity
     */
    public function testLoadEntityFromGateway()
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

        $loadedMapProp = new \ReflectionProperty($this->persister, 'loadedMap');
        $loadedMapProp->setAccessible(true);
        $loadedMap = $loadedMapProp->getValue($this->persister);

        // Be sure the entity is not already in the identity map
        $this->assertNull($loadedMap->get($entityName, array($id)));

        $gateway = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\Role', array('loadEntity'));
        $gateway->expects($this->any())
                ->method('loadEntity')
                ->will($this->returnValue($expected));
        $this->persister->setTableGateway($entityName, $gateway);

        $actual = $this->persister->loadEntity($entityName, $row);
        // Verify we get an entity with the right values
        $this->assertInstanceOf($entityName, $actual);
        $this->assertEquals($expected, $actual);
        // and check this entity has been stored in the identity map
        $this->assertSame($actual, $loadedMap->get($entityName, array($id)));
    }

    public function testLoadEntityFromRegistry()
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

        $loadedMapProp = new \ReflectionProperty($this->persister, 'loadedMap');
        $loadedMapProp->setAccessible(true);
        $loadedMap = $loadedMapProp->getValue($this->persister);

        // Put the entity in the identity map
        $loadedMap->put($expected, array($id));
        // and be sure it is
        $this->assertNotNull($loadedMap->get($entityName, array($id)));

        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Role;
        $this->persister->setTableGateway($entityName, $gateway);

        $actual = $this->persister->loadEntity($entityName, $row);
        // Verify we get an entity
        $this->assertInstanceOf($entityName, $actual);
        // which is the exact same object that we put previously
        $this->assertSame($expected, $actual);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::loadEntities
     */
    public function testLoadEntities()
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

        $rows = array($row);

        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Role;
        $this->persister->setTableGateway($entityName, $gateway);

        $entities = $this->persister->loadEntities($entityName, $rows);
        $this->assertInternalType('array', $entities);
        $this->assertInstanceOf($entityName, $entities[0]);
        $this->assertEquals($expected, $entities[0]);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::fetchAll
     * @covers Serquant\Persistence\Exception\InvalidArgumentException
     */
    public function testFetchAllWithoutGateway()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';

        $this->setExpectedException('InvalidArgumentException', null, 30);
        $entities = $this->persister->fetchAll($entityName, array());
    }

    public function testFetchAllWithoutAssociation()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\User';
        $gatewayName = 'Serquant\Resource\Persistence\Zend\Db\Table\User';
        $this->persister->setTableGateway($entityName, $gatewayName);

        $entities = $this->persister->fetchAll($entityName, array());
        $this->assertInternalType('array', $entities);
        foreach ($entities as $entity) {
            $this->assertInstanceOf($entityName, $entity);
        }
    }

    public function testFetchAllWithoutFetchJoin()
    {
        $issueEntityClass = 'Serquant\Resource\Persistence\Zend\Issue';
        $issueGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\IssueWithoutFetchJoin';
        $this->persister->setTableGateway($issueEntityClass, $issueGatewayClass);

        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $entities = $this->persister->fetchAll($issueEntityClass, array());
        $this->assertInternalType('array', $entities);

        foreach ($entities as $entity) {
            $this->assertInstanceOf($issueEntityClass, $entity);
            $this->assertInstanceOf('Serquant\Resource\Persistence\Zend\PersonProxy', $entity->getReporter());
        }
    }

    public function testFetchAllWithOneToOneMandatoryAssociation()
    {
        $issueEntityClass = 'Serquant\Resource\Persistence\Zend\Issue';
        $issueGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Issue';
        $this->persister->setTableGateway($issueEntityClass, $issueGatewayClass);

        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $entities = $this->persister->fetchAll($issueEntityClass, array());
        $this->assertInternalType('array', $entities);

        $this->assertInstanceOf($issueEntityClass, $entities[0]);
        $this->assertInstanceOf($personEntityClass, $entities[0]->getReporter());

        $this->assertInstanceOf($issueEntityClass, $entities[1]);
        $this->assertInstanceOf($personEntityClass, $entities[1]->getReporter());
    }

    public function testFetchAllWithOneToOneOptionalAssociation()
    {
        $carEntityClass = 'Serquant\Resource\Persistence\Zend\Car';
        $carGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Car';
        $this->persister->setTableGateway($carEntityClass, $carGatewayClass);

        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $entities = $this->persister->fetchAll($carEntityClass, array());
        $this->assertInternalType('array', $entities);

        $this->assertInstanceOf($carEntityClass, $entities[0]);
        $this->assertNull($entities[0]->getOwner());

        $this->assertInstanceOf($carEntityClass, $entities[1]);
        $this->assertInstanceOf($personEntityClass, $entities[1]->getOwner());
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::fetchOne
     */
    public function testFetchOneThrowingNoResultException()
    {
        $issueEntityClass = 'Serquant\Resource\Persistence\Zend\Issue';
        $issueGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Issue';
        $this->persister->setTableGateway($issueEntityClass, $issueGatewayClass);

        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $entity = $this->persister->fetchOne(
            $issueEntityClass,
            array('title' => 'missing')
        );
    }

    public function testFetchOneThrowingNonUniqueResultException()
    {
        $issueEntityClass = 'Serquant\Resource\Persistence\Zend\Issue';
        $issueGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Issue';
        $this->persister->setTableGateway($issueEntityClass, $issueGatewayClass);

        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $entity = $this->persister->fetchOne(
            $issueEntityClass,
            array('reporter' => 1)
        );
    }

    public function testFetchOne()
    {
        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $entity = $this->persister->fetchOne(
            $personEntityClass,
            array('id' => 1)
        );
        $this->assertInstanceOf($personEntityClass, $entity);
        $this->assertEquals(1, $entity->getId());
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::fetchPage
     */
    public function testFetchPageWithoutLimit()
    {
        $issueEntityClass = 'Serquant\Resource\Persistence\Zend\Issue';
        $issueGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Issue';
        $this->persister->setTableGateway($issueEntityClass, $issueGatewayClass);

        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $paginator = $this->persister->fetchPage(
            $issueEntityClass,
            array('reporter' => 1)
        );
        $this->assertInstanceOf('\Zend_Paginator', $paginator);
    }

    public function testFetchPageWithLimit()
    {
        $issueEntityClass = 'Serquant\Resource\Persistence\Zend\Issue';
        $issueGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Issue';
        $this->persister->setTableGateway($issueEntityClass, $issueGatewayClass);

        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $paginator = $this->persister->fetchPage(
            $issueEntityClass,
            array('reporter' => 1, 'limit(0,10)')
        );
        $this->assertInstanceOf('\Zend_Paginator', $paginator);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::fetchPairs
     */
    public function testFetchPairs()
    {
        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $actual = $this->persister->fetchPairs(
            $personEntityClass,
            'id',
            'lastName',
            array('lastName' => 'D*')
        );
        $this->assertInternalType('array', $actual);
        $this->assertEquals(array(11 => 'Deschanel', 13 => 'Doumergue', 14 => 'Doumer'), $actual);
    }
}