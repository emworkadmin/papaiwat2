<?php
class System_TemplateController extends Zend_Controller_Action {
	protected $isDefaultOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่' );
	protected $showFontOption = array ('Y' => 'แสดง', 'N' => 'ไม่แสดง' );
	protected $showVisitOption = array ('Y' => 'แสดง', 'N' => 'ไม่แสดง' );
	protected $showGoogle = array ('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน'  );
	protected $showTruehits = array ('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน' );
	protected $showCustomCSS = array ('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน' );
	protected $showCustomJavaScript = array ('Y' => 'ใช้ง่าน', 'N' => 'ไม่ใช้งาน' );
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
	protected $layoutBoxSizeOption = array ('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11', '12' => '12' );
	protected $themeOptionBak = array(
			'1' => array('name' => 'Theme1', 'boxBgColor' => '#26aae1', 'bgColor' => '#ffffff', 'textColor' => '#000000', 'linkColor' => '#ffffff', 'headerColor' => '#ffffff', 'footerColor' => '#ffffff'),
			'2' => array('name' => 'Theme2', 'boxBgColor' => '#3d449a', 'bgColor' => '#ffffff', 'textColor' => '#333333', 'linkColor' => '#ffffff', 'headerColor' => '#ffffff', 'footerColor' => '#ffffff'),
			'3' => array('name' => 'Theme3', 'boxBgColor' => '#2bb673', 'bgColor' => '#ffffff', 'textColor' => '#666666', 'linkColor' => '#ffffff', 'headerColor' => '#ffffff', 'footerColor' => '#ffffff'),
			'4' => array('name' => 'Theme4', 'boxBgColor' => '#eb4947', 'bgColor' => '#ffffff', 'textColor' => '#999999', 'linkColor' => '#ffffff', 'headerColor' => '#ffffff', 'footerColor' => '#ffffff'),
	);
	protected $themeOption = array('theme1', 'theme2', 'theme3', 'theme4');
	protected $bgRepeatOption = array('no-repeat' => 'No Repeat', 'repeat' => 'Repeat All', 'repeat-x' => 'Repeat Horizontally', 'repeat-y' => 'Repeat Vertically', 'inherit' => 'Inherit');
	protected $bgSizeOption = array('cover' => 'Cover', 'contain' => 'Contain', 'inherit' => 'Inherit');
	protected $bgAttachmentOption = array('fixed' => 'Fixed', 'scroll' => 'Scroll', 'inherit' => 'Inherit');
	protected $bgPositionOption = array(
			'0 0' => 'ซ้าย บน', '0 50%' => 'ซ้าย กึ่งกลาง', '0 100%' => 'ซ้าย ล่าง', 
			'50% 0' => 'ตรงกลาง บน', '50% 50%' => 'ตรงกลาง กึ่งกลาง', '50% 100%' => 'ตรงกลาง ล่าง',
			'100% 0' => 'ขวา บน', '100% 50%' => 'ขวา กึ่งกลาง', '100% 100%' => 'ขวา ล่าง'
	);
	protected $displayMenuGroupOption = array('menu_offcanvas' => 'Menu and Offcanvas', 'menu' => 'Menu Only', 'offcanvas' => 'Offcanvas Only');
	
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
		$canView = $sUser->isAllow("setting.template.manage");
		$canAdd = $sUser->isAllow("setting.template.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Send To View
		$this->view->canAdd = $canAdd;
		$this->view->canView = $canView;
		
		// Set View
		$this->view->pageName = "จัดการ Template";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url ( array ('module' => 'default','controller' => 'index','action' => 'index'), null, true )),
				array ('name' => 'จัดการ Template ')
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
		$canView = $sUser->isAllow("setting.template.manage");
		
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
		
