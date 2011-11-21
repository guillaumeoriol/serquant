<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Type
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\Type;

use Serquant\Type\Currency;

/**
 * Currency test class.
 *
 * @category Serquant
 * @package  Type
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class CurrencyTest extends \PHPUnit_Framework_TestCase
{
    public function testPrivateConstructor()
    {
        $class = new \ReflectionClass('Serquant\Type\Currency');
        $method = $class->getConstructor();
        $this->assertTrue($method->isPrivate());
    }

    public function testGetInstanceWithoutArgument()
    {
        $this->setExpectedException('Serquant\Type\Exception\InvalidArgumentException');
        $currency = Currency::getInstance();
    }

    public function testGetInstanceWithNull()
    {
        $this->setExpectedException('Serquant\Type\Exception\InvalidArgumentException');
        $currency = Currency::getInstance(null);
    }

    public function testGetInstanceWithInvalidArgument()
    {
        $this->setExpectedException('Serquant\Type\Exception\InvalidArgumentException');
        $currency = Currency::getInstance('???');
    }

    public function testGetInstanceWithEUR()
    {
        $currency = Currency::getInstance('EUR');
        $this->assertInstanceOf('Serquant\Type\Currency', $currency);
    }

    public function testGetAvailableCurrencies()
    {
        $currencies = Currency::getAvailableCurrencies();
        $this->assertTrue(is_array($currencies));
        $this->assertEquals(182, count($currencies));
        $this->assertTrue(in_array('USD', $currencies));
    }

    public function testGetCurrencyCode()
    {
        $currencies = Currency::getAvailableCurrencies();
        foreach ($currencies as $code) {
            $currency = Currency::getInstance($code);
            $this->assertEquals($code, $currency->getCurrencyCode());
        }
    }

    public function testGetSymbol()
    {
        $currency = Currency::getInstance('GBP');
        $this->assertEquals('£', $currency->getSymbol());

        $currency = Currency::getInstance('ZWL');
        $this->assertEquals('ZWL', $currency->getSymbol());
    }

    public function testGetDefaultFractionDigits()
    {
        $currency = Currency::getInstance('XDR');
        $this->assertEquals(-1, $currency->getDefaultFractionDigits());

        $currency = Currency::getInstance('JPY');
        $this->assertEquals(0, $currency->getDefaultFractionDigits());

        $currency = Currency::getInstance('EUR');
        $this->assertEquals(2, $currency->getDefaultFractionDigits());

        $currency = Currency::getInstance('KWD');
        $this->assertEquals(3, $currency->getDefaultFractionDigits());
    }

    public function testGetNumericCode()
    {
        $currency = Currency::getInstance('USD');
        $code = $currency->getNumericCode();
        $this->assertInternalType('integer', $code);
        $this->assertEquals(840, $code);
    }

    public function testGetDisplayName()
    {
        $currency = Currency::getInstance('USD');
        $name = $currency->getDisplayName();
        $this->assertInternalType('string', $name);
        $this->assertEquals('US Dollar', $name);
    }
}