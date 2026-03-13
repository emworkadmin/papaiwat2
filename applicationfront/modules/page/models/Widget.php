<?php

class Page_Model_Widget {
	
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
			
		}
	}
	
	public static function getDetailByLaguage($id, $lang) {
		$defaultLang = System_Model_Setting::getDefaultLanguage();
		$lang = empty($lang) ? $defaultLang['Code'] : $lang;
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$data = new stdClass();
		try {
			
			// Get data menu
			$select = $db->select()->distinct()
			->from(array('tbl' => 'sys_widget'), array('ID', 'Lang', 'Title', 'Ordering', 'Position', 'Widget', 'ShowTitle', 'Params', 'PublishDate', 'PublishDown', 'IsEnable', 'IsDeleted'))
			//->where("tbl.Lang = '*' or tbl.Lang = ? ", $lang)
			//->order("tbl.Ordering asc")
			->where("tbl.ID = ? ", $id)
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		
		return $data;
	}
}

?>
