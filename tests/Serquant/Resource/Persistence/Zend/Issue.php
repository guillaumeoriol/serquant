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
 * @Entity(repositoryClass="\Serquant\Resource\Persistence\Zend\Db\Table\Issue")
 */
class Issue
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string", length=50)
     */
    public $title;

    /**
     * @OneToOne(targetEntity="Person")
     * @JoinColumn(name="person_id", referencedColumnName="id")
     */
    public $reporter;

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getReporter()
    {
        return $this->reporter;
    }
}