<?php
define('APPLICATION_ROOT', realpath(dirname(__FILE__) . '/../..'));

define('TEST_PATH', APPLICATION_ROOT . '/tests');

// Ensure Zend is on include_path for Zend Framework internal 'require_once'
set_include_path(implode(PATH_SEPARATOR, array(
    TEST_PATH . '/library',
    get_include_path(),
)));

require_once TEST_PATH . '/library/Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
    'Serquant\\Resource' => TEST_PATH, // Test resources (entities, etc.)
    'Serquant' => APPLICATION_ROOT . '/library',
    'Doctrine' => TEST_PATH . '/library',
    'Symfony' => TEST_PATH . '/library'
));
$loader->registerPrefix('Zend_', TEST_PATH . '/library');
$loader->register();
