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

    private $em;

    private $persister;

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
        \Zend_Db_Table::setDefaultAdapter($this->db);
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    public function testLoadEntity()
    {
        $data = array(
            'id' => 1,
            'first_name' => 'George',
            'last_name' => 'Washington'
        );

        $entityName = 'Serquant\Resource\Persistence\Zend\Person';

        $method = new \ReflectionMethod($this->persister, 'loadEntity');
        $method->setAccessible(true);
        $entity = $method->invoke($this->persister, $entityName, $data);
        $this->assertEquals($data['id'], $entity->getId());
        $this->assertEquals($data['first_name'], $entity->getFirstName());
        $this->assertEquals($data['last_name'], $entity->getLastName());

        // When the entity is already present in the registry,
        // it is just returned as it is and not populated with given values
        $data['last_name'] = 'Adams';
        $entity = $method->invoke($this->persister, $entityName, $data);
        $this->assertEquals($data['id'], $entity->getId());
        $this->assertEquals($data['first_name'], $entity->getFirstName());
        $this->assertNotEquals($data['last_name'], $entity->getLastName());
    }

    public function testRetrieveAlreadyLoadedEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $property = new \ReflectionProperty($this->persister, 'loadedEntities');
        $property->setAccessible(true);
        $loadedEntities = $property->getValue($this->persister);
        $loadedEntities->put($entity);

        $this->assertTrue($entity === $this->persister->retrieve($className, 1));
    }

    public function testRetrieveNoEntityThrowsNoResultException()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('find')
              ->will($this->returnValue(array()));

        $this->persister->setTableGateway($className, $table);

        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $this->persister->retrieve($className, 1);
    }

    public function testRetrieveMultipleEntitiesThrowsNonUniqueResultException()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('find')
              ->will($this->returnValue(array(1, 2, 3)));

        $this->persister->setTableGateway($className, $table);

        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $this->persister->retrieve($className, 1);
    }

    public function testRetrieveNotLoadedEntity()
    {
        $data = array(
            'id' => 1,
            'first_name' => 'George',
            'last_name' => 'Washington'
        );

        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $row = new \Zend_Db_Table_Row(array('data' => $data));

        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('find')
              ->will($this->returnValue(new \ArrayIterator(array($row))));

        $this->persister->setTableGateway($entityName, $table);

        $entity = $this->persister->retrieve($entityName, 1);
        $this->assertEquals($data['id'], $entity->getId());
        $this->assertEquals($data['first_name'], $entity->getFirstName());
        $this->assertEquals($data['last_name'], $entity->getLastName());
    }
}
