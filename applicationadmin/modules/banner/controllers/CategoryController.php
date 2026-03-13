<?php
class Banner_CategoryController extends Zend_Controller_Action {
	
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
	
	public function init() {
		
	}
	public function indexAction() {
		// Log Access
		System_Model_Log::access ();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('banner.category.view');
		$canAdd = $sUser->isAllow('banner.category.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "BannerCategoryIndexSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "BannerCategoryIndexSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "BannerCategoryIndexSearch" );
		}
		
		// Send To View
		$this->view->publishOption = Emwork_Utility::addEmptyOption ( $this->publishOption );
		
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการหมวดหมู่ป้ายโฆษณา";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ( 'name' => 'หน้าแรก', 'url' => $this->view->url ( array ( 'module' => 'default', 'controller' => 'index', 'action' => 'index'  ), null, true)), 
				array ( 'name' => 'รายการหมวดหมู่ป้ายโฆษณา'  ) 
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
		
		$canView = $sUser->isAllow('banner.category.view');
		
		
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
		
		$searchNamespace = new Zend_Session_Namespace("BannerCategoryIndexSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->isEnable = $advancesearch['isEnable'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];
		
		if($canView):
			$model = new Banner_Model_Category();
			$data = $model->getList ( $sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch );
		endif;
		
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
		$canAdd = $sUser->isAllow('banner.category.view');
		
		if (!$canAdd) {
			$this->_helper->redirector('index', 'category', 'banner');
		}
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Banner_Model_Category();
		$categoryRecursiveOption = $model->getRecursiveOption($defaultLanguage['Code']);
		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		$this->view->categoryRecursiveOption = $categoryRecursiveOption;
		
		// Set View
		$this->view->pageName = "เพิ่มหมวดหมู่ป้ายโฆษณา";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url ( array ('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการหมวดหมู่ป้ายโฆษณา','url' => $this->view->url ( array ('module' => 'banner', 'controller' => 'category', 'action' => 'index'), null, true )),
				array ('name' => 'เพิ่มหมวดหมู่ป้ายโฆษณา') 
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
		$canAdd = $sUser->isAllow('banner.category.manage');
		if (!$canAdd) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Banner_Model_Category();
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
			System_Model_Log::action("banner.category", "save", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}
	public function editAction() {
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canDelete = $sUser->isAllow('banner.category.manage');
		$canEdit = $sUser->isAllow('banner.category.manage');
		$canView = $sUser->isAllow('banner.category.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'category', 'banner');
		}
		
		// Get Param
		$itemID = $this->_request->getParam ( 'itemid' );
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'category', 'banner');
		}
		
		$model = new Banner_Model_Category();
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
				
		// Get Data
		$data = $model->getInfo ( $itemID );
		
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
		$categoryRecursiveOption = $model->getRecursiveOption($defaultLanguage['Code'], $data->ParentID,$itemID);

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
		$this->view->pageName = "ปรับปรุงหมวดหมู่ป้ายโฆษณา";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array (
				array('name' => 'รายละเอียดหมวดหมู่', 'active' => true),
				array('name' => 'ตั้งค่าการแสดงผล', 'active' => false, 'url' => $this->view->url(array('module' => 'banner', 'controller' => 'category', 'action' => 'setting', 'itemid' => $itemID), null, true)),
		//		array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'banner', 'controller' => 'item', 'action' => 'rights', 'itemid' => $itemID), null, true))
		);
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการหมวดหมู่ป้ายโฆษณา', 'url' => $this->view->url ( array ('module' => 'banner', 'controller' => 'category', 'action' => 'index'), null, true)),
				array ('name' => 'ปรับปรุงหมวดหมู่ป้ายโฆษณา') 
		);
		
		// Log Acion
		System_Model_Log::action("banner.category", "view", $dataDetails[$defaultLanguage['Code']]->Title, $itemID);
	}
	public function updateAction() {
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
		$canSave = $sUser->isAllow('banner.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Banner_Model_Category();
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
			System_Model_Log::action("banner.category", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
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
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบลบได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบลบได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('banner.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบลบได้';
			$this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest ()->getParams ();

		// Update
		$model = new Banner_Model_Category();
		$data = $model->delete ( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			
			// Log Acion
			System_Model_Log::action("banner.category", "delete", $params['title'], $params["id"]);
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
		// TODO: Use isAllow
		$canView = $sUser->isAllow("banner.category.view");
		$canEdit = $sUser->isAllow("banner.category.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'banner');
		}
		
		$itemID = $this->getRequest ()->getParam ( 'itemid' );
		if (empty($itemID)) {
			$this->_helper->redirector('index', 'category', 'calendar');
		}
		
		$model = new Banner_Model_Category();
		$dataInfo = $model->getInfo($itemID);
		$dataSettings = $model->getSettings($itemID);
		
		$data = json_decode($dataSettings->Settings, true);
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$dataDefault = new stdClass();

		$dataDefault->itemSortSetting = System_Model_Setting::getValue("ItemSortSetting", "Banner");
		$dataDefault->itemSortType = System_Model_Setting::getValue("ItemSortType", "Banner");
		$dataDefault->itemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Banner");
		$dataDefault->itemBannerSizeSetting = System_Model_Setting::getValue("ItemBannerSizeSetting", "Banner");
		$dataDefault->itemBannerSizeHeight = System_Model_Setting::getValue("ItemBannerSizeHeight", "Banner");
		$dataDefault->itemBannerSizeWidth = System_Model_Setting::getValue("ItemBannerSizeWidth", "Banner");
		$dataDefault->itemShowTitleSetting = System_Model_Setting::getValue("ItemShowTitleSetting", "Banner");
		$dataDefault->itemShowTitle = System_Model_Setting::getValue("ItemShowTitle", "Banner");
		$dataDefault->itemShowIntroTextSetting = System_Model_Setting::getValue("ItemShowIntroTextSetting", "Banner");
		$dataDefault->itemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Banner");
		$dataDefault->itemCustomCssSetting = System_Model_Setting::getValue("ItemCustomCssSetting", "Banner");
		$dataDefault->itemCustomCss = System_Model_Setting::getValue("ItemCustomCss", "Banner");

		// Send Data to View
		$this->view->canEdit = $canEdit;
		$this->view->dataInfo = $dataInfo;
		$this->view->data = $data;
		$this->view->dataDefault = $dataDefault;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->defaultCustomizeOption = $this->defaultCustomizeOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าการแสดงผล";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array (
				array('name' => 'รายละเอียดหมวดหมู่', 'active' => false, 'url' => $this->view->url(array('module' => 'banner', 'controller' => 'category', 'action' => 'edit', 'itemid' => $itemID), null, true)),
				array('name' => 'ตั้งค่าการแสดงผล', 'active' => true),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการหมวดหมู่ป้ายโฆษณา', 'url' => $this->view->url(array('module' => 'banner', 'controller' => 'category', 'action' => 'index'), null, true)),
				array('name' => 'ปรับปรุงหมวดหมู่ป้ายโฆษณา', 'url' => $this->view->url(array('module' => 'banner', 'controller' => 'category', 'action' => 'edit', "itemid" => $itemID), null, true)),
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
		$canSave = $sUser->isAllow('banner.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}

		// Model
		$model = new Banner_Model_Category();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		$id = $requestParams["hdfID"];
		
		// Set Params
		$params = array();
		$params["ItemSortSetting"] = $requestParams['ItemSortSetting'];
		$params["ItemSortType"] = $requestParams['ItemSortType'];
		$params["ItemSortDirection"] = $requestParams['ItemSortDirection'];
		$params["ItemBannerSizeSetting"] = $requestParams['ItemBannerSizeSetting'];
		$params["ItemBannerSizeHeight"] = $requestParams['ItemBannerSizeHeight'];
		$params["ItemBannerSizeWidth"] = $requestParams['ItemBannerSizeWidth'];
		$params["ItemShowTitleSetting"] = $requestParams['ItemShowTitleSetting'];
		$params["ItemShowTitle"] = $requestParams['ItemShowTitle'];
		$params["ItemShowIntroTextSetting"] = $requestParams['ItemShowIntroTextSetting'];
		$params["ItemShowIntroText"] = $requestParams['ItemShowIntroText'];
		$params["ItemCustomCssSetting"] = $requestParams['ItemCustomCssSetting'];
		$params["ItemCustomCss"] = $requestParams['ItemCustomCss'];

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
		$canSave = $sUser->isAllow('banner.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Banner_Model_Category();
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
		$canSave = $sUser->isAllow('banner.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Banner_Model_Category();
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
		$canSave = $sUser->isAllow('banner.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Banner_Model_Category();
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
}

