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
 * Promises that a persistence layer should fulfill to persist entities.
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
interface Persistence
{
    /**
     * Determine if the entity is in a managed state or not.
     *
     * @param string|object $entity The argument may be the entity or its id
     * in the identity map
     * @return bool TRUE when the entity is in a managed state; otherwise FALSE.
     */
    public function isInManagedState($entity);

    /**
     * Fetch all entities matching the specified criteria.
     *
     * Filtering, ranging and sorting criteria may be specified through the
     * expression array as defined by the {@link Service#fetchAll()} method
     * of the domain service layer.
     *
     * @param string $entityName Entity class name
     * @param array $expressions Fetch criteria
     * @return array Array of entities
     */
    public function fetchAll($entityName, array $expressions);

    /**
     * Fetch the single entity matching the specified criteria.
     *
     * Filtering, ranging and sorting criteria may be specified through the
     * expression array as defined by the {@link Service#fetchAll()} method
     * of the domain service layer.
     *
     * @param string $entityName Entity class name
     * @param array $expressions Fetch criteria
     * @return object Entity
     * @throws NoResultException If no entity matching the given criteria
     * is found.
     * @throws NonUniqueResultException If several entities matching the given
     * criteria are found.
     */
    public function fetchOne($entityName, array $expressions);

    /**
     * Get a paginator for the entities matching the specified criteria.
     *
     * Filtering, ranging and sorting criteria may be specified through the
     * expression array as defined by the {@link Service#fetchAll()} method
     * of the domain service layer.
     *
     * @param string $entityName Entity class name
     * @param array $expressions Fetch criteria
     * @return \Zend_Paginator Paginator
     */
    public function fetchPage($entityName, array $expressions);

    /**
     * Fetch key/value pairs of the named entity matching the specified
     * criteria.
     *
     * Filtering, ranging and sorting criteria may be specified through the
     * expression array as defined by the {@link Service#fetchAll()} method
     * of the domain service layer.
     * At least two properties of the entity must be selected with the
     * RQL 'select' operator: the ones specified in function arguments.
     *
     * @param string $entityName Entity class name.
     * @param string $idProperty Property name representing the identifier.
     * @param string $labelProperty Property name representing the label.
     * @param array $expressions Fetch criteria.
     * @return array Array consisting of id/label pairs.
     */
    public function fetchPairs(
        $entityName,
        $idProperty,
        $labelProperty,
        array $expressions
    );

    /**
     * Create the new entity into the persistence system.
     *
     * If the given entity has no identity and the identity is assigned by the
     * persistence system, the entity will be updated after its creation.
     *
     * @param object $entity The new entity to persist
     * @return void
     */
    public function create($entity);

    /**
     * Retrieve an entity with the specified identifier from the persistence
     * system.
     *
     * @param string $entityName Entity class name
     * @param mixed $id Identifier of the entity to retrieve
     * @return object The matching entity
     * @throws NoResultException If no entity matching the given id is found.
     * @throws NonUniqueResultException If several entities matching the given
     * id are found.
     */
    public function retrieve($entityName, $id);

    /**
     * Update the existing entity into the persistence system.
     *
     * The given entity must be in a managed state.
     *
     * @param object $entity The existing entity to persist
     * @return void
     * @throws RuntimeException If the given identity is not managed.
     */
    public function update($entity);

    /**
     * Delete the existing entity from the persistence system.
     *
     * The given entity must be in a managed state.
     *
     * @param object $entity The existing entity to delete
     * @return void
     * @throws RuntimeException If the given identity is not managed.
     */
    public function delete($entity);
}