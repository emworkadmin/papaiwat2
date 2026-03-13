<?php

class System_Model_Log
{

	protected $db = null;
	protected $UserID = null;
	protected $auth = null;

	/**
	 *
	 */
	function __construct() {
		try {
			// setup database
			$this->db = Zend_Db_Table::getDefaultAdapter();
				
			// do the authentication
			$authobj = Zend_Auth::getInstance();
			$this->auth = $authobj->getIdentity();
			$this->UserID = isset($this->auth->UserID) ? $this->auth->UserID : '';
		} catch (Exception $e) {
			
		}
	}
	
	public static function viewAmount() {
		$amount = 0;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			
			$select = $db->select()
			->from(array("tbl" => "log_access"), array("Amount" => "count(tbl.ID)"))
			->where("tbl.SiteType = 'F' ")
			->where("tbl.IsAjax = 'N' ")
			->where("tbl.IsDeleted = 'N' ")
			;
			
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->Amount)) {
				$amount = $data->Amount;
			}
		} catch (Exception $e) {
			$amount = 0;
		}
		return $amount;
	}
	
	public static function access() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$authobj = Zend_Auth::getInstance();
		$userId = null;
		if ($authobj->hasIdentity()) {
			$userId = $authobj->getIdentity()->UserID;
		}
	
		$data = new stdClass();
		$data->result = false;
		try {
				
			$request = Zend_Controller_Front::getInstance()->getRequest();
				
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'Mobile_Detect.php';
			$detect = new Mobile_Detect();
			$form  = array();
				
			if ( $detect->isMobile() ) {
	
				$browsers = Mobile_Detect::getBrowsers();
				foreach ($browsers as $key => $val) {
					if ($detect->is($key)) {
						$form["Browser"] = $key;
						$form["Version"] = $detect->version($key);
						break;
					}
				}
	
				$phoneDevices = Mobile_Detect::getPhoneDevices();
				foreach ($phoneDevices as $key => $val) {
					if ($detect->is($key)) {
						$form["Mobile"] = $key;
						break;
					}
				}
	
				$operatingSystems = Mobile_Detect::getOperatingSystems();
				foreach ($operatingSystems as $key => $val) {
					if ($detect->is($key)) {
						$form["Platform"] = $key;
						break;
					}
				}
	
			} else {
				$userAgent = new Zend_Http_UserAgent();
				$device = $userAgent->getDevice();
	
	
				$form["Browser"] = $device->getBrowser();
				$form["Version"] = $device->getBrowserVersion();
				$form["Mobile"] = $device->getFeature('mobile_browser');
				$form["Platform"] = $device->getFeature('compatibility_flag');
			}
				
			$form["UserID"] = $userId;
			$form["SessionID"] = Zend_Session::getId();
			$form["UriString"] = $request->getRequestUri();
			$form["RemoteIP"] = $request->getServer('REMOTE_ADDR');
			if (!empty($form["RemoteIP"])) {
				if (function_exists('geoip_country_code_by_name')) {
					$countryCode = geoip_country_code_by_name($form["RemoteIP"]);
					$form["GeoIP"] = $countryCode;
				}
			}
			//$form["rebot"] = $baseName;
			$form["Referrer"] = $request->getServer('HTTP_REFERER');
			$form["Agent"] = $request->getServer('HTTP_USER_AGENT');
			$form["AcceptLang"] = $request->getServer('HTTP_ACCEPT_LANGUAGE');
			//$form["accept_charset"] = $baseName;
			$form["SiteType"] = 'F';
			$form["ActionDate"] = date('Y-m-d');
			$form["Created"] = date('Y-m-d H:i:s');
			$form["CreatedBy"] = $userId;
				
			$result = $db->insert('log_access', $form);
			$lastid = $db->lastInsertId(); //ค่า ID ที่เพิ่ง Insert ล่าสุด
			$data->result = $result > 0;

			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public static function securitry($userId, $log_type, $username, $password, $result, $note, $ip_address) {
	
		$db = Zend_Db_Table::getDefaultAdapter();
		$authobj = Zend_Auth::getInstance();
	
		try {
	
			// Insert
			$form = array();
			$form["UserID"] = empty($userId) ? null : $userId;
			$form["LogType"] = $log_type;
			$form["Date"] = date('Y-m-d H:i:s');
			$form["Username"] = $username;
			$form["Password"] = $password;
			$form["Result"] = $result;
			$form["Note"] = $note;
			$form["IpAddress"] = $ip_address;
			$form["SiteType"] = 'F';
			$form["Created"] = date('Y-m-d H:i:s');
			$form["Created_by"] = empty($userId) ? null : $userId;
			$db->insert('log_securities', $form);
			$lastid = $db->lastInsertId();
			
		} catch (Exception $e) {
			
		}
	}
}
?>
