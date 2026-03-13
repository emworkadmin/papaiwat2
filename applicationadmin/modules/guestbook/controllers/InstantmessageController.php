<?php

class Guestbook_InstantmessageController extends Zend_Controller_Action

{
	protected $lang = 'th';
	protected $StatusOption = array('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
	
	public function init() {
	    
	}
	
	public function indexAction() {
	    // Log Access
	    System_Model_Log::access();
	    
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canView = $sUser->isAllow('guestbook.instantmessage.view');
		$canAdd = $sUser->isAllow('guestbook.instantmessage.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
	    
	    $this->view->statusOption = $this->StatusOption;
	    
		//Data to Layout
		$this->view->canAdd=$canAdd;
	    $this->view->pageName = "ข้อความสำเร็จรูป";
	    $this->view->pageIcon= "fa fa-pencil-square-o";
        $this->view->subMenus = array();
        
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'ข้อความสำเร็จรูป')
        );

	}
	
	public function getlistAction() {

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->json($responce);
		}
		$canView = $sUser->isAllow('guestbook.instantmessage.view');
		
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
	    $textstr = $this->getRequest()->getParam('textsearch');
	    $textstr = $textstr;
	    $filter = '';
	    
	    //Advance Search
	    $advancesearch['textsearch'] = $textstr;
	    $advancesearch['status'] = $this->getRequest()->getParam('status');

		if($canView):
			$model = new Guestbook_Model_Instantmessage();
			$data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
		endif;

	    $i=$_start;
	    $responce = array();
	    $responce['draw'] = $_draw;
	    $responce['recordsTotal'] = 0;
	    $responce['recordsFiltered'] = 0;
	    $responce['data'] = array();
	    
	    if ($canView && $data) {
	        $responce['recordsTotal'] = $data[0]["RECORD"];
			$responce['recordsFiltered'] = $data[0]["RECORD"];
	        
	        foreach($data as $item) {
	            $i++;
	            $d = array();
	            $d['DT_RowId'] = $item["ID"];
	            $d['ID'] = empty($item["ID"]) ? "" : $item["ID"];
	            $d['NO'] = $i;
				$d['Title'] = Emwork_Utility::notShowNull($item["Title"]);
				$d['IsEnableCode'] = $item["IsEnable"];
				$d['IsEnable'] = Emwork_Utility::notShowNull($this->StatusOption[$item["IsEnable"]]);
	            $responce['data'][] = $d;
	        }
	    }

	    $this->_helper->json($responce);
	}

	public function addAction() {
		 // Log Access
		 System_Model_Log::access();
        
		 $sUser = System_Model_SUser::getInstance();
		 if (!$sUser->hasData()) {
			 $this->_helper->redirector('index', 'index', 'default');
		 }
		 if ($sUser->getInfo()->UserType != "B") {
			 $this->_helper->redirector('index', 'index', 'default');
		 }
		 $canAdd = $sUser->isAllow('guestbook.instantmessage.manage');
		 if (!$canAdd) {
			 $this->_helper->redirector('index', 'instantmessage', 'guestbook');
		 }
		 
		 $languages = System_Model_Setting::getLanguages();
		 $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		 
		 // Get Option
		 $ebookCategoryModel = new Guestbook_Model_Instantmessage();
		 $ebookCategoryOption = $ebookCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
		 
		 // Send Option and lookup To View
		 $this->view->defaultLanguage = $defaultLanguage['Code'];
		 $this->view->defaultLanguageName = $defaultLanguage['Name'];
		 $this->view->languages = $languages;
		 $this->view->statusOption = $this->StatusOption;
		 $this->view->ebookCategoryOption = $ebookCategoryOption;

		 // Set View
		 $this->view->pageName = "เพิ่มข้อความสำเร็จรูป";
		 $this->view->pageIcon= "fa fa-pencil-square-o";
		 $this->view->subMenus = array();
		 $this->view->breadcrumb = array(
			 array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			 array('name' => 'ข้อความสำเร็จรูป', 'url' => $this->view->url(array('module' => 'guestbook', 'controller' => 'instantmessage', 'action' => 'index'), null, true)),
			 array('name' => 'เพิ่มข้อความสำเร็จรูป')
		 );
		
	}

	public function saveAction() {

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
		  $canAdd = $sUser->isAllow('guestbook.instantmessage.manage');
		  if (!$canAdd) {
			  $result['message'] = 'ไม่สามารถบันทึกได้';
			  $this->_helper->json($result);
		  }

		  $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		  $defaultLanguageCode = $defaultLanguage["Code"];
		  
		  $model = new Guestbook_Model_Instantmessage();
		  $params = $this->getRequest()->getParams();
		  $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		  
		  $data = $model->save($params);

		  // Set Result
		  if ($data->result) {
			  $result['result'] = true;
			  $result['message'] = '';
			  $result['id'] = $data->id;
			  
			//   Log Acion
			  System_Model_Log::action("guestbook.Instantmessage", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
			
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
		$canDelete = $sUser->isAllow('guestbook.instantmessage.manage');
		$canEdit = $sUser->isAllow('guestbook.instantmessage.manage');
		$canView = $sUser->isAllow('guestbook.instantmessage.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'instantmessage', 'guestbook');
		}

        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'instantmessage', 'guestbook');
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
		// Get Option
		$bookCategoryModel = new Guestbook_Model_Instantmessage();
		$bookCategoryOption = $bookCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
        
        $model = new Guestbook_Model_Instantmessage();
        
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
        		$dataCategoryIds[] = $dataCategory['BookID'];
        	}
		}
		
		// echo"<pre>";print_r($dataCategoryIds);exit;
        
		// Send To View
        $this->view->data = $data;
        $this->view->dataCategoryIds = $dataCategoryIds;
		$this->view->dataDetails = $dataDetails;
		$this->view->canEdit=$canEdit;
		$this->view->canDelete=$canDelete;

		
		$this->view->bookCategoryOption = $bookCategoryOption;
        $this->view->languages = $languages;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->statusOption = $this->StatusOption;
        
		$this->view->pageName = "ปรับปรุงข้อความสำเร็จรูป";
        $this->view->pageIcon = "fa fa-pencil-square-o";
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'ข้อความสำเร็จรูป', 'url' => $this->view->url(array('module' => 'guestbook', 'controller' => 'instantmessage', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงข้อความสำเร็จรูป')
        );
        
        // Log Acion
        System_Model_Log::action("guestbook.Instantmessage", "view", $dataDetails[$defaultLanguage["Code"]]->Title, $itemID);
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
		$canSave = $sUser->isAllow('guestbook.instantmessage.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Guestbook_Model_Instantmessage();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
        
        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
			$result['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("guestbook.Instantmessage", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);

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
		$canSave = $sUser->isAllow('guestbook.instantmessage.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}

        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Guestbook_Model_Instantmessage();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
			$result['message'] = '';
			
			 // Log Acion
			$modeleInstantmessage = new Guestbook_Model_Instantmessage();
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$datainfo = $modeleInstantmessage->getDataByLanguage($params['itemid'], $defaultLanguage['Code']);
			System_Model_Log::action("guestbook.Instantmessage", "delete", $datainfo->Title, $params['itemid']);

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
		$canSave = $sUser->isAllow('guestbook.instantmessage.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Guestbook_Model_Instantmessage();
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
		$canSave = $sUser->isAllow('guestbook.instantmessage.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Guestbook_Model_Instantmessage();
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
		$canSave = $sUser->isAllow('guestbook.instantmessage.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Guestbook_Model_Instantmessage();
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

