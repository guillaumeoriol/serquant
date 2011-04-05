<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  DependencyInjection
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder as BaseContainerBuilder;

/**
 * Dependency injection container.
 *
 * Long description.
 *
 * @category Serquant
 * @package  DependencyInjection
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ContainerBuilder extends BaseContainerBuilder
{
    /**
     * Defines a named service within the dependency injection container.
     *
     * With Zend Framework, the value argument may be any kind of object
     * returned by a resource plugin or by a method of the Bootstrap class.
     * In dependency injection context, the value argument may be any kind
     * of dependency (ie service) needed by a component.
     *
     * @param string $name
     * @param object $value
     */
    public function __set($name, $value)
    {
        parent::set($name, $value);
    }

    /**
     * Returns true if the given service is defined.
     *
     * @param string $name
     * @return boolean true if the service is defined, false otherwise
     */
    public function __isset($name)
    {
        return parent::has($name);
    }

    /**
     * Get the specified service from the dependency injection container.
     *
     * @param string $name The service name
     * @return object The named service
     *
     */
    public function __get($name)
    {
        return parent::get($name);
    }
}