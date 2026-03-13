<?php

class Intropage_InfoController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'แสดง', 'N' => 'ไม่แสดง');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
    
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
        $canView = $sUser->isAllow('intropage.item.view');
        $canAdd = $sUser->isAllow('intropage.item.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("IntropageInfoSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("IntropageInfoSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->category= $infoSearchNamespace->category;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("IntropageInfoSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        // $this->view->intropageCategoryOption = $intropageCategoryOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการหน้าเริ่มต้น";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการหน้าเริ่มต้น')
            
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
        $canView = $sUser->isAllow('intropage.item.view');

        
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
        
        $infoSearchNamespace = new Zend_Session_Namespace("IntropageInfoSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->category= $advancesearch['isCat'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $model = new Intropage_Model_Info();
        $data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
        
        $i=$_start;
        if ($canView && $data) {
            $responce['recordsTotal'] = $data[0]["RECORD"];
            $responce['recordsFiltered'] = $data[0]["RECORD"];
            
            foreach($data as $item){
                $i++;
                $d = array();
                $d['NO']=$i;
                $d['DT_RowId'] = $item["ID"];
                $d['ID'] = $item["ID"];
                $d['IsEnableCode'] = $item["IsEnable"];
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->publishOption[$item["IsEnable"]]);
                $d['Name'] = $item["Name"];
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
        $canAdd = $sUser->isAllow('intropage.item.manage');
        
        if (!$canAdd) {
           $this->_helper->redirector('index', 'info', 'intropage');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
        $this->view->statusOption=$this->statusOption;
        $this->view->publishOption = $this->publishOption;
        
        // Set View
        $this->view->pageName = "เพิ่มหน้าเริ่มต้น";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการหน้าเริ่มต้น', 'url' => $this->view->url(array('module' => 'intropage', 'controller' => 'info', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มหน้าเริ่มต้น')
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
        $canAdd = $sUser->isAllow('intropage.item.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        

        $fileModel = new System_Model_Fileinfo();
        $model = new Intropage_Model_Info();
        $params = $this->getRequest()->getParams();//อ่านค่าที่รับมาจากแต่ละตัวแปรในหน้า View
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];

        if($params['params'][$defaultLanguageCode]['UseButton']=='Y'){
            $data=$model->getAllInfo();
            foreach($data as $d){
                if($d['IsEnable']='Y'){
                    $data = $model->updateNOEna($params);
                }
            }
        }
        elseif($params['params'][$defaultLanguageCode]['UseButton']=='N'){
        }

       
        // Gallery
        $galleryIds = array();
        if (count($params['hdfbtImages0'])) {
            foreach ($params['hdfbtImages0'] as $hashName){
                unset($fileData);
                $fileData = $fileModel->itemByHashName($hashName);
                if ($fileData->ID != null) {
                    $galleryIds[]= $fileData->ID;
                }
            }
        }
        $params['GalleryIds'][] = $galleryIds;
        
        // Gallery
        $galleryIds = array();
        if (count($params['hdfbtImages1'])) {
            foreach ($params['hdfbtImages1'] as $hashName){
                unset($fileData);
                $fileData = $fileModel->itemByHashName($hashName);
                if ($fileData->ID != null) {
                    $galleryIds[]= $fileData->ID;
                }
            }
        }
        $params['GalleryIds'][] = $galleryIds;

        // Gallery
        $galleryIds = array();
        if (count($params['hdfbtImages2'])) {
            foreach ($params['hdfbtImages2'] as $hashName){
                unset($fileData);
                $fileData = $fileModel->itemByHashName($hashName);
                if ($fileData->ID != null) {
                    $galleryIds[]= $fileData->ID;
                }
            }
        }
        $params['GalleryIds'][] = $galleryIds;

                        // Gallery
        $galleryIds = array();
        if (count($params['hdfbtImages3'])) {
            foreach ($params['hdfbtImages3'] as $hashName){
                unset($fileData);
                $fileData = $fileModel->itemByHashName($hashName);
                if ($fileData->ID != null) {
                    $galleryIds[]= $fileData->ID;
                }
            }
        }
        $params['GalleryIds'][] = $galleryIds;

        $button = array();
        if(count($params['params'])>0){
            foreach($params['params'] as $lang => $value) {
                $button[$lang] = $value['button'];
            }
        }
        if(count($button)>0) {
            foreach($button as $lang => $array){
                foreach($array as $key => $value) {
                    $button[$lang][$key]['btText'] = $button[$lang][$key]['btText'];
                    $button[$lang][$key]['ShowButton'] = $button[$defaultLanguageCode][$key]['ShowButton'];
                    $button[$lang][$key]['btColor'] = $button[$defaultLanguageCode][$key]['btColor'];
                    $button[$lang][$key]['bttxtColor'] = $button[$defaultLanguageCode][$key]['bttxtColor'];
                    $button[$lang][$key]['URL'] = $button[$defaultLanguageCode][$key]['URL'];
                    $button[$lang][$key]['ImageID'] = $params['GalleryIds'][$key][0];

                    $dataImage = empty($params['GalleryIds'][$key][0]) ? null : $model->getFile($params['GalleryIds'][$key][0]);
                    if(count($dataImage)>0) {
                        $Image = array();
                        $Image[] = array(
                            'file_name'=> $dataImage[0]['Name'],
                            'size'=> $dataImage[0]['FileSize'],
                            'type'=> $dataImage[0]['FileType'],
                            'hash_name'=> $dataImage[0]['HashName']
                        );
                        
                    } else {
                        $Image = null;
                    }
                    $button[$lang][$key]['Image'] = $Image;

                }
            }
        }

        $params['button'] = $button;

        
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("intropage.info", "save", $params['params'][$defaultLanguageCode]['Name'], $data->id);
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
        $canDelete = $sUser->isAllow('intropage.item.manage');
        $canEdit = $sUser->isAllow('intropage.item.manage');
        $canView = $sUser->isAllow('intropage.item.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'info', 'intropage');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'info', 'intropage');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        $model = new Intropage_Model_Info();
        // Get Data
        $data = $model->getInfo($itemID); 
             
        $dataDetails = array();
        if (count($languages) > 0) {
        	foreach ($languages as $lang) {
        		$langCode = $lang['Code'];
        		$detail = $model->getDataByLanguage($data->ID, $langCode);
        		if (!empty($detail->Lang)) {
                    $dataDetails[$langCode] = $detail;
                    $button = json_decode($detail->Button, true);
                    $dataDetails[$langCode]->Button = $button;
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
        $this->view->dataCategoryIds = "dataIntropage";
        $this->view->dataDetails = $dataDetails;
        $this->view->dataBTImages1 = $dataBTImages1;
        $this->view->dataBTImages2 = $dataBTImages2;
        $this->view->dataBTImages3 = $dataBTImages3;
        $this->view->dataBTImages4 = $dataBTImages4;
        $this->view->dataFileImages = $dataFileImages;
        $this->view->dataFileDocuments = $dataFileDocuments;
        $this->view->dataFileMultiMedias = $dataFileMultiMedias;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->statusOption;
        $this->view->publishOption = $this->publishOption;
        $this->view->intropageCategoryOption = $intropageCategoryOption;
        $this->view->yesNoOption = $this->yesNoOption;
        $this->view->canCommentTypeOption = $canCommentTypeOption;
        $this->view->defaultCanComment = $defaultCanComment;
        
        // Set View
        $this->view->pageName = "ปรับปรุงหน้าเริ่มต้น";
        $this->view->pageIcon= "fa fa-newspaper-o";
        $this->view->subMenus = array(
        		// array('name' => 'รายละเอียดหน้าเริ่มต้น', 'active' => true),
        		// array('name' => 'ความคิดเห็น', 'active' => false, 'url' => $this->view->url(array('module' => 'intropage', 'controller' => 'info', 'action' => 'comment', 'itemid' => $itemID), null, true)),
        //		array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'intropage', 'controller' => 'info', 'action' => 'rights', 'itemid' => $itemID), null, true))
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการหน้าเริ่มต้น', 'url' => $this->view->url(array('module' => 'intropage', 'controller' => 'info', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงหน้าเริ่มต้น')
        );
        
        // Log Acion
        System_Model_Log::action("intropage.info", "view", $dataDetails[$defaultLanguage["Code"]]->Name, $itemID);
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
        $canSave = $sUser->isAllow('intropage.item.manage');
        if (!$canSave) {
           $result['message'] = 'ไม่สามารถบันทึกได้';
           $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Intropage_Model_Info();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();//อ่านค่าที่รับมาจากแต่ละตัวแปรในหน้า View
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];

        if($params['params'][$defaultLanguageCode]['IsEnable']=='Y'){
            $data=$model->getAllInfo();
            foreach($data as $d){
                if($d['IsEnable']='Y'){
                    $data = $model->updateNOEna($params);
                }
            }
        }
        elseif($params['params'][$defaultLanguageCode]['IsEnable']=='N'){
        }

        // Gallery
        $galleryIds = array();
        if (count($params['hdfbtImages0'])) {
            foreach ($params['hdfbtImages0'] as $hashName){
                unset($fileData);
                $fileData = $fileModel->itemByHashName($hashName);
                if ($fileData->ID != null) {
                    $galleryIds[]= $fileData->ID;
                }
            }
        }
        $params['GalleryIds'][] = $galleryIds;
        
        // Gallery
        $galleryIds = array();
        if (count($params['hdfbtImages1'])) {
            foreach ($params['hdfbtImages1'] as $hashName){
                unset($fileData);
                $fileData = $fileModel->itemByHashName($hashName);
                if ($fileData->ID != null) {
                    $galleryIds[]= $fileData->ID;
                }
            }
        }
        $params['GalleryIds'][] = $galleryIds;

        // Gallery
        $galleryIds = array();
        if (count($params['hdfbtImages2'])) {
            foreach ($params['hdfbtImages2'] as $hashName){
                unset($fileData);
                $fileData = $fileModel->itemByHashName($hashName);
                if ($fileData->ID != null) {
                    $galleryIds[]= $fileData->ID;
                }
            }
        }
        $params['GalleryIds'][] = $galleryIds;

                        // Gallery
        $galleryIds = array();
        if (count($params['hdfbtImages3'])) {
            foreach ($params['hdfbtImages3'] as $hashName){
                unset($fileData);
                $fileData = $fileModel->itemByHashName($hashName);
                if ($fileData->ID != null) {
                    $galleryIds[]= $fileData->ID;
                }
            }
        }
        $params['GalleryIds'][] = $galleryIds;

        $button = array();
        if(count($params['params'])>0){
            foreach($params['params'] as $lang => $value) {
                $button[$lang] = $value['button'];
            }
        }
        if(count($button)>0) {
            foreach($button as $lang => $array){
                foreach($array as $key => $value) {
                    $button[$lang][$key]['btText'] = $button[$lang][$key]['btText'];
                    $button[$lang][$key]['ShowButton'] = $button[$defaultLanguageCode][$key]['ShowButton'];
                    $button[$lang][$key]['btColor'] = $button[$defaultLanguageCode][$key]['btColor'];
                    $button[$lang][$key]['bttxtColor'] = $button[$defaultLanguageCode][$key]['bttxtColor'];
                    $button[$lang][$key]['URL'] = $button[$defaultLanguageCode][$key]['URL'];
                    $button[$lang][$key]['ImageID'] = $params['GalleryIds'][$key][0];

                    $dataImage = empty($params['GalleryIds'][$key][0]) ? null : $model->getFile($params['GalleryIds'][$key][0]);
                    if(count($dataImage)>0) {
                        $Image = array();
                        $Image[] = array(
                            'file_name'=> $dataImage[0]['Name'],
                            'size'=> $dataImage[0]['FileSize'],
                            'type'=> $dataImage[0]['FileType'],
                            'hash_name'=> $dataImage[0]['HashName']
                        );
                    } else {
                        $Image = null;
                    }
                    $button[$lang][$key]['Image'] = $Image;

                }
            }
        }

        $params['button'] = $button;

        $data = $model->update ( $params );

        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("intropage.info", "update", $params['params'][$defaultLanguageCode]['Name'], $data->id);
            
//             $modelIntropage = new Intropage_Model_Info();
//             $dataInfo = $modelIntropage->getFullData($data->ID);
//             $modelIntropage->saveHistory($data->ID, 'save', $sUser->getInfo()->ID, $sUser->getInfo()->DepartmentID, $dataInfo);
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
        $canSave = $sUser->isAllow('intropage.item.manage');
        if (!$canSave) {
           $result['message'] = 'ไม่สามารถลบได้';
           $this->_helper->json($result);
        }
        // Param
        $params = $this->getRequest()->getParams();

        // Update
        $model = new Intropage_Model_Info();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("intropage.info", "delete",  $params["Name"], $params["id"]);
            
//             $modelIntropage = new Intropage_Model_Info();
//             $dataInfo = $modelIntropage->getFullData($params["id"]);
//             $modelIntropage->saveHistory($params["id"], 'delete', $sUser->getInfo()->ID, $sUser->getInfo()->DepartmentID, $dataInfo);
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
    	$canView = true; // $sUser->isAllow('intropage.page.view');
    	$canManage = true; // $sUser->isAllow('intropage.page.manage');
    	if (!$canView) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	
    	$itemID = $this->getRequest()->getParam('itemid');
    	if (empty($itemID)) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	
//     	$InfoSearch = new stdClass();
//     	if (Zend_Session::namespaceIsset("IntropageInfoSearch")) {
//     		$infoSearchNamespace = new Zend_Session_Namespace("IntropageInfoSearch");
//     		$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
//     		$InfoSearch->category= $infoSearchNamespace->category;
//     		$InfoSearch->isenable= $infoSearchNamespace->isenable;
//     		$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
//     		$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
//     		$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
//     		$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;    		
//     		$this->view->InfoSearch = $InfoSearch;
//     		Zend_Session::namespaceUnset("IntropageInfoSearch");
//     	}
    	
    	$languages = System_Model_Setting::getLanguages();
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    	
    	// Get Option
    	$modelOption = new Intropage_Model_Info();
    	$intropageCategoryOption = $modelOption->getOption($defaultLanguage["Code"]);
    	
    	// Send To View
    	$this->view->itemID = $itemID;
    	
    	// Set View
    	$this->view->canManage = $canManage;
    	$this->view->pageName = "ความคิดเห็น";
    	$this->view->pageIcon = "fa fa-newspaper-o";
    	$this->view->subMenus = array(
    			array('name' => 'รายละเอียดหน้าเริ่มต้น', 'active' => false, 'url' => $this->view->url(array('module' => 'intropage', 'controller' => 'info', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			// array('name' => 'ความคิดเห็น', 'active' => true),
    	//		array('name' => 'กำหนดสิทธิ์', 'active' => false, 'url' => $this->view->url(array('module' => 'intropage', 'controller' => 'info', 'action' => 'rights', 'itemid' => $itemID), null, true))
    	);
    	$this->view->breadcrumb = array(
    			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
    			array('name' => 'รายการหน้าเริ่มต้น', 'url' => $this->view->url(array('module' => 'intropage', 'controller' => 'info', 'action' => 'index'), null, true)),
    			array('name' => 'ปรับปรุงหน้าเริ่มต้น', 'url' => $this->view->url(array('module' => 'intropage', 'controller' => 'info', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			// array('name' => 'ความคิดเห็น')
    			
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
    	
    	// Check Action Rights
    	$canView = true; // $canView && $sUser->isAllow('intropage.info.view');
    	
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
    	$advancesearch['infoID'] = $itemID;
    	
//     	$infoSearchNamespace = new Zend_Session_Namespace("IntropageInfoSearch");
//     	$infoSearchNamespace->textsearch = $advancesearch['textsearch'];
//     	$infoSearchNamespace->category= $advancesearch['isCat'];
//     	$infoSearchNamespace->isenable= $advancesearch['isEnable'];
//     	$infoSearchNamespace->tblPage = $_start;
//     	$infoSearchNamespace->tblRow = $_length;
//     	$infoSearchNamespace->tblOrderCol = $_order[0]['column'];
//     	$infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
    	
    	$model = new Intropage_Model_Comment();
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
    		$result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
    		$this->_helper->json($result);
    	}
    	if ($sUser->getInfo()->UserType != "B") {
    		$result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
    		$this->_helper->json($result);
    	}
    	$canManage = true; // $sUser->isAllow('intropage.page.manage');
    	if (!$canManage) {
    		$result['message'] = 'ไม่สามารถปรับปรุงสถานะได้';
    		$this->_helper->json($result);
    	}
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Update
    	$model = new Intropage_Model_Comment();
    	$data = $model->updateStatus($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
			
			 // Log Acion
            System_Model_Log::action("intropage.info", "update", null, $params["id"]);
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
    	$canManage = true; // $sUser->isAllow('intropage.page.manage');
    	if (!$canManage) {
    		$result['message'] = 'ไม่สามารถลบได้';
    		$this->_helper->json($result);
    	}
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Update
    	$model = new Intropage_Model_Comment();
    	$data = $model->delete($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
			// Log Acion
            System_Model_Log::action("intropage.info", "delete", null, $params["id"]);
    	} else {
    		$result['result'] = false;
    		$result['message'] = 'ลบไม่สำเร็จ';
    	}
    	
    	$this->_helper->json($result);
    }
    
    
    public function previewAction() {
    	$this->_helper->layout()->disableLayout();
    	
    	// Check Action Rights
    	$canView = true;
    	
    	// Log Access
    	System_Model_Log::access();
    	
    	$sUser = System_Model_SUser::getInstance();
    	if (!$sUser->hasData()) {
    		// Access Deny
    		$canView = false;
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
    	
    	// Check Action Rights
    	$canView = true;
    	
        $sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->json($responce);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->json($responce);
        }
        $canView = $sUser->isAllow('intropage.item.view');
    	
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
    			$data->Name = $param["Name"];
    			$data->ViewAmount = 0;
    			$data->Created = new DateTime();
    			$data->SocialNetworkFacebook = $param['SocialNetworkFacebook'];
    			$data->SocialNetworkTwitter = $param['SocialNetworkTwitter'];
    			$data->SocialNetworkGooglePlus = $param['SocialNetworkGooglePlus'];
    			$data->ShowQrcode = $param['ShowQrcode'];
    			$data->ShowPrint = $param['ShowPrint'];
    			$data->Detail = $param['Detail'];
     			
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
		$canSave = $sUser->isAllow('intropage.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Intropage_Model_Info();
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
		$canSave = $sUser->isAllow('intropage.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Intropage_Model_Info();
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
		$canSave = $sUser->isAllow('intropage.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Intropage_Model_Info();
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

