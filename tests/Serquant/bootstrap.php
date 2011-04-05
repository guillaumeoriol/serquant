<?php
define('PROJECT_ROOT', realpath(dirname(__FILE__) . '/../..'));
define('TEST_PATH', PROJECT_ROOT . '/tests');

set_include_path(implode(PATH_SEPARATOR, array(
    PROJECT_ROOT . '/library',
    TEST_PATH . '/library',
    get_include_path(),
)));

require_once 'Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
    'Serquant' => PROJECT_ROOT . '/library',
	'Symfony' => TEST_PATH . '/library'
));
$loader->register();
