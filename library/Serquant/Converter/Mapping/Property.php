<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Converter\Mapping;

use Serquant\Converter\Converter;
use Serquant\Converter\Exception\DomainException;

/**
 * 'Property' metadata definition.
 *
 * This class is the definition of the &#64;Property annotation used for
 * conversion purpose (as required by Doctrine annotations package).
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 * @Annotation
 */
final class Property
{
    /**
     * Allowed type aliases
     * @var array
     */
    private static $typeAliases = array(
        'int' => 'integer',
        'bool' => 'boolean',
        'double' => 'float'
    );

    /**
     * Allowed values of the $multiplicity attribute.
     * @var array
     */
    public static $allowedMultiplicities = array('1', '*');

    /**
     * PHP type or class of the property used for conversion purpose.
     *
     * Defaults to 'string'
     * @var string
     */
    private $type = 'string';

    /**
     * Multiplicity of the property.
     *
     * The multiplicity is used to determine the type of variable to be created
     * in the conversion process: for single-valued properties an object of the
     * specified type will be created; for multivalued properties, an object of
     * class Doctrine\Common\Collections\ArrayCollection will be created having
     * items of the specified type.
     *
     * Allowed values: '1', '*'
     * Defaults to '1'
     * @var string
     */
    private $multiplicity = '1';

    /**
     * Constructs a Property instance.
     *
     * @param array $data Key/value for properties to be defined in this class
     */
    public function __construct(array $data)
    {
        if (array_key_exists('type', $data)) {
            $this->setType($data['type']);
        }

        if (array_key_exists('multiplicity', $data)) {
            $this->setMultiplicity($data['multiplicity']);
        }
    }

    /**
     * Determines if the property is convertible.
     *
     * Only properties of some PHP primitive types and properties that are Value
     * objects (like PHP DateTime) are directly convertible. The others are
     * reference objects that should be decomposed.
     *
     * @return boolean
     */
    public function isConvertible()
    {
        return array_key_exists($this->type, Converter::$converterMap);
    }

    /**
     * Sets property's domain model type.
     *
     * When an alias (or abbreviation) is used, it is automatically translated
     * into the canonical type name.
     *
     * @param string $type Domain model type
     * @return void
     */
    public function setType($type)
    {
        if (array_key_exists($type, self::$typeAliases)) {
            $type = self::$typeAliases[$type];
        }
        $this->type = $type;
    }

    /**
     * Gets property's domain model type.
     *
     * The returned type may be different from the one specified in metadata
     * as some abbreviations are allowed (ie: int for integer, bool for boolean,
     * etc).
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the multiplicity of the property.
     *
     * @param string $multiplicity Value of the multiplicity
     * @return void
     * @throws DomainException if the value does not belong to the allowed values.
     */
    public function setMultiplicity($multiplicity)
    {
        if (!in_array($multiplicity, self::$allowedMultiplicities)) {
            throw new DomainException(
                "Invalid multiplicity defined for property: '$multiplicity'"
                . '. Allowed values are '
                . print_r(self::$allowedMultiplicities, true)
            );
        }
        $this->multiplicity = $multiplicity;
    }

    /**
     * Gets the multiplicity of the property.
     *
     * @return string
     */
    public function getMultiplicity()
    {
        return $this->multiplicity;
    }

    /**
     * Determines if the property is multivalued or single-valued, in case of
     * associations.
     *
     * A single-valued property will result in an instance of {@link $type}
     * class. A multivalued property will result in a collection of objects
     * of {@link $type} class.
     *
     * @return boolean
     */
    public function isMultivalued()
    {
        return $this->multiplicity === '*';
    }
}