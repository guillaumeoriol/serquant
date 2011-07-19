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

use Doctrine\ORM\UnitOfWork,
    Serquant\Entity\Registry\Registrable;

/**
 * Implementation of the Registrable interface for Doctrine ORM.
 *
 * This is a simple wrapper around a UnitOfWork object to fulfill
 * the {@link Registrable} interface requirements.
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class DoctrineGateway implements Registrable
{
    /**
     * Identity Map container
     * @var UnitOfWork
     */
    private $uow;

    /**
     * Constructor
     *
     * @param UnitOfWork $uow Doctrine Unit Of Work
     */
    public function __construct(UnitOfWork $uow)
    {
        $this->uow = $uow;
    }

    /**
     * Gets the identifier of an entity.
     *
     * The returned value is always an array of identifier values. If the
     * entity has a composite identifier then the identifier values are
     * in the same order as the identifier field names as returned by
     * ClassMetadata#getIdentifierFieldNames().
     *
     * @param object $entity Entity to get identifier from
     * @return array The identifier value
     */
    public function getEntityIdentifier($entity)
    {
        return $this->uow->getEntityIdentifier($entity);
    }
}