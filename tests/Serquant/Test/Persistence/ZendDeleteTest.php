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

class ZendDeleteTest extends \Serquant\Resource\Persistence\ZendTestCase
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

    public function testDeleteOnEntityNotManaged()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $this->setExpectedException('Serquant\Persistence\Exception\RuntimeException');
        $this->persister->delete($entity);
    }

    public function testDeleteNoEntityThrowsNoResultException()
    {
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('delete')
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
        $this->persister->delete($entity);
    }

    public function testDeleteMultipleEntitiesThrowsNonUniqueResultException()
    {
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('delete')
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
        $this->persister->delete($entity);
    }

    public function testDeleteEntity()
    {
        $table = $this->getMock('Zend_Db_Table');
        $table->expects($this->any())
              ->method('delete')
              ->will($this->returnValue(1));
        $this->persister->setTableGateway($table);

        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';

        $loadedEntitiesProp = new \ReflectionProperty($this->persister, 'loadedEntities');
        $loadedEntitiesProp->setAccessible(true);
        $loadedEntities = $loadedEntitiesProp->getValue($this->persister);
        $loadedEntities->put($entity);

        $this->persister->delete($entity);
        $this->assertFalse($loadedEntities->hasEntity($entity));
    }
}
