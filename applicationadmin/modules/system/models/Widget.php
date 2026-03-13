<?php

class System_Model_Widget {

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
			//echo $e->getMessage();
		}
	}

	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch) {
		
		$data = array();
		try {
			// set query
			$select = $this->db->select()
			->from(array('tbl' => 'sys_widget'), array("ID", "Title", "Ordering", "IsEnable", "Position", "Widget"))
			->where("tbl.IsDeleted = 'N'")
			;
			
			// set criteria
			if (!empty($advancesearch['searchText'])) {
				$select = $select->where("(tbl.Title like " . $this->db->quote("%" . $advancesearch['searchText'] . "%") . ") ");
			}
			if (!empty($advancesearch['enable'])) {
				$select = $select->where("tbl.IsEnable = ?", $advancesearch['enable']);
			}
			if (!empty($advancesearch['position'])) {
				$select = $select->where("tbl.Position = ?", $advancesearch['position']);
			} 
			
			// find total
			$records = $this->getListCount($select);
			
			// set ordering and total
			if (empty($sidx))
				$sidx = "ID";			
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->limitPage($page, $rows)
			; 
			if (in_array($rowSort, array('Title'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {
			echo $e->getMessage();
		}
		
		return $data;
	}

	function getListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	
	function getWidgetTypeList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch) {
		
		$data = array();
		try {
			// set query
			$select = $this->db->select()
			->from(array('tbl' => 'sys_element'), array("ID", "Name", "Type", "RefTypeName"))
			->where("tbl.IsDeleted = 'N'")
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.Type = ? ", "widget")
			;
			
			// set criteria
			if (!empty($advancesearch['searchText'])) {
				$select = $select->where("(tbl.Name like " . $this->db->quote("%" . $advancesearch['searchText'] . "%") . ") ");
			}
			
			// find total
			$records = $this->getWidgetTypeListCount($select);
			
			// set ordering and total
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			;
			if ($rows > 0) {
				$select = $select->limitPage($page, $rows)
				;
			}
			if (in_array($rowSort, array('Name'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
				
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		
		return $data;
	}
	
	function getWidgetTypeListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	function getInfo($userID) {
		$data = new stdClass();
		try {
	
			$select = $this->db->select()
			->from(array('tbl' => 'sys_widget'), array('*'))
			->where("tbl.ID = ?", $userID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->PublishDate = empty($data->PublishDate) ? null : new DateTime($data->PublishDate);
				$data->PublishDown = empty($data->PublishDown) ? null : new DateTime($data->PublishDown);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
	
		}
		return $data;
	}
	
	function save($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$form = array();
			$form['Title'] = empty($params['txtTitle']) ? null : $params['txtTitle'];
			$form['Lang'] = empty($params['lstLanguage']) ? null : $params['lstLanguage'];
			//$form['Note'] = empty($params['txtNote']) ? null : md5($params['txtNote']);
			//$form['Content'] = empty($params['txtContent']) ? null : $params['txtContent'];
			$form['Ordering'] = empty($params['txtOrdering']) ? null : $params['txtOrdering'];
			$form['Position'] = empty($params['lstPosition']) ? null : $params['lstPosition'];
			$form['ShowTitle'] = empty($params['rdlShowTitle']) ? null : $params['rdlShowTitle'];
			//$form['PublishDate'] = empty($params['txtTelephone']) ? null : $params['txtTelephone'];
			//$form['PublishDown'] = empty($params['lstUsergroup']) ? null : $params['lstUsergroup'];
			$form['Widget'] = empty($params['hdfType']) ? null : $params['hdfType'];
			$form['Params'] = null;
			if (count($params["params"]) > 0) {
				$form['Params'] = json_encode($params["params"]); 
			}
			$form['Created'] = date('Y-m-d H:i:s');
			$form['CreatedBy'] = $this->UserID;
			$form['IsEnable'] = $params["isStatus"];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("sys_widget", $form);
			$id = $this->db->lastInsertId();
		
			if($result == true){
				$data->result = true;
				$data->id = $id;
				
				// Save menu
				$showInMenu = $params['ShowInMenu'];
				$formWM = array();
				if ($showInMenu == '0') {
					$formWM["WidgetID"] = $id;
					$formWM["MenuID"] = "0";
					$this->db->insert("sys_widget_menu", $formWM);
				} elseif ($showInMenu == '1' || $showInMenu == '-1') {
					if (count($params["MenuSelected"]) > 0) {
						foreach ($params["MenuSelected"] as $menuID) {							
							$formWM["WidgetID"] = $id;
							$formWM["MenuID"] = $menuID * $showInMenu;
							$this->db->insert("sys_widget_menu", $formWM);
						}
					}
				} 
			}
	
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function update($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$id = $params['hdfID'];
			
			$form = array();
			$form['Title'] = empty($params['txtTitle']) ? null : $params['txtTitle'];
			$form['Lang'] = empty($params['lstLanguage']) ? null : $params['lstLanguage'];
			//$form['Note'] = empty($params['txtNote']) ? null : md5($params['txtNote']);
			//$form['Content'] = empty($params['txtContent']) ? null : $params['txtContent'];
			$form['Ordering'] = empty($params['txtOrdering']) ? null : $params['txtOrdering'];
			$form['Position'] = empty($params['lstPosition']) ? null : $params['lstPosition'];
			$form['ShowTitle'] = empty($params['rdlShowTitle']) ? null : $params['rdlShowTitle'];
			//$form['PublishDate'] = empty($params['txtTelephone']) ? null : $params['txtTelephone'];
			//$form['PublishDown'] = empty($params['lstUsergroup']) ? null : $params['lstUsergroup'];
			$form['Params'] = null;
			if (count($params["params"]) > 0) {
				$form['Params'] = json_encode($params["params"]);
			}
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$form['IsEnable'] = $params["isStatus"];
			$where['ID = ? '] = $params['hdfID'];
			$result = $this->db->update("sys_widget", $form, $where);
						
			if($result){
				$data->result = true;
				$data->id = $id;
				
				// Delete menu
				$whereWM = array();
				$whereWM["WidgetID = ? "] = $id;
				$this->db->delete("sys_widget_menu", $whereWM);
				
				// Save menu
				$showInMenu = $params['ShowInMenu'];
				$formWM = array();
				if ($showInMenu == '0') {
					$formWM["WidgetID"] = $id;
					$formWM["MenuID"] = "0";
					$this->db->insert("sys_widget_menu", $formWM);
				} elseif ($showInMenu == '1' || $showInMenu == '-1') {
					if (count($params["MenuSelected"]) > 0) {
						foreach ($params["MenuSelected"] as $menuID) {
							$formWM["WidgetID"] = $id;
							$formWM["MenuID"] = $menuID * $showInMenu;
							$this->db->insert("sys_widget_menu", $formWM);
						}
					}
				} 
			}
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function delete($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$id = $params['id'];
			//$obj = $this->getInfo($id);
				
			// User
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $id;
			$result = $this->db->update("sys_widget", $form, $where);
							
			$data->result = count($result) > 0;
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function getMenus ($widgetID) {		
		$data = array();
		try {
			// set query
			$select = $this->db->select()
			->from(array('tbl' => 'sys_widget_menu'), array("WidgetID", "MenuID"))
			->where("tbl.WidgetID = ? ", $widgetID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		
		return $data;
	}
}

?>
