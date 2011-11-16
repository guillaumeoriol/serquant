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
class RoleWithPrivateProperties
{
    /**
     * @Converter\Property(type="integer")
     */
    private $id;

    /**
     * @Converter\Property(type="string")
     */
    private $name;

    public function __construct() {
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
}

