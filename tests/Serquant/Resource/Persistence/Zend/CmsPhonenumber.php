<?php
namespace Serquant\Resource\Persistence\Zend;

/**
 * CmsPhonenumber
 *
 * @Entity(repositoryClass="Serquant\Resource\Persistence\Zend\Db\Table\CmsPhonenumber")
 */
class CmsPhonenumber
{
    /**
     * @Id @GeneratedValue(strategy="NONE")
     * @Column(length=50)
     */
    public $phonenumber;

    /**
     * one-to-may bidirectional use case
     *
     * @ManyToOne(targetEntity="CmsUser", inversedBy="phonenumbers", cascade={"merge"})
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    public function setUser(CmsUser $user) {
        $this->user = $user;
    }

    public function getUser() {
        return $this->user;
    }
}
