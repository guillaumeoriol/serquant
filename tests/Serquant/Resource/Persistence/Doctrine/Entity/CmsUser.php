<?php

namespace Serquant\Resource\Persistence\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="test_cms_users")
 */
class CmsUser
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string", length=50)
     */
    public $status;

    /**
     * @Column(type="string", length=255, unique=true)
     */
    public $username;

    /**
     * @Column(type="string", length=255)
     */
    public $name;

    /**
     * @OneToMany(targetEntity="CmsPhonenumber", mappedBy="user", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    public $phonenumbers;

    /**
     * @OneToOne(targetEntity="CmsAccount", cascade={"persist", "remove"}, orphanRemoval=true)
     * @JoinColumn(name="account_id", referencedColumnName="id")
     */
    public $account;

    /**
     * @OneToOne(targetEntity="CmsAddress", mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    public $address;

    public function __construct() {
        $this->phonenumbers = new ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getName() {
        return $this->name;
    }

    /**
     * Adds a phonenumber to the user.
     *
     * @param CmsPhonenumber $phone
     */
    public function addPhonenumber(CmsPhonenumber $phone) {
        $this->phonenumbers[] = $phone;
        $phone->setUser($this);
    }

    public function getPhonenumbers() {
        return $this->phonenumbers;
    }

    public function setPhonenumbers($phonenumbers) {
        foreach ($phonenumbers as $phonenumber) {
            $this->addPhonenumber($phonenumber);
        }
    }

    public function removePhonenumber($index) {
        if (isset($this->phonenumbers[$index])) {
            $ph = $this->phonenumbers[$index];
            unset($this->phonenumbers[$index]);
            $ph->user = null;
            return true;
        }
        return false;
    }

    public function getAddress() { return $this->address; }

    public function setAddress(CmsAddress $address) {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }

    public function getAccount() { return $this->account; }

    public function setAccount(CmsAccount $account) {
        if ($this->account !== $account) {
            $this->account = $account;
        }
    }
}
