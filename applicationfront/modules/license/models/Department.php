<?php
class License_Model_Department {

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

	public function getOption($lang) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'license_department_info' ), array ('ID') )
		->joinInner ( array ('d' => 'license_department_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Name') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Name USING TIS620)  ASC" );
		
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Name"];
		}
		return $result;
	}

	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'license_department_info'), array (
					"ID",
					"Ordering" ,
					"IsDeleted",
					"IsEnable" 
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}
}
?>