<?php

class Ebook_EbookController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	protected $commentStatusOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่', 'S' => 'สแปม');
	protected $canCommentTypeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
    protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
    protected $DefaultCustomizeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
    
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
        $canView = $sUser->isAllow('ebook.ebook.view');
        $canAdd = $sUser->isAllow('ebook.ebook.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("EbookBookSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("EbookBookSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("EbookBookSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Ebook_Model_Category();
        $ebookCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->ebookCategoryOption = $ebookCategoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการหนังสือ";
        $this->view->pageIcon = "fa fa-book";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการหนังสือ')
            
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
        $canView = $sUser->isAllow('ebook.ebook.view');
        
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
        
        $infoSearchNamespace = new Zend_Session_Namespace("EbookBookSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $model = new Ebook_Model_Ebook();
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
        $canAdd = $sUser->isAllow('ebook.ebook.manage');
        if (!$canAdd) {
            $this->_helper->redirector('index', 'ebook', 'ebook');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $ebookCategoryModel = new Ebook_Model_Category();
        //$ebookCategoryOption = $ebookCategoryModel->getOption($defaultLanguage["Code"]);
        $ebookCategoryOption = $ebookCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting 
        $defaultCanComment = System_Model_Setting::getValue("EbookCanComment", "Ebook");
        $canCommentTypeOption = $this->canCommentTypeOption;
        $DefaultCustomizeOption = $this->DefaultCustomizeOption;
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
        $this->view->defaultCustomizeOption = $DefaultCustomizeOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->ebookCategoryOption = $ebookCategoryOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "เพิ่มหนังสือ";
        $this->view->pageIcon = "fa fa-book";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการหนังสือ', 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มหนังสือ')
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
        $canAdd = $sUser->isAllow('ebook.ebook.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Ebook_Model_Ebook();
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

        // AuthorImageID
        $authorImageID = null;
        if (count($params['hdfAuthorImageID'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfAuthorImageID'][0]);
        	if ($fileData->ID != null) {
        		$authorImageID = $fileData->ID;
            }
        }
        $params['AuthorImageID'] = $authorImageID;
        
        // Document
        $documentID = null;
        if (count($params['hdfFileDocuments'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfFileDocuments'][0]);
        	if ($fileData->ID != null) {
        		$documentID = $fileData->ID;
            }
        }
        $params['DocumentID'] = $documentID;
      
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("ebook.ebook", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canDelete = $sUser->isAllow('ebook.ebook.manage');
        $canEdit = $sUser->isAllow('ebook.ebook.manage');
        $canView = $sUser->isAllow('ebook.ebook.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'ebook', 'ebook');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'ebook', 'ebook');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $ebookCategoryModel = new Ebook_Model_Category();
        //$ebookCategoryOption = $ebookCategoryModel->getOption($defaultLanguage["Code"]);
        $ebookCategoryOption = $ebookCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting
        $defaultCanComment = System_Model_Setting::getValue("EbookCanComment", "Ebook");
        $canCommentTypeOption = $this->canCommentTypeOption;
        $canCommentTypeOption["D"] = $canCommentTypeOption["D"] . " (" . $this->yesNoOption[$defaultCanComment] . ")"; 
        
        $model = new Ebook_Model_Ebook();
        
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
        		$dataCategoryIds[] = $dataCategory['EbookCategoryID'];
        	}
        }
        
        
        // Intro Image
        $dataIntroImages = array();
        if(!empty($data->IntroImageID)) {
        	$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
        }

        // Author Image
        $dataAuthorImages = array();
        if(!empty($data->AuthorImageID)) {
        	$dataAuthorImages[] = array('file_name'=> $data->AuthorImageFileName, 'size'=> $data->AuthorImageFileSize, 'type'=> $data->AuthorImageFileType, 'hash_name'=> $data->AuthorImageHashName);
        }
        
        $dataFileDocuments = array();
        if(!empty($data->DocumentID)) {
        	$dataFileDocuments[] = array('file_name'=> $data->DocumentName, 'size'=> $data->DocumentFileSize, 'type'=> $data->DocumentType, 'hash_name'=> $data->DocumentHashName);
        }
    
        $DefaultCustomizeOption = $this->DefaultCustomizeOption;
        $this->view->defaultCustomizeOption = $DefaultCustomizeOption;

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
        $this->view->dataAuthorImages = $dataAuthorImages;
        $this->view->dataFileImages = $dataFileImages;
        $this->view->dataFileDocuments = $dataFileDocuments;
        $this->view->dataFileMultiMedias = $dataFileMultiMedias;
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->ebookCategoryOption = $ebookCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->canCommentTypeOption = $canCommentTypeOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "ปรับปรุงหนังสือ";
        $this->view->pageIcon= "fa fa-book";
        $this->view->subMenus = array(
        		array('name' => 'รายละเอียดหนังสือ', 'active' => true),
                array('name' => 'จัดการหน้าหนังสือ', 'active' => false, 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'editpage', 'itemid' => $itemID), null, true)),
                array('name' => 'ความคิดเห็น', 'active' => false, 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'comment', 'itemid' => $itemID), null, true)),
        //		array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'book', 'action' => 'rights', 'itemid' => $itemID), null, true))
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการหนังสือ', 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงหนังสือ')
        );
        
        // Log Acion
        System_Model_Log::action("ebook.ebook", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
        $canSave = $sUser->isAllow('ebook.ebook.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Ebook_Model_Ebook();
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

        // AuthorImageID
        $authorImageID = null;
        if (count($params['hdfAuthorImageID'])>0) {
            $fileData = $fileModel->itemByHashName($params['hdfAuthorImageID'][0]);
            if ($fileData->ID != null) {
                $authorImageID = $fileData->ID;
            }
        }
        $params['AuthorImageID'] = $authorImageID;
        
        // Document
        $documentID = null;
        if (count($params['hdfFileDocuments'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfFileDocuments'][0]);
        	if ($fileData->ID != null) {
        		$documentID = $fileData->ID;
        	}
        }

        $params['DocumentID'] = $documentID;
        
        $data = $model->update($params);

        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("ebook.ebook", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
            
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
        $canSave = $sUser->isAllow('ebook.ebook.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Ebook_Model_Ebook();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("ebook.ebook", "delete", $params['title'], $params["id"]);
            
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }
    
     /********************** setting ************************/
    
     public function editpageAction()
     {

        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->redirector('index', 'index', 'default');
        }
        $canDelete = $sUser->isAllow('ebook.ebook.manage');
        $canEdit = $sUser->isAllow('ebook.ebook.manage');
        $canView = $sUser->isAllow('ebook.ebook.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'ebook', 'ebook');
        }
        
        // Init
        $itemID = $this->getRequest()->getParam('itemid');

        // Send To View
        $this->view->itemID = $itemID;
        $this->view->canDelete = $canDelete;
        $this->view->canEdit = $canEdit;
        
        $model = new Ebook_Model_Ebook();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $data = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);
        
        // Set View
    	$this->view->pageName = "จัดการหน้าหนังสือ";
    	$this->view->pageIcon = "fa fa-file-photo-o";
    	$this->view->subMenus = array(
    			array('name' => 'รายละเอียด', 'active' => false, 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'edit', 'itemid' => $itemID), null, true)),
                array('name' => 'จัดการหน้าหนังสือ', 'active' => true),
                array('name' => 'ความคิดเห็น', 'active' => false, 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'comment', 'itemid' => $itemID), null, true)),
    	//		array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'book', 'action' => 'rights', 'itemid' => $itemID), null, true))
    	);
    	$this->view->breadcrumb = array(
    			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
    			array('name' => 'รายการหนังสือ', 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'index'), null, true)),
    			array('name' => 'ปรับปรุงหนังสือ', 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'จัดการหน้าหนังสือ')
    			
    	);
        System_Model_Log::action("ebook.editpage", "view", $data->Title, $itemID);
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
        $canDelete = $sUser->isAllow('ebook.ebook.manage');
        $canEdit = $sUser->isAllow('ebook.ebook.manage');
        $canView = $sUser->isAllow('ebook.ebook.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'ebook', 'ebook');
        }
    	
    	$itemID = $this->getRequest()->getParam('itemid');
    	if (empty($itemID)) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}

    	$languages = System_Model_Setting::getLanguages();
        
        $modelebook = new Ebook_Model_Ebook();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $datainfo = $modelebook->getDataByLanguage($itemID, $defaultLanguage['Code']);
        System_Model_Log::action("ebook.comment", "view", $datainfo->Title, $itemID);
    	
    	// Get Option
    	$modelOption = new Ebook_Model_Category();
    	$ebookCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
    	// Send To View
        $this->view->itemID = $itemID;
        
    	// Set View
    	$this->view->canEdit = $canEdit;
    	$this->view->canDelete = $canDelete;
    	$this->view->pageName = "ความคิดเห็น";
    	$this->view->pageIcon = "fa fa-newspaper-o";
    	$this->view->subMenus = array(
            array('name' => 'รายละเอียด', 'active' => false, 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'edit', 'itemid' => $itemID), null, true)),
            array('name' => 'จัดการหน้าหนังสือ', 'active' => false, 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'editpage', 'itemid' => $itemID), null, true)),
            array('name' => 'ความคิดเห็น', 'active' => true),
    	);
    	$this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการหนังสือ', 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงหนังสือ', 'url' => $this->view->url(array('module' => 'ebook', 'controller' => 'ebook', 'action' => 'edit', 'itemid' => $itemID), null, true)),
            array('name' => 'ความคิดเห็น')
        );

    }

    public function getpagelistAction()
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
        $canView = $sUser->isAllow('ebook.ebook.view');
        
    	
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
    	$advancesearch['bookID'] = $itemID;
    	
        if($canView):
            $model = new Ebook_Model_Page();
            $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        endif;
    	
    	$i = $_start;
    	if ($canView && $data) {
    		$responce['recordsTotal'] = $data[0]["RECORD"];
    		$responce['recordsFiltered'] = $data[0]["RECORD"];
    		
    		foreach($data as $item){
    			$i++;
    			$d = array();
    			$d['DT_RowId'] = $item["ID"];
                $d['NO'] = $i;
                $d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
                $d['PageIndex'] = Emwork_Utility::notShowNull($item["PageIndex"]);
    			$d['IsEnableCode'] = $item["IsEnable"];
    			$d['IsEnable'] = Emwork_Utility::notShowNull($this->commentStatusOption[$item["IsEnable"]]);
    			$responce['data'][] = $d;
    		}
    	}
    	$this->_helper->json($responce);
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
        $canView = $sUser->isAllow('ebook.ebook.view');
        
    	
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
    	$advancesearch['bookID'] = $itemID;
        
        if($canView):
            $model = new Ebook_Model_Comment();
            $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        endif;
        
    	$i = $_start;
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
            $result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
            $this->_helper->json($result);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
            $this->_helper->json($result);
        }
        $canSave = $sUser->isAllow('ebook.ebook.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
            $this->_helper->json($result);
        }
    	
    	// Param
    	$params = $this->getRequest()->getParams();

    	// Update
    	$model = new Ebook_Model_Comment();
    	$data = $model->updateStatus($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            $modelebook = new Ebook_Model_Ebook();
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $datainfo = $modelebook->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
			System_Model_Log::action("ebook.comment", "update", $datainfo->Title, $params['itemid']);
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ปรับปรุงสถานะไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }

    public function bookpageupdateAction()
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
        $canSave = $sUser->isAllow('ebook.ebook.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
            $this->_helper->json($result);
        }
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Ebook_Model_Page();
    	$data = $model->updateStatus($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
            // Log Acion

            $modelebook = new Ebook_Model_Ebook();
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $datainfo = $modelebook->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
			System_Model_Log::action("ebook.editpage", "update", $datainfo->Title, $params['itemid']);
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
        $canSave = $sUser->isAllow('ebook.ebook.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }

    	// Param
    	$params = $this->getRequest()->getParams();

    	// Update
    	$model = new Ebook_Model_Comment();
    	$data = $model->delete($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
            // Log Acion
            $modelebook = new Ebook_Model_Ebook();
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $datainfo = $modelebook->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
			System_Model_Log::action("ebook.comment", "delete", $datainfo->Title, $data->ID);
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ลบไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }

    public function bookpagedeleteAction()
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
        $canSave = $sUser->isAllow('ebook.ebook.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
    	
    	// Param
    	$params = $this->getRequest()->getParams();

    	// Update
    	$model = new Ebook_Model_Page();
    	$data = $model->deletebook($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
            // Log Acion
            $modelebook = new Ebook_Model_Ebook();
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $datainfo = $modelebook->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
			System_Model_Log::action("ebook.editpage", "delete", $datainfo->Title, $params['itemid']);
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
        $canView = $sUser->isAllow('ebook.ebook.view');
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

    public function addbookpageAction() {
        $this->_helper->layout()->disableLayout();

		$datasetting = System_Model_Setting::getValue("MaximumSizeImage", "Ebook");
        
        $params = $this->getRequest()->getParams();
        $itemid = $params['itemid'];

        $this->view->datasetting = $datasetting;
        $this->view->itemid = $itemid;
    }
    
    public function addpdfpageAction() {
        $this->_helper->layout()->disableLayout();
        
        $datasetting = System_Model_Setting::getValue("MaximumSizePDF", "Ebook");

        $params = $this->getRequest()->getParams();
        $itemID = $params['itemid'];

        $this->view->itemid = $itemID;  
    	$this->view->datasetting = $datasetting;
    }

    public function editbookpageAction() {
    	$this->_helper->layout()->disableLayout();
    	
		$datasetting = System_Model_Setting::getValue("MaximumSizeImage", "Ebook");
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    	
        $params = $this->getRequest()->getParams();
        $fileid = $params['fileid'];

        $model = new Ebook_Model_Page();
        $data = $model->getBookPage($fileid);

        // File
        $BookImage = array();
        if(!empty($data->FileID)) {
            $BookImage[] = array(
                'file_name'=> $data->FileName, 
                'size'=> $data->FileSize, 
                'type'=> $data->FileType, 
                'hash_name'=> $data->HashName
            );
        }        
        $data->BookImage = $BookImage;
        
        unset($data->FileName);
        unset($data->FileSize);
        unset($data->FileType);
        unset($data->HashName);

        $this->view->datasetting = $datasetting;
        $this->view->data = $data;
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
        $canView = $sUser->isAllow('ebook.ebook.view');
    	
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

    public function savebookAction()  {
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
        $canAdd = $sUser->isAllow('ebook.ebook.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $params = $this->getRequest()->getParams();
        $params['itemid'] = $params['hdfID'];

        // TODO
        if(!$params['Title']) {
            $result['message'] = 'กรุณากรอกข้อมูลชื่อ';
            $this->_helper->json($result);
        }
        if(!$params['hdfBookImage']) {
            $result['message'] = 'กรุณากรอกข้อมูลไฟล์รูปภาพหนังสือ';
            $this->_helper->json($result);
        }

        $model = new Ebook_Model_Page();
        $modelebook = new Ebook_Model_Ebook();
        $fileModel = new System_Model_Fileinfo();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $datainfo = $modelebook->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);

        // IntroImage
        $BookImageID = null;
        if (count($params['hdfBookImage'])>0) {
            foreach($params['hdfBookImage'] as $k => $v) {

                $fileData = $fileModel->itemByHashName($v);
                if ($fileData->ID != null) {
                    $BookImageID = $fileData->ID;
                }
                
                $params['BookImageID'] = $BookImageID;
                $data = $model->save($params);
            }
        }
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("ebook.editpage", "save", $datainfo->Title, $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

    public function updatebookAction()  {

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
        $canSave = $sUser->isAllow('ebook.ebook.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }

        // Init
        $params = $this->getRequest()->getParams();

        if (!$params['hdfBookImage']) {
            $result['message'] = 'กรุณากรอกข้อมูลไฟล์รูปภาพหนังสือ';
            $this->_helper->json($result);
        }

        $fileModel = new System_Model_Fileinfo();
        $BookImageID = null;
        if (count($params['hdfBookImage'])>0) {
            $fileData = $fileModel->itemByHashName($params['hdfBookImage'][0]);
            if ($fileData->ID != null) {
                $BookImageID = $fileData->ID;
            }
        }
        $params['BookImageID'] = $BookImageID;

        $model = new Ebook_Model_Page();
        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("ebook.editpage", "update", $params['Title'], $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

    public function savepdfAction()  {
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
        $canAdd = $sUser->isAllow('ebook.ebook.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
       
        $params = $this->getRequest()->getParams();
        $params['itemid'] = $params['hdfID'];

        if(!$params['Title']) {
            $result['message'] = 'กรุณากรอกข้อมูลชื่อ';
            $this->_helper->json($result);
        }

        if(!$params['hdfFileDocument']) {
            $result['message'] = 'กรุณากรอกข้อมูลไฟล์หนังสือรูปแบบ PDF';
            $this->_helper->json($result);
        }
        // IntroImage
        $fileModel = new System_Model_Fileinfo();
        $BookFileDocument = null;
        if (count($params['hdfFileDocument'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfFileDocument'][0]);
        	if ($fileData->ID != null) {
        		$BookFileDocument = $fileData->ID;
            }
        }
        $params['BookFileDocumentID'] = $BookFileDocument;

        $result = $this->pdftoimg($params);
        
        $modelebook = new Ebook_Model_Ebook();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $datainfo = $modelebook->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
        System_Model_Log::action("ebook.editpage", "save", $datainfo->Title, $params['itemid']);

        $this->_helper->json($result);
    }

    private function pdftoimg($params)
    {
        ini_set('max_execution_time', 300);
        
        $result = new stdClass();
        $result->result = false;

        //Delete file in folder

        $dir = (APPLICATION_ROOT . DS .'tmp' . DS . 'ebook');
        $pdfebook = scandir($dir);

        if(count($pdfebook)){
            foreach ($pdfebook as $namefile){

                if($namefile != "." && $namefile != "..") {
                    $file = $dir.DS.$namefile;
                    (unlink($file));
                }
            }
        }

        $model = new Ebook_Model_Page();
        $data = $model->getfilepdf($params['BookFileDocumentID']);
        $pathpdf = (APPLICATION_ROOT . DS . 'files' . $data->Path . DS . $data->HashName);


        $imagick = new Imagick();

        $imagick->setResolution(200, 200);
       
        $imagick->readImage($pathpdf);

        $imagick->scaleImage(1200, 0);

        $imagick->setImageFormat('jpg');

        $imagick->writeImages(APPLICATION_ROOT . DS .'tmp'. DS . 'ebook' . DS . 'p.jpg', false);
   
        $data = $this->savepicpdf($params);

        if($data->result) {
            $result->result = true;
        }

        return $result;
    }

    private function savepicpdf($params)  {

        $result = new stdClass();
        $result->result = false;
        
        $dir = (APPLICATION_ROOT . DS .'tmp' . DS . 'ebook');
        $imgebook = scandir($dir);
        natsort($imgebook);

        //Upload file image to file Info
        $uploadRootPath = APPLICATION_ROOT . DS . 'files';
        $storePath = '/' . date('Y') . '/' . date('m') . '/' . date('d');
        
        $pathToUploads = $uploadRootPath . $storePath;
        $pathToUploads = str_replace('/', DS, $pathToUploads);
        $pathToUploads = str_replace("\\", DS, $pathToUploads);

        if(count($imgebook)) {
            foreach ($imgebook as $name) {

                if (file_exists($pathToUploads)) {
                    $resultPath = true;
                } else {
                    $resultPath = mkdir($pathToUploads, 0777, true);
                }
                
                if($resultPath && $name!="." && $name!="..") {
                    
                    $namefile = $dir.DS.$name;

                    $strpos = strpos($namefile, '?');
                
                    if($strpos) {
                        $namefile = substr($namefile, 0, $strpos);
                    }

                    $img = file_get_contents($namefile);        
                    $namefile_info = pathinfo($namefile);           
                    $baseName = $namefile_info['basename'];           
                    $fileType = Emwork_Utility_File::returnMIMEType($baseName);            
                    $fileName = pathinfo($baseName, PATHINFO_FILENAME);           
                    $ext = pathinfo($baseName,  PATHINFO_EXTENSION);            
                    $hashFileName = date('Ymd') . md5($fileName) . date('His');            
                    $hashBaseName = $hashFileName . '.' . $ext;            
                    $save_to = $pathToUploads . DS . $hashBaseName;
                    $fileSize = file_put_contents($save_to, $img);
                    
                    if($fileSize) {
                        $modelFile = new System_Model_Fileinfo();
                        $data = $modelFile->save($baseName, $hashBaseName, $storePath, $fileSize, $ext, $fileType);
                        $params['ImagepdfID'] = $data->id;
                        $model = new Ebook_Model_Page();
                        $data = $model->savepdf($params);

                        if($data->result) {
                            $result->result = true;
                        }
                    }
                }
            }
        }

        return $result;
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
		$canSave = $sUser->isAllow('ebook.ebook.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Ebook_Model_Ebook();
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
		$canSave = $sUser->isAllow('ebook.ebook.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Ebook_Model_Ebook();
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
		$canSave = $sUser->isAllow('ebook.ebook.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Ebook_Model_Ebook();
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

