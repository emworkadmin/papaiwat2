<?php

define('DS', DIRECTORY_SEPARATOR);
define('PS', PATH_SEPARATOR);

// Define path to application directory
defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../applicationadmin'));
    
if (!defined('APPLICATION_ROOT')) {
	define('APPLICATION_ROOT', realpath(dirname(__FILE__) . '/../..'));
}

// Define application environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
    
// Define Upload Path
define('UPLOAD_PATH',realpath(dirname(__FILE__)) . DS . '..' . DS . 'uploads');

// Ensure library/ is on include_path
set_include_path(
	implode(
		PATH_SEPARATOR, 
		array(
			realpath(APPLICATION_ROOT . DS . 'library'),
			get_include_path(),
			APPLICATION_PATH .'/models/'		
		)
	)
);

require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();
$loader->setFallbackAutoloader(true);

/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);
$application->bootstrap()->run();