<?php
class Eform_ItemController extends Zend_Controller_Action {
    
    protected $publishOption = array (
        'Y' => 'เผยแพร่',
        'N' => 'ไม่เผยแพร่' 
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
		$canView = $sUser->isAllow('form.item.view');
		$canAdd = $sUser->isAllow('form.item.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass ();
		if (Zend_Session::namespaceIsset("EformIndexIndexSearch" )) {
			$infoSearchNamespace = new Zend_Session_Namespace("EformIndexIndexSearch" );
			$InfoSearch->searchText = $infoSearchNamespace->searchText;
			$InfoSearch->category= $infoSearchNamespace->category;
			$InfoSearch->isEnable = $infoSearchNamespace->isEnable;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol= $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset ( "EformIndexIndexSearch" );
		}

        
        $languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
				
        // Get Option
        $modelOption = new Eform_Model_Category();
		$CategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
		        
		// Send To View
		$this->view->publishOption = Emwork_Utility::addEmptyOption($this->publishOption);
		$this->view->CategoryOption = $CategoryOption;
		
		// Set View
		$this->view->canAdd = $canAdd;
		$this->view->pageName = "รายการแบบฟอร์ม";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ( 'name' => 'หน้าแรก', 'url' => $this->view->url ( array ( 'module' => 'default', 'controller' => 'index', 'action' => 'index'  ), null, true)), 
				array ( 'name' => 'รายการแบบฟอร์ม'  ) 
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
		$canView = $sUser->isAllow('form.item.view');
		
		
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
		$advancesearch['isEnable'] = $this->getRequest ()->getParam ( 'isEnable' );
		
		$searchNamespace = new Zend_Session_Namespace("EformIndexIndexSearch");
		$searchNamespace->searchText = $advancesearch['searchText'];
		$searchNamespace->category = $advancesearch['isCat'];
		$searchNamespace->isEnable = $advancesearch['isEnable'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
		$searchNamespace->tblOrderDir = $_order[0]['dir'];
		
		if($canView) :
			$model = new Eform_Model_Item();
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
				$d['Ordering'] = Emwork_Utility::notShowNull($item["Ordering"]);
				$d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
				$d['CategoryTitle'] = Emwork_Utility::notShowNull($item["CategoryTitle"]);
				$d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySqlToDateThai($item["Created"], 'DT'));
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
		$canAdd = $sUser->isAllow('form.item.manage');
		if (!$canAdd) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
				
        // Get Option
        $modelOption = new Eform_Model_Category();
		$CategoryOption = $modelOption->getOption($defaultLanguage["Code"]);

        // Mobile Detect
        $detect = new Mobile_Detect();
		$this->view->isiOS = $detect->isiOS();
		
        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
		$this->view->publishOption = $this->publishOption;
		$this->view->CategoryOption = $CategoryOption;
		
        // Set View
        $this->view->pageName = "เพิ่มแบบฟอร์ม";
        $this->view->pageIcon = "fa fa-file-text-o";
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบฟอร์ม', 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มแบบฟอร์ม')
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
		$canAdd = $sUser->isAllow('form.item.manage');
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
		$model = new Eform_Model_Item();
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("eform.item", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
		
		$itemID = $this->_request->getParam('itemid');

		if (!$itemID) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
				
        // Get Option
        $modelOption = new Eform_Model_Category();
		$CategoryOption = $modelOption->getOption($defaultLanguage["Code"]);

		// Model
		$model = new Eform_Model_Item();

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

        $dataCategories = $model->getCategories($data->ID);
        $dataCategoryIds = array();
        if (count($dataCategories) > 0) {
        	foreach ($dataCategories as $dataCategory) {
        		$dataCategoryIds[] = $dataCategory['EformCategoryID'];
        	}
		}
		
        // Intro Image
        $dataIntroImages = array();
        if(!empty($data->IntroImageID)) {
        	$dataIntroImages[] = array(
				'file_name'=> $data->IntroImageFileName, 
				'size'=> $data->IntroImageFileSize, 
				'type'=> $data->IntroImageFileType, 
				'hash_name'=> $data->IntroImageHashName
			);
		}		
		
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;

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
		$this->view->dataCategoryIds = $dataCategoryIds;
		$this->view->dataIntroImages = $dataIntroImages;

        // Set View
        $this->view->pageName = "ปรับปรุงแบบฟอร์ม";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array(
			array("name" => "รายละเอียดแบบฟอร์ม", "active" => true),
			array("name" => "จัดการแบบฟอร์ม", "active" => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'editform', "itemid" => $itemID), null, true)),
			array('name' => 'รายการตอบกลับ', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'submission', 'itemid' => $itemID), null, true)),
			array('name' => 'รายงานสถิติ', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'statall', 'itemid' => $itemID), null, true)),
			array('name' => 'ตั้งค่าอีเมล์', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'setting', 'itemid' => $itemID), null, true)),
		);
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบฟอร์ม', 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงแบบฟอร์ม')
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
		$canSave = $sUser->isAllow('form.item.manage');
		if (!$canSave) {
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
		$model = new Eform_Model_Item();
        $data = $model->update($params);

        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("eform.item", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
		$canSave = $sUser->isAllow('form.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest ()->getParams ();
		
		$model = new Eform_Model_Item();
		$data = $model->delete($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			
			// Log Acion

			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$data = $model->getDataByLanguage($params["id"], $defaultLanguage['Code']);
			System_Model_Log::action("form.item", "delete", $data->Title, $params["id"]);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'ลบไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
	}
	
	function editformAction()
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
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
		
		$itemID = $this->getRequest ()->getParam ('itemid');
		if (empty($itemID)) {
			$this->_helper->redirector('index', 'item', 'eform');
		}

		$model = new Eform_Model_Item();
		$dataType  = $model->type($itemID);
		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			$dataType[$key]['NAME_'] = $typeProperty['NAME'];
			$dataType[$key]['LABEL_'] = $typeProperty['LABEL'];
			$dataType[$key]['PLACEHOLDER_'] = $typeProperty['PLACEHOLDER'];
			$dataType[$key]['DESCRIPTION_'] = $typeProperty['DESCRIPTION'];
			$dataType[$key]['REQUIRED_'] = $typeProperty['REQUIRED'];
			$dataType[$key]['VALIDATIONRULE_'] = $typeProperty['VALIDATIONRULE'];
			$dataType[$key]['SIZE_'] = $typeProperty['SIZE'];
			$dataType[$key]['MAXLENGTH_'] = $typeProperty['MAXLENGTH'];
			$dataType[$key]['VALIDATE_'] = $typeProperty['VALIDATE'];
			$dataType[$key]['COLS_'] = $typeProperty['COLS'];
			$dataType[$key]['ROWS_'] = $typeProperty['ROWS'];
			$dataType[$key]['DROPDOWN_'] = $typeProperty['DROPDOWN'];
			$dataType[$key]['OPTION_'] = $typeProperty['OPTION'];
			$dataType[$key]['FLOW_'] = $typeProperty['FLOW'];
			$dataType[$key]['FILESIZE_'] = $typeProperty['FILESIZE'];
			$dataType[$key]['FILETYPE_'] = $typeProperty['FILETYPE'];
			$dataType[$key]['MULTIPLE_'] = $typeProperty['MULTIPLE'];
			$dataType[$key]['RESETBUTTON_'] = $typeProperty['RESETBUTTON'];
			$dataType[$key]['RESETLABEL_'] = $typeProperty['RESETLABEL'];
			$dataType[$key]['MESSAGE_'] = $typeProperty['MESSAGE'];			
		}

		$dataCaptcha = $model->getReCaptchakey();

		$this->view->dataCaptcha = $dataCaptcha;
		$this->view->dataType = $dataType;
		$this->view->ID = $itemID;
		$this->view->canEdit = $canEdit;

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$datainfo = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);
		System_Model_Log::action("form.editform", "view", $datainfo->Title, $itemID);

        // Set View
        $this->view->pageName = "ปรับปรุงแบบฟอร์ม";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array(
			array("name" => "รายละเอียดแบบฟอร์ม", "active" => false, "url" => $this->view->url(array("module" => "eform", "controller" => "item", "action" => "edit", "itemid" => $itemID), null, true)),
			array("name" => "จัดการแบบฟอร์ม", "active" => true),
			array('name' => 'รายการตอบกลับ', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'submission', 'itemid' => $itemID), null, true)),
			array('name' => 'รายงานสถิติ', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'statall', 'itemid' => $itemID), null, true)),
			array('name' => 'ตั้งค่าอีเมล์', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'setting', 'itemid' => $itemID), null, true)),
		);
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบฟอร์ม', 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงแบบฟอร์ม')
        );
	}

	function submissionAction()
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
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
		
		// Get Param
		$itemID = $this->_request->getParam('itemid');
		
		// Model
		if (!$itemID) {
			$this->_helper->redirector('index', 'item', 'eform');
		}

		$InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("EformSubmissionSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("EformSubmissionSearch");
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("EformSubmissionSearch");
		}

		$model = new Eform_Model_Item();
		$dataType  = $model->type($itemID);
		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
				$dataType[$key]['LABEL_'] = $typeProperty['LABEL'];
			} else {
				unset($dataType[$key]);
			}
		}

		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$this->view->dataType = $dataType;
		$this->view->itemid  = $itemID;
		$this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];

		$model = new Eform_Model_Item();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$datainfo = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);
		System_Model_Log::action("form.submission", "view", $datainfo->Title, $itemID);
		
        // Set View
        $this->view->pageName = "รายการตอบกลับ";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array(
			array("name" => "รายละเอียดแบบฟอร์ม", "active" => false, "url" => $this->view->url(array("module" => "eform", "controller" => "item", "action" => "edit", "itemid" => $itemID), null, true)),
			array("name" => "จัดการแบบฟอร์ม", "active" => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'editform', "itemid" => $itemID), null, true)),
			array('name' => 'รายการตอบกลับ', 'active' => true),
			array('name' => 'รายงานสถิติ', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'statall', 'itemid' => $itemID), null, true)),
			array('name' => 'ตั้งค่าอีเมล์', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'setting', 'itemid' => $itemID), null, true)),
		);
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบฟอร์ม', 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'รายการตอบกลับ')
        );
	}

    public function getsubmissionlistAction()
    {
        $filterInt = new Zend_Filter_Int();
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
		$canView = $sUser->isAllow('form.item.view');

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
        $textstr = ''; // $this->getRequest()->getParam('textstr');
        $filter = ''; // $this->getRequest()->getParam('filter');
		$itemID = $this->getRequest()->getParam('itemid');

		$infoSearchNamespace = new Zend_Session_Namespace("EformSubmissionSearch");
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
		$infoSearchNamespace->tblOrderDir = $_order[0]['dir'];

		$advancesearch['itemid'] = $itemID;

		if($canView):
			$model = new Eform_Model_Item();
			$data = $model->getSubmissionList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
			$dataType  = $model->type($itemID);
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
				$d['SubmitDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["SubmitDate"], 'DT'));
				$d['RemoteIP'] = Emwork_Utility::notShowNull($item["RemoteIP"]);

				if(count($dataType) >0 ) {
					foreach($dataType as $key => $value) {
						$typeProperty = $model->typeProperty($value['ID']);
						if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
							$dataType[$key]['LABEL_'] = $typeProperty['LABEL'];
							if($dataType[$key]['EformControlTypeID'] == 7) {
								$typeProperty['NAME'] = 'hdf'.$typeProperty['NAME'];
								$submissionValues = $model->getSubmissionValues($item["ID"], $value['EformFormID'], $typeProperty['NAME']);
								$fileModel = new System_Model_Fileinfo;
								$fileData = $fileModel->item($submissionValues->ControlValue);
								//$d[$value["ID"]] = Emwork_Utility::notShowNull($fileData->Name);
								$d[$value["ID"]] = "-";
								if (!empty($fileData->Name) && !empty($fileData->HashName)) {
									$d[$value["ID"]] = "<a href='" . $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "type" => "download", "file" => $fileData->HashName), null, true) . "' target='_blank'>" . $fileData->Name . "</a>";
								}
							} else {
								$submissionValues = $model->getSubmissionValues($item["ID"], $value['EformFormID'], $typeProperty['NAME']);
								if( !in_array($dataType[$key]['EformControlTypeID'], array(3,4,5)) ) {
									$d[$value["ID"]] = Emwork_Utility::notShowNull($submissionValues->ControlValue);
								} else {
									$tmp_js = '';
									if(count($submissionValues->ControlValue)>0) {
										$jsdecode = json_decode($submissionValues->ControlValue);
										foreach($jsdecode as $jsk) {
											if($tmp_js != '') {
												$tmp_js .= ',';
											}
											$tmp_js .= $jsk;
										}
									}
									$d[$value["ID"]] = Emwork_Utility::notShowNull($tmp_js);
								}
							}
						} else {
							unset($dataType[$key]);
						}
					}
				}

				$responce['data'][] = $d;
            }
		}
		
        $this->_helper->json($responce);
	}
	
	function exportexcelsubmissionAction()
	{
		$tmp_data = array();
		$dataType = array();

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}

		$itemID = $this->getRequest()->getParam('itemid');
		$model = new Eform_Model_Item();
		$data = $model->getExportSubmission($itemID);
		$dataType  = $model->type($itemID);

		if(count($data)>0) {
			$i = 0;
			foreach($data as $item) {
				$d = array();
				$i++;
				$d['NO']= $i;
				// $d['ID'] = $item["ID"];
				$d['SubmitDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["SubmitDate"], 'DT'));
				$d['RemoteIP'] = Emwork_Utility::notShowNull($item["RemoteIP"]);

				if(count($dataType) >0 ) {
					foreach($dataType as $key => $value) {
						$typeProperty = $model->typeProperty($value['ID']);
						if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
							$dataType[$key]['LABEL_'] = $typeProperty['LABEL'];
							if($dataType[$key]['EformControlTypeID'] == 7) {
								$typeProperty['NAME'] = 'hdf'.$typeProperty['NAME'];
								$submissionValues = $model->getSubmissionValues($item["ID"], $value['EformFormID'], $typeProperty['NAME']);
								$fileModel = new System_Model_Fileinfo;
								$fileData = $fileModel->item($submissionValues->ControlValue);
								$d[$value["ID"]] = Emwork_Utility::notShowNull($fileData->Name);
							} else {
								$submissionValues = $model->getSubmissionValues($item["ID"], $value['EformFormID'], $typeProperty['NAME']);
								if( !in_array($dataType[$key]['EformControlTypeID'], array(3,4,5)) ) {
									$d[$value["ID"]] = Emwork_Utility::notShowNull($submissionValues->ControlValue);
								} else {
									$tmp_js = '';
									if(count($submissionValues->ControlValue)>0) {
										$jsdecode = json_decode($submissionValues->ControlValue);
										foreach($jsdecode as $jsk) {
											if($tmp_js != '') {
												$tmp_js .= ',';
											}
											$tmp_js .= $jsk;
										}
									}
									$d[$value["ID"]] = Emwork_Utility::notShowNull($tmp_js);
								}
							}
						} else {
							unset($dataType[$key]);
						}
					}
				}
				$tmp_data[] = $d;
			}
		}


		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
				$dataType[$key]['LABEL_'] = $typeProperty['LABEL'];
			} else {
				unset($dataType[$key]);
			}
		}
		
		$this->view->dataType = $dataType;
		$this->view->tmp_data = $tmp_data;

		// Set view
		Zend_Layout::resetMvcInstance();

		// Set Response
		header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=ResponseReport.xls");
	}

	function statallAction()
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
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
		
		// Get Param
		$itemID = $this->_request->getParam('itemid');

		// Model
		if (!$itemID) {
			$this->_helper->redirector('index', 'item', 'eform');
		}


		$model = new Eform_Model_Item();
		$dataType  = $model->type($itemID);
		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
				$dataType[$key]['Label'] = $typeProperty['LABEL'];
			} else {
				unset($dataType[$key]);
			}
		}

		$data = $model->getStaticList($itemID);
		if(count($data)>0) {
			foreach($data as $item) {
				if(count($dataType) >0 ) {
					foreach($dataType as $key => $value) {
						$typeProperty = $model->typeProperty($value['ID']);
						if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
							$dataType[$key]['Name'] = $typeProperty['NAME'];
							$StaticValue = $model->getStaticValues($value['EformFormID'], $typeProperty['NAME'], $dataType[$key]['EformControlTypeID']);
							$dataType[$key]['Value'] = $StaticValue['Value'];
						} else {
							unset($dataType[$key]);
						}
					}
				}
			}
		}

		$this->view->dataType = $dataType;
		$this->view->itemID = $itemID;

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$datainfo = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);
		System_Model_Log::action("form.stat", "view", $datainfo->Title, $itemID);

		$this->view->pageName = "รายงานสถิติ";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array(
			array("name" => "รายละเอียดแบบฟอร์ม", "active" => false, "url" => $this->view->url(array("module" => "eform", "controller" => "item", "action" => "edit", "itemid" => $itemID), null, true)),
			array("name" => "จัดการแบบฟอร์ม", "active" => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'editform', "itemid" => $itemID), null, true)),
			array('name' => 'รายการตอบกลับ', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'submission', "itemid" => $itemID), null, true)),
			array('name' => 'รายงานสถิติ', 'active' => true, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'statall', 'itemid' => $itemID), null, true)),
			array('name' => 'ตั้งค่าอีเมล์', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'setting', 'itemid' => $itemID), null, true)),
		);
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบฟอร์ม', 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'รายงานสถิติ')
        );		
	}

	function interviewAction()
	{

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}

		$sendform = $this->getRequest()->getParam('sendform');
		$sendto = $this->getRequest()->getParam('sendto');
		$eformid = $this->getRequest()->getParam('eformid');

		$params['sendform'] = base64_decode($sendform);
		$params['sendto'] = base64_decode($sendto);
		$params['EformFormID'] = $eformid;

		$model = new Eform_Model_Item();
		$data = $model->interview($params);

		// send to view
		$this->view->data = $data;
		$this->view->sendto = $sendto;
		$this->view->sendform = $sendform;

		// Set view
		Zend_Layout::resetMvcInstance();
	}

	function staticAction()
	{

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}

		// Get Param
		$itemID = $this->_request->getParam('itemid');
		$sendform = $this->getRequest()->getParam('sendform');
		$sendto = $this->getRequest()->getParam('sendto');
		$eformid = $this->getRequest()->getParam('eformid');

		$params['sendform'] = base64_decode($sendform);
		$params['sendto'] = base64_decode($sendto);
		$params['EformFormID'] = $eformid;

		$model = new Eform_Model_Item();
		$dataType  = $model->type($itemID);
		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
				$dataType[$key]['Label'] = $typeProperty['LABEL'];
			} else {
				unset($dataType[$key]);
			}
		}

		$data = $model->getStaticList($itemID,$params);
		if(count($data)>0) {
			foreach($data as $item) {
				if(count($dataType) >0 ) {
					foreach($dataType as $key => $value) {
						$typeProperty = $model->typeProperty($value['ID']);
						if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
							$dataType[$key]['Name'] = $typeProperty['NAME'];
							$StaticValue = $model->getStaticValues($value['EformFormID'], $typeProperty['NAME'], $dataType[$key]['EformControlTypeID']);
							$dataType[$key]['Value'] = $StaticValue['Value'];
						} else {
							unset($dataType[$key]);
						}
					}
				}
			}
		}


		// send to view
		$this->view->dataType = $dataType;
		$this->view->sendto = $sendto;
		$this->view->sendform = $sendform;

		// Set view
		Zend_Layout::resetMvcInstance();
	}

	function exportexcelstaticAction()
	{

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}

		$tmp_data = array();
		$itemID = $this->_request->getParam('itemid');
		
		$model = new Eform_Model_Item();
		$dataType  = $model->type($itemID);
		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
				$dataType[$key]['Label'] = $typeProperty['LABEL'];
			} else {
				unset($dataType[$key]);
			}
		}

		$data = $model->getStaticList($itemID);
		if(count($data)>0) {
			foreach($data as $item) {
				if(count($dataType) >0 ) {
					foreach($dataType as $key => $value) {
						$typeProperty = $model->typeProperty($value['ID']);
						if( !in_array($dataType[$key]['EformControlTypeID'], array(9,10,11)) ) {
							$dataType[$key]['Name'] = $typeProperty['NAME'];
							$StaticValue = $model->getStaticValues($value['EformFormID'], $typeProperty['NAME'], $dataType[$key]['EformControlTypeID']);
							$dataType[$key]['Value'] = $StaticValue['Value'];
						} else {
							unset($dataType[$key]);
						}
					}
				}
			}
		}
		
		$this->view->dataType = $dataType;

		// Set view
		Zend_Layout::resetMvcInstance();

		// // Set Response
		header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=ResponseReport.xls");
	}

	function settingAction()
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
		$canView = $sUser->isAllow("form.item.view");
		$canEdit = $sUser->isAllow("form.item.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
		
		$itemID = $this->getRequest ()->getParam ( 'itemid' );
		if (empty($itemID)) {
			$this->_helper->redirector('index', 'item', 'eform');
		}

		$model = new Eform_Model_Item();
		$data = $model->getSetting($itemID);

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$datainfo = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);
		System_Model_Log::action("form.settingemail", "view", $datainfo->Title, $itemID);

		$this->view->canEdit = $canEdit;
		$this->view->data = $data;
		$this->view->itemID = $itemID;

		$this->view->pageName = "ตั้งค่าอีเมล์";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array(
			array("name" => "รายละเอียดแบบฟอร์ม", "active" => false, "url" => $this->view->url(array("module" => "eform", "controller" => "item", "action" => "edit", "itemid" => $itemID), null, true)),
			array("name" => "จัดการแบบฟอร์ม", "active" => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'editform', "itemid" => $itemID), null, true)),
			array('name' => 'รายการตอบกลับ', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'submission', "itemid" => $itemID), null, true)),
			array('name' => 'รายงานสถิติ', 'active' => false, 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'statall', 'itemid' => $itemID), null, true)),
			array('name' => 'ตั้งค่าอีเมล์', 'active' => true)
		);
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการแบบฟอร์ม', 'url' => $this->view->url(array('module' => 'eform', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ตั้งค่าอีเมล์')
        );
	}

	function settingsaveAction()
	{
		$result = array ();
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
		$canSave = $sUser->isAllow('form.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}

		$params = $this->getRequest()->getParams();

		$model = new Eform_Model_Item();
		$data = $model->SaveSetting($params);

		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
			
			// Log Acion
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$datainfo = $model->getDataByLanguage($params['hdfID'], $defaultLanguage['Code']);
			System_Model_Log::action("form.settingemail", "save", $datainfo->Title, $params['hdfID']);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
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
		$canSave = $sUser->isAllow('form.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Eform_Model_Item();
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
		$canSave = $sUser->isAllow('form.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
		$params = $this->getRequest()->getParams();
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Eform_Model_Item();
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
		$canSave = $sUser->isAllow('form.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Eform_Model_Item();
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