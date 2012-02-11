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

use Doctrine\Common\PropertyChangedListener;
use Serquant\Entity\Exception\InvalidArgumentException;
use Serquant\Entity\Exception\NotImplementedException;

/**
 * Registry of all loaded entities for non-ORM persistence layer.
 *
 * This class implements a
 * <a href="http://martinfowler.com/eaaCatalog/registry.html">Registry</a> of
 * <a href="http://martinfowler.com/eaaCatalog/identityMap.html">Identity
 * Maps</a>.
 *
 * Entities may have no identifier at all at some point of their lifecycle
 * (ie before they are persisted). But only persisted entities may be registered
 * in this Identity Map. As once persisted, they all get an identifier, entities
 * are registered under their identifier (combined with their class name for
 * uniqueness).
 *
 * However, under certain circumstances, only their identifier is known and no
 * entity object is available to get its hash from. Thus, we need two different
 * ways to get entities from the registry: by hash and by identifier.
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class IdentityMap implements Registrable, PropertyChangedListener
{
    /**
     * Identity map of the managed entities.
     * The key consists of the root class name and the entity identifier.
     * The value is the entity by itself.
     * @var array
     */
    private $identityMap = array();

    /**
     * Map between the entity hash and its identifier.
     * @var array
     */
    private $hashToIdMap = array();

    /**
     * Original state of managed entities.
     * Key is an <a href="http://php.net/manual/en/function.spl-object-hash.php">
     * object hash</a>. This is used for calculating changeset at commit time.
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
     * Gets the registered entity of given name and identifier.
     *
     * @param string $className Class name of the entity
     * @param array $id Entity identifier
     * @return object The registered entity or NULL if no entity is found
     */
    public function get($className, array $id)
    {
        $className = $this->getRootClass($className);
        $idHash = implode(' ', $id);
        if (!isset($this->identityMap[$className][$idHash])) {
            return null;
        }

        return $this->identityMap[$className][$idHash];
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
        return isset($this->hashToIdMap[spl_object_hash($entity)]);
    }

    /**
     * Puts the given entity in the registry
     *
     * @param object $entity The entity instance to register
     * @param array $id Entity identifier
     * @return boolean TRUE if the registration was successful, FALSE if
     * the entity is already present in the registry
     * @throws InvalidArgumentException If no identity is provided.
     */
    public function put($entity, array $id)
    {
        if (empty($id)) {
            throw new InvalidArgumentException(
                'The given entity ' . get_class($entity) . ' has no identity.'
            );
        }

        $className = $this->getRootClass($entity);
        $idHash = implode(' ', $id);
        if (isset($this->identityMap[$className][$idHash])) {
            return false;
        }

        $oid = spl_object_hash($entity);
        $this->hashToIdMap[$oid] = $id;
        $this->identityMap[$className][$idHash] = $entity;
        $this->originalEntities[$oid] = clone $entity;
        return true;
    }

    /**
     * Gets the identifier of the given entity
     *
     * @param object $entity Entity instance
     * @return array
     */
    public function getId($entity)
    {
        return $this->hashToIdMap[spl_object_hash($entity)];
    }

    /**
     * Commit changes to the entity, replacing its old state by the new one.
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
        if (!isset($this->hashToIdMap[$oid])) {
            return false;
        }

        $className = $this->getRootClass($entity);
        $idHash = implode(' ', $this->hashToIdMap[$oid]);

        unset(
            $this->identityMap[$className][$idHash],
            $this->originalEntities[$oid],
            $this->hashToIdMap[$oid]
        );
        return true;
    }

    /**
     * Executes listeners when a property value changes
     *
     * @param object $entity Entity instance
     * @param string $propertyName Name of the property
     * @param mixed $oldValue Old value
     * @param mixed $newValue New value
     * @return void
     */
    public function propertyChanged($entity, $propertyName, $oldValue, $newValue)
    {
        throw new NotImplementedException('This method is not yet implemented');
    }
}