<?php

class Visitor_Model_Visitor {
	
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
	function getLogAccess($startdate,$enddate){
		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"Page" => "SUBSTRING_INDEX(tbl.UriString,'?',1)",
			"CountPage" => "count(tbl.UriString)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		->group("SUBSTRING_INDEX(tbl.UriString,'?',1)")
		->order("count(tbl.UriString) DESC")
		;
		
		if($startdate) {
            $select = $select->where("DATE(tbl.created) >= ?",$startdate." 00:00");
        }
        if($enddate) {
            $select = $select->where("DATE(tbl.created) <= ? ", $enddate . " 23:59");
		}

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		return $data;
	}

	// function getSumByHR($select) {
	// 	$select = $this->db->select()
	// 	->from(array('T' => $select), array("Amount" => "sum(T.CountPage)"))
	// 	;
	// 	$stmt = $this->db->query($select);
	// 	$data = $stmt->fetchObject();

	// 	return $data->RECORDS;
	// }
	
	function getLogAccessDate($startdate,$enddate){
		
		$selectFirst = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"CountPage" => "count(tbl.UriString)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		->where("tbl.created >= ?",$startdate)
		->where("tbl.created <= ? ", $enddate)
		->group("tbl.UriString")
		->order("tbl.created ASC")
		;

		$select = $this->db->select()
		->from(array('T' => $selectFirst), array(
			"Amount" => "SUM(T.CountPage)"
		))
		;

		$stmt = $this->db->query($select);	
		$data = $stmt->fetchObject();

		return $data;
	}
	function getLogAccessMonth($startdate,$enddate){
		
		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"CountPage" => " COUNT(tbl.created)",
			"Date" => " DATE(`tbl`.`created`)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		->where("DATE(tbl.created) >= ?",$startdate)
		->where("DATE(tbl.created) <= ? ", $enddate)
		->group("DATE(tbl.created)")
		->order("tbl.created ASC")
		;
		

		$stmt = $this->db->query($select);	
		$data = $stmt->fetchAll();

		if (count($data) > 0) {
			for ($i = 0; $i < count($data); $i ++) {
				$data[$i]["Date"] = Emwork_Utility_DateTime::MySQLToDateThai($data[$i]["Date"]);
			}
		}

		return $data;
	}
	function getLogAccessviewAll($startdate,$enddate){
		
		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"CountPage" => " COUNT(tbl.created)",
			"Date" => "  DATE_FORMAT(tbl.created,'%Y%m')",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		->where("DATE_FORMAT(tbl.created,'%Y%m') >= ?",$startdate)
		->group("DATE_FORMAT(tbl.created,'%Y%m')")
		->order("tbl.created ASC")
		;
		
	
		$stmt = $this->db->query($select);	
		$data = $stmt->fetchAll();

		if (count($data) > 0) {
			for ($i = 0; $i < count($data); $i ++) {
				$data[$i]["Date"] = Emwork_Utility_DateTime::MySQLToDateThai(date("Y-m",strtotime($data[$i]["Date"]."01")),"DT_Abbr");
			}
		}
		return $data;
	}
	function getLogAccessAll($startdate,$enddate){
		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"Page" => "SUBSTRING_INDEX(tbl.UriString,'?',1)",
			"CountPage" => "count(tbl.UriString)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		->group("tbl.UriString")
		->order("count(tbl.UriString) DESC")
		;
		
		if($startdate) {
            $select = $select->where("DATE_FORMAT(tbl.created,'%Y%m') >= ?",$startdate);
        }
        if($enddate) {
            $select = $select->where("DATE_FORMAT(tbl.created,'%Y%m') <= ? ", $enddate);
		}

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();

		return $data;
	}
}

?>
