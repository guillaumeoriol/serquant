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
    Doctrine\DBAL\Platforms\AbstractPlatform,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver,
    Serquant\Paginator\Adapter\DbSelect,
    Serquant\Persistence\Persistence,
    Serquant\Persistence\Exception\InvalidArgumentException,
    Serquant\Persistence\Exception\NoResultException,
    Serquant\Persistence\Exception\NonUniqueResultException,
    Serquant\Persistence\Exception\RuntimeException,
    Serquant\Persistence\Zend\EntityRegistry;

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
     * Entity manager
     * @var EntityManager
     */
    private $em;

    /**
     * Table data gateway
     * @var \Zend_Db_Table_Abstract
     */
    private $table;

    /**
     * Registry of the loaded entities
     * @var EntityRegistry
     */
    private $loadedEntities;

    /**
     * Service constructor
     *
     * @param EntityManager $em Entity manager used for data mapping
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->loadedEntities = new EntityRegistry($em->getMetadataFactory());
    }

    /**
     * Get class metadata of the given entity
     *
     * @param string|object $entityName Name or instance of the entity
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function getEntityMetadata($entityName)
    {
        if (is_object($entityName)) {
            $entityName = get_class($entityName);
        }
        return $this->em->getClassMetadata($entityName);
    }

    /**
     * Set the table data gateway corresponding to the entity.
     *
     * This function permits to inject a stub gateway for testing purpose.
     *
     * @param \Zend_Db_Table_Abstract $table Table data gateway
     * @return void
     */
    public function setTableGateway(\Zend_Db_Table_Abstract $table)
    {
        $this->table = $table;
    }

    /**
     * Get the table gateway corresponding to the entity.
     *
     * @param string|object $entityName Name or instance of the entity
     * @return \Zend_Db_Table_Abstract
     * @throws InvalidArgumentException If the table gateway is not defined
     * in the entity metadata.
     */
    protected function getTableGateway($entityName)
    {
        if ($this->table === null) {
            $entityMetadata = $this->getEntityMetadata($entityName);
            $className = $entityMetadata->customRepositoryClassName;

            if ($className === null) {
                throw new InvalidArgumentException(
                    $entityName . ' should define a table gateway class ' .
                    'with the \'repository class\' attribute of the entity ' .
                    '(such as the following annotation: ' .
                    '@Entity(repositoryClass="<classname>").'
                );
            }

            $this->table = new $className;
            if (!($this->table instanceof \Zend_Db_Table_Abstract)) {
                throw new InvalidArgumentException(
                    "Class $className is not an instance of "
                    . 'Zend_Db_Table_Abstract.'
                );
            }
        }
        return $this->table;
    }

    /**
     * Translates a RQL ({@link https://github.com/kriszyp/rql Resource Query
     * Language}) query into a {@link
     * http://framework.zend.com/manual/en/zend.db.select.html Zend_Db_Select}
     * query.
     *
     * Filtering, ranging and sorting criteria may be specified through the
     * expression array as defined by the {@link Service#fetchAll()} method
     * of the domain service layer.
     *
     * @param string $entityName Entity name
     * @param array $expressions RQL query
     * @return array List of Zend_Db_Select, page number and page size
     * @throws RuntimeException If non-implemented operator is used, if the sort
     * order is not specified or if a parenthesis-enclosed group syntax is used.
     */
    protected function translate($entityName, array $expressions)
    {
        $pageNumber = $pageSize = null;
        if (count($expressions) === 0) {
            return array($this->select(), $pageNumber, $pageSize);
        }

        $table = $table = $this->getTableGateway($entityName);
        $select = $table->select();
        $columns = array();
        $orderBy = array();
        $limitStart = $limitCount = null;

        $class = $this->getEntityMetadata($entityName);
        $platform = $this->em->getConnection()->getDatabasePlatform();

        foreach ($expressions as $key => $value) {
            if (is_int($key)) {
                // Regular operator syntax
                if (preg_match('/^select\((.*)\)$/', $value, $matches)) {
                    $fields = explode(',', $matches[1]);
                    foreach ($fields as $field) {
                        $columns[] = $class->columnNames[$field];
                    }
                } else if (preg_match('/^sort\((.*)\)$/', $value, $matches)) {
                    $fields = explode(',', $matches[1]);
                    foreach ($fields as $field) {
                        if ('-' === substr($field, 0, 1)) {
                            $orderBy[] = $class->columnNames[substr($field, 1)]
                                       . ' DESC';
                        } else if ('+' === substr($field, 0, 1)) {
                            $orderBy[] = $class->columnNames[substr($field, 1)]
                                       . ' ASC';
                        } else {
                            throw new RuntimeException(
                                'Sort order not specified for property \'' .
                                $field . '\'. It must be preceded by either' .
                                '+ or - sign.'
                            );
                        }
                    }
                } else if (preg_match(
                    '/^limit\(([0-9]+),([0-9]+)\)$/', $value, $matches
                )) {
                    $limitStart = (int) $matches[1];
                    $limitCount = (int) $matches[2];
                } else {
                    throw new RuntimeException(
                        "Operator $value not implemented."
                    );
                }
            } else {
                // Alternate comparison syntax
                if ('(' === substr($key, 0, 1)) {
                    throw new RuntimeException(
                        'Parenthesis-enclosed group syntax not supported. ' .
                        'Use regular operator syntax instead: ' .
                        'or(operator,operator,...)'
                    );
                }
                $column = $class->columnNames[$key];
                if (false === strpos($value, '*')) {
                    $select->where("$column = ?", $value);
                } else {
                    $select->where("$column like ?", str_replace('*', '%', $value));
                }
            }
        }

        if (count($columns) > 0) {
            $select->from($table, $columns);
        }
        if (count($orderBy) > 0) {
            $select->order($orderBy);
        }

        if (($limitStart !== null) && ($limitCount !== null)) {
            $pageNumber = ($limitStart / $limitCount) + 1;
            $pageSize = $limitCount;
        }
        return array($select, $pageNumber, $pageSize);
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
        list ($select) = $this->translate($entityName, $expressions);
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
        list ($select) = $this->translate($entityName, $expressions);
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
        list ($select, $pageNumber, $pageSize)
            = $this->translate($entityName, $expressions);

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
     * @return array Array consisting of id/label pairs.
     */
    public function fetchPairs(
        $entityName,
        $idProperty,
        $labelProperty,
        array $expressions
    ) {
        list ($select) = $this->translate($entityName, $expressions);
        $table = $this->getTableGateway($entityName);
        return $table->getAdapter()->fetchPairs($select);
    }

    /**
     * Convert an entity into an associative array of database values.
     *
     * The output is an associative array whose keys are column names.
     *
     * @param object $entity The entity to convert
     * @param ClassMetadata $class Entity metadata
     * @param AbstractPlatform $platform Database platform
     * @return array The converted data
     */
    protected function convertToDatabaseValues(
        $entity,
        ClassMetadata $class,
        AbstractPlatform $platform
    ) {
        $data = array();
        foreach ($class->fieldMappings as $fieldName => $field) {
            $value = $class->reflFields[$fieldName]->getValue($entity);
            $data[$field['columnName']] = Type::getType($field['type'])
                ->convertToDatabaseValue($value, $platform);
        }

        return $data;
    }

    /**
     * Convert database values into PHP values.
     *
     * The input is an associative array whose keys are column names.<br>
     * The output is an associative array whose keys are entity field names.
     *
     * @param array $row Associative array of database values
     * @param ClassMetadata $class Entity metadata
     * @param AbstractPlatform $platform Database platform
     * @return array The converted data
     */
    protected function convertToPhpValues(
        array $row,
        ClassMetadata $class,
        AbstractPlatform $platform
    ) {
        $data = array();
        foreach ($row as $column => $value) {
            if (isset($class->fieldNames[$column])) {
                $field = $class->fieldNames[$column];
                if (isset($data[$field])) {
                    $data[$column] = $value;
                } else {
                    $data[$field]
                        = Type::getType($class->fieldMappings[$field]['type'])
                            ->convertToPHPValue($value, $platform);
                }
            } else {
                $data[$column] = $value;
            }
        }

        return $data;
    }

    /**
     * Load an entity from a database row.
     *
     * @param string $entityName Entity class name
     * @param array $row Associative array holding database values
     * @return object Entity
     */
    protected function loadEntity($entityName, array $row)
    {
        // Following is a rewriting of a paragraph from Martin Fowler's book
        // [PoEAA, p. 172]:
        // "The Identity Map is checked twice, once in the Zend#retrieve
        // function, and once here. There is a reason for this madness. I need
        // to check the map in the finder because, if the object is already
        // there, I can save myself a trip to database. But I also need to check
        // here because I may have queries that I cant't be sure or resolving in
        // the Identity Map."
        $entity = $this->loadedEntities->tryGetByRow($entityName, $row);
        if ($entity) {
            return $entity;
        }

        $class = $this->getEntityMetadata($entityName);
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $data = $this->convertToPhpValues($row, $class, $platform);

        $entity = $class->newInstance();
        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
                $class->reflFields[$field]->setValue($entity, $value);
            }
        }

        $this->loadedEntities->put($entity);
        return $entity;
    }

    /**
     * Load an array of entities from a row array
     *
     * This function is public because of the pagination adapter.
     *
     * @param string $entityName Entity class name
     * @param array $rows Array of rows, each of which is an associative array
     * whose keys are column names
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
     * Returns an array representing the WHERE clause identifying the entity.
     *
     * For its <var>$where</var> parameter, Zend_Db_Table#update expects an
     * associative array like: <code>array('id = ?' => 1)</code>.
     * Based on \Doctrine\ORM\Mapping\ClassMetadata#getIdentifierValues
     *
     * @param object $entity Entity to get identity from
     * @return array Array representing the WHERE clause.
     */
    protected function getWhereClause($entity)
    {
        $class = $this->getEntityMetadata($entity);
        if ($class->isIdentifierComposite) {
            $id = array();
            foreach ($class->identifier as $idField) {
                $value = $class->reflFields[$idField]->getValue($entity);
                if ($value !== null) {
                    $id[$idField . ' = ?'] = $value;
                }
            }
            return $id;
        } else {
            $value = $class->reflFields[$class->identifier[0]]->getValue($entity);
            if ($value !== null) {
                return array($class->identifier[0] . ' = ?' => $value);
            }
            return array();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param object $entity The new entity to persist
     * @return void
     */
    public function create($entity)
    {
        $class = $this->getEntityMetadata($entity);
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $data = $this->convertToDatabaseValues($entity, $class, $platform);

        // @todo Implement id generation when the identifier is not generated
        // by the storage system.

        $table = $this->getTableGateway($entity);
        $row = $table->createRow($data);
        $primaryKey = $row->save();

        // Update the entity's identity
        $idGen = $class->idGenerator;
        if ($idGen->isPostInsertGenerator()) {
            // The primary key is either an associative array if the key
            // is compound, or a scalar if the key is a single column.
            if (!is_array($primaryKey)) {
                $primaryKey = array($class->identifier[0] => $primaryKey);
            }
            foreach ($primaryKey as $column => $value) {
                if (isset($class->fieldNames[$column])) {
                    $field = $class->fieldNames[$column];
                    $value = Type::getType($class->fieldMappings[$field]['type'])
                        ->convertToPHPValue($value, $platform);
                    $class->reflFields[$field]->setValue($entity, $value);
                }
            }
        }

        $this->loadedEntities->put($entity);
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
        $entity = $this->loadedEntities->tryGetById($entityName, $id);
        if ($entity) {
            return $entity;
        }

        $table = $this->getTableGateway($entityName);
        $rowset = $table->find($id);

        $count = count($rowset);
        if ($count === 0) {
            throw new NoResultException(
                'No entity matching the given identity was found.'
            );
        } else if ($count > 1) {
            throw new NonUniqueResultException(
                $count . ' entities matching the given identity were found.'
            );
        }

        // Create the entity from the row
        $row = $rowset->current();
        $entity = $this->loadEntity($entityName, $row->toArray());
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
     * @throws NoResultException If no entity matching the given id is found.
     * @throws NonUniqueResultException If several entities matching the given
     * id are found.
     */
    public function update($entity)
    {
        if (false === $this->loadedEntities->hasEntity($entity)) {
            throw new RuntimeException(
                'Unable to update an entity (of class ' . get_class($entity) .
                ') that is not managed.'
            );
        }

        $table = $this->getTableGateway($entity);
        $platform = $this->em->getConnection()->getDatabasePlatform();
        $count = $table->update(
            $this->loadedEntities->computeChangeSet($entity, $platform),
            $this->getWhereClause($entity)
        );
        if ($count === 0) {
            throw new NoResultException(
                'No entity matching the given identity was updated.'
            );
        } else if ($count > 1) {
            throw new NonUniqueResultException(
                $count . ' entities matching the given identity were updated.'
            );
        }

        $this->loadedEntities->commitChangeSet($entity);
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
        if (false === $this->loadedEntities->hasEntity($entity)) {
            throw new RuntimeException(
                'Unable to delete an entity (of class ' . get_class($entity) .
                ') that is not managed.'
            );
        }

        $table = $this->getTableGateway($entity);
        $count = $table->delete($this->getWhereClause($entity));
        if ($count === 0) {
            throw new NoResultException(
                'No entity matching the given identity was deleted.'
            );
        } else if ($count > 1) {
            throw new NonUniqueResultException(
                $count . ' entities matching the given identity were deleted.'
            );
        }

        $this->loadedEntities->remove($entity);
    }
}