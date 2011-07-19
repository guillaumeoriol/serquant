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

use Serquant\Entity\Registry\Ormless;

class OrmlessTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $em;

    private $persister;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    public function testPutWithoutId()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $this->setExpectedException('InvalidArgumentException');
        $registry = new Ormless($this->em->getMetadataFactory());
        $result = $registry->put($entity);
    }

    public function testPutMissingEntity()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $registry = new Ormless($this->em->getMetadataFactory());
        $this->assertTrue($registry->put($entity));
    }

    public function testPutExistingEntity()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertFalse($registry->put($entity));
    }

    public function testPutMissingEntityHavingCompoundId()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\Message();
        $entity1->language = 'en';
        $entity1->key = 'error';
        $entity1->message = 'An error has occurred';

        $entity2 = new \Serquant\Resource\Persistence\Zend\Message();
        $entity2->language = 'fr';
        $entity2->key = 'error';
        $entity2->message = 'Une erreur est survenue';

        $registry = new Ormless($this->em->getMetadataFactory());
        $this->assertTrue($registry->put($entity1));
        $this->assertTrue($registry->put($entity2));
    }

    public function testPutExistingEntityHavingCompoundId()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\Message();
        $entity1->language = 'en';
        $entity1->key = 'error';
        $entity1->message = 'An error has occurred';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity1);
        $this->assertFalse($registry->put($entity1));
    }

    public function testPutTwoEntitiesHavingSameIdButDifferentClasses()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\User();
        $entity1->id = 1;
        $entity1->name = 'dummy';

        $entity2 = new \Serquant\Resource\Persistence\Zend\SubclassB();
        $entity2->id = 1;
        $entity2->name = 'dummy';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity1);
        $this->assertTrue($registry->put($entity2));
    }

    public function testPutTwoEntitiesHavingSameIdAndSameRootClassOfMappedSuperclass()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\SubclassA();
        $entity1->id = 1;
        $entity1->name = 'dummy';

        $entity2 = new \Serquant\Resource\Persistence\Zend\SubclassB();
        $entity2->id = 1;
        $entity2->name = 'dummy';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity1);
        $this->assertTrue($registry->put($entity2));
    }

    public function testPutTwoEntitiesHavingSameIdAndSameRootClassOfSingleTableInheritance()
    {
        $entity1 = new \Serquant\Resource\Persistence\Zend\Person();
        $entity1->id = 1;
        $entity1->firstName = 'George';
        $entity1->lastName = 'Washington';

        $entity2 = new \Serquant\Resource\Persistence\Zend\Employee();
        $entity2->id = 1;
        $entity2->firstName = 'John';
        $entity2->lastName = 'Adams';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity1);
        $this->assertFalse($registry->put($entity2));
    }

    // -------------------------------------------------------------------------
    // getById

    public function testGetByIdOnMissingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertFalse($registry->tryGetById($className, 2));
    }

    public function testGetByIdOnExistingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertTrue($entity === $registry->tryGetById($className, 1));
    }

    public function testGetByIdOnExistingEntityHavingCompoundId()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Message';
        $entity = new $className;
        $entity->language = 'en';
        $entity->key = 'error';
        $entity->message = 'An error has occurred';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertTrue($entity === $registry->tryGetById($className, array('en', 'error')));
    }

    public function testGetByIdOnExistingEntityOfMappedSuperclass()
    {
        $className = 'Serquant\Resource\Persistence\Zend\SubclassA';
        $entity = new $className;
        $entity->id = 1;
        $entity->name = 'dummy';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertTrue($entity === $registry->tryGetById($className, 1));
    }

    public function testGetByIdOnExistingEntityOfSingleTableInheritance()
    {
        $className1 = 'Serquant\Resource\Persistence\Zend\Person';
        $entity1 = new $className1;
        $entity1->id = 1;
        $entity1->firstName = 'George';
        $entity1->lastName = 'Washington';

        $className2 = 'Serquant\Resource\Persistence\Zend\Employee';
        $entity2 = new $className2;
        $entity2->id = 2;
        $entity2->firstName = 'John';
        $entity2->lastName = 'Adams';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity1);
        $registry->put($entity2);
        $this->assertTrue($entity1 === $registry->tryGetById($className1, 1));
        $this->assertTrue($entity2 === $registry->tryGetById($className2, 2));
    }

    // -------------------------------------------------------------------------
    // getByRow

    public function testGetByRowWithoutId()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->setExpectedException('InvalidArgumentException');
        $registry->tryGetByRow($className, array('status' => 'deprecated'));
    }

    public function testGetByRowWithoutFirstPartOfCompoundId()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Message';
        $entity = new $className;
        $entity->language = 'en';
        $entity->key = 'error';
        $entity->message = 'An error has occurred';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->setExpectedException('InvalidArgumentException');
        $registry->tryGetByRow($className, array('key' => 'error', 'message' => 'An error has occurred'));
    }

    public function testGetByRowWithoutLastPartOfCompoundId()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Message';
        $entity = new $className;
        $entity->language = 'en';
        $entity->key = 'error';
        $entity->message = 'An error has occurred';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->setExpectedException('InvalidArgumentException');
        $registry->tryGetByRow($className, array('language' => 'en', 'message' => 'An error has occurred'));
    }

    public function testGetByRowOnMissingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertFalse($registry->tryGetByRow($className, array('id' => 2)));
    }

    public function testGetByRowOnExistingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\User';
        $entity = new $className;
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertTrue($entity === $registry->tryGetByRow($className, array('id' => 1)));
    }

    public function testGetByRowOnExistingEntityHavingCompoundId()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Message';
        $entity = new $className;
        $entity->language = 'en';
        $entity->key = 'error';
        $entity->message = 'An error has occurred';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertTrue($entity === $registry->tryGetByRow($className, array('message' => 'An error has occurred', 'language' => 'en', 'key' => 'error')));
    }

    public function testGetByRowOnExistingEntityOfMappedSuperclass()
    {
        $className = 'Serquant\Resource\Persistence\Zend\SubclassA';
        $entity = new $className;
        $entity->id = 1;
        $entity->name = 'dummy';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);
        $this->assertTrue($entity === $registry->tryGetByRow($className, array('name' => 'dummy', 'id' => 1)));
    }

    public function testGetByRowOnExistingEntityOfSingleTableInheritance()
    {
        $className1 = 'Serquant\Resource\Persistence\Zend\Person';
        $entity1 = new $className1;
        $entity1->id = 1;
        $entity1->firstName = 'George';
        $entity1->lastName = 'Washington';

        $className2 = 'Serquant\Resource\Persistence\Zend\Employee';
        $entity2 = new $className2;
        $entity2->id = 2;
        $entity2->firstName = 'John';
        $entity2->lastName = 'Adams';

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity1);
        $registry->put($entity2);
        $this->assertTrue($entity1 === $registry->tryGetByRow($className1, array('id' => 1)));
        $this->assertTrue($entity2 === $registry->tryGetByRow($className2, array('id' => 2)));
    }

    // -------------------------------------------------------------------------

    public function testComputeChangeSetOnSimpleEntity()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->id = 1;
        $entity->username = 'gw';
        $entity->name = 'Washington';

        $platform = $this->em->getConnection()->getDatabasePlatform();
        $registry = new Ormless($this->em->getMetadataFactory());
        $result = $registry->put($entity);

        $entity->id = null;
        $changeSet = $registry->computeChangeSet($entity, $platform);
        $this->assertEquals(array('id' => null), $changeSet);

        $entity->id = 1;
        $entity->status = 'deprecated';
        $changeSet = $registry->computeChangeSet($entity, $platform);
        $this->assertEquals(array('status' => 'deprecated'), $changeSet);

        $entity->id = 2;
        $entity->status = null;
        $changeSet = $registry->computeChangeSet($entity, $platform);
        $this->assertEquals(array('id' => 2), $changeSet);
    }

    public function testComputeChangeSetOnMappedSuperclass()
    {
        $className = 'Serquant\Resource\Persistence\Zend\SubclassA';
        $entity = new $className;
        $entity->id = 1;
        $entity->name = 'dummy';
        $orgDate = new \DateTime('2011-01-01 00:00:00');
        $entity->setSavedAt($orgDate);

        $platform = $this->em->getConnection()->getDatabasePlatform();
        $registry = new Ormless($this->em->getMetadataFactory());
        $result = $registry->put($entity);

        $orgDate->setDate('2011', '01', '02');
        $entity->setSavedAt($orgDate);
        $changeSet = $registry->computeChangeSet($entity, $platform);
        $this->assertEquals(array(), $changeSet);

        $actualDate = new \DateTime('2011-01-03 00:00:00');
        $entity->setSavedAt($actualDate);
        $changeSet = $registry->computeChangeSet($entity, $platform);
        $this->assertEquals(array('saved_at' => '2011-01-03 00:00:00'), $changeSet);
    }

    // -------------------------------------------------------------------------

    public function testCommitChangeSet()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->id = 1;
        $oid = spl_object_hash($entity);

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);

        // When the entity is registered, a copy of it is registered too
        // for later use, to compute change set.
        $originalEntityDataProp = new \ReflectionProperty($registry, 'originalEntityData');
        $originalEntityDataProp->setAccessible(true);
        $originalEntityData = $originalEntityDataProp->getValue($registry);

        // This copy is a clone of the entity.
        // Check that the copy is not another reference to the same object.
        $orgEntity1 = $originalEntityData[$oid];
        $orgHash1 = spl_object_hash($orgEntity1);
        $this->assertNotEquals($oid, $orgHash1, 'before commitChangeSet');

        // Then commit the changes and check if a new copy of the entity has
        // been made.
        $entity->id = 2;
        $registry->commitChangeSet($entity);
        $originalEntityData = $originalEntityDataProp->getValue($registry);
        $orgEntity2 = $originalEntityData[$oid];
        $orgHash2 = spl_object_hash($orgEntity2);
        $this->assertNotEquals($orgHash1, $orgHash2, 'after commitChangeSet');
    }

    // -------------------------------------------------------------------------

    public function testRemoveMissingEntity()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->id = 1;

        $registry = new Ormless($this->em->getMetadataFactory());
        $this->assertFalse($registry->remove($entity));
    }

    public function testRemoveExistingEntity()
    {
        $className = 'Serquant\Resource\Persistence\Zend\Person';
        $entity = new $className;
        $entity->id = 1;
        $entity->firstName = 'George';
        $entity->lastName = 'Washington';
        $oid = spl_object_hash($entity);

        $registry = new Ormless($this->em->getMetadataFactory());
        $registry->put($entity);

        $this->assertTrue($registry->remove($entity));

        $this->assertFalse($registry->hasEntity($entity));

        $hashToIdMapProp = new \ReflectionProperty($registry, 'hashToIdMap');
        $hashToIdMapProp->setAccessible(true);
        $hashToIdMap = $hashToIdMapProp->getValue($registry);
        $this->assertNotContains($oid, $hashToIdMap);

        $originalEntityDataProp = new \ReflectionProperty($registry, 'originalEntityData');
        $originalEntityDataProp->setAccessible(true);
        $originalEntityData = $originalEntityDataProp->getValue($registry);
        $this->assertNotContains($oid, $originalEntityData);
    }
}