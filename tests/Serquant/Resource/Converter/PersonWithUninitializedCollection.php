<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Resource\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Serquant\Converter\Mapping as Converter;

/**
 * @Entity
 */
class PersonWithUninitializedCollection
{
    /**
     * @Converter\Property(type="integer")
     */
    public $id;

    /**
     * @Converter\Property(type="string")
     */
    public $name;

    /**
     * @Converter\Property(type="Serquant\Resource\Converter\Car", multiplicity="*")
     */
    public $cars;
}