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
namespace Serquant\Type;

use Serquant\Type\Exception\InvalidArgumentException;

/**
 * Represents a monetary value.
 *
 * Implements the {@link http://martinfowler.com/eaaCatalog/money.html Money}
 * [PoEAA] pattern.
 * Objects of this class are immutable.
 *
 * @category Serquant
 * @package  Type
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Money
{
    /**
     * Amount, stored as a whole number
     * @var integer
     */
    private $amount;

    /**
     * Currency
     * @var Currency
     */
    private $currency;

    /**
     * Factor matching fraction digits
     * @var array
     */
    private static $factor = array(1, 10, 100, 1000);

    /**
     * Constructs a Money instance.
     *
     * When a floating-point amount is given, it is rounded to the nearest cent.
     * The rounded method is <em>half up</em>.
     *
     * @param integer|float $amount Amount
     * @param Currency $currency Amount currency
     * @param boolean $inCents When true, the amount is given in cents;
     * otherwise, the amount is given in base unit.
     * @throws InvalidArgumentException if the amount is neither an integer nor
     * a floating-point value.
     */
    public function __construct($amount, Currency $currency, $inCents = false)
    {
        $this->currency = $currency;
        if (is_float($amount)) {
            if ($inCents) {
                $this->amount = (int) round($amount, 0, PHP_ROUND_HALF_UP);
            } else {
                $this->amount = (int) round(
                    $amount * $this->getFactor(), 0, PHP_ROUND_HALF_UP
                );
            }
        } else if (is_int($amount)) {
            if ($inCents) {
                $this->amount = $amount;
            } else {
                $this->amount = $amount * $this->getFactor();
            }
        } else {
            throw new InvalidArgumentException(
                'The amount must be an integer or a floating-point value, ' .
                gettype($amount) . ' given.'
            );
        }
    }

    /**
     * Helper function to return a Money instance in euro currency
     *
     * @param integer|float $amount Amount in base unit
     * @return Money
     */
    public static function euro($amount)
    {
        return new self($amount, Currency::getInstance('EUR'));
    }

    /**
     * Helper function to return a Money instance in US dollar currency
     *
     * @param integer|float $amount Amount in base unit
     * @return Money
     */
    public static function dollar($amount)
    {
        return new self($amount, Currency::getInstance('USD'));
    }

    /**
     * Gets the amount as a whole number (expressed in cents).
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Gets the money currency.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Gets the factor a base unit amount should be multiplied by to get a
     * whole number value.
     *
     * @return integer
     */
    private function getFactor()
    {
        $fractionDigits = $this->currency->getDefaultFractionDigits();
        return $fractionDigits === -1 ? 1 : self::$factor[$fractionDigits];
    }

    /**
     * Determines if this Money equals the given one.
     *
     * @param Money $other Money to check for equality
     * @return boolean
     */
    public function equals(Money $other)
    {
        // Currency is compared by reference as its flyweight implementation
        // prevents from having two instances of the same currency.
        return ($this->currency === $other->currency)
            && ($this->amount === $other->amount);
    }

    /**
     * Throws an exception when this Money currency doesn't match the give one.
     *
     * @param Money $other Money to check currency against
     * @return void
     * @throws InvalidArgumentException if this Money currency doesn't match the
     * give one.
     */
    private function assertSameCurrency(Money $other)
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                'Unable to perform arithmetic operation on two moneys having ' .
                'currency that do not match: ' . $this->currency . '/' .
                $other->currency
            );
        }
    }

    /**
     * Adds a Money to this one, returning a new Money.
     *
     * @param Money $other Money to add to this one
     * @return Money
     * @throws InvalidArgumentException if this Money currency doesn't match the
     * give one.
     */
    public function add(Money $other)
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency, true);
    }

    /**
     * Subtract a Money from this one, returning a new Money.
     *
     * @param Money $other Money to subtract from this one
     * @return Money
     * @throws InvalidArgumentException if this Money currency doesn't match the
     * give one.
     */
    public function subtract(Money $other)
    {
        $this->assertSameCurrency($other);
        return new self($this->amount - $other->amount, $this->currency, true);
    }

    /**
     * Compares a Money to this one.
     *
     * This method returns -1 if this amount is lower than the other, 0 if they
     * are equal, and 1 if the second is lower.
     *
     * @param Money $other Money to compare with
     * @return integer
     * @throws InvalidArgumentException if this Money currency doesn't match the
     * give one.
     */
    public function compareTo(Money $other)
    {
        $this->assertSameCurrency($other);
        if ($this->amount < $other->amount) {
            return -1;
        } else if ($this->amount === $other->amount) {
            return 0;
        } else {
            return 1;
        }
    }

    /**
     * Determines if this Money amount is greater than the give one.
     *
     * @param Money $other Money to compare with
     * @return boolean true if this Money amount is greater than the give one
     * @throws InvalidArgumentException if this Money currency doesn't match the
     * give one.
     */
    public function greaterThan(Money $other)
    {
        return ($this->compareTo($other) > 0);
    }

    /**
     * Determines if this Money amount is lower than the give one.
     *
     * @param Money $other Money to compare with
     * @return boolean true it this Money amount is lower than the give one
     * @throws InvalidArgumentException if this Money currency doesn't match the
     * give one.
     */
    public function lowerThan(Money $other)
    {
        return ($this->compareTo($other) < 0);
    }

    /**
     * Multiplies this Money by the given multiplier, returning a new Money.
     *
     * @param integer|float $multiplier Value to multiply this Money amount by
     * @return Money
     */
    public function multiply($multiplier)
    {
        return new self($this->amount * $multiplier, $this->currency, true);
    }

    /**
     * Divides this Money by the given divisor, returning a new Money.
     *
     * @param integer|float $divisor Value to divide this Money amount by
     * @return Money
     */
    public function divide($divisor)
    {
        return new self($this->amount / $divisor, $this->currency, true);
    }

    /**
     * Allocates this amount among different accounts.
     *
     * This method solves Matt Foemmel's conendrum explained in [PoEAA]:
     *
     * <em>Suppose I have a business rule that says that I have to allocate the
     * whole amount of a sum of money to two accounts: 70% to one and 30% to
     * another. I have 5 cents to allocate. If I do the math I end up with 3.5
     * cents and 1.5 cents. Whichever way I round these I get into trouble. If
     * I do the usual rounding to nearest the 1.5 becomes 2 and 3.5 becomes 4.
     * So I end up gaining a penny. Rounding downs gives me 4 cents and rounding
     * up gives me 6 cents</em>.
     *
     * @param array $ratios Array of ratios to allocate among
     * @return array An array of Money
     */
    public function allocate(array $ratios)
    {
        $total = 0;
        $count = count($ratios);
        for ($i = 0; $i < $count; $i++) {
            $total += $ratios[$i];
        }

        $remainder = $this->amount;
        $results = array();
        for ($i = 0; $i < $count; $i++) {
            $results[$i] = new self($this->amount * $ratios[$i] / $total,
                $this->currency, true);
            $remainder -= $results[$i]->amount;
        }

        if ($remainder > 0) {
            for ($i = 0; $i < $remainder; $i++) {
                $results[$i]->amount++;
            }
        } else if ($remainder < 0) {
            for ($i = abs($remainder); $i > 0; $i--) {
                $results[$i]->amount--;
            }
        }

        return $results;
    }
}