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
namespace Serquant\Test\Converter;

use Serquant\Converter\Converter;

/**
 * Converter test class.
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConverterWithInvalidArgument()
    {
        $this->setExpectedException('Serquant\Converter\Exception\InvalidArgumentException');
        $converter = Converter::getConverter('dummy');
    }
}