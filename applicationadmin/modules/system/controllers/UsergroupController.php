<?php

class System_UsergroupController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	
	public function init()
	{ 
		
	}
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}

		// Check Action Rights
		$canView = $sUser->isAllow('setting.usergroup.manage');
		$canAdd = $sUser->isAllow('setting.usergroup.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "SystemUserGroupSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "SystemUserGroupSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "SystemUserGroupSearch" );
		}
		
		// Send Data to View
		$this->view->statusOption = Emwork_Utility::addEmptyOption($this->statusOption);
		
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการกลุ่มผู้ใช้งาน";
		$this->view->pageIcon= "fa fa-user";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการกลุ่มผู้ใช้งาน')
		);
	}
	
	public function getlistAction() {
		
		$filterInt = new Zend_Filter_Int();
		$canView = true;
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$canView = false;
		}
		if ($sUser->getInfo()->UserType != "B") {
			$canView = false;
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('setting.usergroup.view');
		
		// Init Return Value
		$responce = array();
		$responce['draw'] = 0;
		$responce['recordsTotal'] = 0;
		$responce['recordsFiltered'] = 0;
		$responce['data'] = array();
		
		// Init
		$_draw = $this->getRequest()->getParam('draw');
		$_start = $this->getRequest()->getParam('start'); // Rowindex
		$_length = $this->getRequest()->getParam('length'); // Page Size
		$_order = $this->getRequest()->getParam('order'); // Ordering
		$_columns = $this->getRequest()->getParam('columns'); // Columns
		$orderIndex = $_order[0]['column']; // Find Ordering Index
		$sidx = $_columns[$orderIndex]['data']; // Find Ordering Column
		$sord = $_order[0]['dir']; // Find Ordering Direction
		$rows = $_length;	// Assign to Page Size (Row)
		$page = ($_start / $rows) + 1; // Assign to Page Index
		
		// Basic Search
		$textstr = $this->getRequest()->getParam('textsearch');
		$textstr = $textstr;
		$filter = ''; // $this->getRequest()->getParam('filter');
		
		//Advance Search
		$advancesearch['searchText'] = $textstr;
		$advancesearch['enable'] = $this->getRequest()->getParam('enable');
		
		$searchNamespace = new Zend_Session_Namespace("SystemUserGroupSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->isEnable = $advancesearch['enable'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];
		
		$model = new System_Model_Usergroup();
		$data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
		
		$i=$_start;
		$responce['draw'] = $_draw;
		$responce['recordsTotal'] = 0;
		$responce['recordsFiltered'] = 0;
		$responce['data'] = array();
		if ($canView && $data) {
			$responce['recordsTotal'] = $data[0]["RECORD"];
			$responce['recordsFiltered'] = $data[0]["RECORD"];
			
			foreach($data as $item){
				$i++;
				$d = array();
				$d['DT_RowId'] = $item["ID"];
				$d['NO'] = $i;
				$d['NameAbbr'] = Emwork_Utility::notShowNull($item["NameAbbr"]);
				$d['Name'] = Emwork_Utility::notShowNull($item["Name"]);
				$d['IsEnable'] = Emwork_Utility::notShowNull($this->statusOption[$item["IsEnable"]]);
				
				$responce['data'][] = $d;
			}
		}
		
		$this->_helper->json($responce);
	}


	public function addAction()
	{
		// Log Access
		System_Model_Log::access();
	
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
	
		// Check Action Rights
		$canAdd = $sUser->isAllow('setting.usergroup.manage');
	
		if (!$canAdd) {
			$this->_helper->redirector('index', 'usergroup', 'system');
		}
	
		// Send Data To View
		$this->view->statusOption = $this->statusOption;
	
		// Set View
		$this->view->pageName = "เพิ่มกลุ่มผู้ใช้งาน";
		$this->view->pageIcon= "fa fa-users";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการกลุ่มผู้ใช้งาน', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'usergroup', 'action' => 'index'), null, true)),
				array('name' => 'เพิ่มกลุ่มผู้ใช้งาน')
		);
	}
	
	public function saveAction() {
	
		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.usergroup.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
	
		// Param
		$params = $this->getRequest()->getParams();
	
		// Model
		$model = new System_Model_Usergroup();	
		$data = $model->save($params);
	
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
	
		$this->_helper->json($result);
	}
	
	public function editAction()
	{
		// Log Access
		System_Model_Log::access();
			
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
	
		// Check Action Rights
		$canDelete = $sUser->isAllow('setting.usergroup.manage');
		$canEdit = $sUser->isAllow('setting.usergroup.manage');
		$canView = $sUser->isAllow('setting.usergroup.view');
	
		if (!$canView) {
			$this->_helper->redirector('index', 'usergroup', 'system');
		}
	
		// Get Param
		$params = $this->getRequest()->getParams();
		$itemID = $params['itemid'];
	
		if (!$itemID) {
			$this->_helper->redirector('index', 'usergroup', 'system');
		}
	
		// Get Data
		$model = new System_Model_Usergroup();
		$data = $model->getInfo($itemID);
	
		if ($data->is_deleted == 'Y') {
			$this->_helper->redirector('index', 'usergroup', 'system');
		}
	
		// Send Data to View
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;
		$this->view->data = $data;
		$this->view->statusOption= $this->statusOption;
	
		// Set View
		$this->view->pageName = "ปรับปรุงกลุ่มผู้ใช้งาน";
		$this->view->pageIcon= "fa fa-users";
		if ($canEdit) {
			$this->view->subMenus = array(
					array('name' => 'ปรับปรุงกลุ่มผู้ใช้งาน', 'active' => true),
					array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'usergroup', 'action' => 'rights', 'itemid' => $data->ID), null, true)),
			);
		} else {
			$this->view->subMenus = array();
		}
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการกลุ่มผู้ใช้งาน', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'usergroup', 'action' => 'index'), null, true)),
				array('name' => 'ปรับปรุงกลุ่มผู้ใช้งาน')
		);
	}
	
	public function updateAction()
	{
		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.usergroup.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
	
		// Param
		$params = $this->getRequest()->getParams();
	
		// Save & Update
		$model = new System_Model_Usergroup();
		$data = $model->update($params);
	
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
	
		$this->_helper->json($result);
	}
	
	/**
	 * Ajax Action: delete
	 */
	public function deleteAction()
	{
		// Init
		$result = array();
		$result['result'] = false;
		$result['message'] = '';
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.usergroup.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
	
		// Param
		$params = $this->getRequest()->getParams();
	
		// Update
		$model = new System_Model_Usergroup();
		$data = $model->delete($params);
	
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
		} else {
			$result['result'] = false;
			$result['message'] = 'ลบไม่สำเร็จ';
		}
	
		$this->_helper->json($result);
	}
	
	public function rightsAction()
	{
		// Log Access
		System_Model_Log::access();
	
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
	
		// Check Action Rights
		$canEdit = $sUser->isAllow('setting.usergroup.manage');
	
		if (!$canEdit) {
			$this->_helper->redirector('index', 'usergroup', 'system');
		}
	
		// Get Param
		$itemID = $this->_request->getParam('itemid');
	
		// Model
		$model = new System_Model_Usergroup();
		$data = $model->getInfo($itemID);
		
		if (empty($data->ID)) {
			$this->_helper->redirector('index', 'usergroup', 'system');
		}
		if ($data->IsDeleted == 'Y') {
			$this->_helper->redirector('index', 'usergroup', 'system');
		}
	
		$modelAction = new System_Model_Action();
	
		// Get Data
		$usergroupActionList = System_Model_Usergroup::getActions($itemID);
		$actionFullList = $modelAction->getFullList();
	
		$usergroupActions = array();
		if (count($usergroupActionList)) {
			foreach ($usergroupActionList as $item)
				$usergroupActions[] = $item['ActionID'];
		}
	
		$actions = array();
		$actionRecursives = array();
		if (count($actionFullList)) {
			foreach ($actionFullList as $item) {
				// Normal
				if (isset($actions[$item['ActionGroupID']])) {
					$actions[$item['ActionGroupID']]['Action'][] = array('ID' => $item['ActionID'], 'Name' => $item['ActionName']);
				} else {
					$actions[$item['ActionGroupID']] = array();
					$actions[$item['ActionGroupID']]['ID'] = $item['ActionGroupID'];
					$actions[$item['ActionGroupID']]['Name'] = $item['ActionGroupName'];
					$actions[$item['ActionGroupID']]['Action'] = array();
					$actions[$item['ActionGroupID']]['Action'][] = array('ID' => $item['ActionID'], 'Name' => $item['ActionName']);
				}
				
				// Recursive
				$parentID = empty($item["ActionParentID"]) ? "main" : $item["ActionParentID"];
				if (isset($actionRecursives[$item['ActionGroupID']])) {
					if (!isset($actionRecursives[$item['ActionGroupID']]['Action'][$parentID])) {
						$actionRecursives[$item['ActionGroupID']]['Action'][$parentID] = array();
					}
				} else {
					$actionRecursives[$item['ActionGroupID']] = array();
					$actionRecursives[$item['ActionGroupID']]['ID'] = $item['ActionGroupID'];
					$actionRecursives[$item['ActionGroupID']]['Name'] = $item['ActionGroupName'];
					$actionRecursives[$item['ActionGroupID']]['Action'] = array();
					$actionRecursives[$item['ActionGroupID']]['Action'][$parentID] = array();
				}
				$action = array('ID' => $item['ActionID'], 'Name' => $item['ActionName']);
				$actionRecursives[$item['ActionGroupID']]['Action'][$parentID][] = $action;
			}
		}
	
		// Send Data to View
		$this->view->id = $itemID;
		$this->view->usergroupActions = $usergroupActions;
		$this->view->actions = $actions;
		$this->view->actionRecursives = $actionRecursives;
	
		// Set View
		$this->view->pageName = "กำหนดสิทธิ์";
		$this->view->pageIcon= "fa fa-cog";
		$this->view->subMenus = array(
				array('name' => 'ปรับปรุงกลุ่มผู้ใช้งาน', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'usergroup', 'action' => 'edit', 'itemid' => $itemID), null, true)),
				array('name' => 'กำหนดสิทธิ์', 'active' => true)
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการกลุ่มผู้ใช้งาน', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'usergroup', 'action' => 'index'), null, true)),
				array('name' => 'กำหนดสิทธิ์')
		);
	}
	
	public function rightssaveAction() {
	
		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.usergroup.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model
		$model = new System_Model_Usergroup();
	
		// Param
		$params = $this->getRequest()->getParams();
	
		// Check Param
		// Check ID
		if (empty($params['hdfID'])) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
	
		// Save
		$data = $model->updateRights($params);
	
		// Set Result
		if ($data->result) {
	
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
		} else {
			$result['result'] = false;
			//$result['message'] = 'บันทึกไม่สำเร็จ';
			$result['message'] = $data->message;
		}
	
		$this->_helper->json($result);
	}
	
	public function rightssaveactionAction() {
		
		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.usergroup.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model
		$model = new System_Model_Usergroup();
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// Check Param
		// Check ID
		if (empty($params['hdfID'])) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Save
		$data = $model->updateActions($params);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
		} else {
			$result['result'] = false;
			$result['message'] = $data->message;
		}
		
		$this->_helper->json($result);
	}
}

?>