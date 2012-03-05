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

use ReflectionClass;
use Serquant\Persistence\Exception\NoResultException;
use Serquant\Persistence\Exception\NonUniqueResultException;
use Serquant\Persistence\Exception\RuntimeException;
use Serquant\Persistence\Zend\Persister;

/**
 * Table data access class
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
     * Entity name matching this database table
     * @var string
     */
    protected $entityName;

    /**
     * Entity class prototype used to build instances of the entity without
     * invoking its constructor.
     * @var object
     */
    private $prototype;

    /**
     * Allowed platform list for type conversion purpose.
     * Key is the Zend DB adapter name; value is the Doctrine DBAL platform name
     * @var array
     */
    private static $platformMap = array(
        'Zend_Db_Adapter_Pdo_Mysql'  => 'Doctrine\DBAL\Platforms\MySqlPlatform',
        'Zend_Db_Adapter_Pdo_Sqlite' => 'Doctrine\DBAL\Platforms\SqlitePlatform',
        'Zend_Db_Adapter_Pdo_Pgsql'  => 'Doctrine\DBAL\Platforms\PostgreSqlPlatform',
        'Zend_Db_Adapter_Pdo_Oci' => 'Doctrine\DBAL\Platforms\OraclePlatform',
        'Zend_Db_Adapter_Oracle' => 'Doctrine\DBAL\Platforms\OraclePlatform',
        'Zend_Db_Adapter_Db2' => 'Doctrine\DBAL\Platforms\DB2Platform',
        'Zend_Db_Adapter_Pdo_Ibm_Db2' => 'Doctrine\DBAL\Platforms\DB2Platform',
        'Zend_Db_Adapter_Sqlsrv' => 'Doctrine\DBAL\Platforms\MsSqlPlatform'
    );

    /**
     * Database platform used for type conversion
     * @var Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * Reflection properties of the entity to avoid invoking accessors.
     * @var array
     */
    private $reflProperties;

    /**
     * Back reference to the persister managing this class
     * @var Persister
     */
    private $persister;

    /**
     * Map of column names
     * Key is the field name and value is the column name. This map is the
     * opposite of {@link fieldNames}.
     * @var array
     */
    protected $columnNames = array();

    /**
     * Map of field names
     * Key is the column name and value is the field name. This map is the
     * opposite of {@link columnNames}.
     * @var array
     */
    protected $fieldNames = array();

    /**
     * Checks if this table has a column matching the given field
     *
     * @param string $field Entity field name
     * @return boolean
     */
    protected function hasColumn($field)
    {
        return isset($this->columnNames[$field]);
    }

    /**
     * Gets column matching the given field
     *
     * @param string $field Entity field name
     * @return string
     */
    protected function getColumn($field)
    {
        return $this->columnNames[$field];
    }

    /**
     * Checks if the entity has a field matching the given column
     *
     * @param string $column Column name
     * @return boolean
     */
    protected function hasField($column)
    {
        return isset($this->fieldNames[$column]);
    }

    /**
     * Gets field name matching the given column
     *
     * @param string $column Column name
     * @return string
     */
    protected function getField($column)
    {
        return $this->fieldNames[$column];
    }

    /**
     * Sets a back reference to the persister managing this class
     *
     * @param Persister $persister Persister instance
     * @return void
     * @internal This property should be set in the constructor but, as we
     * extend an existing class from Zend, we can't change the constructor
     * signature.
     */
    public function setPersister(Persister $persister)
    {
        $this->persister = $persister;
    }

    /**
     * Gets the persister managing this class
     *
     * @return Persister
     * @throws RuntimeException if the back reference is not set
     */
    protected function getPersister()
    {
        if ($this->persister === null) {
            throw new RuntimeException(
                "Undefined persister on '$this->_name' table data gateway", 10
            );
        }
        return $this->persister;
    }

    /**
     * Gets a Zend_Db_Select to retrieve the given id and label
     *
     * @param string $id Property name of the entity representing the column
     * to be returned as identifier
     * @param string $label Property name of the entity representing the column
     * to be returned as label
     * @return \Zend_Db_Select
     */
    public function selectPairs($id, $label)
    {
        if (!$this->hasColumn($id) || !$this->hasColumn($label)) {
            throw new RuntimeException(
                "Either '$id' or '$label' entity property can not be found" .
                'in field-to-column map.', 20
            );
        }

        return $this->select()
            ->from(
                $this->_name, array($this->getColumn($id), $this->getColumn($label))
            );
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
     * @param \Zend_Db_Select $select Optional select object to use
     * @return array consisting of Zend_Db_Select, item start and item count
     * @throws RuntimeException If non-implemented operator is used, if the sort
     * order is not specified or if a parenthesis-enclosed group syntax is used.
     */
    public function translate(array $expressions, \Zend_Db_Select $select = null)
    {
        $limitStart = $limitCount = null;
        if ($select === null) {
            $select = $this->select();
        }
        if (count($expressions) === 0) {
            return array($select, $limitStart, $limitCount);
        }

        $orderBy = array();
        foreach ($expressions as $key => $value) {
            if (is_int($key)) {
                // Regular operator syntax
                if (preg_match('/^sort\((.*)\)$/', $value, $matches)) {
                    $operands = explode(',', $matches[1]);
                    foreach ($operands as $operand) {
                        if (strlen($operand) > 1) {
                            // Silently discard too short sort operands
                            $field = substr($operand, 1);
                            if ($this->hasColumn($field)) {
                                if ('-' === substr($operand, 0, 1)) {
                                    $orderBy[] = $this->getColumn($field)
                                               . ' DESC';
                                } else {
                                    // We don't check anymore if the first char
                                    // is a '+' symbol as the PHP engine auto-
                                    // matically processes $_GET and $_REQUEST
                                    // superglobals with urldecode(), thus
                                    // changing any plus symbol into a space,
                                    // and dojo.store.JsonRest doesn't encode
                                    // the '+' it adds for sorting.
                                    $orderBy[] = $this->getColumn($field)
                                               . ' ASC';
                                }
                            }
                        }
                    }
                } else if (preg_match(
                    '/^limit\(([0-9]+),([0-9]+)\)$/', $value, $matches
                )) {
                    $limitStart = (int) $matches[1];
                    $limitCount = (int) $matches[2];
                } else {
                    throw new RuntimeException(
                        "Operator $value not implemented.", 30
                    );
                }
            } else {
                // Alternate comparison syntax
                if ('(' === substr($key, 0, 1)) {
                    throw new RuntimeException(
                        'Parenthesis-enclosed group syntax not supported. ' .
                        'Use regular operator syntax instead: ' .
                        'or(operator,operator,...)', 31
                    );
                }
                if ($this->hasColumn($key)) {
                    $column = $this->getColumn($key);
                    if (false === strpos($value, '*')) {
                        $select->where("$column = ?", $value);
                    } else {
                        $select->where("$column like ?", str_replace('*', '%', $value));
                    }
                }
            }
        }

        if (count($orderBy) > 0) {
            $select->order($orderBy);
        }

        return array($select, $limitStart, $limitCount);
    }

    /**
     * Gets database platform
     *
     * @internal We return a Doctrine DBAL class as it is necessary for Doctrine
     * DBAL type conversion (and we don't want to reinvent the weel).
     *
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected function getDatabasePlatform()
    {
        if ($this->platform === null) {
            $class = get_class($this->getAdapter());
            if (!isset(self::$platformMap[$class])) {
                throw new RuntimeException(
                    'No platform matching adapter class (' . $class .
                    ') was found in the adapter map.', 40
                );
            }
            $this->platform = new self::$platformMap[$class];
        }
        return $this->platform;
    }

    /**
     * Gets reflection properties of the entity
     *
     * @return array
     */
    protected function getProperties()
    {
        if ($this->reflProperties === null) {
            $class = new ReflectionClass($this->entityName);
            $this->reflProperties = array();
            foreach ($class->getProperties() as $property) {
                $property->setAccessible(true);
                $this->reflProperties[$property->getName()] = $property;
            }
        }
        return $this->reflProperties;
    }

    /**
     * Creates a new instance of the entity without invoking the constructor
     *
     * @return object
     */
    public function newInstance()
    {
        if ($this->prototype === null) {
            $this->prototype = unserialize(
                sprintf(
                    'O:%d:"%s":0:{}',
                    strlen($this->entityName),
                    $this->entityName
                )
            );
        }
        return clone $this->prototype;
    }

    /**
     * Gets a new proxy instance for the entity matching this gateway and
     * identified by the given primary key.
     *
     * @param array $pk Primary key
     * @return object
     */
    public function newProxyInstance(array $pk)
    {
        $parts = explode('\\', $this->entityName);
        $fqn = $this->persister->getProxyNamespace() . '\\' . end($parts) . 'Proxy';

        return new $fqn($this, $pk);
    }

    /**
     * Gets a primary key from a given entity identifier
     *
     * @param mixed $id Scalar or vector identifier of the entity
     * @return array
     */
    public function getPrimaryKey($id)
    {
        $this->_setupPrimaryKey();
        $pk = array();
        if (is_array($id)) {
            $i = 1;
            foreach ($id as $field => $value) {
                if (is_int($field)) {
                    $pk[$this->_primary[$i++]] = $value;
                } else {
                    $pk[$this->getColumn($field)] = $value;
                }
            }
        } else {
            $pk[reset($this->_primary)] = $id;
        }
        return $pk;
    }

    /**
     * Extracts the primary key values from the given row
     *
     * @param array $row Associative array of column-value pairs
     * @return array
     */
    public function extractPrimaryKey($row)
    {
        $this->_setupPrimaryKey();
        return array_intersect_key($row, array_flip($this->_primary));
    }

    /**
     * Loads an entity from a row
     *
     * With this basic implementation, all entity properties are exposed and
     * consequently may be set if their name match a column of the given row.
     *
     * <b>Override this method for custom needs.</b>
     *
     * @param object $entity Entity to load
     * @param array $row Column-value pairs
     * @return void
     */
    public function loadEntity($entity, array $row)
    {
        $props = $this->getProperties();
        foreach ($row as $column => $value) {
            if ($this->hasField($column)) {
                $field = $this->getField($column);
                $props[$field]->setValue($entity, $value);
            }
        }
    }

    /**
     * Loads a database row from the given entity
     *
     * This basic implementation processes all entity properties having a
     * matching database column.
     *
     * <b>Override this method for custom needs.</b>
     *
     * @param object $entity Entity instance
     * @return array
     */
    public function loadRow($entity)
    {
        $row = array();
        foreach ($this->getProperties() as $name => $prop) {
            // Don't process properties without matching column
            if ($this->hasColumn($name)) {
                $row[$this->getColumn($name)] = $prop->getValue($entity);
            }
        }
        return $row;
    }

    /**
     * Computes changes between two states of an entity and returns the result
     * in a database model array
     *
     * This basic implementation processes all entity properties having a
     * matching database column.
     *
     * <b>Override this method for custom needs.</b>
     *
     * @param object $old Old state of the entity
     * @param object $new New state of the entity
     * @return array Array of column-value pairs to be updated
     */
    public function computeChangeSet($old, $new)
    {
        $row = array();
        foreach ($this->getProperties() as $name => $prop) {
            // Don't process properties without matching column
            if ($this->hasColumn($name)) {
                $oldValue = $prop->getValue($old);
                $newValue = $prop->getValue($new);
                if ($oldValue !== $newValue) {
                    $row[$this->getColumn($name)] = $newValue;
                }
            }
        }

        return $row;
    }

    /**
     * Constructs a WHERE clause from the given primary key
     *
     * The returned WHERE clause is an SQL expression conforming to
     * {@link \Zend_Db_Table#update} and {@link \Zend_Db_Table#delete} API.
     *
     * @param array $pk Column-value pairs
     * @return array
     */
    protected function getWhereClause(array $pk)
    {
        $where = array();
        foreach ($pk as $column => $value) {
            $where[$column . ' = ?'] = $value;
        }
        return $where;
    }

    /**
     * Updates an entity with a database assigned key
     *
     * @param object $entity Entity to update
     * @param array $pk Primary key of the inserted row
     * @return void
     * @todo Implement type conversion
     */
    public function updateEntityIdentifier($entity, array $pk)
    {
        // Update entity's identity only if it is a database assigned key
        // (ie sequence is true or is a string)
        if ($this->_sequence) {
            $props = $this->getProperties();
            foreach ($pk as $column => $value) {
                $props[$this->getField($column)]->setValue($entity, $value);
            }
        }
    }

    /**
     * Retrieves a row matching the given primary key
     *
     * @param array $pk Primary key
     * @return array
     * @throws NoResultException If no row matching the given key is found.
     * @throws NonUniqueResultException If several rows matching the given
     * key are found.
     */
    public function retrieve(array $pk)
    {
        if (count($pk) === 1) {
            $rowset = $this->find(reset($pk));
        } else {
            $rowset = call_user_func_array(
                array($this, 'find'),
                array_values($pk)
            );
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

        return $rowset->current()->toArray();
    }

    /**
     * Inserts a new row
     *
     * @param array $data Column-value pairs.
     * @return array The primary key of the inserted row
     */
    public function insert(array $data)
    {
        $pk = parent::insert($data);

        // Re-normalize the primary key on exit as a mixed value is returned
        if (!is_array($pk)) {
            // As _setupPrimaryKey() is called at the beginning of insert(),
            // $this->_primary is necessarily an array here
            $pk = array(reset($this->_primary) => $pk);
        }
        return $pk;
    }

    /**
     * Updates an existing row
     *
     * @param array $data Column-value pairs.
     * @param array $pk Indexed array representing the primary key
     * @return int The number of rows updated.
     * @todo Enforce type hinting on primary key when refactoring the class
     */
    public function update(array $data, $pk)
    {
        return parent::update($data, $this->getWhereClause($pk));
    }

    /**
     * Deletes an existing row
     *
     * @param array $pk Indexed array representing the primary key
     * @return int The number of rows deleted.
     * @todo Enforce type hinting on primary key when refactoring the class
     */
    public function delete($pk)
    {
        return parent::delete($this->getWhereClause($pk));
    }
}