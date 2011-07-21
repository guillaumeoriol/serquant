<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Entity;

use Doctrine\Common\Util\Inflector,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\ClassMetadataFactory,
    Serquant\Entity\Exception\RuntimeException,
    Serquant\Entity\Registry\Registrable;

/**
 * Serializes an entity or a collection of entities into several formats.
 *
 * This class depends on two components: a metadata factory to get entity
 * metadata from and a registry to get entity values (in case of associations).
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Serializer
{
    /**
     * Metadata factory used to retrieve fields and associations from entities.
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * Entity registry used to retrieve entities referenced by associations.
     * @var Registrable
     */
    private $entityRegistry;

    /**
     * Constructor
     *
     * @param ClassMetadataFactory $metadataFactory Metadata factory
     * @param Registrable $entityRegistry Registry of loaded entities
     */
    public function __construct(
        ClassMetadataFactory $metadataFactory,
        Registrable $entityRegistry
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->entityRegistry = $entityRegistry;
    }

    /**
     * Converts an entity or an entity collection into an array.
     *
     * @param mixed $entity Data to convert
     * @return array Converted data
     * @throws RuntimeException If a composite identifier is used for an
     * association.
     */
    private function convertToArray($entity)
    {
        $data = array();
        if (is_array($entity)
            || (is_object($entity) && ($entity instanceof \Traversable))
        ) {
            foreach ($entity as $item) {
                $data[] = $this->convertToArray($item);
            }
        } else {
            $className = get_class($entity);
            $class = $this->metadataFactory->getMetadataFor($className);
            foreach ($class->fieldMappings as $field => $mapping) {
                $value = $class->reflFields[$field]->getValue($entity);
                if ($value instanceof \DateTime) {
                    $data[$field] = $value->format(\DateTime::ATOM);
                } else if (is_object($value)) {
                    $data[$field] = (string)$value;
                } else {
                    $data[$field] = $value;
                }
            }
            foreach ($class->associationMappings as $field => $mapping) {
                $value = $class->reflFields[$field]->getValue($entity);
                if ($value === null) {
                    $data[$field] = null;
                } else if ($mapping['isCascadeDetach']) {
                    $data[$field] = $this->convertToArray($value);
                } else if ($mapping['isOwningSide']
                    && $mapping['type'] & ClassMetadata::TO_ONE
                ) {
                    // If it's not detached to but there is an owning side
                    // to one entity at least, reflect the identifier.
                    $id = $this->entityRegistry->getEntityIdentifier($value);
                    if (count($id) > 1) {
                        throw new RuntimeException(
                            'Referencing may only work with scalar identifiers '
                            . var_dump($id, true)
                        );
                    }
                    $data[$field] = array('$ref' => current($id));
                }
            }
        }
        return $data;
    }

    /**
     * Serializes the given entity(ies) into an array.
     *
     * @param mixed $entity Data to serialize into array
     * @return array Array-serialized data
     */
    public function toArray($entity)
    {
        return $this->convertToArray($entity);
    }

    /**
     * Serializes the given entity(ies) into JSON.
     *
     * @param $mixed $entity Data to serialize into JSON
     * @return string JSON-serialized data
     */
    public function toJson($entity)
    {
        return json_encode($this->toArray($entity));
    }

    /**
     * Converts an entity or a collection of entities into a DOM document.
     *
     * @param DOMNode $node DOM Node to attach to
     * @param mixed $entity Data to convert
     * @return void
     * @throws RuntimeException If a composite identifier is used for an
     * association.
     */
    private function convertToDom($node, $entity)
    {
        $doc = $node->ownerDocument ? $node->ownerDocument : $node;

        if (is_array($entity)
            || (is_object($entity) && ($entity instanceof \Traversable))
        ) {
            $child = $doc->createElement('collection');
            $node->appendChild($child);
            foreach ($entity as $item) {
                $this->convertToDom($child, $item);
            }
        } else {
            $className = get_class($entity);
            $class = $this->metadataFactory->getMetadataFor($className);
            $child = $doc->createElement(
                Inflector::tableize($class->reflClass->getShortName())
            );
            $node->appendChild($child);
            foreach ($class->fieldMappings as $field => $mapping) {
                $value = $class->reflFields[$field]->getValue($entity);
                $property = $doc->createElement($field);
                $child->appendChild($property);
                if ($value instanceof \DateTime) {
                    $v = $value->format(\DateTime::ATOM);
                } else if (is_object($value)) {
                    $v = (string)$value;
                } else {
                    $v = $value;
                }
                $property->appendChild($doc->createTextNode($v));
            }
            foreach ($class->associationMappings as $field => $mapping) {
                $value = $class->reflFields[$field]->getValue($entity);
                $association = $doc->createElement($field);
                $child->appendChild($association);
                if ($value === null) {
                    continue;
                } else if ($mapping['isCascadeDetach']) {
                    $this->convertToDom($association, $value);
                } else if ($mapping['isOwningSide']
                    && $mapping['type'] & ClassMetadata::TO_ONE
                ) {
                    // If it's not detached to but there is an owning side
                    // to one entity, at least reflect the identifier.
                    $id = $this->entityRegistry->getEntityIdentifier($value);
                    if (count($id) > 1) {
                        throw new RuntimeException(
                            'Referencing may only work with scalar identifiers '
                            . var_dump($id, true)
                        );
                    }
                    $association->appendChild($doc->createTextNode(current($id)));
                }
            }
        }
    }

    /**
     * Transforms the given entity(ies) into a DOM Document.
     *
     * @param mixed $entity Data to transform into a DOM Document
     * @return \DOMDocument DOM document
     */
    public function toDOMDocument($entity)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $this->convertToDom($dom, $entity);
        return $dom;
    }

    /**
     * Serializes the given entity(ies) into XML.
     *
     * @param mixed $entity Data to serialize to XML
     * @param bool $formatOutput Nicely formats output with indentation and
     * extra space when TRUE.
     * @return string XML-serialized data
     */
    public function toXml($entity, $formatOutput = false)
    {
        $dom = $this->toDOMDocument($entity);
        $dom->formatOutput = $formatOutput;
        return $dom->saveXML();
    }
}
