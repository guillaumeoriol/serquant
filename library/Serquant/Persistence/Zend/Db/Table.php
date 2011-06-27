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
namespace Serquant\Persistence\Zend\Db;

use Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Mapping\ClassMetadata,
    Serquant\Persistence\Exception\RuntimeException;

/**
 * Table gateway used by Zend persistence layer.
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Table extends \Zend_Db_Table_Abstract
{
    /**
     * Map between Zend adapters and Doctrine platforms
     * @var array
     */
    private static $platformMap = array(
        'Zend_Db_Adapter_Pdo_Mysql' => '\Doctrine\DBAL\Platforms\MySqlPlatform',
        'Zend_Db_Adapter_Pdo_Sqlite' => '\Doctrine\DBAL\Platforms\SqlitePlatform',
        'Zend_Db_Adapter_Pdo_Pgsql' => '\Doctrine\DBAL\Platforms\PostgreSqlPlatform',
        'Zend_Db_Adapter_Pdo_Oci' => '\Doctrine\DBAL\Platforms\OraclePlatform',
        'Zend_Db_Adapter_Oracle' => '\Doctrine\DBAL\Platforms\OraclePlatform',
        'Zend_Db_Adapter_Db2' => '\Doctrine\DBAL\Platforms\DB2Platform',
        'Zend_Db_Adapter_Pdo_Ibm' => '\Doctrine\DBAL\Platforms\DB2Platform',
        'Zend_Db_Adapter_Sqlsrv' => '\Doctrine\DBAL\Platforms\MsSqlPlatform',
    );

    /**
     * Class metadata of the entity to persist
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $entityMetadata;

    /**
     * Database platform used for type conversion
     * @var Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * Get entity class metadata
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getEntityMetadata()
    {
        return $this->entityMetadata;
    }

    /**
     * Set entity class metadata
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class Entity class metadata
     * @return void
     */
    public function setEntityMetadata(ClassMetadata $class)
    {
        $this->entityMetadata = $class;
    }

    /**
     * Get database platform
     *
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform
     * @throws RuntimeException If no matching platform exists for the current
     * database adapter.
     */
    protected function getDatabasePlatform()
    {
        if ($this->platform === null) {
            $adapterClass = get_class($this->getAdapter());
            if (!isset(self::$platformMap[$adapterClass])) {
                throw new RuntimeException(
                    'The specified database adapter (' . $adapterClass
                    . ') does not have a matching platform.'
                );
            }
            $platformClass = self::$platformMap[$adapterClass];
            $this->platform = new $platformClass;
        }
        return $this->platform;
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
     * @param array $expressions RQL query
     * @return Zend_Db_Select Output query
     * @throws RuntimeException If non-implemented operator is used, if the sort
     * order is not specified or if a parenthesis-enclosed group syntax is used.
     */
    public function translate(array $expressions)
    {
        $pageNumber = $pageSize = null;
        if (count($expressions) === 0) {
            return array($this->select(), $pageNumber, $pageSize);
        }

        $select = $this->select();
        $columns = array();
        $orderBy = array();
        $limitStart = $limitCount = null;

        $class = $this->getEntityMetadata();
        $platform = $this->getDatabasePlatform();

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
                        $column = $class->columnNames[substr($field, 1)];
                        if ('-' === substr($field, 0, 1)) {
                            $orderBy[] = $column . ' DESC';
                        } else if ('+' === substr($field, 0, 1)) {
                            $orderBy[] = $column . ' ASC';
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
                        "Operator $value not implemented yet."
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
            $select->from($this->_name, $columns);
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
     * Convert an entity into an associative array of database values.
     *
     * The output is an associative array whose keys are column names.
     *
     * @param object $entity The entity to convert
     * @return array The converted data
     */
    public function convertToDatabaseValues($entity)
    {
        $class = $this->getEntityMetadata();
        $platform = $this->getDatabasePlatform();
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
     * @return array The converted data
     */
    public function convertToPhpValues(array $row)
    {
        $class = $this->getEntityMetadata();
        $platform = $this->getDatabasePlatform();
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
     * Get an entity from a database row.
     *
     * @param array $row Associative array whose keys are column names
     * @return object Entity
     */
    public function getEntity(array $row)
    {
        $class = $this->getEntityMetadata();

        $data = $this->convertToPhpValues($row);
        $entity = $class->newInstance();
        foreach ($data as $field => $value) {
            if (isset($class->fieldMappings[$field])) {
                $class->reflFields[$field]->setValue($entity, $value);
            }
        }
        return $entity;
    }

    /**
     * Get an array of entities from a row array
     *
     * @param array $rows Array of rows, each of which is an associative array
     * whose keys are column names
     * @return array Entities
     */
    public function getEntities(array $rows)
    {
        $entities = array();
        foreach ($rows as $row) {
            $entities[] = $this->getEntity($row);
        }
        return $entities;
    }

    /**
     * Create the entity into persistence system.
     *
     * If the given entity has no identity and the identity is assigned by the
     * persistence system, the entity will be updated after its creation.
     *
     * @param object $entity The entity to create
     * @return void
     */
    public function create($entity)
    {
        $row = $this->createRow($this->convertToDatabaseValues($entity));
        $primaryKey = $row->save();

        $class = $this->getEntityMetadata();
        $platform = $this->getDatabasePlatform();

        // Update identity of the entity
        $idGen = $class->idGenerator;
        if ($idGen->isPostInsertGenerator()) {
            // The primary key is either an associative array if the key
            // is compound, or a scalar if the key is single-column.
            if (!is_array($primaryKey)) {
                $primaryKey = array($class->identifier[0] => $primaryKey);
            }
            foreach ($primaryKey as $column => $value) {
                if (isset($class->fieldNames[$column])) {
                    $field = $class->fieldNames[$column];
                    $value
                        = Type::getType($class->fieldMappings[$field]['type'])
                            ->convertToPHPValue($value, $platform);
                    $class->reflFields[$field]->setValue($entity, $value);
                }
            }
        }
    }
}