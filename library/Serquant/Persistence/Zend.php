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

use Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,
    Serquant\Paginator\Adapter\DbSelect,
    Serquant\Persistence\Storable,
    Serquant\Persistence\Exception\InvalidArgumentException,
    Serquant\Persistence\Exception\NoResultException,
    Serquant\Persistence\Exception\NonUniqueResultException,
    Serquant\Persistence\Exception\RuntimeException,
    Serquant\Persistence\Zend\Db\Table;

/**
 * Persistence layer using Zend_Db package to persist entities.
 *
 * Partialy based on Matthew Weier O'Phinney post about {@link
 * http://weierophinney.net/matthew/archives/202-Model-Infrastructure.html
 * Model Infrastructure}.
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Zend implements Storable
{
    /**
     * Table gateway
     * @var \Zend_Db_Table_Abstract
     */
    private $table;

    /**
     * Identity map of the managed rows
     * @var array
     */
    private $identityMap = array();

    /**
     * Get class metadata of the given entity
     *
     * @param string|object $entityName Name or instance of the entity
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function getClassMetadata($entityName)
    {
        if (!is_string($entityName)) {
            $entityName = get_class($entityName);
        }
        $class = new ClassMetadata($entityName);
        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        $driver = new AnnotationDriver($reader);
        $driver->loadMetadataForClass($entityName, $class);

        return $class;
    }

    /**
     * Get table gateway corresponding to the entity.
     *
     * @param string|object $entityName Name or instance of the entity
     * @return \Zend_Db_Table_Abstract
     * @throws InvalidArgumentException If the table gateway is not defined
     * in the entity annotations.
     */
    protected function getTable($entityName)
    {
        if ($this->table === null) {
            $entityMetadata = $this->getClassMetadata($entityName);
            $tableClass = $entityMetadata->customRepositoryClassName;

            if ($tableClass === null) {
                throw new InvalidArgumentException(
                    $entityName . ' should define a table class with the '
                    . '@entity(repositoryClass="<classname>") annotation.'
                );
            }

            $this->table = new $tableClass;
            if (!($this->table instanceof Table)) {
                throw new InvalidArgumentException(
                    "Class $tableClass is not an instance of "
                    . 'Serquant\Persistence\Zend\Db\Table'
                );
            }
            $this->table->setEntityMetadata($entityMetadata);
        }
        return $this->table;
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $entity The argument may be the entity or its id
     * in the identity map
     * @return bool TRUE when the entity is in a managed state; otherwise FALSE.
     */
    protected function isInManagedState($entity)
    {
        if (!is_string($entity)) {
            $idHash = $this->getIdentityHash($entity);
        } else {
            $idHash = $entity;
        }

        return isset($this->identityMap[$idHash]);
    }

    /**
     * Get entity identifier used as a key in the identity map.
     *
     * @param string|object $entity Name or instance of the entity
     * @return mixed
     */
    protected function getIdentityHash($entity)
    {
        $class = $this->getClassMetadata($entity);

        if ($class->isIdentifierComposite) {
            $id = array();
            foreach ($class->identifier as $fieldName) {
                $id[] = $class->reflFields[$fieldName]->getValue($entity);
            }
            $idHash = implode(' ', $id);
        } else {
            $fieldName = $class->identifier[0];
            $idHash = $class->reflFields[$fieldName]->getValue($entity);
        }

        return $idHash;
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
        $table = $this->getTable($entityName);
        list($select, $pageNumber, $pageSize) = $table->translate($expressions);
        $data = $select->query()->fetchAll(\Zend_Db::FETCH_ASSOC);

        return $table->getEntities($data);
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
        $table = $this->getTable($entityName);
        list($select, $pageNumber, $pageSize) = $table->translate($expressions);
        $data = $select->query()->fetchAll(\Zend_Db::FETCH_ASSOC);

        $count = count($data);
        if ($count === 0) {
            throw new NoResultException(
                'No entity matching the given criteria was found.'
            );
        } else if ($count > 1) {
            throw new NonUniqueResultException(
                'Several entities matching the given criteria were found.'
            );
        }

        $entities = $table->getEntities($data);
        return $entities[0];
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
        $table = $this->getTable($entityName);
        list($select, $pageNumber, $pageSize) = $table->translate($expressions);
        $adapter = new DbSelect($select);
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
     * @param object $entity The new entity to persist
     * @return void
     */
    public function create($entity)
    {
        $table = $this->getTable($entity);
        $table->create($entity);
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
        $table = $this->getTable($entityName);
        $rowset = $table->find($id);

        $count = count($rowset);
        if ($count === 0) {
            throw new NoResultException(
                'No entity matching the given identity was found.'
            );
        } else if ($count > 1) {
            throw new NonUniqueResultException(
                'Several entities matching the given identity were found.'
            );
        }

        // Create the entity from the row
        $row = $rowset->current();
        $entity = $table->getEntity($row->toArray());

        // Keep this row in the identity map for future use (update and delete)
        $idHash = $this->getIdentityHash($entity);
        $this->identityMap[$idHash] = $row;

        return $entity;
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
     */
    public function update($entity)
    {
        $idHash = $this->getIdentityHash($entity);
        if (!$this->isInManagedState($idHash)) {
            throw new RuntimeException(
                'No managed entity of class ' . get_class($entity)
                . " could be found with identity '$idHash'. Update failed."
            );
        }

        $table = $this->getTable($entity);

        $row = $this->identityMap[$idHash];
        $row->setFromArray($table->convertToDatabaseValues($entity));
        $row->save();
        unset($this->identityMap[$idHash]);
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
        $idHash = $this->getIdentityHash($entity);
        if (!isset($this->identityMap[$idHash])) {
            throw new RuntimeException(
                'No managed entity of class ' . get_class($entity)
                . " could be found with identity '$idHash'. Delete failed."
            );
        }

        $row = $this->identityMap[$idHash];
        $row->delete();
        unset($this->identityMap[$idHash]);
    }
}