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
namespace Serquant\Controller\Plugin;

/**
 * Plugin used to convert a format specified in the <i>Accept</i> header into
 * a request parameter.
 *
 * This plugin may be registered with the following code (to be placed in the
 * module application configuration file):
 * <pre>
 * resources.frontController.plugins[] = "\Serquant\Controller\Plugin\AcceptHandler"
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
class AcceptHandler extends \Zend_Controller_Plugin_Abstract
{
    /**
     * The default context parameter choosen by Zend Framework ('format')
     * may conflict with an application-defined parameter of the same name.
     * Use another one to avoid this (the replacement is done in
     * {@link RestContexts#initContexts()}).
     */
    const CONTEXT_PARAM = '_format';

    /**
     * Method executed at dispatch loop startup
     *
     * @param \Zend_Controller_Request_Abstract $request Request
     * @return void
     */
    public function dispatchLoopStartup(\Zend_Controller_Request_Abstract $request)
    {
        if (!$request instanceof \Zend_Controller_Request_Http) {
            return;
        }

        $accept = $request->getHeader('Accept');
        if (strstr($accept, 'application/json')) {
            $request->setParam(self::CONTEXT_PARAM, 'json');
        } else if (strstr($accept, 'application/xml')
            && (!strstr($accept, 'html'))
        ) {
            $request->setParam(self::CONTEXT_PARAM, 'xml');
        }
    }
}