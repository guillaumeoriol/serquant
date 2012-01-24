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
 * @Entity(repositoryClass="\Serquant\Resource\Persistence\Zend\Db\Table\Person")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="person_type", type="string")
 * @DiscriminatorMap({"person" = "Person", "employee" = "Employee"})
 */
class Person
{
    /**
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer")
     */
    public $id;

    /** @Column(name="first_name", type="string", length=50, nullable=true) */
    public $firstName;

    /** @Column(name="last_name", type="string", length=50, nullable=true) */
    public $lastName;

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }
}
