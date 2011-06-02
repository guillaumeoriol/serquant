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
 * Plugin used to convert a range specified in the <i>Range</i> header into
 * a query parameter.
 *
 * The range <code>items=0-9</code> (from HTTP header) is converted to the query
 * parameter <code>limit(start,count)</code> to conform with the
 * {@link Rest#indexAction()} controller expectations (wich, in turn,
 * follows the {@link http://www.persvr.org/draft-zyp-rql-00.html#anchor15
 * Resource Query Language specification}).
 *
 * This plugin may be registered with the following code (to be placed in the
 * module application configuration file):
 * <pre>
 * resources.frontController.plugins[] = "\Serquant\Controller\Plugin\RangeHandler"
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
class RangeHandler extends \Zend_Controller_Plugin_Abstract
{
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

        if (!$range = $request->getHeader('Range')) {
            return;
        }

        // Expected format is: 'items=0-9'
        $range = explode('=', $range);
        list($start, $end) = explode('-', $range[1]);
        $count = $end - $start + 1;
        $request->setQuery("limit($start,$count)", '');
    }
}
