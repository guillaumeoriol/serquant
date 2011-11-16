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

class CrudTest extends \Serquant\Resource\Persistence\OrmFunctionalTestCase
{
    private $em;

    private $persister;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->persister = new Doctrine($this->em);
    }

    public function testGetService()
    {
        $obj = new \stdClass();
        $obj->x = 1;
        $obj->y = 'a';
        $obj->z = true;

        $container = new \Serquant\DependencyInjection\ContainerBuilder();
        $application = new \Zend_Application(APPLICATION_ROOT . '/application');
        $application->getBootstrap()->setContainer($container);
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('bootstrap', $application->getBootstrap());
        $container = $front->getParam('bootstrap')->getContainer();
        $container->obj = $obj;

        $entityName = null;
        $service = new Crud($entityName, $this->persister);
        $method = new \ReflectionMethod($service, 'get');
        $method->setAccessible(true);
        $this->assertTrue($obj === $method->invoke($service, 'obj'));
    }
}