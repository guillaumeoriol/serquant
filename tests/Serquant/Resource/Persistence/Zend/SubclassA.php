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

use Serquant\Converter\Mapping as Converter;

/**
 * @Entity
 */
class SubclassA extends Informer
{
    /**
     * @Converter\Property(type="integer")
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Converter\Property(type="string")
     * @Column(name="name", type="string", length=50)
     */
    public $name;

    /**
     * @Converter\Property(type="string")
     * @Column(type="string")
     */
    public $specificToA;

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSpecificToA()
    {
        return $this->specificToA;
    }
}