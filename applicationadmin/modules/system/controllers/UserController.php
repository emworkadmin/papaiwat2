<?php
/**
 * EM D2MS
 *
 * @copyright  Copyright (c) 2018 EMWORK Group
 * @license	EMWORK Group
 * @version	1.0.0 applicationadmin/module/system/controllers/UserController.php 2018-02-20
 * @author	EMWORK Group
 */

class System_UserController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $titleNameOption = array('M' => 'นาย', 'R' => 'นาง', 'S' => 'นางสาว', 'O' => 'อื่นๆ');
	protected $accountExpireOption = array('N' => 'ไม่มีหมดอายุ', 'Y' => 'มีวันหมดอายุ');
	protected $yesNoOption = array('N' => 'ไม่', 'Y' => 'ใช่');
	protected $genderOption = array('M' => 'ชาย', 'F' => 'หญิง');
	
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
		$canView = $sUser->isAllow('setting.user.view');
		$canAdd = $sUser->isAllow('setting.user.manage');
	
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "SystemUserSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "SystemUserSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "SystemUserSearch" );
		}
		
		// Send Rights To View
		$this->view->canAdd = $canAdd;	
		
		// Send Data to View
		$this->view->statusOption = Emwork_Utility::addEmptyOption($this->statusOption);
		
		// Set View
		$this->view->pageName = "รายการผู้ใช้งาน";
		$this->view->pageIcon= "fa fa-user";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการผู้ใช้งาน')
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
		$canView = $sUser->isAllow('setting.user.view');
		
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
		$filter = ''; 
		
		//Advance Search
		$advancesearch['searchText'] = $textstr;
		$advancesearch['enable'] = $this->getRequest()->getParam('enable');
		
		$searchNamespace = new Zend_Session_Namespace("SystemUserSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->isEnable = $advancesearch['enable'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];
		
		$model = new System_Model_Users();
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
				$d['Email'] = Emwork_Utility::notShowNull($item["Email"]);
				$d['FullName'] = Emwork_Utility::notShowNull($item["FullName"]);
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
		$canAdd = $sUser->isAllow('setting.user.manage');
		
		if (!$canAdd) {
			$this->_helper->redirector('index', 'user', 'system');
		}
		
		$modelUsergroup = new System_Model_Usergroup();
		$usergroupOption = $modelUsergroup->getOption();
		
		// Send Data To View
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->genderOption = $this->genderOption;
		$this->view->statusOption = $this->statusOption;
		$this->view->titleNameOption = $this->titleNameOption;
		$this->view->usergroupOption = Emwork_Utility::addEmptyOption($usergroupOption);
		
		// Set View
		$this->view->pageName = "เพิ่มผู้ใช้งาน";
		$this->view->pageIcon= "fa fa-user";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการผู้ใช้งาน', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'user', 'action' => 'index'), null, true)),
				array('name' => 'เพิ่มผู้ใช้งาน')
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
		$canSave = $sUser->isAllow('setting.user.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}

		// Model 
		$model = new System_Model_Users();
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// Check Param
		//Check dup username/email
		if ($model->checkEmail($params['txtEmail'])) {
			$result['message'] = 'อีเมล์ซ้ำ โปรดกรอกอีเมล์ใหม่';
			$this->_helper->json($result);
		}
		// Check Password
		if ($params['txtPassword'] != $params['txtConfirmPassword']) {
			$result['message'] = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน โปรดกรอกให้ตรงกัน';
			$this->_helper->json($result);
		}
		
		// Save
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
		$canDelete = $sUser->isAllow('setting.user.manage');
		$canEdit = $sUser->isAllow('setting.user.manage');
		$canView = $sUser->isAllow('setting.user.view');
		
		if (!$canView) {
			$this->_helper->redirector('index', 'user', 'system');
		} 
		
		// Get Param
		$this->view->isUserstatus = $this->statusOption;
		
		$params = $this->getRequest()->getParams();
		$itemID = $params['itemid'];
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Get Data
		$model = new System_Model_Users();
		$data = $model->getInfo($itemID);
		
		$modelUsergroup = new System_Model_Usergroup();
		$usergroupOption = $modelUsergroup->getOption();
		
		// Send Data to View
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;
		$this->view->data = $data;
		$this->view->genderOption = $this->genderOption;
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->statusOption= $this->statusOption;
		$this->view->usergroupOption= Emwork_Utility::addEmptyOption($usergroupOption);

		// Set View
		$this->view->pageName = "ปรับปรุงผู้ใช้งาน";
		$this->view->pageIcon= "fa fa-user";
		if ($canEdit) {
			$this->view->subMenus = array(
					array('name' => 'ปรับปรุงผู้ใช้งาน', 'active' => true),
					array('name' => 'เปลี่ยนรหัสผ่าน', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'user', 'action' => 'password', 'itemid' => $data->ID), null, true)),
			);
		} else {
			$this->view->subMenus = array();
		}
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการผู้ใช้งาน', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'user', 'action' => 'index'), null, true)),
				array('name' => 'ปรับปรุงผู้ใช้งาน')
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
		$canSave = $sUser->isAllow('setting.user.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
	
		// Param
		$params = $this->getRequest()->getParams();
	
		// Save & Update
		$model = new System_Model_Users();
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
		$canSave = $sUser->isAllow('setting.user.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
	
		// Param
		$params = $this->getRequest()->getParams();
	
		// Update
		$model = new System_Model_Users();
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

	public function passwordAction()
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
		
		// Rights
		$canEdit = $sUser->isAllow('setting.user.manage');
		if (!$canEdit) {
			$this->_helper->redirector('index', 'user', 'system');
		}
		
		// Get Param 
		$itemID = $this->_request->getParam('itemid');
		
		// Get Data
		$model = new System_Model_Users();
		$data = $model->getInfo($itemID);

		// Send Data to View
		$this->view->data= $data;
	
		// Set View
		$this->view->pageName = "เปลี่ยนรหัสผ่าน";
		$this->view->pageIcon= "fa fa-key";
		$this->view->subMenus = array(
				array('name' => 'ปรับปรุงผู้ใช้งาน', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'user', 'action' => 'edit', 'itemid' => $itemID), null, true)),
				array('name' => 'เปลี่ยนรหัสผ่าน', 'active' => true),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการผู้ใช้งาน', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'user', 'action' => 'index'), null, true)),
				array('name' => 'เปลี่ยนรหัสผ่าน')
		);
	}
	
	public function passwordsaveAction() {

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
		$canSave = $sUser->isAllow('setting.user.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}

		// Model 
		$model = new System_Model_Users();
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// Check ID
		if (empty($params['hdfID'])) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		// Check Password
		if ($params['txtPassword'] != $params['txtConfirmPassword']) {
			$result['message'] = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน โปรดกรอกให้ตรงกัน';
			$this->_helper->json($result);
		}
		
		// Save
		$data = $model->updatePassword($params);
		
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
}

