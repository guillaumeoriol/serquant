<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Test\Converter;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Serquant\Converter\Mapping\ClassMetadataFactory;
use Serquant\Converter\Mapping\Loader\AnnotationLoader;
use Serquant\Converter\Serializer;
use Serquant\DependencyInjection\Factory\AnnotationReaderFactory;

/**
 * Test class of the Serializer.
 *
 * @category Serquant
 * @package  Converter
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class SerializerTest extends \PHPUnit_Framework_TestCase
{
    private $reader;

    private $loader;

    private $factory;

    protected function setUp()
    {
        AnnotationRegistry::reset();
        $autoloadNamespaces = array(
    		'Serquant\Converter\Mapping' => APPLICATION_ROOT . '/library'
		);
        $config = array(
        	'annotationAutoloadNamespaces' => $autoloadNamespaces,
    		'ignoreNotImportedAnnotations' => true
        );

        $this->reader = AnnotationReaderFactory::get($config);
        $this->loader = new AnnotationLoader($this->reader);
        $this->factory = new ClassMetadataFactory($this->loader);
    }

    public function testConstruct()
    {
        $serializer = new Serializer($this->factory);
        $this->assertInstanceOf('Serquant\Converter\Serializer', $serializer);
    }

    public function testDeserializeWithInvalidArgument()
    {
        $serializer = new Serializer($this->factory);
        $entity = new \stdClass();
        $data = false;

        $this->setExpectedException('Serquant\Converter\Exception\InvalidArgumentException');
        $violations = $serializer->deserialize($entity, $data);
    }

    public function testDeserializeWithPublicPropertiesThatAreConvertible()
    {
        $serializer = new Serializer($this->factory);
        $class = 'Serquant\Resource\Converter\UserWithPublicProperties';
        $entity = new $class;
        $data = array(
            'id' => '1',
            'status' => 'true',
            'username' => 'Washington'
        );
        $violations = $serializer->deserialize($entity, $data);
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $violations);

        $this->assertInternalType('integer', $entity->id);
        $this->assertSame(1, $entity->id);

        $this->assertInternalType('boolean', $entity->status);
        $this->assertSame(true, $entity->status);

        $this->assertInternalType('string', $entity->username);
        $this->assertSame('Washington', $entity->username);
    }

    public function testDeserializeWithPrivatePropertiesThatAreConvertible()
    {
        $serializer = new Serializer($this->factory);
        $class = 'Serquant\Resource\Converter\UserWithPrivateProperties';
        $entity = new $class;
        $data = array(
            'id' => '1',
            'status' => 'true',
            'username' => 'Washington'
        );
        $violations = $serializer->deserialize($entity, $data);
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $violations);

        $this->assertInternalType('integer', $entity->getId());
        $this->assertSame(1, $entity->getId());

        $this->assertInternalType('boolean', $entity->getStatus());
        $this->assertSame(true, $entity->getStatus());

        $this->assertInternalType('string', $entity->getUsername());
        $this->assertSame('Washington', $entity->getUsername());
    }

    public function testDeserializeWithPublicPropertiesThatAreNotConvertible()
    {
        $serializer = new Serializer($this->factory);
        $class = 'Serquant\Resource\Converter\UserWithPublicPropertiesNotConvertible';
        $entity = new $class;
        $data = array(
            'id' => '1',
            'status' => 'true',
            'username' => 'Washington',
            'role' => array(
                'id' => '2',
                'name' => 'guest'
            )
        );
        $violations = $serializer->deserialize($entity, $data);
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $violations);

        $this->assertInternalType('integer', $entity->id);
        $this->assertSame(1, $entity->id);

        $this->assertInternalType('boolean', $entity->status);
        $this->assertSame(true, $entity->status);

        $this->assertInternalType('string', $entity->username);
        $this->assertSame('Washington', $entity->username);

        $this->assertInstanceOf('Serquant\Resource\Converter\RoleWithPublicProperties', $entity->role);

        $this->assertInternalType('integer', $entity->role->id);
        $this->assertSame(2, $entity->role->id);

        $this->assertInternalType('string', $entity->role->name);
        $this->assertSame('guest', $entity->role->name);
    }

    public function testDeserializeWithPrivatePropertiesThatAreNotConvertible()
    {
        $serializer = new Serializer($this->factory);
        $class = 'Serquant\Resource\Converter\UserWithPrivatePropertiesNotConvertible';
        $entity = new $class;
        $data = array(
            'id' => '1',
            'status' => 'true',
            'username' => 'Washington',
            'role' => array(
                'id' => '2',
                'name' => 'guest'
            )
        );
        $violations = $serializer->deserialize($entity, $data);
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $violations);

        $this->assertInternalType('integer', $entity->getId());
        $this->assertSame(1, $entity->getId());

        $this->assertInternalType('boolean', $entity->getStatus());
        $this->assertSame(true, $entity->getStatus());

        $this->assertInternalType('string', $entity->getUsername());
        $this->assertSame('Washington', $entity->getUsername());

        $this->assertInstanceOf('Serquant\Resource\Converter\RoleWithPrivateProperties', $entity->getRole());

        $this->assertInternalType('integer', $entity->getRole()->getId());
        $this->assertSame(2, $entity->getRole()->getId());

        $this->assertInternalType('string', $entity->getRole()->getName());
        $this->assertSame('guest', $entity->getRole()->getName());
    }

    public function testDeserializeWithEmptySinglevaluedProperty()
    {
        $serializer = new Serializer($this->factory);
        $class = 'Serquant\Resource\Converter\UserWithPublicPropertiesNotConvertible';
        $entity = new $class;
        $data = array(
            'id' => '1',
            'status' => 'true',
            'username' => 'Washington',
            'role' => null
        );
        $violations = $serializer->deserialize($entity, $data);
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $violations);

        $this->assertInternalType('integer', $entity->id);
        $this->assertSame(1, $entity->id);

        $this->assertInternalType('boolean', $entity->status);
        $this->assertSame(true, $entity->status);

        $this->assertInternalType('string', $entity->username);
        $this->assertSame('Washington', $entity->username);

        $this->assertNull($entity->role);
    }

    public function testDeserializeWithMultivaluedPropertyOfWrongType()
    {
        $serializer = new Serializer($this->factory);
        $class = 'Serquant\Resource\Converter\Person';
        $entity = new $class;
        $data = array(
            'id' => '1',
            'name' => 'Washington',
            'cars' => 1
        );

        $this->setExpectedException('Serquant\Converter\Exception\InvalidArgumentException');
        $violations = $serializer->deserialize($entity, $data);
    }

    public function testDeserializeWithMultivaluedProperty()
    {
        $serializer = new Serializer($this->factory);
        $class = 'Serquant\Resource\Converter\Person';
        $entity = new $class;
        $data = array(
            'id' => '1',
            'name' => 'Washington',
            'cars' => array(
                array('id' => '10', 'numberPlate' => 'AB-123-CD'),
                array('id' => '11', 'numberPlate' => 'ZY-987-XW')
            )
        );
        $violations = $serializer->deserialize($entity, $data);
        $this->assertInstanceOf('Symfony\Component\Validator\ConstraintViolationList', $violations);

        $this->assertInternalType('integer', $entity->id);
        $this->assertSame(1, $entity->id);

        $this->assertInternalType('string', $entity->name);
        $this->assertSame('Washington', $entity->name);

        $metadata = $this->factory->getClassMetadata($class);
        $property = $metadata->getProperty('cars');
        $cars = $entity->cars;
        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $entity->cars);

        $i = 0;
        foreach ($cars as $car) {
            $this->assertInstanceOf($property->getType(), $car);

            $this->assertInternalType('integer', $car->id);
            $this->assertSame((int)$data['cars'][$i]['id'], $car->id);

            $this->assertInternalType('string', $car->numberPlate);
            $this->assertSame($data['cars'][$i]['numberPlate'], $car->numberPlate);
            $i++;
        }
    }

    public function testDeserializeWithoutViolations()
    {
        $serializer = new Serializer($this->factory);

        $class = 'Serquant\Resource\Converter\Person';
        $entity = new $class;

        $data = array(
            'id' => NULL,
            'name' => 'test'
        );

        $violations = $serializer->deserialize($entity, $data);
        $this->assertEquals(0, count($violations));
    }

    public function testDeserializeWithViolations()
    {
        $serializer = new Serializer($this->factory);

        $class = 'Serquant\Resource\Converter\Person';
        $entity = new $class;

        $data = array(
            'id' => NULL,
            'name' => new \stdClass()
        );

        $violations = $serializer->deserialize($entity, $data);
        $this->assertEquals(1, count($violations));
    }

    // -------------------------------------------------------------------------

    public function testToDomWithPublicProperties()
    {
        $serializer = new Serializer($this->factory);

        $userClass = 'Serquant\Resource\Converter\UserWithPublicProperties';
        $user = new $userClass;
        $user->id = 1;
        $user->status = null;
        $user->username = 'Washington';

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
                  . '<UserWithPublicProperties>'
                  .   '<id>1</id>'
                  .   '<status/>'
                  .   '<username>Washington</username>'
                  . '</UserWithPublicProperties>' . PHP_EOL;

        $this->assertEquals($expected, $serializer->toXml($user));
    }

    public function testToDomWithPrivateProperties()
    {
        $serializer = new Serializer($this->factory);

        $userClass = 'Serquant\Resource\Converter\UserWithPrivateProperties';
        $user = new $userClass;
        $user->setId(1);
        $user->setStatus(true);
        $user->setUsername('Washington');

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
                  . '<UserWithPrivateProperties>'
                  .   '<id>1</id>'
                  .   '<status>1</status>'
                  .   '<username>Washington</username>'
                  . '</UserWithPrivateProperties>' . PHP_EOL;

        $this->assertEquals($expected, $serializer->toXml($user));
    }

    public function testToDomWithNotConvertiblePropertyThatIsNull()
    {
        $serializer = new Serializer($this->factory);

        $userClass = 'Serquant\Resource\Converter\UserWithPublicPropertiesNotConvertible';
        $user = new $userClass;
        $user->id = 1;
        $user->status = true;
        $user->username = 'Washington';
        $user->role = null;

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
                  . '<UserWithPublicPropertiesNotConvertible>'
                  .   '<id>1</id>'
                  .   '<status>1</status>'
                  .   '<username>Washington</username>'
                  .   '<RoleWithPublicProperties/>'
                  . '</UserWithPublicPropertiesNotConvertible>' . PHP_EOL;

        $this->assertEquals($expected, $serializer->toXml($user));
    }

    public function testToDomWithNotConvertiblePropertiesWithoutAccessors()
    {
        $serializer = new Serializer($this->factory);

        $roleClass = 'Serquant\Resource\Converter\RoleWithPublicProperties';
        $role = new $roleClass;
        $role->id = 2;
        $role->name = 'guest';

        $userClass = 'Serquant\Resource\Converter\UserWithPublicPropertiesNotConvertible';
        $user = new $userClass;
        $user->id = 1;
        $user->status = true;
        $user->username = 'Washington';
        $user->role = $role;

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
                  . '<UserWithPublicPropertiesNotConvertible>'
                  .   '<id>1</id>'
                  .   '<status>1</status>'
                  .   '<username>Washington</username>'
                  .   '<RoleWithPublicProperties>'
                  .     '<id>2</id>'
                  .     '<name>guest</name>'
                  .   '</RoleWithPublicProperties>'
                  . '</UserWithPublicPropertiesNotConvertible>' . PHP_EOL;

        $this->assertEquals($expected, $serializer->toXml($user));
    }

    public function testToDomWithMultivaluedProperty()
    {
        $serializer = new Serializer($this->factory);

        $carClass = 'Serquant\Resource\Converter\Car';
        $car1 = new $carClass;
        $car1->id = 10;
        $car1->numberPlate = 'AB-123-CD';
        $car2 = new $carClass;
        $car2->id = 11;
        $car2->numberPlate = 'ZY-987-XW';

        $personClass = 'Serquant\Resource\Converter\Person';
        $person = new $personClass;
        $person->id = 1;
        $person->name = 'Washington';
        $person->cars->add($car1);
        $person->cars->add($car2);

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
                  . '<Person>'
                  .   '<id>1</id>'
                  .   '<name>Washington</name>'
                  .   '<cars>'
                  .     '<Car><id>10</id><numberPlate>AB-123-CD</numberPlate></Car>'
                  .     '<Car><id>11</id><numberPlate>ZY-987-XW</numberPlate></Car>'
                  .   '</cars>'
                  . '</Person>' . PHP_EOL;

        $this->assertEquals($expected, $serializer->toXml($person));
    }

    public function testToDomWithEntityCollection()
    {
        $serializer = new Serializer($this->factory);

        $carClass = 'Serquant\Resource\Converter\Car';
        $car1 = new $carClass;
        $car1->id = 10;
        $car1->numberPlate = 'AB-123-CD';
        $car2 = new $carClass;
        $car2->id = 11;
        $car2->numberPlate = 'ZY-987-XW';
        $cars = array($car1, $car2);

        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
                  . '<Car><id>10</id><numberPlate>AB-123-CD</numberPlate></Car>'
                  . PHP_EOL
                  . '<Car><id>11</id><numberPlate>ZY-987-XW</numberPlate></Car>'
                  . PHP_EOL;

        $this->assertEquals($expected, $serializer->toXml($cars));
    }

    // =========================================================================

    public function testSerializeWithInvalidArgument()
    {
        $serializer = new Serializer($this->factory);

        $this->setExpectedException('Serquant\Converter\Exception\InvalidArgumentException');
        $data = $serializer->serialize(false);
    }

    public function testSerializeConvertiblePropertiesWithoutAccessors()
    {
        $serializer = new Serializer($this->factory);

        $userClass = 'Serquant\Resource\Converter\UserWithPublicProperties';
        $user = new $userClass;
        $user->id = 1;
        $user->status = true;
        $user->username = 'Washington';

        $data = $serializer->serialize($user);
        $this->assertEquals(
            array(
                'id' => '1',
                'status' => '1',
                'username' => 'Washington'
            ),
            $data
        );
    }

    public function testSerializeConvertiblePropertiesWithAccessors()
    {
        $serializer = new Serializer($this->factory);

        $userClass = 'Serquant\Resource\Converter\UserWithPrivateProperties';
        $user = new $userClass;
        $user->setId(1);
        $user->setStatus(true);
        $user->setUsername('Washington');

        $data = $serializer->serialize($user);
        $this->assertEquals(
            array(
                'id' => '1',
                'status' => '1',
                'username' => 'Washington'
            ),
            $data
        );
    }

    public function testSerializeNotConvertiblePropertyThatIsNull()
    {
        $serializer = new Serializer($this->factory);

        $userClass = 'Serquant\Resource\Converter\UserWithPublicPropertiesNotConvertible';
        $user = new $userClass;
        $user->id = 1;
        $user->status = true;
        $user->username = 'Washington';

        $expected = array(
            'id' => '1',
            'status' => '1',
            'username' => 'Washington',
            'role' => null
        );

        $data = $serializer->serialize($user);
        $this->assertEquals($expected, $data);
    }

    public function testSerializeNotConvertiblePropertiesWithoutAccessors()
    {
        $serializer = new Serializer($this->factory);

        $roleClass = 'Serquant\Resource\Converter\RoleWithPublicProperties';
        $role = new $roleClass;
        $role->id = 2;
        $role->name = 'guest';

        $userClass = 'Serquant\Resource\Converter\UserWithPublicPropertiesNotConvertible';
        $user = new $userClass;
        $user->id = 1;
        $user->status = true;
        $user->username = 'Washington';
        $user->role = $role;

        $data = $serializer->serialize($user);
        $this->assertEquals(
            array(
                'id' => '1',
                'status' => '1',
                'username' => 'Washington',
                'role' => array(
                    'id' => '2',
                    'name' => 'guest'
                )
            ),
            $data
        );
    }

    public function testSerializeNotConvertiblePropertiesWithAccessors()
    {
        $serializer = new Serializer($this->factory);

        $roleClass = 'Serquant\Resource\Converter\RoleWithPrivateProperties';
        $role = new $roleClass;
        $role->setId(2);
        $role->setName('guest');

        $userClass = 'Serquant\Resource\Converter\UserWithPrivatePropertiesNotConvertible';
        $user = new $userClass;
        $user->setId(1);
        $user->setStatus(true);
        $user->setUsername('Washington');
        $user->setRole($role);

        $data = $serializer->serialize($user);
        $this->assertEquals(
            array(
                'id' => '1',
                'status' => '1',
                'username' => 'Washington',
                'role' => array(
                    'id' => '2',
                    'name' => 'guest'
                )
            ),
            $data
        );
    }

    public function testSerializeWithMultivaluedProperty()
    {
        $serializer = new Serializer($this->factory);

        $carClass = 'Serquant\Resource\Converter\Car';
        $car1 = new $carClass;
        $car1->id = 10;
        $car1->numberPlate = 'AB-123-CD';
        $car2 = new $carClass;
        $car2->id = 11;
        $car2->numberPlate = 'ZY-987-XW';

        $personClass = 'Serquant\Resource\Converter\Person';
        $person = new $personClass;
        $person->id = 1;
        $person->name = 'Washington';
        $person->cars->add($car1);
        $person->cars->add($car2);

        $expected = array(
            'id' => '1',
            'name' => 'Washington',
            'cars' => array(
                array('id' => '10', 'numberPlate' => 'AB-123-CD'),
                array('id' => '11', 'numberPlate' => 'ZY-987-XW')
            )
        );

        $data = $serializer->serialize($person);
        $this->assertEquals($expected, $data);
    }
}