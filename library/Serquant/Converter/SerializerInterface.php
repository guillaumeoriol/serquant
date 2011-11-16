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

use Serquant\Converter\Mapping\ClassMetadataFactory;

/**
 * Requirements a Serializer class must fulfill.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface SerializerInterface
{
    /**
     * Constructs an instance of this class.
     *
     * @param ClassMetadataFactory $metadataFactory
     */
    public function __construct(ClassMetadataFactory $metadataFactory);

    /**
     * Serializes an entity or an entity collection into an array.
     *
     * @param mixed $entity Entity to serialize
     * @return array Serialized data
     * @throws InvalidArgumentException If the argument is not an entity nor
     * a collection.
     */
    public function serialize($entity);

    /**
     * Serializes an entity into JSON.
     *
     * @param $mixed $entity Data to serialize into JSON
     * @return string JSON-serialized data
     */
    public function toJson($entity);

    /**
     * Serializes an entity or an entity collection into XML.
     *
     * @param mixed $entity Entity to serialize
     * @param bool $formatOutput Nicely formats output with indentation and
     * extra space when TRUE.
     * @return string XML-serialized data
     */
    public function toXml($entity, $formatOutput = false);

    /**
     * Deserializes input data to populate an entity
     *
     * @param object $entity Entity to populate
     * @param array|object $data Input data, in the form of field/value pairs.
     * @return ConstraintViolationList
     * @throws InvalidArgumentException if data is not an array nor an object.
     */
    public function deserialize($entity, $data);
}