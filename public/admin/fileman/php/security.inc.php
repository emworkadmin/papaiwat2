<?php
/*
  RoxyFileman - web based file manager. Ready to use with CKEditor, TinyMCE. 
  Can be easily integrated with any other WYSIWYG editor or CMS.

  Copyright (C) 2013, RoxyFileman.com - Lyubomir Arsov. All rights reserved.
  For licensing, see LICENSE.txt or http://RoxyFileman.com/license

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.

  Contact: Lyubomir Arsov, liubo (at) web-lobby.com
*/
function checkAccess($action){

  define('DS', DIRECTORY_SEPARATOR);
  define('PS', PATH_SEPARATOR);
  // Define path to application directory
  defined('APPLICATION_PATH') || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../../../applicationadmin'));
  define('APPLICATION_ROOT', realpath(dirname(__FILE__) . '/../../../..'));
  
  // Define application environment
  defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));
  
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
  
  //$application->bootstrap()->run();
  $application->bootstrap();
  
  $auth = Zend_Auth::getInstance();
  if ($auth->hasIdentity()) {
  	$authObj = $auth->getIdentity();
  	if (empty($authObj->UserID)) {
  		exit;
  	}
  } else {
  	exit;
  }
}
?>