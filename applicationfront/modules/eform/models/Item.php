<?php
class Eform_Model_Item {
	
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
	
	function getDataByLanguage($id, $lang = 'th')
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array("tbl"=>"eform_form_info"), "")
			->joinLeft(array("d"=>"eform_form_detail"),"tbl.ID = d.ID and d.Lang = '{$lang}'", "")
			->columns(array(
				"tbl.ID",
				"tbl.PublishDate",
				"tbl.PublishDown",
				"tbl.CustomCss",
				"d.Title",
				"d.Detail",
				"d.ResponseMessage",
				"d.ResponseURL",
				"d.ResponseMessageType",
			))
			->where("tbl.ID = ?", $id)
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {

		}
		return $data;
	}

	function type($id)
	{
		$data = array();
		try {

			$select = $this->db->select()
			->from(array("tbl" => "eform_form_control"), "")
			->joinLeft(array("ct" => "eform_control_type"), "tbl.EformControlTypeID = ct.ID" ,"")
			->columns(array(
				"tbl.ID",
				"ct.Name",
				"tbl.EformFormID",
				"tbl.EformControlTypeID",
				"tbl.Ordering"
			))
			->where("tbl.EformFormID = ?", $id)
			->where("tbl.IsDeleted = 'N' ")
			->order("tbl.Ordering asc")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	function typeProperty($id)
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "eform_form_control_property"), "")
			->columns(array(
				"tbl.Name",
				"tbl.Value"
			))
			->where("tbl.EformControlID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$result = $stmt->fetchAll();

			foreach($result as $key => $val) {
				$data[$val['Name']] = $val['Value'];
			}

		} catch (Exception $e) {

		}
		return $data;
	}	

	function save($params) {

		$data = new stdClass();
		$data->result = false;
		try {
			
			$request = Zend_Controller_Front::getInstance()->getRequest();
			$EformFormID = $params['hdfID'];

			if(count($params) > 0) {
				$form = array();
				$form['Lang'] = $params['lang'];
				$form['EformFormID'] = $EformFormID;
				$form['SubmitDate'] = date("Y-m-d H:i:s");
				$form['RemoteIP'] = $request->getServer('REMOTE_ADDR');
				$form['UserID'] = empty($this->UserID) ? null : $this->UserID;
				$form['Created'] = date("Y-m-d H:i:s");
				$form['Createdby'] = empty($this->UserID) ? null : $this->UserID;
				$result = $this->db->insert("eform_submission_info", $form);
				$EformSubmissionID = $this->db->lastInsertId();

				foreach($params['params'] as $k => $v) {

					$formData = array();
					$formData['EformFormID'] = $EformFormID;
					$formData['EformSubmissionID'] =  $EformSubmissionID;
					$formData['ControlName'] = $k;
					$formData['ControlValue'] = $v;
					if(gettype($v) == 'array') {
						$tmp_array = array();
						$tmp_array = $v;
						$formData['ControlValue'] = json_encode($tmp_array);
					}
					$this->db->insert("eform_submission_value", $formData);
				}

				unset($params['hdfID']);
				unset($params['lang']);
				unset($params['params']);
				unset($params['g-recaptcha-response']);
				if(count($params)>0) {
					$fileModel = new System_Model_Fileinfo();
					foreach($params as $k => $v) {
						$fileData = $fileModel->itemByHashName($v[0]);
						$formFile = array();
						$formFile['EformFormID'] = $EformFormID;
						$formFile['EformSubmissionID'] =  $EformSubmissionID;
						$formFile['ControlName'] = $k;
						$formFile['ControlValue'] = $fileData->ID;
						$this->db->insert("eform_submission_value", $formFile);
					}
				}

			}
			
			if($result) {
				$data->result = true;
				$data->id = $EformFormID;
			}
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function getDataMail($id)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "eform_form_info"), "")
			->columns(array(
				"UserFromEmail",
				"UserFromName",
				"UserSendEmail",
				"UserSubject",
				"UserBody",
	
				"AdminFromEmail",
				"AdminFromName",
				"AdminSendEmail",
				"AdminSubject",
				"AdminBody",
			))
			->where("tbl.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {

		}
		return $data;
	}

}
?>