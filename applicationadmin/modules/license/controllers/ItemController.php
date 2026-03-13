<?php
class License_ItemController extends Zend_Controller_Action {
    
    protected $publishOption = array (
        'Y' => 'เผยแพร่',
        'N' => 'ไม่เผยแพร่' 
	);
	protected $urlTypeOption = array (
        'D' => 'ค่าพื้นฐาน',
        'E' => 'ลิงค์ระบบภายนอก' 
    );

    public function init()
    {

    }

    public function indexAction()
    {
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
		$canView = $sUser->isAllow('license.item.view');
		$canAdd = $sUser->isAllow('license.item.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset("LicenseItemIndexSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace("LicenseItemIndexSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->category= $infoSearchNamespace->category;
			$InfoSearch->issue= $infoSearchNamespace->issue;
			$InfoSearch->department= $infoSearchNamespace->department;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			Zend_Session::namespaceUnset ( "LicenseItemIndexSearch" );
		}
		$this->view->InfoSearch = $InfoSearch;

        
        $languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
				
        // Get Option
        $modelOption = new License_Model_Category();
		$CategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
		$modelDept = new License_Model_Department();
		$DepartmentOption = $modelDept->getOption($defaultLanguage["Code"]);
		        
		// Send To View
		$this->view->publishOption = $this->publishOption;
		$this->view->categoryOption = $CategoryOption;
		$this->view->departmentOption = $DepartmentOption;
		
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการใบอนุญาต";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ( 'name' => 'หน้าแรก', 'url' => $this->view->url ( array ( 'module' => 'default', 'controller' => 'index', 'action' => 'index'  ), null, true)), 
				array ( 'name' => 'รายการใบอนุญาต'  ) 
        ); 
	}
	
	public function getlistAction() 
	{
		
		$filterInt = new Zend_Filter_Int ();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->json($responce);
		}
		$canView = $sUser->isAllow('license.item.view');
		
		
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
		$advancesearch['searchText'] = $textstr;
		$advancesearch['isCat'] = $this->getRequest()->getParam('isCat');
		$advancesearch['isIssue'] = $this->getRequest ()->getParam ( 'isIssue' );
		$advancesearch['isDepartment'] = $this->getRequest ()->getParam ( 'isDepartment' );
		$advancesearch['isEnable'] = $this->getRequest ()->getParam ( 'isEnable' );
		
		$searchNamespace = new Zend_Session_Namespace("LicenseItemIndexSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->category = $advancesearch['isCat'];
		$searchNamespace->issue = $advancesearch['isIssue'];
		$searchNamespace->department = $advancesearch['isDepartment'];
		$searchNamespace->isEnable = $advancesearch['isEnable'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];
		
		$data = array();
		if($canView) :
			$model = new License_Model_Item();
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
				$d['DT_RowId'] = $item ["ID"];
				$d['NO'] = $i;
				//$d['Ordering'] = Emwork_Utility::notShowNull($item["Ordering"]);
				$d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
				$d['CategoryTitle'] = Emwork_Utility::notShowNull($item["CategoryTitle"]);
				$d['IssueName'] = Emwork_Utility::notShowNull($item["IssueName"]);
				$d['DepartmentName'] = Emwork_Utility::notShowNull($item["DepartmentName"]);
				$d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["Created"], 'DT'));
				$d['IsEnableCode'] = $item["IsEnable"];
				$d['IsEnable'] = Emwork_Utility::notShowNull ( $this->publishOption [$item ["IsEnable"]] );
				$responce ['data'] [] = $d;
			}
		}
		
		$this->_helper->json ( $responce );
	}

    public function addAction()
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
		$canAdd = $sUser->isAllow('license.item.manage');
		if (!$canAdd) {
			$this->_helper->redirector('index', 'item', 'license');
		}
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
				
        // Get Option
        $modelOption = new License_Model_Category();
		$CategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
		$modelDept = new License_Model_Department();
		$departmentOption = $modelDept->getOption($defaultLanguage["Code"]);

        // Mobile Detect
        $detect = new Mobile_Detect();
		$this->view->isiOS = $detect->isiOS();
		
        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		$this->view->CategoryOption = $CategoryOption;
		$this->view->departmentOption = $departmentOption;
		$this->view->defaultCommentUrl = System_Model_Setting::getValue("CommentUrl", "License");
		
        // Set View
        $this->view->pageName = "เพิ่มใบอนุญาต";
        $this->view->pageIcon = "fa fa-file-text-o";
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการใบอนุญาต', 'url' => $this->view->url(array('module' => 'license', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มใบอนุญาต')
        );						
	}
	
	function saveAction()
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
		$canAdd = $sUser->isAllow('license.item.manage');
		if (!$canAdd) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$params = $this->getRequest()->getParams();

		$languages = System_Model_Setting::getLanguages();
		foreach($languages as $langCode) {
			if($params['params'][$langCode['Code']]['ResponseMessageType'] == 'M') {
				if(empty($params['params'][$langCode['Code']]['ResponseMessage'])) {
					$result['message'] = 'กรุณากรอกข้อความตอบกลับ' . $langCode['Name'];
					$this->_helper->json($result);
				}
			}
		}
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$defaultLanguageCode = $defaultLanguage["Code"];
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
		// IntroImage
		$fileModel = new System_Model_Fileinfo();
        $introImageID = null;
        if (count($params['hdfIntroImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
        	if ($fileData->ID != null) {
        		$introImageID = $fileData->ID;
            }
        }
		$params['IntroImageID'] = $introImageID;
		
		// Save
		$model = new License_Model_Item();
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("license.item", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
	}

	function editAction()
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
		$canDelete = $sUser->isAllow('license.item.manage');
		$canEdit = $sUser->isAllow('license.item.manage');
		$canView = $sUser->isAllow('license.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'license');
		}
		
		$itemID = $this->_request->getParam('itemid');

		if (!$itemID) {
			$this->_helper->redirector('index', 'item', 'license');
		}
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
				
        // Get Option
        $modelOption = new License_Model_Category();
		$CategoryOption = $modelOption->getOption($defaultLanguage["Code"]);

		// Model
		$model = new License_Model_Item();

        // Get Data
		$data = $model->getInfo($itemID);
		
		// Detail
		$dataDetails = array();
		// Procedure
		$dataProcedureDetailList = array();   
		
		if (count($languages) > 0) {
			foreach ($languages as $lang) {
				$langCode = $lang['Code'];
				$detail = $model->getDataByLanguage($data->ID, $langCode);
				if (!empty($detail->Lang)) {
					$dataDetails[$langCode] = $detail;
				}
				
				// Procedure
				$procedureDetail = $model->getProcedureByLanguage($data->ID, $langCode);
				if (count($procedureDetail) > 0) {
					foreach ($procedureDetail as $procedure) {
						
						if (array_key_exists($procedure['ID'], $dataProcedureDetailList)) {
							$dataProcedureDetailList[$procedure['ID']][] = $procedure;
						} else {
							$dataProcedureDetailList[$procedure['ID']] = array();
							$dataProcedureDetailList[$procedure['ID']][] = $procedure;
						}
					}
				}
			}
		}
		
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;

		// Department Option
		$modelDept = new License_Model_Department();
		$departmentOption = $modelDept->getOption($defaultLanguage["Code"]);

        // Mobile Detect
        $detect = new Mobile_Detect();
		$this->view->isiOS = $detect->isiOS();

        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		$this->view->CategoryOption = $CategoryOption;
		$this->view->data = $data;
		$this->view->dataDetails = $dataDetails;
		$this->view->dataProcedureDetailList = $dataProcedureDetailList;
		$this->view->departmentOption = $departmentOption;
		$this->view->defaultCommentUrl = System_Model_Setting::getValue("CommentUrl", "License");

        // Set View
        $this->view->pageName = "ปรับปรุงใบอนุญาต";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการใบอนุญาต', 'url' => $this->view->url(array('module' => 'license', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงใบอนุญาต')
        );		
	}

	function updateAction()
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
		$canSave = $sUser->isAllow('license.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$params = $this->getRequest()->getParams();

		$languages = System_Model_Setting::getLanguages();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$defaultLanguageCode = $defaultLanguage["Code"];
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];

		// Save
		$model = new License_Model_Item();
        $data = $model->update($params);

        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("license.item", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
	}

	function deleteAction()
	{
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
		$canSave = $sUser->isAllow('license.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest ()->getParams ();
		
		$model = new License_Model_Item();
		$data = $model->delete($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			
			// Log Acion
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$data = $model->getDataByLanguage($params["id"], $defaultLanguage['Code']);
			System_Model_Log::action("license.item", "delete", $data->Title, $params["id"]);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'ลบไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}
	
	public function procedureformAction() {
		
		$this->_helper->layout()->disableLayout();
		
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canAdd = $sUser->isAllow('license.item.manage');
		
		if (!$canAdd) {
			$this->_helper->redirector('index', 'item', 'license');
		}
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// Get Params
		$data =  $this->getRequest()->getParam('data');
		$dataObj = json_decode($data, true);
		
		// Prepare Data
		$details = array();
		$ID = null;
		if (!empty($data)) {
			$ID = $dataObj["ID"];
		}
		$publishDateTime = '';
		$publishDownTime = '';
		if (count($languages) > 0) {
			foreach ($languages as $lang) {
				$langCode = $lang['Code'];
				$details[$langCode] = new stdClass();
				$details[$langCode]->Title = '';
				$details[$langCode]->LicenseDepartmentID = '';
				$details[$langCode]->IconType = '';
				$details[$langCode]->Url = '';
				$details[$langCode]->UrlType = '';
				$details[$langCode]->UrlComment = '';
				$details[$langCode]->PublishDate = Emwork_Utility_DateTime::MySQLToDateThai(date('Y-m-d'));
				$details[$langCode]->PublishDown = '';
				$details[$langCode]->PublishDateTime = date('H:i');
				$details[$langCode]->PublishDownTime = '0:00';
				$details[$langCode]->StartDate = '';
				$details[$langCode]->EndDate = '';
				$details[$langCode]->IsEnable = 'Y';
				if (!empty($data)) {
					if ((count($dataObj['Lang']) > 0) && (array_key_exists($langCode, $dataObj['Lang']))) {
						$details[$langCode]->Title = $dataObj['Lang'][$langCode]["Title"];
						$details[$langCode]->LicenseDepartmentID = $dataObj['Lang'][$langCode]["LicenseDepartmentID"];
						$details[$langCode]->IconType = $dataObj['Lang'][$langCode]["IconType"];
						$details[$langCode]->Url = $dataObj['Lang'][$langCode]["Url"];
						$details[$langCode]->UrlType = $dataObj['Lang'][$langCode]["UrlType"];
						$details[$langCode]->UrlComment = $dataObj['Lang'][$langCode]["UrlComment"];
						$details[$langCode]->IsEnable = $dataObj['Lang'][$langCode]["Enable"];
						$details[$langCode]->PublishDate = $dataObj['Lang'][$langCode]["PublishDate"];
						$details[$langCode]->PublishDown = $dataObj['Lang'][$langCode]["PublishDown"];
						$details[$langCode]->PublishDateTime = $dataObj['Lang'][$langCode]["PublishDateTime"];
						$details[$langCode]->PublishDownTime = $dataObj['Lang'][$langCode]["PublishDownTime"];
						$details[$langCode]->StartDate = $dataObj['Lang'][$langCode]["StartDate"];
						$details[$langCode]->EndDate = $dataObj['Lang'][$langCode]["EndDate"];
					}
				}
				if ($langCode == $defaultLanguage["Code"]) {
					$publishDateTime = $details[$langCode]->PublishDateTime;
					$publishDownTime = $details[$langCode]->PublishDownTime;
				}
			}
		}		

		// Department Option
		$modelDept = new License_Model_Department();
		$departmentOption = $modelDept->getOption($defaultLanguage["Code"]);
		
		// Mobile Detect
		$detect = new Mobile_Detect();
		$this->view->isiOS = $detect->isiOS();
		
		// Send Data to view
		$this->view->ID = $ID;
		$this->view->dataDetails = $details;
		$this->view->publishDateTime = $publishDateTime;
		$this->view->publishDownTime = $publishDownTime;
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		$this->view->urlTypeOption = $this->urlTypeOption;
		$this->view->departmentOption = $departmentOption;
	}

	public function importformAction() {
		
		$this->_helper->layout()->disableLayout();
		
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canAdd = $sUser->isAllow('license.item.manage');
		
		if (!$canAdd) {
			$this->_helper->redirector('index', 'item', 'license');
		}
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		// Mobile Detect
		$detect = new Mobile_Detect();
		$this->view->isiOS = $detect->isiOS();
	}

	public function importAction() {
        
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
		$canSave = $sUser->isAllow('license.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Get Param
		$params = $this->getRequest()->getParams();
		
        try {
		
			// Get File
			$fileModel = new System_Model_Fileinfo();
			$fileHashName = $params['hdfImportFile'][0];
			if($fileHashName){
				$fileData = $fileModel->itemByHashName($fileHashName);
				if ($fileData->ID != null) {
					$Path = explode("/",$fileData->Path);
					$tmpFileName =  APPLICATION_ROOT.DS."files".DS.$Path[1].DS.$Path[2].DS.$Path[3].DS.$fileData->HashName;
				}
			}
			
			if(empty($tmpFileName)){
				$result['message'] = 'กรุณาอัพโหลดไฟล์';
                $this->_helper->json($result);
			}
			
			// Read File
			$inputFileType = 'Excel2007';
			
			$objReader = PHPExcel_IOFactory::createReader($inputFileType);
			$objReader->setReadDataOnly(true);
			$objPHPExcel = $objReader->load($tmpFileName);
			
			$loadedSheetNames = $objPHPExcel->getSheetNames();
                
            $k = 0;
            $objPHPExcel->setActiveSheetIndexByName($loadedSheetNames[$k]);
            
            $objSheet = $objPHPExcel->getActiveSheet();
            
            $highestColumn = $objSheet->getHighestColumn();
            $total_rows = $objSheet->getHighestRow();
            $total_cols = PHPExcel_Cell::columnIndexFromString($objSheet->getHighestColumn());
            
            $dataRow = array();
            if ($total_rows > 1) {
                $data_all = array();
                for ($row = 1; $row <= $total_rows; $row ++) {
                    $datas = array();
                    for ($col = 0; $col < $total_cols; $col ++) {
                        $colName = PHPExcel_Cell::stringFromColumnIndex($col);
                        $datas[$colName] = $objSheet->getCell($colName . $row)->getValue();
                    }
                    $data_all[$row-1] = $datas;
                }
            }
            
            $checkData = true;
            if (count($data_all)>0) {
                $dataList = array();
                $row = 1;
                foreach ($data_all as $d) {

                    if(empty($d["A"])){
                        continue;
                    }

                    $key = 'row-'.$row;
                    if (array_key_exists($key, $dataList)) {
                        // nothing
                    } else {
                        $dataList[$key] = array(
                                "A" => $d["A"],
                                "B" => $d["B"],
								"C" => $d["C"],
								"D" => $d["D"],
								"E" => $d["E"],
								"F" => $d["F"],
								"G" => $d["G"],
								"H" => $d["H"],
								"I" => $d["I"],
								"J" => $d["J"],
								"K" => $d["K"],
								"L" => $d["L"],
								"M" => $d["M"],
								"N" => $d["N"],
                        );
                    }
                    $row++;
                }
			}
			// echo '<pre>'; print_r($dataList); echo '</pre>'; exit;
            
			// Check Data
            if(strpos($dataList['row-1']['A'], 'หมวดหมู่ (ไทย)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['B'], 'หมวดหมู่ (อังกฤษ)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['C'], 'ประเด็น (ไทย)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['D'], 'ประเด็น (อังกฤษ)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['E'], 'ใบอนุญาต (ไทย)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['F'], 'ใบอนุญาต (อังกฤษ)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['G'], 'กระบวนการ (ไทย)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['H'], 'กระบวนการ (อังกฤษ)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['I'], 'หน่วยงาน (ไทย)') === false) { $checkData=false; }
			if(strpos($dataList['row-1']['J'], 'หน่วยงาน (อังกฤษ)') === false) { $checkData=false; }
			// if(strpos($dataList['row-1']['K'], 'Url Info') === false) { $checkData=false; }
			// if(strpos($dataList['row-1']['L'], 'Url ลิงค์แสดงความคิดเห็น') === false) { $checkData=false; }
			// if(strpos($dataList['row-1']['M'], 'วันที่เปิดรับฟังความคิดเห็น') === false) { $checkData=false; }
			// if(strpos($dataList['row-1']['N'], 'วันที่ปิดรับฟังความคิดเห็น') === false) { $checkData=false; }
            
            if(!$checkData){
                $result['message'] = 'ไฟล์ข้อมูลไม่ถูกต้อง';
                $this->_helper->json($result);
            }

            unset($dataList['row-1']);

            // Prepare Data
			$dataExcel = $this->prepareDataFormat($dataList);
			// echo '<pre>'; print_r($dataExcel); echo '</pre>'; exit;

			// Save License
			$model = new License_Model_Item();
			$data = $model->importLicense($dataExcel);

			if($data->result){
				$result['result'] = true;
            	$result['message'] = $data->message;
			}
			
		} catch (Exception $e) {
			$result['message'] = 'การเชื่อมต่อข้อมูลผิดพลาด กรุณาตรวจสอบไฟล์ข้อมูลของท่าน';
		}
		
		// Return Result
		$this->_helper->json($result);
	}
	
	private function prepareDataFormat($dataList){

		$validator = new Zend_Validate_Date(array('format' => 'Y-m-d'));
		$data = array();
		$now = date('Y-m-d');
		if(count($dataList>0)){
			foreach($dataList as $d){

				$tmpM = str_replace(' ','-',str_replace('/','-',$d['M']));
				$tmpN = str_replace(' ','-',str_replace('/','-',$d['N']));
				$startDate = $validator->isValid($tmpM) ? $tmpM : '';
				$endDate = $validator->isValid($tmpN) ? $tmpN : '';

				if(!$data[$d['A']]){
					$data[$d['A']]['CategoryTH'] = $d['A'];
					$data[$d['A']]['CategoryEN'] = $d['B'];
					$data[$d['A']]['Issue'] = array();
				}
				if(!$data[$d['A']]['Issue'][$d['C']] && !empty($d['C'])){
					$data[$d['A']]['Issue'][$d['C']]['IssueTH'] = $d['C'];
					$data[$d['A']]['Issue'][$d['C']]['IssueEN'] = $d['D'];
					$data[$d['A']]['Issue'][$d['C']]['License'] = array();
				}
				if(!$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']] && !empty($d['E'])){
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['LicenseTH'] = $d['E'];
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['LicenseEN'] = $d['F'];
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'] = array();
				}
				if(!$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']] && !empty($d['G']) && !empty($d['I'])){
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureID'] = $this->gen_uuid();
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureTH'] = $d['G'];
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureEN'] = $d['H'];
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureDepartmentTH'] = $d['I'];
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureDepartmentEN'] = $d['J'];
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureUrlInfo'] = $d['K'];
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureUrlComment'] = $d['L'];
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureStartDate'] = $startDate;
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedureEndDate'] = $endDate;
					$data[$d['A']]['Issue'][$d['C']]['License'][$d['E']]['Procedure'][$d['G']]['ProcedurePublishDate'] = $now;
				}
			}
		}
        return $data;
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
		$canSave = $sUser->isAllow('license.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new License_Model_Item();
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
		$canSave = $sUser->isAllow('license.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
		$params = $this->getRequest()->getParams();
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new License_Model_Item();
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
		$canSave = $sUser->isAllow('license.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new License_Model_Item();
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
	
	private function gen_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	
			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),
	
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,
	
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,
	
			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}
}
?>