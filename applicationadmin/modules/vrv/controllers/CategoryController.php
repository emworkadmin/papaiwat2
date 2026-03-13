<?php
class Vrv_CategoryController extends Zend_Controller_Action {
	
	protected $publishOption = array (
			'Y' => 'เผยแพร่',
			'N' => 'ไม่เผยแพร่' 
	);
	
	// Settting Option
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
	protected $defaultCustomizeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
	protected $publicCommentOption = array ('Y' => 'เผยแพร่ทันที', 'N' => 'รอพิจารณา');
	
	public function init() {
		
	}
	public function indexAction() {
		// Log Access
		System_Model_Log::access ();
		
		// / Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('vrv.category.view');
		$canAdd = $sUser->isAllow('vrv.category.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "VrCategoryIndexSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "VrCategoryIndexSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "VrCategoryIndexSearch" );
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$model = new Vrv_Model_Category();
		$recursiveList = $model->getRecursiveList($defaultLanguage['Code']);

		// Send To View
		$this->view->recursiveList = $recursiveList;
		$this->view->publishOption = Emwork_Utility::addEmptyOption ( $this->publishOption );
		
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการหมวดหมู่ Virtual Reality";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ( 'name' => 'หน้าแรก', 'url' => $this->view->url ( array ( 'module' => 'default', 'controller' => 'index', 'action' => 'index'  ), null, true)), 
				array ( 'name' => 'รายการหมวดหมู่ Virtual Reality'  ) 
		);
	}

