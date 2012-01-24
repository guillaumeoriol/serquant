<?php
namespace Serquant\Resource\Persistence\Zend;

/**
 * CmsAccount
 *
 * @Entity(repositoryClass="Serquant\Resource\Persistence\Zend\Db\Table\CmsAccount")
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
     * @Column(name="account_number", length=50)
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