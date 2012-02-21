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
namespace Serquant\Test\Entity\Registry;

use Serquant\Entity\Registry\IdentityMap;

/**
 * Test class for IdentityMap
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class IdentityMapTest extends \PHPUnit_Framework_TestCase
{
    private $identityMap;

    protected function setUp()
    {
        $this->identityMap = new IdentityMap();
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::getRootClass
     */
    public function testGetRootClassFromBaseClass()
    {
        $class = 'Serquant\Resource\Persistence\Zend\User';
        $instance = new $class;

        $method = new \ReflectionMethod($this->identityMap, 'getRootClass');
        $method->setAccessible(true);

        $actual = $method->invoke($this->identityMap, $instance);
        $this->assertEquals($class, $actual);
    }

    public function testGetRootClassFromInheritedClass()
    {
        $class = 'Serquant\Resource\Persistence\Zend\PersonSubclass';
        $instance = new $class;

        $method = new \ReflectionMethod($this->identityMap, 'getRootClass');
        $method->setAccessible(true);

        $actual = $method->invoke($this->identityMap, $instance);
        $this->assertEquals('Serquant\Resource\Persistence\Zend\Person', $actual);
    }

    public function testGetRootClassFromInheritedOfInheritedClass()
    {
        $class = 'Serquant\Resource\Persistence\Zend\PersonSubclassSubclass';
        $instance = new $class;

        $method = new \ReflectionMethod($this->identityMap, 'getRootClass');
        $method->setAccessible(true);

        $actual = $method->invoke($this->identityMap, $instance);
        $this->assertEquals('Serquant\Resource\Persistence\Zend\Person', $actual);
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::get
     */
    public function testGetMissingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->setId(1);
        $entity->setStatus('deprecated');
        $entity->setUsername('gw');
        $entity->setName('Washington');

        $registry = new IdentityMap();
        $registry->put($entity, array(1));
        $this->assertNull($registry->get($className, array(2)));
    }

    public function testGetExistingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->setId(1);
        $entity->setStatus('deprecated');
        $entity->setUsername('gw');
        $entity->setName('Washington');

        $registry = new IdentityMap();
        $registry->put($entity, array(1));
        $this->assertSame($entity, $registry->get($className, array(1)));
    }

    public function testGetExistingEntityHavingCompoundId()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Permission';
        $entity = new $className;
        $entity->setRole(1);
        $entity->setResource(2);

        $registry = new IdentityMap();
        $registry->put($entity, array(1, 2));
        $this->assertSame($entity, $registry->get($className, array(1, 2)));
    }

    public function testGetExistingEntitySubclass()
    {
        $className = 'Serquant\Resource\Persistence\Zend\PersonSubclass';
        $entity = new $className;
        $entity->setId(1);
        $entity->setFirstName('Charles');
        $entity->setLastName('de Gaulle');
        $entity->setBirthDate('1890-11-22');

        $registry = new IdentityMap();
        $registry->put($entity, array(1));
        $this->assertSame($entity, $registry->get($className, array(1)));
    }

    public function testGetExistingEntitiesOfSameInheritanceHierarchy()
    {
        $className1 = 'Serquant\Resource\Persistence\Zend\Person';
        $entity1 = new $className1;
        $entity1->setId(1);
        $entity1->setFirstName('René');
        $entity1->setLastName('Coty');

        $className2 = 'Serquant\Resource\Persistence\Zend\PersonSubclass';
        $entity2 = new $className2;
        $entity2->setId(2);
        $entity2->setFirstName('Charles');
        $entity2->setLastName('de Gaulle');
        $entity2->setBirthDate('1890-11-22');

        $registry = new IdentityMap();
        $registry->put($entity1, array(1));
        $registry->put($entity2, array(2));
        $this->assertSame($entity1, $registry->get($className1, array(1)));
        $this->assertSame($entity2, $registry->get($className2, array(2)));
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::getOriginal
     */
    public function testGetOriginal()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->setId(1);
        $entity->setFirstName('Charles');
        $entity->setLastName('de Gaulle');

        $expected = clone $entity;

        $registry = new IdentityMap();
        $registry->put($entity, array(1));

        $entity->setFirstName('Philippe');

        $this->assertEquals($expected, $registry->getOriginal($entity));
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::has
     */
    public function testHasMissingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->setId(1);
        $entity->setStatus('deprecated');
        $entity->setUsername('gw');
        $entity->setName('Washington');

        $registry = new IdentityMap();
        $this->assertFalse($registry->has($entity));
    }

    public function testHasExistingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->setId(1);
        $entity->setStatus('deprecated');
        $entity->setUsername('gw');
        $entity->setName('Washington');

        $registry = new IdentityMap();
        $registry->put($entity, array(1));
        $this->assertTrue($registry->has($entity));
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::put
     * @covers Serquant\Entity\Exception
     * @covers Serquant\Entity\Exception\InvalidArgumentException
     */
    public function testPutWithoutId()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->setStatus('deprecated');
        $entity->setUsername('gw');
        $entity->setName('Washington');

        $registry = new IdentityMap();
        $this->setExpectedException('InvalidArgumentException');
        $registry->put($entity, array());
    }

    public function testPutMissingEntity()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->setId(1);
        $entity->setStatus('deprecated');
        $entity->setUsername('gw');
        $entity->setName('Washington');

        $registry = new IdentityMap();
        $this->assertTrue($registry->put($entity, array(1)));
    }

    public function testPutExistingEntity()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->setId(1);
        $entity->setStatus('deprecated');
        $entity->setUsername('gw');
        $entity->setName('Washington');

        $registry = new IdentityMap();
        $registry->put($entity, array(1));
        $this->assertFalse($registry->put($entity, array(1)));
    }

    public function testPutMissingEntityHavingCompoundId()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\Permission();
        $entity1->setRole(1);
        $entity1->setResource(2);

        $entity2 = new \Serquant\Resource\Persistence\Zend\Permission();
        $entity2->setRole(1);
        $entity2->setResource(3);

        $entity3 = new \Serquant\Resource\Persistence\Zend\Permission();
        $entity3->setRole(2);
        $entity3->setResource(3);

        $registry = new IdentityMap();
        $this->assertTrue($registry->put($entity1, array(1, 2)));
        $this->assertTrue($registry->put($entity2, array(1, 3)));
        $this->assertTrue($registry->put($entity3, array(2, 3)));
    }

    public function testPutExistingEntityHavingCompoundId()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\Permission();
        $entity1->setRole(1);
        $entity1->setResource(2);

        $registry = new IdentityMap();
        $registry->put($entity1, array(1, 2));
        $this->assertFalse($registry->put($entity1, array(1, 2)));
    }

    public function testPutTwoEntitiesHavingSameIdButDifferentClasses()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\User();
        $entity1->setId(1);
        $entity1->setName('Alice');

        $entity2 = new \Serquant\Resource\Persistence\Zend\PersonSubclass();
        $entity2->setId(1);
        $entity2->setFirstName('Alice');

        $registry = new IdentityMap();
        $this->assertTrue($registry->put($entity1, array(1)));
        $this->assertTrue($registry->put($entity2, array(1)));
    }

    public function testPutTwoEntitiesHavingSameIdAndSameRootClass()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\PersonSubclass();
        $entity1->setId(1);
        $entity1->setFirstName('Alice');

        $entity2 = new \Serquant\Resource\Persistence\Zend\PersonSubclassSubclass();
        $entity2->setId(1);
        $entity2->setFirstName('Ben');

        $registry = new IdentityMap();
        $this->assertTrue($registry->put($entity1, array(1)));
        $this->assertFalse($registry->put($entity2, array(1)));
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::getId
     */
    public function testGetId()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->setId(1);
        $entity->setStatus('deprecated');
        $entity->setUsername('gw');
        $entity->setName('Washington');

        $registry = new IdentityMap();
        $registry->put($entity, array('id' => 1));
        $id = $registry->getId($entity);
        $this->assertInternalType('array', $id);
        $this->assertEquals(array('id' => 1), $id);
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::commit
     */
    public function testCommit()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->setId(1);
        $oid = spl_object_hash($entity);

        $registry = new IdentityMap();
        $registry->put($entity, array(1));

        // When the entity is registered, a copy of it is registered too
        // for later use, to compute change set.
        $originalEntitiesProp = new \ReflectionProperty($registry, 'originalEntities');
        $originalEntitiesProp->setAccessible(true);

        // This copy is a clone of the entity.
        // Check that the copy is not another reference to the same object.
        $originalEntities = $originalEntitiesProp->getValue($registry);
        $orgEntity1 = $originalEntities[$oid];
        $orgHash1 = spl_object_hash($orgEntity1);
        $this->assertNotEquals($oid, $orgHash1, 'before commit');

        // Then commit the changes and check if a new copy of the entity has
        // been made.
        $entity->setId(2);
        $registry->commit($entity);

        $originalEntities = $originalEntitiesProp->getValue($registry);
        $orgEntity2 = $originalEntities[$oid];
        $orgHash2 = spl_object_hash($orgEntity2);
        $this->assertNotEquals($orgHash1, $orgHash2, 'after commit');
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::remove
     */
    public function testRemoveMissingEntity()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->setId(1);

        $registry = new IdentityMap();
        $this->assertFalse($registry->remove($entity));
    }

    public function testRemoveExistingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->setId(1);
        $oid = spl_object_hash($entity);

        $registry = new IdentityMap();
        $registry->put($entity, array(1));

        $this->assertTrue($registry->remove($entity));

        $this->assertFalse($registry->has($entity));

        $hashToIdMapProp = new \ReflectionProperty($registry, 'hashToIdMap');
        $hashToIdMapProp->setAccessible(true);
        $hashToIdMap = $hashToIdMapProp->getValue($registry);
        $this->assertNotContains($oid, $hashToIdMap);

        $originalEntitiesProp = new \ReflectionProperty($registry, 'originalEntities');
        $originalEntitiesProp->setAccessible(true);
        $originalEntities = $originalEntitiesProp->getValue($registry);
        $this->assertNotContains($oid, $originalEntities);
    }

    /**
     * @covers Serquant\Entity\Registry\IdentityMap::propertyChanged
     * @covers Serquant\Entity\Exception
     * @covers Serquant\Entity\Exception\NotImplementedException
     */
    public function testPropertyChanged()
    {
        $registry = new IdentityMap();
        $this->setExpectedException('Serquant\Entity\Exception\NotImplementedException');
        $registry->propertyChanged(1, 2, 3, 4);
    }
}