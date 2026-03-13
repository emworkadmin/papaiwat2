<?php

class System_Model_Help {

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

	function getFullList($lang = 'th') {
		
		$data = array();
		try {
			// set query
			$select = $this->db->select()
			->from(array('tbl' => 'help'), array("id", "help_category_id", "alias", "view_amount", "created"))
			->joinInner(array("d" => 'help_detail'), "tbl.id = d.id", array("title"))
			->joinInner(array("hc" => "lkup_help_category"), "tbl.help_category_id = hc.id", "")
			->joinInner(array("hcd" => "lkup_help_category_detail"), "hc.id = hcd.id", array("help_category_name"=>"name"))
			->where("tbl.is_deleted = 'N' ")
			->where("hc.is_deleted = 'N' ")
			->where("d.lang = ? ", $lang)
			->where("hcd.lang = ? ", $lang)
			->order("hc.ordering")
			->order("tbl.ordering")
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		
		} catch (Exception $e) {
			
		}
		
		return $data;
	}
	
	function getInfo($helpID, $lang = 'th') {
		$data = new stdClass();
		try {
	
			$select = $this->db->select()
			->from(array('tbl' => 'help'), array("id", "help_category_id", "alias", "view_amount", "is_default", "ordering", "created", "created_by", "modified", "modified_by"))
			->joinInner(array("d" => 'help_detail'), "tbl.id = d.id", array("title", "detail"))
			->joinLeft(array("hc" => "lkup_help_category"), "tbl.help_category_id = hc.id", "")
			->joinLeft(array("hcd" => "lkup_help_category_detail"), "hc.id = hcd.id", array("help_category_name"=>"name"))
			->where("tbl.is_deleted = 'N' ")
			->where("tbl.id = ? ", $helpID)
			->where("d.lang = ? ", $lang)
			->where("hcd.lang = ? ", $lang)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
	
		}
		return $data;
	}
	
	function getInfoByAlias($alias, $lang = 'th') {
		$data = new stdClass();
		try {
	
			$select = $this->db->select()
			->from(array('tbl' => 'help'), array("id", "help_category_id", "alias", "view_amount", "is_default", "ordering", "created", "created_by", "modified", "modified_by"))
			->joinInner(array("d" => 'help_detail'), "tbl.id = d.id", array("title", "detail"))
			->joinLeft(array("hc" => "lkup_help_category"), "tbl.help_category_id = hc.id", "")
			->joinLeft(array("hcd" => "lkup_help_category_detail"), "hc.id = hcd.id", array("help_category_name"=>"name"))
			->where("tbl.is_deleted = 'N' ")
			->where("tbl.alias = ? ", $alias)
			->where("d.lang = ? ", $lang)
			->where("hcd.lang = ? ", $lang)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getInfoByDefaultOrFirst($lang = 'th') {
		$data = new stdClass();
		try {
	
			$select = $this->db->select()
			->from(array('tbl' => 'help'), array("id", "help_category_id", "alias", "view_amount", "is_default", "ordering", "created", "created_by", "modified", "modified_by"))
			->joinInner(array("d" => 'help_detail'), "tbl.id = d.id", array("title", "detail"))
			->joinLeft(array("hc" => "lkup_help_category"), "tbl.help_category_id = hc.id", "")
			->joinLeft(array("hcd" => "lkup_help_category_detail"), "hc.id = hcd.id", array("help_category_name"=>"name"))
			->where("tbl.is_deleted = 'N' ")
			->where("d.lang = ? ", $lang)
			->where("hcd.lang = ? ", $lang)
			->order("(case when tbl.is_default = 'Y' then 0 else 1 end)")
			->order("tbl.ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
}

?>
