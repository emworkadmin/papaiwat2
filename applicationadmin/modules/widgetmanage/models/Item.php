<?php

class Widgetmanage_Model_Item {
	
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

	public function getOption($widgetType=null, $notID=null) {
		$select = $this->db->select()
		->from ( array ('tbs' => 'widget_item' ), array ('ID','Name') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		;
		if($widgetType){
			$select = $select->where("tbs.WidgetType = ?", $widgetType);
		}
		if($notID){
			$select = $select->where("tbs.ID != ?", $notID);
		}
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Name"];
		}
		return $result;
	}
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'widget_item'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"Name" => "tbl.Name",
					"DataType" => "tbl.DataType",
					"WidgetType" => "tbl.WidgetType",
					"IsEnable" => "tbl.IsEnable"
			))
			->where("tbl.IsDeleted ='N'")
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("tbl.Name like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['widgetType'])) {
				$select = $select->where("tbl.WidgetType = ? ", $advancesearch['widgetType']);
			}
			if (!empty($advancesearch['dataType'])) {
				$select = $select->where("tbl.DataType = ? ", $advancesearch['dataType']);
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
			->limitPage($page, $rows)
			;
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
	
	function getListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	function getInfo($id) {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'widget_item'), array('tbl.*'))
			->where("tbl.ID = ? " , $id)
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
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
			$form['Name'] = $params['Name'];
			$form['DataType'] = $params['DataType'];
			$form['WidgetType'] = $params['WidgetType'];
			$form['ContentSettings'] = json_encode($params['ContentSettings']);
			$form['DisplaySettings'] = json_encode($params['DisplaySettings']);
			$form['Created'] = date("Y-m-d H:i:s");
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= 'Y';
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("widget_item", $form);
			$id = $this->db->lastInsertId();

			if($result){
				$data->id = $id;
				$data->result = true;
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
			$id = $params['ID'];

			$form = array();
			$where = array();
			if($params['UpdateType']=='C'){
				$form['Name'] = $params['Name'];
				$form['ContentSettings'] = json_encode($params['ContentSettings']);
				$form['IsEnable']= $params['IsEnable'];
			}else if($params['UpdateType']=='D'){
				$form['DisplaySettings'] = json_encode($params['DisplaySettings']);
			}
			$form['Modified'] = date("Y-m-d H:i:s");
			$form['ModifiedBy']= $this->UserID;
			$where['ID = ? '] = $id;
			$result = $this->db->update("widget_item", $form, $where);
			
			if($result){
				$data->id = $id;
				$data->result = true;
			}
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function updateCustom($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$id = $params['ID'];

			$form = array();
			$where = array();
			$form['Name'] = $params['Name'];
			$form['ContentSettings'] = json_encode($params['ContentSettings']);
			$form['Modified'] = date("Y-m-d H:i:s");
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= 'Y';
			$where['ID = ? '] = $id;
			$result = $this->db->update("widget_item", $form, $where);
			
			if($result){
				$data->id = $id;
				$data->result = true;
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

			$modified = date("Y-m-d H:i:s");
			
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("widget_item", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	public function getOptionWidgetSystem() {
		$select = $this->db->select()
		->from ( array ('tbs' => 'sys_widget' ), array ('ID','Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		;
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Title"];
		}
		return $result;
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
					$affRow += $this->db->update("widget_item", $form, $where);
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
					->from(array('tbl'=>'widget_item'), array(
						'Name',
						'DataType',
						'WidgetType',
						'ContentSettings',
						'DisplaySettings',
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['Name'] = empty($dataInfo->Name) ? null : ($dataInfo->Name.' (Copy)');
					$form['DataType'] = empty($dataInfo->DataType) ? null : ($dataInfo->DataType);
					$form['WidgetType'] = empty($dataInfo->WidgetType) ? null : ($dataInfo->WidgetType);
					$form['ContentSettings'] = empty($dataInfo->ContentSettings) ? null : ($dataInfo->ContentSettings);
					$form['DisplaySettings'] = empty($dataInfo->DisplaySettings) ? null : ($dataInfo->DisplaySettings);
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("widget_item", $form);
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
					$form['IsDeleted'] = 'Y';
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['ID = ? '] = $id;
					$affRow += $this->db->update("widget_item", $form, $where);
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
