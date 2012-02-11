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

use Serquant\Entity\Registry\IdentityMap;
use Serquant\Paginator\Adapter\DbSelect;
use Serquant\Persistence\Persistence;
use Serquant\Persistence\Exception\InvalidArgumentException;
use Serquant\Persistence\Exception\NoResultException;
use Serquant\Persistence\Exception\NonUniqueResultException;
use Serquant\Persistence\Exception\RuntimeException;
use Serquant\Persistence\Zend\Db\Table;

/**
 * Persistence layer using Zend_Db package to persist entities.
 *
 * Partialy based on Matthew Weier O'Phinney post about {@link
 * http://weierophinney.net/matthew/archives/202-Model-Infrastructure.html
 * Model Infrastructure} and Martin Fowler [PoEAA] book.
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Zend implements Persistence
{
    /**
     * Map of table data gateway names
     * The key is the entity class and the value is the gateway class
     * @var array
     */
    private $config;

    /**
     * Map of table data gateways.
     * The key is the entity name and the value an instance of
     * {@link Serquant\Persistence\Zend\Db\Table} (i.e. the gateway)
     * @var array
     */
    private $gateways;

    /**
     * Map of the loaded entities
     * @var IdentityMap
     */
    private $loadedMap;

    /**
     * Constructs a persister
     *
     * @param array $config Map between entity name and gateway name
     */
    public function __construct(array $config = array())
    {
        $this->config = $config;
        $this->gateways = array();
        $this->loadedMap = new IdentityMap();
    }

    /**
     * Normalizes the given argument to return an entity name
     *
     * @param string|object $entity Name or instance of the entity
     * @return string The normalized entity name
     * @throws InvalidArgumentException If the entity argument is of wrong type.
     */
    protected function normalizeEntityName($entity)
    {
        if (is_object($entity)) {
            $entity = get_class($entity);
        }
        if (!is_string($entity)) {
            throw new InvalidArgumentException(
                'The entity argument should either be an entity name or ' .
                'an instance of an entity class. ' . gettype($entity) .
                ' type provided.', 10
            );
        }
        return $entity;
    }

    /**
     * Sets the table data gateway corresponding to an entity name
     *
     * This function permits to inject a stub gateway for testing purpose.
     *
     * @param string|object $entityName Name or instance of the entity
     * @param Table $gateway Table data gateway
     * @return void
     */
    public function setTableGateway($entityName, $gateway)
    {
        if (is_string($gateway)) {
            $gateway = new $gateway;
        }

        if (!$gateway instanceof Table) {
            throw new RuntimeException(
                get_class($gateway) .
                ' is not an instance of Serquant\Persistence\Zend\Db\Table', 20
            );
        }

        $gateway->setPersister($this);
        $this->gateways[$this->normalizeEntityName($entityName)] = $gateway;
    }

    /**
     * Gets the table data gateway corresponding to an entity
     *
     * @param string|object $entityName Name or instance of the entity
     * @return Table
     * @throws InvalidArgumentException If the table gateway is not defined
     * in the entity metadata.
     */
    public function getTableGateway($entityName)
    {
        $entityName = $this->normalizeEntityName($entityName);
        if (!isset($this->gateways[$entityName])) {
            if (!isset($this->config[$entityName])) {
                throw new InvalidArgumentException(
                    'No table data gateway was found matching ' . $entityName, 30
                );
            }
            $this->setTableGateway($entityName, $this->config[$entityName]);
        }
        return $this->gateways[$entityName];
    }

    /**
     * Loads an entity from a database row
     *
     * @param string $entityName Entity class name
     * @param array $row Associative array holding database row
     * @return object
     */
    public function loadEntity($entityName, array $row)
    {
        // Following is a rewriting of a paragraph from Martin Fowler's book
        // [PoEAA, p. 172]:
        // "The Identity Map is checked twice, once in the Zend#retrieve
        // function, and once here. There is a reason for this madness. I need
        // to check the map in the finder because, if the object is already
        // there, I can save myself a trip to database. But I also need to check
        // here because I may have queries that I cant't be sure or resolving in
        // the Identity Map."
        $gateway = $this->getTableGateway($entityName);
        $pk = $gateway->getPrimaryKey($row);
        $entity = $this->loadedMap->get($entityName, $pk);
        if ($entity) {
            return $entity;
        }

        $entity = $gateway->loadEntity($row);

        $this->loadedMap->put($entity, $pk);
        return $entity;
    }

    /**
     * Loads an array of entities from an array of rows
     *
     * @param string $entityName Entity class name
     * @param array $rows Array of rows, each of which is an associative array
     * of column name-value
     * @return array Entities
     */
    public function loadEntities($entityName, array $rows)
    {
        $entities = array();
        foreach ($rows as $row) {
            $entities[] = $this->loadEntity($entityName, $row);
        }
        return $entities;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $entityName Entity class name
     * @param array $expressions Fetch criteria
     * @return array Array of entities
     */
    public function fetchAll($entityName, array $expressions)
    {
        $gateway = $this->getTableGateway($entityName);
        list ($select) = $gateway->translate($expressions);
        $data = $select->query()->fetchAll(\Zend_Db::FETCH_ASSOC);

        return $this->loadEntities($entityName, $data);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $entityName Entity class name
     * @param array $expressions Fetch criteria
     * @return object Entity
     * @throws NoResultException If no entity matching the given criteria
     * is found.
     * @throws NonUniqueResultException If several entities matching the given
     * criteria are found.
     */
    public function fetchOne($entityName, array $expressions)
    {
        $gateway = $this->getTableGateway($entityName);
        list ($select) = $gateway->translate($expressions);
        $data = $select->query()->fetchAll(\Zend_Db::FETCH_ASSOC);

        $count = count($data);
        if ($count === 0) {
            throw new NoResultException(
                'No entity matching the given criteria was found.', 40
            );
        } else if ($count > 1) {
            throw new NonUniqueResultException(
                'Several entities matching the given criteria were found.', 41
            );
        }

        return $this->loadEntity($entityName, $data[0]);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $entityName Entity class name
     * @param array $expressions Fetch criteria
     * @return \Zend_Paginator Paginator
     */
    public function fetchPage($entityName, array $expressions)
    {
        $gateway = $this->getTableGateway($entityName);
        list ($select, $pageNumber, $pageSize)
            = $gateway->translate($expressions);

        $adapter = new DbSelect($select, $this, $entityName);
        $paginator = new \Zend_Paginator($adapter);
        if (($pageNumber !== null) && ($pageSize !== null)) {
            $paginator->setCurrentPageNumber($pageNumber)
                ->setItemCountPerPage($pageSize);
        }
        return $paginator;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $entityName Entity class name.
     * @param string $idProperty Property name representing the identifier.
     * @param string $labelProperty Property name representing the label.
     * @param array $expressions Fetch criteria.
     * @return array Array consisting of id-label pairs.
     */
    public function fetchPairs(
        $entityName,
        $idProperty,
        $labelProperty,
        array $expressions
    ) {
        $gateway = $this->getTableGateway($entityName);
        list ($select) = $gateway->translate(
            $expressions,
            $gateway->selectPairs($idProperty, $labelProperty)
        );
        return $gateway->getAdapter()->fetchPairs($select);
    }

    /**
     * {@inheritDoc}
     *
     * @param object $entity The new entity to persist
     * @return void
     */
    public function create($entity)
    {
        $gateway = $this->getTableGateway($entity);
        $row = $gateway->loadRow($entity);
        $id = $gateway->insert($row);
        $gateway->updateEntityIdentifier($entity, $id);

        $this->loadedMap->put($entity, $id);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $entityName Entity class name
     * @param mixed $id Identifier of the entity to retrieve
     * @return object The matching entity
     * @throws NoResultException If no entity matching the given id is found.
     * @throws NonUniqueResultException If several entities matching the given
     * id are found.
     */
    public function retrieve($entityName, $id)
    {
        $entity = $this->loadedMap->get($entityName, (array) $id);
        if ($entity) {
            return $entity;
        }

        $gateway = $this->getTableGateway($entityName);
        if (is_array($id)) {
            $rowset = call_user_func_array(
                array($gateway, 'find'),
                array_values($id)
            );
        } else {
            $rowset = $gateway->find($id);
        }

        $count = count($rowset);
        if ($count === 0) {
            throw new NoResultException(
                'No entity matching the given identity was found.', 50
            );
        } else if ($count > 1) {
            throw new NonUniqueResultException(
                $count . ' entities matching the given identity were found.', 51
            );
        }

        // Create the entity from the row
        $row = $rowset->current();
        return $this->loadEntity($entityName, $row->toArray());
    }

    /**
     * {@inheritDoc}
     *
     * The given entity must have been retrieved previously with the
     * {@link Zend#retrieve()} method.
     *
     * @param object $entity The existing entity to persist
     * @return void
     * @throws RuntimeException If the given identity is not managed.
     * @throws NoResultException If no entity matching the given id is found.
     * @throws NonUniqueResultException If several entities matching the given
     * id are found.
     */
    public function update($entity)
    {
        if (false === $this->loadedMap->has($entity)) {
            throw new RuntimeException(
                'Unable to update an entity (of class ' . get_class($entity) .
                ') that is not managed. Did you forget to retrieve it?', 60
            );
        }

        $gateway = $this->getTableGateway($entity);
        $original = $this->loadedMap->getOriginal($entity);
        if ($changeSet = $gateway->computeChangeSet($original, $entity)) {
            $count = $gateway->update($changeSet, $this->loadedMap->getId($entity));
            if ($count === 0) {
                throw new NoResultException(
                    'No entity matching the given identity was updated.', 61
                );
            } else if ($count > 1) {
                throw new NonUniqueResultException(
                    $count . ' entities matching the given identity were updated.', 62
                );
            }

            $this->loadedMap->commit($entity);
        }
    }

    /**
     * {@inheritDoc}
     *
     * The given entity must have been retrieved previously with the
     * {@link Zend#retrieve()} method.
     *
     * @param object $entity The existing entity to delete
     * @return void
     * @throws RuntimeException If the given identity is not managed.
     */
    public function delete($entity)
    {
        if (false === $this->loadedMap->has($entity)) {
            throw new RuntimeException(
                'Unable to delete an entity (of class ' . get_class($entity) .
                ') that is not managed. Did you forget to retrieve it?', 70
            );
        }

        $gateway = $this->getTableGateway($entity);
        $count = $gateway->delete($this->loadedMap->getId($entity));
        if ($count === 0) {
            throw new NoResultException(
                'No entity matching the given identity was deleted.', 71
            );
        } else if ($count > 1) {
            throw new NonUniqueResultException(
                $count . ' entities matching the given identity were deleted.', 72
            );
        }

        $this->loadedMap->remove($entity);
    }
}