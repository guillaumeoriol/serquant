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

class ZendRetrieveTest extends \Serquant\Resource\Persistence\ZendTestCase
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
            dirname(__FILE__) . '/fixture/permissions.yaml'
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
     * @covers Serquant\Persistence\Zend::retrieve
     */
    public function testRetrieveAlreadyLoadedEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->setId(1);
        $entity->setFirstName('Charles');
        $entity->setLastName('de Gaulle');

        $property = new \ReflectionProperty($this->persister, 'loadedMap');
        $property->setAccessible(true);
        $loadedMap = $property->getValue($this->persister);
        $loadedMap->put($entity, array(1));

        $this->assertSame($entity, $this->persister->retrieve($className, 1));
    }

    public function testRetrieveNoEntityThrowsNoResultException()
    {
        $gateway = $this->getMock('Serquant\Persistence\Zend\Db\Table');
        $gateway->expects($this->any())
                ->method('find')
                ->will($this->returnValue(array()));

        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $this->persister->setTableGateway($className, $gateway);

        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $this->persister->retrieve($className, 1);
    }

    public function testRetrieveMultipleEntitiesThrowsNonUniqueResultException()
    {
        $gateway = $this->getMock('Serquant\Persistence\Zend\Db\Table');
        $gateway->expects($this->any())
                ->method('find')
                ->will($this->returnValue(array(1, 2, 3)));

        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $this->persister->setTableGateway($className, $gateway);

        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $this->persister->retrieve($className, 1);
    }

    public function testRetrieveNotLoadedEntityWithStub()
    {
        $id = 1;
        $firstName = 'Louis-Napoléon';
        $lastName = 'Bonaparte';

        $row = array(
            'id' => $id,
            'first_name' => $firstName,
            'last_name' => $lastName
        );
        $row = new \Zend_Db_Table_Row(array('data' => $row));
        $gateway = $this->getMock(
        	'Serquant\Persistence\Zend\Db\Table',
            array('find', 'loadEntity'),
            array(array(), new \Doctrine\Common\EventManager())
        );
        $gateway->expects($this->any())
                ->method('find')
                ->will($this->returnValue(new \ArrayIterator(array($row))));

        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $expected = new $entityName;
        $expected->setId($id);
        $expected->setFirstName($firstName);
        $expected->setLastName($lastName);

        $persister = $this->getMock(
        	'Serquant\Persistence\Zend',
            array('loadEntity'),
            array(array(), new \Doctrine\Common\EventManager())
        );
        $persister->expects($this->any())
                  ->method('loadEntity')
                  ->will($this->returnValue($expected));
        $persister->setTableGateway($entityName, $gateway);

        $entity = $persister->retrieve($entityName, 1);
        $this->assertEquals($id, $entity->getId());
        $this->assertEquals($firstName, $entity->getFirstName());
        $this->assertEquals($lastName, $entity->getLastName());
    }

    public function testRetrieveNotLoadedEntity()
    {
        $id = 1;
        $firstName = 'Louis-Napoléon';
        $lastName = 'Bonaparte';

        $personEntityClass = 'Serquant\Resource\Persistence\Zend\Person';
        $personGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Person';
        $this->persister->setTableGateway($personEntityClass, $personGatewayClass);

        $entity = $this->persister->retrieve($personEntityClass, $id);
        $this->assertEquals($id, $entity->getId());
        $this->assertEquals($firstName, $entity->getFirstName());
        $this->assertEquals($lastName, $entity->getLastName());
    }

    public function testRetrieveNotLoadedEntityHavingCompoundKey()
    {
        $role = 12;
        $resource = 34;

        $permissionEntityClass = 'Serquant\Resource\Persistence\Zend\Permission';
        $permissionGatewayClass = 'Serquant\Resource\Persistence\Zend\Db\Table\Permission';
        $this->persister->setTableGateway($permissionEntityClass, $permissionGatewayClass);

        $entity = $this->persister->retrieve($permissionEntityClass, array($role, $resource));
        $this->assertInstanceOf($permissionEntityClass, $entity);
        $this->assertEquals($role, $entity->getRole());
        $this->assertEquals($resource, $entity->getResource());
    }
}
