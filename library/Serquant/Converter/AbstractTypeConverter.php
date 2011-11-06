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
use Serquant\Converter\Exception\InvalidArgumentException;

/**
 * Base class for all type converters.
 *
 * This class is inspired by the interface of the javax.faces.convert package
 * and by the Doctrine\DBAL\Types\Type class.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
abstract class AbstractTypeConverter
{
    const TARRAY = 'array';
    const BIGINT = 'bigint';
    const BOOLEAN = 'boolean';
    const DATE = 'date';
    const DATETIME = 'datetime';
    const DATETIMETZ = 'datetimetz';
    const DECIMAL = 'decimal';
    const FLOAT = 'float';
    const INTEGER = 'integer';
    const OBJECT = 'object';
    const SMALLINT = 'smallint';
    const STRING = 'string';
    const TEXT = 'text';
    const TIME = 'time';

    /**
     * Map of supported converters.
     * @var array
     */
    private static $converterMap = array(
        self::TARRAY => 'Serquant\Converter\ArrayConverter',
        self::BIGINT => 'Serquant\Converter\BigIntConverter',
        self::BOOLEAN => 'Serquant\Converter\BooleanConverter',
        self::DATE => 'Serquant\Converter\DateConverter',
        self::DATETIME => 'Serquant\Converter\DateTimeConverter',
        self::DATETIMETZ => 'Serquant\Converter\DateTimeTzConverter',
        self::DECIMAL => 'Serquant\Converter\DecimalConverter',
        self::FLOAT => 'Serquant\Converter\FloatConverter',
        self::INTEGER => 'Serquant\Converter\IntegerConverter',
        self::OBJECT => 'Serquant\Converter\ObjectConverter',
        self::SMALLINT => 'Serquant\Converter\SmallIntConverter',
        self::STRING => 'Serquant\Converter\StringConverter',
        self::TEXT => 'Serquant\Converter\TextConverter',
        self::TIME => 'Serquant\Converter\TimeConverter',
    );

    /**
     * Map of already instantiated converter objects.
     * One instance per type (flyweight).
     * @var array
     */
    private static $converterObjects = array();

    /**
     * This private constructor prevents instantiation and forces use of the
     * factory method.
     */
    final private function __construct()
    {
    }

    /**
     * Factory method to create converter instances.
     *
     * Converter instances are implemented as flyweights.
     *
     * @param string $name The name of the mapping type.
     * @return Serquant\Converter\Converter
     * @throws InvalidArgumentException when an invalid mapping type is given.
     */
    public static function getConverter($name)
    {
        if (!isset(self::$converterObjects[$name])) {
            if (!isset(self::$converterMap[$name])) {
                throw new InvalidArgumentException(
                    'Unknown converter type: ' . $name
                );
            }
            self::$converterObjects[$name] = new self::$converterMap[$name]();
        }

        return self::$converterObjects[$name];
    }

    /**
     * Convert the input value to the internal type of the domain.
     *
     * The returned value is not of the type specified in {@link getConverter}
     * (called a mapping type). Instead, the returned value is of domain type.
     * To determine the domain type, we use the mapping type specified by the
     * entity metadata (and passed to getConverter()) and find the corresponding
     * converter with an internal map.
     * The value to convert may be a string or may already be of domain type
     * (or even of another type) as a conversion may have been done earlier by
     * the controller.
     * In this class, we mimic the interface of javax.faces.convert package.
     * In Java, this method is called <code>getAsObject</code>. We call it
     * <code>getAsDomainType</code> as primitive types are not objects in PHP.
     *
     * The method returns null if the value to convert is null.
     *
     * @param mixed $value The value to convert
     * @return mixed The converted value
     * @throws ConverterException when the conversion fails.
     */
    abstract public function getAsDomainType($value);

    /**
     * Convert the input value (of domain type) to string.
     *
     * @param mixed $value The domain type value to convert
     * @return string The converted value
     * @throws ConverterException when the conversion fails.
     * @todo Do we need such a method as this conversion is done by the
     * Serquant\Entity\Serializer for the moment? Or do we need to change
     * the entity serializer to use these methods? The second option seems
     * preferable.
     */
    abstract public function getAsString($value);
}