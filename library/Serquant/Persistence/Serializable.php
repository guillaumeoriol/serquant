<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Persistence;

/**
 * Promises a persister must fulfill to permit its consumer (usually the service
 * layer) to instantiate a serializer object.
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface Serializable
{
    /**
     * Get the persister's metadata factory.
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadataFactory Metadata factory
     */
    public function getMetadataFactory();

    /**
     * Get the persister's registry of loaded entities.
     *
     * @return \Serquant\Entity\Registry\Registrable Entity registry
     */
    public function getEntityRegistry();
}