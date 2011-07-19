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

use Doctrine\DBAL\Platforms\AbstractPlatform,
    Doctrine\DBAL\Types\Type,
    Doctrine\ORM\Mapping\ClassMetadataFactory,
    Serquant\Doctrine\Exception\InvalidArgumentException,
    Serquant\Entity\Registry\Registrable;

/**
 * Registry of all loaded entities for non-ORM persistence layer.
 *
 * This class implements an
 * <a href="http://martinfowler.com/eaaCatalog/identityMap.html">Identity
 * Map</a>.
 *
 * Entities may have no identifier at all at some point of their lifecycle
 * (ie before they are persisted). But only persisted entities may be registered
 * in this Identity Map. Once persisted, they all get an identifier. Therefore,
 * entities are registered under their identifier (combined with their class
 * name for uniqueness).
 * Under certain circumstances, only their identifier is known and no entity
 * object is available to get its hash from. Thus, we need two different ways
 * to get entities from the registry: by their hash and by their identifier.
 *
 * @category Serquant
 * @package  Entity
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Ormless implements Registrable
{
    /**
     * Factory used to retrieve metadata of entity classes.
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * Identity map of the managed entities.
     * The key is made of the root class name and the entity identifier,
     * and the value is the entity by itself.
     * @var array
     */
    private $identityMap = array();

    /**
     * Map betweend the entity hash and the entity identifier.
     * @var array
     */
    private $hashToIdMap = array();

    /**
     * Map of the original entity data of managed entities.
     * Keys are object ids (spl_object_hash). This is used for calculating
     * changesets at commit time.
     *
     * @var array
     * @internal Note that PHPs "copy-on-write" behavior helps a lot with memory
     * usage. A value will only really be copied if the value in the entity is
     * modified by the user.
     */
    private $originalEntityData = array();

    /**
     * Constructor
     *
     * @param ClassMetadataFactory $metadataFactory Metadata factory
     * @return void
     */
    public function __construct(ClassMetadataFactory $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Put the given entity in the registry.
     *
     * If the identifier was passed as an argument, we could expect some
     * performance benefit (as it is computed earlier). On the other hand,
     * the caller would be able to pass a wrong value and thus corrupt the
     * identity map. For reliability reasons and to de-couple the registry
     * from the persistence layer, the identity is directly extracted from
     * the entity by this function.
     * This function ressembles Doctrine\ORM\UnitOfWork#addToIdentityMap
     *
     * @param object $entity The entity to register
     * @return boolean TRUE if the registration was successful, FALSE if
     * the identity of the entity in question is already managed.
     * @throws InvalidArgumentException If the given entity has no identity.
     */
    public function put($entity)
    {
        $className = get_class($entity);
        $class = $this->metadataFactory->getMetadataFor($className);

        $id = array();
        foreach ($class->getIdentifierFieldNames() as $field) {
            $value = $class->reflFields[$field]->getValue($entity);
            if ($value === null) {
                throw new InvalidArgumentException(
                    'The given entity has no identity.'
                );
            }
            $id[$field] = $value;
        }
        $idHash = implode(' ', $id);

        $className = $class->rootEntityName;
        if (isset($this->identityMap[$className][$idHash])) {
            return false;
        }

        $oid = spl_object_hash($entity);
        $this->hashToIdMap[$oid] = $id;
        $this->identityMap[$className][$idHash] = $entity;
        $this->originalEntityData[$oid] = clone $entity;

        if ($entity instanceof NotifyPropertyChanged) {
            $entity->addPropertyChangedListener($this);
        }
        return true;
    }

    /**
     * Get an entity from its identifier only.
     *
     * @param string $entityName Class name of the entity to retrieve
     * @param mixed $id Identifier of the entity to retrieve
     * @return mixed Returns the entity with the specified identifier
     * if it exists in this registry, FALSE otherwise.
     */
    public function tryGetById($entityName, $id)
    {
        $class = $this->metadataFactory->getMetadataFor($entityName);

        if (is_array($id)) {
            $idHash = implode(' ', $id);
        } else {
            $idHash = $id;
        }

        $className = $class->rootEntityName;
        if (!isset($this->identityMap[$className][$idHash])) {
            return false;
        }

        return $this->identityMap[$className][$idHash];
    }

    /**
     * Get an entity from the associative array representation of the database
     * row.
     *
     * @param string $entityName Class name of the entity to retrieve.
     * @param array $row Associative array representing the database row.
     * @return mixed Returns the entity with the specified identifier
     * if it exists in this registry, FALSE otherwise.
     * @throws InvalidArgumentException If the identifier column is not set.
     */
    public function tryGetByRow($entityName, array $row)
    {
        $class = $this->metadataFactory->getMetadataFor($entityName);
        $id = array();
        foreach ($class->getIdentifierColumnNames() as $column) {
            if (!isset($row[$column])) {
                throw new InvalidArgumentException(
                    'The given entity has no identity.'
                );
            }
            $id[] = $row[$column];
        }

        $idHash = implode(' ', $id);
        $className = $class->rootEntityName;
        if (!isset($this->identityMap[$className][$idHash])) {
            return false;
        }

        return $this->identityMap[$className][$idHash];
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
        return $this->hashToIdMap[spl_object_hash($entity)];
    }

    /**
     * Checks if the given entity is registered or not.
     *
     * @param object $entity The entity to check.
     * @return bool TRUE if the entity is registered; otherwise FALSE.
     */
    public function hasEntity($entity)
    {
        return isset($this->hashToIdMap[spl_object_hash($entity)]);
    }

    /**
     * Computes the changes that happened to an entity.
     *
     * @param object $entity Entity containing updated values to be persisted.
     * @param AbstractPlatform $platform Database platform
     * @return array Array of column names/database value pairs to be updated.
     */
    public function computeChangeSet($entity, AbstractPlatform $platform)
    {
        $class = $this->metadataFactory->getMetadataFor(get_class($entity));
        $oid = spl_object_hash($entity);
        $original = $this->originalEntityData[$oid];

        $changeSet = array();
        foreach ($class->fieldMappings as $fieldName => $field) {
            $orgValue = $class->reflFields[$fieldName]->getValue($original);
            $actualValue = $class->reflFields[$fieldName]->getValue($entity);
            if (is_object($orgValue) && $orgValue !== $actualValue) {
                $changeSet[$field['columnName']]
                    = Type::getType($field['type'])
                        ->convertToDatabaseValue($actualValue, $platform);
            } else if ($orgValue != $actualValue
                || ($orgValue === null ^ $actualValue === null)
            ) {
                $changeSet[$field['columnName']]
                    = Type::getType($field['type'])
                        ->convertToDatabaseValue($actualValue, $platform);
            }
        }
        return $changeSet;
    }

    /**
     * Commit changes to the entity, replacing its old value by the new one.
     *
     * Following this function call, {@link computeChangeSet()} returns an empty
     * array. The only purpose of this function is to implement a two-phase
     * commit to be able to restore the old value of an entity in case of update
     * failure.
     *
     * @param object $entity Entity to be committed
     * @return void
     */
    public function commitChangeSet($entity)
    {
        $oid = spl_object_hash($entity);
        unset($this->originalEntityData[$oid]);
        $this->originalEntityData[$oid] = clone $entity;
    }

    /**
     * Removes an entity from the registry.
     *
     * @param object $entity Entity to be removed
     * @return int FALSE if the entity could not be found in the registry;
     * otherwise TRUE.
     */
    public function remove($entity)
    {
        $oid = spl_object_hash($entity);
        if (!isset($this->hashToIdMap[$oid])) {
            return false;
        }

        $class = $this->metadataFactory->getMetadataFor(get_class($entity));
        $className = $class->rootEntityName;
        $idHash = implode(' ', $this->hashToIdMap[$oid]);

        unset($this->identityMap[$className][$idHash]);
        unset($this->originalEntityData[$oid]);
        unset($this->hashToIdMap[$oid]);
        return true;
    }
}