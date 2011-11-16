<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  package
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Resource\Converter;

use Serquant\Converter\Mapping as Converter;

/**
 * @Entity
 */
class RoleWithPublicProperties
{
    /**
     * @Converter\Property(type="integer")
     */
    public $id;

    /**
     * @Converter\Property(type="string")
     */
    public $name;

    public function __construct() {
    }
}

