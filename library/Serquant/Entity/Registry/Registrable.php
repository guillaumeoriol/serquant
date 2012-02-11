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
namespace Serquant\Entity\Registry;

/**
 * Promises an entity registry must fulfill to conform with serializer
 * requirements.
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface Registrable
{
    /**
     * Gets the identifier of an entity.
     *
     * The returned value is always an array of identifier values. If the
     * entity has a composite identifier then the identifier values are
     * in the same order as the identifier field names as returned by
     * ClassMetadata#getIdentifierFieldNames().
     *
     * @param object $entity Entity to get identifier value from
     * @return array The identifier value
     */
//    public function getEntityIdentifier($entity);
}