	public function getlistAction() {
		
		$filterInt = new Zend_Filter_Int ();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->json($responce);
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('vrv.category.view');

		// Init
		$_draw = $this->getRequest ()->getParam ( 'draw' );
		$_start = $this->getRequest ()->getParam ( 'start' );
		$_length = $this->getRequest ()->getParam ( 'length' );
		$_order = $this->getRequest ()->getParam ( 'order' );
		$_columns = $this->getRequest ()->getParam ( 'columns' );
		$orderIndex = $_order [0] ['column'];
		$sidx = $_columns [$orderIndex] ['data'];
		$sord = $_order [0] ['dir'];
		$rows = $_length;
		$page = ($_start / $rows) + 1;
		                               
		// Basic Search
		$textstr = $this->getRequest ()->getParam ( 'textstr' );
		$textstr = $textstr;
		$filter = '';
		              
		// Advance Search
		$advancesearch ['searchText'] = $textstr;
		$advancesearch ['isEnable'] = $this->getRequest ()->getParam ( 'isEnable' );
		
		$searchNamespace = new Zend_Session_Namespace("VrCategoryIndexSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->isEnable = $advancesearch['isEnable'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];
		
		if($canView):
			$model = new Vrv_Model_Category();
			$data = $model->getList ( $sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch );
		endif;
		
		$i = $_start;
		$responce = array ();
		$responce ['draw'] = $_draw;
		$responce ['recordsTotal'] = 0;
		$responce ['recordsFiltered'] = 0;
		$responce ['data'] = array ();
		if ($data) {
			$responce ['recordsTotal'] = $data [0] ["RECORD"];
			$responce ['recordsFiltered'] = $data [0] ["RECORD"];
			
			foreach ( $data as $item ) {
				$i ++;
				$d = array ();
				$d ['DT_RowId'] = $item ["ID"];
				$d ['NO'] = $i;
				$d ['Ordering'] = Emwork_Utility::notShowNull ( $item ["Ordering"] ); 
				$d ['Title'] = Emwork_Utility::notShowNull ( $item ["Title"] );
				$d ['ItemAmount'] = Emwork_Utility::notShowNull ( $item ["ItemAmount"], 0 );
				$d ['IsEnableCode'] = $item["IsEnable"];
				$d ['IsEnable'] = Emwork_Utility::notShowNull ( $this->publishOption [$item ["IsEnable"]] );
				$responce ['data'] [] = $d;
			}
		}
		
		$this->_helper->json ( $responce );
	}

	public function addAction() {
		// Log Access
		System_Model_Log::access ();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canAdd = $sUser->isAllow('vrv.category.manage');
		
		if (!$canAdd) {
			$this->_helper->redirector('index', 'category', 'vrv');
		}
		
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Vrv_Model_Category();
		$categoryRecursiveOption = $model->getRecursiveOption($defaultLanguage['Code']);
		
		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		$this->view->categoryRecursiveOption = $categoryRecursiveOption;
		
		// Set View
		$this->view->pageName = "เพิ่มหมวดหมู่ Virtual Reality";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url ( array ('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการหมวดหมู่ Virtual Reality','url' => $this->view->url ( array ('module' => 'vrv', 'controller' => 'category', 'action' => 'index'), null, true )),
				array ('name' => 'เพิ่มหมวดหมู่ Virtual Reality') 
		);

	}

	public function saveAction() {
		// Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		$canAdd = $sUser->isAllow('vrv.category.manage');
		if (!$canAdd) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Vrv_Model_Category();
		$params = $this->getRequest ()->getParams ();
		
		$fileModel = new System_Model_Fileinfo();
		// Prepare Intro Image
		$introImageID= null;
		if (count($params['hdfIntroImage'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
			if ($fileData->ID != null) {
				$introImageID = $fileData->ID;
			}
		}
		$params['IntroImageID'] = $introImageID;
		
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		$data = $model->save ($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("vrv.category", "save", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}

	public function editAction() {
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
		$canDelete = $sUser->isAllow('vrv.category.manage');
		$canEdit = $sUser->isAllow('vrv.category.manage');
		$canView = $sUser->isAllow('vrv.category.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'category', 'vrv');
		}
		
		// Get Param
		$itemID = $this->_request->getParam ( 'itemid' );
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'category', 'vrv');
		}
		
		$model = new Vrv_Model_Category();
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
				
		// Get Data
		$data = $model->getInfo ( $itemID );

		if (!$data->ID || $data->IsDeleted=='Y') {
			$this->_helper->redirector('index', 'category', 'vrv');
		}
		
		$dataDetails = array();
		if (count($languages) > 0) {
			foreach ($languages as $lang) {
				$langCode = $lang['Code'];
				$detail = $model->getDataByLanguage($data->ID, $langCode);
				if (!empty($detail->Lang)) {
					$dataDetails[$langCode] = $detail;
				}
			}
		}
		
		// Option
		$categoryRecursiveOption = $model->getOption($defaultLanguage['Code']);
		
		// Send Rights to View
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;
		
		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		$this->view->categoryRecursiveOption = $categoryRecursiveOption;
		
		$this->view->data = $data;
		$this->view->dataDetails = $dataDetails;
		
		// Set View
		$this->view->pageName = "ปรับปรุงหมวดหมู่ Virtual Reality";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array (
				array('name' => 'รายละเอียดหมวดหมู่', 'active' => true),
				array('name' => 'ตั้งค่าการแสดงผล', 'active' => false, 'url' => $this->view->url(array('module' => 'vrv', 'controller' => 'category', 'action' => 'setting', 'itemid' => $itemID), null, true)),
		);
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการหมวดหมู่ Virtual Reality', 'url' => $this->view->url ( array ('module' => 'vrv', 'controller' => 'category', 'action' => 'index'), null, true)),
				array ('name' => 'ปรับปรุงหมวดหมู่ Virtual Reality') 
		);
		
		// Log Acion
		System_Model_Log::action("vrv.category", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('vrv.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Vrv_Model_Category();
		$params = $this->getRequest ()->getParams ();
		
		$fileModel = new System_Model_Fileinfo();
		// Prepare Intro Image
		$introImageID= null;
		if (count($params['hdfIntroImage'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
			if ($fileData->ID != null) {
				$introImageID = $fileData->ID;
			}
		}
		$params['IntroImageID'] = $introImageID;
		
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		$data = $model->update ( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("vrv.category", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
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
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('vrv.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest ()->getParams ();
		
		// Update
		$model = new Vrv_Model_Category();
		$data = $model->delete ( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			
			// Log Acion
			System_Model_Log::action("vrv.category", "delete",  $params['title'], $params["id"]);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'ลบไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}
	
	/***************** Setting ***********************/
	public function settingAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canView = $sUser->isAllow("vrv.category.view");
		$canEdit = $sUser->isAllow("vrv.category.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'category', 'vrv');
		}
		
		$itemID = $this->getRequest ()->getParam ( 'itemid' );
		if (empty($itemID)) {
			$this->_helper->redirector('index', 'category', 'vrv');
		}
		
		$model = new Vrv_Model_Category();
		$dataInfo = $model->getInfo($itemID);

		if(!$dataInfo->ID || $dataInfo->IsDeleted=='Y'){
			$this->_helper->redirector('index', 'category', 'vrv');
		}

		$dataSettings = $model->getSettings($itemID);
		$data = json_decode($dataSettings->Settings, true);
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$dataDefault = new stdClass();
		$dataDefault->itemPerPage = System_Model_Setting::getValue("ItemPerPage", "VRV");
		$dataDefault->itemSortType = System_Model_Setting::getValue("ItemSortType", "VRV");
		$dataDefault->itemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "VRV");
		$dataDefault->itemShowNoDataCategory = System_Model_Setting::getValue("ItemShowNoDataCategory", "VRV");
		$dataDefault->itemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "VRV");
		$dataDefault->itemShowImage = System_Model_Setting::getValue("ItemShowImage", "VRV");
		$dataDefault->itemShowCategory = System_Model_Setting::getValue("ItemShowCategory", "VRV");
		$dataDefault->itemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "VRV");
		$dataDefault->itemShowAmount = System_Model_Setting::getValue("ItemShowAmount", "VRV");
		
		$dataDefault->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "VRV");
		$dataDefault->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "VRV");
		$dataDefault->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "VRV");
		$dataDefault->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "VRV");
		$dataDefault->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "VRV");
		
		// Send Data to View
		$this->view->dataInfo = $dataInfo;
		$this->view->data = $data;
		$this->view->dataDefault = $dataDefault;
		$this->view->canEdit = $canEdit;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->defaultCustomizeOption = $this->defaultCustomizeOption;
		$this->view->publicCommentOption = $this->publicCommentOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าการแสดงผล";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array (
				array('name' => 'รายละเอียดหมวดหมู่', 'active' => false, 'url' => $this->view->url(array('module' => 'vrv', 'controller' => 'category', 'action' => 'edit', 'itemid' => $itemID), null, true)),
				array('name' => 'ตั้งค่าการแสดงผล', 'active' => true),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการหมวดหมู่ Virtual Reality', 'url' => $this->view->url(array('module' => 'vrv', 'controller' => 'category', 'action' => 'index'), null, true)),
				array('name' => 'ปรับปรุงหมวดหมู่ Virtual Reality', 'url' => $this->view->url(array('module' => 'vrv', 'controller' => 'category', 'action' => 'edit', "itemid" => $itemID), null, true)),
				array('name' => 'ตั้งค่าการแสดงผล')
		);
	}
	
	public function settingsaveAction() {
		
		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('vrv.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model
		$model = new Vrv_Model_Category();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		$id = $requestParams["hdfID"];
		
		// Check Param
		
		// Set Params
		$params = array();
		$params["ItemPerPageSetting"] = $requestParams['ItemPerPageSetting'];
		$params["ItemPerPage"] = $requestParams['ItemPerPage'];
		$params["ItemSortSetting"] = $requestParams['ItemSortSetting'];
		$params["ItemSortType"] = $requestParams['ItemSortType'];
		$params["ItemSortDirection"] = $requestParams['ItemSortDirection'];
		$params["ItemShowNoDataCategorySetting"] = $requestParams['ItemShowNoDataCategorySetting'];
		$params["ItemShowNoDataCategory"] = $requestParams['ItemShowNoDataCategory'];
		$params["ItemAmountColumnSetting"] = $requestParams['ItemAmountColumnSetting'];
		$params["ItemAmountColumn"] = $requestParams['ItemAmountColumn'];
		$params["ItemShowImageSetting"] = $requestParams['ItemShowImageSetting'];
		$params["ItemShowImage"] = $requestParams['ItemShowImage'];
		$params["ItemShowCategorySetting"] = $requestParams['ItemShowCategorySetting'];
		$params["ItemShowCategory"] = $requestParams['ItemShowCategory'];
		$params["ItemShowIntroTextSetting"] = $requestParams['ItemShowIntroTextSetting'];
		$params["ItemShowIntroText"] = $requestParams['ItemShowIntroText'];
		$params["ItemShowAmountSetting"] = $requestParams['ItemShowAmountSetting'];
		$params["ItemShowAmount"] = $requestParams['ItemShowAmount'];
		
		$params["CategoryPerPageSetting"] = $requestParams['CategoryPerPageSetting'];
		$params["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["CategoryAmountColumnSetting"] = $requestParams['CategoryAmountColumnSetting'];
		$params["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["CategoryShowImageSetting"] = $requestParams['CategoryShowImageSetting'];
		$params["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["CategoryShowCategorySetting"] = $requestParams['CategoryShowCategorySetting'];
		$params["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["CategoryShowIntroTextSetting"] = $requestParams['CategoryShowIntroTextSetting'];
		$params["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];
		
		$settings = json_encode($params);
		
		// Save
		$data = $model->saveSetting($id, $settings);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
		} else {
			$result['result'] = false;
			$result['message'] = $data->message; // 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}

	public function listpublishAction()
    {
    	// Init
    	$result = array();
    	$result['result'] = false;
    	$result['message'] = '';
    	
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('vrv.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Vrv_Model_Category();
    	$data = $model->listPublish($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
			$result['message'] = '';
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ปรับปรุงสถานะไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }

	public function listcopyAction()
    {
    	// Init
    	$result = array();
    	$result['result'] = false;
    	$result['message'] = '';
    	
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('vrv.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
		$params = $this->getRequest()->getParams();
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Vrv_Model_Category();
    	$data = $model->listCopy($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
			$result['message'] = '';
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'คัดลอกข้อมูลไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }
	
	public function listdeleteAction()
    {
    	// Init
    	$result = array();
    	$result['result'] = false;
    	$result['message'] = '';
    	
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('vrv.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Vrv_Model_Category();
    	$data = $model->listDelete($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
			$result['message'] = '';
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ลบข้อมูลไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
	}
	
	public function updateparentAction()
    {
    	// Init
    	$result = array();
    	$result['result'] = false;
    	$result['message'] = '';
    	
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('vrv.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Vrv_Model_Category();
    	$data = $model->updateParent($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
			$result['message'] = '';
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ปรับปรุงสถานะไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }
}

