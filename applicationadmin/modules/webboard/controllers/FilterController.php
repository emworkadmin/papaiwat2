<?php

class Webboard_FilterController extends Zend_Controller_Action

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
		
		// Check Action Rights
		$canView = $sUser->isAllow('webboard.filter.view');
		$canAdd = $sUser->isAllow('webboard.filter.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
	    
	    $this->view->statusOption = $this->StatusOption;
		$this->view->canAdd = $canAdd;
	    //Data to Layout
	    $this->view->pageName = "รายการคัดกรองคำ";
	    $this->view->pageIcon= "fa fa-list";
        $this->view->subMenus = array();
        
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการคัดกรองคำ')
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
		
		$canView = $sUser->isAllow('webboard.filter.view');

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
			$model = new Webboard_Model_Filter();
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
				$d['Detail'] = Emwork_Utility::notShowNull($item["Detail"]);
				$d['Replace'] = Emwork_Utility::notShowNull($item["Replace"]);
				// $d['Amount'] = Emwork_Utility::notShowNull($item["Amount"],0);
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
		$canAdd = $sUser->isAllow('webboard.filter.manage');
		
		if (!$canAdd) {
		   $this->_helper->redirector('index', 'filter', 'webboard');
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
		$this->view->pageName = "เพิ่มข้อมูลการคัดกรองคำ";
		$this->view->pageIcon= "fa fa-pencil-square-o";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'รายการคัดกรองคำ', 'url' => $this->view->url(array('module' => 'webboard', 'controller' => 'filter', 'action' => 'index'), null, true)),
			array('name' => 'เพิ่มข้อมูลการคัดกรองคำ')
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
		 $canAdd = $sUser->isAllow('webboard.filter.manage');
		 if (!$canAdd) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		 }
		 
		 $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		 $defaultLanguageCode = $defaultLanguage["Code"];
		 
		 $model = new Webboard_Model_Filter();
		 $params = $this->getRequest()->getParams();
		 $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		 
		 $data = $model->save($params);

		 // Set Result
		 if ($data->result) {
			 $result['result'] = true;
			 $result['message'] = '';
			 $result['id'] = $data->id;
			 
			 // Log Acion
		     System_Model_Log::action("webboard.filter", "save", $params['Detail'], $data->id);
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
	   $canDelete = $sUser->isAllow('webboard.filter.manage');
	   $canEdit = $sUser->isAllow('webboard.filter.manage');
	   $canView = $sUser->isAllow('webboard.filter.view');
	   if (!$canView) {
		  $this->_helper->redirector('index', 'filter', 'webboard');
	   }
	
	   // Get Param
	   $itemID = $this->_request->getParam('itemid');

	   // Model
	   if (!$itemID) {
		   $this->_helper->redirector('index', 'Category', 'Webboard');
	   }
	   
	   $languages = System_Model_Setting::getLanguages();
	   $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	   
	   $this->view->canDelete = $canDelete;
	   $this->view->canEdit = $canEdit;

	   // Get Data
	   $model = new Webboard_Model_Filter();
	   $data = $model->getData($itemID);

	   // Send To View
	   $this->view->data = $data;
	   $this->view->statusOption = $this->StatusOption;
	   
	   $this->view->pageName = "รายละเอียดการคัดกรองคำ";
	   $this->view->pageIcon = "fa fa-list";
	   $this->view->breadcrumb = array(
		   array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
		   array('name' => 'รายการคัดกรองคำ', 'url' => $this->view->url(array('module' => 'webboard', 'controller' => 'filter', 'action' => 'index'), null, true)),
		   array('name' => 'รายละเอียดการคัดกรองคำ')
	   );
	   
	   // Log Acion
		System_Model_Log::action("webboard.filter", "view", $data->Detail, $data->ID);
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
	   $canSave = $sUser->isAllow('webboard.filter.manage');
	   if (!$canSave) {
		  $result['message'] = 'ไม่สามารถบันทึกได้';
		  $this->_helper->json($result);
	   }
	   
	   $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	   $defaultLanguageCode = $defaultLanguage["Code"];
	   
	   $model = new Webboard_Model_Filter();
	   $params = $this->getRequest()->getParams();
	   $params['DefaultLanguageCode'] = $defaultLanguage['Code'];
	   
	   $data = $model->update($params);
	   
	   // Set Result
	   if ($data->result) {
		   $result['result'] = true;
		   $result['message'] = '';
		   $result['id'] = $data->id;
		   
		   $data = $model->getData($data->id);
		   System_Model_Log::action("webboard.filter", "update", $data->Detail, $data->id);
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
	   $canSave = $sUser->isAllow('webboard.filter.manage');
	   if (!$canSave) {
		  $result['message'] = 'ไม่สามารถลบได้';
		  $this->_helper->json($result);
	   }

	   // Param
	   $params = $this->getRequest()->getParams();

	   // Update
	   $model = new Webboard_Model_Filter();
	   $data = $model->delete($params);
	   // Set Result
	   if ($data->result) {
		   $result['result'] = true;
		   $result['message'] = '';

		   // Log Acion
			$detail = $model->getData($params['id']);
		   System_Model_Log::action("webboard.filter", "delete", $detail->Detail, $params['id']);
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
		$canSave = $sUser->isAllow('webboard.filter.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Webboard_Model_Filter();
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
		$canSave = $sUser->isAllow('webboard.filter.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Webboard_Model_Filter();
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
		$canSave = $sUser->isAllow('webboard.filter.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Webboard_Model_Filter();
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

