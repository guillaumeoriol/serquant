<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Converter;

use Serquant\Converter\Converter,
    Serquant\Converter\Exception\ConverterException;

/**
 * Converter for the 'integer' mapping type.
 *
 * The corresponding domain type is the PHP primitive type 'integer'.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class IntegerConverter extends Converter
{
    /**#@+
     * Code of the {@link ConverterException} thrown in case of conversion
     * failure.
     * @var int
     */
    const OBJECT_TO_STRING = 1;
    const ARRAY_OR_RESOURCE = 2;
    const NOT_NUMERIC = 3;
    const INFINITE = 4;
    const OVERFLOW = 5;
    /**#@-*/

    /**
     * Message identifiers to be returned if the conversion fails.
     * The message format string for these messages may optionally include the
     * following placeholders:
     * {type} is replaced by the mapping type name
     * {value} is replaced by the value before its conversion.
     * @var array
     */
    private static $messageId = array(
        self::OBJECT_TO_STRING => 'com.serquant.converter.integer.OBJECT_TO_STRING',
        self::ARRAY_OR_RESOURCE => 'com.serquant.converter.integer.ARRAY_OR_RESOURCE',
        self::NOT_NUMERIC => 'com.serquant.converter.integer.NOT_NUMERIC',
        self::INFINITE => 'com.serquant.converter.integer.INFINITE',
        self::OVERFLOW => 'com.serquant.converter.integer.OVERFLOW'
    );

    /**
     * {@inheritDoc}
     *
     * Conversion rules:
     * <ul>
     *   <li>NULL is returned unchanged.</li>
     *   <li>The boolean TRUE is converted to 1 and the boolean FALSE is
     *       converted to 0.</li>
     *   <li>An integer is returned unchanged.</li>
     *   <li>A float number is rounded to the nearest integer. An exact half
     *       is rounded up (9.5 is rounded to 10). If the float value is greater
     *       than the max integer value, a {@link ConverterException} exception
     *       is thrown. Be careful with near-limit values: for instance
     *       <code>var_dump((((float) PHP_INT_MAX) + 1) > PHP_INT_MAX)</code>
     *       returns <em>false</em> because of limited precision in floating
     *       point numbers.</li>
     *   <li>A string is trimmed first. If it's empty, NULL is returned.
     *       Otherwise, if it takes the is_numeric() test (Numeric strings
     *       consist of optional sign, any number of digits, optional decimal
     *       part and optional exponential part. Thus +0123.45e6 is a valid
     *       numeric value. Hexadecimal notation (0xFF) is allowed too but only
     *       without sign, decimal and exponential part.), the value is
     *       converted to a number. Otherwise, it throws a
     *       {@link ConverterException} exception. "If the string does not
     *       contain any of the characters '.', 'e', or 'E' and the numeric
     *       value fits into integer type limits (as defined by PHP_INT_MAX),
     *       the string will be evaluated as an integer. In all other cases it
     *       will be evaluated as a float." When the number is evaluated as a
     *       float, the float conversion rule applies.</li>
     *   <li>An array throws a {@link ConverterException} exception.</li>
     *   <li>An object is first casted to string if it defines a __toString()
     *       method; then, the string conversion rule applies. If this method
     *       is missing, a {@link ConverterException} exception is thrown.</li>
     *   <li>A resource throws a {@link ConverterException} exception.</li>
     * </ul>
     *
     * @param mixed $value The value to convert
     * @return integer|NULL The converted value
     * @throws ConverterException when the conversion fails.
     */
    public function getAsDomainType($value)
    {
        if ($value === null || is_int($value)) {
            return $value;
        }

        if (is_bool($value)) {
             // Do an explicit conversion, though a simple cast should suffice
            return $value ? 1 : 0;
        }

        if (is_array($value) || is_resource($value)) {
            throw new ConverterException(
                self::$messageId[self::ARRAY_OR_RESOURCE],
                self::ARRAY_OR_RESOURCE
            );
        }

        if (is_object($value)) {
            if (!method_exists($value, '__toString')) {
                throw new ConverterException(
                    self::$messageId[self::OBJECT_TO_STRING],
                    self::OBJECT_TO_STRING
                );
            }
            $value = $value->__toString();
        }

        if (is_string($value)) {
            $value = trim($value);
            if (empty($value)) {
                return null;
            }

            if (!is_numeric($value)) {
                throw new ConverterException(
                    self::$messageId[self::NOT_NUMERIC],
                    self::NOT_NUMERIC
                );
            }
            // According to PHP documentation
            // (http://www.php.net/manual/en/language.types.string.html#language.types.string.conversion):
            // "When a string is evaluated in a numeric context, the resulting
            // value and type are determined as follows. If the string does not
            // contain any of the characters '.', 'e', or 'E' and the numeric
            // value fits into integer type limits (as defined by PHP_INT_MAX),
            // the string will be evaluated as an integer. In all other cases
            // it will be evaluated as a float."
            // Hence we convert the string into a number by adding zero.
            $value = 0 + $value;
            if ($value === INF) {
                throw new ConverterException(
                    self::$messageId[self::INFINITE],
                    self::INFINITE
                );
            }
        }

        if (is_float($value)) {
            if ($value > PHP_INT_MAX) {
                throw new ConverterException(
                    self::$messageId[self::OVERFLOW],
                    self::OVERFLOW
                );
            }
            return round($value, 0, PHP_ROUND_HALF_UP);
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param integer $value Value of domain type.
     * @return string Value converted to string
     * @throws ConverterException when the conversion fails.
     */
    public function getAsString($value)
    {
        return (string) $value;
    }
}