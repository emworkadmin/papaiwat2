<?php
class Wat_Model_Province {
	
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
			$this->db = Zend_Db_Table::getDefaultAdapter ();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			
		}
	}
	public function getOption($lang, $regionID) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'wat_province_info' ), array ('Code') )
		->joinInner ( array ('d' => 'wat_province_detail'), "tbs.Code = d.Code and d.Lang = '{$lang}' ", array ('Name') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->order ( "convert(d.Name USING TIS620)  ASC" );
		if (!empty($regionID)) {
			$select = $select->where("tbs.WatRegionID = ? ", $regionID);
		}
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["Code"]] = $value ["Name"];
		}
		return $result;
	}
	
	function getInfo($code) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'wat_province_info'), array (
					"Code",
					"IsDeleted",
					"IsEnable"
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.Code = ? ", $code )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}
	
	public function getDataByLanguage($code, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'wat_province_info'), "")
			->joinLeft(array("md" => "wat_province_detail"), "m.Code = md.Code and md.Lang = '{$lang}' ", "")
			->columns(array(
					"Code" => "m.Code",
					"Lang" => 'md.Lang',
					"Name" => "md.Name",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable"
			))
			->where("m.Code = ?", $code)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
}

?>
