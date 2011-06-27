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

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\UnitOfWork,
    DoctrineExtensions\Paginate\PaginationAdapter,
    Serquant\Persistence\Persistence,
    Serquant\Persistence\Exception\NoResultException,
    Serquant\Persistence\Exception\NonUniqueResultException,
    Serquant\Persistence\Exception\RuntimeException;

/**
 * Persistence layer using Doctrine ORM to persist entities.
 *
 * @category Serquant
 * @package  Persistence
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Doctrine implements Persistence
{
    /**
     * Entity manager
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * Set the entity manager.
     *
     * @param \Doctrine\ORM\EntityManager $em Doctrine entity manager
     * @return void
     */
    public function setEntityManager(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * Get the entity manager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        if ($this->entityManager === null) {
            $front = \Zend_Controller_Front::getInstance();
            $container = $front->getParam('bootstrap')->getContainer();
            $this->entityManager = $container->doctrine;
        }
        return $this->entityManager;
    }

    /**
     * Translates a RQL ({@link https://github.com/kriszyp/rql Resource Query
     * Language}) query into a DQL ({@link http://www.doctrine-project.org/
     * Doctrine Query Language}) query.
     *
     * Filtering, ranging and sorting criteria may be specified through the
     * expression array as defined by the {@link Service#fetchAll()} method
     * of the domain service layer.
     *
     * @param string $entityName Class name of the entity
     * @param array $expressions RQL query
     * @return \Doctrine\ORM\Query Output Doctrine query
     * @throws RuntimeException If non-implemented operator is used, if the sort
     * order is not specified or if a parenthesis-enclosed group syntax is used.
     * @todo This function could be built on a ABNF parser that would use
     * its callbacks to produce the target language. We may also translate
     * to PHP the JavaScript parser written by Kris Zyp (see
     * http://github.com/kriszyp/rql).
     */
    protected function translate($entityName, array $expressions)
    {
        $pageNumber = $pageSize = null;
        if (count($expressions) === 0) {
            return array("select e from $entityName e", $pageNumber, $pageSize);
        }

        $select = array();
        $where = array();
        $orderBy = array();
        $parameters = array();
        $limitStart = $limitCount = null;

        $em = $this->getEntityManager();
        $factory = $em->getMetadataFactory();
        $entityMetadata = $factory->getMetadataFor($entityName);

        foreach ($expressions as $key => $value) {
            if (is_int($key)) {
                // Regular operator syntax
                if (preg_match('/^select\((.*)\)$/', $value, $matches)) {
                    $fields = explode(',', $matches[1]);
                    foreach ($fields as $field) {
                        if ($entityMetadata->hasField($field)) {
                            $select[] = 'e.' . $field;
                        }
                    }
                } else if (preg_match('/^sort\((.*)\)$/', $value, $matches)) {
                    $fields = explode(',', $matches[1]);
                    foreach ($fields as $field) {
                        if ('-' === substr($field, 0, 1)) {
                            $orderBy[] = 'e.' . substr($field, 1) . ' DESC';
                        } else if ('+' === substr($field, 0, 1)) {
                            $orderBy[] = 'e.' . substr($field, 1) . ' ASC';
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
                if ($entityMetadata->hasField($key)) {
                    // Check if the parameter is actually an entity property.
                    if (false === strpos($value, '*')) {
                        $where[] = "e.$key = :$key";
                        $parameters[$key] = $value;
                    } else {
                        $where[] = "e.$key like :$key";
                        $parameters[$key] = str_replace('*', '%', $value);
                    }
                }
            }
        }

        if (count($select) > 0) {
            $dql = 'select ' . implode(',', $select);
        } else {
            $dql = 'select e';
        }
        $dql .= " from $entityName e";
        if (count($where) > 0) {
            $dql .= ' where ' . implode(' and ', $where);
        }
        if (count($orderBy) > 0) {
            $dql .= ' order by ' . implode(', ', $orderBy);
        }

        $query = $em->createQuery($dql);
        $query->setParameters($parameters);

        if (($limitStart !== null) && ($limitCount !== null)) {
            $pageNumber = ($limitStart / $limitCount) + 1;
            $pageSize = $limitCount;
        }
        return array($query, $pageNumber, $pageSize);
    }

    /**
     * {@inheritDoc}
     *
     * @param string|object $entity The argument may be the entity or its id
     * in the identity map
     * @return bool TRUE when the entity is in a managed state; otherwise FALSE.
     */
    public function isInManagedState($entity)
    {
        $em = $this->getEntityManager();
        $uow = $em->getUnitOfWork();
        return ($uow->getEntityState($entity) === UnitOfWork::STATE_MANAGED);
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
        list ($query) = $this->translate($entityName, $expressions);

        $entities = $query->getResult();
        return $entities;
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
        list ($query) = $this->translate($entityName, $expressions);

        try {
            $entity = $query->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            // Recast exception thrown when no item was found
            // to comply with interface requirements
            throw new NoResultException($e->getMessage());
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            // Recast exception thrown when several items were found
            // to comply with interface requirements
            throw new NonUniqueResultException($e->getMessage());
        }

        return $entity;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $entityName Entity class name
     * @param array $expressions Fetch criteria
     * @return \Zend_Paginator Paginator
     * @todo Implement a simple paginator for non fetch-joined queries,
     * as explained here: http://github.com/beberlei/DoctrineExtensions
     */
    public function fetchPage($entityName, array $expressions)
    {
        list ($query, $pageNumber, $pageSize)
            = $this->translate($entityName, $expressions);

        $adapter = new PaginationAdapter($query);
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
        list ($query) = $this->translate($entityName, $expressions);
        $data = $query->getArrayResult();

        $pairs = array();
        foreach ($data as $row) {
            $pairs[$row[$idProperty]] = $row[$labelProperty];
        }
        return $pairs;
    }

    /**
     * {@inheritDoc}
     *
     * @param object $entity The new entity to persist
     * @return void
     */
    public function create($entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
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
        $em = $this->getEntityManager();
        $entity = $em->find($entityName, $id);
        return $entity;
    }

    /**
     * {@inheritDoc}
     *
     * @param object $entity The existing entity to persist
     * @return void
     * @throws RuntimeException If the given identity is not managed.
     */
    public function update($entity)
    {
        $em = $this->getEntityManager();
        if (!$this->isInManagedState($entity)) {
            throw new RuntimeException(
                'No managed entity of class ' . get_class($entity)
                . ' could be found. Update failed.'
            );
        }

        $em->flush();
    }

    /**
     * {@inheritDoc}
     *
     * @param object $entity The existing entity to delete
     * @return void
     * @throws RuntimeException If the given identity is not managed.
     */
    public function delete($entity)
    {
        $em = $this->getEntityManager();
        if (!$this->isInManagedState($entity)) {
            throw new RuntimeException(
                'No managed entity of class ' . get_class($entity)
                . ' could be found. Update failed.'
            );
        }

        $em->remove($entity);
        $em->flush();
    }
}