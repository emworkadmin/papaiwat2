<?php

class System_Model_Menu {

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
	
	public static function getHome() {
		$data = null;
			
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = $db->select()
		->from(array('tbl' => 'menu_info'), array('*'))
		->where("IsHome = 'Y' ")
		->where("IsEnable = 'Y' ")
		->where("IsDeleted = 'N' ")
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchObject();
		
		if ($data) {
			$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
			$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
		}		
		
		return $data;
	}
	
	public static function findMenuByUrl($url) {
		$data = array();
		
		$db = Zend_Db_Table::getDefaultAdapter();
		$select = $db->select()
		->from(array('tbl' => 'menu_info'), array('*'))
		->where("IsEnable = 'Y' ")
		->where("IsDeleted = 'N' ")
		->where("Url = ? ", $url)
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchAll();
		
		if (count(data) > 0) {
			$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
			$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
		}
		
		return $data;
	}
	
	
	function getInfo($id) {
		$data = new stdClass ();
		try {			
			$select = $this->db->select ()->from ( array ('m' => 'menu_info'), array (
					"ID",
					"MenuGroupID",
					"ParentID",
					"Url",
					"IsHome",
					"Ordering",
					"IsDeleted",
					"IsEnable",
					"Type",
					"FrontModule",
					"FrontController",
					"Params"
			))
			->where ("m.IsDeleted = 'N' " )
			->where ( "m.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
		}
		return $data;
	}
	
	
	
	public function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'menu_info'), "")
			->joinLeft(array("md" => "menu_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "m.ID",
					"Lang" => 'md.Lang',
					"Name" => "md.Name",
					"m.IsHome",
					"MenuGroupID" => "m.MenuGroupID",
					"ParentID" => "m.ParentID",
					"Url" => "m.Url",
					"Ordering" => "m.Ordering",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable"
			))
			->where ("m.IsDeleted = 'N' " )
			->where("m.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
}

?>
