<?php

namespace Serquant\Resource\Persistence\Doctrine\Entity;

/**
 * @Entity
 * @Table(name="test_cms_phonenumbers")
 */
class CmsPhonenumber
{
    /**
     * @Id @Column(length=50)
     */
    public $phonenumber;
    /**
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
