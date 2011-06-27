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

use Serquant\Controller\Exception\RuntimeException,
    Serquant\Service\Service;

/**
 * Regular Zend_Controller_Action controller enclosing a service layer.
 *
 * @category Serquant
 * @package  Controller
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Action extends \Zend_Controller_Action
{
    /**
     * Service layer
     * @var \Serquant\Service\Service
     */
    private $service;

    /**
     * Service layer name (retrieved from dependency injection container)
     * to be defined in child classes.
     * @var string
     */
    protected $serviceName;

    /**
     * Get service layer.
     *
     * @return \Serquant\Service\Service
     */
    protected function getService()
    {
        if ($this->service === null) {
            $front = \Zend_Controller_Front::getInstance();
            $container = $front->getParam('bootstrap')->getContainer();
            $this->service = $container->{$this->serviceName};
            if (!($this->service instanceof Service)) {
                throw new RuntimeException(
                    "The provided service '{$this->serviceName}' must " .
                    'implement the Serquant\Service\Service interface ' .
                    '(but ' . get_class($this->service) . ' does not).'
                );
            }
        }
        return $this->service;
    }
}