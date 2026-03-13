<?php

class Banner_Model_Stat {
	
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
	function getlist($lang = 'th'){
		$select = $this->db->select()
		->from(array('tbl' => 'banner_item_click'), "")
		->joinInner(array("td" => "banner_item_info"), "tbl.BannerItemID = td.ID", "")
		->joinInner(array('d' => 'banner_item_detail') , "td.ID = d.ID and d.Lang = '{$lang}' " , '')
		->columns(array(
			"Title" => "d.Title",
			"CountClick" => "count(tbl.BannerItemID)",
		))
		->where("td.IsDeleted = 'N'")
		->group("tbl.BannerItemID")
		->order("count(tbl.BannerItemID) DESC")
		;

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		return $data;
	}
	function getsum($startdate,$enddate){
		$select = $this->db->select()
		->from(array('tbl' => 'banner_item_click'), "")
		->joinInner(array("td" => "banner_item_info"), "tbl.BannerItemID = td.ID", "")
		->columns(array(
			"CountClick" => "count(tbl.BannerItemID)",
		))
		->where("td.IsDeleted = 'N'")
		;
		if($startdate) {
            $select = $select->where("tbl.ActionDate >= ? ", $startdate);
        }
        if($enddate) {
            $select = $select->where("tbl.ActionDate <= ? ", $enddate . " 23:59");
		}
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data;
	}
	function getbetweendate($startdate,$enddate,$lang = 'th'){
		$select = $this->db->select()
		->from(array('tbl' => 'banner_item_click'), "")
		->joinInner(array("td" => "banner_item_info"), "tbl.BannerItemID = td.ID", "")
		->joinInner(array('d' => 'banner_item_detail') , "td.ID = d.ID and d.Lang = '{$lang}' " , '')
		->columns(array(
			"Title" => "d.Title",
			"CountClick" => "count(tbl.BannerItemID)",
		))
		->where("td.IsDeleted = 'N'")
		->group("tbl.BannerItemID")
		->order("count(tbl.BannerItemID) DESC")
		;
		if($startdate) {
            $select = $select->where("tbl.ActionDate >= ?",$startdate);
        }
        if($enddate) {
            $select = $select->where("tbl.ActionDate <= ? ", $enddate . " 23:59");
		}
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		return $data;
	}
}

?>
