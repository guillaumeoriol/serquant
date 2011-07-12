<?php

namespace Serquant\Resource\Persistence\Doctrine\Entity;

/**
 * CmsAccount
 *
 * @Entity
 * @Table(name="test_cms_accounts")
 */
class CmsAccount
{
    /**
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @Column(length=50)
     */
    public $bank;

    /**
     * @Column(length=50)
     */
    public $accountNumber;

    public function getId() {
        return $this->id;
    }

    public function getBank() {
        return $this->bank;
    }

    public function getAccountNumber() {
        return $this->accountNumber;
    }
}