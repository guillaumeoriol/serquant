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
namespace Serquant\Test\Controller\Plugin;

use Serquant\Controller\Plugin\RangeHandler;

/**
 * RangeHandler test class
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class RangeHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatchLoopStartupWithNonHttpRequest()
    {
        $expected = new \Zend_Controller_Request_Simple();
        $request = clone $expected;

        $rangeHandler = new RangeHandler();
        $rangeHandler->dispatchLoopStartup($request);
        $this->assertEquals($expected, $request);
    }

    public function testDispatchLoopStartupWithoutRange()
    {
        $request = new \Zend_Controller_Request_Http();

        $rangeHandler = new RangeHandler();
        $rangeHandler->dispatchLoopStartup($request);
        $this->assertEmpty($request->getQuery());
    }

    public function testDispatchLoopStartupOnFirstPage()
    {
        $request = new \Zend_Controller_Request_Http();
        $range = 'items=0-9';
        $_SERVER['HTTP_RANGE'] = $range;

        $rangeHandler = new RangeHandler();
        $rangeHandler->dispatchLoopStartup($request);
        $this->assertEquals(array('limit(0,10)' => ''), $request->getQuery());
        unset($_SERVER['HTTP_RANGE']);
    }

    public function testDispatchLoopStartupOnSubsequentPage()
    {
        $request = new \Zend_Controller_Request_Http();
        $lower = rand(10,100);
        $upper = rand($lower, 200);
        $count = $upper - $lower + 1;
        $range = "items=$lower-$upper";
        $_SERVER['HTTP_RANGE'] = $range;
        $this->assertEquals($range, $_SERVER['HTTP_RANGE']);

        $rangeHandler = new RangeHandler();
        $rangeHandler->dispatchLoopStartup($request);
        $this->assertEquals(array("limit($lower,$count)" => ''), $request->getQuery());
        unset($_SERVER['HTTP_RANGE']);
    }
}