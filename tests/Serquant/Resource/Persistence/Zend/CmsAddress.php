<?php
namespace Serquant\Resource\Persistence\Zend;

/**
 * CmsAddress
 *
 * @Entity(repositoryClass="Serquant\Resource\Persistence\Zend\Db\Table\CmsAddress")
 */
class CmsAddress
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @Column(length=50)
     */
    public $country;

    /**
     * @Column(length=50)
     */
    public $zip;

    /**
     * @Column(length=50)
     */
    public $city;

    /**
     * Testfield for Schema Updating Tests.
     */
    public $street;

    /**
     * one-to-one bidirectional use case
     *
     * @OneToOne(targetEntity="CmsUser", inversedBy="address")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    public function getId() {
        return $this->id;
    }

    public function getUser() {
        return $this->user;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getZipCode() {
        return $this->zip;
    }

    public function getCity() {
        return $this->city;
    }

    public function setUser(CmsUser $user) {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}