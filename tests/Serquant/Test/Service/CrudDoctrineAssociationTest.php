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

use Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber;
use Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress;
use Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount;
use Serquant\Resource\Persistence\Doctrine\Entity\CmsUser;
use Serquant\Persistence\Doctrine;
use Serquant\Service\Crud;

class CrudDoctrineAssociationTest extends \Serquant\Resource\Persistence\OrmFunctionalTestCase
{
    private $db;
    private $em;
    private $persister;
    private $users = array();

    private function setupDatabase()
    {
        $dataSets = array();
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_accounts.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_users.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_addresses.yaml'
        );
        $dataSets[] = new \PHPUnit_Extensions_Database_DataSet_YamlDataSet(
            dirname(__FILE__) . '/fixture/cms_phonenumbers.yaml'
        );
        $data = new \PHPUnit_Extensions_Database_DataSet_CompositeDataSet(
            $dataSets
        );

        $this->db = $this->getTestAdapter();
        $connection = new \Zend_Test_PHPUnit_Db_Connection($this->db, null);
        $tester = new \Zend_Test_PHPUnit_Db_SimpleTester($connection);
        $tester->setupDatabase($data);
    }

    protected function setUp()
    {
        $this->setupDatabase();
        $this->em = $this->getTestEntityManager();
        $this->persister = new Doctrine($this->em);
    }
/*
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
*/

    public function testUserWithPhoneAndAddress()
    {
        $user = $this->em->getRepository('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser')
                         ->findOneBy(array('username' => 'a'));

        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $address);

        $account = $user->getAccount();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount', $account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $phonenumbers);
        foreach ($phonenumbers as $phonenumber) {
            $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $phonenumber);
        }
    }

    public function testUserWithPhoneAndAddressThroughService()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';

        $service = new Crud($entityName, $this->persister);
        $result = $service->fetchOne(array('username' => 'a'));
        $user = $result->getData();

        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $address);

        $account = $user->getAccount();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount', $account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $phonenumbers);
        foreach ($phonenumbers as $phonenumber) {
            $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $phonenumber);
        }
    }

    public function testUserWithoutAddress()
    {
        $user = $this->em->getRepository('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser')
                         ->findOneBy(array('username' => 'b'));

        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertNull($address);

        $account = $user->getAccount();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount', $account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $phonenumbers);
        foreach ($phonenumbers as $phonenumber) {
            $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $phonenumber);
        }
    }

    public function testUserWithoutAddressThroughService()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';

        $service = new Crud($entityName, $this->persister);
        $result = $service->fetchOne(array('username' => 'b'));
        $user = $result->getData();

        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertNull($address);

        $account = $user->getAccount();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount', $account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $phonenumbers);
        foreach ($phonenumbers as $phonenumber) {
            $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsPhonenumber', $phonenumber);
        }
    }

    public function testUserWithoutPhonenumbers()
    {
        $user = $this->em->getRepository('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser')
                         ->findOneBy(array('username' => 'c'));

        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $address);

        $account = $user->getAccount();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount', $account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $phonenumbers);
        $this->assertTrue($phonenumbers->isEmpty());
    }

    public function testUserWithoutPhonenumbersThroughService()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';

        $service = new Crud($entityName, $this->persister);
        $result = $service->fetchOne(array('username' => 'c'));
        $user = $result->getData();

        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAddress', $address);

        $account = $user->getAccount();
        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsAccount', $account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $phonenumbers);
        $this->assertTrue($phonenumbers->isEmpty());
    }

    public function testUserWithoutAny()
    {
        $user = $this->em->getRepository('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser')
                         ->findOneBy(array('username' => 'e'));

        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertNull($address);

        $account = $user->getAccount();
        $this->assertNull($account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $phonenumbers);
        $this->assertTrue($phonenumbers->isEmpty());
    }

    public function testUserWithoutAnyThroughService()
    {
        $entityName = 'Serquant\Resource\Persistence\Doctrine\Entity\CmsUser';

        $service = new Crud($entityName, $this->persister);
        $result = $service->fetchOne(array('username' => 'e'));
        $user = $result->getData();

        $this->assertInstanceOf('Serquant\Resource\Persistence\Doctrine\Entity\CmsUser', $user);

        $address = $user->getAddress();
        $this->assertNull($address);

        $account = $user->getAccount();
        $this->assertNull($account);

        $phonenumbers = $user->getPhonenumbers();
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $phonenumbers);
        $this->assertTrue($phonenumbers->isEmpty());
    }
}
