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
 * Converter for the 'text' mapping type.
 *
 * The corresponding domain type is the PHP primitive type 'string'.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class TextConverter extends Converter
{
    /**#@+
     * Code of the {@link ConverterException} thrown in case of conversion
     * failure.
     * @var int
     */
    const OBJECT_TO_STRING = 1;
    const ARRAY_OR_RESOURCE = 2;
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
        self::OBJECT_TO_STRING => 'com.serquant.converter.text.OBJECT_TO_STRING',
        self::ARRAY_OR_RESOURCE => 'com.serquant.converter.text.ARRAY_OR_RESOURCE'
    );

    /**
     * {@inheritDoc}
     *
     * Conversion rules:
     * <ul>
     *   <li>NULL is returned unchanged.</li>
     *   <li>Unlike normal PHP's boolean-to-string conversion, boolean TRUE is
     *       converted to '1' and boolean FALSE is converted to '0'.</li>
     *   <li>According to
     *       {@link http://www.php.net/manual/en/language.types.string.php PHP
     *       documentation}, an integer is converted to a string
     *       representing the number textually.</li>
     *   <li>According to
     *       {@link http://www.php.net/manual/en/language.types.string.php PHP
     *       documentation}, a float is converted to a string representing
     *       the number textually (including the exponent part for floats).
     *       Floating point numbers can be converted using exponential notation
     *       (4.1E+6).</li>
     *   <li>An empty string or a string consisting of spaces only returns NULL.
     *       Otherwise it is returned unchanged.</li>
     *   <li>An array throws a {@link ConverterException} exception.</li>
     *   <li>An object is casted to string if it defines a __toString() method;
     *       otherwise, it throws a {@link ConverterException} exception. Then
     *       the string rule applies.</li>
     *   <li>A resource throws a {@link ConverterException} exception.</li>
     * </ul>
     *
     * <em>Leading and trailing spaces are preserved.</em>
     *
     * @param mixed $value The value to convert
     * @return string|NULL The converted value
     * @throws ConverterException when the conversion fails.
     */
    public function getAsDomainType($value)
    {
        if ($value === null) {
            return $value;
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
            if (strlen(trim($value)) === 0) {
                return null;
            }
            return $value;
        }

        if (is_array($value) || is_resource($value)) {
            throw new ConverterException(
                self::$messageId[self::ARRAY_OR_RESOURCE],
                self::ARRAY_OR_RESOURCE
            );
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // May either be an int or a float
        return (string) $value;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $value The domain value.
     * @return string Value converted to string
     * @throws ConverterException when the conversion fails.
     */
    public function getAsString($value)
    {
        return $value;
    }
}