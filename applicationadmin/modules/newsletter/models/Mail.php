<?php

class Newsletter_Model_Mail {
	
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
    
    function getlist($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
    {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "enewsletter_mail"), "")
			->columns(array(
                "tbl.ID",
                "tbl.EnewsletterCategoryID",
                "tbl.Subject",
				"tbl.Created",
				"tbl.IsEnable"
            ))
			->where("tbl.IsDeleted = 'N' ")
			;

			if (!empty($advancesearch['textsearch'])) {
				$val = $advancesearch['textsearch'];
				$select = $select->where("tbl.Subject like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}
			if (!empty($advancesearch['isCat'])) {
				$select = $select->where("tbl.EnewsletterCategoryID = ? ", $advancesearch['isCat']);
			}
						
			$records = $this->getListCount($select);
			
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			//->order("$rowSort $sord")
			->limitPage($page, $rows)
			;
			if (in_array($rowSort, array('Subject'))) {
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

	function getListCount($select) 
	{
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}

	function getInfo($id)
	{
		$select= $this->db->select()
		->from(array("tbl"=>"enewsletter_mail"), "")
		->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID", "")
		->columns(array(
			"tbl.ID",
			"tbl.Subject",
			"tbl.EnewsletterCategoryID",
			"tbl.SendType",
			"tbl.Body",

			"FileID" => "fi.ID",
			"FileName" => "fi.Name",
			"HashName" => "fi.HashName",
			"FilePath" => "fi.Path",
			"FileType" => "fi.FileType",
			"FileSize" => "fi.FileSize",
			"FileExtension" => "fi.Extension",

			"tbl.FromName",
			"tbl.FromEmail",

			"tbl.IsSend",
			"tbl.SendDate",
			"tbl.SendBy",

			"tbl.IsEnable"
		))
		->where("tbl.ID = ? ", $id)
		;

		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		if($data->IsSend == 'Y') {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_user'), "")
			->columns(array(
				"FullName" => "CONCAT(tbl.FirstName,' ',tbl.LastName)"
			))
			->where("tbl.ID = ?", $data->SendBy)
			;
			$stmt = $this->db->query($select);
			$user = $stmt->fetchObject();
			$data->FullName = $user->FullName;
		}

		return $data;
	}

	function save($params)
	{
		$data = new stdClass ();
		$data->result = false;
		try {

			$id = '';
			$modified = date("Y-m-d H:i:s");

			$defaultSetting = new stdClass();
			$defaultSetting->FromEmail = System_Model_Setting::getValue("FromEmail", "SMTP");
			$defaultSetting->FromName = System_Model_Setting::getValue("FromName", "SMTP");

			$form = array();
			$form['Subject'] = $params['Subject'];
			$form['Body'] = $params['Body'];
			$form['SendType'] = $params['IsSendType'];
			$form['EnewsletterCategoryID'] = $params['IsCat'];
			$form['FromName'] = empty($params['FromName']) ? $defaultSetting->FromName : $params['FromName'];
			$form['FromEmail'] = empty($params['FromEmail']) ? $defaultSetting->FromEmail : $params['FromEmail'];
			$form['FileInfoID'] = $params['FileID'];
			if($params['IsSend'] == 'Y') {
				$form['IsSend'] = 'Y';
				$form['SendDate'] = $modified;
				$form['SendBy'] = $this->UserID;
			} else {
				$form['IsSend'] = 'N';
			}
			$form['Created'] = $modified;
			$form['CreatedBy'] = $this->UserID;			
			$form['IsEnable'];
			$form['IsDeleted'] = 'N';

			$result = $this->db->insert("enewsletter_mail", $form);
			$id = $this->db->lastInsertId();
			
			if($result) {
				$data->id = $id;
				$data->result = true;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}

	function update($params)
	{
		$data = new stdClass ();
		$data->result = false;
		try {

			$id = $params['hdfID'];
			$modified = date("Y-m-d H:i:s");

			$defaultSetting = new stdClass();
			$defaultSetting->FromEmail = System_Model_Setting::getValue("FromEmail", "SMTP");
			$defaultSetting->FromName = System_Model_Setting::getValue("FromName", "SMTP");

			$form = array();
			$form['Subject'] = $params['Subject'];
			$form['Body'] = $params['Body'];
			$form['SendType'] = $params['IsSendType'];
			$form['EnewsletterCategoryID'] = $params['IsCat'];
			$form['FromName'] = empty($params['FromName']) ? $defaultSetting->FromName : $params['FromName'];
			$form['FromEmail'] = empty($params['FromEmail']) ? $defaultSetting->FromEmail : $params['FromEmail'];
			$form['FileInfoID'] = $params['FileID'];
			if($params['IsSend'] == 'Y') {
				$form['IsSend'] = 'Y';
				$form['SendDate'] = $modified;
				$form['SendBy'] = $this->UserID;
			} else {
				$form['IsSend'] = 'N';
			}
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$form['IsEnable'];
			$form['IsDeleted'] = 'N';
			$where['ID = ?'] = $id;
			$result = $this->db->update("enewsletter_mail", $form, $where);
			
			if($result) {
				$data->id = $id;
				$data->result = true;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}

	function delete($params) {
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
			$result = $this->db->update("enewsletter_mail", $form, $where);
			
			$data->result = $result;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function getDataEmail($params)
	{
		$data = array();
		try {

			$Type = $params->SendType;
			$All = $params->SendAll;
			
			$tmp_send = "";

			if($All == 'N') {
				$select = $this->db->select()
				->from(array("tbl" => "enewsletter_mail_send"), array("EnewsletterSubscriberID"))
				->where("tbl.EnewsletterMailID = ?", $params->ID)
				;
				$stmt = $this->db->query($select);
				$dataSend = $stmt->fetchAll();
				foreach($dataSend as $key => $value) {
					$tmp_send .= $value['EnewsletterSubscriberID'] . ',';
				}	
				$tmp_send = substr($tmp_send, 0, -1);
			}
	
			$select = $this->db->select()
			->from(array("tbl" => "enewsletter_subscriber"), array("ID", "Name", "Email"))
			->joinLeft(array("cs" => "enewsletter_category_subscriber"), "tbl.ID = cs.EnewsletterSubscriberID", "")
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			;

			if($tmp_send && $All == 'N') {
				$select = $select->where("tbl.ID NOT IN (" . $tmp_send . ")");
			}

			if($Type == 'C') {
				$select = $select->where("cs.EnewsletterCategoryID = ?", $params->EnewsletterCategoryID);
			} else {
				$select = $select->group("cs.EnewsletterSubscriberID");
			}

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	function updateMail($dataMail, $dataUser)
	{
		$data = new stdClass();
		$data->result = false;
		try {

			$modified = date("Y-m-d H:i:s");
			foreach($dataUser as $user) {
				$form = array();
				$where = array();
				$form['EnewsletterSubscriberID'] = $user['ID'];
				$form['EnewsletterMailID'] = $dataMail->ID;
				$form['Subject'] = $dataMail->Subject;
				$form['Body'] = $dataMail->Body;
				$form['IsSend'] = 'Y';
				$form['SendDate'] = $modified;
				$form['Created'] = $modified;
				$form['CreatedBy'] = $this->UserID;
				$form['IsEnable'] = 'Y';
				$form['IsDeleted'] = 'N';
				$result += $this->db->insert("enewsletter_mail_send", $form);
			}

			if($result == count($dataUser)) {
				$data->result = true;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
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
					$affRow += $this->db->update("enewsletter_mail", $form, $where);
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


					$selectInfo = $this->db->select ()
					->from(array('tbl'=>'enewsletter_mail'), array(
						'Lang',
						'EnewsletterCategoryID',
						'Subject',
						'Body',
						'FromName',
						'FromEmail',
						'SendType',
						'FileInfoID',
						'IsSend',
						'SendDate',
						'SendBy'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['Lang'] = empty($dataInfo->Lang) ? null : $dataInfo->Lang;
					$form['EnewsletterCategoryID'] = empty($dataInfo->EnewsletterCategoryID) ? null : $dataInfo->EnewsletterCategoryID;
					$form['Subject'] = empty($dataInfo->Subject) ? null : ($dataInfo->Subject.' (Copy)');
					$form['Body'] = empty($dataInfo->Body) ? null : $dataInfo->Body;
					$form['FromName'] = empty($dataInfo->FromName) ? null : $dataInfo->FromName;
					$form['FromEmail'] = empty($dataInfo->FromEmail) ? null : $dataInfo->FromEmail;
					$form['SendType'] = empty($dataInfo->SendType) ? null : $dataInfo->SendType;
					$form['FileInfoID'] = empty($dataInfo->FileInfoID) ? null : $dataInfo->FileInfoID;
					$form['IsSend'] = empty($dataInfo->IsSend) ? null : $dataInfo->IsSend;
					$form['SendDate'] = empty($dataInfo->SendDate) ? null : $dataInfo->SendDate;
					$form['SendBy'] = empty($dataInfo->SendBy) ? null : $dataInfo->SendBy;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("enewsletter_mail", $form);
					$tmpID = $this->db->lastInsertId();

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
					$form ['IsDeleted'] = 'Y';
					$form ['Modified'] = $modified;
					$form ['ModifiedBy'] = $this->UserID;
					$where ['ID = ? '] = $id;
					$affRow += $this->db->update("enewsletter_mail", $form, $where);
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