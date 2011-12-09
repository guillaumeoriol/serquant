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
namespace Serquant\Test\Entity\Registry;

use Serquant\Entity\Registry\DoctrineGateway;

/**
 * DoctrineGateway test class
 *
 * @category Serquant
 * @package  Test
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class DoctrineGatewayTest extends \Doctrine\Tests\OrmTestCase
{
    private $em;

    private $uow;

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();
    }

    public function testGetEntityIdentifier()
    {
        $entityName = '\Serquant\Resource\Persistence\Doctrine\Entity\User';
        $entity = new $entityName;
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';
        $this->em->persist($entity);

        $gateway = new DoctrineGateway($this->em->getUnitOfWork());

        $id = $gateway->getEntityIdentifier($entity);
        $this->assertInternalType('array', $id);
        $this->assertContains(1, $id);
    }
}