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
		
	function update($params)
	{
		$data = new stdClass();
		$data->result = false;

		try {

			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = $params['hdfID'];
			$modified = date("Y-m-d H:i:s");
			
			$obj = $this->getInfo();
			$id = $obj->ID;

			$form = array();
			$where = array();

			$form['Modified']  = $modified;
			$form['ModifiedBy'] =  empty($this->UserID) ? null : $this->UserID;
			$where['ID = ?'] = $id;
			$result = $this->db->update("license_intro_info", $form, $where);
			if ($result == 0) {
				$form['Created']  = $modified;
				$form['CreatedBy'] =  empty($this->UserID) ? null : $this->UserID;
				$result = $this->db->insert("license_intro_info", $form);
				$id = $this->db->lastInsertId();
			}
			

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Detail'] = $value['Detail'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$whereDetail['ID = ?'] = $id;
					$whereDetail['Lang = ?'] = $lang;
					$affectedRows = $this->db->update("license_intro_detail", $formDetail, $whereDetail);
					if ($affectedRows == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$this->db->insert("license_intro_detail", $formDetail);
					}
				}
			}
			

			if($result){
				$data->id = $id;
				$data->result = true;
			}
		} catch (Exception $e) {
			$data->message = $e->getMessage();
			$data->result = false;
		}
		return $data;
	}
}
?>
