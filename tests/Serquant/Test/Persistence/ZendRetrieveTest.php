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
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
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

    /**
     * @covers \Serquant\Persistence\Zend::retrieve
     */
    public function testRetrieveNotLoadedEntity()
    {
        $row = array(
            'id' => 1,
            'first_name' => 'George',
            'last_name' => 'Washington'
        );

        $entityName = 'Serquant\Resource\Persistence\Zend\Person';
        $row = new \Zend_Db_Table_Row(array('data' => $row));

        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('find')
              ->will($this->returnValue(new \ArrayIterator(array($row))));

        $this->persister->setTableGateway($entityName, $table);

        $entity = $this->persister->retrieve($entityName, 1);
        $this->assertEquals($row['id'], $entity->getId());
        $this->assertEquals($row['first_name'], $entity->getFirstName());
        $this->assertEquals($row['last_name'], $entity->getLastName());
    }
}
