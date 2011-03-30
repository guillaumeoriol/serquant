<?php
error_reporting(E_ALL | E_STRICT);

$rootPath = realpath(dirname(__FILE__) . '/../../');

// Define path to test directory
defined('TEST_PATH')
    || define('TEST_PATH', $rootPath . DIRECTORY_SEPARATOR . 'tests');

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', $rootPath . DIRECTORY_SEPARATOR . 'application');

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', 'development');

// Ensure library/ is on the include_path
set_include_path(implode(PATH_SEPARATOR, array(
    $rootPath . DIRECTORY_SEPARATOR . 'library',
    get_include_path(),
)));

require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();

