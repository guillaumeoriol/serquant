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
namespace Serquant\Resource\Converter;

use Serquant\Converter\Mapping as Converter;

/**
 * @Converter\Entity(prefix="/rest/user/")
 */
class UserWithPublicProperties
{
    /**
     * @Converter\Id
     * @Converter\Property(type="integer")
     */
    public $id;

    /**
     * @Converter\Property(type="bool")
     */
    public $status;

    /**
     * @Converter\Property(type="string")
     */
    public $username;

    public function __construct() {
    }
}

