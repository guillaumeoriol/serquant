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
 * Mapped superclass designed to implement a simple informer that is common to
 * multiple sublasses. It gives the ability to know who created the entity and
 * when it happened and the same information regarding the last update as well.
 *
 * It implements two Doctrine behaviors at the same time: Timestampable and
 * Blameable as they were described by Benjamin Eberlei in
 * {@link http://www.doctrine-project.org/blog/doctrine2-behaviours-nutshell?id=136
 * a post}.
 *
 * @MappedSuperclass
 */
class Informer
{
    /**
     * @Converter\Property(type="DateTime")
     * @Column(name="saved_at", type="datetime")
     */
    protected $savedAt;

    /**
     * @Converter\Property(type="integer")
     * @Column(name="saved_by", type="integer", nullable=true)
     */
    protected $savedBy;

    /**
     * Set creation date from {@link http://www.ietf.org/rfc/rfc3339.txt
     * RFC 3339}/ISO 8601 formatted string.
     *
     * @param string $date RFC 3339/ISO 8601 formatted string (for instance
     * "2010-09-28T17:38:21").
     * @return void
     */
    public function setSavedAt(\DateTime $date)
    {
        if ($date != $this->savedAt) {
            $this->savedAt = $date;
        }
    }

    /**
     * Get creation date in {@link http://www.ietf.org/rfc/rfc3339.txt
     * RFC 3339}/ISO 8601 formatted string.
     *
     * @return string RFC 3339/ISO 8601 formatted string (for instance
     * "2010-09-28T17:38:21").
     */
    public function getSavedAt()
    {
        return $this->savedAt;
    }

    /**
     * Set las update date from {@link http://www.ietf.org/rfc/rfc3339.txt
     * RFC 3339}/ISO 8601 formatted string.
     *
     * @param string $date RFC 3339/ISO 8601 formatted string (for instance
     * "2010-09-28T17:38:21").
     * @return void
     */
    public function setSavedBy($user)
    {
        $this->savedBy = $user;
    }

    /**
     * Get last update date in {@link http://www.ietf.org/rfc/rfc3339.txt
     * RFC 3339}/ISO 8601 formatted string.
     *
     * @return string RFC 3339/ISO 8601 formatted string (for instance
     * "2010-09-28T17:38:21").
     */
    public function getSavedBy()
    {
        return $this->savedBy;
    }
}