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
namespace Serquant\Test\DependencyInjection\Factory;

use Doctrine\Common\EventSubscriber;
use Serquant\DependencyInjection\Factory\EventManagerFactory;

/**
 * Test class for EventManagerFactory
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class EventManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers Serquant\DependencyInjection\Factory\EventManagerFactory::get
     */
    public function testGetWithEmptyConfig()
    {
        $config = array();
        $evm = EventManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\EventManager', $evm);
    }

    /**
     * @covers Serquant\DependencyInjection\Factory\EventManagerFactory::addListeners
     */
    public function testGetWithSubscribersArgumentOfWrongType()
    {
        $config = array(
            'subscribers' => ''
        );
        $this->setExpectedException('PHPUnit_Framework_Error');
        $evm = EventManagerFactory::get($config);
    }

    public function testGetWithSubscriberName()
    {
        $config = array(
            'subscribers' => array(
                'Serquant\Test\DependencyInjection\Factory\TestSubscriber'
            )
        );
        $evm = EventManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\EventManager', $evm);
        $this->assertTrue(
            $evm->hasListeners(TestSubscriber::TEST_SUBSCRIBER_EVENT)
        );
    }

    public function testGetWithSubscriberOfWrongType()
    {
        $config = array(
            'subscribers' => array(
                123
            )
        );
        $this->setExpectedException('Serquant\DependencyInjection\Exception\InvalidArgumentException', null, 10);
        $evm = EventManagerFactory::get($config);
    }

    public function testGetWithSubscriberOfWrongClass()
    {
        $config = array(
            'subscribers' => array(
                'stdClass'
            )
        );
        $this->setExpectedException('Serquant\DependencyInjection\Exception\InvalidArgumentException', null, 11);
        $evm = EventManagerFactory::get($config);
    }

    public function testGetWithSubscriberInstance()
    {
        $config = array(
            'subscribers' => array(
                new TestSubscriber()
            )
        );
        $evm = EventManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\EventManager', $evm);
        $this->assertTrue(
            $evm->hasListeners(TestSubscriber::TEST_SUBSCRIBER_EVENT)
        );
    }

    /**
     * @covers Serquant\DependencyInjection\Factory\EventManagerFactory::addListeners
     */
    public function testGetWithListenersArgumentOfWrongType()
    {
        $config = array(
            'listeners' => ''
        );
        $this->setExpectedException('PHPUnit_Framework_Error');
        $evm = EventManagerFactory::get($config);
    }

    public function testGetWithListenerOfWrongType()
    {
        $wrong = new \stdClass();
        $config = array(
            'listeners' => array(
                $wrong
            )
        );
        $this->setExpectedException('Serquant\DependencyInjection\Exception\InvalidArgumentException', null, 20);
        $evm = EventManagerFactory::get($config);
    }

    public function testGetWithListenerName()
    {
        $config = array(
            'listeners' => array(
                'Serquant\Test\DependencyInjection\Factory\TestListener'
            )
        );
        $evm = EventManagerFactory::get($config);
        $this->assertInstanceOf('Doctrine\Common\EventManager', $evm);
        $this->assertTrue(
            $evm->hasListeners(TestListener::TEST_LISTENER_EVENT1)
        );
        $this->assertTrue(
            $evm->hasListeners(TestListener::TEST_LISTENER_EVENT2)
        );
    }
}

class TestSubscriber implements EventSubscriber
{
    const TEST_SUBSCRIBER_EVENT = 'testSubscriberEvent';

    public $invoked = false;

    public function getSubscribedEvents()
    {
        return self::TEST_SUBSCRIBER_EVENT;
    }

    public function testSubscriberEvent()
    {
        $this->invoked = true;
    }
}

class TestListener
{
    const TEST_LISTENER_EVENT1 = 'testListenerEvent1';
    const TEST_LISTENER_EVENT2 = 'testListenerEvent2';

    public $invoked1 = false;
    public $invoked2 = false;

    public function __construct($evm)
    {
        $evm->addEventListener(
            array(self::TEST_LISTENER_EVENT1, self::TEST_LISTENER_EVENT2),
            $this
        );
    }

    public function testListenerEvent1()
    {
        $this->invoked1 = true;
    }

    public function testListenerEvent2()
    {
        $this->invoked2 = true;
    }
}
