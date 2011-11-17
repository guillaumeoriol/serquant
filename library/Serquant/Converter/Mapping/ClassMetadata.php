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

use ReflectionClass;
use ReflectionProperty;
use Serquant\Converter\Exception\OutOfBoundsException;
use Serquant\Converter\Mapping\Property;

/**
 * Class metadata for type conversion between (external) client and (internal)
 * domain model.
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ClassMetadata implements ClassMetadataInterface
{
    /**
     * Fully qualified class name
     * @var string
     */
    private $name;

    /**
     * ReflectionClass instance
     * @var ReflectionClass
     */
    private $reflClass;

    /**
     * Array of ReflectionProperty objects
     * @var array
     */
    private $reflProperties;

    /**
     * List of properties that make up the identifier
     * @var array
     */
    private $identifier;

    /**
     * Prefix that must be added to the identifier or removed from the
     * identifier during serialization/deserialization.
     * @var string
     */
    private $identifierPrefix;

    /**
     * Map associating property names to conversion metadata
     * @var array
     */
    private $conversionProperties;

    /**
     * Constructs a metadata for the given class
     *
     * @param string $class The fully qualified class name without leading
     * namespace separator
     */
    public function __construct($class)
    {
        $this->name = $class;
        $this->reflProperties = array();
        $this->identifier = array();
        $this->conversionProperties = array();
    }

    /**
     * Returns the properties of this class to be serialized
     *
     * @return array
     */
    public function __sleep()
    {
        return array(
            'name',
            'identifier',
            'identifierPrefix',
            'conversionProperties'
        );
    }

    /**
     * Restores the properties of this class that were not serialized
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->reflClass = new ReflectionClass($this->getClassName());
        $className = $this->reflClass->getName();

        foreach ($this->reflClass->getProperties() as $reflProp) {
            if ($reflProp->getDeclaringClass()->getName() === $className) {
                $name = $reflProp->getName();
                if (isset($this->conversionProperties[$name])) {
                    $this->addReflectionProperty($name, $reflProp);
                }
            }
        }
    }

    /**
     * Returns the fully qualified name of the introspected class
     *
     * @return string The fully qualified class name
     */
    public function getClassName()
    {
        return $this->name;
    }

    /**
     * Returns a ReflectionClass instance of the introspected class.
     *
     * @return ReflectionClass
     */
    public function getReflectionClass()
    {
        if (!$this->reflClass) {
            $this->reflClass = new ReflectionClass($this->getClassName());
        }

        return $this->reflClass;
    }

    /**
     * Adds a reflection property to the class metadata.
     *
     * @param string $name Property name
     * @param ReflectionProperty $reflProp Reflection property
     * @return void
     */
    public function addReflectionProperty($name, ReflectionProperty $reflProp)
    {
        $reflProp->setAccessible(true);
        $this->reflProperties[$name] = $reflProp;
    }

    /**
     * Gets a reflection property
     *
     * @param string $name Reflection property name
     * @return ReflectionProperty
     */
    public function getReflectionProperty($name)
    {
        return $this->reflProperties[$name];
    }

    /**
     * Defines a property as being the entity identifier (or part of it in case
     * of composite identifier).
     *
     * @param string $name Property name
     * @return void
     */
    public function setIdentifier($name)
    {
        if ($this->isIdentifier($name)) {
            throw new RuntimeException($message, $code, $previous);
        }
        $this->identifier[] = $name;
    }

    /**
     * Determines if a property name is the entity identifier (or part of it).
     *
     * @param string $name Property name
     * @return boolean
     */
    public function isIdentifier($name)
    {
        return in_array($name, $this->identifier);
    }

    /**
     * Sets the identifier prefix of the class
     *
     * @param string $prefix Identifier prefix
     * @return void
     */
    public function setIdentifierPrefix($prefix)
    {
        $this->identifierPrefix = $prefix;
    }

    /**
     * Gets the identifier prefix of the class
     *
     * @return string
     */
    public function getIdentifierPrefix()
    {
        return $this->identifierPrefix;
    }

    /**
     * Adds a conversion property to the class metadata
     *
     * @param string $name Property name
     * @param Property $property Conversion property
     * @return void
     */
    public function addProperty($name, Property $property)
    {
        $this->conversionProperties[$name] = $property;
    }

    /**
     * Gets a conversion property
     *
     * @param string $name Property name
     * @return Property
     * @throws OutOfBoundsException if the given name is not part of the
     * conversion properties.
     */
    public function getProperty($name)
    {
        if (!isset($this->conversionProperties[$name])) {
            throw new OutOfBoundsException(
                "The name '$name' is not part of the conversion properties " .
                'of the class ' . $this->getClassName()
            );
        }
        return $this->conversionProperties[$name];
    }

    /**
     * Gets the whole list of conversion properties
     *
     * @return array Conversion properties
     */
    public function getProperties()
    {
        return $this->conversionProperties;
    }

    /**
     * Merges the conversion properties of the given metadata into this object.
     *
     * @param ClassMetadata $source The source metadata
     * @return void
     */
    public function mergeProperties(ClassMetadata $source)
    {
        foreach ($source->getProperties() as $name => $property) {
            $this->addProperty($name, clone $property);
        }
    }
}