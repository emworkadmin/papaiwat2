<?php

class Content_ArticleController extends Zend_Controller_Action
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
        $canView = $sUser->isAllow('article.article.view');
        $canAdd = $sUser->isAllow('article.article.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("ContentArticleSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("ContentArticleSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("ContentArticleSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Content_Model_Category();
        $contentCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->contentCategoryOption = $contentCategoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการบทความ";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการบทความ')
            
        );
    }
    
    public function getlistAction()
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
        
        $canView = $sUser->isAllow('article.article.view');
        
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
        
        $infoSearchNamespace = new Zend_Session_Namespace("ContentArticleSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        if($canView):
        $model = new Content_Model_Article();
        $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        endif;
        
        $i = $_start;
        if ($data) {
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
        $canAdd = $sUser->isAllow('article.article.manage');
        
        if (!$canAdd) {
            $this->_helper->redirector('index', 'article', 'content');
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $contentCategoryModel = new Content_Model_Category();
        //$contentCategoryOption = $contentCategoryModel->getOption($defaultLanguage["Code"]);
        $contentCategoryOption = $contentCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting 
        $defaultCanComment = System_Model_Setting::getValue("ArticleCanComment", "Content");
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
        $this->view->contentCategoryOption = $contentCategoryOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "เพิ่มบทความ";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการบทความ', 'url' => $this->view->url(array('module' => 'content', 'controller' => 'article', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มบทความ')
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
        $canAdd = $sUser->isAllow('article.article.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Content_Model_Article();
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
            System_Model_Log::action("content.article", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canDelete = $sUser->isAllow('article.article.manage');
        $canEdit = $sUser->isAllow('article.article.manage');
        $canView = $sUser->isAllow('article.article.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'article', 'content');
        }

        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'article', 'content');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $contentCategoryModel = new Content_Model_Category();
        //$contentCategoryOption = $contentCategoryModel->getOption($defaultLanguage["Code"]);
        $contentCategoryOption = $contentCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting
        $defaultCanComment = System_Model_Setting::getValue("ArticleCanComment", "Content");
        $canCommentTypeOption = $this->canCommentTypeOption;
        $canCommentTypeOption["D"] = $canCommentTypeOption["D"] . " (" . $this->yesNoOption[$defaultCanComment] . ")"; 
        
        $model = new Content_Model_Article();
        
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
        		$dataCategoryIds[] = $dataCategory['ContentCategoryID'];
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
        //Tag
        $AllKeyword = $model->getAllKeywordByArticle($data->ID);
        $dataTag = array();
        if (count($AllKeyword) > 0) {
        	foreach ($AllKeyword as $dataAllTag) {
        		$dataTag[] = $dataAllTag['Title'];
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
        $this->view->dataTag=$dataTag;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->contentCategoryOption = $contentCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->canCommentTypeOption = $canCommentTypeOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "ปรับปรุงบทความ";
        $this->view->pageIcon= "fa fa-newspaper-o";
        $this->view->subMenus = array(
        		array('name' => 'รายละเอียดบทความ', 'active' => true),
        		array('name' => 'ความคิดเห็น', 'active' => false, 'url' => $this->view->url(array('module' => 'content', 'controller' => 'article', 'action' => 'comment', 'itemid' => $itemID), null, true)),
        //		array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'content', 'controller' => 'article', 'action' => 'rights', 'itemid' => $itemID), null, true))
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการบทความ', 'url' => $this->view->url(array('module' => 'content', 'controller' => 'article', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงบทความ')
        );
        
        // Log Acion
        System_Model_Log::action("content.article", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
        $canSave = true; // $sUser->isAllow('content.page.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Content_Model_Article();
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
            System_Model_Log::action("content.article", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canSave = true; // $sUser->isAllow('content.page.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Content_Model_Article();
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        $dataOld = $model->getDataByLanguage($params["id"], $defaultLanguageCode);
        $title = $dataOld->Title;
        
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("content.article", "delete", $title, $params["id"]);
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
        $canView = $sUser->isAllow('article.article.view');
        $canAdd = $sUser->isAllow('article.article.manage');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
    	
    	$itemID = $this->getRequest()->getParam('itemid');
    	if (empty($itemID)) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	
    	$languages = System_Model_Setting::getLanguages();
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    	
    	// Get Option
    	$modelOption = new Content_Model_Category();
    	$contentCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
    	
    	// Send To View
    	$this->view->itemID = $itemID;
    	
    	// Set View
    	$this->view->canAdd = $canAdd;
    	$this->view->pageName = "ความคิดเห็น";
    	$this->view->pageIcon = "fa fa-newspaper-o";
    	$this->view->subMenus = array(
    			array('name' => 'รายละเอียดบทความ', 'active' => false, 'url' => $this->view->url(array('module' => 'content', 'controller' => 'article', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'ความคิดเห็น', 'active' => true),
    	);
    	$this->view->breadcrumb = array(
    			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
    			array('name' => 'รายการบทความ', 'url' => $this->view->url(array('module' => 'content', 'controller' => 'article', 'action' => 'index'), null, true)),
    			array('name' => 'ปรับปรุงบทความ', 'url' => $this->view->url(array('module' => 'content', 'controller' => 'article', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'ความคิดเห็น')
    			
        );
        $modelContent = new Content_Model_Article();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $datainfo = $modelContent->getDataByLanguage($itemID, $defaultLanguage['Code']);
        System_Model_Log::action("content.comment", "view", $datainfo->Title, $itemID);
    }
    
    public function getcommentlistAction()
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
    	
        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Check Action Rights
        $canView = $sUser->isAllow('article.article.view');

    	
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
    	
        if($canView):
            $model = new Content_Model_Comment();
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
                $d['ArticleTitle'] =  Emwork_Utility::notShowNull($item["Title"]);;
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
    		$result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
    		$this->_helper->json($result);
    	}
    	if ($sUser->getInfo()->UserType != "B") {
    		$result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
    		$this->_helper->json($result);
    	}
    	$canManage = true; // $sUser->isAllow('content.page.manage');
    	if (!$canManage) {
    		$result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
    		$this->_helper->json($result);
    	}
    	
    	// Param
    	$params = $this->getRequest()->getParams();
        
    	// Update
    	$model = new Content_Model_Comment();
        $data = $model->updateStatus($params);
            
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
            $result['message'] = '';
            //Log
            $modelContent = new Content_Model_Article();
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $datainfo = $modelContent->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
            System_Model_Log::action("content.comment", "update", $datainfo->Title, $params['itemid']);
		
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ปรับปรุงสถานะไม่สำเร็จ';
    	}
    	
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
    	$canManage = true; // $sUser->isAllow('content.page.manage');
    	if (!$canManage) {
    		$result['message'] = 'ไม่สามารถลบได้';
    		$this->_helper->json($result);
    	}
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Update
    	$model = new Content_Model_Comment();
        $data = $model->delete($params);

    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
            $result['message'] = '';
            
            //Log
            $modelContent = new Content_Model_Article();
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $datainfo = $modelContent->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
            System_Model_Log::action("content.comment", "view", $datainfo->Title, $params['itemid']);
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
           $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Check Action Rights
        $canView = $sUser->isAllow('article.article.view');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
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
           $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Check Action Rights
        $canView = $sUser->isAllow('article.article.view');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }
    	
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
    			$data->Source = $param['Source'];
    			$dataFileDocuments = array();
    			if (count($params["hdfFileDocuments"]) > 0) {
    				foreach ($params["hdfFileDocuments"] as $val) {
    					$fileObj = $fileModel->itemByHashName($val);
    					$dataFileDocuments[] = array("hash_name" => $fileObj->HashName, "file_name" => $fileObj->Name, "Extension" => $fileObj->Extension);
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
    			$dataTags = array();
    			if (count($params["hdfTag"]) > 0) {
    				foreach ($params["hdfTag"] as $val) {
    					$dataTags[] = array("Title" => $val);
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
    			$this->view->datatag = $dataTags;
    			
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
    public function getkeywordautocompleteAction (){
        // Get Params
        $params = $this->_request->getParams();
        $searchType = $params['searchType'];
    
        // Get Data
        $model = new Content_Model_Article();
        $dataList = $model->getAllKeyword($params['query']);
         
        $data = new stdClass();
        $data->query = "Unit";
         
        $chkDistinct = array();
         
        $suggestions = array();
        if(count($dataList)>0){
            foreach ($dataList as $d){
                if(!empty($d['Title']) && !in_array($d['Title'], $chkDistinct)){
                    $tmp = new stdClass();
                    $tmp->value = $d['Title'];
                    $tmp->data = $d['ID'];
                    $suggestions[] =  $tmp;
                    $chkDistinct[] = $d['Title'];
                }
            }
        }
         
        $data->suggestions =$suggestions;
         
        // Return value
        $this->_helper->json($data);
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
		$canSave = $sUser->isAllow('article.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Content_Model_Article();
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
		$canSave = $sUser->isAllow('article.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Content_Model_Article();
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
		$canSave = $sUser->isAllow('article.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Content_Model_Article();
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

