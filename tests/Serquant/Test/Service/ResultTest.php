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
namespace Serquant\Service\Test;

use Serquant\Service\Result;

/**
 * Result test class.
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $result = new Result(Result::STATUS_SUCCESS, null, array('error' => 'message'));
        $this->assertNull($result->getErrors());

        $result = new Result(Result::STATUS_VALIDATION_ERROR, null, null);
        $this->assertNull($result->getErrors());

        $result = new Result(Result::STATUS_VALIDATION_ERROR, null, array('error' => 'message'));
        $this->assertNotNull($result->getErrors());
    }

    public function testSetStatusWithInvalidArgument()
    {
        $this->setExpectedException('Serquant\Service\Exception\InvalidArgumentException');
        $result = new Result(-99999, null, null);
    }

    public function testGetStatus()
    {
        $result = new Result(Result::STATUS_VALIDATION_ERROR, null, null);
        $this->assertEquals(Result::STATUS_VALIDATION_ERROR, $result->getStatus());
    }
}