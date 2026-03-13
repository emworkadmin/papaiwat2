<?php

class Newsletter_Model_Subscriber
{
	
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
            ->from(array("tbl" => "enewsletter_subscriber"), "")
			->columns(array(
                "tbl.ID",
                "tbl.Name",
                "tbl.Email",
                "tbl.Created",
                "tbl.IsEnable"
            ))
			->where("tbl.IsDeleted = 'N' ")
			;

			if (!empty($advancesearch['textsearch'])) {
				$val = $advancesearch['textsearch'];
                $select = $select->where("tbl.Name like " . $this->db->quote("%" . $val . "%" ) . ") OR (tbl.Email like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
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

	function getlistreport()
	{
		$data = array();
		try {

			$select = $this->db->select()
            ->from(array("tbl" => "enewsletter_subscriber"), "")
			->columns(array(
                "tbl.ID",
                "tbl.Name",
                "tbl.Email",
                "tbl.Created",
                "tbl.IsEnable"
            ))
			->where("tbl.IsDeleted = 'N' ")
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}
	
	function getInfo($id)
	{
		$data = new StdClass();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "enewsletter_subscriber"), "")
			->columns(array(
				"tbl.ID",
				"tbl.Name",
				"tbl.Email",
				"tbl.Created",
				"tbl.IsEnable"
			))
			->where('tbl.ID = ? ', $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

		} catch (Exception $e) {

		}
		return $data;
	}

	function checkEmail($email, $id = null)
	{
		$data = new StdClass();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "enewsletter_subscriber"), "")
			->columns(array(
				"tbl.ID"
			))
			->where('tbl.Email = ? ', $email)
			;

			if($id) {
				$select = $select->where("tbl.ID != ?", $id);
			}
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

		} catch (Exception $e) {

		}
		return $data;
	}

	function getDataCategory($id)
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "enewsletter_category_subscriber") ,"tbl.EnewsletterCategoryID")
			->where("tbl.EnewsletterSubscriberID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

			foreach($data as $key => $value) {
				$result[$key] = $value['EnewsletterCategoryID'];
			}

		} catch (Exception $e) {

		}
		return $result;
	}

	function save($params)
	{
		$data = new stdClass();
		$data->result = false;
		$data->message = '';
		try {

			$modified = date("Y-m-d H:i:s");
			
			$form = array();
			$form['Name'] = $params['Name'];
			$form['Email'] = $params['Email'];
			$form['Created'] = $modified;
			$form['CreatedBy'] = $this->UserID;
			$form['IsEnable'] = $params['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("enewsletter_subscriber", $form);
			$id = $this->db->lastInsertId();
			
			if(count($params['Iscat'])>0) {
				foreach($params['Iscat'] as $key => $value) {
					$formCat = array();
					if($value == 'Y') {
						$formCat['EnewsletterCategoryID'] = $key;
						$formCat['EnewsletterSubscriberID'] = $id;
						$formCat['Created'] = $modified;
						$formCat['CreatedBy'] = $this->UserID;
						$formCat['IsEnable'] = 'Y';
						$formCat['IsDeleted'] = 'N';
						$result = $this->db->insert("enewsletter_category_subscriber", $formCat);
					}
				}
			}

			if($result) {
				$data->result = true;
				$data->id = $id;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMess();
		}
		return $data;
	}
	
	function update($params)
	{
		$data = new stdClass();
		$data->result = false;
		$data->message = '';
		try {

			$modified = date("Y-m-d H:i:s");
			$id = $params['hdfID'];

			$form = array();
			$form['Name'] = $params['Name'];
			$form['Email'] = $params['Email'];
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$form['IsEnable'] = $params['IsEnable'];
			$form['IsDeleted'] = 'N';
			$where['ID = ?'] = $id;
			$result = $this->db->update("enewsletter_subscriber", $form, $where);

			$whereCat = array();
			$whereCat['EnewsletterSubscriberID = ?'] = $id;
			$this->db->delete("enewsletter_category_subscriber", $whereCat);

			if(count($params['Iscat'])>0) {
				foreach($params['Iscat'] as $key => $value) {
					$formCat = array();
					if($value == 'Y') {
						$formCat['EnewsletterCategoryID'] = $key;
						$formCat['EnewsletterSubscriberID'] = $id;
						$formCat['Created'] = $modified;
						$formCat['CreatedBy'] = $this->UserID;
						$formCat['IsEnable'] = 'Y';
						$formCat['IsDeleted'] = 'N';
						$result = $this->db->insert("enewsletter_category_subscriber", $formCat);
					}
				}
			}

			if($result) {
				$data->result = true;
				$data->id = $id;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMess();
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
			$result = $this->db->update("enewsletter_subscriber", $form, $where);
			
			$data->result = $result;
			
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
					$affRow += $this->db->update("enewsletter_subscriber", $form, $where);
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
					->from(array('tbl'=>'enewsletter_subscriber'), array(
						'UserID',
						'Name',
						'Email',
						'ReceiveType',
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['UserID'] = empty($dataInfo->UserID) ? null : $dataInfo->UserID;
					$form['Name'] = empty($dataInfo->Name) ? null : $dataInfo->Name;
					$form['Email'] = empty($dataInfo->Email) ? null : $dataInfo->Email;
					$form['ReceiveType'] = empty($dataInfo->ReceiveType) ? null : $dataInfo->ReceiveType;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("enewsletter_subscriber", $form);
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
					$affRow += $this->db->update("enewsletter_subscriber", $form, $where);
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