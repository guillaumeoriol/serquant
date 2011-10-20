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

class StringConverterObjectA
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class StringConverterObjectB
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

class StringConverterObjectC
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return '   -' . $this->value . '-   ';
    }
}

class StringConverterTest extends \PHPUnit_Framework_TestCase
{
    private $converter;

    protected function setUp()
    {
        $this->converter = Converter::getConverter('string');
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
        $this->assertEquals('0', $converted);

        $converted = $this->converter->getAsDomainType(TRUE);
        $this->assertEquals('1', $converted);
    }

    public function testGetAsDomainTypeWithIntValue()
    {
        $raw = 123;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals('123', $converted);
    }

    public function testGetAsDomainTypeWithFloatValue()
    {
        $raw = 1.2345;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals('1.2345', $converted);

        $raw = 12345678901234567890123456789;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals('1.2345678901235E+28', $converted);
    }

    public function testGetAsDomainTypeWithStringValue()
    {
        $raw = 'abcd';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertTrue($raw === $converted);

        $raw = "\t    efgh\r\n";
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals('efgh', $converted);
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
        $raw = new StringConverterObjectA(123);
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithPrintableObjectValue()
    {
        $raw = new StringConverterObjectB(123);
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals('(123)', $converted);
    }

    public function testGetAsDomainTypeWithResourceValue()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = tmpfile();
        $converted = $this->converter->getAsDomainType($raw);
    }
}