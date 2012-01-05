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

use Serquant\Entity\Serializer;
use Serquant\Persistence\Persistence;
use Serquant\Service\ServiceInterface;
use Serquant\Service\Exception\InvalidArgumentException;
use Serquant\Service\Exception\RuntimeException;
use Serquant\Service\Result;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

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
class Crud implements ServiceInterface
{
    /**
     * Entity class name that is managed by this service layer.
     * @var string
     */
    protected $entityName;

    /**
     * Persistence layer
     * @var Persistence
     */
    private $persister;

    /**
     * Entity serializer.
     * @var Serializer
     */
    private $serializer;

    /**
     * Constructor
     *
     * @param string $entityName Entity class name
     * @param Persistence $persister Persistence layer
     */
    public function __construct($entityName, Persistence $persister)
    {
        $this->entityName = $entityName;
        $this->persister = $persister;
    }

    /**
     * Get a service from the dependency injection container.
     *
     * @param string $name Service name
     * @return mixed
     */
    protected function get($name)
    {
        $front = \Zend_Controller_Front::getInstance();
        $container = $front->getParam('bootstrap')->getContainer();
        return $container->{$name};
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
        try {
            $entities = $this->persister->fetchAll($this->entityName, $expressions);
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
        try {
            $entity = $this->persister->fetchOne($this->entityName, $expressions);
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
        try {
            $paginator
                = $this->persister->fetchPage($this->entityName, $expressions);
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
     * @param string $idProperty Property name representing the identifier.
     * @param string $labelProperty Property name representing the label.
     * @param array $expressions Array of query expressions.
     * @return Result
     * On success, Result#getStatus() returns 0 and Result#getData() returns
     * an array consisting of id/label pairs.
     * @throws RuntimeException If an error occurs in the persistence layer.
     */
    public function fetchPairs($idProperty = 'id', $labelProperty = 'name',
        array $expressions = array()
    ) {
        foreach ($expressions as $key => $value) {
            if (is_int($key) && (preg_match('/^select\((.*)\)$/', $value))) {
                throw new InvalidArgumentException(
                    'The specified array of expressions already has a '
                    . "'select' operator: '" . print_r($value, true) . "'"
                );
            }
        }
        $expressions[] = "select($idProperty,$labelProperty)";

        try {
            $data = $this->persister->fetchPairs(
                $this->entityName, $idProperty, $labelProperty, $expressions
            );
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) .
                'Unable to fetch key/value pairs matching given criteria.'
            );
        }

        return new Result(Result::STATUS_SUCCESS, $data);
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
     * Get error messages from a violation list.
     *
     * @param ConstraintViolationList $violations List of constraint violations.
     * @return array
     */
    protected function getErrorMessages(ConstraintViolationList $violations)
    {
        $translator = $this->get('translator');
        $messages = array();
        foreach ($violations as $violation) {
            $template = $translator->translate($violation->getMessageTemplate());
            $messageParameters = $violation->getMessageParameters();
            $message = \str_replace(
                array_keys($messageParameters),
                array_values($messageParameters),
                $template
            );
            $messages[$violation->getPropertyPath()] = $message;
        }
        return $messages;
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
        try {
            $entity = new $this->entityName;
            $violations = $this->get('serializer')->deserialize($entity, $data);
            $violations->addAll($this->get('validator')->validate($entity));
            if (count($violations) === 0) {
                $this->persister->create($entity);
                $status = Result::STATUS_SUCCESS;
                $errors = null;
            } else {
                $status = Result::STATUS_VALIDATION_ERROR;
                $entity = $data;
                $errors = $this->getErrorMessages($violations);
            }
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e) . 'Unable to create entity.'
            );
        }

        return new Result($status, $entity, $errors);
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

        try {
            $entity = $this->persister->retrieve($this->entityName, $id);
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

        try {
            $entity = $this->persister->retrieve($this->entityName, $id);
            $violations = $this->get('serializer')->deserialize($entity, $data);
            $violations->addAll($this->get('validator')->validate($entity));
            if (count($violations) === 0) {
                $this->persister->update($entity);
                $status = Result::STATUS_SUCCESS;
                $errors = null;
            } else {
                $status = Result::STATUS_VALIDATION_ERROR;
                $entity = $data;
                $errors = $this->getErrorMessages($violations);
            }
        } catch (\Exception $e) {
            // Sanitize for exception shielding
            throw new RuntimeException(
                $this->getSanitizedException($e)
                . "Unable to update entity matching id $id."
            );
        }

        return new Result($status, $entity, $errors);
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

        try {
            $entity = $this->persister->retrieve($this->entityName, $id);
            $this->persister->delete($entity);
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
        $id = ':';
        $message = $exception->getMessage() . PHP_EOL
                 . $exception->getTraceAsString();

        $front = \Zend_Controller_Front::getInstance();
        if ($bootstrap = $front->getParam('bootstrap')) {
            if ($container = $bootstrap->getContainer()) {
                if (isset($container->log)) {
                    $id = ' [errorId:' . uniqid('shield-', true) . ']:';
                    $container->log->err('Details of' . $id . PHP_EOL . $message);
                    $message = 'details may be found in the application log under given errorId';
                }
            }
        }

        return 'An error has occurred while running service'
            . $id . PHP_EOL . $message . PHP_EOL;
    }
}