<?php

class System_LogController extends Zend_Controller_Action
{
    protected $actionOption = array("new" => "สร้างข้อมูล", "save" => "ปรับปรุง", "delete" => "ลบข้อมูล");
    protected $systemOption = array();
    protected $logTypeOption = array("Login" => "เข้าสู่ระบบ", "Logoff" => "ออกจากระบบ" );
    protected $resultOption = array("Success" => "สำเร็จ", "Fail" => "ผิดพลาด");
    
	public function init()
	{ 
		
	}
	
	public function actionAction()
	{
	    // Log Access
	    System_Model_Log::access();
	    
	    $sUser = System_Model_SUser::getInstance();
	    if (!$sUser->hasData()) {
	        $this->_helper->redirector('index', 'index', 'default');
	    }
	    if ($sUser->getInfo()->UserType != 'B') {
	        $this->_helper->redirector('index', 'index', 'default');
	    }
	    $canView = $sUser->isAllow("setting.logaccess.view");
	    if (!$canView) {
	        $this->_helper->redirector('index', 'index', 'default');
	    }
	    
	    $InfoSearch = new stdClass();
	    if (Zend_Session::namespaceIsset("SystemLogActionSearch")) {
	    	$infoSearchNamespace = new Zend_Session_Namespace("SystemLogActionSearch");
	    	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
	    	$InfoSearch->system= $infoSearchNamespace->system;
	    	$InfoSearch->actiontype= $infoSearchNamespace->actiontype;
	    	$InfoSearch->sendfrom= $infoSearchNamespace->sendfrom;
	    	$InfoSearch->sendto= $infoSearchNamespace->sendto;
	    	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
	    	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
	    	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
	    	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
	    	
	    	$this->view->InfoSearch = $InfoSearch;
	    	Zend_Session::namespaceUnset("SystemLogActionSearch");
	    }
	    
	    $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	    
	    $moduleModel = new System_Model_Module();
	    $moduleOption = $moduleModel->getOption(false);
	    $actionTypeOption = $moduleModel->getActionTypeOption(false);
	    
	    // Send Data to View
	    $this->view->systemOption = $moduleOption;
	    $this->view->actionOption = $actionTypeOption;
	    
	    // Data to Layout
	    $this->view->pageName = "รายการประวัติการใช้งาน";
	    $this->view->pageIcon= "fa fa-list";
	    $this->view->subMenus = array(
	    		array('name' => 'ประวัติการใช้งาน', 'active' => true),
	    		array('name' => 'ประวัติการเข้าใช้งาน', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'log', 'action' => 'security'), null, true)),
	    );
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการประวัติการใช้งาน')
	    );		
	}
	
	public function getactionlistAction() {
	    
	    $filterInt = new Zend_Filter_Int();
	    $canView = true;
	    
	    $sUser = System_Model_SUser::getInstance();
	    if (!$sUser->hasData()) {
	        $canView = false;
	    }
	    if ($sUser->getInfo()->UserType != 'B') {
	        $canView = false;
	    }
	    $canView = $sUser->isAllow("setting.logaccess.view");
	    
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
	    $advancesearch['system'] = $this->getRequest()->getParam('system');
	    $advancesearch['actiontype'] = $this->getRequest()->getParam('actiontype');
	    $advancesearch['sendfrom'] = $this->getRequest()->getParam('sendfrom');
	    $advancesearch['sendto'] = $this->getRequest()->getParam('sendto');
	    
	    $infoSearchNamespace = new Zend_Session_Namespace("SystemLogActionSearch");
	    $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
	    $infoSearchNamespace->system= $advancesearch['system'];
	    $infoSearchNamespace->actiontype= $advancesearch['actiontype'];
	    $infoSearchNamespace->sendfrom= $advancesearch['sendfrom'];
	    $infoSearchNamespace->sendto= $advancesearch['sendto'];
	    $infoSearchNamespace->tblPage = $_start;
	    $infoSearchNamespace->tblRow = $_length;
	    $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
	    $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
	    
	    $model = new System_Model_Log();
	    $data = $model->getActionList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
	    
	    $i=$_start;
	    $responce = array();
	    $responce['draw'] = $_draw;
	    $responce['recordsTotal'] = 0;
	    $responce['recordsFiltered'] = 0;
	    $responce['data'] = array();
	    if ($canView && $data) {
	        $responce['recordsTotal'] = $data[0]["RECORD"];
	        $responce['recordsFiltered'] = $data[0]["RECORD"];
	        
	        foreach($data as $item){
	            $i++;
	            $d = array();
	            $d['DT_RowId'] = $item["ID"];
	            $d['NO'] = $i;
	            $d['ModuleName'] = Emwork_Utility::notShowNull($item["ModuleName"]);
	            $d['FullName'] = Emwork_Utility::notShowNull($item["FullName"]);
	            $d['ActionTypeName'] = Emwork_Utility::notShowNull($item["ActionTypeName"]);
	            $d['ActionDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["ActionDate"], 'DT'));
	            $d['ValueString'] = Emwork_Utility::notShowNull($item["ValueString"]);
	            $d['RemoteIP'] = Emwork_Utility::notShowNull($item["RemoteIP"]);
	            $responce['data'][] = $d;
	        }
	    }
	    $this->_helper->json($responce);
	}
	
	public function actionexportexcelAction() {
		
		$canView = true;
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$canView = $canView && false;
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$canView = $canView &&false;
		}
		$canView = $canView && $sUser->isAllow("setting.logaccess.view");
		
		// Basic Search
		$textstr = $this->getRequest()->getParam('textsearch');
		$textstr = base64_decode($textstr);
		$filter = '';
		//Advance Search
		$advancesearch['textsearch'] = $textstr;
		$advancesearch['system'] = base64_decode($this->getRequest()->getParam('system'));
		$advancesearch['action'] = base64_decode($this->getRequest()->getParam('action'));
		$advancesearch['sendfrom'] = base64_decode($this->getRequest()->getParam('sendfrom'));
		$advancesearch['sendto'] = base64_decode($this->getRequest()->getParam('sendto'));
		
		$data = array();
		
		if ($canView) {
			$model = new System_Model_Log();
			$data = $model->getActionAll($sidx, $sord, $textstr, $filter, $advancesearch);
		}
		
		$this->_helper->layout()->disableLayout();
		
		$this->view->data = $data;
		
		// Set Response
		header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=Export.xls");
	}
	
	public function securityAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		$canView = $sUser->isAllow("setting.logaccess.view");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass();
		if (Zend_Session::namespaceIsset("SystemLogSecuritySearch")) {
			$infoSearchNamespace = new Zend_Session_Namespace("SystemLogSecuritySearch");
			$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
			$InfoSearch->logtype= $infoSearchNamespace->logtype;
			$InfoSearch->result= $infoSearchNamespace->result;
			$InfoSearch->sendfrom= $infoSearchNamespace->sendfrom;
			$InfoSearch->sendto= $infoSearchNamespace->sendto;
			$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
			$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
			$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
			$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
			
			$this->view->InfoSearch = $InfoSearch;
			Zend_Session::namespaceUnset("SystemLogSecuritySearch");
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// Send Data to View
		$this->view->logTypeOption = $this->logTypeOption;
		$this->view->resultOption = $this->resultOption;
		
		// Data to Layout
		$this->view->pageName = "รายการประวัติการเข้าใช้งาน";
		$this->view->pageIcon= "fa fa-list";
		$this->view->subMenus = array(
				array('name' => 'ประวัติการใช้งาน', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'log', 'action' => 'action'), null, true)),
				array('name' => 'ประวัติการเข้าใช้งาน', 'active' => true),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'รายการประวัติการเข้าใช้งาน')
		);
	}
	
	public function getsecuritylistAction() {
		
		$filterInt = new Zend_Filter_Int();
		$canView = true;
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$canView = false;
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$canView = false;
		}
		$canView = $sUser->isAllow("setting.logaccess.view");
		
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
		$advancesearch['logtype'] = $this->getRequest()->getParam('logtype');
		$advancesearch['result'] = $this->getRequest()->getParam('result');
		$advancesearch['sendfrom'] = $this->getRequest()->getParam('sendfrom');
		$advancesearch['sendto'] = $this->getRequest()->getParam('sendto');
		
		$infoSearchNamespace = new Zend_Session_Namespace("SystemLogSecuritySearch");
		$infoSearchNamespace->textsearch = $advancesearch['textsearch'];
		$infoSearchNamespace->logtype= $advancesearch['logtype'];
		$infoSearchNamespace->result= $advancesearch['result'];
		$infoSearchNamespace->sendfrom= $advancesearch['sendfrom'];
		$infoSearchNamespace->sendto= $advancesearch['sendto'];
		$infoSearchNamespace->tblPage = $_start;
		$infoSearchNamespace->tblRow = $_length;
		$infoSearchNamespace->tblOrderCol = $_order[0]['column'];
		$infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
		
		$model = new System_Model_Log();
		$data = $model->getSecutiryList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
		
		$i=$_start;
		$responce = array();
		$responce['draw'] = $_draw;
		$responce['recordsTotal'] = 0;
		$responce['recordsFiltered'] = 0;
		$responce['data'] = array();
		if ($canView && $data) {
			$responce['recordsTotal'] = $data[0]["RECORD"];
			$responce['recordsFiltered'] = $data[0]["RECORD"];
			
			foreach($data as $item){
				$i++;
				$d = array();
				$d['DT_RowId'] = $item["ID"];
				$d['NO'] = $i;
				$d['ActionDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["ActionDate"], 'DT'));
				$d['Username'] = Emwork_Utility::notShowNull($item["Username"]);
				$d['FullName'] = Emwork_Utility::notShowNull($item["FullName"]);
				$d['LogType'] = Emwork_Utility::notShowNull($this->logTypeOption[$item["LogType"]]);
				$d['Result'] = Emwork_Utility::notShowNull($this->resultOption[$item["Result"]]);
				$d['RemoteIP'] = Emwork_Utility::notShowNull($item["RemoteIP"]);
				$responce['data'][] = $d;
			}
		}
		$this->_helper->json($responce);
	}
	
	public function securitydetailAction() {
		
		$this->_helper->layout()->disableLayout();
		
		$filterInt = new Zend_Filter_Int();
		$canView = true;
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$canView = $canView && false;
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$canView = $canView && false;
		}
		$canView = $canView && $sUser->isAllow("setting.logaccess.view");
		
		$params = $this->getRequest()->getParams();
		$id = $params['id'];
		
		$data = new stdClass();
		if ($canView && !empty($id)) {
			$model = new System_Model_Log();
			$data = $model->getSecutiryDetail($id);
		}
		
		$this->view->data = $data;
		$this->view->logTypeOption = $this->logTypeOption;
		$this->view->resultOption = $this->resultOption;
	}
	
	public function securityexportexcelAction() {
		
		$canView = true;
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$canView = $canView && false;
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$canView = $canView &&false;
		}
		$canView = $canView && $sUser->isAllow("setting.logaccess.view");
		
		// Basic Search
		$textstr = $this->getRequest()->getParam('textsearch');
		$textstr = base64_decode($textstr);
		$filter = '';
		//Advance Search
		$advancesearch['textsearch'] = $textstr;
		$advancesearch['logtype'] = base64_decode($this->getRequest()->getParam('logtype'));
		$advancesearch['result'] = base64_decode($this->getRequest()->getParam('result'));
		$advancesearch['sendfrom'] = base64_decode($this->getRequest()->getParam('sendfrom'));
		$advancesearch['sendto'] = base64_decode($this->getRequest()->getParam('sendto'));
		
		$data = array();
		
		if ($canView) {
			$model = new System_Model_Log();
			$data = $model->getSecutiryAll($sidx, $sord, $textstr, $filter, $advancesearch);
		}
		
		$this->_helper->layout()->disableLayout();
		
		$this->view->data = $data;
		$this->view->logTypeOption = $this->logTypeOption;
		$this->view->resultOption = $this->resultOption;
		
		// Set Response
		header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=Export.xls");
	}
}

