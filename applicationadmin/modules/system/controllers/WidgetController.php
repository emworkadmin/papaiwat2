<?php
/**
 * EM D2MS
 *
 * @copyright  Copyright (c) 2018 EMWORK Group
 * @license	EMWORK Group
 * @version	1.0.0 applicationadmin/module/system/controllers/WidgetController.php 
 * @author	EMWORK Group
 */

class System_WidgetController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $yesNoOption = array('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $showInMenu = array('0' => 'ทุกหน้า', '-' => 'ไม่แสดง', '1' => "เฉพาะที่เลือก", "-1" => "ไม่แสดงเฉพาะที่เลือก");
	protected $layoutPositionOption = array (
			'' => '', 'logo' => 'logo', 'menu' => 'menu', 'breadcrumb' => 'breadcrumb', 'left' => 'left', 'right' => 'right', 'search' => 'search', 'offcanvas' => 'offcanvas', 
			'top1' => 'top1', 'top2' => 'top2', 'top3' => 'top3', 'top4' => 'top4',
			'position1' => 'position1', 'position2' => 'position2', 'position3' => 'position3', 'position4' => 'position4',
			'position5' => 'position5', 'position6' => 'position6', 'position7' => 'position7', 'position8' => 'position8',
			'position9' => 'position9', 'position10' => 'position10', 'position11' => 'position11', 'position12' => 'position12',
			'position13' => 'position13', 'position14' => 'position14', 'position15' => 'position15', 'position16' => 'position16',
			'bottom1' => 'bottom1', 'bottom2' => 'bottom2', 'bottom3' => 'bottom3', 'bottom4' => 'bottom4',
			'footer1' => 'footer1', 'footer2' => 'footer2', 'footer3' => 'footer3', 'footer4' => 'footer4',
	);
	
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
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow("setting.widget.manage");
		$canAdd = $sUser->isAllow("setting.widget.manage");		
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "SystemWidgetSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "SystemWidgetSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->position = $infoSearchNamespace->position;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "SystemWidgetSearch" );
		}
		
		// Send Rights To View
		$this->view->canAdd = $canAdd;
		
		// Send Data to View
		$this->view->statusOption = $this->statusOption;
		$this->view->positionOption = $this->layoutPositionOption;
		
		// Set View
		$this->view->pageName = "รายการ Widget";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการ Widget')
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
		if ($sUser->getInfo()->UserType != 'B') {
			$canView = false;
		}
		
		// Check Action Rights
		$canView = $canView && $sUser->isAllow("setting.widget.manage");
		
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
		$advancesearch['position'] = $this->getRequest()->getParam('position');
		
		$searchNamespace = new Zend_Session_Namespace("SystemWidgetSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->isEnable = $advancesearch['enable'];
		$searchNamespace->position = $advancesearch['position'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];
		
		$model = new System_Model_Widget();
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
				$d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
				$d['Position'] = Emwork_Utility::notShowNull($item["Position"]);
				$d['Widget'] = Emwork_Utility::notShowNull($item["Widget"]);
				$d['Ordering'] = Emwork_Utility::notShowNull($item["Ordering"]);
				$d['IsEnable'] = Emwork_Utility::notShowNull($this->statusOption[$item["IsEnable"]]);
				
				$responce['data'][] = $d;
			}
		}
		
		$this->_helper->json($responce);
	}
	
	public function selectAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canAdd = $sUser->isAllow("setting.widget.manage");		
		if (!$canAdd) {
			$this->_helper->redirector('index', 'widget', 'system');
		}
		
		$model = new System_Model_Widget();
		$widgetTypeList = $model->getWidgetTypeList("Name", "asc", 0, 1, null, null, null);
		
		// Send Data To View
		$this->view->widgetTypeList = $widgetTypeList;
		
		// Set View
		$this->view->pageName = "เลือกประเภท";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการ Widget', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'widget', 'action' => 'index'), null, true)),
				array('name' => 'เลือกประเภท')
		);
	}
	
	public function addAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canAdd = $sUser->isAllow("setting.widget.manage");		
		if (!$canAdd) {
			$this->_helper->redirector('index', 'widget', 'system');
		}
		
		$widgetTypeName = $this->_request->getParam("type");
		if (empty($widgetTypeName)) {
			$this->_helper->redirector('index', 'widget', 'system');
		}
		
		// หาตัวจัดการ/ตั้งค่าของ Widget
		$widgetConfig = new stdClass();
		$widgetConfigPath = APPLICATION_ROOT . DS . 'applicationfront' . DS . 'views' . DS . 'helpers' . DS . 'Widget' . DS . ucfirst($widgetTypeName) . DS . ucfirst($widgetTypeName) . '.json';
		if (file_exists($widgetConfigPath)) {
			$widgetConfig = json_decode(file_get_contents($widgetConfigPath));
		}
		
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$menuGroupModel = new System_Model_Menugroup();
		$menuModel = new System_Model_Menu();
		$menuGroupOption = $menuGroupModel->getOption();
		
		$menuGroups = array();
		if (count($menuGroupOption) > 0) {
			foreach ($menuGroupOption as $key => $val) {
				$menuOption = $menuModel->getRecursiveOption($defaultLanguage["Code"], $key);
				if (count($menuOption) > 0) {
					$menuGroup = array();
					$menuGroup["MenuOption"] = $menuOption;
					$menuGroup["MenuGroupName"] = $val;
					$menuGroups[] = $menuGroup;
				}
			}
		}
		
		$languageOption = array('*' => 'ทุกภาษา');
		$langList = System_Model_Setting::getLanguages();
		if (count($langList) > 0) {
			foreach ($langList as $lang) {
				$languageOption[$lang["Code"]] = $lang["Name"];
			}
		}
		
		// Send Data To View
		$this->view->statusOption = $this->statusOption;
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->positionOption = $this->layoutPositionOption;
		$this->view->showInMenu = $this->showInMenu;
		$this->view->menuGroups = $menuGroups;
		$this->view->languageOption = $languageOption;
		$this->view->widgetTypeName = $widgetTypeName;
		$this->view->widgetConfig = $widgetConfig;
		
		
		// Set View
		$this->view->pageName = "เพิ่ม Widget";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการ Widget', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'widget', 'action' => 'index'), null, true)),
				array('name' => 'เพิ่ม Widget')
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
		$canSave = $sUser->isAllow("setting.widget.manage");
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model
		$model = new System_Model_Widget();
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// Check Param
		if (empty($params['hdfType'])) {
			$result['message'] = 'ไม่สามารถบันทึกได้ ไม่พบประเภท Widget';
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
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow("setting.widget.manage");
		$canEdit = $sUser->isAllow("setting.widget.manage");		
		if (!$canView) {
			$this->_helper->redirector('index', 'widget', 'system');
		} 
		
		$itemID = $this->_request->getParam('itemid');
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'widget', 'system');
		}
		
		$model = new System_Model_Widget();
		$data = $model->getInfo($itemID);
		$dataMenus = $model->getMenus($itemID);
		$data->MenuIds = array();
		$data->ShowInMenu = "-";
		if (count($dataMenus) > 0) {
			if ($dataMenus[0]["MenuID"] == 0) {
				$data->ShowInMenu = "0";
			} elseif ($dataMenus[0]["MenuID"] > 0) {
				$data->ShowInMenu = "1";
			} else {
				$data->ShowInMenu = "-1";
			}
			foreach ($dataMenus as $dataMenu) {
				$data->MenuIds[] = abs($dataMenu["MenuID"]);
			}
		}
		
		// หาตัวจัดการ/ตั้งค่าของ Widget
		$widgetConfig = new stdClass();
		$widgetConfigPath = APPLICATION_ROOT . DS . 'applicationfront' . DS . 'views' . DS . 'helpers' . DS . 'Widget' . DS . ucfirst($data->Widget) . DS . ucfirst($data->Widget) . '.json';
		if (file_exists($widgetConfigPath)) {
			$widgetConfig = json_decode(file_get_contents($widgetConfigPath));
		}
		
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$menuGroupModel = new System_Model_Menugroup();
		$menuModel = new System_Model_Menu();
		$menuGroupOption = $menuGroupModel->getOption();
		
		$menuGroups = array();
		if (count($menuGroupOption) > 0) {
			foreach ($menuGroupOption as $key => $val) {
				$menuOption = $menuModel->getRecursiveOption($defaultLanguage["Code"], $key);
				if (count($menuOption) > 0) {
					$menuGroup = array();
					$menuGroup["MenuOption"] = $menuOption;
					$menuGroup["MenuGroupName"] = $val;
					$menuGroups[] = $menuGroup;
				}
			}
		}
		
		$languageOption = array('*' => 'ทุกภาษา');
		$langList = System_Model_Setting::getLanguages();
		if (count($langList) > 0) {
			foreach ($langList as $lang) {
				$languageOption[$lang["Code"]] = $lang["Name"];
			}
		}
		
		// Send Data To View
		$this->view->statusOption = $this->statusOption;
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->positionOption = $this->layoutPositionOption;
		$this->view->showInMenu = $this->showInMenu;
		$this->view->menuGroups = $menuGroups;
		$this->view->languageOption = $languageOption;
		$this->view->widgetTypeName = $widgetTypeName;
		$this->view->widgetConfig = $widgetConfig;
		$this->view->data = $data;
		
		// Set View
		$this->view->pageName = "ปรับปรุง Widget";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการ Widget', 'url' => $this->view->url(array('module' => 'system', 'controller' => 'widget', 'action' => 'index'), null, true)),
				array('name' => 'ปรับปรุง Widget')
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
		$canSave = $sUser->isAllow("setting.widget.manage");
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// Save & Update
		$model = new System_Model_Widget();
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
		$canSave = $sUser->isAllow("setting.widget.manage");
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// Update
		$model = new System_Model_Widget();
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

	
	
	public function ajaxlistAction() {
		
		$result = array();
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow("setting.widget.manage");
		if (!$canSave) {
			$this->_helper->json($result);
		}
		
		$id = $this->getRequest()->getParam("id");
		$className = $this->getRequest()->getParam("c");
		$methodName = $this->getRequest()->getParam("m");
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$option = $className::$methodName($id);
		
		$i = 0;
		foreach ($option as $key => $value) {
			$result[$i]["id"] = $key;
			$result[$i]["name"] = $value;
			$i ++;
		}
		
		$this->_helper->json($result);
	}
}

