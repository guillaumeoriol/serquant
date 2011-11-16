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

use DOMDocument;
use DOMNode;
use Traversable;
use Serquant\Converter\Exception\ConverterException;
use Serquant\Converter\Exception\InvalidArgumentException;
use Serquant\Converter\Exception\RuntimeException;
use Serquant\Converter\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Service to serialize/deserialize entities with type conversion.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Serializer implements SerializerInterface
{

    public function __construct(ClassMetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Serializes an entity or an entity collection into an array.
     *
     * @param mixed $entity Entity to serialize
     * @return array Serialized data
     * @throws InvalidArgumentException If the argument is not an entity nor
     * a collection.
     */
    public function serialize($entity)
    {
        $data = array();
        if (is_array($entity)
            || (is_object($entity) && ($entity instanceof Traversable))
        ) {
            foreach ($entity as $item) {
                $data[] = $this->serialize($item);
            }
        } else {
            if (!is_object($entity)) {
                throw new InvalidArgumentException(
                    'Only entities or entity collections may be serialized. ' .
                    'A ' . gettype($entity) . ' was given.'
                );
            }
            $metadata = $this->metadataFactory->getClassMetadata(get_class($entity));
            foreach ($metadata->getProperties() as $name => $property) {
                $type = $property->getType();
                $method = 'get' . ucfirst($name);
                if (method_exists($entity, $method)) {
                    $value = call_user_func(array($entity, $method));
                } else {
                    $value = $metadata->getReflectionProperty($name)->getValue($entity);
                }
                if ($property->isConvertible()) {
                    // No need to enclose conversion in a try/catch block here
                    // as exceptions are not thrown in normal processing
                    $converter = Converter::getConverter($type);
                    $data[$name] = $converter->getAsString($value);
                } else {
                    $data[$name] = $value === null ? null : $this->serialize($value);
                }
            }
        }
        return $data;
    }

    /**
     * Serializes an entity or an entity collection into JSON.
     *
     * @param $mixed $entity Entity to serialize
     * @return string JSON-serialized data
     */
    public function toJson($entity)
    {
        return json_encode($this->serialize($entity));
    }

    /**
     * Serializes an entity or an entity collection into a DOM document.
     *
     * @param DOMNode $node DOM Node to attach to
     * @param mixed $entity Entity to serialize
     * @return void
     * @throws InvalidArgumentException If the argument is not an entity nor
     * a collection.
     */
    public function toDom(DOMNode $node, $entity)
    {
        $doc = $node->ownerDocument ? $node->ownerDocument : $node;
        if (is_array($entity)
            || (is_object($entity) && ($entity instanceof Traversable))
        ) {
            foreach ($entity as $item) {
                $this->toDom($node, $item);
            }
        } else {
            if (!is_object($entity)) {
                throw new InvalidArgumentException(
                    'Only entities or entity collections may be serialized. ' .
                    'A ' . gettype($entity) . ' was given.'
                );
            }
            $metadata = $this->metadataFactory->getClassMetadata(get_class($entity));
            $name = $metadata->getReflectionClass()->getShortName();
            $entityNode = $doc->createElement($name);
            $node->appendChild($entityNode);
            foreach ($metadata->getProperties() as $name => $property) {
                $type = $property->getType();
                $method = 'get' . ucfirst($name);
                if (method_exists($entity, $method)) {
                    $value = call_user_func(array($entity, $method));
                } else {
                    $value = $metadata->getReflectionProperty($name)->getValue($entity);
                }
                if ($property->isConvertible()) {
                    $propertyNode = $doc->createElement($name);
                    $entityNode->appendChild($propertyNode);
                    // No need to enclose conversion in a try/catch block here
                    // as exceptions are not thrown in normal processing
                    $converter = Converter::getConverter($type);
                    $converted = $converter->getAsString($value);
                    if ($converted !== null) {
                        $propertyNode->appendChild($doc->createTextNode($converted));
                    }
                } else {
                    if ($property->isMultivalued()) {
                        $propertyNode = $doc->createElement($name);
                        $entityNode->appendChild($propertyNode);
                        $this->toDom($propertyNode, $value);
                    } else {
                        if ($value === null) {
                            $name = substr(strrchr($type, '\\'), 1);
                            $propertyNode = $doc->createElement($name);
                            $entityNode->appendChild($propertyNode);
                        } else {
                            $this->toDom($entityNode, $value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Serializes an entity or an entity collection into a DOM Document.
     *
     * @param mixed $entity Entity to serialize
     * @return DOMDocument DOM document
     */
    public function toDomDocument($entity)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $this->toDom($dom, $entity);
        return $dom;
    }

    /**
     * Serializes an entity or an entity collection into XML.
     *
     * @param mixed $entity Entity to serialize
     * @param bool $formatOutput Nicely formats output with indentation and
     * extra space when TRUE.
     * @return string XML-serialized data
     */
    public function toXml($entity, $formatOutput = false)
    {
        $dom = $this->toDomDocument($entity);
        $dom->formatOutput = $formatOutput;
        return $dom->saveXML();
    }

    /**
     * Deserializes input data to populate an entity
     *
     * @param object $entity Entity to populate
     * @param array|object $data Input data, in the form of field/value pairs.
     * @return ConstraintViolationList
     * @throws InvalidArgumentException if data is not an array nor an object.
     * @todo Should we consider collections as first-class objects having their
     * own metadata? For instance:
     * <pre>
     * class CarList implements Collection {
     *     /**
     *      * &#64;Converter\Property(type="Car", multiplicity="*")
     *      &#42;&#47;
     *     private $elements;
     * }
     * </pre>
     */
    public function deserialize($entity, $data)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new InvalidArgumentException(
            	'Entities may only be populated from arrays or objects.'
            );
        }

        $errors = new ConstraintViolationList();
        $metadata = $this->metadataFactory->getClassMetadata(get_class($entity));
        foreach ($metadata->getProperties() as $name => $property) {
            if (array_key_exists($name, $data)) {
                $type = $property->getType();
                $value = $data[$name];
                if ($property->isConvertible()) {
                    try {
                        $converter = Converter::getConverter($type);
                        $converted = $converter->getAsDomainType($value);
                    } catch (ConverterException $e) {
                        $errors->add(
                            new ConstraintViolation(
                                $e->getMessage(),
                                array('{type}' => $type, '{value}' => $value),
                                $entity,
                                $name,
                                $value
                            )
                        );
                        continue;
                    }
                } else {
                    if ($property->isMultivalued()) {
                        // Consider the property has been initialized with an
                        // object implementing the Collection interface, ie
                        // defining an add() method.
                        if (!empty($value)) {
                            if (!is_array($value) && !is_object($value)) {
                                throw new InvalidArgumentException(
                                	'Multivalued properties may only be ' .
                                    'populated from arrays or objects.'
                                );
                            }
                            // @todo Should we try first with a getting method?
                            $collection = $metadata->getReflectionProperty($name)->getValue($entity);
                            if ($collection === null) {
                                throw new RuntimeException(
                                    'Multivalue properties must be initialized ' .
                                    'with an object implementing the \Doctrine' .
                                    '\Common\Collections\Collection interface.'
                                );
                            }
                            foreach ($value as $item) {
                                $converted = new $type;
                                $errors->addAll($this->deserialize($converted, $item));
                                $collection->add($converted);
                            }
                        }
                        continue;
                    } else {
                        if (empty($value)) {
                            $converted = null;
                        } else {
                            $converted = new $type;
                            $errors->addAll($this->deserialize($converted, $value));
                        }
                    }
                }
                $method = 'set' . ucfirst($name);
                if (method_exists($entity, $method)) {
                    call_user_func(array($entity, $method), $converted);
                } else {
                    $metadata->getReflectionProperty($name)->setValue($entity, $converted);
                }
            }
        }

        return $errors;
    }
}