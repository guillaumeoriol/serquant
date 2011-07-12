<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\Service;

use Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber,
    Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress,
    Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount,
    Serquant\Resource\Persistence\Doctrine\Entity\CmsUser,
    Serquant\Persistence\Doctrine,
    Serquant\Service\Crud;

class CrudDoctrineAssociationTest
    extends \Serquant\Resource\Persistence\OrmFunctionalTestCase
{
    private $em;

    private $persister;

    private $users = array();

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->persister = new Doctrine();
        $this->persister->setEntityManager($this->em);

        $this->setUpDb();
    }

    protected function setUpDb()
    {
        $phonenumber = new CmsPhonenumber();
        $phonenumber->phonenumber = '0160947030';

        $address = new CmsAddress();
        $address->country = 'France';
        $address->zip = '77280';
        $address->city = 'Othis';

        $account = new CmsAccount();
        $account->bank = '12345';
        $account->accountNumber = '12345678901';

        $user = new CmsUser();
        $user->status = 'test';
        $user->username = 'user1';
        $user->name = 'Guillaume';
        $user->addPhonenumber($phonenumber);
        $user->setAddress($address);
        $user->setAccount($account);
        $this->users['user1'] = $user;

        $user = new CmsUser();
        $user->status = 'test';
        $user->username = 'user2';
        $user->name = 'Olivier';
        $user->addPhonenumber($phonenumber);
        $this->users['user2'] = $user;

        $user = new CmsUser();
        $user->status = 'test';
        $user->username = 'user3';
        $user->name = 'Frederic';
        $user->setAddress($address);
        $this->users['user3'] = $user;

        $user = new CmsUser();
        $user->status = 'test';
        $user->username = 'user4';
        $user->name = 'Henry';
        $this->users['user4'] = $user;

        foreach ($this->users as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
    }

    public function testUserWithPhoneAndAddress()
    {
        $user = $this->em->getRepository('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser')
                         ->findOneBy(array('username' => 'user1'));

        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $address);

        $account = $user->getAccount();
        // $this->assertInstanceOf('\Doctrine\ORM\Proxy\Proxy', $account);
        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount', $account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('\Doctrine\Common\Collections\Collection', $phonenumbers);
        foreach ($phonenumbers as $phonenumber) {
            $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $phonenumber);
        }
    }

    public function testUserWithPhoneAndAddressThroughService()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';
        $inputFilterName = null;

        $service = new Crud($entityName, $inputFilterName, $this->persister);
        $result = $service->fetchOne(array('username' => 'user1'));
        $user = $result->getData();

        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $address);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('\Doctrine\Common\Collections\Collection', $phonenumbers);
        foreach ($phonenumbers as $phonenumber) {
            $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $phonenumber);
        }
    }

    public function testUserWithoutAddress()
    {
        $user = $this->em->getRepository('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser')
                         ->findOneBy(array('username' => 'user2'));

        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertNull($address);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('\Doctrine\Common\Collections\Collection', $phonenumbers);
        foreach ($phonenumbers as $phonenumber) {
            $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $phonenumber);
        }
    }

    public function testUserWithoutAddressThroughService()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';
        $inputFilterName = null;

        $service = new Crud($entityName, $inputFilterName, $this->persister);
        $result = $service->fetchOne(array('username' => 'user2'));
        $user = $result->getData();

        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertNull($address);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('\Doctrine\Common\Collections\Collection', $phonenumbers);
        foreach ($phonenumbers as $phonenumber) {
            $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $phonenumber);
        }
    }

    public function testUserWithoutPhonenumbers()
    {
        $user = $this->em->getRepository('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser')
                         ->findOneBy(array('username' => 'user3'));

        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $address);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('\Doctrine\Common\Collections\Collection', $phonenumbers);
        $this->assertTrue($phonenumbers->isEmpty());
    }

    public function testUserWithoutPhonenumbersThroughService()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';
        $inputFilterName = null;

        $service = new Crud($entityName, $inputFilterName, $this->persister);
        $result = $service->fetchOne(array('username' => 'user3'));
        $user = $result->getData();

        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $address);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('\Doctrine\Common\Collections\Collection', $phonenumbers);
        $this->assertTrue($phonenumbers->isEmpty());
    }

    public function testUserWithoutAny()
    {
        $user = $this->em->getRepository('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser')
                         ->findOneBy(array('username' => 'user4'));

        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertNull($address);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('\Doctrine\Common\Collections\Collection', $phonenumbers);
        $this->assertTrue($phonenumbers->isEmpty());
    }

    public function testUserWithoutAnyThroughService()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';
        $inputFilterName = null;

        $service = new Crud($entityName, $inputFilterName, $this->persister);
        $result = $service->fetchOne(array('username' => 'user4'));
        $user = $result->getData();

        $this->assertInstanceOf('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertNull($address);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('\Doctrine\Common\Collections\Collection', $phonenumbers);
        $this->assertTrue($phonenumbers->isEmpty());
    }

    protected function tearDown()
    {
        $rep = $this->em->getRepository('\Serquant\Resource\Persistence\Doctrine\Entity\CmsUser');

        $user = $rep->findOneBy(array('username' => 'user1'));
        $this->em->remove($user);
        $user = $rep->findOneBy(array('username' => 'user2'));
        $this->em->remove($user);
        $user = $rep->findOneBy(array('username' => 'user3'));
        $this->em->remove($user);
        $user = $rep->findOneBy(array('username' => 'user4'));
        $this->em->remove($user);

        $this->em->flush();
    }
}
