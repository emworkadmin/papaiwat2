<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 *
 */

/**
 * Zend
 */
require_once 'Zend/Db/Adapter/Exception.php';

/**
 * App_Db_Adapter_Sqlsrv_Exception
 *
 * @category   Zend
 * @package    Zend_Db
 * @subpackage Adapter
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class App_Db_Adapter_Sqlsrv_Exception extends Zend_Db_Adapter_Exception
{
	protected $message = 'Unknown exception';
	protected $code = 0;

	/**
	 *
	 * @param array|string $error
	 * @param int $code
	 */
	function __construct($error = null, $code = 0) {

		if (is_array($error)) {
			// Error should be array of errors
			// We only need first one (?)
			if (isset($error[0]))
				$error = $error[0];

			$this->message = (string) $error['message'];
			$this->code = $error['code'];
		} else if (is_string($error)) {
			$this->message = $error;
		}

		if (!$this->code && $code) {
			$this->code = $code;
		}
	}
}
