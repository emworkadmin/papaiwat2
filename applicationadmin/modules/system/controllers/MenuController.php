<?php
class System_MenuController extends Zend_Controller_Action {
	protected $publishOption = array (
			'Y' => 'เผยแพร่',
			'N' => 'ไม่เผยแพร่'
	);
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $menuTypeOption = array ('component' => 'ประเภทเมนู', 'url' => 'Url', 'none' => 'ไม่มีลิงค์');
	protected $urlTargetOption = array ('_blank' => 'เปิดหน้าต่างใหม่', '_self' => 'อยู่หน้าเดิม');
	
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
		$canView = $sUser->isAllow("setting.menu.manage");
		$canAdd = $sUser->isAllow("setting.menu.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "SystemMenuSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "SystemMenuSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->GroupMenu= $infoSearchNamespace->GroupMenu;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "SystemMenuSearch" );
		}
		
		// Get Option
		$menuGroupModel = new System_Model_Menugroup();
		$menuGroupOption = $menuGroupModel->getOption();
		
		// Send To View
		$this->view->publishOption = Emwork_Utility::addEmptyOption ( $this->publishOption );
		$this->view->groupMenu= Emwork_Utility::addEmptyOption ( $menuGroupOption);
				
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการเมนู";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก','url' => $this->view->url ( array ('module' => 'default','controller' => 'index','action' => 'index'), null, true)),
				array ('name' => 'รายการเมนู')
		);
	}
	public function getlistAction() {
		$filterInt = new Zend_Filter_Int ();
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
		$canView = $canView && $sUser->isAllow("setting.menu.manage");
		
		// Init
		$_draw = $this->getRequest ()->getParam ( 'draw' );
		$_start = $this->getRequest ()->getParam ( 'start' ); // Rowindex
		$_length = $this->getRequest ()->getParam ( 'length', '10' ); // Page Size
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
		$advancesearch ['menuGroupID'] = $this->getRequest ()->getParam ( 'menuGroupID' );
		
		$searchNamespace = new Zend_Session_Namespace ( "SystemMenuSearch" );
		$searchNamespace->searchText = $advancesearch ['searchText'];
		$searchNamespace->isEnable = $advancesearch ['isEnable'];
		$searchNamespace->GroupMenu= $advancesearch ['menuGroupID'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order [0] ['column'];
		$searchNamespace->tblOrderDir = $_order [0] ['dir'];
		
		$model = new System_Model_Menu();
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
				$d ['MenuName'] = Emwork_Utility::notShowNull( $item ["MenuName" ] );
				$d ['MenuGroupName'] = Emwork_Utility::notShowNull ( $item ["MenuGroupName"] );
				$d ['Ordering'] = Emwork_Utility::notShowNull ( $item ["Ordering"] );
				$d ['IsEnable'] = Emwork_Utility::notShowNull ( $this->publishOption [$item ["IsEnable"]] );
				$d ['IsHome'] = Emwork_Utility::notShowNull ( $this->yesNoOption [$item ["IsHome"]] );
				$responce ['data'] [] = $d;
			}
		}
		
		$this->_helper->json ( $responce );
	}
	
	public function addAction() {
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
		$canAdd = $sUser->isAllow("setting.menu.manage");		
		if (! $canAdd) {
			$this->_helper->redirector ( 'index', 'menu', 'system' );
		}
		
		$menuGroupModel = new System_Model_Menugroup();
		$menuGroupOption = $menuGroupModel->getOption();
		
		// Send To View
		$languageList = System_Model_Setting::getLanguages ();
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		$this->view->menuGroupOption = $menuGroupOption;
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->menuTypeOption = $this->menuTypeOption;
		$this->view->urlTargetOption = $this->urlTargetOption;
		
		// Set View
		$this->view->pageName = "เพิ่มเมนู";
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
						'name' => 'รายการเมนู',
						'url' => $this->view->url ( array (
								'module' => 'system',
								'controller' => 'menu',
								'action' => 'index'
						), null, true )
				),
				array (
						'name' => 'เพิ่มรายการเมนู'
				)
		);
	}
	public function saveAction() {
		// Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();		
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		// Check Rights
		if ($sUser->getInfo()->UserType != 'B') {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow("setting.menu.manage");
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}		
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$defaultLanguageCode = $defaultLanguage["Code"];
		
		$model = new System_Model_Menu ();
		$params = $this->getRequest ()->getParams ();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		// Check: Select module and controller (Only component)
		if ($params["rdlMenuType"] == 'component') {
			if (empty($params["mConfig"]) || empty($params["cConfig"])) {
				$result['message'] = 'โปรดเลือกประเภทเมนู';
				$this->_helper->json($result);
			}
			
			// Find Url
			$url = '';
			$mConfig = $params['mConfig'];
			$mClass = ucfirst($mConfig);
			$cConfig = $params['cConfig'];
			$mParams = $params["mparams"];
			try {
				$serviceUrlClass = "{$mClass}_Service_Url";
				$url = $serviceUrlClass::generate($cConfig, $mParams);
			} catch (Exception $e) {
				
			}
			$params["params"][$defaultLanguage['Code']]["Url"] = $url;
		} 
		
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
		
		// / Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canDelete = $sUser->isAllow("setting.menu.manage");
		$canEdit = $sUser->isAllow("setting.menu.manage");
		$canView = $sUser->isAllow("setting.menu.manage");			
		if (!$canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Get Param
		$itemID = $this->_request->getParam ( 'itemid' );
		
		// Model
		if (! $itemID) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$menuGroupModel = new System_Model_Menugroup();
		$menuGroupOption = $menuGroupModel->getOption();
		
		$model = new System_Model_Menu ();
		$data = $model->getInfo ( $itemID );
		$data->Type = empty($data->Type) ? "component" : $data->Type;
		
		$dataDetails = array();
		if (count($languages) > 0) {
			foreach ($languages as $lang) {
				$langCode = $lang['Code'];
				$detail = $model->getDataByLanguage($data->ID, $langCode);
				if (!empty($detail->Lang)) {
					$dataDetails[$langCode] = $detail;
				}				
				if ($lang['IsDefault'] == 'Y') {
					$defaultLanguage = $lang;
				}
			}
		}
		
		$data->ModuleName = "";
		$data->ControllerName = "";
		if ($data->Type == "component") {		
			$elementData = System_Model_Element::getInfoByRef("component", $data->FrontModule);
			$data->ModuleName = $elementData->Name;
			$data->ControllerName = "";
			
			$config = null;
			$configPath = APPLICATION_ROOT . DS . "applicationfront" . DS . "modules" . DS . $data->FrontModule . DS . "views" . DS . "scripts" . DS . $data->FrontController . DS . $data->FrontController . ".json";
			if (file_exists($configPath)) {
				$config = json_decode(file_get_contents($configPath));
				$data->ControllerName = $config->name;
			}
		} elseif ($data->Type == "url") {
			$menuSetting = empty($data->Params) ? array() : json_decode($data->Params, true);
			$data->Target = $menuSetting["Target"];
		}
		
		// Send To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		$this->view->menuGroupOption= $menuGroupOption;
		$this->view->publishOption = $this->publishOption;
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->menuTypeOption = $this->menuTypeOption;
		$this->view->urlTargetOption = $this->urlTargetOption;
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;
		$this->view->data = $data;
		$this->view->dataDetails = $dataDetails;
		
		// Set View
		$this->view->pageName = "ปรับปรุงเมนู";
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
						'name' => 'รายการเมนู',
						'url' => $this->view->url ( array (
								'module' => 'system',
								'controller' => 'menu',
								'action' => 'index'
						), null, true )
				),
				array (
						'name' => 'ปรับปรุงเมนู'
				)
		);
	}
	public function updateAction() {
		// Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow("setting.menu.manage");
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new System_Model_Menu();
		$params = $this->getRequest ()->getParams ();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		// Check ว่าเลือก Parent เป็นตัวเองหรือไม่
		if ($params['hdfID'] == $params["params"][$defaultLanguage['Code']]["Parent"]) {
			$result['message'] = 'ไม่สามารถเลือก Parent ที่เป็นเมนูตัวเองได้';
			$this->_helper->json($result);
		}
		
		// Check ว่า Parent ที่เลือกไม่ได้เป็น Child ของข้อมูลปัจจุบัน
		$isChildResult = $model->isChild($params['hdfID'], $params["params"][$defaultLanguage['Code']]["Parent"]);
		if ($isChildResult) {
			$result['message'] = 'ไม่สามารถเลือก Parent ที่เป็น Child ของเมนูนี้ได้';
			$this->_helper->json($result);
		}
		
		// Check: Select module and controller (Only component)
		if ($params["rdlMenuType"] == 'component') {
			if (empty($params["mConfig"]) || empty($params["cConfig"])) {
				$result['message'] = 'โปรดเลือกประเภทเมนู';
				$this->_helper->json($result);
			}
			
			// Find Url
			$url = '';
			$mConfig = $params['mConfig'];
			$mClass = ucfirst($mConfig);
			$cConfig = $params['cConfig'];
			$mParams = $params["mparams"];
			try {
				$serviceUrlClass = "{$mClass}_Service_Url";
				$url = $serviceUrlClass::generate($cConfig, $mParams);
			} catch (Exception $e) {
				
			}
			$params["params"][$defaultLanguage['Code']]["Url"] = $url;
		}
		
		
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
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow("setting.menu.manage");
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		
		// Check Auth
		$auth = Zend_Auth::getInstance();
		if (!$auth->hasIdentity()) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// Update
		$model = new System_Model_Menu();
		$data = $model->delete($params);
		
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
	
	// Ajax Cas
	public function ajaxlistAction() {
		
		$id = $this->getRequest()->getParam("id");
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new System_Model_Menu();
		$data = $model->getRecursiveOption($defaultLanguage['Code'], $id);
		
		$i = 0;
		foreach ($data as $key => $value) {
			$result[$i]["id"] = $key;
			$result[$i]["name"] = $value;
			$i ++;
		}
		
		$this->_helper->json($result);
	}
	
	// Component List 
	public function componentsAction() {
		
		$this->_helper->layout()->disableLayout();
				
		$model = new System_Model_Menu();
		$componentList = $model->getComponentList("Name", "asc", 0, 1, null, null, null);
		
		$components = array();
		if (count($componentList) > 0) {
			foreach ($componentList as $componentItem) {
				$refTypeName = $componentItem["RefTypeName"];
				$component = array();
				$component["Name"] = $componentItem["Name"];
				$component["Module"] = $refTypeName;
				$component["Controllers"] = array();
				$moduleFrontViewPath = APPLICATION_ROOT . DS . "applicationfront" . DS . "modules" . DS . $refTypeName . DS . "views" . DS . "scripts";
				if (file_exists($moduleFrontViewPath)) {
					$moduleViews = scandir($moduleFrontViewPath);
					if ($moduleViews !== false) {
						foreach ($moduleViews as $moduleView) {
							if ($moduleView != '.' && $moduleView != '..' && is_dir($moduleFrontViewPath . DS . $moduleView)) {
								$filePathConfigs = glob($moduleFrontViewPath . DS . $moduleView . DS . "*.json");
								if (count($filePathConfigs) > 0) {
									foreach ($filePathConfigs as $filePathConfig) {
										$fileConfig = json_decode(file_get_contents($filePathConfig));
										$controller = array();
										$controller["Name"] = $fileConfig->name;
										$controller["Controller"] = $fileConfig->controller;
										$controller["Description"] = $fileConfig->description;
										$component["Controllers"][] = $controller;
									}
								}
							}
						}
						
					}
				}
				
				if (count($component["Controllers"]) > 0) {
					$components[] = $component;
				}
			}
		}
		
		
		// Send Data To View
		$this->view->components = $components;
	}
	
	function componentAction() {
		$this->_helper->layout()->disableLayout();
		$module = $this->getRequest()->getParam("m");
		$controller = $this->getRequest()->getParam("c");
		
		$data = new stdClass();
		$id = $this->getRequest()->getParam("id");
		if (!empty($id)) {
			$model = new System_Model_Menu();
			$data = $model->getInfo($id);
		}
		
		$config = null;
		$configPath = APPLICATION_ROOT . DS . "applicationfront" . DS . "modules" . DS . $module . DS . "views" . DS . "scripts" . DS . $controller . DS . $controller . ".json";
		if (file_exists($configPath)) {
			$config = json_decode(file_get_contents($configPath));
		}
		
		$this->view->config = $config;
		$this->view->data = $data;
	}
}

