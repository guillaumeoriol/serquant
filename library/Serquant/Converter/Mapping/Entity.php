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

/**
 * 'Entity' metadata definition.
 *
 * This class is the definition of the &#64;Entity annotation used for
 * conversion purpose (as required by Doctrine Annotations package).
 *
 * @category Serquant
 * @package  Mapping
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 * @Annotation
 */
final class Entity
{
    /**
     * Prefix that must be added to the identifier or removed from the
     * identifier during serialization/deserialization.
     * @var string
     */
    private $prefix;

    /**
     * Constructs a Entity instance.
     *
     * @param array $data Key/value for properties to be defined in this class
     */
    public function __construct(array $data)
    {
        if (array_key_exists('prefix', $data)) {
            $this->setPrefix($data['prefix']);
        }
    }

    /**
     * Sets the identifier prefix of the entity.
     *
     * @param string $prefix Identifier prefix
     * @return void
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Gets the identifier prefix of the entity.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}