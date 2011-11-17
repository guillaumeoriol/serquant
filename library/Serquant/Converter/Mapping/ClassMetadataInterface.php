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

use ReflectionProperty;
use Serquant\Converter\Mapping\Property;

/**
 * Requirements a ClassMetadata must fulfill.
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface ClassMetadataInterface
{
    /**
     * Returns a ReflectionClass instance of this class.
     *
     * @return ReflectionClass
     */
    public function getReflectionClass();

    /**
     * Adds a reflection property to the class metadata.
     *
     * @param string $name Property name
     * @param ReflectionProperty $reflProp Reflection property
     * @return void
     */
    public function addReflectionProperty($name, ReflectionProperty $reflProp);

    /**
     * Gets a reflection property
     *
     * @param string $name Reflection property name
     * @return ReflectionProperty
     */
    public function getReflectionProperty($name);

    /**
     * Defines a property as being the entity identifier (or part of it in case
     * of composite identifier).
     *
     * @param string $name Property name
     * @return void
     */
    public function setIdentifier($name);

    /**
     * Determines if a property name is the entity identifier (or part of it).
     *
     * @param string $name Property name
     * @return boolean
     */
    public function isIdentifier($name);

    /**
     * Sets the identifier prefix of the class
     *
     * @param string $prefix Identifier prefix
     * @return void
     */
    public function setIdentifierPrefix($prefix);

    /**
     * Gets the identifier prefix of the class
     *
     * @return string
     */
    public function getIdentifierPrefix();

    /**
     * Adds a conversion property to the class metadata
     *
     * @param string $name Property name
     * @param Property $property Conversion property
     * @return void
     */
    public function addProperty($name, Property $property);

    /**
     * Gets a conversion property
     *
     * @param string $name Property name
     * @return Property
     * @throws OutOfBoundsException if the given name is not part of the
     * conversion properties.
     */
    public function getProperty($name);

    /**
     * Gets the whole list of conversion properties
     *
     * @return array Conversion properties
     */
    public function getProperties();

    /**
     * Merges the conversion properties of the given metadata into this object.
     *
     * @param ClassMetadata $source The source metadata
     * @return void
     */
    public function mergeProperties(ClassMetadata $source);
}