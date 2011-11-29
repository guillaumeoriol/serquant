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
    Serquant\Converter\FloatConverter;

class FloatConverterObjectA
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class FloatConverterObjectB
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

class FloatConverterObjectC
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

class FloatConverterTest extends \PHPUnit_Framework_TestCase
{
    private $converter;

    protected function setUp()
    {
        $this->converter = Converter::getConverter('float');
    }

    public function testGetAsDomainTypeWithNull()
    {
        $raw = null;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertNull($converted);
    }

    public function testGetAsDomainTypeWithBooleanValue()
    {
        $converted = $this->converter->getAsDomainType(FALSE);
        $this->assertEquals(0, $converted);
        $this->assertInternalType('float', $converted);

        $converted = $this->converter->getAsDomainType(TRUE);
        $this->assertEquals(1, $converted);
        $this->assertInternalType('float', $converted);
    }

    public function testGetAsDomainTypeWithIntValue()
    {
        $raw = 123;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertTrue(123.0 === $converted);
    }

    public function testGetAsDomainTypeWithFloatValue()
    {
        $raw = 1.234;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertTrue($raw === $converted);
    }

    public function testGetAsDomainTypeWithStringValue()
    {
        $raw = '';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertNull($converted);

        $raw = "\t   \r\n";
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertNull($converted);

        $raw = '0';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(0, $converted, '', 0.000001);
        $this->assertInternalType('float', $converted);

        $raw = '123';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(123, $converted, '', 0.000001);
        $this->assertInternalType('float', $converted);

        $raw = '-123';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(-123, $converted, '', 0.000001);
        $this->assertInternalType('float', $converted);

        $raw = '12.3';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(12.3, $converted, '', 0.000001);
        $this->assertInternalType('float', $converted);

        $raw = '012';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(12, $converted, '', 0.000001);
        $this->assertInternalType('float', $converted);

        $raw = '1e3';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(1000, $converted, '', 0.000001);
        $this->assertInternalType('float', $converted);

        $raw = '0x1A';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(26, $converted, '', 0.000001);
        $this->assertInternalType('float', $converted);

        $raw = ' 123 ';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(123, $converted, '', 0.000001);
        $this->assertInternalType('float', $converted);
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
        $raw = new FloatConverterObjectA(123);
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithPrintableObjectValue()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = new FloatConverterObjectB(123);
        $converted = $this->converter->getAsDomainType($raw);

        $raw = new FloatConverterObjectC('1A');
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(26, $converted);
    }

    public function testGetAsDomainTypeWithResourceValue()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = tmpfile();
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsString()
    {
        $converted = $this->converter->getAsString(0.7);
        $this->assertEquals("0.7", $converted);
    }

    public function testGetAsStringWithNull()
    {
        $converted = $this->converter->getAsString(null);
        $this->assertNull($converted);
    }
}