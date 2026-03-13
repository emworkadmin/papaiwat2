<?php

class Calendar_ArticleController extends Zend_Controller_Action
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
        $canAdd = $sUser->isAllow('calendar.article.manage');
        $canView = $sUser->isAllow('calendar.article.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
         
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("CalendarArticleSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("CalendarArticleSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("CalendarArticleSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Calendar_Model_Category();
        $calendarCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->calendarCategoryOption = $calendarCategoryOption;
        
        // Set View
        $this->view->canAdd =$canAdd;
        $this->view->pageName = "รายการปฏิทินกิจกรรม";
        $this->view->pageIcon = "fa fa-calendar";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการปฏิทินกิจกรรม')
            
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
        $canView = $sUser->isAllow('calendar.article.view');

        
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
        
        $infoSearchNamespace = new Zend_Session_Namespace("CalendarArticleSearch");
        $infoSearchNamespace->textsearch = $advancesearch['searchText'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $model = new Calendar_Model_Article();
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
        $canAdd = $sUser->isAllow('calendar.article.manage');
        
        if (!$canAdd) {
           $this->_helper->redirector('index', 'article', 'calendar');
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $calendarCategoryModel = new Calendar_Model_Category();
        //$calendarCategoryOption = $calendarCategoryModel->getOption($defaultLanguage["Code"]);
        $calendarCategoryOption = $calendarCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting 
        $defaultCanComment = System_Model_Setting::getValue("ArticleCanComment", "Calendar");
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
        $this->view->calendarCategoryOption = $calendarCategoryOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "เพิ่มปฏิทินกิจกรรม";
        $this->view->pageIcon = "fa fa-calendar";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการปฏิทินกิจกรรม', 'url' => $this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มปฏิทินกิจกรรม')
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

        $canAdd = $sUser->isAllow('calendar.article.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Calendar_Model_Article();
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
        
        // Gallery
        $galleryIds = array();
        if (count($params['hdfFileImages'])) {
        	foreach ($params['hdfFileImages'] as $hashName){
        		unset($fileData);
        		$fileData = $fileModel->itemByHashName($hashName);
        		if ($fileData->ID != null) {
        			$galleryIds[]= $fileData->ID;
        		}
        	}
        }
        $params['GalleryIds'] = $galleryIds;
        
        // Document
        $documentIds = array();
        if (count($params['hdfFileDocuments'])) {
        	foreach ($params['hdfFileDocuments'] as $hashName){
        		unset($fileData);
        		$fileData = $fileModel->itemByHashName($hashName);
        		if ($fileData->ID != null) {
        			$documentIds[]= $fileData->ID;
        		}
        	}
        }
        $params['DocumentIds'] = $documentIds;
        
        // MultiMedia
        $vdoIds = array();
        if (count($params['hdfFileMultiMedias'])) {
        	foreach ($params['hdfFileMultiMedias'] as $hashName){
        		unset($fileData);
        		$fileData= $fileModel->itemByHashName($hashName);
        		if ($fileData->ID != null) {
        			$vdoIds[]= $fileData->ID;
        		}
        	}
        }
        $params['VdoIds'] = $vdoIds;
        
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("calendar.article", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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

        $canDelete = $sUser->isAllow('calendar.article.manage');
        $canEdit = $sUser->isAllow('calendar.article.manage');
        $canView = $sUser->isAllow('calendar.article.view');

        if (!$canView) {
            $this->_helper->redirector('index', 'article', 'calendar');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'article', 'calendar');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $calendarCategoryModel = new Calendar_Model_Category();
        //$calendarCategoryOption = $calendarCategoryModel->getOption($defaultLanguage["Code"]);
        $calendarCategoryOption = $calendarCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting
        $defaultCanComment = System_Model_Setting::getValue("ArticleCanComment", "Calendar");
        $canCommentTypeOption = $this->canCommentTypeOption;
        $canCommentTypeOption["D"] = $canCommentTypeOption["D"] . " (" . $this->yesNoOption[$defaultCanComment] . ")"; 
        
        $model = new Calendar_Model_Article();
        
        // Get Data
        $data = $model->getInfo($itemID);
        
        if (!$data->ID) {
        	$this->_helper->redirector('index', 'article', 'calendar');
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
        		$dataCategoryIds[] = $dataCategory['CalendarCategoryID'];
        	}
        }
        
        
        // Intro Image
        $dataIntroImages = array();
        if(!empty($data->IntroImageID)) {
        	$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
        }
        
        $dataFiles = $model->getFiles($data->ID);
        $dataFileImages = array();
        $dataFileDocuments = array();
        $dataFileMultiMedias = array();
        if (count($dataFiles) > 0) {
        	foreach ($dataFiles as $dataFile) {
        		if ($dataFile["ArticleFileType"] == "image") {
        			$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
        		} elseif ($dataFile["ArticleFileType"] == "document") {
        			$dataFileDocuments[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
        		} elseif ($dataFile["ArticleFileType"] == "multimedia") {
        			$dataFileMultiMedias[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
        		}
        	}
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
        $this->view->dataFileImages = $dataFileImages;
        $this->view->dataFileDocuments = $dataFileDocuments;
        $this->view->dataFileMultiMedias = $dataFileMultiMedias;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->calendarCategoryOption = $calendarCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->canCommentTypeOption = $canCommentTypeOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "ปรับปรุงปฏิทินกิจกรรม";
        $this->view->pageIcon= "fa fa-calendar";
        $this->view->subMenus = array(
        		array('name' => 'รายละเอียดปฏิทินกิจกรรม', 'active' => true),
        		array('name' => 'ความคิดเห็น', 'active' => false, 'url' => $this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'comment', 'itemid' => $itemID), null, true)),
        //		array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'rights', 'itemid' => $itemID), null, true))
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการปฏิทินกิจกรรม', 'url' => $this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงปฏิทินกิจกรรม')
        );
        
        // Log Acion
        System_Model_Log::action("calendar.article", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
        $canSave = $sUser->isAllow('calendar.article.manage');
        if (!$canSave) {
           $result['message'] = 'ไม่สามารถบันทึกได้';
           $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Calendar_Model_Article();
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
        
        // Gallery
        $galleryIds = array();
        if (count($params['hdfFileImages'])) {
        	foreach ($params['hdfFileImages'] as $hashName){
        		unset($fileData);
        		$fileData = $fileModel->itemByHashName($hashName);
        		if ($fileData->ID != null) {
        			$galleryIds[]= $fileData->ID;
        		}
        	}
        }
        $params['GalleryIds'] = $galleryIds;
        
        // Document
        $documentIds = array();
        if (count($params['hdfFileDocuments'])) {
        	foreach ($params['hdfFileDocuments'] as $hashName){
        		unset($fileData);
        		$fileData = $fileModel->itemByHashName($hashName);
        		if ($fileData->ID != null) {
        			$documentIds[]= $fileData->ID;
        		}
        	}
        }
        $params['DocumentIds'] = $documentIds;
        
        // MultiMedia
        $vdoIds = array();
        if (count($params['hdfFileMultiMedias'])) {
        	foreach ($params['hdfFileMultiMedias'] as $hashName){
        		unset($fileData);
        		$fileData= $fileModel->itemByHashName($hashName);
        		if ($fileData->ID != null) {
        			$vdoIds[]= $fileData->ID;
        		}
        	}
        }
        $params['VdoIds'] = $vdoIds;
        
        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("calendar.article", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canSave = $sUser->isAllow('calendar.article.manage');
        if (!$canSave) {
           $result['message'] = 'ไม่สามารถลบได้';
           $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Calendar_Model_Article();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("calendar.article", "delete", $params['title'], $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }
    
    
    
    /********************** Comment ************************/
    
    public function commentAction()
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
        $canView = $sUser->isAllow('calendar.article.view');
        $canAdd = $sUser->isAllow('calendar.article.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }

    	$itemID = $this->getRequest()->getParam('itemid');
    	if (empty($itemID)) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	
    	// $InfoSearch = new stdClass();
    	// if (Zend_Session::namespaceIsset("CalendarArticleSearch")) {
    	// 	$infoSearchNamespace = new Zend_Session_Namespace("CalendarArticleSearch");
    	// 	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
    	// 	$InfoSearch->category= $infoSearchNamespace->category;
    	// 	$InfoSearch->isenable= $infoSearchNamespace->isenable;
    	// 	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
    	// 	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
    	// 	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
    	// 	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;    		
    	// 	$this->view->InfoSearch = $InfoSearch;
    	// 	Zend_Session::namespaceUnset("CalendarArticleSearch");
    	// }
        
        $this->view->canAdd = $canAdd;
    	$languages = System_Model_Setting::getLanguages();
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    	
    	// Get Option
    	$modelOption = new Calendar_Model_Category();
    	$calendarCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
    	
    	// Send To View
    	$this->view->itemID = $itemID;
    	
    	// Set View
    	$this->view->canManage = $canManage;
    	$this->view->pageName = "ความคิดเห็น";
    	$this->view->pageIcon = "fa fa-calendar";
    	$this->view->subMenus = array(
    			array('name' => 'รายละเอียดปฏิทินกิจกรรม', 'active' => false, 'url' => $this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'ความคิดเห็น', 'active' => true),
    	//		array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'rights', 'itemid' => $itemID), null, true))
    	);
    	$this->view->breadcrumb = array(
    			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
    			array('name' => 'รายการปฏิทินกิจกรรม', 'url' => $this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'index'), null, true)),
    			array('name' => 'ปรับปรุงปฏิทินกิจกรรม', 'url' => $this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'ความคิดเห็น')
    			
    	);
    }
    
    public function getcommentlistAction()
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

        $canView = $sUser->isAllow('calendar.article.view');
    	
    	$itemID = $this->getRequest()->getParam('itemid');
    	
    	if (empty($itemID)) {
    		$this->_helper->json($responce);
    	}
    	
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
    	$advancesearch = array();
    	$advancesearch['searchText'] = $textstr;
    	$advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');
    	$advancesearch['articleID'] = $itemID;
    	
    	// $infoSearchNamespace = new Zend_Session_Namespace("CalendarArticleSearch");
    	// $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
    	// $infoSearchNamespace->category= $advancesearch['isCat'];
    	// $infoSearchNamespace->isenable= $advancesearch['isEnable'];
    	// $infoSearchNamespace->tblPage = $_start;
    	// $infoSearchNamespace->tblRow = $_length;
    	// $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
    	// $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
    	
    	$model = new Calendar_Model_Comment();
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
    			$d['FullName'] = Emwork_Utility::notShowNull($item["FullName"]);
    			$d['ActionDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["ActionDate"], 'DT'));
    			$d['IsEnableCode'] = $item["IsEnable"];
    			$d['IsEnable'] = Emwork_Utility::notShowNull($this->commentStatusOption[$item["IsEnable"]]);
    			$d['Message'] = nl2br($item["Message"]);
    			$responce['data'][] = $d;
    		}
    	}
    	$this->_helper->json($responce);
    }
    
    public function commentupdateAction()
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
        $canSave = $sUser->isAllow('calendar.article.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Update
    	$model = new Calendar_Model_Comment();
    	$data = $model->updateStatus($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ปรับปรุงสถานะไม่สำเร็จ';
    	}
    	 // Log Acion
         System_Model_Log::action("calendar.article", "updatecomment", $params['params'][$defaultLanguageCode]['Title'], $params["id"]);
    	$this->_helper->json($result);
    }
    
    public function commentdeleteAction()
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
        $canSave = $sUser->isAllow('calendar.article.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Update
    	$model = new Calendar_Model_Comment();
    	$data = $model->delete($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
			// Log Acion
         System_Model_Log::action("calendar.article", "deletecomment", $params['params'][$defaultLanguageCode]['Title'], $params["id"]);
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ลบไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }
    
    
    public function previewAction() {
    	$this->_helper->layout()->disableLayout();
        
        // Log Access
    	System_Model_Log::access();

        $sUser = System_Model_SUser::getInstance();
        
        if (!$sUser->hasData()) {
            $this->_helper->json($responce);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->json($responce);
        }

        $canView = $sUser->isAllow('calendar.article.view');
    	
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    	
    	$params = $this->getRequest()->getParams();
    	$hashCode = uniqid();
    	
    	$previewNamespace = new Zend_Session_Namespace("Preview" . $hashCode);
    	$previewNamespace->params = $params;
    	
    	$this->view->hashCode = $hashCode;
    	$this->view->canView = $canView;
    }
    
    public function previewdisplayAction() {
    	
    	$this->_helper->_layout->setLayout('layoutpreview');
    	
    	// Log Access
    	System_Model_Log::access();
    	
    	 $sUser = System_Model_SUser::getInstance();
        
        if (!$sUser->hasData()) {
            $this->_helper->json($responce);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->json($responce);
        }

        $canView = $sUser->isAllow('calendar.article.view');
    	
    	if ($canView) {
    		$hashCode = $this->getRequest()->getParam("code", "");
    		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    		$defaultLanguageCode = $defaultLanguage["Code"];
    		
    		if (Zend_Session::namespaceIsset("Preview" . $hashCode)) {
    			$previewNamespace = new Zend_Session_Namespace("Preview" . $hashCode);
    			$params = $previewNamespace->params;
    			
    			// Model 
    			$fileModel = new System_Model_Fileinfo();
    			
    			// Prepare Data
    			$param = $params["params"][$defaultLanguageCode];
    			$dataDetail = new stdClass();
    			$data = new stdClass();
    			$dataInfo = new stdClass();
    			
    			$dataInfo->TemplateType = $params["TemplateType"];
    			$dataInfo->IntroImageHashName = (count($params["hdfIntroImage"]) > 0) ? $params["hdfIntroImage"][0] : '';
    			    			
    			// Set Data
    			$data->Title = $param["Title"];
    			$data->ViewAmount = 0;
    			$data->Created = new DateTime();
    			$data->SocialNetworkFacebook = $param['SocialNetworkFacebook'];
    			$data->SocialNetworkTwitter = $param['SocialNetworkTwitter'];
    			$data->SocialNetworkGooglePlus = $param['SocialNetworkGooglePlus'];
    			$data->ShowQrcode = $param['ShowQrcode'];
    			$data->ShowPrint = $param['ShowPrint'];
    			$data->Detail = $param['Detail'];
    			$dataFileDocuments = array();
    			if (count($params["hdfFileDocuments"]) > 0) {
    				foreach ($params["hdfFileDocuments"] as $val) {
    					$fileObj = $fileModel->itemByHashName($val);
    					$dataFileDocuments[] = array("hash_name" => $fileObj->HashName, "file_name" => $fileObj->Name);
    				}
    			}
    			$dataFileMultiMedias = array();
    			if (count($params["hdfFileMultiMedias"]) > 0) {
    				foreach ($params["hdfFileMultiMedias"] as $val) {
    					$dataFileMultiMedias[] = array("hash_name" => $val);
    				}
    			}
    			$dataFileImages = array();
    			if (count($params["hdfFileImages"]) > 0) {
    				foreach ($params["hdfFileImages"] as $val) {
    					$dataFileImages[] = array("hash_name" => $val);
    				}
    			}
    			
    			$subStringAdmin = $this->view->baseUrl("");
    			$subStringAdminLen = strlen($subStringAdmin) - 1;
    			
    			$this->view->subStringAdminLen = $subStringAdminLen;
    			
    			// Send Data to View
    			$this->view->lang = $defaultLanguage["Code"];
    			$this->view->params = $params;
    			$this->view->param = $param;
    			$this->view->data = $data;
    			$this->view->dataInfo = $dataInfo;
    			$this->view->dataDetail = $dataDetail;
    			$this->view->dataFileImages = $dataFileImages;
    			$this->view->dataFileDocuments = $dataFileDocuments;
    			$this->view->dataFileMultiMedias = $dataFileMultiMedias;
    			
    			// Clear Session
    			Zend_Session::namespaceUnset("Preview" . $hashCode);
    		} else {
    			// Access Deny
    			$this->_helper->viewRenderer->setNoRender();
    			echo '<div class="well well-lg"><p class="text-center">ไม่สามารถชมในส่วนนี้ได้</p></div>';
    		}
    	} else {
    		// Access Deny
    		$this->_helper->viewRenderer->setNoRender();
    		echo '<div class="well well-lg"><p class="text-center">ไม่สามารถชมในส่วนนี้ได้</p></div>';
    	}
    }

    public function dialogimportexcelAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$this->_helper->layout()->disableLayout();
    }
    
    public function importexcelAction() {
		
		// Init
		$result = array();
		$result['result'] = false;
		$result['message'] = '';
		
        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
           $this->_helper->redirector('index', 'index', 'default');
        }

        $canAdd = $sUser->isAllow('calendar.article.manage');
        if (!$canAdd) {
           $this->_helper->redirector('index', 'article', 'calendar');
        }

		// Get Param
		$params = $this->getRequest()->getParams();
		if($canAdd){
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
                                    "O" => $d["O"],
                                    "P" => $d["P"],
                            );

                            $split = explode(" ",$dataList[$key]['H']);
                            $datePart = $split[0];
                            $timePart = $split[1];
                            $dataList[$key]['H'] = empty($datePart) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($datePart) . ' ' . $timePart);

                            $split = explode(" ",$dataList[$key]['I']);
                            $datePart = $split[0];
                            $timePart = $split[1];
                            $dataList[$key]['I'] = empty($datePart) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($datePart) . ' ' . $timePart);

                            $split = explode(" ",$dataList[$key]['L']);
                            $datePart = $split[0];
                            $timePart = $split[1];
                            $dataList[$key]['L'] = empty($datePart) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($datePart) . ' ' . $timePart);

                            $split = explode(" ",$dataList[$key]['M']);
                            $datePart = $split[0];
                            $timePart = $split[1];
                            $dataList[$key]['M'] = empty($datePart) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($datePart) . ' ' . $timePart);

                        }
                        $row++;
                    }
                }
                
                // Check Data
                $checkData = true;
                if(strpos($dataList['row-1']['A'], 'ชื่อ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['B'], 'ข้อความเกริ่นนำ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['C'], 'สถานที่') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['D'], 'ข้อมูลการติดต่อ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['E'], 'เบอร์โทรศัพท์') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['F'], 'ลิงค์ของกิจกรรม') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['G'], 'ชื่อหน่วยงานที่จัดกิจกรรม') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['H'], 'วันที่เริ่มต้น') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['I'], 'วันที่สิ้นสุด') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['J'], 'พิกัด Latitude') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['K'], 'พิกัด Longitude') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['L'], 'วันที่ประกาศ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['M'], 'วันที่ปิดประกาศ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['N'], 'รายละเอียด') === false) { $checkData=false; }
                if(strpos($dataList['row-2']['A'], 'ตัวอย่างข้อมูล : กรุณากรอกข้อมูลตาม Format ที่กำหนดให้เท่านั้น ระบบจะไม่ทำการอัพโหลดข้อมูลในแถวนี้ (กรุณาอย่าลบ)') === false) { $checkData=false; }
                
                if(!$checkData){
                    $result['message'] = 'ไฟล์ข้อมูลไม่ถูกต้อง';
                    $this->_helper->json($result);
                }

                unset($dataList['row-1']);
                unset($dataList['row-2']);

                // Save
                $model = new Calendar_Model_Article();
                $data = $model->importExcel($dataList);
        
			if($data->result){
                $result['result'] = true;
                $result['message'] = 'นำเข้าข้อมูล';
			}else{
				$result['message'] = $data->message; //'นำเข้าข้อมูลไม่สำเร็จ';
			}
			
            } catch (Exception $e) {
                $result['message'] = 'การเชื่อมต่อข้อมูลผิดพลาด กรุณาตรวจสอบไฟล์ข้อมูลของท่าน';
            }
        }
		
		// Return Result
		$this->_helper->json($result);
    }

    public function dialogimportcsvAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$this->_helper->layout()->disableLayout();
    }
    
    public function importcsvAction() {
		
		// Init
		$result = array();
		$result['result'] = false;
		$result['message'] = '';
		
        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
           $this->_helper->redirector('index', 'index', 'default');
        }
        $canAdd = $sUser->isAllow('calendar.article.manage');
        
        if (!$canAdd) {
           $this->_helper->redirector('index', 'article', 'calendar');
        }
		
		// Get Param
		$params = $this->getRequest()->getParams();
		if($canAdd){
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

                $key = 1;
                $dataList = array();
                
                $file = fopen($tmpFileName,"r");
                while(!feof($file)){
                    $dataList['row-'.$key] = fgetcsv($file);
                    $key++;
                }
                fclose($file);

                // Prepare Data List
                if(count($dataList)>0){
                    foreach($dataList as $k=>$v){

                        if($k=='row-1'){
                            continue;
                        }
                        if(count($dataList[$k])!=14){
                            unset($dataList[$k]);
                            continue;
                        }
                        
                        $split = explode(" ",$dataList[$k]['7']);
                        $datePart = $split[0];
                        $timePart = $split[1];
                        $dataList[$k]['7'] = empty($datePart) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($datePart) . ' ' . $timePart);

                        $split = explode(" ",$dataList[$k]['8']);
                        $datePart = $split[0];
                        $timePart = $split[1];
                        $dataList[$k]['8'] = empty($datePart) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($datePart) . ' ' . $timePart);

                        $split = explode(" ",$dataList[$k]['11']);
                        $datePart = $split[0];
                        $timePart = $split[1];
                        $dataList[$k]['11'] = empty($datePart) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($datePart) . ' ' . $timePart);

                        $split = explode(" ",$dataList[$k]['12']);
                        $datePart = $split[0];
                        $timePart = $split[1];
                        $dataList[$k]['12'] = empty($datePart) ? null : (Emwork_Utility_DateTime::dateThaiToMySQL($datePart) . ' ' . $timePart);
                    }
                }
                
                // Check Data
                $checkData = true;
                if(strpos($dataList['row-1']['0'], 'ชื่อ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['1'], 'ข้อความเกริ่นนำ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['2'], 'สถานที่') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['3'], 'ข้อมูลการติดต่อ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['4'], 'เบอร์โทรศัพท์') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['5'], 'ลิงค์ของกิจกรรม') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['6'], 'ชื่อหน่วยงานที่จัดกิจกรรม') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['7'], 'วันที่เริ่มต้น') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['8'], 'วันที่สิ้นสุด') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['9'], 'พิกัด Latitude') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['10'], 'พิกัด Longitude') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['11'], 'วันที่ประกาศ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['12'], 'วันที่ปิดประกาศ') === false) { $checkData=false; }
                if(strpos($dataList['row-1']['13'], 'รายละเอียด') === false) { $checkData=false; }
                
                if(!$checkData){
                    $result['message'] = 'ไฟล์ข้อมูลไม่ถูกต้อง';
                    $this->_helper->json($result);
                }

                unset($dataList['row-1']);

                // Save
                $model = new Calendar_Model_Article();
                $data = $model->importCSV($dataList);
            
                    if($data->result){
                        $result['result'] = true;
                        $result['message'] = 'นำเข้าข้อมูล';
                    }else{
                        $result['message'] = $data->message; //'นำเข้าข้อมูลไม่สำเร็จ';
                    }
                    
                } catch (Exception $e) {
                    $result['message'] = 'การเชื่อมต่อข้อมูลผิดพลาด กรุณาตรวจสอบไฟล์ข้อมูลของท่าน';
                }
            }
		
		// Return Result
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
		$canSave = $sUser->isAllow('calendar.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Calendar_Model_Article();
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
		$canSave = $sUser->isAllow('calendar.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Calendar_Model_Article();
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
		$canSave = $sUser->isAllow('calendar.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Calendar_Model_Article();
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

