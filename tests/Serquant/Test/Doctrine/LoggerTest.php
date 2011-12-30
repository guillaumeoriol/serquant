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
namespace Serquant\Test\Doctrine;

use Serquant\Doctrine\Logger;

/**
 * Test class for the Logger
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class LoggerTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $options = array(
            array(
                'writerName' => 'Mock'
            )
        );
        $logger = new Logger($options);
        $this->assertAttributeInstanceOf('Zend_Log', 'logger', $logger);
    }

    public function testStartQuery()
    {
        $message = 'dummy';
        $mock = new \Zend_Log_Writer_Mock();
        $options = array($mock);
        $logger = new Logger($options);
        $logger->startQuery($message);
        $this->assertEquals($message, $mock->events[0]['message']);
    }

    public function testStartQueryWithReplacements()
    {
        $format = 'The quick brown ? jumps over the lazy ?';
        $replacements = array('fox', 'dog');
        $mock = new \Zend_Log_Writer_Mock();
        $options = array($mock);
        $logger = new Logger($options);
        $logger->startQuery($format, $replacements);
        $this->assertEquals('The quick brown fox jumps over the lazy dog', $mock->events[0]['message']);
    }

    public function testStopQuery()
    {
        $message = 'dummy';
        $mock = new \Zend_Log_Writer_Mock();
        $options = array($mock);
        $logger = new Logger($options);
        $logger->startQuery($message);
        $logger->stopQuery();
        $this->assertEquals($message, $mock->events[0]['message']);
        $this->assertEquals(1, preg_match('/^Elapsed time: [0-9]+\.[0-9]* ms$/', $mock->events[1]['message']));
    }
}