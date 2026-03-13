<?php

class Visitor_Model_Country {
	
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
	function getLogAccessCountry($startdate,$enddate,$limit){
		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"Country" => "tbl.GeoIP",
			"CountPage" => "COUNT(tbl.ID)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		->group("tbl.GeoIP")
		->order("CountPage DESC")
		;

		if($limit) {
            $select = $select->limit("10");
        }
		if($startdate) {
            $select = $select->where("DATE(tbl.created) >= ?",$startdate." 00:00");
        }
        if($enddate) {
            $select = $select->where("DATE(tbl.created) <= ? ", $enddate . " 23:59");
		}
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		
		if (count($data) > 0) {
			for ($i = 0; $i < count($data); $i ++) {
				$data[$i]["Country"] = empty($data[$i]["Country"])? 'Not Set':$data[$i]["Country"];
			}
			
		}
		return $data;
	}
}

?>
