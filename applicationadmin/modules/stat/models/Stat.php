<?php

class Stat_Model_Stat {
	
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

	function getvisiter($startdate, $enddate)
	{

		// $data = new stdClass();
		// $data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
		// $data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
		// $startdate = $data->VisitorSiteOpenDate;
		// $BeginAmount = $data->VisitorBeginVisitor;

		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"Amount" => " COUNT(tbl.ID)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		//->order("tbl.created ASC")
		;

		// if($startdate) {
		// 	$select = $select
		// 	->where("DATE_FORMAT(tbl.created,'%Y%m%d') >= ?", $startdate);
		// 	(date_field BETWEEN '2010-01-30 14:15:55' AND '2010-09-29 10:15:55')
		// }

		if(!empty($startdate) && !empty($enddate)){
			$select = $select
			->where("DATE_FORMAT(tbl.created,'%Y-%m-%d') between '{$startdate}' and '{$enddate}' ");
		}
		
		$stmt = $this->db->query($select);	
		$data = $stmt->fetchObject();
		
		$amount = $data->Amount + $BeginAmount;

		return $amount;
	}
	
	function getlist(){
		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"Page" => "SUBSTRING_INDEX(tbl.UriString,'?',1)",
			"CountPage" => "count(tbl.UriString)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		// ->group("tbl.UriString")
		// ->order("count(tbl.UriString) DESC")
		->group("Page")
		->order("CountPage DESC")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		return $data;
	}
	function getsum($startdate,$enddate){
		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			// "Page" => "tbl.UriString",
			"SumPage" => "count(tbl.UriString)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		;
		if($startdate) {
            $select = $select->where("tbl.created >= ? ", $startdate);
        }
        if($enddate) {
            $select = $select->where("tbl.created <= ? ", $enddate . " 23:59");
		}
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data;
	}
	function getbetweendate($startdate,$enddate){
		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"Page" => "SUBSTRING_INDEX(tbl.UriString,'?',1)",
			"CountPage" => "count(tbl.UriString)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		// ->group("tbl.UriString")
		// ->order("count(tbl.UriString) DESC")
		->group("Page")
		->order("CountPage DESC")
		;
		
		if($startdate) {
            $select = $select->where("tbl.created >= ?",$startdate);
        }
        if($enddate) {
            $select = $select->where("tbl.created <= ? ", $enddate . " 23:59");
        }
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		return $data;
	}
}

?>
