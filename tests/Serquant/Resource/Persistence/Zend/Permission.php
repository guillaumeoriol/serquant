<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Resource\Persistence\Zend;

/**
 * Permission entity
 *
 * @Entity(repositoryClass="\Serquant\Resource\Persistence\Zend\Db\Table\Permission")
 */
class Permission
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @Column(name="role", type="integer")
     */
    protected $role;

    /**
     * @Column(name="resource", type="integer")
     */
    protected $resource;

    /**
     * @Column(name="action", type="string", length=25)
     */
    protected $actionName;

    /**
     * @Column(name="assertion", type="string", length=100)
     */
    protected $assertion;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setRole($role)
    {
        $this->role = $role;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setActionName($actionName)
    {
        $this->actionName = $actionName;
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function setAssertion($assertion)
    {
        $this->assertion = $assertion;
    }

    public function getAssertion()
    {
        return $this->assertion;
    }
}