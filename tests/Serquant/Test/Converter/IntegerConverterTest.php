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

use Serquant\Converter\Converter,
    Serquant\Converter\IntegerConverter;

class IntegerConverterObjectA
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class IntegerConverterObjectB
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return '(' . $this->value . ')';
    }
}

class IntegerConverterObjectC
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return hexdec($this->value);
    }
}

class IntegerConverterTest extends \PHPUnit_Framework_TestCase
{
    private $converter;

    protected function setUp()
    {
        $this->converter = Converter::getConverter('integer');
    }

    public function testGetAsDomainTypeWithNull()
    {
        $raw = null;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals($raw, $converted);
    }

    public function testGetAsDomainTypeWithBooleanValue()
    {
        $converted = $this->converter->getAsDomainType(FALSE);
        $this->assertEquals(0, $converted);

        $converted = $this->converter->getAsDomainType(TRUE);
        $this->assertEquals(1, $converted);
    }

    public function testGetAsDomainTypeWithIntValue()
    {
        $raw = 123;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertTrue($raw === $converted);
    }

    public function testGetAsDomainTypeWithFloatValue()
    {
        $raw = 1.234;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(1, $converted);

        $raw = 5.678;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(6, $converted);

        $raw = 1.5;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(2, $converted);

        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = ((float) PHP_INT_MAX) + 1000000;
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithStringValue()
    {
        $raw = '123';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(123, $converted);

        $raw = '-123';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(-123, $converted);

        $raw = '12.3';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(12, $converted);

        $raw = '012';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(12, $converted);

        $raw = '1e3';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(1000, $converted);

        $raw = '0x1A';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(26, $converted);

        $raw = ' 123 ';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(123, $converted);
    }

    public function testGetAsDomainTypeWithStringValueNotNumeric()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = 'A123';
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithStringValueInfinite()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = '1e500';
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithArrayValue()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = array(1);
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithNonPrintableObjectValue()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = new IntegerConverterObjectA(123);
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithPrintableObjectValue()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = new IntegerConverterObjectB(123);
        $converted = $this->converter->getAsDomainType($raw);

        $raw = new IntegerConverterObjectC('1A');
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(26, $converted);
    }

    public function testGetAsDomainTypeWithResourceValue()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = tmpfile();
        $converted = $this->converter->getAsDomainType($raw);
    }
}