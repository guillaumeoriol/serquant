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
 * Converter for the 'boolean' mapping type.
 *
 * The corresponding domain type is the PHP primitive type 'boolean'.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class BooleanConverter extends Converter
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
        self::OBJECT_TO_STRING => 'com.serquant.converter.boolean.OBJECT_TO_STRING',
        self::ARRAY_OR_RESOURCE => 'com.serquant.converter.boolean.ARRAY_OR_RESOURCE'
    );

    /**
     * {@inheritDoc}
     *
     * Conversion rules:
     * <ul>
     *   <li>NULL is returned unchanged.</li>
     *   <li>A boolean is returned unchanged.</li>
     *   <li>The integer value 0 is converted to FALSE; non-zero value is
     *       converted to TRUE.</li>
     *   <li>The float value 0.0 is converted to FALSE; non-zero value is
     *       converted to TRUE.</li>
     *   <li>An empty string and the string "0" are converted to FALSE; other
     *       string values are converted to TRUE.</li>
     *   <li>An array throws a {@link ConverterException} exception.</li>
     *   <li>An object is first casted to string if it defines a __toString()
     *       method; then, the string conversion rule applies.</li>
     *   <li>A resource throws a {@link ConverterException} exception.</li>
     * </ul>
     *
     * @param mixed $value The value to convert
     * @return boolean|NULL The converted value
     * @throws ConverterException when the conversion fails.
     */
    public function getAsDomainType($value)
    {
        if ($value === null || is_bool($value)) {
            return $value;
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
        }

        return (boolean) $value;
    }

    /**
     * {@inheritDoc}
     *
     * Unlike normal PHP boolean-to-string conversion, TRUE is converted to '1'
     * and FALSE is converted to '0' (and not to an empty string).
     *
     * @param boolean $value Value of domain type.
     * @return string Value converted to string
     * @throws ConverterException when the conversion fails.
     */
    public function getAsString($value)
    {
        return $value ? '1' : '0';
    }
}