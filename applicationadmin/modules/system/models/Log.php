<?php

class System_Model_Log {

	/**
	 * 
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;

	function __construct() {
		try {
			// setup database
			$this->db = Zend_Db_Table::getDefaultAdapter();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance();
			$this->auth = $authobj->getIdentity();
			$this->UserID = isset($this->auth->UserID) ? $this->auth->UserID : '';
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	
	function getActionList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			// set query maininfo
			$select = $this->db->select()
			->from(array("tbl" => "log_action"), "")
			->joinLeft(array("mi" => "module_info"), "tbl.Module = mi.Code", "")
			->joinLeft(array("ma" => "module_action"), "tbl.ActionType = ma.Code", "")
			->joinLeft(array("u" => "sys_user"), "tbl.CreatedBy = u.ID", "")
			->columns(array(
					"ID" => "tbl.ID",
					"ModuleName" => "mi.Name",
					"FullName" => "concat(u.FirstName, ' ', u.LastName)",
					"ActionType" => "tbl.ActionType",
					"ActionTypeName" => "ma.Name",
					"ActionDate" => "tbl.ActionDate",
					"ValueString" => "tbl.ValueString",
					"RemoteIP" => "tbl.RemoteIP"
			));
			
			// set criteria
			if (!empty($advancesearch['textsearch'])) {
				$select = $select->where("tbl.ValueString like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%") . " or concat(u.FirstName, ' ', u.LastName) like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
			}
			if (!empty($advancesearch['system'])) {
				$select = $select->where("tbl.Module = ?", $advancesearch['system']);
			}
			if (!empty($advancesearch['actiontype'])) {
				$select = $select->where("tbl.ActionType = ?", $advancesearch['actiontype']);
			}
			if (!empty($advancesearch['sendfrom'])) {
				$value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfrom']);
				$select = $select->where("tbl.ActionDate >= ?", $value);
			}
			if (!empty($advancesearch['sendto'])) {
				$value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendto']);
				$select = $select->where("tbl.ActionDate <= ?", $value . " 23:59:59");
			}
			// find total
			$records = $this->getActionListCount($select);
			
			// set ordering and total
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->limitPage($page, $rows)
			;
			if (in_array($rowSort, array('FullName','ValueString'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["ActionDate"] = empty($data[$i]["ActionDate"]) ? null : new DateTime($data[$i]["ActionDate"]);
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		
		return $data;
	}
	
	function getActionListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	function getActionAll($sidx, $sord, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			
			$select = $this->db->select()
			->from(array("tbl" => "log_action"), "")
			->joinLeft(array("mi" => "module_info"), "tbl.Module = mi.Code", "")
			->joinLeft(array("ma" => "module_action"), "tbl.ActionType = ma.Code", "")
			->joinLeft(array("u" => "sys_user"), "tbl.CreatedBy = u.ID", "")
			->columns(array(
					"ID" => "tbl.ID",
					"ModuleName" => "mi.Name",
					"FullName" => "concat(u.FirstName, ' ', u.LastName)",
					"ActionType" => "tbl.ActionType",
					"ActionTypeName" => "ma.Name",
					"ActionDate" => "tbl.ActionDate",
					"ValueString" => "tbl.ValueString",
					"RemoteIP" => "tbl.RemoteIP"
			))
			;
			
			// set criteria
			if (!empty($advancesearch['textsearch'])) {
				$select = $select->where("tbl.ValueString like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%") . " or concat(u.FirstName, ' ', u.LastName) like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
			}
			if (!empty($advancesearch['system'])) {
				$select = $select->where("tbl.Module = ?", $advancesearch['system']);
			}
			if (!empty($advancesearch['actiontype'])) {
				$select = $select->where("tbl.ActionType = ?", $advancesearch['actiontype']);
			}
			if (!empty($advancesearch['sendfrom'])) {
				$value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfrom']);
				$select = $select->where("tbl.ActionDate >= ?", $value);
			}
			if (!empty($advancesearch['sendto'])) {
				$value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendto']);
				$select = $select->where("tbl.ActionDate <= ?", $value . " 23:59:59");
			}
			
			$select = $select->order("ActionDate desc");
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["ActionDate"] = empty($data[$i]["ActionDate"]) ? null : new DateTime($data[$i]["ActionDate"]);
				}
			}
		} catch (Exception $e) {
			//echo $e->getMessage();
		}
		
		return $data;
	}
	
	
	
	function getSecutiryDetail($id) {
		$data = new stdClass();
		try {
			
			// set query maininfo
			$select = $this->db->select()
			->from(array("tbl" => "log_securities"), "")
			->joinLeft(array("u" => "sys_user"), "tbl.CreatedBy = u.ID", "")
			->columns(array(
					"ID" => "tbl.ID",
					"tbl.LogType",
					"tbl.Result",
					"tbl.IpAddress",
					"tbl.SiteType",
					"tbl.Username",
					"tbl.Password",
					"tbl.Note",
					"FullName" => "concat(u.FirstName, ' ', u.LastName)",
					"ActionDate" => "tbl.Date"
			))
			->where("tbl.ID = ?", $id)
			->where("tbl.IsEnable = ?", 'Y')
			->where("tbl.IsDeleted = ?", 'N')
			->where("tbl.SiteType = ?", 'B')
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->ActionDate = empty($data->ActionDate) ? null : new DateTime($data->ActionDate);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	
	function getSecutiryList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			
			// set query maininfo
			$select = $this->db->select()
			->from(array("tbl" => "log_securities"), "")
			->joinLeft(array("u" => "sys_user"), "tbl.CreatedBy = u.ID", "")
			->columns(array(
					"ID" => "tbl.ID",
					"tbl.LogType",
					"tbl.Result",
					"RemoteIP" => "tbl.IpAddress",
					"tbl.SiteType",
					"tbl.Username",
					"FullName" => "concat(u.FirstName, ' ', u.LastName)",
					"ActionDate" => "tbl.Date"
			))
			->where("tbl.IsEnable = ?", 'Y')
			->where("tbl.IsDeleted = ?", 'N')
			->where("tbl.SiteType = ?", 'B')
			;
			
			// set criteria
			if (!empty($advancesearch['textsearch'])) {
				$select = $select->where("concat(u.FirstName, ' ', u.LastName) like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%") . " or tbl.Username like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
			}
			if (!empty($advancesearch['logtype'])) {
				$select = $select->where("tbl.LogType = ?", $advancesearch['logtype']);
			}
			if (!empty($advancesearch['result'])) {
				$select = $select->where("tbl.Result = ?", $advancesearch['result']);
			}
			if (!empty($advancesearch['sendfrom'])) {
				$value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfrom']);
				$select = $select->where("tbl.Date >= ?", $value);
			}
			if (!empty($advancesearch['sendto'])) {
				$value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendto']);
				$select = $select->where("tbl.Date <= ?", $value . " 23:59:59");
			}
			// find total
			$records = $this->getSecutiryListCount($select);
			
			// set ordering and total
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->limitPage($page, $rows)
			;
			if (in_array($rowSort, array('FullName','Username'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["ActionDate"] = empty($data[$i]["ActionDate"]) ? null : new DateTime($data[$i]["ActionDate"]);
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		
		return $data;
	}
	
	function getSecutiryListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	
	function getSecutiryAll($sidx, $sord, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {			
			
			$select = $this->db->select()
			->from(array("tbl" => "log_securities"), "")
			->joinLeft(array("u" => "sys_user"), "tbl.CreatedBy = u.ID", "")
			->columns(array(
					"ID" => "tbl.ID",
					"tbl.LogType",
					"tbl.Result",
					"RemoteIP" => "tbl.IpAddress",
					"tbl.SiteType",
					"tbl.Username",
					"FullName" => "concat(u.FirstName, ' ', u.LastName)",
					"ActionDate" => "tbl.Date"
			))
			->where("tbl.IsEnable = ?", 'Y')
			->where("tbl.IsDeleted = ?", 'N')
			->where("tbl.SiteType = ?", 'B')
			;
			
			// set criteria
			if (!empty($advancesearch['textsearch'])) {
				$select = $select->where("concat(u.FirstName, ' ', u.LastName) like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%") . " or tbl.Username like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
			}
			if (!empty($advancesearch['logtype'])) {
				$select = $select->where("tbl.LogType = ?", $advancesearch['logtype']);
			}
			if (!empty($advancesearch['result'])) {
				$select = $select->where("tbl.Result = ?", $advancesearch['result']);
			}
			if (!empty($advancesearch['sendfrom'])) {
				$value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfrom']);
				$select = $select->where("tbl.Date >= ?", $value);
			}
			if (!empty($advancesearch['sendto'])) {
				$value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendto']);
				$select = $select->where("tbl.Date <= ?", $value . " 23:59:59");
			}
			
			$select = $select->order("ActionDate desc");
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["ActionDate"] = empty($data[$i]["ActionDate"]) ? null : new DateTime($data[$i]["ActionDate"]);
				}
			}
		} catch (Exception $e) {
			//echo $e->getMessage();
		}
		
		return $data;
	}
	
	
	public static function action($module, $actionType, $value, $refID) {
		
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
			$actionDate = date('Y-m-d H:i:s');
			
			$form = array();
			$form["Module"] = $module;
			$form["ActionType"] = $actionType;
			$form["ActionDate"] = $actionDate;
			$form["RemoteIP"] = $request->getServer('REMOTE_ADDR');
			if (!empty($form["RemoteIP"])) {
				$countryCode = geoip_country_code_by_name($form["RemoteIP"]);
				$form["GeoIP"] = $countryCode;
			}
			$form["ValueString"] = $value;
			$form["RefID"] = $refID;
			$form["Created"] = $actionDate;
			$form["CreatedBy"] = $userId;			
			$result = $db->insert('log_action', $form);
			$lastid = $db->lastInsertId();
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public static function access($isAjax = false) {
		
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
// 			$form["rebot"] = $baseName;
			$form["Referrer"] = $request->getServer('HTTP_REFERER');
			$form["Agent"] = $request->getServer('HTTP_USER_AGENT');
			$form["AcceptLang"] = $request->getServer('HTTP_ACCEPT_LANGUAGE');
// 			$form["accept_charset"] = $baseName;
			$form["SiteType"] = 'B';
			$form["IsAjax"] = $isAjax ? 'Y' : 'N';
			$form["ActionDate"] = date('Y-m-d');
			$form["Created"] = date('Y-m-d H:i:s');
			$form["CreatedBy"] = $userId;
			
			$result = $db->insert('log_access', $form);
			$lastid = $db->lastInsertId();
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
			$form["SiteType"] = 'B';
			$form["Created"] = date('Y-m-d H:i:s');
			$form["CreatedBy"] = empty($userId) ? null : $userId;
			$db->insert('log_securities', $form);
			$lastid = $db->lastInsertId();
			
		} catch (Exception $e) {
			
		}
	}

}

?>
