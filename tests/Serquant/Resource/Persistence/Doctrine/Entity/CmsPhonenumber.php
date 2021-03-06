<?php

namespace Serquant\Resource\Persistence\Doctrine\Entity;

/**
 * @Entity
 * @Table(name="cms_phonenumbers")
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
