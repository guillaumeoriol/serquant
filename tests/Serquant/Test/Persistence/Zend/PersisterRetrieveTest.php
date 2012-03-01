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

use Serquant\Persistence\Zend\Configuration;
use Serquant\Persistence\Zend\Db\Table;

class PersisterRetrieveTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;
    private $config;
    private $persister;

    private function setupDatabase()
    {
        $dataSets = array();

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/people.yaml')
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            realpath(dirname(__FILE__) . '/../fixture/permissions.yaml')
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
        $this->config = new Configuration();
        $this->config->setEventManager($evm);
        $this->persister = new \Serquant\Persistence\Zend\Persister($this->config);
    }

    /**
     * @covers Serquant\Persistence\Zend\Persister::retrieve
     */
    public function testRetrieveAlreadyLoadedEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $gateway = new \Serquant\Resource\Persistence\Zend\Db\Table\Person();
        $this->persister->setTableGateway($className, $gateway);

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

        // The loaded map is empty

        $gateway = $this->getMock(
        	'Serquant\Persistence\Zend\Db\Table',
            array('retrieve', 'getPrimaryKey')
        );
        $gateway->expects($this->any())
                ->method('retrieve')
                ->will($this->returnValue($row));
        $gateway->expects($this->any())
                ->method('getPrimaryKey')
                ->will($this->returnValue(array('id' => 1)));

        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $expected = new $entityName;
        $expected->setId($id);
        $expected->setFirstName($firstName);
        $expected->setLastName($lastName);

        $persister = $this->getMock(
        	'Serquant\Persistence\Zend\Persister',
            array('loadEntity'),
            array($this->config)
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
