<?php

class Orgchart_chartController extends Zend_Controller_Action
{
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');

	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $defaultCustomizeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
	protected $targetOption = array ('_blank' => '_blank', '_self' => '_self');

	public function initAction()
	{

	}

	public function indexAction()
	{
        // Log Access
        System_Model_Log::access();
        
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('orgchart.chart.view');
		$canAdd = $sUser->isAllow('orgchart.chart.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}

        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("OrgchartChartSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("OrgchartChartSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
            $InfoSearch->isenable = $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
            $InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;

        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("OrgchartChartSearch");
        }		

		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
        // Get Option
        $modelOption = new Content_Model_Category();
        $contentCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->publishOption = $this->publishOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการแผนผัง";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแผนผัง')
        );		
	}

    public function getlistAction()
    {
        $filterInt = new Zend_Filter_Int();
        $canView = true;
        $responce = array();
        $responce['draw'] = $_draw;
        $responce['recordsTotal'] = 0;
        $responce['recordsFiltered'] = 0;
        $responce['data'] = array();

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->json($responce);
		}
		
		$canView = $sUser->isAllow('orgchart.chart.view');

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
        $textstr = $this->getRequest()->getParam('textstr');
        $textstr = $textstr;
        $filter = ''; //$this->getRequest()->getParam('filter');
        
        //Advance Search
        $advancesearch['searchText'] = $textstr;
        $advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');

