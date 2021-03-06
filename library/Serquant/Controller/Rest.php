<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Controller
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Controller;

use Serquant\Controller\Exception\RuntimeException;
use Serquant\Converter\SerializerInterface;
use Serquant\Service\ServiceInterface;

/**
 * Generic RESTful controller.
 *
 * Implements the basic {@link
 * http://wikipedia.org/wiki/Representational_State_Transfer REST} methods
 * ({@link http://tools.ietf.org/html/rfc2616#section-9.3 GET},
 * {@link http://tools.ietf.org/html/rfc2616#section-9.5 POST},
 * {@link http://tools.ietf.org/html/rfc2616#section-9.6 PUT},
 * {@link http://tools.ietf.org/html/rfc2616#section-9.7 DELETE})
 * through a CRUD service.
 *
 * There is no exception handling in this controller as Zend Framework
 * has its own mechanism to handle exceptions in controllers
 * ({@link \Zend_Controller_Plugin_ErrorHandler the ErrorHandler plugin})
 * that would trigger the error action of the error controller from
 * the default module.
 *
 * This class also serves as a marker to identify RESTful controllers
 * that are context-aware (cf {@link RestContexts}).
 *
 * Based on {@link http://weierophinney.net/matthew/archives/233-Responding-to-Different-Content-Types-in-RESTful-ZF-Apps.html
 * a post} written by Matthew Weier O'Phinney.
 *
 * @category Serquant
 * @package  Controller
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Rest extends \Zend_Rest_Controller
{
    /**
     * Service layer
     * @var ServiceInterface
     */
    private $service;

    /**
     * Service layer name (retrieved from dependency injection container)
     * to be defined in child classes.
     * @var string
     */
    protected $serviceName;

    /**
     * Serializer to convert data from/to the client
     * @var \Serquant\Converter\SerializerInterface
     */
    protected $serializer;

    /**
     * Gets the service layer object.
     *
     * @return ServiceInterface
     * @throws RuntimeException A RuntimeException is thrown if the service
     * is missing or if it does not implement the ServiceInterface.
     */
    protected function getService()
    {
        if ($this->service === null) {
            $front = \Zend_Controller_Front::getInstance();
            $container = $front->getParam('bootstrap')->getContainer();
            try {
                $this->service = $container->{$this->serviceName};
                if (!($this->service instanceof ServiceInterface)) {
                    throw new RuntimeException(
                        "The provided service '{$this->serviceName}' must " .
                        'implement the Serquant\Service\ServiceInterface, ' .
                        'but ' . get_class($this->service) . ' does not.'
                    );
                }
            } catch (\InvalidArgumentException $e) {
                throw new RuntimeException(
                    "The service layer '{$this->serviceName}' does not exist."
                );
            }
        }
        return $this->service;
    }

    /**
     * Gets the serializer.
     *
     * @return SerializerInterface
     * @throws RuntimeException A RuntimeException is thrown if the serializer
     * service is missing or if it does not implement the SerializerInterface.
     */
    protected function getSerializer()
    {
        if ($this->serializer === null) {
            $front = \Zend_Controller_Front::getInstance();
            $container = $front->getParam('bootstrap')->getContainer();
            try {
                $this->serializer = $container->serializer;
                if (!($this->serializer instanceof SerializerInterface)) {
                    throw new RuntimeException(
                        'The serializer must implement the ' .
                        'Serquant\Converter\SerializerInterface but ' .
                        get_class($this->serializer) . ' does not.'
                    );
                }
            } catch (\InvalidArgumentException $e) {
                throw new RuntimeException('The serializer service does not exist.');
            }
        }
        return $this->serializer;
    }

    /**
     * Sanitize the RQL query to swap operators from the key side of the array
     * to the value side.
     *
     * @param array $query The query as it is exploded by PHP engine (ie $_GET)
     * @return array The sanitized RQL
     */
    protected function sanitizeRql($query)
    {
        $rql = array();
        foreach ($query as $key => $value) {
            if (!is_int($key) && empty($value)) {
                $rql[] = $key;
            } else {
                $rql[$key] = $value;
            }
        }

        return $rql;
    }

    /**
     * GET is the REST method to retrieve a collection of resources when no
     * identifier is present.
     *
     * This action handles index/list requests. It should respond with
     * a collection of the requested resources.
     *
     * Filtering, ranging and sorting options may be specified in the query
     * string as defined by the {@link Service#fetchAll()} method of the
     * domain service layer.
     *
     * @return void The view script shall return HTTP 200 on success.
     * This action defines the following properties of the view object:
     * response => Zend_Controller_Request_Abstract,
     * result => Serquant\Service\Result,
     * serializer => Serquant\Converter\Serializer.
     */
    public function indexAction()
    {
        $service = $this->getService();
        $rql = $this->sanitizeRql($this->_request->getQuery());
        $result = $service->fetchPage($rql);

        $this->view->response = $this->getResponse();
        $this->view->result = $result;
        $this->view->serializer = $this->getSerializer();
    }

    /**
     * GET is the REST method to retrieve a single resource when an identifier
     * is present.
     *
     * This action handles GET requests. It retrieves the resource identifier
     * from the request parameters (under the conventional name <var>id</var>).
     * It should respond with the server resource state of the resource
     * identified by the <var>id</var> value.
     *
     * @return void The view script shall return HTTP 200 on success.
     * This action defines the following properties of the view object:
     * response => Zend_Controller_Request_Abstract,
     * result => Serquant\Service\Result,
     * serializer => Serquant\Converter\Serializer.
     */
    public function getAction()
    {
        $service = $this->getService();
        // The "id" parameter is retrieved from the request parameters
        // instead of the request body (that would be accessible through
        // the helper \Serquant\Controller\Helper\Params).
        $result = $service->retrieve($this->_getParam('id'));

        $this->view->response = $this->getResponse();
        $this->view->result = $result;
        $this->view->serializer = $this->getSerializer();
    }

    /**
     * POST is the REST method to create a resource.
     *
     * This action handles POST requests. It should accept and digest a POSTed
     * resource representation and persist the resource state. The resource data
     * is retrieved from the request body via the {@link Params} action helper.
     *
     * According to {@link
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.5 RFC 2616}:
     * "If a resource has been created on the origin server, the response
     * SHOULD be 201 (Created) and contain an entity which describes the
     * status of the request and refers to the new resource, and a Location
     * header (see section 14.30)."
     *
     * @return void The view script shall return HTTP 201 on success.
     * This action defines the following properties of the view object:
     * response => Zend_Controller_Request_Abstract,
     * result => Serquant\Service\Result,
     * serializer => Serquant\Converter\Serializer.
     */
    public function postAction()
    {
        $service = $this->getService();
        $result = $service->create($this->_helper->params());

        $this->view->response = $this->getResponse();
        $this->view->result = $result;
        $this->view->serializer = $this->getSerializer();
    }

    /**
     * PUT is the REST method to update a resource.
     *
     * This action handles PUT requests. It retrieves the resource identifier
     * from the request parameters (under the conventional name <var>id</var>)
     * and the data from the request body via the {@link Params} action helper.
     * It should update the server resource state of the resource identified by
     * the <var>id</var> value.
     *
     * According to {@link
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.6 RFC 2616}:
     * "If an existing resource is modified, either the 200 (OK) or 204
     * (No Content) response codes SHOULD be sent to indicate successful
     * completion of the request. If the resource could not be created
     * or modified with the Request-URI, an appropriate error response
     * SHOULD be given that reflects the nature of the problem."
     *
     * @return void The view script shall return HTTP 200 or 204 on success,
     * wheter the updated entity is returned or not.
     * This action defines the following properties of the view object:
     * response => Zend_Controller_Request_Abstract,
     * result => Serquant\Service\Result,
     * serializer => Serquant\Converter\Serializer.
     */
    public function putAction()
    {
        $service = $this->getService();
        // The "id" parameter is retrieved by the Zend_Rest_Route router
        // from the path part of the URI. The data is retrieved and decoded by
        // the \Serquant\Controller\Helper\Params action helper from the
        // request body, according to its content type.
        $result = $service->update(
            $this->_getParam('id'),
            $this->_helper->params()
        );

        $this->view->response = $this->getResponse();
        $this->view->result = $result;
        $this->view->serializer = $this->getSerializer();
    }

    /**
     * DELETE is the REST method to delete a resource.
     *
     * This action handles DELETE requests. It retrieves the resource identifier
     * from the request parameters (under the conventional name <var>id</var>).
     * It should update the server resource state of the resource identified by
     * the <var>id</var> value.
     *
     * According to {@link
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.7 RFC 2616}:
     * "A successful response SHOULD be 200 (OK) if the response includes an
     * entity describing the status, 202 (Accepted) if the action has not
     * yet been enacted, or 204 (No Content) if the action has been enacted
     * but the response does not include an entity."
     *
     * @return void The view script shall return HTTP 200 or 204 on success,
     * whether the deleted entity is returned or not.
     * This action defines the following properties of the view object:
     * response => Zend_Controller_Request_Abstract,
     * result => Serquant\Service\Result,
     * serializer => Serquant\Converter\Serializer.
     */
    public function deleteAction()
    {
        $service = $this->getService();
        // The "id" parameter is retrieved by the {@link Zend_Rest_Route router}
        // from the path part of the URI.
        $result = $service->delete($this->_getParam('id'));

        $this->view->response = $this->getResponse();
        $this->view->result = $result;
        $this->view->serializer = $this->getSerializer();
    }

    /**
     * Get initial state of a resource.
     *
     * When the user is responsible for populating a blank form to create a new
     * resource, it may be useful to setup default values. They can't always be
     * set once for all. For example, a default timestamp will be different for
     * each new resource. Therefore we need a way to retrieve the initial state
     * of the resource. But no specific method is defined by the REST
     * architecture. Hence, we use an action defined in {@link \Zend_Rest_Route}
     * that was designed for a slightly different purpose.
     *
     * @return void The view script shall return HTTP 200 on success.
     * This action defines the following properties of the view object:
     * response => Zend_Controller_Request_Abstract,
     * result => Serquant\Service\Result,
     * serializer => Serquant\Converter\Serializer.
     */
    public function newAction()
    {
        $service = $this->getService();
        $result = $service->getDefault();

        $this->view->response = $this->getResponse();
        $this->view->result = $result;
        $this->view->serializer = $this->getSerializer();
    }

    /**
     * Configure the view renderer to avoid controller name in the script path
     * (as all REST controllers share the same view scripts).
     *
     * @return void
     */
    public function postDispatch()
    {
        if (!$this->getInvokeArg('noViewRenderer')
            && $this->_helper->hasHelper('viewRenderer')
        ) {
            $this->_helper->viewRenderer->setNoController(true);
        }
    }
}