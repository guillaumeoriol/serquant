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

use Serquant\Controller\Rest,
    Serquant\Controller\Plugin\AcceptHandler;

/**
 * Action helper used to automatically enable context switching on
 * controllers that are instances of {@link Rest}.
 *
 * This helper may be registered with the following code (to be placed in the
 * module bootstrap class):
 * <pre>
 * $helper = new \Serquant\Controller\Helper\RestContexts();
 * Zend_Controller_Action_HelperBroker::addHelper($helper);
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
class RestContexts extends \Zend_Controller_Action_Helper_Abstract
{
    /**
     * Defined output formats
     * @var array
     */
    protected static $contexts = array(
        'json'
    );

    /**
     * List of actions to switch context on
     * @var array
     */
    protected static $actions = array(
        'index',
        'get',
        'post',
        'put',
        'delete',
        'new'
    );

    /**
     * Enable context switch for controllers that are instances of {@link Rest}.
     *
     * @return void
     */
    public function preDispatch()
    {
        $controller = $this->getActionController();
        if (!$controller instanceof Rest) {
            return;
        }

        $this->initContexts();

        // Set a Vary response header based on the Accept header.
        // This is to ensure that, if the client chooses to cache responses,
        // it will cache separate responses based on the value sent
        // in the "Accept" header.
        $this->getResponse()->setHeader('Vary', 'Accept');
    }

    /**
     * Enable context switch on the listed actions.
     *
     * @return void
     */
    protected function initContexts()
    {
        $controller = $this->getActionController();
        $contextSwitch = $controller->getHelper('contextSwitch');

        // Disable automatic Json serialization to remain format agnostic
        // until rendering. Rendering is done via separate view scripts.
        $contextSwitch->setAutoJsonSerialization(false);

        // Replace default parameter ('format') by '_format' to avoid
        // possible collision with an application-defined parameter.
        $contextSwitch->setContextParam(AcceptHandler::CONTEXT_PARAM);

        foreach (self::$contexts as $context) {
            foreach (self::$actions as $action) {
                $contextSwitch->addActionContext($action, $context);
            }
        }
        $contextSwitch->initContext();
    }
}