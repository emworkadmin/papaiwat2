<?php
class System_MenugroupController extends Zend_Controller_Action {
	protected $publishOption = array (
			'Y' => 'เผยแพร่',
			'N' => 'ไม่เผยแพร่' 
	);
	public function init() {
	}
	public function indexAction() {
		// Log Access
		System_Model_Log::access ();
		
		// / Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow("setting.menugroup.manage");
		$canAdd = $sUser->isAllow("setting.menugroup.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "SystemMenuGroupSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "SystemMenuGroupSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "SystemMenuGroupSearch" );
		}
		
		// Get Option
		
		// Send To View
		$this->view->publishOption = Emwork_Utility::addEmptyOption ( $this->publishOption );
		
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการกลุ่มเมนู";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array (
						'name' => 'หน้าแรก',
						'url' => $this->view->url ( array (
								'module' => 'default',
								'controller' => 'index',
								'action' => 'index' 
						), null, true ) 
				),
				array (
						'name' => 'รายการกลุ่มเมนู' 
				) 
		);
	}
	public function getlistAction() {
		$filterInt = new Zend_Filter_Int ();
		$canView = true;
		
		// / Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$canView = false;
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$canView = false;
		}
		
		// Check Action Rights
		$canView = $canView && $sUser->isAllow("setting.menugroup.manage");
		
		// Init
		$_draw = $this->getRequest ()->getParam ( 'draw' );
		$_start = $this->getRequest ()->getParam ( 'start' ); // Rowindex
		$_length = $this->getRequest ()->getParam ( 'length' ); // Page Size
		$_order = $this->getRequest ()->getParam ( 'order' ); // Ordering
		$_columns = $this->getRequest ()->getParam ( 'columns' ); // Columns
		$orderIndex = $_order [0] ['column']; // Find Ordering Index
		$sidx = $_columns [$orderIndex] ['data']; // Find Ordering Column
		$sord = $_order [0] ['dir']; // Find Ordering Direction
		$rows = $_length; // Assign to Page Size (Row)
		$page = ($_start / $rows) + 1; // Assign to Page Index
		                               
		// Basic Search
		$textstr = $this->getRequest ()->getParam ( 'textstr' );
		$textstr = $textstr;
		$filter = ''; // $this->getRequest()->getParam('filter');
		              
		// Advance Search
		$advancesearch ['searchText'] = $textstr;
		$advancesearch ['isEnable'] = $this->getRequest ()->getParam ( 'isEnable' );
		
		$searchNamespace = new Zend_Session_Namespace ( "SystemMenuGroupSearch" );
		$searchNamespace->searchText = $advancesearch ['searchText'];
		$searchNamespace->isEnable = $advancesearch ['isEnable'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order [0] ['column'];
		$searchNamespace->tblOrderDir = $_order [0] ['dir'];
		
		$model = new System_Model_Menugroup ();
		$data = $model->getList ( $sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch );
		
		// print_r($data);
		// exit;
		$i = $_start;
		$responce = array ();
		$responce ['draw'] = $_draw;
		$responce ['recordsTotal'] = 0;
		$responce ['recordsFiltered'] = 0;
		$responce ['data'] = array ();
		if ($canView && $data) {
			$responce ['recordsTotal'] = $data [0] ["RECORD"];
			$responce ['recordsFiltered'] = $data [0] ["RECORD"];
			
			foreach ( $data as $item ) {
				$i ++;
				$d = array ();
				$d ['DT_RowId'] = $item ["ID"];
				$d ['NO'] = $i;
				
				$d ['Name'] = Emwork_Utility::notShowNull ( $item ["Name"] );
				$d ['IsEnable'] = Emwork_Utility::notShowNull ( $this->publishOption [$item ["IsEnable"]] );
				$responce ['data'] [] = $d;
			}
		}
		
		$this->_helper->json ( $responce );
	}
	public function addAction() {
		// Log Access
		System_Model_Log::access ();
		
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canAdd = $sUser->isAllow("setting.menugroup.manage");		
		if (! $canAdd) {
			$this->_helper->redirector ( 'index', 'menugroup', 'system' );
		}
		
		// Send To View		
		$this->view->publishOption = $this->publishOption;
		
		// Set View
		$this->view->pageName = "เพิ่มกลุ่มเมนู";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array (
						'name' => 'หน้าแรก',
						'url' => $this->view->url ( array (
								'module' => 'default',
								'controller' => 'index',
								'action' => 'index' 
						), null, true ) 
				),
				array (
						'name' => 'รายการกลุ่มเมนู',
						'url' => $this->view->url ( array (
								'module' => 'system',
								'controller' => 'menugroup',
								'action' => 'index' 
						), null, true ) 
				),
				array (
						'name' => 'เพิ่มกลุ่มเมนู' 
				) 
		);
	}
	public function saveAction() {
		// Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		// / Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result ['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json ( $result );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$result ['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json ( $result );
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow("setting.menugroup.manage");
		if (! $canSave) {
			$result ['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json ( $result );
		}
		
		$model = new System_Model_Menugroup ();
		$params = $this->getRequest ()->getParams ();
		$data = $model->save ( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}
	public function editAction() {
		// Log Access
		System_Model_Log::access ();
		
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Check Action Rights
		$canDelete = $sUser->isAllow("setting.menugroup.manage");
		$canEdit = $sUser->isAllow("setting.menugroup.manage");
		$canView = $sUser->isAllow("setting.menugroup.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Get Param
		$itemID = $this->_request->getParam ( 'itemid' );
		
		// Model
		if (! $itemID) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		$model = new System_Model_Menugroup ();
		$languageList = System_Model_Setting::getLanguages ();
		
		// Get Data
		$data = $model->getInfo ( $itemID );
		
		// Send To View
		$this->view->publishOption = $this->publishOption;
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;
		$this->view->data = $data;
		
		// Set View
		$this->view->pageName = "ปรับปรุงกลุ่มเมนู";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array (
						'name' => 'หน้าแรก',
						'url' => $this->view->url ( array (
								'module' => 'default',
								'controller' => 'index',
								'action' => 'index' 
						), null, true ) 
				),
				array (
						'name' => 'กลุ่มเมนู',
						'url' => $this->view->url ( array (
								'module' => 'system',
								'controller' => 'menugroup',
								'action' => 'index' 
						), null, true ) 
				),
				array (
						'name' => 'ปรับปรุงกลุ่มเมนู' 
				) 
		);
	}
	public function updateAction() {
		// Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		// / Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result ['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json ( $result );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$result ['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json ( $result );
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow("setting.menugroup.manage");
		if (! $canSave) {
			$result ['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json ( $result );
		}
		
		$model = new System_Model_Menugroup ();
		$params = $this->getRequest ()->getParams ();
		$data = $model->update ( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}
	public function deleteAction() {
		// Init
		$result = array ();
		$result ['result'] = false;
		$result ['message'] = '';
		
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result ['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json ( $result );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$result ['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json ( $result );
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow("setting.menugroup.manage");
		if (! $canSave) {
			$result ['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json ( $result );
		}
		
		// Param
		$params = $this->getRequest ()->getParams ();
		
		// Update
		$model = new System_Model_Menugroup ();
		$data = $model->delete ( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
		} else {
			$result ['result'] = false;
			$result ['message'] = 'ลบไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}
}

