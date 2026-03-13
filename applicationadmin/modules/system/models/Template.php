<?php
class System_Model_Template{
	
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
		// echo 'test';
		$data = array ();
		try {
			
			// set query
			$select = $this->db->select ()
			->from ( array ('tbl' => 'sys_template' ), '')
			->columns(array(
					"tbl.ID",
					"tbl.Name",
					"tbl.IsDefault",
					"tbl.Created",
					"tbl.CreatedBy",
					"tbl.Modified",
					"tbl.ModifiedBy",
					"tbl.IsEnable",
					"tbl.IsDeleted"
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
			
			$select = $this->db->select ()
			->from (array ('tbl' => $select), array ('*' ) )
			->columns ( array ("RECORD" => "abs($records)"))
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

	function getInfoByID($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from ( array ('t' => 'sys_template'), "" )
			->joinLeft( array ('p1' => 'file_info' ), " t.FaviconImageID = p1.ID ", array ('' ))
			->joinLeft( array ('p2' => 'file_info' ), " t.LogoImageID = p2.ID ", array ('' ))
			->joinLeft( array ('p3' => 'file_info' ), " t.BackgroundImageID = p3.ID", array ('' ))
			->columns(array(
					't.ID',
					't.MenuGroupID',
					't.DisplayMenuGroupType',
					't.Name',
					"t.ShowFontSize",
					"t.ShowVisitAmount",
					"t.IsDefault",
					'FaviconImageName'=>'p1.Name',
					'FaviconImageFileSize'=>'p1.FileSize',
					'FaviconImageFileType'=>'p1.FileType',
					'FaviconImageHashName'=>'p1.HashName',
					
					'LogoImageName'=>'p2.Name',
					'LogoImageFileSize'=>'p2.FileSize',
					'LogoImageFileType'=>'p2.FileType',
					'LogoImageHashName'=>'p2.HashName',
					
					't.BackgroundImageID',
					't.BackgroundRepeat',
					't.BackgroundSize',
					't.BackgroundAttachment',
					't.BackgroundPosition',
					't.BackgroundColor',
					'BackgroundImageName'=>'p3.name',
					'BackgroundImageFileSize'=>'p3.FileSize',
					'BackgroundImageFileType'=>'p3.FileType',
					'BackgroundImageHashName'=>'p3.HashName'
			) )
			->where ( "t.ID = ? ", $id );
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
		}
		return $data;
	}
	
	function getCoding ($id) {
		$data = new stdClass();
		try {
			
				$select = $this->db->select()
				->from( array('t' => 'sys_template' ), "" )
				->columns( array( 	
					'ID',
					'IsUseGoogleAnalytic',
					'GoogleAnanlyticCode',
				    'GoogleAnanlyticScript',
					'IsUseTruehit',
					'TruehitCode',
				    'TruehitScript',
					'IsUseCustomCss',
					'CustomCss',
					'IsUseCustomJs',
					'CustomJs'
				))
				->where ( "t.ID = ? ", $id );
				
				$stmt = $this->db->query( $select );
				$data = $stmt->fetchObject();
		} catch (Exception $e) {			
			
		}
		return $data;
	}
	
	function getLayout($id) {
		$data = new stdClass();
		try {
			
			$select = $this->db->select()
			->from( array('t' => 'sys_template' ), "" )
			->columns( array(
					'ID',
					'LayoutParams'
			))
			->where ( "t.ID = ? ", $id );
			
			$stmt = $this->db->query( $select );
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getTheme($id) {
		$data = new stdClass();
		try {
			
			$select = $this->db->select()
			->from( array('t' => 'sys_template' ), "" )
			->columns( array(
					'ID',
					'ThemeParams'
			))
			->where ( "t.ID = ? ", $id );
			
			$stmt = $this->db->query( $select );
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getMenus ($id) {
		$data = array();
		try {
			// set query
			$select = $this->db->select()
			->from(array('tbl' => 'menu_info'), array("ID", "TemplateID"))
			->where("tbl.TemplateID = ? ", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		
		return $data;
	}
	
	function save($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$form = array ();
			
			$form ['Name'] = $params['name'];
			$form ['MenuGroupID'] = $params['groupMenu'];
			$form ['DisplayMenuGroupType'] = $params['displayMenuGroup'];
			$form ['FaviconImageID'] = $params ['faviconId'];
			$form ['LogoImageID'] = $params ['logoId'];
			$form ['BackgroundImageID'] = $params ['bgId'];
			$form ['BackgroundRepeat'] = $params ['bgRepeat'];
			$form ['BackgroundSize'] = $params ['bgSize'];
			$form ['BackgroundAttachment'] = $params ['bgAttachment'];
			$form ['BackgroundPosition'] = $params ['bgPosition'];
			$form ['BackgroundColor'] = $params ['bgColor'];
			$form ['ShowFontSize'] = empty( $params ['showFont'] ) ? 'N' : $params ['showFont'];
			$form ['ShowVisitAmount'] = empty( $params ['showVisit'] ) ? 'N' : $params ['showVisit'];
			$form ['IsDefault'] = empty( $params ['isDefault'] ) ? 'N' : $params ['isDefault'];
			
			$form ['Created'] = date ('Y-m-d H:i:s' );
			$form ['CreatedBy'] = $this->UserID;
			$form ['IsEnable'] = 'Y';
			$form ['IsDeleted'] = 'N';
			$result = $this->db->insert ( "sys_template", $form );
			$id = $this->db->lastInsertId ();
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
				if ($form ['IsDefault'] == 'Y') {
					$formDefault = array();
					$whereDefault = array();
					$formDefault["IsDefault"] = 'N';
					$whereDefault["ID <> ? "] = $id;
					$this->db->update( "sys_template", $formDefault, $whereDefault);
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
			
			$id = $params['hdfID'];
			
			$form = array ();
			
			$form ['Name'] = $params['name'];
			$form ['MenuGroupID'] = $params['groupMenu'];
			$form ['DisplayMenuGroupType'] = $params['displayMenuGroup'];
			$form ['FaviconImageID'] = $params ['faviconId'];
			$form ['LogoImageID'] = $params ['logoId'];
			$form ['BackgroundImageID'] = $params ['bgId'];
			$form ['BackgroundRepeat'] = $params ['bgRepeat'];
			$form ['BackgroundSize'] = $params ['bgSize'];
			$form ['BackgroundAttachment'] = $params ['bgAttachment'];
			$form ['BackgroundPosition'] = $params ['bgPosition'];
			$form ['BackgroundColor'] = $params ['bgColor'];
			$form ['ShowFontSize'] = empty( $params ['showFont'] ) ? 'N' : $params ['showFont'];
			$form ['ShowVisitAmount'] = empty( $params ['showVisit'] ) ? 'N' : $params ['showVisit'];
			$form ['IsDefault'] = empty( $params ['isDefault'] ) ? 'N' : $params ['isDefault'];
			
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$where['id = ?'] = $id;
			$result = $this->db->update( "sys_template", $form, $where);
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
				if ($form ['IsDefault'] == 'Y') {
					$formDefault = array();
					$whereDefault = array();
					$formDefault["IsDefault"] = 'N';
					$whereDefault["ID <> ? "] = $id;
					$this->db->update( "sys_template", $formDefault, $whereDefault);
				}
			}
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
// 	-----------------------------------------------------------------------------------------------------------------------

	function updateCoding($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$id = $params['hdfID'];
			$form = array ();
			
			$form ['IsUseGoogleAnalytic'] = empty( $params ['is_use_google_analytic'] ) ? 'N' : $params ['is_use_google_analytic'];
			$form ['GoogleAnanlyticScript'] = $params ['google_ananlytic_script'];
			$form ['IsUseTruehit'] = empty( $params ['is_use_truehit'] ) ? 'N' : $params ['is_use_truehit'];
			$form ['TruehitScript'] = $params ['truehit_script'];
			$form ['IsUseCustomCss'] = empty( $params ['is_use_custom_css'] ) ? 'N' : $params ['is_use_custom_css'];
			$form ['CustomCss'] = $params ['custom_css'];
			$form ['IsUseCustomJs'] = empty( $params ['is_use_custom_js'] ) ? 'N' : $params ['is_use_custom_js'];
			$form ['CustomJs'] = $params ['custom_js'];
			
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$where['ID = ?'] = $id;
			$result = $this->db->update( "sys_template", $form, $where);
			
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
	
	// 	-----------------------------------------------------------------------------------------------------------------------	
	function updateLayout($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$id = $params['hdfID'];
			$form = array ();
			
			$sections = $params['section'];
			if (is_array($sections)) {
				$sections = json_encode($sections);
			}
			
			$form ['LayoutParams'] = $sections;
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$where['ID = ?'] = $id;
			$result = $this->db->update( "sys_template", $form, $where);
			
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
	
	// 	-----------------------------------------------------------------------------------------------------------------------
	function updateTheme($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$id = $params['hdfID'];
			$form = array ();
			
			$themeParams = new stdClass();
			$themeParams->themeSelected = $params['themeid'];
			$themeParams->themes = array();
			
			if (count($params["theme"]) > 0) {
				foreach ($params["theme"] as $key => $theme) {
					$themeParams->themes[$key] = array(
							"bgColor" => $theme["bgColor"],
							"textColor" => $theme["textColor"],
							"linkColor" => $theme["linkColor"],
							"headerColor" => $theme["headerColor"],
							"footerColor" => $theme["footerColor"]
					);
				}
			}
			
			$form ['ThemeParams'] = json_encode($themeParams);
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$where['ID = ?'] = $id;
			$result = $this->db->update( "sys_template", $form, $where);
			
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
	
	// 	-----------------------------------------------------------------------------------------------------------------------
	function updateAssignMenu($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$id = $params['hdfID'];
			
			// Clear Template in menu
			$form = array();
			$where = array();
			$form["TemplateID"] = '0';
			$where["TemplateID = ? "] = $id;
			$this->db->update("menu_info", $form, $where);
			
			// Save menu
			if (count($params["MenuSelected"]) > 0) {
				foreach ($params["MenuSelected"] as $menuID) {
					$form = array();
					$where = array();
					$form["TemplateID"] = $id;
					$where["ID = ?"] = $menuID;
					$this->db->update("menu_info", $form, $where);
				}
			}
			
			$data->result = true;
			$data->id = $id;
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
}

?>
