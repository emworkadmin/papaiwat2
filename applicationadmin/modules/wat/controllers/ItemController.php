<?php

class Wat_ItemController extends Zend_Controller_Action
{
	protected $extensionName;
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $everydayOption = array("-1" => "ทุกวัน");
    
    public function init()
    {
    	$this->extensionName = Wat_Service_Info::getExtensionName();
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
        $canView = $sUser->isAllow('wat.item.view');
        $canAdd = $sUser->isAllow('wat.item.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("WatItemSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("WatItemSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->province= $infoSearchNamespace->province;
        	$InfoSearch->sect= $infoSearchNamespace->sect;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	Zend_Session::namespaceUnset("WatItemSearch");
        }
        $this->view->InfoSearch = $InfoSearch;
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $categoryModel = new Wat_Model_Category();
        $categoryOption = $categoryModel->getOption($defaultLanguage["Code"]);
        $sectModel = new Wat_Model_Sect();
        $sectOption = $sectModel->getOption($defaultLanguage["Code"]);
        $provinceModel = new Wat_Model_Province();
        $provinceOption = $provinceModel->getOption($defaultLanguage["Code"]);
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->categoryOption = $categoryOption;
        $this->view->sectOption = $sectOption;
        $this->view->provinceOption = $provinceOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการวัด";
        $this->view->pageIcon = "fa fa-list";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
        		array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
        		array ( 'name' => $this->extensionName ), 
            	array('name' => 'รายการวัด')            
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
        
        $canView = $sUser->isAllow('wat.item.view');
        
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
        $advancesearch['isCat'] = $this->getRequest()->getParam('isCat');
        $advancesearch['province'] = $this->getRequest()->getParam('province');
        $advancesearch['sect'] = $this->getRequest()->getParam('sect');
        
        $infoSearchNamespace = new Zend_Session_Namespace("WatItemSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->province= $advancesearch['province'];
        $infoSearchNamespace->sect= $advancesearch['sect'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        if($canView):
        $model = new Wat_Model_Item();
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
                $d['SectTitle'] = Emwork_Utility::notShowNull($item["SectTitle"]);
                $d['ProvinceName'] = Emwork_Utility::notShowNull($item["ProvinceName"]);
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
        $canAdd = $sUser->isAllow('wat.item.manage');
        
        if (!$canAdd) {
            $this->_helper->redirector('index', 'article', 'content');
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $categoryModel = new Wat_Model_Category();
        $categoryOption = $categoryModel->getOption($defaultLanguage["Code"]);
        $sectModel = new Wat_Model_Sect();
        $sectOption = $sectModel->getOption($defaultLanguage["Code"]);
        $provinceModel = new Wat_Model_Province();
        $provinceOption = $provinceModel->getOption($defaultLanguage["Code"]);
        $watKindOption = Wat_Service_Info::getWatKindOption();
        $typeModel = new Wat_Model_Type();
        $watTypeOption = $typeModel->getOption($defaultLanguage["Code"]);
        $watClassOption = Wat_Service_Info::getWatClassOption();
        $dayOption = Wat_Service_Info::getDayOption();
        // เพิ่มทุกวัน
        $dayOption = $this->everydayOption + $dayOption;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
        $this->view->publishOption = $this->publishOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->categoryOption = $categoryOption;
        $this->view->sectOption = $sectOption;
        $this->view->provinceOption = $provinceOption;
        $this->view->watTypeOption = $watTypeOption;
        $this->view->watKindOption = $watKindOption;
        $this->view->watClassOption = $watClassOption;
        $this->view->dayOption = $dayOption;
        
        // Set View
        $this->view->pageName = "เพิ่มวัด";
        $this->view->pageIcon = "fa fa-file-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
        		array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
        		array ( 'name' => $this->extensionName ), 
	            array('name' => 'รายการวัด', 'url' => $this->view->url(array('module' => 'wat', 'controller' => 'item', 'action' => 'index'), null, true)),
	            array('name' => 'เพิ่มวัด')
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
        $canAdd = $sUser->isAllow('wat.item.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Wat_Model_Item();
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
        
        // CoverImage
        $coverImageID = null;
        if (count($params['hdfCoverImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfCoverImage'][0]);
        	if ($fileData->ID != null) {
        		$coverImageID = $fileData->ID;
        	}
        }
        $params['CoverImageID'] = $coverImageID;
        
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
        
        // Save
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("wat.item", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canDelete = $sUser->isAllow('wat.item.manage');
        $canEdit = $sUser->isAllow('wat.item.manage');
        $canView = $sUser->isAllow('wat.item.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'item', 'wat');
        }

        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'wat');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Get Option
        $modelBank = new Wat_Model_Bank();
        $bankOption = $modelBank->getOption($defaultLanguage['Code']);
        $categoryModel = new Wat_Model_Category();
        $categoryOption = $categoryModel->getOption($defaultLanguage["Code"]);
        $sectModel = new Wat_Model_Sect();
        $sectOption = $sectModel->getOption($defaultLanguage["Code"]);
        $provinceModel = new Wat_Model_Province();
        $provinceOption = $provinceModel->getOption($defaultLanguage["Code"]);
        $watKindOption = Wat_Service_Info::getWatKindOption();
        $typeModel = new Wat_Model_Type();
        $watTypeOption = $typeModel->getOption($defaultLanguage["Code"]);
        $watClassOption = Wat_Service_Info::getWatClassOption();
        $dayOption = Wat_Service_Info::getDayOption();
        // เพิ่มทุกวัน
        $dayOption = $this->everydayOption + $dayOption;
        
        $model = new Wat_Model_Item();
        
        // Get Data
        $data = $model->getInfo($itemID);        
        
        $dataDetails = array();
        $dataActivityDetailList = array();
        $dataRescueDetailList = array();
        if (count($languages) > 0) {
        	foreach ($languages as $lang) {
        		$langCode = $lang['Code'];
        		$detail = $model->getDataByLanguage($data->ID, $langCode);
        		if (!empty($detail->Lang)) {
        			$dataDetails[$langCode] = $detail;
        		}
        		
        		//Tag
        		$AllKeyword = $model->getAllKeywordByItem($data->ID, $langCode);
        		$dataTag = array();
        		if (count($AllKeyword) > 0) {
        			foreach ($AllKeyword as $dataAllTag) {
        				$dataTag[] = $dataAllTag['Title'];
        			}
        		}
        		$dataDetails[$langCode]->Tags = $dataTag;
        		
        		// Activity
        		$activitiesDetail = $model->getActivitiesByLanguage($data->ID, $langCode);
        		if (count($activitiesDetail) > 0) {
        			foreach ($activitiesDetail as $activityDetail) {
        				if (array_key_exists($activityDetail['DataCode'], $dataActivityDetailList)) {
        					$dataActivityDetailList[$activityDetail['DataCode']][] = $activityDetail;
        				} else {
        					$dataActivityDetailList[$activityDetail['DataCode']] = array();
        					$dataActivityDetailList[$activityDetail['DataCode']][] = $activityDetail;
        				}
        			}
        		}
        		
        		// Rescue
        		$rescuesDetail = $model->getRescuesByLanguage($data->ID, $langCode);
        		if (count($rescuesDetail) > 0) {
        			foreach ($rescuesDetail as $rescueDetail) {
        				if (array_key_exists($rescueDetail['DataCode'], $dataRescueDetailList)) {
        					$dataRescueDetailList[$rescueDetail['DataCode']][] = $rescueDetail;
        				} else {
        					$dataRescueDetailList[$rescueDetail['DataCode']] = array();
        					$dataRescueDetailList[$rescueDetail['DataCode']][] = $rescueDetail;
        				}
        			}
                }
                
                // Bank
        		$bankAccountsDetail = $model->getBankAccountByLanguage($data->ID, $langCode);
        		if (count($bankAccountsDetail) > 0) {
        			foreach ($bankAccountsDetail as $d) {
                        $dataBankDetailList[$d['DataCode']] = $d;
        			}
                }

                // PromptPay
        		$promptpayAccountsDetail = $model->getPromptPayAccountByLanguage($data->ID, $langCode);
        		if (count($promptpayAccountsDetail) > 0) {
        			foreach ($promptpayAccountsDetail as $d) {
                        $dataPromptPayDetailList[$d['DataCode']] = $d;
        			}
                }
                
        	}
        }

        $dataCategories = $model->getCategories($data->ID);
        $dataCategoryIds = array();
        if (count($dataCategories) > 0) {
        	foreach ($dataCategories as $dataCategory) {
        		$dataCategoryIds[] = $dataCategory['WatCategoryID'];
        	}
        }
        
        // Intro Image
        $dataIntroImages = array();
        if(!empty($data->IntroImageID)) {
        	$dataIntroImages[] = array('file_name'=> $data->IntroImageFileName, 'size'=> $data->IntroImageFileSize, 'type'=> $data->IntroImageFileType, 'hash_name'=> $data->IntroImageHashName);
        }
        // Cover Image
        $dataCoverImages = array();
        if(!empty($data->CoverImageID)) {
        	$dataCoverImages[] = array('file_name'=> $data->CoverImageFileName, 'size'=> $data->CoverImageFileSize, 'type'=> $data->CoverImageFileType, 'hash_name'=> $data->CoverImageHashName);
        }
        
        // WatType
        $dataWatTypeIds = array();
        $dataWatTypeList = $model->getWatTypes($data->ID);
        if (count($dataWatTypeList) > 0) {
        	foreach ($dataWatTypeList as $dataWatType) {
        		$dataWatTypeIds[] = $dataWatType["WatTypeID"];
        	}
        }
        
        // OpenHour
        $dataOpenHourList = $model->getOpenHours($data->ID, $defaultLanguage['Code']);
        
        $dataFiles = $model->getFiles($data->ID);
        $dataFileImages = array();
        $dataFileDocuments = array();
        $dataFileMultiMedias = array();
        if (count($dataFiles) > 0) {
        	foreach ($dataFiles as $dataFile) {
        		if ($dataFile["ItemFileType"] == "image") {
        			$dataFileImages[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
        		} elseif ($dataFile["ItemFileType"] == "document") {
        			$dataFileDocuments[] = array('file_name'=> $dataFile['FileName'], 'size'=> $dataFile['FileSize'], 'type'=> $dataFile['FileType'], 'hash_name'=> $dataFile['FileHashName']);
        		} elseif ($dataFile["ItemFileType"] == "multimedia") {
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
        $this->view->dataCoverImages = $dataCoverImages;
        $this->view->dataFileImages = $dataFileImages;
        $this->view->dataFileDocuments = $dataFileDocuments;
        $this->view->dataFileMultiMedias = $dataFileMultiMedias;
        $this->view->watTypeIds = $dataWatTypeIds;
        $this->view->dataActivityDetailList = $dataActivityDetailList;
        $this->view->dataRescueDetailList = $dataRescueDetailList;
        $this->view->dataBankDetailList = $dataBankDetailList;
        $this->view->dataPromptPayDetailList = $dataPromptPayDetailList;
        $this->view->dataOpenHourList = $dataOpenHourList;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->categoryOption = $categoryOption;
        $this->view->sectOption = $sectOption;
        $this->view->provinceOption = $provinceOption;
        $this->view->watTypeOption = $watTypeOption;
        $this->view->watKindOption = $watKindOption;
        $this->view->watClassOption = $watClassOption;
        $this->view->dayOption = $dayOption;
        $this->view->bankOption = $bankOption;
        
        // Set View
        $this->view->pageName = "ปรับปรุงวัด";
        $this->view->pageIcon= "fa fa-pencil-square-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
        		array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
        		array ( 'name' => $this->extensionName ), 
	            array('name' => 'รายการวัด', 'url' => $this->view->url(array('module' => 'wat', 'controller' => 'item', 'action' => 'index'), null, true)),
	            array('name' => 'ปรับปรุงวัด')
        );
        
        // Log Acion
        System_Model_Log::action("wat.item", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
        $canSave = $sUser->isAllow('wat.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Wat_Model_Item();
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
        
        // CoverImage
        $coverImageID = null;
        if (count($params['hdfCoverImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfCoverImage'][0]);
        	if ($fileData->ID != null) {
        		$coverImageID = $fileData->ID;
        	}
        }
        $params['CoverImageID'] = $coverImageID; 
        
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

        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("wat.item", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
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
        $canSave = $sUser->isAllow('wat.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Wat_Model_Item();
        
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
            System_Model_Log::action("wat.item", "delete", $title, $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }
    
    /********************** Keyword ************************/
    
    public function getkeywordautocompleteAction (){
        // Get Params
        $params = $this->_request->getParams();
        $query = $params['query'];
        $lang = $params['lang'];
        
        if (empty($lang)) {
        	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
        	$lang = $defaultLanguage["Code"];
        }
    
        // Get Data
        $model = new Wat_Model_Item();
        $dataList = $model->getAllKeyword($query, $lang);
         
        $data = new stdClass();
        $data->query = "Unit";
        //$data->query = $query;
         
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
         
        $data->suggestions = $suggestions;
         
        // Return value
        $this->_helper->json($data);
    }
    
    
    
    /********************** Action in List Page  ************************/

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
		$canSave = $sUser->isAllow('wat.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Wat_Model_Item();
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
		$canSave = $sUser->isAllow('wat.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Wat_Model_Item();
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
		$canSave = $sUser->isAllow('wat.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
        $model = new Wat_Model_Item();
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
    
    
    /* ****************** Dialog ***********************/

    public function activityformAction() {
    	
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
    	
    	// Get Option
    	$activityTypeModel = new Wat_Model_Activity();
    	$activityTypeOption = $activityTypeModel->getOption($defaultLanguage['Code']);
    	
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
    			$details[$langCode]->Detail = '';
    			$details[$langCode]->TypeID = '';
    			
    			if (!empty($data)) {
    				if ((count($dataObj['Lang']) > 0) && (array_key_exists($langCode, $dataObj['Lang']))) {
    					$details[$langCode]->Detail = $dataObj['Lang'][$langCode]["Detail"];
    					$details[$langCode]->TypeID = $dataObj["TypeID"];
    				}
    			}
    		}
    	}
    	
    	// Send Data to view
    	$this->view->ID = $ID;
    	$this->view->dataDetails = $details;
    	$this->view->defaultLanguage = $defaultLanguage['Code'];
    	$this->view->languages = $languages;
    	$this->view->activityTypeOption = $activityTypeOption;
    }

    public function rescueformAction() {
    	
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
    	
    	// Get Option
    	$rescueModel = new Wat_Model_Rescue();
    	$rescueOption = $rescueModel->getOption($defaultLanguage['Code']);
    	
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
    			$details[$langCode]->Detail = '';
    			$details[$langCode]->TypeID = '';
    			
    			if (!empty($data)) {
    				if ((count($dataObj['Lang']) > 0) && (array_key_exists($langCode, $dataObj['Lang']))) {
    					$details[$langCode]->Detail = $dataObj['Lang'][$langCode]["Detail"];
    					$details[$langCode]->TypeID = $dataObj["TypeID"];
    				}
    			}
    		}
    	}
    	
    	// Send Data to view
    	$this->view->ID = $ID;
    	$this->view->dataDetails = $details;
    	$this->view->defaultLanguage = $defaultLanguage['Code'];
    	$this->view->languages = $languages;
    	$this->view->rescueOption = $rescueOption;
    }

    public function bankformAction() {
    	
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
    	
    	// Get Option
        $modelBank = new Wat_Model_Bank();
        $bankOption = $modelBank->getOption($defaultLanguage['Code']);
        $modelBankAcc = new Wat_Model_Bankaccounttype();
    	$bankaccounttypeOption = $modelBankAcc->getOption($defaultLanguage['Code']);
    	
    	// Get Params
    	$data =  $this->getRequest()->getParam('data');
        $dataObj = json_decode($data, true);

        $fileModel = new System_Model_Fileinfo();
        $QRCodeImage = array();
        if (count($dataObj['QRCodeImageHash'])>0) {
        	$fileData = $fileModel->itemByHashName($dataObj['QRCodeImageHash']);
        	if ($fileData->ID != null) {
                $QRCodeImage[] = array('file_name'=> $fileData->Name, 'size'=> $fileData->FileSize, 'type'=> $fileData->FileType, 'hash_name'=> $fileData->HashName);
            }
        }
        
    	// Send Data to view
    	$this->view->data = $dataObj;
    	$this->view->QRCodeImage = $QRCodeImage;
    	$this->view->bankOption = $bankOption;
    	$this->view->bankaccounttypeOption = $bankaccounttypeOption;
    }

    public function promptpayformAction() {
    	
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
        $dataObj = json_decode($data, true);
        
    	// Send Data to view
    	$this->view->data = $dataObj;
    }
    
    
    /* ****************** Location ***********************/
    public function getdistrictAction(){
    	
    	$provinceCode = $this->getRequest()->getParam("id");
    	$lang = $this->getRequest()->getParam("lang");
    	
    	if (empty($lang)) {
    		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    		$lang = $defaultLanguage['Code'];
    	}
    	
    	$result = array();
    	if (empty($provinceCode)) {
    		$this->_helper->json($result);
    	}
    	
    	$model = new Wat_Model_District();
    	$data = $model->getOption($lang, $provinceCode);
    	
    	$i = 0;
    	foreach ($data as $key => $value) {
    		$result[$i]["id"] = $key;
    		$result[$i]["name"] = $value;
    		$i ++;
    	}
    	
    	$this->_helper->json($result);
    }
    
    public function getsubdistrictAction(){
    	
    	$districtCode = $this->getRequest()->getParam("id");
    	$lang = $this->getRequest()->getParam("lang");
    	
    	if (empty($lang)) {
    		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    		$lang = $defaultLanguage['Code'];
    	}
    	
    	$result = array();
    	if (empty($districtCode)) {
    		$this->_helper->json($result);
    	}
    	
    	$model = new Wat_Model_Subdistrict();
    	$data = $model->getOption($lang, $districtCode);
    	
    	$i = 0;
    	foreach ($data as $key => $value) {
    		$result[$i]["id"] = $key;
    		$result[$i]["name"] = $value;
    		$i ++;
    	}
    	
    	$this->_helper->json($result);
    }
}

