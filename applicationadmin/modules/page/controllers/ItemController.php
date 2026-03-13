<?php

class Page_ItemController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	
	protected $layoutBoxSizeOption = array ('1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '10' => '10', '11' => '11', '12' => '12' );
	protected $titleHeaderOption = array('' => '', 'h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'h5' => 'h5', 'h6' => 'h6');
	protected $fontWeightOption = array('' => '', 'normal' => 'normal', 'bold' => 'bold', 'bolder' => 'bolder', 'lighter' => 'lighter', 'initial' => 'initial', 'inherit' => 'inherit');
	protected $alignOption = array('' => '', 'center' => 'กึ่งกลาง', 'left' => 'ชิดซ้าย', 'right' => 'ชิดขวา');
	protected $bgRepeatOption = array('no-repeat' => 'No Repeat', 'repeat' => 'Repeat All', 'repeat-x' => 'Repeat Horizontally', 'repeat-y' => 'Repeat Vertically', 'inherit' => 'Inherit');
	protected $bgSizeOption = array('cover' => 'Cover', 'contain' => 'Contain', 'inherit' => 'Inherit');
	protected $bgAttachmentOption = array('fixed' => 'Fixed', 'scroll' => 'Scroll', 'inherit' => 'Inherit');
	protected $bgPositionOption = array(
			'0 0' => 'ซ้าย บน', '0 50%' => 'ซ้าย กึ่งกลาง', '0 100%' => 'ซ้าย ล่าง',
			'50% 0' => 'ตรงกลาง บน', '50% 50%' => 'ตรงกลาง กึ่งกลาง', '50% 100%' => 'ตรงกลาง ล่าง',
			'100% 0' => 'ขวา บน', '100% 50%' => 'ขวา กึ่งกลาง', '100% 100%' => 'ขวา ล่าง'
	);
	protected $animateOption = array(
			'Attention Seekers' => array(
					'bounce' => 'bounce',
					'flash' => 'flash',
					'pulse' => 'pulse',
					'rubberBand' => 'rubberBand',
					'shake' => 'shake',
					'swing' => 'swing',
					'tada' => 'tada',
					'wobble' => 'wobble',
					'jello' => 'jello',
			),
			'Fading Entrances' => array(
					'fadeIn' => 'fadeIn',
					'fadeInDown' => 'fadeInDown',
					'fadeInDownBig' => 'fadeInDownBig',
					'fadeInLeft' => 'fadeInLeft',
					'fadeInLeftBig' => 'fadeInLeftBig',
					'fadeInRight' => 'fadeInRight',
					'fadeInRightBig' => 'fadeInRightBig',
					'fadeInUp' => 'fadeInUp',
					'fadeInUpBig' => 'fadeInUpBig',
			),
			'Fading Exits' => array(
					'fadeOut' => 'fadeOut',
					'fadeOutDown' => 'fadeOutDown',
					'fadeOutDownBig' => 'fadeOutDownBig',
					'fadeOutLeft' => 'fadeOutLeft',
					'fadeOutLeftBig' => 'fadeOutLeftBig',
					'fadeOutRight' => 'fadeOutRight',
					'fadeOutRightBig' => 'fadeOutRightBig',
					'fadeOutUp' => 'fadeOutUp',
					'fadeOutUpBig' => 'fadeOutUpBig',
			),
			'Sliding Entrances' => array(
					'slideInUp' => 'slideInUp',
					'slideInDown' => 'slideInDown',
					'slideInLeft' => 'slideInLeft',
					'slideInRight' => 'slideInRight',
			), 
			'Sliding Exits' => array(
					'slideOutUp' => 'slideOutUp',
					'slideOutDown' => 'slideOutDown',
					'slideOutLeft' => 'slideOutLeft',
					'slideOutRight' => 'slideOutRight',
			)
	);
    
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
        $canView = $sUser->isAllow('page.item.view');
        $canAdd = $sUser->isAllow('page.item.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        
        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("PageItemSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("PageItemSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;

        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("PageItemSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
                
        // Send To View
        $this->view->publishOption = $this->publishOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการเพจ";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการเพจ')
            
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
        
        // Check Action Rights
        $canView = $sUser->isAllow('page.item.view');
        
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
        
        $infoSearchNamespace = new Zend_Session_Namespace("PageItemSearch");
        $infoSearchNamespace->textsearch = $advancesearch['searchText'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        if($canView):
            $model = new Page_Model_Item();
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
                $d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
                //$d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["Created"], 'DT'));
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
        $canAdd = $sUser->isAllow('page.item.manage');
        if (!$canAdd) {
            $this->_helper->redirector('index', 'item', 'page');
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
        $this->view->publishOption = $this->publishOption;
        
        // Set View
        $this->view->pageName = "เพิ่มเพจ";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการเพจ', 'url' => $this->view->url(array('module' => 'page', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'เพิ่มเพจ')
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
        $canAdd = $sUser->isAllow('page.item.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Page_Model_Item();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        // OpenGraphImage
        $openGraphImageID = null;
        if (count($params['hdfOpenGraphImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfOpenGraphImage'][0]);
        	if ($fileData->ID != null) {
        		$openGraphImageID = $fileData->ID;
            }
        }
        $params['OpenGraphImageID'] = $openGraphImageID;
        
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("page.item", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
            
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
        
        // Check Action Rights
        $canDelete = $sUser->isAllow('page.item.manage');
        $canEdit = $sUser->isAllow('page.item.manage');
        $canView = $sUser->isAllow('page.item.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'item', 'page');
        }
        
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'page');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        $model = new Page_Model_Item();
        
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
        
        // Open Graph Image
        $dataOpenGraphImages = array();
        if(!empty($data->OpenGraphImageID)) {
        	$dataOpenGraphImages[] = array('file_name'=> $data->OpenGraphImageFileName, 'size'=> $data->OpenGraphImageFileSize, 'type'=> $data->OpenGraphImageFileType, 'hash_name'=> $data->OpenGraphImageHashName);
        }
        
        // Send Rights to View
        $this->view->canDelete = $canDelete;
        $this->view->canEdit = $canEdit;
        
        // Mobile Detect
        $detect = new Mobile_Detect();
        $this->view->isiOS = $detect->isiOS();
        
        // Send To View
        $this->view->data = $data;
        $this->view->dataDetails = $dataDetails;
        $this->view->dataOpenGraphImages = $dataOpenGraphImages;
        
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->publishOption = $this->publishOption;
        
        // Set View
        $this->view->pageName = "ปรับปรุงเพจ";
        $this->view->pageIcon= "fa fa-newspaper-o";
        $this->view->subMenus = array(
        		array('name' => 'รายละเอียดเพจ', 'active' => true),
        		array('name' => 'ปรับโครงสร้าง', 'active' => false, 'url' => $this->view->url(array('module' => 'page', 'controller' => 'item', 'action' => 'layout', 'itemid' => $itemID), null, true))
        );
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการเพจ', 'url' => $this->view->url(array('module' => 'page', 'controller' => 'item', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงเพจ')
        );
        
        // Log Acion
        System_Model_Log::action("page.item", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
        $canSave = $sUser->isAllow('page.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Page_Model_Item();
        $fileModel = new System_Model_Fileinfo();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        // OpenGraphImage
        $openGraphImageID = null;
        if (count($params['hdfOpenGraphImage'])>0) {
        	$fileData = $fileModel->itemByHashName($params['hdfOpenGraphImage'][0]);
        	if ($fileData->ID != null) {
        		$openGraphImageID = $fileData->ID;
        	}
        }
        $params['OpenGraphImageID'] = $openGraphImageID;
        
        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
            
            // Log Acion
            System_Model_Log::action("page.item", "update", $params['params'][$defaultLanguageCode]['Title'], $data->id);
            
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
        $canSave = $sUser->isAllow('page.item.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Page_Model_Item();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $data = $model->getDataByLanguage($params["id"], $defaultLanguageCode['Code']);
            System_Model_Log::action("page.item", "delete", $data->Title, $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
    }
    
    
    
    /********************** Layout ************************/
    
    public function layoutAction()
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
    	$canView = $sUser->isAllow('page.item.view');
    	$canManage = $sUser->isAllow('page.item.manage');
    	if (!$canView) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	
    	$itemID = $this->getRequest()->getParam('itemid');
    	if (empty($itemID)) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	
    	$languages = System_Model_Setting::getLanguages();
    	$defaultLanguage = System_Model_Setting::getDefaultLanguage();
    	
    	$model = new Page_Model_Item();
    	$data = $model->getInfo($itemID);
    	
    	// Widget Option
    	$widgetModel = new System_Model_Widget();
    	$widgetList = $widgetModel->getList("Title", "asc", 999, 1, null, null, null);
    	$widgetOption = array();
    	if (count($widgetList) > 0) {
    		foreach ($widgetList as $widgetItem) {
    			$widgetOption[$widgetItem["ID"]] = $widgetItem["Title"] . " : " . $widgetItem["Widget"];
    		}
    	}
    	
    	// Option
    	$this->view->animateOption = $this->animateOption;
    	$this->view->widgetTypeOption = $widgetOption;
    	$this->view->layoutBoxSizeOption = $this->layoutBoxSizeOption;
    	$this->view->fontWeightOption = $this->fontWeightOption;
    	$this->view->titleHeaderOption = $this->titleHeaderOption;
    	$this->view->alignOption = $this->alignOption;
    	$this->view->bgRepeatOption = $this->bgRepeatOption;
    	$this->view->bgSizeOption = $this->bgSizeOption;
    	$this->view->bgAttachmentOption = $this->bgAttachmentOption;
    	$this->view->bgPositionOption = $this->bgPositionOption;
    	
    	// Send To View
    	$this->view->itemID = $itemID;
    	$this->view->data = $data;
    	
    	// Set View
    	$this->view->canManage = $canManage;
    	$this->view->pageName = "ปรับโครงสร้าง";
    	$this->view->pageIcon = "fa fa-newspaper-o";
    	$this->view->subMenus = array(
    			array('name' => 'รายละเอียดเพจ', 'active' => false, 'url' => $this->view->url(array('module' => 'page', 'controller' => 'item', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'ปรับโครงสร้าง', 'active' => true)
    	);
    	$this->view->breadcrumb = array(
    			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
    			array('name' => 'รายการเพจ', 'url' => $this->view->url(array('module' => 'page', 'controller' => 'item', 'action' => 'index'), null, true)),
    			array('name' => 'ปรับปรุงเพจ', 'url' => $this->view->url(array('module' => 'page', 'controller' => 'item', 'action' => 'edit', 'itemid' => $itemID), null, true)),
    			array('name' => 'ปรับโครงสร้าง')
    			
    	);
    }
    
    public function updatelayoutAction()
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
    	$canManage = $sUser->isAllow('page.item.manage');
    	if (!$canManage) {
    		$result['message'] = 'ไม่สามารถบันทึกได้';
    		$this->_helper->json($result);
    	}
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Update
    	$model = new Page_Model_Item();
    	$data = $model->updateLayout($params);
    	
    	// Set Result
    	if ($data->result) {
    		$result['result'] = true;
    		$result['message'] = '';
			
			// Log Acion
            $defaultLanguage = System_Model_Setting::getDefaultLanguage();
            $data = $model->getDataByLanguage($params["hdfID"], $defaultLanguage['Code']);
            System_Model_Log::action("page.item", "update", $data->Title, $params["hdfID"]);
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
		$canSave = $sUser->isAllow('page.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Page_Model_Item();
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
		$canSave = $sUser->isAllow('page.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Page_Model_Item();
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
		$canSave = $sUser->isAllow('page.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Page_Model_Item();
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