		$searchNamespace = new Zend_Session_Namespace ( "SystemTemplateSearch" );
		$searchNamespace->searchText = $advancesearch ['searchText'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order [0] ['column'];
		$searchNamespace->tblOrderDir = $_order [0] ['dir'];
		
		$model = new System_Model_Template();
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
				$d ['IsDefault'] = Emwork_Utility::notShowNull ( $this->isDefaultOption [$item ["IsDefault"]] );
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
		$canView = $sUser->isAllow("setting.template.manage");
		$canAdd = $sUser->isAllow("setting.template.manage");		
		if (! $canAdd) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Get Option
		$modelMenuGroup = new System_Model_Menugroup();
		$groupMenu = $modelMenuGroup->getOption();
		
		
		// Send To View
		$this->view->canAdd = $canAdd;
		$this->view->canView = $canView;
		$this->view->groupMenu= Emwork_Utility::addEmptyOption($groupMenu);
		$this->view->showFontOption=  $this->showFontOption;
		$this->view->showVisitOption=  $this->showVisitOption;
		$this->view->isDefaultOption=  $this->isDefaultOption;
		$this->view->displayMenuGroupOption=  $this->displayMenuGroupOption;
		$this->view->bgRepeatOption = $this->bgRepeatOption;
		$this->view->bgSizeOption = $this->bgSizeOption;
		$this->view->bgAttachmentOption = $this->bgAttachmentOption;
		$this->view->bgPositionOption = $this->bgPositionOption;
		
		// Set View
		$this->view->pageName = "เพิ่ม Template";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก','url' => $this->view->url ( array ('module' => 'default','controller' => 'index','action' => 'index'), null, true )),
				array ('name' => 'จัดการ Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'index'), null, true )),
				array ('name' => 'เพิ่ม Template ')
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
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow("setting.template.manage");
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$params = $this->getRequest ()->getParams ();
		
		$model = new System_Model_Template();
		$modelFile = new System_Model_Fileinfo();
		
		// Prepare
		$faviconId= null;
		if (count($params['hdfFavicon'])) {
			unset($fileData);
			$fileData = $modelFile->itemByHashName($params['hdfFavicon'][0]);
			if ($fileData->ID != null) {
				$faviconId= $fileData->ID;
			}
		}
		$logoId= null;
		if (count($params['hdflogo'])) {
			unset($fileData);
			$fileData = $modelFile->itemByHashName($params['hdflogo'][0]);
			if ($fileData->ID != null) {
				$logoId= $fileData->ID;
			}
		}
		$bgId= null;
		if (count($params['hdfbackground'])) {
			unset($fileData);
			$fileData = $modelFile->itemByHashName($params['hdfbackground'][0]);
			if ($fileData->ID != null) {
				$bgId= $fileData->ID;
			}
		}
		$params['faviconId'] = $faviconId;
		$params['logoId'] = $logoId;
		$params['bgId'] = $bgId;
		
		
		$data= $model->save($params);
		
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
		$canView = $sUser->isAllow("setting.template.manage");
		$canEdit = $sUser->isAllow("setting.template.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Get Param
		$id = $this->getRequest()->getParam("itemid");
		
		// Get Option
		$model = new System_Model_Template();
		$data = $model->getInfoByID($id);
		
		if ($data === false || empty($data->ID)) {
			$this->_helper->redirector ( 'index', 'template', 'system' );
		}
		
		// Get Option
		$modelMenuGroup = new System_Model_Menugroup();
		$groupMenu = $modelMenuGroup->getOption();
		
		// Prepare Favicon Image
		$faviconImage = array();
		if(!empty($data->FaviconImageHashName)){
			$faviconImage[] = array(
					"file_name" => $data->FaviconImageName,
					"size" => $data->FaviconImageFileSize,
					"type" => $data->FaviconImageFileType,
					"hash_name" => $data->FaviconImageHashName,
			);
			$data->faviconImage= $faviconImage;
		} else {
			$data->faviconImage= null;
		}
		$logoImage = array();
		if(!empty($data->LogoImageHashName)){
			$logoImage[] = array(
					"file_name" => $data->LogoImageName,
					"size" => $data->LogoImageFileSize,
					"type" => $data->LogoImageFileType,
					"hash_name" => $data->LogoImageHashName,
			);
			$data->logoImage= $logoImage;
		} else {
			$data->logoImage= null;
		}
		$bgImage = array();
		if(!empty($data->BackgroundImageHashName)){
			$bgImage[] = array(
					"file_name" => $data->BackgroundImageName,
					"size" => $data->BackgroundImageFileSize,
					"type" => $data->BackgroundImageFileType,
					"hash_name" => $data->BackgroundImageHashName,
			);
			$data->bgImage= $bgImage;
		} else {
			$data->bgImage= null;
		}
		
		// Send To View
		$this->view->canEdit = $canEdit;
		$this->view->canView = $canView;
		$this->view->groupMenu= Emwork_Utility::addEmptyOption($groupMenu);
		$this->view->showFontOption=  $this->showFontOption;
		$this->view->showVisitOption=  $this->showVisitOption;
		$this->view->isDefaultOption=  $this->isDefaultOption;
		$this->view->displayMenuGroupOption=  $this->displayMenuGroupOption;
		$this->view->bgRepeatOption = $this->bgRepeatOption;
		$this->view->bgSizeOption = $this->bgSizeOption;
		$this->view->bgAttachmentOption = $this->bgAttachmentOption;
		$this->view->bgPositionOption = $this->bgPositionOption;
		$this->view->data=  $data;
		
		// Set View
		$this->view->pageName = "ปรับปรุง Template";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = $this->subMenu('edit', $id);
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก','url' => $this->view->url ( array ('module' => 'default','controller' => 'index','action' => 'index'), null, true )),
				array ('name' => 'จัดการ Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'index'), null, true )),
				array ('name' => 'ปรับปรุง Template ')
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
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.template.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$params = $this->getRequest ()->getParams ();
		
		$model = new System_Model_Template();
		$modelFile = new System_Model_Fileinfo();
		
		// Prepare
		$faviconId= null;
		if (count($params['hdfFavicon'])) {
			unset($fileData);
			$fileData = $modelFile->itemByHashName($params['hdfFavicon'][0]);
			if ($fileData->ID != null) {
				$faviconId= $fileData->ID;
			}
		}
		$logoId= null;
		if (count($params['hdflogo'])) {
			unset($fileData);
			$fileData = $modelFile->itemByHashName($params['hdflogo'][0]);
			if ($fileData->ID != null) {
				$logoId= $fileData->ID;
			}
		}
		$bgId= null;
		if (count($params['hdfbackground'])) {
			unset($fileData);
			$fileData = $modelFile->itemByHashName($params['hdfbackground'][0]);
			if ($fileData->ID != null) {
				$bgId= $fileData->ID;
			}
		}
		$params['faviconId'] = $faviconId;
		$params['logoId'] = $logoId;
		$params['bgId'] = $bgId;
		
		
		$data= $model->update($params );
		
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

	public function codingAction() {
		// Log Access
		System_Model_Log::access ();
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow("setting.template.manage");
		$canEdit = $sUser->isAllow("setting.template.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Get Param
		$id = $this->getRequest()->getParam("itemid");
		
		// Get Option
		$model = new System_Model_Template();
		$data = $model->getCoding($id);
		
		if ($data === false || empty($data->ID)) {
			$this->_helper->redirector ( 'index', 'template', 'system' );
		}
		
		// Send To View
		$this->view->canEdit = $canEdit;
		$this->view->canView = $canView;
		$this->view->showGoogle=  $this->showGoogle;
		$this->view->showTruehits=  $this->showTruehits;
		$this->view->showCustomCSS=  $this->showCustomCSS;
		$this->view->showCustomJavaScript=  $this->showCustomJavaScript;
		$this->view->data=  $data;
		
		// Set View
		$this->view->pageName = "จัดการ Template";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = $this->subMenu('coding', $id);
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก','url' => $this->view->url ( array ('module' => 'default','controller' => 'index','action' => 'index'), null, true )),
				array ('name' => 'จัดการ Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'index'), null, true )),
				array ('name' => 'ปรับปรุง Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'edit', 'itemid' => $id), null, true )),
				array ('name' => 'ปรับแต่งโค้ด')
		);
	}
	

	public function updatecodingAction() {
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
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.template.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$model = new System_Model_Template();
		$params = $this->getRequest ()->getParams ();
		
		$data = $model->updateCoding( $params );
		
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
	
	public function layoutAction() {
		// Log Access
		System_Model_Log::access ();
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow("setting.template.manage");
		$canEdit = $sUser->isAllow("setting.template.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Get Param
		$id = $this->getRequest()->getParam("itemid");
		
		// Get Option
		$model = new System_Model_Template();
		$data = $model->getLayout($id);
		
		if ($data === false || empty($data->ID)) {
			$this->_helper->redirector ( 'index', 'template', 'system' );
		}
		
		// Set BgImage Info to $data
		if (!empty($data->LayoutParams)) {
			$temp = json_decode($this->data->LayoutParams, true);
		}
		
		// Send To View
		$this->view->canEdit = $canEdit;
		$this->view->layoutPositionOption = $this->layoutPositionOption;
		$this->view->layoutBoxSizeOption = $this->layoutBoxSizeOption;
		$this->view->bgRepeatOption = $this->bgRepeatOption;
		$this->view->bgSizeOption = $this->bgSizeOption;
		$this->view->bgAttachmentOption = $this->bgAttachmentOption;
		$this->view->bgPositionOption = $this->bgPositionOption;
		$this->view->canView = $canView;
		$this->view->data=  $data;
		
		// Set View
		$this->view->pageName = "จัดการ Template";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = $this->subMenu('layout', $id);
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก','url' => $this->view->url ( array ('module' => 'default','controller' => 'index','action' => 'index'), null, true )),
				array ('name' => 'จัดการ Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'index'), null, true )),
				array ('name' => 'ปรับปรุง Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'edit', 'itemid' => $id), null, true )),
				array ('name' => 'ปรับโครงสร้าง')
		);
	}
	
	
	public function updatelayoutAction() {
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
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.template.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$fileModel = new System_Model_Fileinfo();
		$model = new System_Model_Template();
		$params = $this->getRequest ()->getParams ();
		
		// TODO: Check Value
		
		// TODO: prepare BgImage
		if (count($params["hdfsection"]) > 0) {
			foreach ($params["hdfsection"] as $gid => $val) {
				if (isset($val["bgImage"]) && count($val["bgImage"]) > 0) {
					foreach ($val["bgImage"] as $bgImageHashName) {
						unset($fileData);
						$fileData = $fileModel->itemByHashName($bgImageHashName);
						if ($fileData->ID != null) {
							$params['section'][$gid]['bgImage'] = array(
									"fileID" => $fileData->ID,
									"fileName" => $fileData->Name,
									"fileHashName" => $fileData->HashName,
									"fileType" => $fileData->FileType,
							);
						}
					}
				}
			}
		}
		
		$data = $model->updateLayout( $params );
		
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
	
	public function themeAction() {
		// Log Access
		System_Model_Log::access ();
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow("setting.template.manage");
		$canEdit = $sUser->isAllow("setting.template.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Get Param
		$id = $this->getRequest()->getParam("itemid");
		
		// Get Option
		$model = new System_Model_Template();
		$data = $model->getTheme($id);
		
		if ($data === false || empty($data->ID)) {
			$this->_helper->redirector ( 'index', 'template', 'system' );
		}
		
		$data->themeSelected = $this->themeOption[0];
		if (!empty($data->ThemeParams)) {
			$themeParams = json_decode($data->ThemeParams, true);
			$data->themeSelected = $themeParams["themeSelected"];
			$data->themes = $themeParams["themes"];
		}
		
		// Send To View
		$this->view->canEdit = $canEdit;
		$this->view->canView = $canView;
		$this->view->themeOption = $this->themeOption;
		$this->view->data = $data;
		
		// Set View
		$this->view->pageName = "จัดการ Template";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = $this->subMenu('theme', $id);
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก','url' => $this->view->url ( array ('module' => 'default','controller' => 'index','action' => 'index'), null, true )),
				array ('name' => 'จัดการ Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'index'), null, true )),
				array ('name' => 'ปรับปรุง Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'edit', 'itemid' => $id), null, true )),
				array ('name' => 'ธีม')
		);
	}
	
	public function updatethemeAction() {
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
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.template.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$model = new System_Model_Template();
		$params = $this->getRequest ()->getParams ();
		
		// TODO: Check Value
		
		$data = $model->updateTheme( $params );
		
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
	
	
	public function assignmenuAction() {
		// Log Access
		System_Model_Log::access ();
		
		/// Check Auth
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow("setting.template.manage");
		$canEdit = $sUser->isAllow("setting.template.manage");		
		if (! $canView) {
			$this->_helper->redirector ( 'index', 'index', 'default' );
		}
		
		// Get Param
		$id = $this->getRequest()->getParam("itemid");
		
		// Get Option
		$model = new System_Model_Template();
		$data = $model->getInfoByID($id);
		
		if ($data === false || empty($data->ID)) {
			$this->_helper->redirector ( 'index', 'template', 'system' );
		}
		
		$data->MenuIds = array();
		$dataMenus = $model->getMenus($id);
		
		if (count($dataMenus) > 0) {
			foreach ($dataMenus as $dataMenu) {
				$data->MenuIds[] = $dataMenu["ID"];
			}
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// MenuGroup & MenuOption
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
		
		// Send To View
		$this->view->canEdit = $canEdit;
		$this->view->canView = $canView;
		$this->view->menuGroups = $menuGroups;
		$this->view->data = $data;
		
		// Set View
		$this->view->pageName = "กำหนดเมนู";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = $this->subMenu('assignmenu', $id);
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก','url' => $this->view->url ( array ('module' => 'default','controller' => 'index','action' => 'index'), null, true )),
				array ('name' => 'จัดการ Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'index'), null, true )),
				array ('name' => 'ปรับปรุง Template','url' => $this->view->url ( array ('module' => 'system', 'controller' => 'template','action' => 'edit', 'itemid' => $id), null, true )),
				array ('name' => 'กำหนดเมนู')
		);
	}
	
	public function updateassignmenuAction() {
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
		
		// Check Action Rights
		$canSave = $sUser->isAllow('setting.template.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$model = new System_Model_Template();
		$params = $this->getRequest ()->getParams ();
		
		// TODO: Check Value
		
		$data = $model->updateAssignMenu( $params );
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
		} else {
			$result ['result'] = false;
			$result ['message'] = $data->message; //'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}
	
	
	
	// --- Generate Sub Menu
	private function subMenu($activeAction, $id) {
		$subMenus = array();
		if ($activeAction == 'edit') {
			$subMenus[] = array('name' => 'ค่าพื้นฐาน', 'active' => true);
		} else {
			$subMenus[] = array('name' => 'ค่าพื้นฐาน', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'template', 'action' => 'edit', 'itemid' => $id), null, true));
		}
		if ($activeAction == 'coding') {
			$subMenus[] = array('name' => 'ปรับแต่งโค้ด', 'active' => true);
		} else {
			$subMenus[] = array('name' => 'ปรับแต่งโค้ด', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'template', 'action' => 'coding', 'itemid' => $id), null, true));
		}
		if ($activeAction == 'layout') {
			$subMenus[] = array('name' => 'ปรับโครงสร้าง', 'active' => true);
		} else {
			$subMenus[] = array('name' => 'ปรับโครงสร้าง', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'template', 'action' => 'layout', 'itemid' => $id), null, true));
		}
// 		if ($activeAction == 'custom') {
// 			$subMenus[] = array('name' => 'ปรับไฟล์', 'active' => true);
// 		} else {
// 			$subMenus[] = array('name' => 'ปรับไฟล์', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'template', 'action' => 'custom', 'itemid' => $id), null, true));
// 		}
		if ($activeAction == 'theme') {
			$subMenus[] = array('name' => 'ธีม', 'active' => true);
		} else {
			$subMenus[] = array('name' => 'ธีม', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'template', 'action' => 'theme', 'itemid' => $id), null, true));
		}
		if ($activeAction == 'assignmenu') {
			$subMenus[] = array('name' => 'กำหนดเมนู', 'active' => true);
		} else {
			$subMenus[] = array('name' => 'กำหนดเมนู', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'template', 'action' => 'assignmenu', 'itemid' => $id), null, true));
		}
		return $subMenus;
	}
	
}

