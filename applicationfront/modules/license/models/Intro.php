<?php
class License_Model_Intro {

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
	
	function getInfo() {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbl' => 'license_intro_info'), "")
			->columns(array(
					"tbl.ID",
					"IsDeleted",
					"IsEnable" ,
					"Created",
					"Modified"
			))
			->where ( "tbl.IsDeleted = 'N' " )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();

			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}

		} catch ( Exception $e ) {
			
		}
		return $data;
	}
	
	function getDataByLanguage($lang)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'license_intro_info'), "")
			->joinLeft(array("md" => "license_intro_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "m.ID",
					"Lang" => "md.Lang",
					"m.Created",
					"m.Modified",
					"Detail" => 'md.Detail',
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable"
			))
			->where ( "m.IsDeleted = 'N' " )
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}

		} catch (Exception $e) {
			
		}
		return $data;
	}
}
?>
