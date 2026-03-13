<?php

class Story_ArticleController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	protected $commentStatusOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่', 'S' => 'สแปม');
	protected $canCommentTypeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่'); 
	protected $componentTitle = "เรื่องราว";
    
    public function init()
    {
    	$moduleConfig = new Zend_Config_Ini(APPLICATION_PATH . '/modules/story/configs/config.ini', 'text');
    	$this->componentTitle = empty($moduleConfig->name) ? $this->componentTitle : $moduleConfig->name;
    	$this->view->componentTitle = $this->componentTitle;
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
        $canView = $sUser->isAllow('story.article.view');
        $canAdd = $sUser->isAllow('story.article.manage');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("StoryArticleSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("StoryArticleSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("StoryArticleSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Story_Model_Category();
        $storyCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->storyCategoryOption = $storyCategoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการ" . $this->componentTitle;
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการ' . $this->componentTitle)
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
        $canView = $sUser->isAllow('story.article.view');

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
        
        $infoSearchNamespace = new Zend_Session_Namespace("StoryArticleSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $model = new Story_Model_Article();
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
        $canAdd = $sUser->isAllow('story.article.manage');
        
        if (!$canAdd) {
           $this->_helper->redirector('index', 'article', 'story');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $storyCategoryModel = new Story_Model_Category();
        $storyCategoryOption = $storyCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting 
        $defaultCanComment = System_Model_Setting::getValue("ArticleCanComment", "Story");
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
        $this->view->storyCategoryOption = $storyCategoryOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "เพิ่ม" . $this->componentTitle;
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
        	array('name' => 'รายการ' . $this->componentTitle, 'url' => $this->view->url(array('module' => 'story', 'controller' => 'article', 'action' => 'index'), null, true)),
        	array('name' => 'เพิ่ม' . $this->componentTitle)
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
        $canAdd = $sUser->isAllow('story.article.manage');
        if (!$canAdd) {
           $result['message'] = 'ไม่สามารถบันทึกได้';
           $this->_helper->json($result);
        }
        
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Story_Model_Article();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];

        // Prepare Data
        $parameter = $this->prepareData($params);

        // Seve Data
        $data = $model->save($parameter);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("story.article", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canDelete = $sUser->isAllow('story.article.manage');
        $canEdit = $sUser->isAllow('story.article.manage');
        $canView = $sUser->isAllow('story.article.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'article', 'story');
        }

        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'article', 'story');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $storyCategoryModel = new Story_Model_Category();
        $storyCategoryOption = $storyCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting
        $defaultCanComment = System_Model_Setting::getValue("ArticleCanComment", "Story");
        $canCommentTypeOption = $this->canCommentTypeOption;
        $canCommentTypeOption["D"] = $canCommentTypeOption["D"] . " (" . $this->yesNoOption[$defaultCanComment] . ")"; 
        
        $model = new Story_Model_Article();
        
        // Get Data
        $data = $model->getInfo($itemID);  
        if ($data->IsDeleted=='Y') {
        	$this->_helper->redirector('index', 'article', 'story');
        }      
        
        // Detail
        $dataDetails = array();        
        // CoverImage
        $dataCoverImageDetails = array();
        $dataCoverImageDetailList = array();        
        if (count($languages) > 0) {
        	foreach ($languages as $lang) {
        		$langCode = $lang['Code'];
        		$detail = $model->getDataByLanguage($data->ID, $langCode);
        		if (!empty($detail->Lang)) {
        			$dataDetails[$langCode] = $detail;
        		}
        		
        		// CoverImage
        		$coverImageDetail = $model->getCoverByLanguage($data->ID, $langCode); 
        		if (count($coverImageDetail) > 0) {
        			foreach ($coverImageDetail as $coverDetail) {
        				$fileModel = new System_Model_Fileinfo();
        				$fileData = $fileModel->item($coverDetail['FileInfoID']);
        				$Picture = array();
        				if(!empty($fileData->Name)) {
        					$Picture[] = array(
        							"file_name" => $fileData->Name,
        							"size" => $fileData->FileSize,
        							"type" => $fileData->FileType,
        							"hash_name" => $fileData->HashName,
        					);
        					$coverDetail['CoverImage'] = $Picture; // TODO
        				}else{
        					$coverDetail['CoverImage'] = null;
        				}
        				
        				if (array_key_exists($coverDetail['DataCode'], $dataCoverImageDetailList)) {
        					$dataCoverImageDetailList[$coverDetail['DataCode']][] = $coverDetail;
        				} else {
        					$dataCoverImageDetailList[$coverDetail['DataCode']] = array();
        					$dataCoverImageDetailList[$coverDetail['DataCode']][] = $coverDetail;
        				}
        			}
        		}
        		
        	}
        }
        
        $dataCategories = $model->getCategories($data->ID);
        $dataCategoryIds = array();
        if (count($dataCategories) > 0) {
        	foreach ($dataCategories as $dataCategory) {
        		$dataCategoryIds[] = $dataCategory['StoryCategoryID'];
        	}
        }
        
        // Intro Image
        $dataIntroImages = array();
        if(!empty($data->IntroImageID)) {
        	$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
        }

        $storyArticle = $model->getInfoData($data->ID);
        $storyArticleData = array();
        if(count($storyArticle)>0){
            foreach($storyArticle as $k=>$v){
                $storyArticleData[$v['Lang']][] = $v;
            }
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
        $this->view->storyArticleData = $storyArticleData;
        $this->view->dataCoverImageList = $dataCoverImageDetailList;
        $this->view->dataFileImages = $dataFileImages;
        $this->view->dataFileDocuments = $dataFileDocuments;
        $this->view->dataFileMultiMedias = $dataFileMultiMedias;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->storyCategoryOption = $storyCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->canCommentTypeOption = $canCommentTypeOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "ปรับปรุง" . $this->componentTitle;
        $this->view->pageIcon= "fa fa-newspaper-o";
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
        	array('name' => 'รายการ' . $this->componentTitle, 'url' => $this->view->url(array('module' => 'story', 'controller' => 'article', 'action' => 'index'), null, true)),
        	array('name' => 'ปรับปรุง' . $this->componentTitle)
        );
        
        // Log Acion
        System_Model_Log::action("story.article", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
        $canSave = $sUser->isAllow('story.article.manage');
        if (!$canSave) {
           $result['message'] = 'ไม่สามารถบันทึกได้';
           $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Story_Model_Article();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        // Prepare Data
        $parameter = $this->prepareData($params);

        // Update Data
        $data = $model->update($parameter);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("story.article", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canSave = $sUser->isAllow('story.article.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Story_Model_Article();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            $model = new Story_Model_Article();		
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $detail = $model->getDataByLanguage($params['id'], $defaultLanguage['Code']);
            System_Model_Log::action("story.article", "delete", $detail->Title, $detail->ID);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }
    
    public function coverformAction() {
    	
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
        $canAdd = $sUser->isAllow('story.article.manage');

        if (!$canAdd) {
            $this->_helper->redirector('index', 'article', 'story');
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
    	if (count($languages) > 0) {
    		foreach ($languages as $lang) {
    			$langCode = $lang['Code'];
    			$details[$langCode] = new stdClass();
    			$details[$langCode]->Title = '';
    			$details[$langCode]->IntroText = '';
    			$details[$langCode]->CoverImage = null;
    			if (!empty($data)) {
    				if ((count($dataObj['Lang']) > 0) && (array_key_exists($langCode, $dataObj['Lang']))) {
    					$details[$langCode]->Title = $dataObj['Lang'][$langCode]["Title"];
    					$details[$langCode]->IntroText = $dataObj['Lang'][$langCode]["IntroText"];
    				}
    			}
    		}
    	}
    	
    	// Prepare ProductImage
    	$fileModel = new System_Model_Fileinfo();
    	$fileData = $fileModel->itemByHashName($dataObj['CoverImage']);
    	
    	$Picture = array();
    	if(!empty($fileData->Name)) {
    		$Picture[] = array(
    				"file_name" => $fileData->Name,
    				"size" => $fileData->FileSize,
    				"type" => $fileData->FileType,
    				"hash_name" => $fileData->HashName,
    		);
    		$details['CoverImage'] = $Picture;
    	}else{
    		$details['CoverImage'] = null;
    	}
    	
    	// Send Data to view
    	$this->view->ID = $ID;
    	$this->view->dataDetails = $details;
    	$this->view->defaultLanguage = $defaultLanguage['Code'];
    	$this->view->languages = $languages;
    }
    
    private function prepareData($params) {
        
        $languages = System_Model_Setting::getLanguages();
        $fileModel = new System_Model_Fileinfo();
            
        // IntroImage
        $introImageID = null;
        if (count($params['hdfIntroImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
        	if ($fileData->ID != null) {
        		$introImageID = $fileData->ID;
            }
            $params['IntroImageID'] = $introImageID;
        }else{
        	$params['IntroImageID'] = null;
        }
        
        // Prepare Document
        if(count($params['Document'])>0){
            $documentList = array();
            foreach ($params['Document'] as $k2=>$v2){
                if(!empty($params[$v2]['IntroText'])){
                    $documentList[$v2] = $params[$v2]['IntroText'];
                }
            }
        }
        
        // Prepare Gallery
        if(count($params['Gallery'])>0){
            $galleryList = array();
            foreach ($params['Gallery'] as $k2=>$v2){
                if(count($params['hdf'.$v2])>0){
                    $galleryIds = array();
                    foreach ($params['hdf'.$v2] as $imageHashName){
                        unset($fileData);
                        $fileData = $fileModel->itemByHashName($imageHashName);
                        if ($fileData->ID != null) {
                            $galleryIds[] = $fileData->ID;
                        }
                    }
                    $galleryList[$v2] = $galleryIds;
                }
            }
        }
        
        // Prepare Mutimedia
        if(count($params['MultimediaType'])>0){
            $multimediaList = array();
            foreach ($params['MultimediaType'] as $k2=>$v2){
                $multimediaList['Multimedia'.substr($v2,7,37)]['type'] = $params[$v2];
                $multimediaList['Multimedia'.substr($v2,7,37)]['url'] = $params['txtYoutubeUrl'.substr($v2,7,37)];
                $imageHashName = $params['hdfMultimedia'.substr($v2,7,37)][0];
                if($imageHashName){
                    unset($fileData);
                    $fileData = $fileModel->itemByHashName($imageHashName);
                    $multimediaList['Multimedia'.substr($v2,7,37)]['fileId'] = $fileData->ID;
                }
            }
        }
		
		// Prepare Data Sortable
        foreach ($languages as $lang){

            if(count($params['Sortable'][$lang['Code']])>0){
                $i = 1;
                foreach ($params['Sortable'][$lang['Code']] as $sortKey){

                    $hahData = false;
                    
                    if(count($documentList)>0){
                        foreach ($documentList as $k=>$v){
                            if($k == $sortKey){
                                $params['sortable'][$lang['Code']][$i]['id'] = substr($k,9,45);
                                $params['sortable'][$lang['Code']][$i]['type'] = "D";
                                $params['sortable'][$lang['Code']][$i]['data'] = $v;
                                unset($documentList[$k]);
                                break;
                                $hahData = true;
                            }
                        }
                    }
                    
                    if(count($galleryList)>0 && !$hahData){
                        foreach ($galleryList as $k=>$v){
                            if($k == $sortKey){
                                $params['sortable'][$lang['Code']][$i]['id'] = substr($k,8,44);
                                $params['sortable'][$lang['Code']][$i]['type'] = "G";
                                $params['sortable'][$lang['Code']][$i]['data'] = $v;
                                unset($documentList[$k]);
                                break;
                                $hahData = true;
                            }
                        }
                    }
                    
                    if(count($multimediaList)>0 && !$hahData){
                        foreach ($multimediaList as $k=>$v){
                            if($k == $sortKey){
                                $params['sortable'][$lang['Code']][$i]['id'] = substr($k,11,47);
                                $params['sortable'][$lang['Code']][$i]['type'] = "M";
                                $params['sortable'][$lang['Code']][$i]['data'] = $v;
                                unset($documentList[$k]);
                                break;
                                $hahData = true;
                            }
                        }
                    }
                    
                    $i++;
                }
            }
        } 
        
        // File Document
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
		
		return $params;
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
        $canView = $sUser->isAllow('story.article.view');
        $canAdd = $sUser->isAllow('story.article.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
    	
    	$itemID = $this->getRequest()->getParam('itemid');
    	if (empty($itemID)) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	
    	// $InfoSearch = new stdClass();
    	// if (Zend_Session::namespaceIsset("StoryArticleSearch")) {
    	// 	$infoSearchNamespace = new Zend_Session_Namespace("StoryArticleSearch");
    	// 	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
    	// 	$InfoSearch->category= $infoSearchNamespace->category;
    	// 	$InfoSearch->isenable= $infoSearchNamespace->isenable;
    	// 	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
    	// 	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
    	// 	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
    	// 	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;    		
    	// 	$this->view->InfoSearch = $InfoSearch;
    	// 	Zend_Session::namespaceUnset("StoryArticleSearch");
    	// }
    	
    	$languages = System_Model_Setting::getLanguages();
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    	
    	// Get Option
    	$modelOption = new Story_Model_Category();
    	$storyCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
    	
    	// Send To View
    	$this->view->itemID = $itemID;
    	
    	// Set View
    	$this->view->canManage = $canManage;
    	$this->view->pageName = "ความคิดเห็น";
    	$this->view->pageIcon = "fa fa-newspaper-o";
    	$this->view->subMenus = array(
    			array('name' => 'รายละเอียด' . $this->componentTitle, 'active' => false, 'url' => $this->view->url(array('module' => 'story', 'controller' => 'article', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'ความคิดเห็น', 'active' => true),
    	);
    	$this->view->breadcrumb = array(
    			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
    			array('name' => 'รายการ' . $this->componentTitle, 'url' => $this->view->url(array('module' => 'story', 'controller' => 'article', 'action' => 'index'), null, true)),
    			array('name' => 'ปรับปรุง' . $this->componentTitle, 'url' => $this->view->url(array('module' => 'story', 'controller' => 'article', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'ความคิดเห็น')
        );
        
    	$model = new Story_Model_Article();		
        $detail = $model->getDataByLanguage($itemID, $defaultLanguage['Code']);
        System_Model_Log::action("story.article", "view", "ความคิดเห็น : ".$detail->Title, $detail->ID);
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
        $canView = $sUser->isAllow('story.article.view');
    	
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
    	
    	// $infoSearchNamespace = new Zend_Session_Namespace("StoryArticleSearch");
    	// $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
    	// $infoSearchNamespace->category= $advancesearch['isCat'];
    	// $infoSearchNamespace->isenable= $advancesearch['isEnable'];
    	// $infoSearchNamespace->tblPage = $_start;
    	// $infoSearchNamespace->tblRow = $_length;
    	// $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
    	// $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
    	
    	$model = new Story_Model_Comment();
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
        $canSave = $sUser->isAllow('story.article.manage');
        if (!$canSave) {
           $result['message'] = 'ไม่สามารถบันทึกได้';
           $this->_helper->json($result);
        }
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Update
    	$model = new Story_Model_Comment();
    	$data = $model->updateStatus($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
            
            // Log Acion
            $model = new Story_Model_Article();		
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $detail = $model->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
            System_Model_Log::action("story.article", "update", "ความคิดเห็น : ".$detail->Title, $detail->ID);
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
        $canSave = $sUser->isAllow('story.article.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Update
    	$model = new Story_Model_Comment();
    	$data = $model->delete($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';

            // Log Acion
            $model = new Story_Model_Article();		
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $detail = $model->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
            System_Model_Log::action("story.article", "delete", "ความคิดเห็น : ".$detail->Title, $detail->ID);
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ลบไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }
    
    public function previewAction() {
    	$this->_helper->layout()->disableLayout();
    	
    	$sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->json($responce);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->json($responce);
        }
        $canView = $sUser->isAllow('story.article.view');
    	
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
    	
    	$sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->json($responce);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->json($responce);
        }
        $canView = $sUser->isAllow('story.article.view');
    	
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        $fileModel = new System_Model_Fileinfo();
        
        $hashCode = $this->getRequest()->getParam("code", "");
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        
        if (Zend_Session::namespaceIsset("Preview" . $hashCode)) {
        	$previewNamespace = new Zend_Session_Namespace("Preview" . $hashCode);
            $params = $previewNamespace->params;
            Zend_Session::namespaceUnset("Preview" . $hashCode);
        }
        
        $params = $this->prepareData($params);
        $param = $params['params'];

        // Cover Image
        $dataCover = array();
        if($param[$defaultLanguageCode]['Cover'] > 0) {
            $i = 0;
            
            foreach ($param[$defaultLanguageCode]['Cover'] as $val) {
                $obj = json_decode($val, true);
                $coverImageData = $fileModel->itemByHashName($obj['CoverImage']);
                $dataCover[$i]['Title'] = $obj['Lang'][$defaultLanguageCode]['Title'];
                $dataCover[$i]['IntroText'] = $obj['Lang'][$defaultLanguageCode]['IntroText'];
                $dataCover[$i]['FileInfoHashName'] = $coverImageData->HashName;
                $i++;
            }
        }
        
        // File Document
        $dataFileDocuments = array();
        if (count($params["hdfFileDocuments"]) > 0) {
        	foreach ($params["hdfFileDocuments"] as $val) {
        		$fileObj = $fileModel->itemByHashName($val);
        		$dataFileDocuments[] = array("hash_name" => $fileObj->HashName, "file_name" => $fileObj->Name);
        	}
        }



        // dataInfo
        $dataInfo = $param[$defaultLanguageCode];
        $dataInfo['FileHashName'] = new StdClass();
        if(count($params['hdfIntroImage'])>0) {
            $IntroImage = $fileModel->itemByHashName($obj['CoverImage']);
            $dataInfo['FileHashName'] = $IntroImage->HashName;
        }

        $Created = date("Y-m-d H:i:s");
        $Created = new DateTime($Created);
        
        $subStringAdmin = $this->view->baseUrl("");
        $subStringAdminLen = strlen($subStringAdmin) - 1;        
        $this->view->subStringAdminLen = $subStringAdminLen;
        
        $this->view->TemplateType = $params['TemplateType'];
        $this->view->dataCover = $dataCover;
        $this->view->dataInfo = $dataInfo;
        $this->view->dataDetail = $params['sortable'][$defaultLanguageCode];
        $this->view->dataFileDocuments = $dataFileDocuments;
        $this->view->Created = $Created;
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
		$canSave = $sUser->isAllow('story.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Story_Model_Article();
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
		$canSave = $sUser->isAllow('story.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Story_Model_Article();
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
		$canSave = $sUser->isAllow('story.article.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Story_Model_Article();
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

    public function dialogsortingAction() {
    	
    	$this->_helper->layout()->disableLayout();
    	
    	$sUser = System_Model_SUser::getInstance();
    	if (!$sUser->hasData()) {
    		
    	}
    	if ($sUser->getInfo()->UserType != 'B') {
    		
    	}
    	$canManage = $sUser->isAllow("wat.item.manage");
    	if (!$canManage) {
    		
    	}
    	
    	$languages = System_Model_Setting::getLanguages();
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    	
    	// Get Params
        $data =  $this->getRequest()->getParam('data');

        // Send Data to view
    	$this->view->data = $data;
    }

}

