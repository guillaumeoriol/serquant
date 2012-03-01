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

use Serquant\Entity\Exception\InvalidArgumentException;
use Serquant\Persistence\Zend\Persister;

/**
 * Registry of all loaded entities for non-ORM persistence layer.
 *
 * This class implements a
 * {@link http://martinfowler.com/eaaCatalog/registry.html Registry} of
 * {@link http://martinfowler.com/eaaCatalog/identityMap.html Identity Maps}.
 *
 * Entities may have no identifier at all at some point of their lifecycle
 * (ie before they are persisted). But only persisted entities may be registered
 * in this Identity Map. As, once persisted, they all have such an identifier,
 * entities are registered under a key consisting of this identifier combined
 * with their class name (for uniqueness).
 *
 * However, under certain circumstances, only their identifier is known and no
 * entity object is available to get its hash from. Thus, we need two different
 * ways to get entities from the registry: by hash and by identifier.
 *
 * As stated above, the key of this map consists of the entity identifier
 * combined with the class name. The entity identifier is the primary key of
 * of the corresponding database table in the form of an associative array whose
 * keys are column names. Array keys of the identifier are useless in
 * {@link get} and {@link put} methods, but they matter in {@link getPrimaryKey}
 * as they are used in {@link Persister::update} and {@link Persister::delete}
 * as an argument passed to the corresponding method of the gateway.
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class IdentityMap
{
    /**
     * Identity map of the managed entities.
     * The key consists of the root class name and the entity identifier.
     * The value is the entity by itself.
     * @var array
     */
    private $identityMap = array();

    /**
     * Map between the entity hash and its primary key.
     * @var array
     */
    private $hashToPkMap = array();

    /**
     * Original state of managed entities.
     * Key is an {@link http://php.net/manual/en/function.spl-object-hash.php
     * object hash}. This is used for calculating changeset at commit time.
     * @var array
     * @internal Note that PHPs "copy-on-write" behavior helps a lot with memory
     * usage. A value will only really be copied if the value in the entity is
     * modified by the user.
     */
    private $originalEntities = array();

    /**
     * Gets the root class name of a class
     *
     * @param string|object $entity Class instance or class name
     * @return string
     * @internal Is it a good idea to enable class autoloading on class_parents?
     */
    protected function getRootClass($entity)
    {
        $parents = class_parents($entity, true);
        $root = $parents ? end($parents)
                         : (is_object($entity) ? get_class($entity) : $entity);
        return $root;
    }

    /**
     * Computes a hash of the given primary key
     *
     * @param array $pk Primary key
     * @return string
     */
    protected function hash(array $pk)
    {
        return implode(' ', $pk);
    }

    /**
     * Gets the entity matching the given name and primary key.
     *
     * @param string $className Class name of the entity
     * @param array $pk Primary key
     * @return object The registered entity or NULL if no entity is found
     */
    public function get($className, array $pk)
    {
        $className = $this->getRootClass($className);
        $pkHash = $this->hash($pk);
        if (!isset($this->identityMap[$className][$pkHash])) {
            return null;
        }

        return $this->identityMap[$className][$pkHash];
    }

    /**
     * Gets the original state of the given entity
     *
     * @param object $entity Entity instance
     * @return object
     */
    public function getOriginal($entity)
    {
        return $this->originalEntities[spl_object_hash($entity)];
    }

    /**
     * Checks if the given entity is registered or not
     *
     * @param object $entity Entity instance to check
     * @return bool TRUE if the entity is registered; otherwise FALSE.
     */
    public function has($entity)
    {
        return isset($this->hashToPkMap[spl_object_hash($entity)]);
    }

    /**
     * Puts the given entity into the registry
     *
     * @param object $entity The entity instance to register
     * @param array $pk Primary key
     * @return boolean TRUE if the registration was successful, FALSE if
     * the entity is already present in the registry
     * @throws InvalidArgumentException If no identity is provided.
     */
    public function put($entity, array $pk)
    {
        if (empty($pk)) {
            throw new InvalidArgumentException(
                'The given entity ' . get_class($entity) . ' has no primary key.'
            );
        }

        $className = $this->getRootClass($entity);
        $pkHash = $this->hash($pk);
        if (isset($this->identityMap[$className][$pkHash])) {
            return false;
        }

        $oid = spl_object_hash($entity);
        $this->hashToPkMap[$oid] = $pk;
        $this->identityMap[$className][$pkHash] = $entity;
        $this->originalEntities[$oid] = clone $entity;
        return true;
    }

    /**
     * Gets the primary key of the given entity
     *
     * @param object $entity Entity instance
     * @return array
     */
    public function getPrimaryKey($entity)
    {
        return $this->hashToPkMap[spl_object_hash($entity)];
    }

    /**
     * Commits changes to the entity, replacing its old state by the new one.
     *
     * Following this function call,
     * {@link Serquant\Persistence\Zend\Db\Table#computeChangeSet} returns an
     * empty array. The only purpose of this function is to implement a two-phase
     * commit to be able to restore the old value of an entity in case of update
     * failure.
     *
     * @param object $entity Entity instance to be committed
     * @return void
     */
    public function commit($entity)
    {
        $oid = spl_object_hash($entity);
        unset($this->originalEntities[$oid]);
        $this->originalEntities[$oid] = clone $entity;
    }

    /**
     * Removes an entity from the registry
     *
     * @param object $entity Entity instance to be removed
     * @return boolean FALSE if the entity could not be found in the registry;
     * otherwise TRUE.
     */
    public function remove($entity)
    {
        $oid = spl_object_hash($entity);
        if (!isset($this->hashToPkMap[$oid])) {
            return false;
        }

        $className = $this->getRootClass($entity);
        $pkHash = $this->hash($this->hashToPkMap[$oid]);

        unset(
            $this->identityMap[$className][$pkHash],
            $this->originalEntities[$oid],
            $this->hashToPkMap[$oid]
        );
        return true;
    }
}