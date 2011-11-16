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

use Serquant\Converter\Exception\ConverterException;

/**
 * Converter for the 'float' PHP type.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class FloatConverter extends Converter
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
        self::OBJECT_TO_STRING => 'com.serquant.converter.float.OBJECT_TO_STRING',
        self::ARRAY_OR_RESOURCE => 'com.serquant.converter.float.ARRAY_OR_RESOURCE',
        self::NOT_NUMERIC => 'com.serquant.converter.float.NOT_NUMERIC',
        self::INFINITE => 'com.serquant.converter.float.INFINITE'
    );

    /**
     * {@inheritDoc}
     *
     * Conversion rules:
     * <ul>
     *   <li>NULL is returned unchanged.</li>
     *   <li>The boolean TRUE is converted to the floating-point value 1 and
     *       the boolean FALSE is converted to the floating-point value 0.</li>
     *   <li>An integer is returned casted to float.</li>
     *   <li>A float number is returned unchanged.</li>
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
     *       will be evaluated as a float." When the number is evaluated as an
     *       integer, the integer conversion rule applies.</li>
     *   <li>An array throws a {@link ConverterException} exception.</li>
     *   <li>An object is first casted to string if it defines a __toString()
     *       method; then, the string conversion rule applies. If this method
     *       is missing, a {@link ConverterException} exception is thrown.</li>
     *   <li>A resource throws a {@link ConverterException} exception.</li>
     * </ul>
     *
     * @param mixed $value The value to convert
     * @return float The converted value
     * @throws ConverterException when the conversion fails.
     */
    public function getAsDomainType($value)
    {
        if ($value === null || is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return (float) $value;
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
            // Don't use empty() as empty('0') returns true
            if (strlen($value) === 0) {
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

        if (is_int($value)) {
            $value = (float) $value;
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param float $value The domain type value to convert
     * @return string Value converted to string
     */
    public function getAsString($value)
    {
        if ($value === null) {
            return null;
        }
        return (string) $value;
    }
}