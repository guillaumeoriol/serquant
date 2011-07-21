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
namespace Serquant\Test;

use Serquant\Entity\Serializer,
    Serquant\Entity\Registry\Ormless;

class SerializerTest extends \Serquant\Resource\Persistence\ZendTestCase
{
    private $db;

    private $em;

    private $registry;

    private $serializer;

    private $entities = array();

    private $expected = array();

    protected function setUp()
    {
        $this->db = $this->getTestAdapter();
        \Zend_Db_Table::setDefaultAdapter($this->db);
        $this->em = $this->getTestEntityManager();

        $mf = $this->em->getMetadataFactory();
        $this->registry = new Ormless($mf);
        $this->serializer = new Serializer($mf, $this->registry);

        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->id = 1;
        $entity->status = 'deprecated';
        $entity->username = 'gw';
        $entity->name = 'Washington';
        $entity->transientProperty = 'whatever';
        $this->entities[] = $entity;

        $expected = array(
            'id' => $entity->id,
            'status' => $entity->status,
            'username' => $entity->username,
            'name' => $entity->name
        );
        $this->expected[] = $expected;

        $entity = new \Serquant\Resource\Persistence\Zend\User();
        $entity->id = 2;
        $entity->status = 'deprecated';
        $entity->username = 'ja';
        $entity->name = 'Adams';
        $entity->transientProperty = 'whatever';
        $this->entities[] = $entity;

        $expected = array(
            'id' => $entity->id,
            'status' => $entity->status,
            'username' => $entity->username,
            'name' => $entity->name
        );
        $this->expected[] = $expected;
    }

    public function testSimpleEntityToArray()
    {
        $this->assertEquals(
            $this->expected[0],
            $this->serializer->toArray($this->entities[0])
        );
    }

    public function testSimpleEntityArrayToArray()
    {
        $this->assertEquals(
            $this->expected,
            $this->serializer->toArray($this->entities)
        );
    }

    public function testSimpleEntityCollectionToArray()
    {
        $collection = new \Doctrine\Common\Collections\ArrayCollection($this->entities);
        $this->assertEquals(
            $this->expected,
            $this->serializer->toArray($collection)
        );
    }

    public function testEntityWithMappedSuperclassInheritanceToArray()
    {
        $entity = new \Serquant\Resource\Persistence\Zend\SubclassA();
        $entity->id = 1;
        $entity->name = 'Washington';
        $entity->specificToA = 'A';
        $entity->setSavedAt(new \DateTime('2011-01-01 00:00:00'));

        $expected = array(
            'id' => 1,
            'name' => 'Washington',
            'savedAt' => '2011-01-01T00:00:00+01:00',
            'savedBy' => null,
        	'specificToA' => 'A',
        );
        $this->assertEquals(
            $expected,
            $this->serializer->toArray($entity)
        );
    }

    public function testOneToOneAssociationWithoutValue()
    {
        $issue = new \Serquant\Resource\Persistence\Zend\Issue();
        $issue->id = 2;
        $issue->title = 'Issue title';
        $issue->reporter = null;
        $this->registry->put($issue);

        $expected = array(
			'id' => 2,
            'reporter' => null,
            'title' => 'Issue title'
        );
        $this->assertEquals(
            $expected,
            $this->serializer->toArray($issue)
        );

        $this->assertEquals(
            '{"id":2,"title":"Issue title","reporter":null}',
            $this->serializer->toJson($issue)
        );

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
                  . '<Issue><id>2</id><title>Issue title</title><reporter/></Issue>'
                  . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->serializer->toXml($issue)
        );
    }

    public function testOneToOneAssociation()
    {
        $person = new \Serquant\Resource\Persistence\Zend\Person();
        $person->id = 1;
        $person->firstName = 'Thomas';
        $person->lastName = 'Jefferson';
        $this->registry->put($person);

        $issue = new \Serquant\Resource\Persistence\Zend\Issue();
        $issue->id = 2;
        $issue->title = 'Issue title';
        $issue->reporter = $person;
        $this->registry->put($issue);

        $expected = array(
			'id' => 2,
            'reporter' => array('$ref' => 1),
            'title' => 'Issue title'
        );
        $this->assertEquals(
            $expected,
            $this->serializer->toArray($issue)
        );

        $this->assertEquals(
            '{"id":2,"title":"Issue title","reporter":{"$ref":1}}',
            $this->serializer->toJson($issue)
        );

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
                  . '<Issue><id>2</id><title>Issue title</title><reporter>1</reporter></Issue>'
                  . PHP_EOL;
        $this->assertEquals(
            $expected,
            $this->serializer->toXml($issue)
        );
    }
}