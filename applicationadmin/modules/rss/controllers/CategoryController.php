<?php
class Rss_CategoryController extends Zend_Controller_Action {
	
	protected $publishOption = array (
			'Y' => 'เผยแพร่',
			'N' => 'ไม่เผยแพร่' 
	);
	
	// Settting Option
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', /*'publishdate' => 'วันที่เผยแพร่',*/ 'viewamount' => 'ความนิยม');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
	protected $defaultCustomizeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
	protected $publicCommentOption = array ('Y' => 'เผยแพร่ทันที', 'N' => 'รอพิจารณา');
	
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
		$canView = $sUser->isAllow('rss.category.view');
		$canAdd = $sUser->isAllow('rss.category.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset ( "RssCategoryIndexSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace ( "RssCategoryIndexSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "RssCategoryIndexSearch" );
		}
		
		// Get Option
		
		// Send To View
		$this->view->publishOption = Emwork_Utility::addEmptyOption ( $this->publishOption );
		
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการหมวดหมู่ RSS";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ( 'name' => 'หน้าแรก', 'url' => $this->view->url ( array ( 'module' => 'default', 'controller' => 'index', 'action' => 'index'  ), null, true)), 
				array ( 'name' => 'รายการหมวดหมู่ RSS'  ) 
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
		
		$canView = $sUser->isAllow('rss.category.view');
		
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
		
		$searchNamespace = new Zend_Session_Namespace("RssCategoryIndexSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->isEnable = $advancesearch['isEnable'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];

		if($canView):
			$model = new Rss_Model_Category();
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
		$canAdd = $sUser->isAllow('rss.category.manage');
		
		if (!$canAdd) {
		   $this->_helper->redirector('index', 'category', 'rss');
		}
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		
		// Set View
		$this->view->pageName = "เพิ่มหมวดหมู่ RSS";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url ( array ('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการหมวดหมู่ RSS','url' => $this->view->url ( array ('module' => 'rss', 'controller' => 'category', 'action' => 'index'), null, true )),
				array ('name' => 'เพิ่มหมวดหมู่ RSS') 
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
		$canAdd = $sUser->isAllow('rss.category.manage');
		if (!$canAdd) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Rss_Model_Category();
		$params = $this->getRequest ()->getParams ();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		$data = $model->save ($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("rss.category", "save", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
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
		$canDelete = $sUser->isAllow('rss.category.manage');
		$canEdit = $sUser->isAllow('rss.category.manage');
		$canView = $sUser->isAllow('rss.category.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'category', 'rss');
		}
		
		// Get Param
		$itemID = $this->_request->getParam ( 'itemid' );
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'category', 'rss');
		}
		
		$model = new Rss_Model_Category();
		
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
		
		// Send Rights to View
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;
		
		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		
		$this->view->data = $data;
		$this->view->dataDetails = $dataDetails;
		
		// Set View
		$this->view->pageName = "ปรับปรุงหมวดหมู่ RSS";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array (
				array('name' => 'รายละเอียดหมวดหมู่', 'active' => true),
				array('name' => 'ตั้งค่าการแสดงผล', 'active' => false, 'url' => $this->view->url(array('module' => 'rss', 'controller' => 'category', 'action' => 'setting', 'itemid' => $itemID), null, true)),
		);
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการหมวดหมู่ RSS', 'url' => $this->view->url ( array ('module' => 'rss', 'controller' => 'category', 'action' => 'index'), null, true)),
				array ('name' => 'ปรับปรุงหมวดหมู่ RSS') 
		);
		
		// Log Acion
		System_Model_Log::action("rss.category", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
		$canSave = $sUser->isAllow('rss.category.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Rss_Model_Category();
		$params = $this->getRequest ()->getParams ();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		$data = $model->update ( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("rss.category", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
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
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('rss.category.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest ()->getParams ();
		
		// Update
		$model = new Rss_Model_Category();
		$data = $model->delete ( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			
			// Log Acion
			System_Model_Log::action("rss.category", "delete",  $params["title"], $params["id"]);
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
		$canView = $sUser->isAllow("rss.category.view");
		$canEdit = $sUser->isAllow("rss.category.manage");
		if (!$canView) {
		   $this->_helper->redirector('index', 'category', 'rss');
		}
		
		$itemID = $this->getRequest ()->getParam ( 'itemid' );
		if (empty($itemID)) {
			$this->_helper->redirector('index', 'category', 'rss');
		}
		
		$model = new Rss_Model_Category();
		$dataInfo = $model->getInfo($itemID);
		$dataSettings = $model->getSettings($itemID);
		
		$data = json_decode($dataSettings->Settings, true);
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$dataDefault = new stdClass();
		$dataDefault->itemPerPage = System_Model_Setting::getValue("ItemPerPage", "Rss");
		$dataDefault->itemSortType = System_Model_Setting::getValue("ItemSortType", "Rss");
		$dataDefault->itemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Rss");
		$dataDefault->itemHtmlClass = System_Model_Setting::getValue("ItemHtmlClass", "Rss");
		$dataDefault->itemCustomCSS = System_Model_Setting::getValue("ItemCustomCSS", "Rss");

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
		$this->view->publicCommentOption = $this->publicCommentOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าการแสดงผล";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array (
				array('name' => 'รายละเอียดหมวดหมู่', 'active' => false, 'url' => $this->view->url(array('module' => 'rss', 'controller' => 'category', 'action' => 'edit', 'itemid' => $itemID), null, true)),
				array('name' => 'ตั้งค่าการแสดงผล', 'active' => true),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการหมวดหมู่ RSS', 'url' => $this->view->url(array('module' => 'rss', 'controller' => 'category', 'action' => 'index'), null, true)),
				array('name' => 'ปรับปรุงหมวดหมู่ RSS', 'url' => $this->view->url(array('module' => 'rss', 'controller' => 'category', 'action' => 'edit', "itemid" => $itemID), null, true)),
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
		$canSave = $sUser->isAllow('rss.category.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		// Model
		$model = new Rss_Model_Category();
		
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
		$params["ItemHtmlClassSetting"] = $requestParams['ItemHtmlClassSetting'];
		$params["ItemHtmlClass"] = $requestParams['ItemHtmlClass'];
		$params["ItemCSSSetting"] = $requestParams['ItemCSSSetting'];
		$params["ItemCustomCSS"] = $requestParams['ItemCustomCSS'];
		
		$settings = json_encode($params);
		
		// Save
		$data = $model->saveSetting($id, $settings);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
		} else {
			$result['result'] = false;
			$result['message'] = $data->message;
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
		$canSave = $sUser->isAllow('rss.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Rss_Model_Category();
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
		$canSave = $sUser->isAllow('rss.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Rss_Model_Category();
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
		$canSave = $sUser->isAllow('rss.category.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Rss_Model_Category();
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

