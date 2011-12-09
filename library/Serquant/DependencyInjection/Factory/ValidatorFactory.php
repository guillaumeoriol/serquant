<?php
/**
 * This file is part of the Serquant library.
 *
 * PHP version 5.3
 *
 * @category Serquant
 * @package  Factory
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
namespace Serquant\DependencyInjection\Factory;

use Symfony\Component\Validator\ConstraintValidatorFactory;
use Symfony\Component\Validator\ValidatorContext;
use Symfony\Component\Validator\ValidatorContextInterface;
use Symfony\Component\Validator\ValidatorInterface;
use Symfony\Component\Validator\Exception\MappingException;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\Mapping\Loader\XmlFilesLoader;
use Symfony\Component\Validator\Mapping\Loader\YamlFilesLoader;
use Serquant\DependencyInjection\Factory\AnnotationReaderFactory;

/**
 * Factory used by the service container (DependencyInjection) to bootstrap
 * Symfony Validator and return an instance of it.
 *
 * @category Serquant
 * @package  Factory
 * @author   Guillaume Oriol <goriol@serquant.com>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @link     http://www.serquant.com/
 */
class ValidatorFactory
{
    /**
     * Factory method to be called by the service container in order to get
     * a validator instance.
     *
     * Sample configuration file:
     * <pre>
     * parameters:
     *   validator_config:
     *     mappingFiles:
     *       - validation.xml
     *     annotations:
     *       annotationReader: @annotation_reader
     *     staticMethod:
     *
     * services:
     *   validator:
     *     class: Symfony\Component\Validator\Validator
     *     factory_class: Serquant\Validator\DependencyInjection\ValidatorFactory
     *     factory_method: get
     *     arguments: [%validator_config%]
     * </pre>
     *
     * @param array $config Service configuration options
     * @return ValidatorInterface
     */
    static public function get($config)
    {
        $xmlMappingFiles = array();
        $yamlMappingFiles = array();
        $loaders = array();
        $context = new ValidatorContext();

        if (isset($config['mappingFiles'])) {
            if (!is_array($config['mappingFiles'])) {
                $config['mappingFiles'] = array($config['mappingFiles']);
            }
            foreach ($config['mappingFiles'] as $file) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);

                if ($extension === 'xml') {
                    $xmlMappingFiles[] = $file;
                } else if ($extension === 'yaml' || $extension === 'yml') {
                    $yamlMappingFiles[] = $file;
                } else {
                    throw new MappingException(
                        'The only supported mapping file formats are XML and '
                        . 'YAML. ".' . $extension . '" given.'
                    );
                }
            }
        }

        if (count($xmlMappingFiles) > 0) {
            $loaders[] = new XmlFilesLoader($xmlMappingFiles);
        }

        if (count($yamlMappingFiles) > 0) {
            $loaders[] = new YamlFilesLoader($yamlMappingFiles);
        }

        if (isset($config['annotations'])) {
            $options = $config['annotations'];
            $reader = isset($options['annotationReader'])
                ? $options['annotationReader']
                : AnnotationReaderFactory::get($options);
            $loaders[] = new AnnotationLoader($reader);
        }

        if (isset($config['staticMethod'])) {
            $loaders[] = new StaticMethodLoader($config['staticMethod']);
        }

        if (count($loaders) > 1) {
            $loader = new LoaderChain($loaders);
        } else if (count($loaders) === 1) {
            $loader = $loaders[0];
        } else {
            throw new MappingException(
                'No mapping loader was found for the given parameters'
            );
        }

        $context->setClassMetadataFactory(new ClassMetadataFactory($loader));
        $context->setConstraintValidatorFactory(new ConstraintValidatorFactory());

        $factory = new static($context);
        return $factory->getValidator();
    }

    /**
     * Sets the given context as default context
     *
     * @param ValidatorContextInterface $defaultContext A preconfigured context
     */
    public function __construct(ValidatorContextInterface $defaultContext = null)
    {
        $this->defaultContext = null === $defaultContext ?
            new ValidatorContext() : $defaultContext;
    }

    /**
     * Creates a new validator with the settings stored in the default context
     *
     * @return ValidatorInterface The new validator
     */
    public function getValidator()
    {
        return $this->defaultContext->getValidator();
    }
}
