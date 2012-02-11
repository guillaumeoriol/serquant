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
 * Class to test multiple-level inheritance
 *
 * @category Serquant
 * @package  Resource
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class PersonSubclassSubclass extends PersonSubclass
{
    private $mainHobby;

    public function getMainHobby()
    {
        return $this->mainHobby;
    }

    public function setMainHobby($mainHobby)
    {
        $this->mainHobby = $mainHobby;
    }
}