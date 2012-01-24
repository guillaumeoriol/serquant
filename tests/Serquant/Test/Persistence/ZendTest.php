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

class ZendTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;
    private $em;
    private $persister;
    private $entityName;

    private function setupDatabase()
    {
        $this->entityName = 'Serquant\Resource\Persistence\Zend\Issue';

        $dataSets = array();
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/people.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/issues.yaml'
        );

        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_accounts.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_users.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_addresses.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_phonenumbers.yaml'
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
        $this->em = $this->getTestEntityManager();
        $this->persister = new \Serquant\Persistence\Zend($this->em);
    }

    public function testGetMetadataFactory()
    {
        $factory = $this->persister->getMetadataFactory();
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ClassMetadataFactory', $factory);
    }

    public function testGetEntityRegistry()
    {
        $registry = $this->persister->getEntityRegistry();
        $this->assertInstanceOf('Serquant\Entity\Registry\Registrable', $registry);
    }

    public function testGetClassMetadata()
    {
        $metadata = $this->persister->getClassMetadata($this->entityName);
        $this->assertInstanceOf('Doctrine\ORM\Mapping\ClassMetadata', $metadata);
    }

    public function testNormalizeEntityName()
    {
        $method = new \ReflectionMethod($this->persister, 'normalizeEntityName');
        $method->setAccessible(true);

        $result = $method->invoke($this->persister, $this->entityName);
        $this->assertEquals($this->entityName, $result);

        $object = new $this->entityName;
        $result = $method->invoke($this->persister, $object);
        $this->assertEquals($this->entityName, $result);

        $this->setExpectedException('InvalidArgumentException');
        $result = $method->invoke($this->persister, 1);
    }

    public function testGetTableGatewayWithoutGateway()
    {
        $method = new \ReflectionMethod($this->persister, 'getTableGateway');
        $method->setAccessible(true);

        $entityName = 'Serquant\Resource\Persistence\Zend\UserWithoutGateway';
        $this->setExpectedException(
        	'Serquant\Persistence\Exception\InvalidArgumentException',
            null,
            2
        );
        $gateway = $method->invoke($this->persister, $entityName);
    }

    public function testGetTableGatewayWithInvalidGateway()
    {
        $method = new \ReflectionMethod($this->persister, 'getTableGateway');
        $method->setAccessible(true);

        $entityName = 'Serquant\Resource\Persistence\Zend\UserWithInvalidGateway';
        $this->setExpectedException(
        	'Serquant\Persistence\Exception\InvalidArgumentException',
            null,
            3
        );
        $gateway = $method->invoke($this->persister, $entityName);
    }

    /**
     * @covers \Serquant\Persistence\Zend::fetchAll
     */
    public function testFetchAll()
    {
        $entities = $this->persister->fetchAll($this->entityName, array());
        $this->assertInternalType('array', $entities);
        foreach ($entities as $entity) {
            $this->assertInstanceOf($this->entityName, $entity);
        }
    }

    public function testFetchOneThrowingNoResultException()
    {
        $this->setExpectedException('Serquant\Persistence\Exception\NoResultException');
        $entity = $this->persister->fetchOne(
            $this->entityName,
            array('title' => 'missing')
        );
    }

    public function testFetchOneThrowingNonUniqueResultException()
    {
        $this->setExpectedException('Serquant\Persistence\Exception\NonUniqueResultException');
        $entity = $this->persister->fetchOne(
            $this->entityName,
            array('reporter' => 1)
        );
    }

    /**
     * @covers \Serquant\Persistence\Zend::fetchOne
     */
    public function testFetchOne()
    {
        $entity = $this->persister->fetchOne(
            $this->entityName,
            array('id' => 1)
        );
        $this->assertInstanceOf($this->entityName, $entity);
        $this->assertEquals(1, $entity->id);
    }

    public function testFetchPageWithoutLimit()
    {
        $paginator = $this->persister->fetchPage(
            $this->entityName,
            array('reporter' => 1)
        );
        $this->assertInstanceOf('\Zend_Paginator', $paginator);
    }

    public function testFetchPageWithLimit()
    {
        $paginator = $this->persister->fetchPage(
            $this->entityName,
            array('reporter' => 1, 'limit(0,10)')
        );
        $this->assertInstanceOf('\Zend_Paginator', $paginator);
    }

    public function testConvertToDatabaseValuesWithoutAssociation()
    {
        $entityName = 'Serquant\Resource\Persistence\Zend\UserWithConvertibleProperties';
        $entity = new $entityName;
        $entity->id = 123;
        $entity->active = true;
        $entity->createdOn = new \DateTime('2000-01-01');

        $class = $this->persister->getClassMetadata($entityName);
        $platform = $this->em->getConnection()->getDatabasePlatform();

        $method = new \ReflectionMethod($this->persister, 'convertToDatabaseValues');
        $method->setAccessible(true);
        $this->assertEquals(
            array('identifier' => 123, 'is_active' => 1, 'created_on' => '2000-01-01'),
            $method->invoke($this->persister, $entity, $class, $platform)
        );
    }

    public function testConvertToDatabaseValuesWithOneToOneAssociationThatIsNull()
    {
        $userClass = 'Serquant\Resource\Persistence\Zend\CmsUser';
        $user = new $userClass;
        $user->id = 20;
        $user->status = 'online';
        $user->username = 'j';
        $user->name = 'Joe';

        $class = $this->persister->getClassMetadata($userClass);
        $platform = $this->em->getConnection()->getDatabasePlatform();

        $method = new \ReflectionMethod($this->persister, 'convertToDatabaseValues');
        $method->setAccessible(true);
        $this->assertEquals(
            array('id' => 20, 'status' => 'online', 'username' => 'j', 'name' => 'Joe'),
            $method->invoke($this->persister, $user, $class, $platform)
        );
    }

    public function testConvertToPhpValuesWithoutAssociation()
    {
        $expected = array(
            'id' => 123,
            'active' => true,
            'createdOn' => new \DateTime('2000-01-01')
        );

        $row = array(
            'identifier' => 123,
            'is_active' => 1,
            'created_on' => '2000-01-01'
        );

        $entityName = 'Serquant\Resource\Persistence\Zend\UserWithConvertibleProperties';
        $class = $this->persister->getClassMetadata($entityName);
        $platform = $this->em->getConnection()->getDatabasePlatform();

        $method = new \ReflectionMethod($this->persister, 'convertToPhpValues');
        $method->setAccessible(true);
        $this->assertEquals(
            $expected,
            $method->invoke($this->persister, $row, $class, $platform)
        );
    }

    public function testConvertToPhpValuesWithOneToOneAssociationThatIsNull()
    {
        $expected = array(
            'id' => 10,
            'status' => 'online',
            'username' => 'j',
            'name' => 'Joe'
        );

        $row = array(
            'id' => 10,
            'status' => 'online',
            'username' => 'j',
            'name' => 'Joe'
        );

        $entityName = 'Serquant\Resource\Persistence\Zend\CmsUser';
        $class = $this->persister->getClassMetadata($entityName);
        $platform = $this->em->getConnection()->getDatabasePlatform();

        $method = new \ReflectionMethod($this->persister, 'convertToPhpValues');
        $method->setAccessible(true);
        $this->assertEquals(
            $expected,
            $method->invoke($this->persister, $row, $class, $platform)
        );
    }

    public function testLoadEntityFromRegistry()
    {
        $row = array(
            'id' => 1,
            'first_name' => 'George',
            'last_name' => 'Washington'
        );

        $entityName = 'Serquant\Resource\Persistence\Zend\Person';

        $method = new \ReflectionMethod($this->persister, 'loadEntity');
        $method->setAccessible(true);
        $entity = $method->invoke($this->persister, $entityName, $row);

        $this->assertEquals($row['id'], $entity->getId());
        $this->assertEquals($row['first_name'], $entity->getFirstName());
        $this->assertEquals($row['last_name'], $entity->getLastName());

        // When the entity is already present in the registry,
        // it is just returned as is and not populated with given values
        $row['last_name'] = 'Adams';
        $entity = $method->invoke($this->persister, $entityName, $row);

        $this->assertEquals($row['id'], $entity->getId());
        $this->assertEquals($row['first_name'], $entity->getFirstName());
        $this->assertNotEquals($row['last_name'], $entity->getLastName());
    }
}