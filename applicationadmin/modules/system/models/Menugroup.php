<?php
class System_Model_Menugroup{
	
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
			echo $e->getMessage ();
		}
	}
	public function getOption() {
		$select = $this->db->select()
		->from(array('m' => 'menugroup_info'), array("ID", "Name"))
		->where("m.IsDeleted = ?", "N")
		->where("m.IsEnable = ?", "Y")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch) {
		// echo 'test';
		$data = array ();
		try {
			
			// set query
			$select = $this->db->select ()
			->from ( array ('tbl' => 'menugroup_info' ), '')
			->columns(array(
					"ID",
					"Name",
					"Created",
					"CreatedBy",
					"Modified",
					"ModifiedBy",
					"IsEnable",
					"IsDeleted"
			))
			->where ( "tbl.IsDeleted = 'N'" )
			;
			
			// set criteria
			if (! empty ( $advancesearch ['searchText'] )) {
				$val = $advancesearch ['searchText'];
				$select = $select->where ( "tbl.Name like " . $this->db->quote ( "%" . $val . "%" ) );
			}
			
			if (! empty ( $advancesearch ['isEnable'] )) {
				$select = $select->where ( "tbl.IsEnable = ? ", $advancesearch ['isEnable'] );
			}
			
			// find total
			$records = $this->getListCount ( $select );
			
			// set ordering and total
			if (empty ( $sidx )) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select ()->from ( array (
					'tbl' => $select 
			), array ('*' ) )->columns ( array (
					"RECORD" => "abs($records)" 
			) )
			->limitPage ( $page, $rows )
			;
			if (in_array($rowSort, array('Name'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
						
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
			
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
		// print_r($data);
		return $data;
	}
	function getListCount($select) {
		$select = $this->db->select ()->from ( array (
				'T' => $select 
		), array (
				"RECORDS" => "count(T.ID)" 
		) );
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchObject ();
		return $data->RECORDS;
	}
	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()->distinct ()
			->from (array ('tbl' => 'menugroup_info' ), array (
					"ID",
					"Name",
					"IsDeleted",
					"IsEnable"
			))
			->where ( "tbl.IsDeleted = 'N' " )
			->where ( "tbl.ID = ? ", $id );
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
			
		} catch ( Exception $e ) {
		}
		return $data;
	}

	function save($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$form = array ();
			
			$form ['Name'] = $params['txtTitle'];
			$form ['Created'] = date ( 'Y-m-d H:i:s' );
			$form ['CreatedBy'] = $this->UserID;
			$form ['IsEnable'] = empty ( $params ['is_enable'] ) ? 'N' : $params ['is_enable'];
			$form ['IsDeleted'] = 'N';
			$result = $this->db->insert ( "menugroup_info", $form );
			$id = $this->db->lastInsertId ();
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
			}
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	function update($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$form = array ();
			
			$form ['Name'] = $params['txtTitle'];
			$form ['IsEnable'] = $params ['is_enable'];
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$result = $where ["ID = ?"] = $params ['hdfID'];
			$this->db->update ( "menugroup_info", $form, $where );
			
			$id = $params ["hdfID"];
			$data->result = $result > 0;
			$data->id = $id;
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
			}
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	function delete($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$id = $params ['id'];
			
			// User
			$form = array ();
			$where = array ();
			$form ['IsDeleted'] = 'Y';
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$where ['ID = ? '] = $id;
			$result = $this->db->update ("menugroup_info", $form, $where );
			
			$data->result = count ( $result ) > 0;
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
}

?>
