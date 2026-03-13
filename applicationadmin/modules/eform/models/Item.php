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
	
	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbl' => 'eform_form_info'), "")
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID",
					"IsDeleted",
					"IsEnable" ,
					"PublishDate",
					"PublishDown",
					"CustomCss",

					"IntroImageID" => "tbl.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where ( "tbl.IsDeleted = 'N' " )
			->where ( "tbl.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();

			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
			}

		} catch ( Exception $e ) {
			
		}
		return $data;
	}
	
	function getDataByLanguage($id, $lang)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'eform_form_info'), "")
			->joinLeft(array("md" => "eform_form_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "m.ID",
					"EformCategoryID",
					"Lang" => 'md.Lang',
					"Title" => "md.Title",
					"IntroText" => "md.IntroText",
					"ResponseMessage" => "md.ResponseMessage",
					"ResponseURL" => "md.ResponseURL",
					"ResponseMessageType" => "md.ResponseMessageType",
					"Detail" => "md.Detail",
					"Ordering" => "m.Ordering",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable"
			))
			->where("m.ID = ?", $id)
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getCategories($EformFormID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'eform_category_form'), '')
			->columns(array(
					"EformCategoryID" => "tbl.EformCategoryID"
			))
			->where("tbl.EformFormID = ? ", $EformFormID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'eform_form_info'), array( 'ID'))
		->joinInner(array('d' => 'eform_form_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->order("convert(d.Title USING tis620) ASC")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}	
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "eform_form_info"), "")
			->joinLeft(array("d" => "eform_form_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->columns(array(
				"ID" => "tbl.ID",
				"Created" => "tbl.Created",
				"IsEnable" => "tbl.IsEnable",
				"Title" => "d.Title",
			))
			->where("tbl.IsDeleted = 'N'")
			;
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}

			$records = $this->getListCount ( $select );
			// set ordering and total
			if (empty ( $sidx )) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select ()
			->from ( array ('tbs' => $select ), array ('*'))
			->columns ( array ("RECORD" => "abs($records)" ) )
			->limitPage ( $page, $rows )
			;

			if (in_array($rowSort, array('Title'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	function getlistCount($select)
	{
		$select = $this->db->select()
		->from(array("T" => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}

	function save($params)
	{
		$data = new stdClass();
		$data->result = false;

		try {

			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = '';
			$modified = date("Y-m-d H:i:s");

			if($defaultLanguageCode == 'th') {
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			} else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}

			$form = array();

			$form['IntroImageID'] = $params['IntroImageID'];
			$form['PublishDate'] =  $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] =  $param[$defaultLanguageCode]['PublishDown'];
			$form['CustomCss'] = $param[$defaultLanguageCode]['ItemCustomCss'];
			$form['Created']  = $modified;
			$form['CreatedBy'] =  empty($this->UserID) ? null : $this->UserID;
			$form['IsEnable'] = 'Y';
			$form['IsDeleted'] = 'N';

			$result = $this->db->insert("eform_form_info", $form);
			$id = $this->db->lastInsertId();

			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['EformFormID'] = $id;
					$formDetail['EformCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("eform_category_form", $formDetail);					
				}
			}

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Title'] = $value['Title'];
					$formDetail['IntroText'] = $value['IntroText'];
					$formDetail['Detail'] = $value['Detail'];
					$formDetail['ResponseMessageType'] = $value['ResponseMessageType'];
					$formDetail['ResponseMessage'] = $value['ResponseMessage'];
					$formDetail['ResponseURL'] = $value['ResponseURL'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("eform_form_detail", $formDetail);
				}
			}
			
			if($result){
				$data->id = $id;
				$data->result = true;
			}


		} catch (Exception $e) {
			$data->result = false;
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

			if($defaultLanguageCode == 'th') {
				$param[$defaultLanguageCode]['PublishDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			} else {
				$param[$defaultLanguageCode]['PublishDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDate']);
				$param[$defaultLanguageCode]['PublishDate'] .= empty($param[$defaultLanguageCode]['PublishDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDateTime']);
				$param[$defaultLanguageCode]['PublishDown'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['PublishDown']);
				$param[$defaultLanguageCode]['PublishDown'] = empty($param[$defaultLanguageCode]['PublishDown']) ? null : $param[$defaultLanguageCode]['PublishDown'] . " " . (empty($param[$defaultLanguageCode]['PublishDownTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['PublishDownTime']));
			}

			$form = array();
			$where = array();

			$form['EformCategoryID'] = empty($params['CategoryIds'][0]) ? null: $params['CategoryIds'][0];

			$form['IntroImageID'] = $params['IntroImageID'];
			$form['PublishDate'] =  $param[$defaultLanguageCode]['PublishDate'];
			$form['PublishDown'] =  $param[$defaultLanguageCode]['PublishDown'];
			$form['CustomCss'] = $param[$defaultLanguageCode]['ItemCustomCss'];
			$form['Created']  = $modified;
			$form['CreatedBy'] =  empty($this->UserID) ? null : $this->UserID;
			$form['IsEnable'] = 'Y';
			$form['IsDeleted'] = 'N';
			$where['ID = ?'] = $id;
			$result = $this->db->update("eform_form_info", $form, $where);

			// Delete All Category
			$whereIdDelete = array();
			$whereIdDelete['EformFormID = ? '] = $id;
			$this->db->delete("eform_category_form",$whereIdDelete);
			// Category
			if (count($params['CategoryIds']) > 0) {
				foreach ($params['CategoryIds'] as $cId) {
					$formDetail = array();
					$formDetail['EformFormID'] = $id;
					$formDetail['EformCategoryID'] = $cId;
					$formDetail['Created'] = $modified;
					$formDetail['CreatedBy']= $this->UserID;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("eform_category_form", $formDetail);
				}
			}

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $value['Title'];
					$formDetail['IntroText'] = $value['IntroText'];
					$formDetail['Detail'] = $value['Detail'];
					$formDetail['ResponseMessageType'] = $value['ResponseMessageType'];
					$formDetail['ResponseMessage'] = $value['ResponseMessage'];
					$formDetail['ResponseURL'] = $value['ResponseURL'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$whereDetail['ID = ?'] = $id;
					$whereDetail['Lang = ?'] = $lang;
					$result = $this->db->update("eform_form_detail", $formDetail, $whereDetail);
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
	
	function delete($params)
	{
		$data = new stdClass();
		$data->result = false;
		try {

			$modified = date("Y-m-d H:i:s");
			
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("eform_form_info", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	/****************************************************** Edit Form ******************************************************/

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

	function typeDetail($id)
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "eform_control_type_field"), "")
			->columns(array(
				"tbl.Name",
				"tbl.Values"
			))
			->where("tbl.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$result = $stmt->fetchAll();

			foreach($result as $key => $val) {
				$data[$val['Name']] = $val['Values'];
			}

		} catch (Exception $e) {

		}
		return $data;
	}

	function getReCaptchakey() {
		$result = array();
		
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_setting'), "")
			->columns(array(
				"tbl.Name",
				"tbl.Value"
			))
			->where("tbl.GroupName = 'General'")
			->where("tbl.Name LIKE '%Captcha%'")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			foreach ($data as $key => $value) {
				$result[$value["Name"]] = $value["Value"]; 
			}
			
			
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return $result;
	}

	/****************************************************** Submission ******************************************************/
	
	function submissionlist($id)
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "eform_submission_info"), "")
			->columns(array(
				"tbl.SubmitDate",
				"tbl.RemoteIP",
				"tbl.UserID",
			))
			->where("tbl.EformFormID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	function getSubmissionList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'eform_submission_info'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"SubmitDate" => "tbl.SubmitDate",
					"RemoteIP" => "tbl.RemoteIP",
			))
			->where("tbl.EformFormID = ?", $advancesearch['itemid'])
			->where("tbl.IsDeleted ='N'")
			;
			
			$records = $this->getSubmissionListCount($select);
			
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->order("$rowSort $sord")
			->limitPage($page, $rows)
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["SubmitDate"] = empty($data[$i]["SubmitDate"]) ? null : new DateTime($data[$i]["SubmitDate"]);
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return $data;
	}
	function getSubmissionListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}

	function getExportSubmission($id, $lang = 'th') {
		$data = array();
		try {

			$select = $this->db->select()
			->from(array('tbl' => 'eform_submission_info'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"SubmitDate" => "tbl.SubmitDate",
					"RemoteIP" => "tbl.RemoteIP",
			))
			->where("tbl.EformFormID = ?", $id)
			->where("tbl.IsDeleted ='N'")
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["SubmitDate"] = empty($data[$i]["SubmitDate"]) ? null : new DateTime($data[$i]["SubmitDate"]);
				}
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getSubmissionValues($submissionID, $eformID, $controllerName) {

		$data = new stdClass();
		try {
			$select= $this->db->select()
			->from(array("tbl" => "eform_submission_value"), "")
			->columns(array(
				"tbl.ID",
				"tbl.ControlValue"
			))
			->where("tbl.EformSubmissionID = ?", $submissionID)
			->where("tbl.EformFormID = ?", $eformID)
			->where("tbl.ControlName = ?", $controllerName)
			;

			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();

		} catch (Exception $e) {
			
		}
		return $data;
	}

	/****************************************************** Static ******************************************************/

	function getStaticList($eformID,$params)
	{
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'eform_submission_info'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"SubmitDate" => "tbl.SubmitDate",
					"RemoteIP" => "tbl.RemoteIP",
					"EformFormID" => "tbl.EformFormID"
			))
			->where("tbl.EformFormID = ?", $eformID)
			->where("tbl.IsDeleted ='N'")
			;

			if (!empty($params['sendform'])) {
				$select = $select->where("tbl.SubmitDate >= ? ", Emwork_Utility_DateTime::dateThaiToMySQL($params['sendform']));
			}
			if (!empty($params['sendto'])) {
				$select = $select->where("tbl.SubmitDate <= ? ", Emwork_Utility_DateTime::dateThaiToMySQL($params['sendto']) . " 23:59:59");
			}

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	function getStaticValues($EformFormID, $ControlName, $ControlTypeID)
	{
		if($ControlTypeID == 7) {
			$ControlName = 'hdf'.$ControlName;
		}
		
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "eform_submission_value"), "")
			->columns(array(
				"tbl.ControlName",
				"tbl.ControlValue"
			))
			->where("tbl.EformFormID = ?", $EformFormID)
			->where("tbl.ControlName = ?", $ControlName)
			;
			$stmt = $this->db->query($select);
			$result = $stmt->fetchAll();

			if($ControlTypeID == 7) {
				$fileModel = new System_Model_Fileinfo;
				$tmp_result = array();
				foreach($result as $k => $v) {
					$fileData = $fileModel->item($v['ControlValue']);
					$key = Emwork_Utility::notShowNull($fileData->Name, 'No Data.');
					if(count($tmp_result[$key])<0) {
						$tmp_result[$key] = array();
					} else {
						$tmp_result[$key]['Value'] = $tmp_result[$key]['Value'] + 1;
					}
				}
				$data['Value'] = $tmp_result;
			} else {
				if(!in_array($ControlTypeID, array(3,4,5))) {
					$tmp_result = array();
					foreach($result as $k => $v) {
						$key = Emwork_Utility::notShowNull($v['ControlValue'], 'No Data.');
						if(count($tmp_result[$key])<0) {
							$tmp_result[$key] = array();
						} else {
							$tmp_result[$key]['Value'] = $tmp_result[$key]['Value'] + 1;
						}						
					}
					$data['Value'] = $tmp_result;
				} else {
					$tmp_result = array();
					foreach($result as $k => $v) {
						$jsdecode = json_decode($v['ControlValue']);
						foreach($jsdecode as $jsk) {
							$key = Emwork_Utility::notShowNull($jsk, 'No Data.');
							if(count($tmp_result[$key])<0) {
								$tmp_result[$key] = array();
							} else {
								$tmp_result[$key]['Value'] = $tmp_result[$key]['Value'] + 1;
							}
						}
					}
					$data['Value'] = $tmp_result;
				}
			}

		} catch (Exception $e) {

		}
		return $data;
	}
	
	
	function interview($params)
	{
		try {

			$select = $this->db->select()
			->from(array("tbl" => "eform_submission_info"), "")
			->columns(array(
				"ID" => "COUNT(ID)",
				"SubmitDate" => "DATE_FORMAT(SubmitDate, '%Y-%m-%d')"
			))
			->where("tbl.EformFormID = ?", $params['EformFormID'])
			;
			if (!empty($params['sendform'])) {
				$select = $select->where("tbl.SubmitDate >= ? ", Emwork_Utility_DateTime::dateThaiToMySQL($params['sendform']));
			}
			if (!empty($params['sendto'])) {
				$select = $select->where("tbl.SubmitDate <= ? ", Emwork_Utility_DateTime::dateThaiToMySQL($params['sendto']) . " 23:59:59");
			}
			$select = $select->group("DATE_FORMAT(tbl.SubmitDate, '%Y-%m-%d')");

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	/****************************************************** Setting ******************************************************/

	function getSetting($id)
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

	function SaveSetting($params)
	{
		$data = new StdClass();
		$data->result = false;
		try {

			$id = $params['hdfID'];

			$form = array();
			$where = array();

			$form['UserFromEmail'] = $params['ItemUserFromEmail'];
			$form['UserFromName'] = $params['ItemUserFromName'];
			$form['UserSendEmail'] = $params['ItemUserSendEmail'];
			$form['UserSubject'] = $params['ItemUserSubject'];
			$form['UserBody'] = $params['ItemUserBody'];

			$form['AdminFromEmail'] = $params['ItemAdminFromEmail'];
			$form['AdminFromName'] = $params['ItemAdminFromName'];
			$form['AdminSendEmail'] = $params['ItemAdminSendEmail'];
			$form['AdminSubject'] = $params['ItemAdminSubject'];
			$form['AdminBody'] = $params['ItemAdminBody'];

			$where['ID = ?'] = $id;

			$this->db->update("eform_form_info", $form, $where);

			$data->result = true;
			$data->id = $id;

		} catch (Exception $e) {
		}

		return $data;
	}

	function listPublish($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			$status = $params['status'];
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {
					$form = array ();
					$where = array ();
					$form['IsEnable'] = empty($status) ? 'N' : $status;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['ID = ? '] = $id;
					$affRow += $this->db->update("eform_form_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function listCopy($params) { 
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			$defaultLanguage = $params['defaultLanguage'];
			
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {

					// Info
					$selectInfo = $this->db->select ()
					->from(array('tbl'=>'eform_form_info'), array(
						'EformCategoryID',
						'IntroImageID',
						'MainTitle',
						'TemplateType',
						'PublishDate',
						'PublishDown',
						'CustomCss',
						'Ordering',
						'Settings',
						'UserFromEmail',
						'UserFromName',
						'UserSendEmail',
						'UserSubject',
						'UserBody',
						'AdminFromEmail',
						'AdminFromName',
						'AdminSendEmail',
						'AdminSubject',
						'AdminBody',
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['EformCategoryID'] = empty($dataInfo->EformCategoryID) ? null : $dataInfo->EformCategoryID;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['MainTitle'] = empty($dataInfo->MainTitle) ? null : $dataInfo->MainTitle;
					$form['TemplateType'] = empty($dataInfo->TemplateType) ? 1 : $dataInfo->TemplateType;
					$form['PublishDate'] = empty($dataInfo->PublishDate) ? null : $dataInfo->PublishDate;
					$form['PublishDown'] = empty($dataInfo->PublishDown) ? null : $dataInfo->PublishDown;
					$form['CustomCss'] = empty($dataInfo->CustomCss) ? null : $dataInfo->CustomCss;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['Settings'] = empty($dataInfo->Settings) ? null : $dataInfo->Settings;
					$form['UserFromEmail'] = empty($dataInfo->UserFromEmail) ? null : $dataInfo->UserFromEmail;
					$form['UserFromName'] = empty($dataInfo->UserFromName) ? null : $dataInfo->UserFromName;
					$form['UserSendEmail'] = empty($dataInfo->UserSendEmail) ? null : $dataInfo->UserSendEmail;
					$form['UserSubject'] = empty($dataInfo->UserSubject) ? null : $dataInfo->UserSubject;
					$form['UserBody'] = empty($dataInfo->UserBody) ? null : $dataInfo->UserBody;
					$form['AdminFromEmail'] = empty($dataInfo->AdminFromEmail) ? null : $dataInfo->AdminFromEmail;
					$form['AdminFromName'] = empty($dataInfo->AdminFromName) ? null : $dataInfo->AdminFromName;
					$form['AdminSendEmail'] = empty($dataInfo->AdminSendEmail) ? null : $dataInfo->AdminSendEmail;
					$form['AdminSubject'] = empty($dataInfo->AdminSubject) ? null : $dataInfo->AdminSubject;
					$form['AdminBody'] = empty($dataInfo->AdminBody) ? null : $dataInfo->AdminBody;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("eform_form_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'eform_form_detail'), array('Lang','Title','IntroText','Detail','ResponseMessageType','ResponseMessage','ResponseURL'))
						->where("tbl.ID = ? ", $id)
						;
						$stmtDetail = $this->db->query($selectDetail);
						$dataDetail = $stmtDetail->fetchAll();

						if(count($dataDetail)>0){
							foreach($dataDetail as $d){
								$form = array();
								$form['ID'] = $tmpID;
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Title'] = empty($d['Title']) ? null : $d['Title'];
								if($defaultLanguage == $d['Lang']){
									$form['Title'] = $form['Title'] . ' (Copy)';
								}
								$form['IntroText'] = empty($d['IntroText']) ? null : $d['IntroText'];
								$form['Detail'] = empty($d['Detail']) ? null : $d['Detail'];
								$form['ResponseMessageType'] = empty($d['ResponseMessageType']) ? null : $d['ResponseMessageType'];
								$form['ResponseMessage'] = empty($d['ResponseMessage']) ? null : $d['ResponseMessage'];
								$form['ResponseURL'] = empty($d['ResponseURL']) ? null : $d['ResponseURL'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("eform_form_detail", $form);
							}
						}

						// Category Form
						$selectCatArt = $this->db->select ()
						->from(array('tbl'=>'eform_category_form'), array('EformCategoryID'))
						->where("tbl.EformFormID = ? ", $id)
						;
						$stmtCatArt = $this->db->query($selectCatArt);
						$dataCatArt = $stmtCatArt->fetchAll();

						if(count($dataCatArt)>0){
							foreach($dataCatArt as $d){
								$form = array();
								$form['EformFormID'] = $tmpID;
								$form['EformCategoryID'] = empty($d['EformCategoryID']) ? null : $d['EformCategoryID'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("eform_category_form", $form);
							}
						}

						// Form Control
						$selectFormControl = $this->db->select ()
						->from(array('tbl'=>'eform_form_control'), array('ID','EformControlTypeID','Ordering'))
						->where("tbl.EformFormID = ? ", $id)
						->where("tbl.IsDeleted = 'N' ")
						;
						$stmtFormControl = $this->db->query($selectFormControl);
						$dataFormControl = $stmtFormControl->fetchAll();

						if(count($dataFormControl)>0){
							foreach($dataFormControl as $d){
								$form = array();
								$form['EformFormID'] = $tmpID;
								$form['EformControlTypeID'] = empty($d['EformControlTypeID']) ? null : $d['EformControlTypeID'];
								$form['Ordering'] = empty($d['Ordering']) ? null : $d['Ordering'];
								// $form['Created'] = $modified;
								// $form['CreatedBy'] = $this->UserID;
								$this->db->insert("eform_form_control", $form);
								$tmpEformControlID = $this->db->lastInsertId();

								// Form Control Property
								$selectFCP = $this->db->select ()
								->from(array('tbl'=>'eform_form_control_property'), array('Name','Value'))
								->where("tbl.EformControlID = ? ", $d['ID'])
								;
								$stmtFCP = $this->db->query($selectFCP);
								$dataFCP = $stmtFCP->fetchAll();

								if(count($dataFCP)>0){
									foreach($dataFCP as $d){
										$form = array();
										$form['EformControlID'] = $tmpEformControlID;
										$form['Name'] = empty($d['Name']) ? null : $d['Name'];
										$form['Value'] = empty($d['Value']) ? null : $d['Value'];
										// $form['Created'] = $modified;
										// $form['CreatedBy'] = $this->UserID;
										$this->db->insert("eform_form_control_property", $form);
									}
								}

							}
						}

					}

				} // End Foreach
			} // End Count
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function listDelete($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {
					$form = array ();
					$where = array ();
					$form['IsDeleted'] = 'Y';
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['ID = ? '] = $id;
					$affRow += $this->db->update("eform_form_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

}
?>
