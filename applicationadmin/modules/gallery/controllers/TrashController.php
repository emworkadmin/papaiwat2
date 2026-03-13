<?php

class Gallery_TrashController extends Zend_Controller_Action
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
        $canView = $sUser->isAllow('gallery.trash.view');
        $canAdd = $sUser->isAllow('gallery.trash.manage');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }

        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("GalleryArticleSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("GalleryArticleSearch");
            $InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->trashdate= $infoSearchNamespace->trashdate;
        	$InfoSearch->trashdateto= $infoSearchNamespace->trashdateto;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("GalleryArticleSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelOption = new Gallery_Model_Category();
        $galleryCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
        

        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->dataDefault=$dataDefault;
        $this->view->galleryCategoryOption = $galleryCategoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "จัดการถังขยะ";
        $this->view->pageIcon = "fa fa-file-image-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'จัดการถังขยะ')
            
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
        
        $canView = $sUser->isAllow('gallery.trash.view');
        
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
        $advancesearch['trashdate'] = $this->getRequest()->getParam('trashdate');
        $advancesearch['trashdateTo'] = $this->getRequest()->getParam('trashdateTo');
        $advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');
        $advancesearch['isCat'] = $this->getRequest()->getParam('isCat');
        
        $infoSearchNamespace = new Zend_Session_Namespace("GalleryArticleSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->trashdate= $advancesearch['trashdate'];
        $infoSearchNamespace->trashdateto= $advancesearch['trashdateTo'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];

        $model = new Gallery_Model_Trash();
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
                $d['TrashDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["TrashDate"], 'DT'));
                $d['TrashByName'] = Emwork_Utility::notShowNull($item["TrashByName"]);
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);
    }

    public function detailAction()
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
        $canDelete = $sUser->isAllow('gallery.trash.manage');
        $canEdit = $sUser->isAllow('gallery.trash.manage');
        $canView = $sUser->isAllow('gallery.trash.view');
        if (!$canView) {
           $this->_helper->redirector('index', 'trash', 'gallery');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'article', 'gallery');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $galleryCategoryModel = new Gallery_Model_Category();
        $galleryCategoryOption = $galleryCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        // Setting UploadFile
        $dataDefault = new stdClass();
        $dataDefault->SizeUploadFile = System_Model_Setting::getValue("SizeUploadFile", "Gallery");

        // Setting
        $defaultCanComment = System_Model_Setting::getValue("ArticleCanComment", "Gallery");
        $canCommentTypeOption = $this->canCommentTypeOption;
        $canCommentTypeOption["D"] = $canCommentTypeOption["D"] . " (" . $this->yesNoOption[$defaultCanComment] . ")"; 

        // Get Data
        $model = new Gallery_Model_Article();
        $data = $model->getInfo($itemID);   
        
        if (!$data->ID) {
        	$this->_helper->redirector('index', 'article', 'gallery');
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
        		$dataCategoryIds[] = $dataCategory['GalleryCategoryID'];
        	}
        }
        
        // Intro Image
        $dataIntroImages = array();
        if(!empty($data->IntroImageID)) {
        	$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
        }

        // $dataFiles = $model->getFiles($data->ID);
        // $dataFileImages = array();
        // $dataFileDocuments = array();
        // $dataFileMultiMedias = array();
        // if (count($dataFiles) > 0) {
        // 	foreach ($dataFiles as $dataFile) {
        // 		if ($dataFile["ArticleFileType"] == "image") {
        // 			$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
        // 		} elseif ($dataFile["ArticleFileType"] == "document") {
        // 			$dataFileDocuments[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
        // 		} elseif ($dataFile["ArticleFileType"] == "multimedia") {
        // 			$dataFileMultiMedias[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
        // 		}
        // 	}
        // }
        
        //Tag
        $AllKeyword = $model->getAllKeywordByArticle($data->ID);
        $dataTag = array();
        if (count($AllKeyword) > 0) {
        	foreach ($AllKeyword as $dataAllTag) {
        		$dataTag[] = $dataAllTag['Title'];
        	}
        }

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
                            $coverDetail['CoverImage'] = $Picture;
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
        $this->view->dataDefault = $dataDefault;
        $this->view->dataIntroImages = $dataIntroImages;
        // $this->view->dataFileImages = $dataFileImages;
        // $this->view->dataFileDocuments = $dataFileDocuments;
        // $this->view->dataFileMultiMedias = $dataFileMultiMedias;
        $this->view->dataTag = $dataTag;
        $this->view->dataCoverImageList = $dataCoverImageDetailList;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->galleryCategoryOption = $galleryCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->canCommentTypeOption = $canCommentTypeOption;
        $this->view->defaultCanComment = $defaultCanComment;

        // Set View
        $this->view->pageName = "รายละเอียดห้องภาพ";
        $this->view->pageIcon= "fa fa-file-image-o";
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'จัดการถังขยะ', 'url' => $this->view->url(array('module' => 'gallery', 'controller' => 'trash', 'action' => 'index'), null, true)),
            array('name' => 'รายละเอียดห้องภาพ')
        );
        
        // Log Acion
        System_Model_Log::action("gallery.trash", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
    }

    public function untrashAction()
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
		$canSave = $sUser->isAllow('gallery.trash.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Gallery_Model_Trash();
        $data = $model->untrash($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("gallery.trash", "update", null, $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
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
        $canSave = $sUser->isAllow('gallery.trash.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Gallery_Model_Trash();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("gallery.trash", "delete", null, $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }

}

