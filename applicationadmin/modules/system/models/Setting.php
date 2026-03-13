<?php
/**
 * EM D2MS
 * 
 * @copyright	Copyright (c) 2018 EMWORK Group 
 * @license		EMWORK Group
 * @version		1.0.0 
 * @since			2018-03-03
 * @author 		EMWORK Group
 *
 */

class System_Model_Setting {

	/**
	 * 
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;
	
	protected static $list;
	protected static $languages;
	protected static $defaultLanguage;

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
	
	/** setting ***/
	
	public static function getValue($name, $group) {
		$value = null;
		
		if (empty(self::$list) && !is_array(self::$list)) {
			self::$list = array();
			
			$db = Zend_Db_Table::getDefaultAdapter();
			$select = $db->select()
			->from(array('tbl' => 'sys_setting'), array('*'))
			;
			$stmt = $db->query($select);
			$list = $stmt->fetchAll();
			
			if (count($list) > 0) {
				foreach ($list as $item) {
					if (array_key_exists($item['GroupName'], self::$list)) {
						self::$list[$item['GroupName']][$item['Name']] = $item['Value'];
					} else {
						self::$list[$item['GroupName']] = array();
						self::$list[$item['GroupName']][$item['Name']] = $item['Value'];
					}
				}
			}
			
			// TODO: Force SiteUrl
			$ssl      = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
			$sp       = strtolower( $_SERVER['SERVER_PROTOCOL'] );
			$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
			$port     = $_SERVER['SERVER_PORT'];
			$port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
			$use_forwarded_host = isset($use_forwarded_host) ? $use_forwarded_host : false;
			$host     = ( $use_forwarded_host && isset( $_SERVER['HTTP_X_FORWARDED_HOST'] ) ) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : null );
			$host     = isset( $host ) ? $host : $_SERVER['SERVER_NAME'] . $port;
			$siteUrl = $protocol . '://' . $host;
			
			if (!array_key_exists("Site", self::$list)) {
				self::$list['Site'] = array();
			}
			if (!array_key_exists("General", self::$list)) {
				self::$list['General'] = array();
			}
			self::$list['Site']['SiteUrl'] = $siteUrl;
			self::$list['General']['SiteUrl'] = $siteUrl;
		}
		
