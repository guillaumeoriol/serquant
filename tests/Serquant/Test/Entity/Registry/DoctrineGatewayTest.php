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
use Serquant\Resource\Persistence\Doctrine\Entity\EntityWithApplicationAssignedId;

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

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();
    }

    public function testGetEntityIdentifier()
    {
        $entity = new EntityWithApplicationAssignedId;
        $entity->id = 1;
        $entity->name = 'whatever';
        $this->em->persist($entity);
        // Do not flush the persist operation as we only want to store the
        // entity in the registry of loaded entities.

        $gateway = new DoctrineGateway($this->em->getUnitOfWork());

        $id = $gateway->getEntityIdentifier($entity);
        $this->assertInternalType('array', $id);
        $this->assertEquals($entity->id, current($id));
    }
}