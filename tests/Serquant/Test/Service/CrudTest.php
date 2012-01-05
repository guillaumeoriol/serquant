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

use Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber;
use Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress;
use Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount;
use Serquant\Resource\Persistence\Doctrine\Entity\CmsUser;
use Serquant\Persistence\Doctrine;
use Serquant\Service\Crud;
use Serquant\Service\Result;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class CrudTestSerializerStub
{
    public function deserialize($entity, $data)
    {
        return new ConstraintViolationList();
    }
}

class CrudTestValidatorStubReturningNoViolation
{
    public function validate($entity)
    {
        return new ConstraintViolationList();
    }
}

class CrudTestValidatorStubReturningViolations
{
    public function validate($entity)
    {
        $violation = new ConstraintViolation(
            'message1',
            array('%lang%' => 'française', '%num%' => '#1'),
            null,
            'property1',
            null
        );
        $violations = new ConstraintViolationList();
        $violations->add($violation);
        return $violations;
    }
}

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
        $method = new \ReflectionMethod($service, 'get');
        $method->setAccessible(true);
        $this->assertTrue($obj === $method->invoke($service, 'obj'));
    }

    /**
     * @covers \Serquant\Service\Crud::fetchAll
     */
    public function testFetchAllThrowingException()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('fetchAll')
             ->will($this->throwException(new \Exception()));

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->fetchAll(array());
    }

    public function testFetchAllReturningResult()
    {
        $expected = array('dummy');
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('fetchAll')
             ->will($this->returnValue($expected));

        $service = new Crud($entityName, $stub);

        $result = $service->fetchAll(array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame($expected, $result->getData());
    }

    /**
     * @covers \Serquant\Service\Crud::fetchOne
     */
    public function testFetchOneThrowingException()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('fetchOne')
             ->will($this->throwException(new \Exception()));

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->fetchOne(array());
    }

    public function testFetchOneReturningResult()
    {
        $expected = new \stdClass();
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('fetchOne')
             ->will($this->returnValue($expected));

        $service = new Crud($entityName, $stub);

        $result = $service->fetchOne(array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame($expected, $result->getData());
    }

    /**
     * @covers \Serquant\Service\Crud::fetchPage
     */
    public function testFetchPageThrowingException()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('fetchPage')
             ->will($this->throwException(new \Exception()));

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->fetchPage(array());
    }

    public function testFetchPageReturningResult()
    {
        $expected = new \Zend_Paginator(new \Zend_Paginator_Adapter_Array(array()));
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('fetchPage')
             ->will($this->returnValue($expected));

        $service = new Crud($entityName, $stub);

        $result = $service->fetchPage(array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame($expected, $result->getData());
    }

    /**
     * @covers \Serquant\Service\Crud::fetchPairs
     */
    public function testFetchPairsThrowingInvalidArgumentException()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\InvalidArgumentException');
        $service->fetchPairs('id', 'name', array('select(id,name)'));
    }

    public function testFetchPairsThrowingRuntimeException()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('fetchPairs')
             ->will($this->throwException(new \Exception()));

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->fetchPairs();
    }

    public function testFetchPairsReturningResult()
    {
        $expected = array('id' => 1, 'name' => 'Washington');
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('fetchPairs')
             ->will($this->returnValue($expected));

        $service = new Crud($entityName, $stub);

        $result = $service->fetchPairs(array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame($expected, $result->getData());
    }

    /**
     * @covers \Serquant\Service\Crud::getDefault
     */
    public function testGetDefaultThrowingException()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\NonInstantiableEntity';
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('getDefault')
             ->will($this->throwException(new \Exception()));

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->getDefault();
    }

    public function testGetDefaultReturningResult()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $service = new Crud($entityName, $stub);

        $result = $service->getDefault(array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertInstanceOf($entityName, $result->getData());
    }

    /**
     * @covers \Serquant\Service\Crud::delete
     */
    public function testDeleteWithoutId()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\InvalidArgumentException');
        $service->delete();
    }

    public function testDeleteThrowingRuntimeException()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('retrieve')
             ->will($this->throwException(new \Exception()));

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->delete(1);
    }

    public function testDeleteReturningResult()
    {
        $expected = new \stdClass();
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('retrieve')
             ->will($this->returnValue($expected));

        $stub->expects($this->any())
             ->method('delete');

        $service = new Crud($entityName, $stub);

        $result = $service->delete(1);
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame($expected, $result->getData());
    }

    /**
     * @covers \Serquant\Service\Crud::retrieve
     */
    public function testRetrieveWithoutId()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\InvalidArgumentException');
        $service->retrieve();
    }

    public function testRetrieveThrowingRuntimeException()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('retrieve')
             ->will($this->throwException(new \Exception()));

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->retrieve(1);
    }

    public function testRetrieveReturningResult()
    {
        $expected = new \stdClass();
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('retrieve')
             ->will($this->returnValue($expected));

        $service = new Crud($entityName, $stub);

        $result = $service->retrieve(1);
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame($expected, $result->getData());
    }

    public function testGetErrorMessages()
    {
        // Setup translations
        $french = array(
            'message1' => 'traduction %lang% du message %num%',
            'message2' => 'traduction %lang% du message %num%',
            'message3' => 'traduction %lang% du message %num%'
        );

        // Build the translator
        $translator = new \Zend_Translate(array(
            'adapter' => 'array',
            'content' => $french,
            'locale' => 'fr'
        ));

        // Place the translator in DIC
        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $container = $front->getParam('bootstrap')->getContainer();
        $container->translator = $translator;

        // Setup a constraint violation list
        $violation1 = new ConstraintViolation(
            'message1',
            array('%lang%' => 'française', '%num%' => '#1'),
            null,
            'property1',
            null
        );
        $violations = new ConstraintViolationList();
        $violations->add($violation1);

        $entityName = null;
        $service = new Crud($entityName, $this->persister);
        $method = new \ReflectionMethod($service, 'getErrorMessages');
        $method->setAccessible(true);
        $messages = $method->invoke($service, $violations);
        $this->assertEquals(array('property1' => 'traduction française du message #1'), $messages);
    }

    /**
     * @covers \Serquant\Service\Crud::create
     */
    public function testCreateThrowingException()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\NonInstantiableEntity';
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->create(array());
    }

    public function testCreateWithoutViolations()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('create');

        // Build a serializer stub
        $serializer = new CrudTestSerializerStub();

        // Build a validator stub
        $validator = new CrudTestValidatorStubReturningNoViolation();

        // Place the serializer and the validator in DIC
        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $container = $front->getParam('bootstrap')->getContainer();
        $container->serializer = $serializer;
        $container->validator = $validator;

        $service = new Crud($entityName, $stub);
        $result = $service->create(array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertInstanceOf($entityName, $result->getData());
    }

    public function testCreateWithViolations()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('create');

        // Build a serializer stub
        $serializer = new CrudTestSerializerStub();

        // Build a validator stub
        $validator = new CrudTestValidatorStubReturningViolations();

        // Build a translator stub
        $french = array(
            'message1' => 'traduction %lang% du message %num%',
            'message2' => 'traduction %lang% du message %num%',
            'message3' => 'traduction %lang% du message %num%'
        );
        $translator = new \Zend_Translate(array(
            'adapter' => 'array',
            'content' => $french,
            'locale' => 'fr'
        ));

        // Place the serializer, validator and translator in DIC
        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $container = $front->getParam('bootstrap')->getContainer();
        $container->serializer = $serializer;
        $container->validator = $validator;
        $container->translator = $translator;

        $service = new Crud($entityName, $stub);
        $result = $service->create(array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_VALIDATION_ERROR, $result->getStatus());
        $this->assertEquals(array('property1' => 'traduction française du message #1'), $result->getErrors());
    }

    /**
     * @covers \Serquant\Service\Crud::update
     */
    public function testUpdateWithoutId()
    {
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\InvalidArgumentException');
        $service->update(null, array());
    }

    public function testUpdateThrowingRuntimeException()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\NonInstantiableEntity';
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('retrieve')
             ->will($this->throwException(new \Exception()));

         $service = new Crud($entityName, $stub);

        $this->setExpectedException('Serquant\Service\Exception\RuntimeException');
        $service->update(1, array());
    }

    public function testUpdateWithoutViolations()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expected = new $entityName;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('retrieve')
             ->will($this->returnValue($expected));

         $stub->expects($this->any())
             ->method('update');

        // Build a serializer stub
        $serializer = new CrudTestSerializerStub();

        // Build a validator stub
        $validator = new CrudTestValidatorStubReturningNoViolation();

        // Place the serializer and the validator in DIC
        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $container = $front->getParam('bootstrap')->getContainer();
        $container->serializer = $serializer;
        $container->validator = $validator;

        $service = new Crud($entityName, $stub);
        $result = $service->update(1, array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertInstanceOf($entityName, $result->getData());
    }

    public function testUpdateWithViolations()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\User';
        $expected = new $entityName;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();

        $stub->expects($this->any())
             ->method('retrieve')
             ->will($this->returnValue($expected));

        $stub->expects($this->any())
             ->method('update');

        // Build a serializer stub
        $serializer = new CrudTestSerializerStub();

        // Build a validator stub
        $validator = new CrudTestValidatorStubReturningViolations();

        // Build a translator stub
        $french = array(
            'message1' => 'traduction %lang% du message %num%',
            'message2' => 'traduction %lang% du message %num%',
            'message3' => 'traduction %lang% du message %num%'
        );
        $translator = new \Zend_Translate(array(
            'adapter' => 'array',
            'content' => $french,
            'locale' => 'fr'
        ));

        // Place the serializer, validator and translator in DIC
        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $container = $front->getParam('bootstrap')->getContainer();
        $container->serializer = $serializer;
        $container->validator = $validator;
        $container->translator = $translator;

        $service = new Crud($entityName, $stub);
        $result = $service->update(1, array());
        $this->assertInstanceOf('Serquant\Service\Result', $result);
        $this->assertEquals(Result::STATUS_VALIDATION_ERROR, $result->getStatus());
        $this->assertEquals(array('property1' => 'traduction française du message #1'), $result->getErrors());
    }

    /**
     * @covers \Serquant\Service\Crud::getSanitizedException
     */
    public function testGetSanitizedExceptionWithoutLog()
    {
        // Build a persister stub to instantiate the class to test
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();
        $service = new Crud($entityName, $stub);

        // Do the test
        $originalMessage = 'Original exception message';
        $exception = new \Exception($originalMessage);
        $method = new \ReflectionMethod($service, 'getSanitizedException');
        $method->setAccessible(true);
        $message = $method->invoke($service, $exception);
        $prefixPattern = 'An error has occurred while running service:';
        $pattern = '/^' . $prefixPattern . '\s' . $originalMessage . '/';
        $this->assertEquals(1, preg_match($pattern, $message));
    }

    public function testGetSanitizedExceptionWithLog()
    {
        // Build a logger stub
        $writer = new \Zend_Log_Writer_Mock();
        $logger = new \Zend_Log($writer);

        // Place the logger in DIC
        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $container = $front->getParam('bootstrap')->getContainer();
        $container->log = $logger;

        // Build a persister stub to instantiate the class to test
        $entityName = null;
        $stub = $this->getMockBuilder('Serquant\Persistence\Doctrine')
                     ->setConstructorArgs(array($this->em))
                     ->getMock();
        $service = new Crud($entityName, $stub);

        // Do the test
        $originalMessage = 'Original exception message';
        $exception = new \Exception($originalMessage);
        $method = new \ReflectionMethod($service, 'getSanitizedException');
        $method->setAccessible(true);
        $message = $method->invoke($service, $exception);
        $idPattern = ' \[errorId:shield-[0-9a-f.]*\]:';
        $replPattern = 'details may be found in the application log under given errorId';
        $prefixPattern = 'An error has occurred while running service';

        $pattern = '/^' . $prefixPattern . $idPattern . '\s' . $replPattern . '\s$/';
        $this->assertEquals(1, preg_match($pattern, $message));

        $pattern = '/^Details of' . $idPattern . '\s' . $originalMessage . '/';
        $this->assertEquals(1, preg_match($pattern, $writer->events[0]['message']));
    }
}