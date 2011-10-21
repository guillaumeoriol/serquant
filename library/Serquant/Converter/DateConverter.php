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
 * Converter for the 'date' mapping type.
 *
 * The corresponding domain type is the PHP class 'DateTime'.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class DateConverter extends Converter
{
    /**
     * Date format.
     *
     * Date format consisting of 4-digit year, '-' sign, 2-digit month with
     * leading zeros, '-' sign, 2-digit day of the month with leading zeros.
     * @var string
     */
    const FORMAT_DATE = 'Y-m-d';

    /**
     * Date and time format.
     *
     * Date format consisting of {@link FORMAT_DATE the date} plus 'T' sign,
     * 24-hour format of an hour with leading zeros, ':' sign, minutes with
     * leading zeros, ':' sign, seconds with leading zeros.
     * @var string
     */
    const FORMAT_DATE_TIME = 'Y-m-d\TH:i:s';

    /**
     * Date, time and timezone format.
     *
     * Date format consisting of {@link FORMAT_DATE_TIME the date and time}
     * plus '+' or '-' sign followed by the difference to UTC in hours and
     * minutes, colon between hours and minutes.
     * @var string
     */
    const FORMAT_DATE_TIME_TZ = 'Y-m-d\TH:i:sP';

    /**#@+
     * Code of the {@link ConverterException} thrown in case of conversion
     * failure.
     * @var int
     */
    const OBJECT_TO_STRING = 1;
    const PARSE_ERROR = 2;
    const PARSE_WARNING = 3;
    const INCONVERTIBLE = 4;
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
        self::OBJECT_TO_STRING => 'com.serquant.converter.date.OBJECT_TO_STRING',
        self::PARSE_ERROR => 'com.serquant.converter.date.PARSE_ERROR',
        self::PARSE_WARNING => 'com.serquant.converter.date.PARSE_WARNING',
        self::INCONVERTIBLE => 'com.serquant.converter.date.INCONVERTIBLE'
    );

    /**
     * {@inheritDoc}
     *
     * Conversion rules:
     * <ul>
     *   <li>NULL is returned unchanged.</li>
     *   <li>A boolean throws a {@link ConverterException} exception.</li>
     *   <li>An integer throws a {@link ConverterException} exception.</li>
     *   <li>A float throws a {@link ConverterException} exception.</li>
     *   <li>A string is trimmed first. If it's empty, NULL is returned.
     *       Otherwise it is converted to a DateTime object if the value
     *       conforms to one of the three allowed formats: {@link FORMAT_DATE},
     *       {@link FORMAT_DATE_TIME} or {@link FORMAT_DATE_TIME_TZ}. If the
     *       value doesn't conform to one of these formats, a
     *       {@link ConverterException} exception is thrown. If the time is
     *       missing, the '00:00:00' time is assumed. If the timezone is
     *       missing, the server timezone is assumed.</li>
     *   <li>An array throws a {@link ConverterException} exception.</li>
     *   <li>An object is casted to string if it defines a __toString() method;
     *       otherwise, it throws a {@link ConverterException} exception. Then
     *       the string rule applies.</li>
     *   <li>A resource throws a {@link ConverterException} exception.</li>
     * </ul>
     *
     * @param mixed $value The value to convert
     * @return DateTime|NULL The converted value
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
            $value = trim($value);
            if (empty($value)) {
                return null;
            }

            $length = strlen($value);
            if ($length === 10) {
                $value = $value . 'T00:00:00';
                $format = self::FORMAT_DATE_TIME;
            } elseif ($length === 19) {
                $format = self::FORMAT_DATE_TIME;
            } else {
                $format = self::FORMAT_DATE_TIME_TZ;
            }
            $result = \DateTime::createFromFormat($format, $value);
            if ($result === false) {
                throw new ConverterException(
                    self::$messageId[self::PARSE_ERROR],
                    self::PARSE_ERROR
                );
            }
            $errors = \DateTime::getLastErrors();
            if (count($errors['warnings']) > 0) {
                throw new ConverterException(
                    self::$messageId[self::PARSE_WARNING],
                    self::PARSE_WARNING
                );
            }
            return $result;
        }

        throw new ConverterException(
            self::$messageId[self::INCONVERTIBLE],
            self::INCONVERTIBLE
        );
    }

    /**
     * Get a string representation of a DateTime object.
     *
     * Date is returned in {@link FORMAT_DATE_TIME_TZ full date and time with
     * timezone format}.
     *
     * @param DateTime $value Value of domain type.
     * @return string Value converted to string
     * @throws ConverterException when the conversion fails.
     */
    public function getAsString($value)
    {
        return $value->format(self::FORMAT_DATE_TIME_TZ);
    }
}