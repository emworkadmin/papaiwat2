<?php

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
	protected static $languageActives;
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
	
	/** Language **/
	public static function getLanguages() {
		$data = null;
		
		if (empty(self::$languages) && !is_array(self::$languages)) {
			try {
				$db = Zend_Db_Table::getDefaultAdapter();
				$select = $db->select()
				->from(array('tbl' => 'sys_setting_language'), '')
				->columns(array(
						"ID" => "tbl.ID",
						"Code" => "tbl.Code",
						"AbbrDisplay" => "tbl.AbbrDisplay",
						"Name" => "tbl.Name",
						"NameLocal" => "tbl.NameLocal",
						"IsDefault" => "tbl.IsDefault",
						"IsRequired" => "tbl.IsRequired",
						"Ordering" => "tbl.Ordering",
						"Created" => "tbl.Created",
						"CreatedBy" => "tbl.CreatedBy",
						"Modified" => "tbl.Modified",
						"ModifiedBy" => "tbl.ModifiedBy",
						"IsEnable" => "tbl.IsEnable",
						"IsDeleted" => "tbl.IsDeleted",
				))
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
	
	public static function getLanguageActives() {
		$data = null;
		
		if (empty(self::$languageActives) && !is_array(self::$languageActives)) {
			try {
				$db = Zend_Db_Table::getDefaultAdapter();
				$select = $db->select()
				->from(array('tbl' => 'sys_setting_language'), '')
				->columns(array(
						"ID" => "tbl.ID",
						"Code" => "tbl.Code",
						"AbbrDisplay" => "tbl.AbbrDisplay",
						"Name" => "tbl.Name",
						"NameLocal" => "tbl.NameLocal",
						"IsDefault" => "tbl.IsDefault",
						"IsRequired" => "tbl.IsRequired",
						"Ordering" => "tbl.Ordering",
						"Created" => "tbl.Created",
						"CreatedBy" => "tbl.CreatedBy",
						"Modified" => "tbl.Modified",
						"ModifiedBy" => "tbl.ModifiedBy",
						"IsEnable" => "tbl.IsEnable",
						"IsDeleted" => "tbl.IsDeleted",
				))
				->where("tbl.IsEnable = ?", 'Y')
				->where("tbl.IsDeleted = ?", 'N')
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
				
				self::$languageActives = $data;
			} catch (Exception $e) {
				
			}
		} else {
			$data = self::$languageActives;
		}
		return $data;
	}
	
	
	public static function getDefaultLanguage() {
		$data = null;
		
		if (empty(self::$defaultLanguage) && !is_array(self::$defaultLanguage)) {
			try {
				$db = Zend_Db_Table::getDefaultAdapter();
				$select = $db->select()
				->from(array('tbl' => 'sys_setting_language'), '')
				->columns(array(
						"ID" => "tbl.ID",
						"Code" => "tbl.Code",
						"AbbrDisplay" => "tbl.AbbrDisplay",
						"Name" => "tbl.Name",
						"NameLocal" => "tbl.NameLocal",
						"IsDefault" => "tbl.IsDefault",
						"IsRequired" => "tbl.IsRequired",
						"Ordering" => "tbl.Ordering",
						"Created" => "tbl.Created",
						"CreatedBy" => "tbl.CreatedBy",
						"Modified" => "tbl.Modified",
						"ModifiedBy" => "tbl.ModifiedBy",
						"IsEnable" => "tbl.IsEnable",
						"IsDeleted" => "tbl.IsDeleted",
				))
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
}

?>
