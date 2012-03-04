<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Resource\Persistence\Zend\Proxy;

use Serquant\Resource\Persistence\Zend\RoleWithPermissionAssoc;

/**
 * Proxy
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class RoleWithPermissionAssocProxy extends RoleWithPermissionAssoc implements \Doctrine\ORM\Proxy\Proxy
{
    private $_gateway;

    private $_identifier;

    public $__isInitialized__ = false;

    public function __construct($gateway, $identifier)
    {
        $this->_gateway = $gateway;
        $this->_identifier = $identifier;
    }

    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_gateway) {
            $this->__isInitialized__ = true;

            if (method_exists($this, '__wakeup')) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what Table::newInstance()
                // provides.
                $this->__wakeup();
            }

            $row = $this->_gateway->retrieve($this->_identifier);
            $this->_gateway->loadEntity($this, $row);
            unset($this->_gateway, $this->_identifier);
        }
    }

    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function setName($name)
    {
        $this->__load();
        return parent::setName($name);
    }

    public function getName()
    {
        $this->__load();
        return parent::getName();
    }

    public function getPermissions()
    {
        $this->__load();
        return parent::getPermissions();
    }

    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'name', 'permissions');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_gateway) {
            $this->__isInitialized__ = true;
            $original = $this->_gateway->newInstance();
            $row = $this->_gateway->retrieve($this->_identifier);
            $this->_gateway->loadEntity($original, $row);

            $this->id = $original->id;
            $this->name = $original->name;
            $this->permissions = $original->permissions;

            unset($this->_gateway, $this->_identifier);
        }
    }
}