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

use Serquant\Persistence\Storable,
    Serquant\Service\Persistable,
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
class Crud implements Persistable
{
    /**
     * Persistence layer
     * @var \Serquant\Persistence\Storable
     */
    private $persister;

    /**
     * Entity class name that is managed by this service layer.
     * @var string
     */
    protected $entityName;

    /**
     * Input filter class name used to validate input data before saving.
     * @var string
     */
    protected $inputFilterName;

    /**
     * Constructor
     *
     * @param string $entityName Entity class name
     * @param string $inputFilterName Input filter class name
     * @param Storable $persister Persistence layer
     */
    public function __construct($entityName, $inputFilterName, Storable $persister)
    {
        $this->entityName = $entityName;
        $this->inputFilterName = $inputFilterName;
        $this->persister = $persister;
    }

    /**
     * Get the persistence layer manager.
     *
     * @return Storable
     */
    protected function getPersister()
    {
        if ($this->persister === null) {
            $front = \Zend_Controller_Front::getInstance();
            $container = $front->getParam('bootstrap')->getContainer();
            $this->persister = $container->doctrine;
        }
        return $this->persister;
    }

    /**
     * {@inheritDoc}
     *
     * @param array $expressions Array of query expressions
     * @return Result
     * On success, Result#getStatus() returns 0 and Result#getData() returns
     * the fetched collection of entities.
     * @throws RuntimeException If an error occurs in the persistence layer.
     */
    public function fetchAll(array $expressions = array())
    {
        $persister = $this->getPersister();
        try {
            $entities = $persister->fetchAll($this->entityName, $expressions);
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e)
                . 'Unable to fetch entities matching given criteria.'
            );
        }

        return new Result(Result::STATUS_SUCCESS, $entities);
    }

    /**
     * {@inheritDoc}
     *
     * @param array $expressions Array of query expressions.
     * @return Result
     * On success, Result#getStatus() returns 0 and Result#getData() returns
     * the fetched entity.
     * @throws RuntimeException If an error occurs in the persistence layer.
     */
    public function fetchOne(array $expressions = array())
    {
        $persister = $this->getPersister();
        try {
            $entity = $persister->fetchOne($this->entityName, $expressions);
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e)
                . 'Unable to fetch a single entity matching given criteria.'
            );
        }

        return new Result(Result::STATUS_SUCCESS, $entity);
    }

    /**
     * {@inheritDoc}
     *
     * @param array $expressions Array of query expressions.
     * @return Result
     * On success, Result#getStatus() returns 0 and Result#getData() returns
     * a \Zend_Paginator instance.
     * @throws RuntimeException If an error occurs in the persistence layer.
     */
    public function fetchPage(array $expressions = array())
    {
        $persister = $this->getPersister();
        try {
            $paginator = $persister->fetchPage($this->entityName, $expressions);
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e)
                . 'Unable to fetch paginated entities matching criteria.'
            );
        }

        return new Result(Result::STATUS_SUCCESS, $paginator);
    }

    /**
     * {@inheritDoc}
     *
     * @return Result Result#getStatus() always returns 0 and Result#getData()
     * returns the new entity.
     * @throws RuntimeException If an error occurs during entity instantiation.
     */
    public function getDefault()
    {
        try {
            $entity = new $this->entityName;
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e)
                . 'Unable to get default value of the entity.'
            );
        }

        return new Result(Result::STATUS_SUCCESS, $entity);
    }

    /**
     * Populate the entity from the input filter
     *
     * @param object $entity Entity to populate
     * @param \Zend_Form $inputFilter Input filter to populate from
     * @return void
     * @throws RuntimeException If a setter method is not available for
     * a property.
     */
    protected function populate($entity, \Zend_Form $inputFilter)
    {
        if ($inputFilter->isArray()) {
            throw new RuntimeException(
                'Form \'' . get_class($inputFilter) . '\' shall not use the ' .
                'array notation (ie call setElementsBelongTo() method) as ' .
                'this service does not implement the logic for this notation.'
            );
        }

        foreach ($inputFilter->getElements() as $name => $element) {
            if (!$element->getIgnore()) {
                $method = 'set' . ucfirst($name);
                if (method_exists($entity, $method)) {
                    $value = $element->getValue();
                    call_user_func(array($entity, $method), $value);
                } else {
                    throw new RuntimeException(
                        'No setter method defined for field \'' . $name
                        . '\' of entity ' . get_class($entity)
                    );
                }
            }
        }

        foreach ($inputFilter->getSubForms() as $name => $subForm) {
            $this->populate($entity, $subForm);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param array $data Input data, in the form of name/value pairs.
     * @return Result
     * On success, Result#getStatus() returns 0 (no error) and Result#getData()
     * returns the created entity.<br> When validation fails,
     * Result#getStatus() returns 1 (validation error) and Result#getErrors()
     * returns a collection of constraint violations.
     * @throws RuntimeException If an error occurs in persistence layer
     * (other than validation failure).
     */
    public function create(array $data)
    {
        $persister = $this->getPersister();
        try {
            $inputFilter = new $this->inputFilterName;
            if ($inputFilter->isValid($data)) {
                $entity = new $this->entityName;
                $this->populate($entity, $inputFilter);
                $persister->create($entity);
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
                $this->getSanitizedException($e) . 'Unable to create entity.'
            );
        }

        return new Result($status, $entity, $violations);
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $id Entity identifier (the id may be of scalar type,
     * or a vector value when a compound key is used).
     * @return Result
     * On success, Result#getStatus() returns 0 (no error) and Result#getData()
     * returns the retrieved entity.
     * @throws InvalidArgumentException When the identifier is missing.
     * @throws RuntimeException If an error occurs in persistence layer.
     */
    public function retrieve($id = null)
    {
        if ($id === null) {
            throw new InvalidArgumentException(
                'Unable to retrieve entity: the identifier is missing.'
            );
        }

        $persister = $this->getPersister();
        try {
            $entity = $persister->retrieve($this->entityName, $id);
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e)
                . "Unable to retrieve entity matching id $id."
            );
        }

        return new Result(Result::STATUS_SUCCESS, $entity);
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $id Entity identifier (the id may be of scalar type,
     * or a vector value when a compound key is used).
     * @param array $data Input data, in the form of name/value pairs.
     * Names shall match entity field names.
     * @return Result
     * On success, Result#getStatus() returns 0 (no error) and Result#getData()
     * returns the updated entity.<br> When validation fails,
     * Result#getStatus() returns 1 (validation error) and Result#getErrors()
     * returns a collection of constraint violations.
     * @throws InvalidArgumentException When the identifier is missing.
     * @throws RuntimeException If an error occurs in persistence layer
     * (other than validation failure).
     */
    public function update($id, array $data)
    {
        if ($id === null) {
            throw new InvalidArgumentException(
                'Unable to update entity: the identifier is missing.'
            );
        }

        $persister = $this->getPersister();
        try {
            $inputFilter = new $this->inputFilterName;
            if ($inputFilter->isValid($data)) {
                $entity = $persister->retrieve($this->entityName, $id);
                $this->populate($entity, $inputFilter);
                $persister->update($entity);
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
                $this->getSanitizedException($e)
                . "Unable to update entity matching id $id."
            );
        }

        return new Result($status, $entity, $violations);
    }

    /**
     * {@inheritDoc}
     *
     * @param mixed $id Entity identifier (the id may be of scalar type,
     * or a vector value when a compound key is used).
     * @return Result
     * On success, Result#getStatus() returns 0 (no error) and Result#getData()
     * returns the deleted entity.
     * @throws InvalidArgumentException When the identifier is missing.
     * @throws RuntimeException If an error occurs in persistence layer.
     */
    public function delete($id = null)
    {
        if ($id === null) {
            throw new InvalidArgumentException(
                'Unable to delete entity: the identifier is missing.'
            );
        }

        $persister = $this->getPersister();
        try {
            $entity = $persister->retrieve($this->entityName, $id);
            $persister->delete($entity);
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e)
                . "Unable to delete entity matching id $id."
            );
        }

        return new Result(Result::STATUS_SUCCESS, $entity);
    }

    /**
     * Return a sanitized representation of the given exception.
     *
     * Implements the {@link http://www.soapatterns.org/exception_shielding.php
     * Exception Shielding} pattern with logging.
     *
     * @param \Exception $exception The exception to sanitize
     * @return string The sanitized message.
     */
    protected function getSanitizedException(\Exception $exception)
    {
        $id = uniqid('shield-', true);

        $front = \Zend_Controller_Front::getInstance();
        $container = $front->getParam('bootstrap')->getContainer();
        if ($log = $container->log) {
            $log->err(
                "[errorId:$id] " . $exception->getMessage() . PHP_EOL
                . $exception->getTraceAsString()
            );
        }

        return "An error has occurred while running service [errorId:$id]. ";
    }
}