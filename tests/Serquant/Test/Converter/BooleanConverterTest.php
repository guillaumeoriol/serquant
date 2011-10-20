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
    Serquant\Converter\BooleanConverter;

class BooleanConverterObjectA
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class BooleanConverterObjectB
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return ' ' . $this->value . ' ';
    }
}

class BooleanConverterObjectC
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

class BooleanConverterTest extends \PHPUnit_Framework_TestCase
{
    private $converter;

    protected function setUp()
    {
        $this->converter = Converter::getConverter('boolean');
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
        $this->assertEquals(FALSE, $converted);

        $converted = $this->converter->getAsDomainType(TRUE);
        $this->assertEquals(TRUE, $converted);
    }

    public function testGetAsDomainTypeWithIntValue()
    {
        $raw = 0;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(FALSE, $converted);

        $raw = 123;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(TRUE, $converted);

        $raw = -1;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(TRUE, $converted);
    }

    public function testGetAsDomainTypeWithFloatValue()
    {
        $raw = 0.0;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(FALSE, $converted);

        $raw = 0.00000000000000001;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(TRUE, $converted);

        $raw = 1.234e5;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(TRUE, $converted);
    }

    public function testGetAsDomainTypeWithStringValue()
    {
        $raw = '';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(FALSE, $converted);

        $raw = ' ';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(FALSE, $converted);

        $raw = '0';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(FALSE, $converted);

        $raw = ' 0 ';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(FALSE, $converted);

        $raw = '0.0';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(TRUE, $converted);

        $raw = '-1';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(TRUE, $converted);

        $raw = 'ABC';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(TRUE, $converted);
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
        $raw = new BooleanConverterObjectA(123);
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithPrintableObjectValue()
    {
        $raw = new BooleanConverterObjectB(0);
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(FALSE, $converted);

        $raw = new BooleanConverterObjectC(1);
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals(TRUE, $converted);
    }

    public function testGetAsDomainTypeWithResourceValue()
    {
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $raw = tmpfile();
        $converted = $this->converter->getAsDomainType($raw);
    }
}