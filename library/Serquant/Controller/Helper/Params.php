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
namespace Serquant\Controller\Helper;

/**
 * Action helper used to detect the content type, retrieve parameters from raw
 * body (if present) and decode them according to that content type.
 *
 * This helper may be registered with the following code (to be placed in the
 * module bootstrap class):
 * <pre>
 * $helper = new \Serquant\Controller\Helper\Params();
 * \Zend_Controller_Action_HelperBroker::addHelper($helper);
 * </pre>
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
class Params extends \Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var array Parameters detected in raw content body
     */
    protected $bodyParams = array();

    /**
     * Detect the content type, retrieve parameters from raw body (if present)
     * and decode them according to that content type.
     *
     * @return void
     */
    public function init()
    {
        $request = $this->getRequest();
        if (!$request instanceof \Zend_Controller_Request_Http) {
            return;
        }

        $rawBody = $request->getRawBody();
        if (!$rawBody) {
            return;
        }

        $contentType = $request->getHeader('Content-Type');
        if (strstr($contentType, 'application/json')) {
            $this->setBodyParams(\Zend_Json::decode($rawBody));
        } else if (strstr($contentType, 'application/xml')) {
            $config = new \Zend_Config_Xml($rawBody);
            $this->setBodyParams($config->toArray());
        } else if (strstr($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($rawBody, $params);
            $this->setBodyParams($params);
        }
    }

    /**
     * Set body parameters
     *
     * @param array $params Parameters
     * @return \Serquant\Controller\Helper\Params
     */
    public function setBodyParams(array $params)
    {
        $this->bodyParams = $params;
        return $this;
    }

    /**
     * Get all body parameters
     *
     * @return array
     */
    public function getBodyParams()
    {
        return $this->bodyParams;
    }

    /**
     * Get body parameter
     *
     * @param string $name Parameter name
     * @return mixed
     */
    public function getBodyParam($name)
    {
        if ($this->hasBodyParam($name)) {
            return $this->bodyParams[$name];
        }
        return null;
    }

    /**
     * Is the given body parameter set?
     *
     * @param string $name Parameter name
     * @return bool
     */
    public function hasBodyParam($name)
    {
        if (isset($this->bodyParams[$name])) {
            return true;
        }
        return false;
    }

    /**
     * Do we have any body parameters?
     *
     * @return bool
     */
    public function hasBodyParams()
    {
        if (!empty($this->bodyParams)) {
            return true;
        }
        return false;
    }

    /**
     * Get submit parameters
     *
     * @return array
     */
    public function getSubmitParams()
    {
        if ($this->hasBodyParams()) {
            return $this->getBodyParams();
        }
        return $this->getRequest()->getPost();
    }

    /**
     * Proxies to the {@link getSubmitParams()} function.
     *
     * @return array
     */
    public function direct()
    {
        return $this->getSubmitParams();
    }
}
