<?php

class Newsletter_Model_Index
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
	
	function getCategoryOption($lang)
	{
		$select = $this->db->select()
		->from ( array ('tbs' => 'enewsletter_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'enewsletter_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Name') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ShowFront = 'Y'")
		// ->where ( "d.Lang = ?", $lang )
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

	function checkEmail($email, $id = null)
	{
		$data = new StdClass();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "enewsletter_subscriber"), "")
			->columns(array(
				"tbl.*"
			))
			->where('tbl.Email = ? ', $email)
			->where("tbl.IsDeleted = 'N'")
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
	
	function save($params)
	{
		$data = new stdClass();
		$data->result = false;
		$data->message = '';
		try {

			$modified = date("Y-m-d H:i:s");
			
			$form = array();
			$where = array();
			$form['Name'] = $params['Name'];
			//$form['Email'] = $params['Email'];
			$form['Created'] = $modified;
			$form['UserID'] = empty($this->UserID) ? null : $this->UserID;
			$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
			$form['IsEnable'] = 'Y';
			$where["Email = ? "] = $params['Email'];
			$where["IsDeleted = ? "] = 'N';
			$affRow = $this->db->update("enewsletter_subscriber", $form, $where);
			if ($affRow == 0) {
				$form['Email'] = $params['Email'];
				$form['IsDeleted'] = 'N';
				$affRow = $this->db->insert("enewsletter_subscriber", $form);
				$id = $this->db->lastInsertId();
			} else {
				$obj = $this->checkEmail($params['Email']);
				$id = $obj->ID;
			}
			
// 			$affRow = $this->db->insert("enewsletter_subscriber", $form);
// 			$id = $this->db->lastInsertId();
			
			$formCat = array();
			$whereCat = array();
			$formCat["IsEnable"] = "N";
			$whereCat['EnewsletterSubscriberID = ? '] = $id;
			$this->db->update("enewsletter_category_subscriber", $formCat, $whereCat);
			if(count($params['Iscat'])>0) {
				foreach($params['Iscat'] as $key => $value) {
					$formCat = array();
					$whereCat = array();
					if($value == 'Y') {
						$formCat['Created'] = $modified;
						$formCat['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$formCat['IsEnable'] = 'Y';
						$formCat['IsDeleted'] = 'N';
						$whereCat["EnewsletterCategoryID = ? "] = $key;
						$whereCat["EnewsletterSubscriberID = ? "] = $id;
						$affRowCat = $this->db->update("enewsletter_category_subscriber", $formCat, $whereCat);
						if ($affRowCat == 0) {
							$formCat['EnewsletterCategoryID'] = $key;
							$formCat['EnewsletterSubscriberID'] = $id;
							$this->db->insert("enewsletter_category_subscriber", $formCat);
						}
						
						
					}
				}
			}

			if($affRow) {
				$data->result = true;
				$data->id = $id;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMess();
		}
		return $data;
	}
}

?>