        $infoSearchNamespace = new Zend_Session_Namespace("OrgchartChartSearch");
        $infoSearchNamespace->textsearch = $advancesearch['searchText'];
        $infoSearchNamespace->isenable = $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];

		if($canView):
			$model = new Orgchart_Model_Chart();
			$data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
		endif;
        
        $i=$_start;
        if ($canView && $data) {
            $responce['recordsTotal'] = $data[0]["RECORD"];
            $responce['recordsFiltered'] = $data[0]["RECORD"];
            
            foreach($data as $item){
                $i++;
                $d = array();
                $d['DT_RowId'] = $item["ID"];
                $d['NO'] = $i;
                $d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
                $d['Ordering'] = Emwork_Utility::notShowNull($item["Ordering"], '-');
                $d['IsEnableCode'] = $item["IsEnable"];
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->publishOption[$item["IsEnable"]]);
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);        
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
		$canAdd = $sUser->isAllow('orgchart.chart.manage');
		
		if (!$canAdd) {
		   $this->_helper->redirector('index', 'chart', 'orgchart');
		}
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		
		// Set View
		$this->view->pageName = "เพิ่มแผนผัง";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
			array ('name' => 'หน้าแรก', 'url' => $this->view->url ( array ('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
			array ('name' => 'รายการแผนผัง','url' => $this->view->url ( array ('module' => 'orgchart', 'controller' => 'chart', 'action' => 'index'), null, true )),
			array ('name' => 'เพิ่มแผนผัง') 
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
		$canAdd = $sUser->isAllow('orgchart.chart.manage');
		if (!$canAdd) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Orgchart_Model_Chart();
		$params = $this->getRequest ()->getParams ();
		
		$fileModel = new System_Model_Fileinfo();
		// Prepare Intro Image
		$introImageID = null;
		if (count($params['hdfIntroImage'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
			if ($fileData->ID != null) {
				$introImageID = $fileData->ID;
			}
		}
		$params['IntroImageID'] = $introImageID;
		
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
		$data = $model->save($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("orgchart.chart", "save", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json( $result );
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
		$canDelete = $sUser->isAllow('orgchart.chart.manage');
		$canEdit = $sUser->isAllow('orgchart.chart.manage');
		$canView = $sUser->isAllow('orgchart.chart.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'chart', 'orgchart');
		}
		
		// Get Param
		$itemID = $this->_request->getParam('itemid');
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'chart', 'orgchart');
		}
		
		$model = new Orgchart_Model_Chart();
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		// Get Data
		$data = $model->getInfo($itemID);
        
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
		
		// Log Acion
		System_Model_Log::action("orgchart.chart", "view", $dataDetails[$defaultLanguage['Code']]->Title, $itemID);

		// Set View
		$this->view->pageName = "ปรับปรุงแผนผัง";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array (
			array('name' => 'รายละเอียดแผนผัง', 'active' => true),
			array('name' => 'จัดการแผนผัง', 'active' => false, 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'editchart', 'itemid' => $itemID), null, true)),
			array('name' => 'ตั้งค่าการแสดงผล', 'active' => false, 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'setting', 'itemid' => $itemID), null, true)),
		);		
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการแผนผัง', 'url' => $this->view->url ( array ('module' => 'orgchart', 'controller' => 'chart', 'action' => 'index'), null, true)),
				array ('name' => 'ปรับปรุงแผนผัง') 
		);

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
		$canSave = $sUser->isAllow('orgchart.chart.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Orgchart_Model_Chart();
		$params = $this->getRequest ()->getParams ();
		
		$fileModel = new System_Model_Fileinfo();
		// Prepare Intro Image
		$introImageID = null;
		if (count($params['hdfIntroImage'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
			if ($fileData->ID != null) {
				$introImageID = $fileData->ID;
			}
		}
		$params['IntroImageID'] = $introImageID;
		
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
		$data = $model->update($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("orgchart.chart", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json( $result );
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
		$canSave = $sUser->isAllow('orgchart.chart.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest()->getParams();

		// Update
		$model = new Orgchart_Model_Chart();
		$data = $model->delete($params);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$model = new Orgchart_Model_Chart();
			$data = $model->getDataByLanguage($params["id"], $defaultLanguage['Code']);
			System_Model_Log::action("orgchart.chart", "delete", $data->Title, $params["id"]);
		} else {
			$result['result'] = false;
			$result['message'] = 'ลบไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
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
		$canView = $sUser->isAllow("orgchart.chart.view");
		$canEdit = $sUser->isAllow("orgchart.chart.manage");
		if (!$canView) {
		   $this->_helper->redirector('index', 'chart', 'orgchart');
		}
		
		$itemID = $this->getRequest()->getParam ('itemid');
		if (empty($itemID)) {
			$this->_helper->redirector('index', 'chart', 'orgchart');
		}
		
		$model = new Orgchart_Model_Chart();
		$dataInfo = $model->getInfo($itemID);
		$dataSettings = $model->getSettings($itemID);
		
		$data = json_decode($dataSettings->Settings, true);


		$model = new System_Model_Setting();
		
		// Get Data
		$dataDefault = new stdClass();
		$dataDefault->PersonShowImage = System_Model_Setting::getValue("PersonShowImage", "OrgChart");
		$dataDefault->PersonShowTitle = System_Model_Setting::getValue("PersonShowTitle", "OrgChart");
		$dataDefault->PersonShowJob = System_Model_Setting::getValue("PersonShowJob", "OrgChart");
		$dataDefault->PersonShowWorkgroup = System_Model_Setting::getValue("PersonShowWorkgroup", "OrgChart");
		$dataDefault->PersonShowDepartment = System_Model_Setting::getValue("PersonShowDepartment", "OrgChart");
		$dataDefault->PersonShowLink = System_Model_Setting::getValue("PersonShowLink", "OrgChart");
		$dataDefault->PersonLinkTarget = System_Model_Setting::getValue("PersonLinkTarget", "OrgChart");
		$dataDefault->PersonVerticalLevel = System_Model_Setting::getValue("PersonVerticalLevel", "OrgChart");
		$dataDefault->PersonVisibleLevel = System_Model_Setting::getValue("PersonVisibleLevel", "OrgChart");

		// Send Data to View
		$this->view->canEdit = $canEdit;
		$this->view->dataInfo = $dataInfo;
		$this->view->data = $data;
		$this->view->dataDefault = $dataDefault;

		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->targetOption = $this->targetOption;
		$this->view->defaultCustomizeOption = $this->defaultCustomizeOption;
				
		// Set View
		$this->view->pageName = "ตั้งค่าการแสดงผล";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array (
				array('name' => 'รายละเอียดแผนผัง', 'active' => false, 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'edit', 'itemid' => $itemID), null, true)),
				array('name' => 'จัดการแผนผัง', 'active' => false, 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'editchart', 'itemid' => $itemID), null, true)),
				array('name' => 'ตั้งค่าการแสดงผล', 'active' => true),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการแผนผัง', 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'index'), null, true)),
				array('name' => 'ปรับปรุงแผนผัง', 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'edit', "itemid" => $itemID), null, true)),
				array('name' => 'ตั้งค่าการแสดงผล')
		);
	}

	public function settingsaveAction()
	{
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
		$canSave = $sUser->isAllow("orgchart.chart.manage");
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}

		// Model
		$model = new Orgchart_Model_Chart();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		$id = $requestParams["hdfID"];

		// Set Params
		$params = array();
		$params['PersonShowImageSetting'] = $requestParams['PersonShowImageSetting'];
		$params['PersonShowImage'] = $requestParams['PersonShowImage'];
		$params['PersonShowTitleSetting'] = $requestParams['PersonShowTitleSetting'];
		$params['PersonShowTitle'] = $requestParams['PersonShowTitle'];
		$params['PersonShowJobSetting'] = $requestParams['PersonShowJobSetting'];
		$params['PersonShowJob'] = $requestParams['PersonShowJob'];
		$params['PersonShowWorkgroupSetting'] = $requestParams['PersonShowWorkgroupSetting'];
		$params['PersonShowWorkgroup'] = $requestParams['PersonShowWorkgroup'];
		$params['PersonShowDepartmentSetting'] = $requestParams['PersonShowDepartmentSetting'];
		$params['PersonShowDepartment'] = $requestParams['PersonShowDepartment'];
		$params['PersonShowLinkSetting'] = $requestParams['PersonShowLinkSetting'];
		$params['PersonShowLink'] = $requestParams['PersonShowLink'];
		$params['PersonLinkTarget'] = $requestParams['PersonLinkTarget'];
		$params['PersonLevelSetting'] = $requestParams['PersonLevelSetting'];
		$params['PersonVerticalLevel'] = $requestParams['PersonVerticalLevel'];
		$params['PersonVisibleLevel'] = $requestParams['PersonVisibleLevel'];
		$params['PersonCustomCss'] = $requestParams['PersonCustomCss'];
		$params['PersonDetailCustomCss'] = $requestParams['PersonDetailCustomCss'];
		
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

	/*********** Chart ************/
	public function editchartAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		$canDelete = $sUser->isAllow('orgchart.chart.manage');
		$canEdit = $sUser->isAllow('orgchart.chart.manage');
		$canView = $sUser->isAllow('orgchart.chart.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'chart', 'orgchart');
		}

		$itemID = $this->getRequest()->getParam ('itemid');
		if (empty($itemID)) {
			$this->_helper->redirector('index', 'chart', 'orgchart');
		}

		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		$model = new Orgchart_Model_Chart();
		$data = $model->getInfo($itemID);
		$detail = $model->getDataByLanguage($data->ID, $defaultLanguage['Code']);
		$chartDetail = $model->getChartDetail($itemID);

		$dataChart = new stdClass();
		$dataObj = null;
		if($chartDetail->ChartDetail) {
			$dataObj = json_decode($chartDetail->ChartDetail, true);

			if($dataObj['id'][0] == 'w') {
				$dataObj['id'] = substr($dataObj['id'],1);
				$modelWorkgroup = new Orgchart_Model_Workgroup();
				$dataWorkgroup = $modelWorkgroup->getDataByLanguage($dataObj['id'], $defaultLanguage['Code']);
				$imageUrl = "";
				if (!empty($dataWorkgroup->IntroImageHashName)) {
					$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataWorkgroup->IntroImageHashName), null, true);
				}
				$title = '';
				if($imageUrl!="") {
					$title .= '<img src="' . $imageUrl . '">';
				}
				$title .= '<div class="id" style="display:none;">w' . $dataWorkgroup->ID . '</div>';
				$title .= '<div>' . $dataWorkgroup->Title . '</div>';
				$dataChart->id = $dataWorkgroup->ID;
				$dataChart->title = $title;
			} else {
				$modelPerson = new Orgchart_Model_Person();
				$dataObj['id'] = substr($dataObj['id'],1);
				$dataPerson = $modelPerson->getDataByLanguage($dataObj['id'], $defaultLanguage['Code']);
				$imageUrl = $this->view->baseUrl("images/icons/no-image.jpg");
				if (!empty($dataPerson->IntroImageHashName)) {
					$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataPerson->IntroImageHashName), null, true);
				}
				$title = '';
				if($imageUrl!="") {
					$title .= '<img src="' . $imageUrl . '">';
				}
				$title .= '<div class="id" style="display:none;">p' . $dataPerson->ID . '</div>';
				$title .= '<div>' . $dataPerson->Title . '</div>';
				$title .= '<div>ตำแหน่ง ' . $dataPerson->Job . '</div>';
				$title .= '<div>กลุ่มงาน ' . $dataPerson->WorkgroupName . '</div>';
				$title .= '<div>หน่วยงานที่สังกัด ' . $dataPerson->Department . '</div>';
				$dataChart->id = $dataPerson->ID;
				$dataChart->title = $title;
			}

			if(count($dataObj['children'])>0) {
				foreach($dataObj['children'] as $k1 => $v1) {
					$dataChart->children[] = $this->getChildren($v1, $defaultLanguage['Code']);
				}
			}
		}

		$modelWorkgroup = new Orgchart_Model_Workgroup();
		$workgroupOption = $modelWorkgroup->getOption($defaultLanguage['Code']);

		$this->view->workgroupOption = $workgroupOption;

		$this->view->data = $data;
		$this->view->detail = $detail;
		$this->view->dataChart = $dataChart;

		$this->view->canEdit = $canEdit;
		$this->view->canDelete = $canDelete;
		
		// Set View
		$this->view->pageName = "จัดการแผนผัง";
		$this->view->pageIcon= "fa fa-sitemap";
		$this->view->subMenus = array (
				array('name' => 'รายละเอียดแผนผัง', 'active' => false, 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'edit', 'itemid' => $itemID), null, true)),
				array('name' => 'จัดการแผนผัง', 'active' => true),
				array('name' => 'ตั้งค่าการแสดงผล', 'active' => false, 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'setting', 'itemid' => $itemID), null, true))
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการแผนผัง', 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'index'), null, true)),
				array('name' => 'ปรับปรุงแผนผัง', 'url' => $this->view->url(array('module' => 'orgchart', 'controller' => 'chart', 'action' => 'edit', "itemid" => $itemID), null, true)),
				array('name' => 'จัดการแผนผัง')
		);
		System_Model_Log::action("orgchart.chart", "view", $detail->Title, $data->ID);
	}

	private function getChildren($dataObj, $lang = 'th')
	{
		$dataChart = new stdClass();
		if($dataObj['id'][0] == 'w') {
			$dataObj['id'] = substr($dataObj['id'],1);
			$modelWorkgroup = new Orgchart_Model_Workgroup();
			$dataWorkgroup = $modelWorkgroup->getDataByLanguage($dataObj['id'], $lang);
			$imageUrl = "";
			if (!empty($dataWorkgroup->IntroImageHashName)) {
				$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataWorkgroup->IntroImageHashName), null, true);
			}
			$title = '';
			if($imageUrl!="") {
				$title .= '<img src="' . $imageUrl . '">';
			}
			$title .= '<div class="id" style="display:none;">w' . $dataWorkgroup->ID . '</div>';
			$title .= '<div>' . $dataWorkgroup->Title . '</div>';
			$dataChart->id = $dataWorkgroup->ID;
			$dataChart->title = $title;
		} else {
			$modelPerson = new Orgchart_Model_Person();
			$dataObj['id'] = substr($dataObj['id'],1);
			$dataPerson = $modelPerson->getDataByLanguage($dataObj['id'], $lang);
			$imageUrl = "";
			if (!empty($dataPerson->IntroImageHashName)) {
				$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $dataPerson->IntroImageHashName), null, true);
			}
			$title = '';
			if($imageUrl!="") {
				$title .= '<img src="' . $imageUrl . '">';
			}
			$title .= '<div class="id" style="display:none;">p' . $dataPerson->ID . '</div>';
			$title .= '<div>' . $dataPerson->Title . '</div>';
			$title .= '<div>ตำแหน่ง ' . $dataPerson->Job . '</div>';
			$title .= '<div>กลุ่มงาน ' . $dataPerson->WorkgroupName . '</div>';
			$title .= '<div>หน่วยงานที่สังกัด ' . $dataPerson->Department . '</div>';
			$dataChart->id = $dataPerson->ID;
			$dataChart->title = $title;
		}

		if(count($dataObj['children'])>0) {
			foreach($dataObj['children'] as $k1 => $v1) {
				$dataChart->children[] = $this->getChildren($v1, $lang);
			}
		}
		return $dataChart;
	}

	public function persondetailAction()
	{

		$result = array ();
		$result['result'] = false;
		$result['html'] = "";

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}

		// Check Action Rights
		$canAdd = $sUser->isAllow('orgchart.chart.manage');

		if($canAdd):
			$itemID = $this->getRequest()->getParam('itemid');

			$defaultLanguage = System_Model_Setting::getDefaultLanguage();

			$model = new Orgchart_Model_Person();
			$data = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);

			$imageUrl = "";
			if (!empty($data->IntroImageHashName)) {
				$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $data->IntroImageHashName), null, true);
			}

			$title = '';
			if($imageUrl!="") {
				$title .= '<img src="' . $imageUrl . '">';
			}
			$title .= '<div class="id" style="display:none;">p' . $data->ID . '</div>';
			$title .= '<div>' . $data->Title . '</div>';
			$title .= '<div>ตำแหน่ง ' . $data->Job . '</div>';
			$title .= '<div>กลุ่มงาน ' . $data->WorkgroupName . '</div>';
			$title .= '<div>หน่วยงานที่สังกัด ' . $data->Department . '</div>';

			$html = $title;

			$result['result'] = true;
			$result['html'] = $html;
		endif;
		$this->_helper->json($result);
	}

	public function groupdetailAction()
	{
		$result = array ();
		$result['result'] = false;
		$result['html'] = "";

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}

		// Check Action Rights
		$canAdd = $sUser->isAllow('orgchart.chart.manage');
		
		if($canAdd):
			$itemID = $this->getRequest()->getParam('itemid');

			$defaultLanguage = System_Model_Setting::getDefaultLanguage();

			$model = new Orgchart_Model_Workgroup();
			$data = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);

			$imageUrl = "";
			if (!empty($data->IntroImageHashName)) {
				$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $data->IntroImageHashName), null, true);
			}

			$title = '';
			if($imageUrl!="") {
				$title .= '<img src="' . $imageUrl . '">';
			}
			$title .= '<div class="id" style="display:none;">w' . $data->ID . '</div>';
			$title .= '<div>' . $data->Title . '</div>';

			$html = $title;

			$result['result'] = true;
			$result['html'] = $html;
		endif;

		$this->_helper->json($result);
	}

	public function updatechartAction()
	{
		// Init
		$result = array ();
		$result ['result'] = false;
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
		$canSave = $sUser->isAllow('orgchart.chart.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}

		// Param
		$params = $this->getRequest()->getParams();
		
		// Update
		$model = new Orgchart_Model_Chart();
		$data = $model->updatechart($params);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$model = new Orgchart_Model_Chart();
			$data = $model->getDataByLanguage($params["id"], $defaultLanguage['Code']);
			System_Model_Log::action("orgchart.chart", "update", $data->Title, $params["id"]);
		} else {
			$result['result'] = false;
			$result['message'] = 'ปรับปรุงแผนผัง';
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
		$canSave = $sUser->isAllow('orgchart.chart.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Orgchart_Model_Chart();
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
		$canSave = $sUser->isAllow('orgchart.chart.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Orgchart_Model_Chart();
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
		$canSave = $sUser->isAllow('orgchart.chart.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Orgchart_Model_Chart();
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
?>