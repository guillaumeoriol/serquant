<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Service
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Service;

use Serquant\Service\Exception\InvalidArgumentException;

/**
 * Defines the object returned by the service.
 *
 * @category Serquant
 * @package  Service
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class Result
{
    // Don't forget to update STATUS_MAX_VALUE when a new status is added.
    // It's value should be equal to the greater status value.
    const STATUS_SUCCESS = 0;
    const STATUS_VALIDATION_ERROR = 1;
    const STATUS_MAX_VALUE = 1;

    /**
     * Result status
     * @var int
     */
    protected $status;

    /**
     * Result data
     * @var mixed
     */
    protected $data;

    /**
     * Result errors
     * @var array
     */
    protected $errors;

    /**
     * Constructor
     *
     * @param int $status
     * @param mixed $data
     * @param array $errors
     */
    public function __construct($status, $data, array $errors = null)
    {
        $this->setStatus($status);
        $this->setData($data);
        if ($errors !== null) {
            $this->setErrors($errors);
        }
    }

    /**
     * Set result status.
     *
     * The status may be any integer value between 0 and 255.<br>
     * Success is traditionally represented with the value 0.<br>
     * Failure is normally indicated with a non-zero value.
     *
     * @param int $status
     * @return void
     * @throws InvalidArgumentException when the
     * provided status is out of range.
     */
    public function setStatus($status)
    {
        if (!is_int($status) ||
            ($status < self::STATUS_SUCCESS) ||
            ($status > self::STATUS_MAX_VALUE)
        ) {
            throw new InvalidArgumentException(
            	'Status (' . $status . ') out of range (' .
                self::STATUS_SUCCESS . '-' . self::STATUS_MAX_VALUE .').');
        }

        $this->status = (int) $status;
    }

    /**
     * Get result status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set result data.
     *
     * @param mixed $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get result data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set result errors.
     *
     * The specified array associates a name to a corresponding message.
     *
     * @param array $errors
     * @return void
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * Get result errors.
     *
     * The returned array associates a name to an error message. The
     * message may be a string or an array of string when multiple messages
     * correspond to the same error.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}