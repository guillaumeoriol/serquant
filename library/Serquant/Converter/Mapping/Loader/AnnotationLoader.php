<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Loader
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\Converter\Mapping\Loader;

use Doctrine\Common\Annotations\Reader;
use Serquant\Converter\Mapping\ClassMetadata;

/**
 * Metadata loader for annotations.
 *
 * @category Serquant
 * @package  Loader
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class AnnotationLoader implements LoaderInterface
{
    /**
     * Reader used to get annotations from a class.
     * @var Reader
     */
    protected $reader;

    /**
     * Constructs an AnnotationLoader instance.
     *
     * @param Reader $reader Annotation reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritDoc}
     *
     * @param ClassMetadata $metadata The object to put metadata into.
     * @return void
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        $reflClass = $metadata->getReflectionClass();
        $className = $reflClass->getName();

        if ($annotation = $this->reader->getClassAnnotation(
            $reflClass, 'Serquant\Converter\Mapping\Entity'
        )) {
            $metadata->setIdentifierPrefix($annotation->getPrefix());
        }

        foreach ($reflClass->getProperties() as $reflProp) {
            if ($reflProp->getDeclaringClass()->getName() === $className) {
                $name = $reflProp->getName();
                if ($annotation = $this->reader->getPropertyAnnotation(
                    $reflProp, 'Serquant\Converter\Mapping\Id'
                )) {
                    $metadata->setIdentifier($name);
                }

                if ($annotation = $this->reader->getPropertyAnnotation(
                    $reflProp, 'Serquant\Converter\Mapping\Property'
                )) {
                    $metadata->addProperty($name, $annotation);
                    $metadata->addReflectionProperty($name, $reflProp);
                }
            }
        }
    }
}