<?php
class System_Model_Menu {
	
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
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch) {
		
		$data = array ();
		try {
			
			// set query
			$select = $this->db->select ()
			->from ( array ('m' => 'menu_info'), "" )
			->joinInner( array ('d' => 'menu_detail' ), " d.ID = m.ID ", array ('' ))
			->joinInner( array ('g' => 'menugroup_info' ), " g.ID = m.MenuGroupID ", array ('' ))
			->columns(array(
					"ID" => "m.ID",
					"MenuName"  => "d.Name",
					"MenuGroupName"  => "g.Name",
					"Ordering" => "m.Ordering",
					"m.IsHome",
					"IsEnable" => "m.IsEnable",
			) )
			->where("m.IsDeleted = ?", "N" )
			->where("d.Lang = 'th' ");
			
			// set criteria
			if (! empty ( $advancesearch ['searchText'] )) {
				$val = $advancesearch ['searchText'];
				$select = $select->where ( "d.Name like " . $this->db->quote ( "%" . $val ."%" ) );
			}
			
			if (! empty ( $advancesearch ['isEnable'] )) {
				$select = $select->where ( "m.IsEnable = ? ", $advancesearch ['isEnable'] );
			}
			if (! empty ( $advancesearch ['menuGroupID'] )) {
				$select = $select->where("m.MenuGroupID = ? ", $advancesearch['menuGroupID']);
			}
			// find total
			$records = $this->getListCount ( $select );
			
			// set ordering and total
			if (empty ( $sidx ))
				$sidx = "ID";
			$rowSort = $sidx;
			
			if (empty($sord)) {
				$sord = 'asc';
			}
			
			$select = $this->db->select ()->from ( array ('m' => $select ), array ('*' ) )
			->columns ( array ("RECORD" => "abs($records)" ) )
// 			->order ( "$rowSort $sord" )
			->limitPage ( $page, $rows )
			;
			if (in_array($rowSort, array('MenuName', 'MenuGroupName'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
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
			
			$select = $this->db->select ()->from ( array ('m' => 'menu_info'), array (
					"ID",
					"MenuGroupID",
					"ParentID",
					"Url",
					"IsHome",
					"Ordering",
					"IsDeleted",
					"IsEnable",
					"Type",
					"FrontModule",
					"FrontController",
					"Params"
			) )
			->where ("m.IsDeleted = 'N' " )
			->where ( "m.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
		}
		return $data;
	}
	function getDetails($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()->from ( array ('d' => 'menu_detail'), array (
					"ID",
					"Name",
					"IsEnable",
					"Lang" 
			) )
			->where ( "d.IsDeleted = 'N'" )
			->where ( "d.ID = ? ", $id );
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	
	
	public function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'menu_info'), "")
			->joinLeft(array("md" => "menu_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "m.ID",
					"Lang" => 'md.Lang',
					"Name" => "md.Name",
					"m.IsHome",
					"MenuGroupID" => "m.MenuGroupID",
					"ParentID" => "m.ParentID",
					"Url" => "m.Url",
					"m.Type",
					"m.Params",
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
	
	function save($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			
			$param = $params['params'];
			$id = '';
			$modified = date("Y-m-d H:i:s");
			
			$form = array ();			
			$form['MenuGroupID'] = $param[$defaultLanguageCode]['MenuGroup'];
			$form['ParentID'] = empty($param[$defaultLanguageCode]['Parent']) ? null : $param[$defaultLanguageCode]['Parent'];
			$form['ParentID'] = empty($param[$defaultLanguageCode]['Parent']) ? null : $param[$defaultLanguageCode]['Parent'];
			$form['Ordering'] = empty ( $param[$defaultLanguageCode]['Ordering'] ) ? 0 : $param[$defaultLanguageCode]['Ordering'];
			$form['IsHome'] = empty ( $param[$defaultLanguageCode]['IsHome'] ) ? 'N' : $param[$defaultLanguageCode]['IsHome'];
			
			$form['Type'] = empty($params["rdlMenuType"]) ? "component" : $params["rdlMenuType"];
			
			if ($form['Type'] == 'component') { 
				$form["FrontModule"] = $params["mConfig"];
				$form["FrontController"] = $params["cConfig"];
				$form['Params'] = null;
				if (count($params["mparams"]) > 0) {
					$form['Params'] = json_encode($params["mparams"]);
				}
				$form['Url'] = $param[$defaultLanguageCode]['Url'];
			} elseif ($form['Type'] == 'url') {
				$form['Url'] = $params["txtExternelUrl"];
				$menuSetting = new stdClass();
				$menuSetting->Taget = empty($params["lstExternelUrlTarget"]) ? "_blank" : $params["lstExternelUrlTarget"];
				$form['Params'] = json_encode($menuSetting);
			} else {
				// Do nothing
			}
			
			$form['Created'] = $modified;
			$form['CreatedBy'] = $this->UserID;
			$form['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert ( "menu_info", $form );
			$id = $this->db->lastInsertId ();
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
				// Clear Home
				if ($form ['IsHome'] == "Y") {
					$formHome = array();
					$whereHome = array();
					$formHome["IsHome"] = 'N';
					$whereHome["ID <> ? "] = $id;
					$this->db->update("menu_info", $formHome, $whereHome);
				}
				
				// Detail
				$i = 0;
				if(count($params['params'])>0){
					foreach ($params['params'] as $p){
						if ($i < count($params['params'])){
							$formDetail = array ();
							$formDetail ['ID'] = $id;
							$formDetail ['Lang'] = array_keys($params['params'])[$i];
							$formDetail ['Name'] = empty ( $p ['Name'] ) ? null : $p['Name'];
							$formDetail ['Created'] = $modified;
							$formDetail ['CreatedBy'] = $this->UserID;
							$formDetail ['IsEnable'] = 'Y';
							$formDetail ['IsDeleted'] = 'N';
							$this->db->insert ( "menu_detail", $formDetail );
							$i ++;
						}
					}
				}
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
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			
			$param = $params['params'];
			
			$id = $params["hdfID"];
			$modified = date("Y-m-d H:i:s");
			
			$form = array ();
			$where = array();
			$form['MenuGroupID'] = $param[$defaultLanguageCode]['MenuGroup'];
			$form['ParentID'] = empty($param[$defaultLanguageCode]['Parent']) ? null : $param[$defaultLanguageCode]['Parent'];
			$form['Ordering'] = $param[$defaultLanguageCode]['Ordering'];
			$form ['IsHome'] = empty ( $param[$defaultLanguageCode]['IsHome'] ) ? 'N' : $param[$defaultLanguageCode]['IsHome'];
			
			$form['Type'] = empty($params["rdlMenuType"]) ? "component" : $params["rdlMenuType"];
			
			if ($form['Type'] == 'component') { 
				$form["FrontModule"] = $params["mConfig"];
				$form["FrontController"] = $params["cConfig"];
				$form['Params'] = null;
				if (count($params["mparams"]) > 0) {
					$form['Params'] = json_encode($params["mparams"]);
				}
				$form['Url'] = $param[$defaultLanguageCode]['Url'];
			} elseif ($form['Type'] == 'url') {
				$form['FrontModule'] = null;
				$form['FrontController'] = null;
				$form['Url'] = $params["txtExternelUrl"];
				$menuSetting = new stdClass();
				$menuSetting->Target = empty($params["lstExternelUrlTarget"]) ? "_blank" : $params["lstExternelUrlTarget"];
				$form['Params'] = json_encode($menuSetting);
			} else {
				// Do nothing
				$form['FrontModule'] = null;
				$form['FrontController'] = null;
				$form['Params'] = null;
				$form['Url'] = null;
			}
			
			$form['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where["ID = ?"] = $id;
			$result = $this->db->update( "menu_info", $form, $where );
			
			$data->result = $result > 0;
			$data->id = $id;
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
				// Clear Home
				if ($form ['IsHome'] == "Y") {
					$formHome = array();
					$whereHome = array();
					$formHome["IsHome"] = 'N';
					$whereHome["ID <> ? "] = $id;
					$this->db->update("menu_info", $formHome, $whereHome);
				}
				
				if (count($param) > 0) {
					foreach ($param as $lang => $detail) {
						// MainInfoDetail
						$formDetail = array();
						$whereDetail = array();
						$formDetail['Name'] = $detail['Name'];
						$formDetail['Modified'] = $modified;
						$whereDetail["ID = ?"] = $id;
						$whereDetail["Lang = ?"] = $lang;
						$affectedRows = $this->db->update("menu_detail", $formDetail, $whereDetail);
						if ($affectedRows == 0) {
							$formDetail["ID"] = $id;
							$formDetail["Lang"] = $lang;
							$formDetail['Created'] = $modified;
							$formDetail["IsEnable"] = 'Y';
							$formDetail["IsDeleted"] = 'N';
							$this->db->insert("menu_detail", $formDetail);
						}
					}
				}
				
				// Update Menugroup to chlidren
				$this->updateToChildren($id, $param[$defaultLanguageCode]['MenuGroup']);
			}
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	private function updateToChildren($id, $menuGroupID) {
		
		$select = $this->db->select()
		->from(array('tbl' => 'menu_info'), array( 'ID'))
		->where("tbl.ParentID = ?", $id)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		
		if (count($data) > 0) {
			foreach ($data as $item) {
				$form = array();
				$where = array();
				$form['MenuGroupID'] = $menuGroupID;
				$where["ID = ?"] = $item['ID'];
				$this->db->update( "menu_info", $form, $where );
				
				$this->updateToChildren($item['ID'], $menuGroupID);
			}
		}		
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
			$result = $this->db->update("menu_info", $form, $where);
			
			$data->result = count ( $result ) > 0;
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	public function getRecursiveOption($lang, $menuGroupID) {
		$result = array();
		
		if (empty($menuGroupID)) {
			return $result;
		}
		
		$select = $this->db->select()
		->from(array('tbl' => 'menu_info'), array( "ID"))
		->joinInner(array('d' => 'menu_detail'), "tbl.ID = d.ID", array("Name"))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ParentID is null")
		->where("d.Lang = ?", 'th')
		->order("tbl.Ordering ASC")
		->order("convert(d.Name USING TIS620) ASC")
		;
		if(!empty($menuGroupID)){
			$select = $select->where("tbl.MenuGroupID = ? ",$menuGroupID);
		}
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
			$this->recursive($result, $value["ID"], 1);
		}
		return $result;
	}
	private function recursive(&$menus, $parentID, $level) {
		$select = $this->db->select()
		->from(array('tbl' => 'menu_info'), array( "ID"))
		->joinInner(array('d' => 'menu_detail'), "tbl.ID = d.ID", array("Name"))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("d.Lang = ?", 'th')
		->where("tbl.ParentID = ? ", $parentID)
		->order("tbl.Ordering ASC")
		->order("convert(d.Name USING TIS620) ASC")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		
		if (count($data) > 0) {
			foreach ($data as $value) {
				$menus[$value["ID"]] = str_repeat("- - ", $level) . $value["Name"];
				$this->recursive($menus, $value["ID"], $level + 1);
			}
		}
	}
	
	public function isChild($id, $checkID) {
		$result = false;
		
		if ($id == $checkID) {
			return true;
		}

		$select = $this->db->select()
		->from(array('tbl' => 'menu_info'), array( "ID"))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->where("tbl.ParentID = ? ", $id)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		
		if (count($data) > 0) {
			foreach ($data as $value) {
				if ($value['ID'] == $checkID) {
					$result = true;
				} else {
					$result = $this->isChild($value['ID'], $checkID);
				}
			}
		}
		
		return $result;
	}
	
	function getComponentList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch) {
		
		$data = array();
		try {
			// set query
			$select = $this->db->select()
			->from(array('tbl' => 'sys_element'), array("ID", "Name", "Type", "RefTypeName"))
			->where("tbl.IsDeleted = 'N'")
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.Type = ? ", "component")
			;
			
			// set criteria
			if (!empty($advancesearch['searchText'])) {
				$select = $select->where("(tbl.Name like " . $this->db->quote("%" . $advancesearch['searchText'] . "%") . ") ");
			}
			
			// find total
			$records = $this->getComponentListCount($select);
			
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
	
	function getComponentListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
}

?>
