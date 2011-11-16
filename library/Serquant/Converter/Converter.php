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
abstract class Converter
{
    /**#@+
     * Canonical name of the convertible type
     * @var string
     */
    const BOOLEAN = 'boolean';
    const DATETIME = 'DateTime';
    const FLOAT = 'float';
    const INTEGER = 'integer';
    const STRING = 'string';
    /**#@-*/

    /**
     * Map of supported converters.
     * @var array
     */
    public static $converterMap = array(
        self::BOOLEAN => 'Serquant\Converter\BooleanConverter',
        self::DATETIME => 'Serquant\Converter\DateTimeConverter',
        self::FLOAT => 'Serquant\Converter\FloatConverter',
        self::INTEGER => 'Serquant\Converter\IntegerConverter',
        self::STRING => 'Serquant\Converter\StringConverter'
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
     * @param string $name The canonical name of the type.
     * @return Converter
     * @throws InvalidArgumentException when an invalid type is given.
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
     * Converts the input value to the internal type of the domain.
     *
     * The value to convert may be a string or may already be of domain type
     * (or even of another type) as a conversion may have occurred earlier
     * in the controller.
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
     * Converts the input value (of domain type) to string.
     *
     * The method returns null if the value to convert is null.
     *
     * @param mixed $value The domain type value to convert
     * @return string The converted value
     */
    abstract public function getAsString($value);
}