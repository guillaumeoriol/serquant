<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Service
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Service;

use Serquant\Service\Persistable,
    Serquant\Service\Exception\RuntimeException,
    Serquant\Service\Result;

/**
 * Basic service layer implementing CRUD functions for persistent entities.
 *
 * <i>Note: as we are here in a service context and not in a REST context,
 * we speak about entities and not resources.</i>
 *
 * Implements the {@link http://martinfowler.com/eaaCatalog/serviceLayer.html
 * Service Layer} [PoEAA] pattern.
 *
 * @category Serquant
 * @package  Service
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Doctrine implements Persistable
{
    /**
     * Entity class name that is managed by this service layer.
     * @var string
     */
    protected $entityClass;

    /**
     * Input filter class name used to validate input data before saving.
     * @var \Zend_Form
     */
    protected $inputFilterClass;

    /**
     * Entity manager
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * Get the entity manager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
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
     * @todo This function could be built on a ABNF parser that would use
     * its callbacks to produce the target language. We may also translate
     * to PHP the {@link http://github.com/kriszyp/rql JavaScript parser}
     * written by Kris Zyp.
     *
     * @param array $query Input RQL query
     * @param bool $limit Flag to determine if the limit is to be returned
     * (false) or written (true) in the query
     * @param int $pageNumber Requested page number
     * @param int $pageSize Requested page size (ie item count per page)
     * @return \Doctrine\ORM\Query Output Doctrine query
     */
    protected function translate(
        array $query,
        $limit = false,
        &$pageNumber = null,
        &$pageSize = null
    ) {
        $statement = "select e from {$this->entityClass} e";
        $where = array();
        $orderBy = array();
        $parameters = array();
        $paramCount = 1;
        $limitStart = null;
        $limitCount = null;

        $em = $this->getEntityManager();
        $factory = $em->getMetadataFactory();
        $entityMetadata = $factory->getMetadataFor($this->entityClass);

        foreach ($query as $key => $value) {
            if (preg_match('/^sort\((.*)\)$/', $key, $matches)) {
                $fields = explode(',', $matches[1]);
                foreach ($fields as $field) {
                    if ('-' === substr($field, 0, 1)) {
                        $orderBy[] = 'e.' . substr($field, 1) . ' DESC';
                    } else {
                        $orderBy[] = 'e.' . substr($field, 1) . ' ASC';
                    }
                }
            } else if (preg_match('/^limit\(([0-9]+),([0-9]+)\)$/', $key, $matches)) {
                $limitStart = (int) $matches[1];
                $limitCount = (int) $matches[2];
            } else {
                // Consider all other query string parameters as filters.
                // Check if the parameter is an entity property.
                if ($entityMetadata->hasField($key)) {
                    if (false === strpos($value, '*')) {
                        $where[] = "e.$key = :$key";
                        $parameters[$key] = $value;
                        $paramCount++;
                    } else {
                        $where[] = "e.$key like :$key";
                        $parameters[$key] = str_replace('*', '%', $value);
                        $paramCount++;
                    }
                }
            }
        }

        if (count($where) > 0) {
            $statement .= ' where ' . implode(' and ', $where);
        }
        if (count($orderBy) > 0) {
            $statement .= ' order by ' . implode(', ', $orderBy);
        }

        $query = $em->createQuery($statement);
        $query->setParameters($parameters);
        if (($limitStart !== null) && ($limitCount !== null)) {
            if ($limit) {
                $query->setFirstResult($limitStart);
                $query->setMaxResults($limitCount);
            } else {
                $pageNumber = ($limitStart / $limitCount) + 1;
                $pageSize = $limitCount;
            }
        }
        return $query;
    }

    /**
     * {@inheritDoc}
     *
     * @return Result
     */
    public function fetch(array $expressions = array())
    {
        try {
            $query = $this->translate($expressions);
            $collection = $query->getResult();
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                'Unable to fetch entities matching given criteria.');
        }

        return new Result(Result::STATUS_SUCCESS, $collection);
    }

    /**
     * {@inheritDoc}
     *
     * @return Result
     */
    public function fetchOne(array $expressions = array())
    {
        try {
            $query = $this->translate($expressions);
            $entity = $query->getSingleResult();
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                'Unable to fetch a single entity matching given criteria.');
        }

        return new Result(Result::STATUS_SUCCESS, $entity);
    }

    /**
     * {@inheritDoc}
     *
     * @todo Implement a simple paginator for non fetch-joined queries,
     * as explained {@link http://github.com/beberlei/DoctrineExtensions here}.
     *
     * <pre>
	 * $count = Paginate::getTotalQueryResults($query);
     * $result = $query->setFirstResult($offset)
     *                 ->setMaxResults($limitPerPage)
     *                 ->getResult();
     * </pre>
     *
     * @return Result
     */
    public function fetchPage(array $expressions = array())
    {
        try {
            $query = $this->translate($expressions, false, $pageNumber, $pageSize);
            $adapter = new \DoctrineExtensions\Paginate\PaginationAdapter($query);
            $paginator = new \Zend_Paginator($adapter);
            if (($pageNumber !== null) && ($pageSize !== null)) {
                $paginator->setCurrentPageNumber($pageNumber)
                          ->setItemCountPerPage($pageSize);
            }
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                'Unable to fetch paginated entities matching criteria.');
        }

        return new Result(Result::STATUS_SUCCESS, $paginator);
    }

    /**
     * {@inheritDoc}
     *
     * @return Result
     */
    public function getDefault()
    {
        try {
            $entity = new $this->entityClass;
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                'Unable to get default value of the entity.');
        }

        return new Result(Result::STATUS_SUCCESS, $entity);
    }

    /**
     * {@inheritDoc}
     *
     * @return Result
     */
    public function create(array $data)
    {
        try {
            $inputFilter = new $this->inputFilterClass;
            if ($inputFilter->isValid($data)) {
                $entity = new $this->entityClass;
                $entity->populate($inputFilter);
                $em = $this->getEntityManager();
                $em->persist($entity);
                $em->flush();
                $status = Result::STATUS_SUCCESS;
                $violations = null;
            } else {
                $status = Result::STATUS_VALIDATION_ERROR;
                $entity = $inputFilter->getUnfilteredValues();
                $violations = $inputFilter->getMessages();
            }
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                'Unable to create entity.');
        }

        return new Result($status, $entity, $violations);
    }

    /**
     * {@inheritDoc}
     *
     * @return Result
     */
    public function retrieve($id = null)
    {
        if ($id === null) {
            throw new InvalidArgumentException(
                'Unable to retrieve entity: the identifier is missing.');
        }

        try {
            $em = $this->getEntityManager();
            $entity = $em->find($this->entityClass, $id);
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                "Unable to retrieve entity matching id $id.");
        }

        return new Result(Result::STATUS_SUCCESS, $entity);
    }

    /**
     * {@inheritDoc}
     *
     * @return Result
     */
    public function update($id, array $data)
    {
        if ($id === null) {
            throw new InvalidArgumentException(
                'Unable to update entity: the identifier is missing.');
        }

        try {
            $inputFilter = new $this->inputFilterClass;
            if ($inputFilter->isValid($data)) {
                $em = $this->getEntityManager();
                $entity = $em->find($this->entityClass, $id);
                $entity->populate($inputFilter);
                $em->flush();
                $status = Result::STATUS_SUCCESS;
                $violations = null;
            } else {
                $status = Result::STATUS_VALIDATION_ERROR;
                $entity = $inputFilter->getUnfilteredValues();
                $violations = $inputFilter->getMessages();
            }
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                "Unable to update entity matching id $id.");
        }

        return new Result($status, $entity, $violations);
    }

    /**
     * {@inheritDoc}
     *
     * @return Result
     */
    public function delete($id = null)
    {
        if ($id === null) {
            throw new InvalidArgumentException(
                'Unable to delete entity: the identifier is missing.');
        }

        try {
            $em = $this->getEntityManager();
            $entity = $em->getReference($this->entityClass, $id);
            $em->remove($entity);
            $em->flush();
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                "Unable to delete entity matching id $id.");
        }

        return new Result(Result::STATUS_SUCCESS, $entity);
    }
}