		if (array_key_exists($group, self::$list)) {
			if (array_key_exists($name, self::$list[$group])) {
				$value = self::$list[$group][$name];
			}
		}
		
		
		return $value;
	}
	
	public function getInfo() {
		$db = Zend_Db_Table::getDefaultAdapter();

		$data = null;
		try {
			$select = $db->select()
			->from(array('tbl' => 'sys_setting'), array('*'))
			;
		
			$stmt = $db->query($select);
			$obj = $stmt->fetchObject();
			
			$data = new stdClass();
			$data->GroupName = $obj->GroupName;
			$data->Name = $obj->Name;
			$data->Modified = empty($obj->Modified) ? null : new DateTime($obj->Modified);
			$data->ModifiedBy = $obj->ModifiedBy;
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	/**
	 *
	 * @param array $params
	 * @return stdClass
	 */
	function saveParams($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			if (count(array_keys($params)) > 0) {
				foreach (array_keys($params) as $groupName) {
					// Delete all item in GroupName (key)
					$where = array();
					$where['GroupName = ?'] = $groupName;
					$this->db->delete("sys_setting", $where);
					
					if (count($params[$groupName]) > 0) {
						foreach ($params[$groupName] as $name => $value) {
							// Insert item
							$form = array();
							$form['GroupName'] = $groupName;
							$form['Name'] = $name;
							$form['Value'] = $value;
							$form['Modified'] = date("Y-m-d H:i:s");
							$form['ModifiedBy'] = $this->UserID;
							$this->db->insert("sys_setting", $form);
						}
					}
				}
			}
			
			$data->result = true;
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	
	/** Language **/
	
	
	public static function getLanguages() {
		$data = null;
		
		if (empty(self::$languages) && !is_array(self::$languages)) {
			try {
				$db = Zend_Db_Table::getDefaultAdapter();
				$select = $db->select()
				->from(array('tbl' => 'sys_setting_language'), array('*'))
				->order("(case when tbl.IsDefault = 'Y' then 1 else 2 end)")
				->order("(case when tbl.IsRequired = 'Y' then 1 else 2 end)")
				->order("tbl.Ordering")
				;
				
				$stmt = $db->query($select);
				$data = $stmt->fetchAll();
				
				if (count($data) > 0) {
					for ($i = 0; $i < count($data); $i ++) {
						$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
						$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
					}
				}
				
				self::$languages = $data;
			} catch (Exception $e) {
				
			}
		} else {
			$data = self::$languages;
		}
		return $data;
	}
	
	
	
	public static function getDefaultLanguage() {
		$data = null;
		
		if (empty(self::$defaultLanguage) && !is_array(self::$defaultLanguage)) {
			try {
				$db = Zend_Db_Table::getDefaultAdapter();
				$select = $db->select()
				->from(array('tbl' => 'sys_setting_language'), array('*'))
				->order("(case when tbl.IsDefault = 'Y' then 1 else 2 end)")
				->order("(case when tbl.IsRequired = 'Y' then 1 else 2 end)")
				->order("tbl.Ordering")
				;
				
				$stmt = $db->query($select);
				$datas = $stmt->fetchAll();
				
				if (count($datas) > 0) {
					for ($i = 0; $i < count($datas); $i ++) {
						$datas[$i]["Created"] = empty($datas[$i]["Created"]) ? null : new DateTime($datas[$i]["Created"]);
						$datas[$i]["Modified"] = empty($datas[$i]["Modified"]) ? null : new DateTime($datas[$i]["Modified"]);
					}
				}
				
				if (count($datas) > 0) {
					$data = $datas[0];
					self::$defaultLanguage = $data;
				}
			} catch (Exception $e) {
				
			}
		} else {
			$data = self::$defaultLanguage;
		}
		return $data;
	}
	
	public function getLanguageInfo($id) {
		$data = null;
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_setting_language'), array('*'))
			->where("tbl.ID = ? ", $id)
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
			$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	public function getLanguageList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_setting_language'), array('*'))
			->where("tbl.IsDeleted = 'N' ")
			;
			
			// set criteria
			if (!empty($advancesearch['searchText'])) {
				$select = $select->where("tbl.Name like " . $this->db->quote("%" . $advancesearch['searchText'] . "%") ."OR tbl.NameLocal like " . $this->db->quote("%" . $advancesearch['searchText'] . "%"));
			}
			if (!empty($advancesearch['enable'])) {
				$select = $select->where("tbl.IsEnable = ?", $advancesearch['enable']);
			}
			
			// find total
			$records = $this->getLanguageListCount($select);
			
			// set ordering and total
			if (empty($sidx)) {
				$sidx = "Ordering";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->order("$rowSort $sord")
			->limitPage($page, $rows)
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
				
		} catch (Exception $e) {
			
		}
		return $data;
	}
	public function getLanguageListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	/**
	 *
	 * @param array $params
	 * @return stdClass
	 */
	function saveLanguageParams($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$form = array();
			$where = array();
			if (!empty($params['IsEnable'])) {
				$form['IsEnable'] = $params['IsEnable'];
			}
			if (!empty($params['IsRequired'])) {
				$form['IsRequired'] = $params['IsRequired'];
			}
			if (!empty($params['IsDefault'])) {
				$form['IsDefault'] = $params['IsDefault'];
			}
			$form['Modified'] = date("Y-m-d H:i:s");
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ?'] = $params['ID'];
			$result = $this->db->update("sys_setting_language", $form, $where);
			
			if ($result) {
				if (!empty($params['IsDefault']) && ($params['IsDefault'] == 'Y')) {
					// Set 'N' to another of this TYPE
					$form = array();
					$where = array();
					$form['IsDefault'] = 'N';
					$where['ID <> ?'] = $params['ID'];
					$this->db->update("sys_setting_language", $form, $where);
				}
			}
			
			$data->result = true;
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	
	
	
	
	/*** Unuse ***/
	
	/**
	 * 
	 * @param array $params
	 * @return stdClass
	 */
	function save($params) {
		$data = new stdClass();
		$data->result = false;
// 		echo '<pre>';print_r($params);'</pre>';exit;
		try {

			// Update
			$form = array();
			$where = array();
			$form['smtp_host'] = $params['txtSmtpHost'];
			$form['smtp_port'] = empty($params['txtSmtpPort']) ? 25 : $params['txtSmtpPort'];
			$form['smtp_from_name'] = $params['txtSmtpFromName'];
			$form['smtp_from_email'] = $params['txtSmtpFromEmail'];
			$form['smtp_use_ssl'] = $params['rdlSmtpUseSsl'];
			$form['smtp_ssl_type'] = $form['smtp_use_ssl'] == 'Y' ? $params['lstSmtpSslType'] : null;
			$form['smtp_use_auth'] = $params['rdlSmtpUseAuth'];
			$form['smtp_auth_type'] = $form['smtp_use_auth'] == 'Y' ? $params['lstSmtpAuthType'] : null;
			$form['smtp_auth_username'] = $form['smtp_use_auth'] == 'Y' ? $params['txtSmtpAuthUsername'] : null;
			if ($form['smtp_use_auth'] == 'Y' && !empty($params['txtSmtpAuthPassword'])) {
				$form['smtp_auth_password'] = $params['txtSmtpAuthPassword'];
			} elseif ($form['smtp_use_auth'] != 'Y') {
				$form['smtp_auth_password'] = null;
			}
			$form['modified_by'] = $this->UserID;
			$form['modified'] = date('Y-m-d H:i:s');
			$form['is_facebook'] = empty($params['isFacebook']) ? 'N' : $params['isFacebook'];
			$form['facebook_url'] = empty($params['txtfacebookurl']) ? '' : $params['txtfacebookurl'];
			$form['is_twitter'] = empty($params['isTwitter']) ? 'N' : $params['isTwitter'];
			$form['twitter_url'] = empty($params['txttwitterurl']) ? '' : $params['txttwitterurl'];
			$form['is_youtube'] = empty($params['isYoutube']) ? 'N' : $params['isYoutube'];
			$form['youtube_url'] = empty($params['txtyoutubeurl']) ? '' : $params['txtyoutubeurl'];
			$form['is_instagram'] = empty($params['isInstagram']) ? 'N' : $params['isInstagram'];
			$form['instagram_url'] = empty($params['txtinstagramurl']) ? '' : $params['txtinstagramurl'];
			$form['product_new'] = empty($params['txtProductNew']) ? 0 : $params['txtProductNew'];
			$form['product_update'] = empty($params['txtProductUpdate']) ? 0 : $params['txtProductUpdate'];
			$result = $this->db->update("sys_settings", $form);
			if ($result == 0) {
				$form['created_by'] = $this->UserID;
				$form['created'] = date('Y-m-d H:i:s');
				$result = $this->db->insert("sys_settings", $form);
			}
			
			// Update Language
			$formLang = array();
			$formLang['IsEnable'] = 'N';
			$this->db->update("sys_setting_language", $formLang);
			if (count($params['chkLanguage']) > 0) {
				foreach ($params['chkLanguage'] as $langID) {
					$formLang = array();
					$whereLang = array();
					$formLang['IsEnable'] = 'Y';
					$whereLang['ID = ?'] = $langID;
					$this->db->update("sys_setting_language", $formLang, $whereLang);
				}
			}
	
			$data->result = true;
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
}

?>
