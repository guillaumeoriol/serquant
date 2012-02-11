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
namespace Serquant\Resource\Persistence\Zend;

/**
 * Use case for testing entity lazy loading
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PersonProxy extends Person implements \Doctrine\ORM\Proxy\Proxy
{
    private $persister;

    private $identifier;

    public $__isInitialized__ = false;

    public function __construct($persister, $identifier)
    {
        $this->persister = $persister;
        $this->identifier = $identifier;
    }

    public function __load()
    {
        if (!$this->__isInitialized__ && $this->persister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->persister->retrieve('Serquant\Resource\Persistence\Zend\Person', $this->identifier) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->persister, $this->identifier);
        }
    }

    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function getFirstName()
    {
        $this->__load();
        return parent::getFirstName();
    }

    public function getLastName()
    {
        $this->__load();
        return parent::getLastName();
    }

    public function setId($id)
    {
        $this->__load();
        return parent::setId($id);
    }

    public function setFirstName($firstName)
    {
        $this->__load();
        return parent::setFirstName($firstName);
    }

    public function setLastName($lastName)
    {
        $this->__load();
        return parent::setLastName($lastName);
    }

    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'firstName', 'lastName');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->persister) {
            $this->__isInitialized__ = true;
            $original = $this->persister->retrieve('Serquant\Resource\Persistence\Zend\Person', $this->identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            $this->id = $original->id;
            $this->firstName = $original->firstName;
            $this->lastName = $original->lastName;
            unset($this->persister, $this->identifier);
        }
    }
}