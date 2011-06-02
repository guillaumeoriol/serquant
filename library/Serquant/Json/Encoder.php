<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Json
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Json;

/**
 * JSON encoder that permits to encode non-accessible properties of entities.
 *
 * Neither the PECL extension function json_encode(), neither Zend_Json or its
 * utility class (Zend_Json_Encoder) handle non-public properties. But domain
 * entities must declare their properties either protected or private to conform
 * with EJB 3 specification. The main purpose of this class is to handle domain
 * entities.
 *
 * @category Serquant
 * @package  Json
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Encoder extends \Zend_Json_Encoder
{
    /**
     * We need to override this function; otherwise, parent::encode() would
     * instantiate the parent class.
     *
     * {@inheritDoc}
     *
     * @param mixed $value The value to be encoded
     * @param boolean $cycleCheck Whether or not to check for possible object
     * recursion when encoding
     * @param array $options Additional options used during encoding
     * @return string The encoded value
     */
    public static function encode($value, $cycleCheck = false, $options = array())
    {
        $encoder = new self(($cycleCheck) ? true : false, $options);

        return $encoder->_encodeValue($value);
    }

    /**
     * Function overridden to encode iterators as arrays instead of objects
     * with the {@link encodeIterator()} method.
     *
     * {@inheritDoc}
     *
     * @param mixed &$value The value to be encoded
     * @return string The encoded value
     */
    protected function _encodeValue(&$value)
    {
        if (is_object($value)) {
            if ($value instanceof \Iterator) {
                return $this->encodeIterator($value);
            } else {
                return $this->_encodeObject($value);
            }
        } else if (is_array($value)) {
            return $this->_encodeArray($value);
        }

        return $this->_encodeDatum($value);
    }

    /**
     * {@inheritDoc}
     *
     * @param object &$value The object to be encoded
     * @return string The encoded object
     * @throws Zend_Json_Exception If recursive checks are enabled
     * and the object has been serialized previously.
     */
    protected function _encodeObject(&$value)
    {
        if ($this->_cycleCheck) {
            if ($this->_wasVisited($value)) {

                if (isset($this->_options['silenceCyclicalExceptions'])
                    && $this->_options['silenceCyclicalExceptions'] === true
                ) {
                    return '"* RECURSION (' . get_class($value) . ') *"';
                } else {
                    throw new \Zend_Json_Exception(
                        'Cycles not supported in JSON encoding, cycle ' .
                        'introduced by class "' . get_class($value) . '"'
                    );
                }
            }

            $this->_visited[] = $value;
        }


        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d\TH:i:s');
        }

        if ($value instanceof \Serquant\Entity\Formattable) {
            $propCollection = $this->normalizeEntity($value);
        } else {
            $propCollection = get_object_vars($value);
        }

        $first = true;
        $props = '{';
        foreach ($propCollection as $name => $propValue) {
            if ($first) {
                $first = false;
            } else {
                $props .= ',';
            }
            $props .= $this->_encodeString($name)
                    . ':'
                    . $this->_encodeValue($propValue);
        }
        $props .= '}';

        return $props;
    }

    /**
     * Encodes iterators as arrays instead of objects.
     *
     * This function assume iterators are indexed arrays.
     *
     * @param \Iterator &$iterator The iterator to encode
     * @throws \RuntimeException If an element of the array has a non-integer
     * key.
     * @return string The encoded iterator
     */
    protected function encodeIterator(&$iterator)
    {
        $tmpArray = array();
        $result = '[';
        foreach ($iterator as $key => $value) {
            if (!is_int($key)) {
                // It is an associative array, actually.
                throw new \RuntimeException(
                    "A non-integer key was found ($key) while encoding an " .
                    'iterator to JSON.'
                );
            }
            $tmpArray[] = $this->_encodeValue($value);
        }
        $result .= implode(',', $tmpArray);
        $result .= ']';

        return $result;
    }

    /**
     * Normalize the given entity.
     *
     * @param Object $entity The entity to normalize
     * @return array The normalized entity
     */
    protected function normalizeEntity($entity)
    {

        $class = new \ReflectionClass(get_class($entity));
        $properties = $class->getProperties();
        $parent = $class;
        while (($parent = $parent->getParentClass()) !== false) {
            $properties = array_merge($properties, $parent->getProperties());
        }

        $normalized = array();
        foreach ($properties as $prop) {
            $comment = $prop->getDocComment();
            if (stripos($comment, '@column')) {
                // Keep persistent properties only
                $propName = $prop->getName();
                $getter = 'get' . ucfirst($propName);
                if ($class->hasMethod($getter)) {
                    $propValue = $entity->$getter();
                } else {
                    $prop->setAccessible(true);
                    $propValue = $prop->getValue($entity);
                }
                $normalized[$propName] = $propValue;
            }
        }
        return $normalized;
    }
}