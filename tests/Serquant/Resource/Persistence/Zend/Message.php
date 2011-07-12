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
 * @Entity(repositoryClass="\Serquant\Resource\Persistence\Zend\Db\Table\Message")
 */
class Message
{
    /**
     * @Id
     * @Column(type="string", length=2)
     */
    public $language;

    /**
     * @Id
     * @Column(type="string", length=50)
     */
    public $key;

    /**
     * @Column(type="string", length=255, unique=true)
     */
    public $message;

    public function __construct() {
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getKey() {
        return $this->key;
    }

    public function getMessage() {
        return $this->message;
    }
}
