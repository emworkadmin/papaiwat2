<?php

class Wat_Model_District {
	
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
	
	public function getOption($lang, $provinceCode = null) {
		$select = $this->db->select()
		->from(array('tbl' => 'wat_district_info'), array( 'ID' => 'Code'))
		->joinInner(array('d' => 'wat_district_detail'), "tbl.Code = d.Code and d.Lang = '{$lang}' ", array('Name'))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->order("convert(d.Name USING tis620) ASC")
		;
		if (!empty($provinceCode)) {
			$select = $select->where("tbl.WatProvinceCode = ?", $provinceCode);
		}
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}
}

?>
