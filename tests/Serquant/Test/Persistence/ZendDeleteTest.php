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

class ZendDeleteTest extends \Serquant\Resource\Persistence\ZendTestCase
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
        $this->persister = new \Serquant\Persistence\Zend();
    }

    /**
     * @covers \Serquant\Persistence\Zend::delete
     */
    public function testDeleteOnEntityNotManaged()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->setId(1);
        $entity->setFirstName('Louis-Napoléon');
        $entity->setLastName('Bonaparte');

        $this->setExpectedException('Serquant\Persistence\Exception\RuntimeException');
        $this->persister->delete($entity);
    }

    public function testDeleteNoEntityThrowsNoResultException()
    {
        // Force Table#delete to say zero row was deleted
        $gateway = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\Person', array('delete'));
        $gateway->expects($this->any())
              ->method('delete')
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

        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $this->persister->delete($person);
    }

    public function testDeleteMultipleEntitiesThrowsNonUniqueResultException()
    {
        // Force Table#delete to say several rows were deleted
        $gateway = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\Person', array('delete'));
        $gateway->expects($this->any())
              ->method('delete')
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

        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $this->persister->delete($person);
    }

    public function testDeleteEntity()
    {
        // Force Table#delete to say everything is ok
        $gateway = $this->getMock('Serquant\Resource\Persistence\Zend\Db\Table\Person', array('delete'));
        $gateway->expects($this->any())
              ->method('delete')
              ->will($this->returnValue(1));

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

        $this->persister->delete($person);
        $this->assertFalse($loadedMap->has($person));
    }
}
