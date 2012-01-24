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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Serquant\Paginator\Adapter\DbSelect;
use Serquant\Entity\Registry\Ormless;
use Serquant\Persistence\Persistence;
use Serquant\Persistence\Exception\InvalidArgumentException;
use Serquant\Persistence\Exception\NoResultException;
use Serquant\Persistence\Exception\NonUniqueResultException;
use Serquant\Persistence\Exception\RuntimeException;

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
    private $entityManager;

    /**
     * Table data gateways
     * @var array Array of Zend_Db_Table_Abstract elements
     */
    private $gateways;

    /**
     * Registry of the loaded entities
     * @var \Serquant\Entity\Registry\Registrable
     */
    private $loadedEntities;

    /**
     * Service constructor
     *
     * @param EntityManager $em Entity manager used for data mapping
     */
    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
        $this->loadedEntities = new Ormless($em->getMetadataFactory());
    }

    /**
     * Get metadata factory
     *
     * @return ClassMetadataFactory Metadata factory
     */
    public function getMetadataFactory()
    {
        return $this->entityManager->getMetadataFactory();
    }

    /**
     * Get registry of loaded entities.
     *
     * @return \Serquant\Entity\Registry\Registrable Entity registry
     */
    public function getEntityRegistry()
    {
        return $this->loadedEntities;
    }

    /**
     * Get metadata of the given class
     *
     * @param string $className Name of the entity class
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata($className)
    {
        return $this->getMetadataFactory()->getMetadataFor($className);
    }

    /**
     * Normalizes the given argument to return an entity name.
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
                ' type provided.', 1
            );
        }
        return $entity;
    }

    /**
     * Set the table data gateway corresponding to an entity.
     *
     * This function permits to inject a stub gateway for testing purpose.
     *
     * @param string|object $entityName Name or instance of the entity
     * @param \Zend_Db_Table_Abstract $table Table data gateway
     * @return void
     */
    public function setTableGateway($entityName, \Zend_Db_Table_Abstract $table)
    {
        $this->gateways[$this->normalizeEntityName($entityName)] = $table;
    }

    /**
     * Get the table gateway corresponding to an entity.
     *
     * @param string|object $entityName Name or instance of the entity
     * @return \Zend_Db_Table_Abstract
     * @throws InvalidArgumentException If the table gateway is not defined
     * in the entity metadata.
     */
    protected function getTableGateway($entityName)
    {
        $entityName = $this->normalizeEntityName($entityName);
        if (!isset($this->gateways[$entityName])) {
            $entityMetadata = $this->getClassMetadata($entityName);
            $className = $entityMetadata->customRepositoryClassName;

            if ($className === null) {
                throw new InvalidArgumentException(
                    $entityName . ' should define a table gateway class ' .
                    'with the \'repository class\' attribute of the entity ' .
                    '(such as the following annotation: ' .
                    '@Entity(repositoryClass="<classname>").', 2
                );
            }

            $gateway = new $className;
            if (!($gateway instanceof \Zend_Db_Table_Abstract)) {
                throw new InvalidArgumentException(
                    "Class $className is not an instance of "
                    . 'Zend_Db_Table_Abstract.', 3
                );
            }
            $this->gateways[$entityName] = $gateway;
        }
        return $this->gateways[$entityName];
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
     * @return array consisting of Zend_Db_Select, page number and page size
     * @throws RuntimeException If non-implemented operator is used, if the sort
     * order is not specified or if a parenthesis-enclosed group syntax is used.
     */
    protected function translate($entityName, array $expressions)
    {
        $pageNumber = $pageSize = null;
        $table = $table = $this->getTableGateway($entityName);
        $select = $table->select();
        if (count($expressions) === 0) {
            return array($select, $pageNumber, $pageSize);
        }

        $columns = array();
        $orderBy = array();
        $limitStart = $limitCount = null;

        $class = $this->getClassMetadata($entityName);
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();

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
                        } else {
                            // We don't check anymore if the first character
                            // is a '+' symbol as the PHP engine automatically
                            // processes $_GET and $_REQUEST superglobals with
                            // urldecode(), thus changing any plus symbol into
                            // a space, and dojo.store.JsonRest doesn't encode
                            // the '+' it adds for sorting.
                            $orderBy[] = $class->columnNames[substr($field, 1)]
                                       . ' ASC';
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
                $column = null;
                if ($class->hasField($key)) {
                    $column = $class->columnNames[$key];
                } else if ($class->hasAssociation($key)) {
                    $column = $class->getSingleAssociationJoinColumnName($key);
                }
                if ($column) {
                    if (false === strpos($value, '*')) {
                        $select->where("$column = ?", $value);
                    } else {
                        $select->where("$column like ?", str_replace('*', '%', $value));
                    }
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

        $class = $this->getClassMetadata($entityName);
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();
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
        $class = $this->getClassMetadata(get_class($entity));
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
        $class = $this->getClassMetadata(get_class($entity));
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();
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
        if (is_array($id)) {
            $rowset = call_user_func_array(
                array($table, 'find'),
                array_values($id)
            );
        } else {
            $rowset = $table->find($id);
        }

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
        $platform = $this->entityManager->getConnection()->getDatabasePlatform();
        if ($changeSet = $this->loadedEntities->computeChangeSet($entity, $platform)) {
            $count = $table->update($changeSet, $this->getWhereClause($entity));
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