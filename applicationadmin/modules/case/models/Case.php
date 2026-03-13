<?php

class Case_Model_Case {
	
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
			$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'case' . DS . 'configs' . DS . 'config.ini', 'database');
			$this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			$this->db = null;
		}
    }
	public function getCourtCode() {
		$select = $this->db->select()->distinct()
		->from(array('tbl' => 'coj_case'), "")
		->columns(array(
				"CourtCode" => "tbl.CourtCode",
		))
		;

		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array ();
	
		foreach ( $data as $value ) {
			$result [$value ["CourtCode"]] = $value ["CourtCode"];
		}
		return $result;
	}
}

?>
