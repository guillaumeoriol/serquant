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
    Serquant\Converter\DateConverter;

class DateConverterNonPrintableObject
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class DateConverterPrintableObject
{
    private $value;

    public function __construct($value)
    {
        $this->value = new \DateTime($value);
    }

    public function __toString()
    {
        return $this->value->format(\DateTime::ATOM);
    }
}

class DateConverterTest extends \PHPUnit_Framework_TestCase
{
    private $converter;

    protected function setUp()
    {
        $this->converter = Converter::getConverter('date');
    }

    public function testGetAsDomainTypeWithNull()
    {
        $raw = null;
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals($raw, $converted);
    }

    public function testGetAsDomainTypeWithBooleanValue()
    {
        $raw = TRUE;
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithIntValue()
    {
        $raw = 123;
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithFloatValue()
    {
        $raw = 1.2345;
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithStringValue()
    {
        $raw = '';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertNull($converted);

        $raw = "\t  \r\n";
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertNull($converted);

        $timezone = new \DateTimeZone(date_default_timezone_get());

        $raw = '2011-10-20';
        $converted = $this->converter->getAsDomainType($raw);
        $expected = \DateTime::createFromFormat('Y-m-d\TH:i:s', $raw . 'T00:00:00', $timezone);
        $this->assertEquals(
            $this->converter->getAsString($expected),
            $this->converter->getAsString($converted)
        );

        $raw = '2011-10-21T09:33:45';
        $converted = $this->converter->getAsDomainType($raw);
        $expected = \DateTime::createFromFormat('Y-m-d\TH:i:s', $raw, $timezone);
        $this->assertEquals(
            $this->converter->getAsString($expected),
            $this->converter->getAsString($converted)
        );

        $raw = '2011-10-21T09:33:45+01:00';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals($raw, $this->converter->getAsString($converted));
    }

    public function testGetAsDomainTypeWithStringValueNotRepresentingADate()
    {
        $raw = 'ABCDEF';
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithStringValueRepresentingAWrongDate()
    {
        $raw = '2011-13-00';
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithStringValueRepresentingAWrongDateTime()
    {
        $raw = '2011-10-01 08:23:45';
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithStringValueRepresentingAWrongDateTimeTz()
    {
        $raw = '2011-10-21T09:33:45+0100';
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals('2011-10-21T09:33:45+01:00', $this->converter->getAsString($converted));

        $raw = '2011-10-01T08:23:45+02 00';
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithArrayValue()
    {
        $raw = array(1);
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithNonPrintableObjectValue()
    {
        $raw = new DateConverterNonPrintableObject(123);
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }

    public function testGetAsDomainTypeWithPrintableObjectValue()
    {
        $dateString = '2011-10-21T09:33:45+01:00';
        $date = new \DateTime($dateString);
        $raw = new DateConverterPrintableObject($dateString);
        $converted = $this->converter->getAsDomainType($raw);
        $this->assertEquals($date, $converted);
    }

    public function testGetAsDomainTypeWithResourceValue()
    {
        $raw = tmpfile();
        $this->setExpectedException('Serquant\Converter\Exception\ConverterException');
        $converted = $this->converter->getAsDomainType($raw);
    }
}