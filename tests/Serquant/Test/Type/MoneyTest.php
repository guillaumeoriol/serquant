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
use Serquant\Type\Money;

/**
 * Money test class.
 *
 * @category Serquant
 * @package  Type
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class MoneyTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructWithInvalidArgument()
    {
        $this->setExpectedException('Serquant\Type\Exception\InvalidArgumentException');
        $currency = Currency::getInstance('EUR');
        $badArgument = false;
        $money = new Money($badArgument, $currency);
    }

    public function testConstructWithNull()
    {
        $this->setExpectedException('Serquant\Type\Exception\InvalidArgumentException');
        $currency = Currency::getInstance('EUR');
        $money = new Money(null, $currency);
    }

    public function testConstructWithZero()
    {
        $currency = Currency::getInstance('EUR');
        $money = new Money(0, $currency);

        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertEquals(0, $reflProp->getValue($money));
    }

    public function testConstructWithInteger()
    {
        $currency = Currency::getInstance('EUR');
        $money = new Money(123, $currency);

        $this->assertEquals(12300, $money->getAmount());
        $this->assertSame($currency, $money->getCurrency());
    }

    public function testConstructWithFloatHavingNotApplicableFractionDigits()
    {
        $currency = Currency::getInstance('XDR');

        $money = new Money(123.4, $currency);

        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame(123, $reflProp->getValue($money));
    }

    public function testConstructInCentsWithInt()
    {
        $expected = 12345;
        $currency = Currency::getInstance('EUR');
        $money = new Money($expected, $currency, true);

        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame($expected, $reflProp->getValue($money));
    }

    public function testConstructInCentsWithFloat()
    {
        $currency = Currency::getInstance('EUR');
        $money = new Money(12345.6789, $currency, true);
        $this->assertSame(12346, $money->getAmount());
    }

    public function testConstructWithFloatHavingNoFractionDigits()
    {
        $currency = Currency::getInstance('JPY');

        $money = new Money(123.4, $currency);

        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame(123, $reflProp->getValue($money));
    }

    public function testConstructWithFloatHavingTwoFractionDigits()
    {
        $currency = Currency::getInstance('EUR');

        $money = new Money(123.0, $currency);

        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame(12300, $reflProp->getValue($money));

        $money = new Money(123.456, $currency);

        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame(12346, $reflProp->getValue($money));

        $money = new Money(123.999999999999999999, $currency);

        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame(12400, $reflProp->getValue($money));
    }

    public function testConstructWithFloatHavingThreeFractionDigits()
    {
        $currency = Currency::getInstance('KWD');

        $money = new Money(123.456789012345, $currency);

        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame(123457, $reflProp->getValue($money));
    }

    public function testEuroHelper()
    {
        $money = Money::euro(12.79);
        $this->assertInstanceOf('Serquant\Type\Money', $money);
        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame(1279, $reflProp->getValue($money));
    }

    public function testDollarHelper()
    {
        $money = Money::dollar(12.79);
        $this->assertInstanceOf('Serquant\Type\Money', $money);
        $reflProp = new \ReflectionProperty($money, 'amount');
        $reflProp->setAccessible(true);
        $this->assertSame(1279, $reflProp->getValue($money));
    }

    public function testEquals()
    {
        $money1 = Money::euro(39.50);
        $money2 = Money::euro(39.50);
        $this->assertTrue($money1->equals($money2));

        $money1 = Money::euro(39.50);
        $money2 = Money::euro(40);
        $this->assertFalse($money1->equals($money2));

        $money1 = Money::dollar(39.50);
        $money2 = Money::euro(39.50);
        $this->assertFalse($money1->equals($money2));
    }

    public function testAddWithMismatchingCurrencies()
    {
        $money1 = Money::dollar(39.50);
        $money2 = Money::euro(39.50);

        $this->setExpectedException('Serquant\Type\Exception\InvalidArgumentException');
        $money1->add($money2);
    }

    public function testAddWithSameCurrency()
    {
        $money1 = Money::euro(0.1);
        $money2 = Money::euro(0.7);

        $money3 = $money1->add($money2);
        $this->assertNotSame($money3, $money1);
        $this->assertEquals(80, $money3->getAmount());
    }

    public function testSubtractWithMismatchingCurrencies()
    {
        $money1 = Money::dollar(39.50);
        $money2 = Money::euro(39.50);

        $this->setExpectedException('Serquant\Type\Exception\InvalidArgumentException');
        $money1->subtract($money2);
    }

    public function testSubtractWithSameCurrency()
    {
        $money1 = Money::euro(12.34);
        $money2 = Money::euro(56.78);

        $money3 = $money1->subtract($money2);
        $this->assertNotSame($money3, $money1);
        $this->assertEquals(-4444, $money3->getAmount());
    }

    public function testCompareToWithMismatchingCurrencies()
    {
        $money1 = Money::dollar(39.50);
        $money2 = Money::euro(39.50);

        $this->setExpectedException('Serquant\Type\Exception\InvalidArgumentException');
        $money1->compareTo($money2);
    }

    public function testCompareToWithSameCurrency()
    {
        $money1 = Money::dollar(39.50);

        $money2 = Money::dollar(39.50);
        $this->assertEquals(0, $money1->compareTo($money2));

        $money2 = Money::dollar(40);
        $this->assertEquals(-1, $money1->compareTo($money2));

        $money2 = Money::dollar(30);
        $this->assertEquals(1, $money1->compareTo($money2));
    }

    public function testGreaterThan()
    {
        $money1 = Money::dollar(39.50);

        $money2 = Money::dollar(38);
        $this->assertTrue($money1->greaterThan($money2));

        $money2 = Money::dollar(40);
        $this->assertFalse($money1->greaterThan($money2));

        $money2 = Money::dollar(39.50);
        $this->assertFalse($money1->greaterThan($money2));
    }

    public function testLowerThan()
    {
        $money1 = Money::dollar(39.50);

        $money2 = Money::dollar(38);
        $this->assertFalse($money1->lowerThan($money2));

        $money2 = Money::dollar(40);
        $this->assertTrue($money1->lowerThan($money2));

        $money2 = Money::dollar(39.50);
        $this->assertFalse($money1->lowerThan($money2));
    }

    public function testMultiply()
    {
        $money = Money::dollar(39.50);

        $this->assertEquals(7900, $money->multiply(2)->getAmount());

        $this->assertEquals(6583, $money->multiply(1.6666666)->getAmount());
    }

    public function testDivide()
    {
        $money = Money::dollar(39.50);
        $this->assertEquals(1975, $money->divide(2)->getAmount());

        $money = Money::dollar(2);
        $this->assertEquals(67, $money->divide(3)->getAmount());
    }

    public function testAllocate()
    {
        $ratios = array(3, 7);
        $money = Money::dollar(0.05);
        $monies = $money->allocate($ratios);
        $this->assertEquals(2, $monies[0]->getAmount());
        $this->assertEquals(3, $monies[1]->getAmount());
    }

    public function testAllocateWithRandomValues()
    {
        $count = rand(2, 20);
        $ratios = array();
        for ($i = 0; $i < $count; $i++) {
            $ratios[] = rand(1, 100);
        }
        $money = Money::dollar(rand(100, 10000) / 100);

        $monies = $money->allocate($ratios);
        $message = 'Trying to allocate ' . $money->getAmount() . ' among ' . print_r($ratios, true);
        $total = 0;
        foreach ($monies as $element) {
            $total += $element->getAmount();
        }
        $this->assertEquals($money->getAmount(), $total, $message);
    }
}