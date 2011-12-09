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
use Serquant\Service\ServiceInterface;

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
     * Get service layer.
     *
     * @return ServiceInterface
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
}