<?php

class Weblink_ItemController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	protected $commentStatusOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่', 'S' => 'สแปม');
	protected $canCommentTypeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
    
    public function init()
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
		$canView = $sUser->isAllow('weblink.item.view');
		$canAdd = $sUser->isAllow('weblink.item.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("WeblinkItemSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("WeblinkItemSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("WeblinkItemSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Weblink_Model_Category();
        $weblinkCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->weblinkCategoryOption = $weblinkCategoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการเว็บลิงค์";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการเว็บลิงค์')
            
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
		$canView = $sUser->isAllow('weblink.item.view');
        
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
        $advancesearch['type'] = $this->getRequest()->getParam('type');
        $advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');
        $advancesearch['isCat'] = $this->getRequest()->getParam('isCat');
        
        $infoSearchNamespace = new Zend_Session_Namespace("WeblinkItemSearch");
        $infoSearchNamespace->textsearch = $advancesearch['searchText'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $model = new Weblink_Model_Item();
        $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        
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
                $d['ViewAmount'] = $item["ViewAmount"];
                $d['Ordering'] = Emwork_Utility::notShowNull($item["Ordering"], '-');
                $d['CategoryTitle'] = Emwork_Utility::notShowNull($item["CategoryTitle"]);
                $d['AuthorName'] = Emwork_Utility::notShowNull($item["AuthorName"]);
                $d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["Created"], 'DT'));
                $d['IsEnableCode'] = $item["IsEnable"];
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->publishOption[$item["IsEnable"]]);
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);
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
		$canAdd = $sUser->isAllow('weblink.item.manage');

		if (!$canAdd) {
		    $this->_helper->redirector('index', 'item', 'weblink');
		}
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $weblinkCategoryModel = new Weblink_Model_Category();
        $weblinkCategoryOption = $weblinkCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting 
        $defaultCanComment = System_Model_Setting::getValue("ItemCanComment", "Weblink");
        $canCommentTypeOption = $this->canCommentTypeOption;
        $canCommentTypeOption["D"] = $canCommentTypeOption["D"] . " (" . $this->yesNoOption[$defaultCanComment] . ")"; 
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
        $this->view->publishOption = $this->publishOption;
        $this->view->canCommentTypeOption = $canCommentTypeOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->weblinkCategoryOption = $weblinkCategoryOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "เพิ่มเว็บลิงค์";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการเว็บลิงค์', 'url' => $this->view->url(array('module' => 'weblink', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มเว็บลิงค์')
        );
    }
    
    public function saveAction()
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
		$canAdd = $sUser->isAllow('weblink.item.manage');
		if (!$canAdd) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Weblink_Model_Item();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        // IntroImage
        $introImageID = null;
        if (count($params['hdfIntroImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
        	if ($fileData->ID != null) {
        		$introImageID = $fileData->ID;
            }
        }
        $params['IntroImageID'] = $introImageID;
        
        // Save
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("weblink.item", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        
        $sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		$canDelete = $sUser->isAllow('weblink.item.manage');
		$canEdit = $sUser->isAllow('weblink.item.manage');
		$canView = $sUser->isAllow('weblink.item.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'item', 'weblink');
		}
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'weblink');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $weblinkCategoryModel = new Weblink_Model_Category();
        //$weblinkCategoryOption = $weblinkCategoryModel->getOption($defaultLanguage["Code"]);
        $weblinkCategoryOption = $weblinkCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting
        $defaultCanComment = System_Model_Setting::getValue("ItemCanComment", "Weblink");
        $canCommentTypeOption = $this->canCommentTypeOption;
        $canCommentTypeOption["D"] = $canCommentTypeOption["D"] . " (" . $this->yesNoOption[$defaultCanComment] . ")"; 
        
        // Get Data
        $model = new Weblink_Model_Item();
        $data = $model->getInfo($itemID);        

        if (!$data->ID) {
        	$this->_helper->redirector('index', 'item', 'weblink');
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
        
        $dataCategories = $model->getCategories($data->ID);
        $dataCategoryIds = array();
        if (count($dataCategories) > 0) {
        	foreach ($dataCategories as $dataCategory) {
        		$dataCategoryIds[] = $dataCategory['WeblinkCategoryID'];
        	}
        }
        
        // Intro Image
        $dataIntroImages = array();
        if(!empty($data->IntroImageID)) {
        	$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
        }
        
        // Send Rights to View
        $this->view->canDelete = $canDelete;
        $this->view->canEdit = $canEdit;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send To View
        $this->view->data = $data;
        $this->view->dataCategoryIds = $dataCategoryIds;
        $this->view->dataDetails = $dataDetails;
        $this->view->dataIntroImages = $dataIntroImages;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->weblinkCategoryOption = $weblinkCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->canCommentTypeOption = $canCommentTypeOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "ปรับปรุงเว็บลิงค์";
        $this->view->pageIcon= "fa fa-newspaper-o";
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการเว็บลิงค์', 'url' => $this->view->url(array('module' => 'weblink', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงเว็บลิงค์')
        );
        
        // Log Acion
        System_Model_Log::action("weblink.item", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
    }
    
    public function updateAction()
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
		$canSave = $sUser->isAllow('weblink.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Weblink_Model_Item();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        // IntroImage
        $introImageID = null;
        if (count($params['hdfIntroImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
        	if ($fileData->ID != null) {
        		$introImageID = $fileData->ID;
        	}
        }
        $params['IntroImageID'] = $introImageID;
        
        // Update
        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("weblink.item", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }
    
    
    public function deleteAction()
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
		$canSave = $sUser->isAllow('weblink.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Weblink_Model_Item();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("weblink.item", "delete", $params['title'], $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
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
		$canSave = $sUser->isAllow('weblink.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Weblink_Model_Item();
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
		$canSave = $sUser->isAllow('weblink.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Weblink_Model_Item();
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
		$canSave = $sUser->isAllow('weblink.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Weblink_Model_Item();
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

