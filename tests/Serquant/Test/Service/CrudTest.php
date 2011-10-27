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
namespace Serquant\Test\Service;

use Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber,
    Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress,
    Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount,
    Serquant\Resource\Persistence\Doctrine\Entity\CmsUser,
    Serquant\Persistence\Doctrine,
    Serquant\Service\Crud;

class CrudTest extends \Serquant\Resource\Persistence\OrmFunctionalTestCase
{
    private $em;

    private $persister;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->persister = new Doctrine($this->em);
    }

    public function testGetService()
    {
        $obj = new \stdClass();
        $obj->x = 1;
        $obj->y = 'a';
        $obj->z = true;

        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $container = $front->getParam('bootstrap')->getContainer();
        $container->obj = $obj;

        $entityName = null;
        $service = new Crud($entityName, $this->persister);
        $method = new \ReflectionMethod($service, 'getService');
        $method->setAccessible(true);
        $this->assertTrue($obj === $method->invoke($service, 'obj'));
    }

    public function testPopulateOnBasicEntityWithSetters()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\Role';
        $service = new Crud($entityName, $this->persister);

        $entity = new $entityName;
        $data = array(
            'id' => '1',
            'name' => 123
        );

        $method = new \ReflectionMethod($service, 'populate');
        $method->setAccessible(true);
        $violations = $method->invoke($service, $entity, $data);
        $this->assertTrue(
            is_int($entity->getId())
            && is_string($entity->getName())
        );
    }

    public function testPopulateOnBasicEntityWithoutSetters()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\User';
        $service = new Crud($entityName, $this->persister);

        $entity = new $entityName;
        $data = array(
            'id' => '1',
            'status' => true,
            'username' => 1.2e3,
            'name' => 123
        );

        $method = new \ReflectionMethod($service, 'populate');
        $method->setAccessible(true);
        $violations = $method->invoke($service, $entity, $data);
        $this->assertTrue(
            is_int($entity->getId())
            && is_string($entity->getStatus())
            && is_string($entity->getUsername())
            && is_string($entity->getName())
        );
    }

    public function testPopulateOnEntityWithAssociations()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';
        $service = new Crud($entityName, $this->persister);

        $entity = new $entityName;
        $data = array(
            'id' => '1',
            'status' => false,
            'username' => 'gwashington',
            'name' => 123,
            'phonenumbers' => array(
            	array('phonenumber' => '0160947030'),
            	array('phonenumber' => '0160947031')
            ),
            'account' => array(
                'bank' => '12345',
                'accountNumber' => '12345678901'
            ),
            'address' => array(
                'country' => 'France',
                'zip' => 77280,
                'city' => 'Othis'
            )
        );

        $method = new \ReflectionMethod($service, 'populate');
        $method->setAccessible(true);
        $violations = $method->invoke($service, $entity, $data);
        $this->assertInternalType('integer', $entity->getId());
        $this->assertInternalType('string', $entity->getStatus());
        $this->assertInternalType('string', $entity->getUsername());
        $this->assertInternalType('string', $entity->getName());
        $this->assertInstanceOf('\Doctrine\Common\Collections\ArrayCollection', $entity->getPhonenumbers());
        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $entity->getPhonenumbers()->current());
        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount', $entity->getAccount());
        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $entity->getAddress());
    }

    public function testPopulateWithoutViolations()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\Role';
        $service = new Crud($entityName, $this->persister);

        $entity = new $entityName;
        $data = array(
            'id' => NULL,
            'name' => 'test'
        );

        $method = new \ReflectionMethod($service, 'populate');
        $method->setAccessible(true);
        $violations = $method->invoke($service, $entity, $data);
        $this->assertEquals(0, count($violations));
    }

    public function testPopulateWithViolations()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\Role';
        $service = new Crud($entityName, $this->persister);

        $entity = new $entityName;
        $data = array(
            'id' => NULL,
            'name' => new \stdClass()
        );

        $method = new \ReflectionMethod($service, 'populate');
        $method->setAccessible(true);
        $violations = $method->invoke($service, $entity, $data);
        $this->assertEquals(1, count($violations));
    }
}