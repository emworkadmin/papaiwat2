<?php

class Webboard_UserController extends Zend_Controller_Action
{
	protected $lang = 'th';
	protected $StatusOption = array('Y' => 'เป็นสมาชิก', 'N' => 'ยกเลิกการเป็นสมาชิก');
	protected $GenderOption = array('M' => 'ผู้ชาย', 'F' => 'ผู้หญิง');
	protected $StatusPostOption = array('W' => 'รอการอนุมัติ' , 'Y' => 'ผ่านการพิจราณา', 'N' => 'ไม่ผ่านการพิจราณา');
	
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
		$canView = $sUser->isAllow('webboard.user.view');
		$canAdd = $sUser->isAllow('webboard.user.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		$this->view->statusPostOption = $this->StatusPostOption;
		$this->view->statusOption = $this->StatusOption;
		
		$modeCategory = new Webboard_Model_Post();
		$categoryOption = $modeCategory->getOption($this->lang);

		$this->view->categoryOption = $categoryOption;
		
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->view->windowOS = 'Y'  ;
		} else {
			$this->view->windowOS = 'N'  ;
		}

		$this->view->canAdd = $canAdd;
	    //Data to Layout
	    $this->view->pageName = "รายการข้อมูลสมาชิก";
	    $this->view->pageIcon= "fa fa-edit";
        $this->view->subMenus = array();
        
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการข้อมูลสมาชิก')
        );

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
		 $canAdd = $sUser->isAllow('webboard.user.manage');
		 
		 if (!$canAdd) {
			$this->_helper->redirector('index', 'user', 'webboard');
		 }
		 
		 $itemid=  $this->_request->getParam('itemid');

		 $model = new Webboard_Model_Post();
		 $data = $model->getData($itemid);

		 $modeCategory = new Webboard_Model_Post();
		 $categoryOption = $modeCategory->getOption($this->lang);
 
		 $this->view->categoryOption = $categoryOption;

		 $this->view->data = $data;
		 $this->view->statusOption = $this->StatusOption;
		 $this->view->statusPostOption = $this->StatusPostOption;
	 	$this->view->genderOption = $this->GenderOption;
			 
		// Set View
		$this->view->pageName = "เพิ่มข้อมูลสมาชิก";
		$this->view->pageIcon = "fa fa-edit";

	        
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'รายการข้อมูลสมาชิก', 'url' => $this->view->url(array('module' => 'webboard', 'controller' => 'user', 'action' => 'index'), null, true)),
			array('name' => 'เพิ่มข้อมูลสมาชิก')
        );
	   
   }


	public function editAction() {

		 // Log Access
		 System_Model_Log::access();
	    
		 $sUser = System_Model_SUser::getInstance();
		 if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		 }
		 if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		 }
		 $canDelete = $sUser->isAllow('webboard.user.manage');
		 $canEdit = $sUser->isAllow('webboard.user.manage');
		 $canView = $sUser->isAllow('webboard.user.view');
		 if (!$canView) {
			$this->_helper->redirector('index', 'user', 'webboard');
		 }
		 
		 $itemid=  $this->_request->getParam('itemid');

		 $model = new Webboard_Model_User();
		 $data = $model->getData($itemid);

		 $modeCategory = new Webboard_Model_Post();
		 $categoryOption = $modeCategory->getOption($this->lang);

		 $this->view->canDelete = $canDelete;
		 $this->view->canEdit = $canEdit;
 
		 $this->view->categoryOption = $categoryOption;
		 $this->view->data = $data;
		 $this->view->genderOption = $this->GenderOption;
		 $this->view->statusOption = $this->StatusOption;
		 $this->view->statusPostOption = $this->StatusPostOption;
	 	
		// Set View
		$this->view->pageName = "ปรับปรุงข้อมูลสมาชิก";
		$this->view->pageIcon = "fa fa-edit";

	        
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'รายการข้อมูลสมาชิก', 'url' => $this->view->url(array('module' => 'webboard', 'controller' => 'user', 'action' => 'index'), null, true)),
			array('name' => 'ปรับปรุงข้อมูลสมาชิก')
		);
		
		$name = $data->TitleName.$data->FirstName. ' '. $data->LastNamel;
		System_Model_Log::action("webboard.user", "view", $name, $data->id);

	}
	
	public function getlistAction() {

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->json($responce);
		}
		
		$canView = $sUser->isAllow('webboard.user.view');

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
		$advancesearch['statuspost'] = $this->getRequest()->getParam('statuspost');
		$advancesearch['category'] = $this->getRequest()->getParam('category');
	    $advancesearch['sendfromLogin'] = $this->getRequest()->getParam('sendfromLogin');
        $advancesearch['sendtoLogin'] = $this->getRequest()->getParam('sendtoLogin');
        $advancesearch['sendfromRegister'] = $this->getRequest()->getParam('sendfromRegister');
	    $advancesearch['sendtoRegister'] = $this->getRequest()->getParam('sendtoRegister');

		if($canView):
			$model = new Webboard_Model_User();
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
	            $d['Username'] = Emwork_Utility::notShowNull($item["Username"]);
				$d['Email'] = Emwork_Utility::notShowNull($item["Email"]);
				$d['FullName'] = Emwork_Utility::notShowNull($item["FullName"]);
                $d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["Created"], 'DT'));
                $d['LastLoginDate'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["LastLoginDate"], 'DT'));
                $d['IsEnableCode'] = $item["IsEnable"];
				$d['IsEnable'] = Emwork_Utility::notShowNull($this->StatusOption[$item["IsEnable"]]);

	            $responce['data'][] = $d;
	        }
	    }

	    $this->_helper->json($responce);
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
		$canAdd = $sUser->isAllow('webboard.user.manage');
		if (!$canAdd) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Webboard_Model_User();
        $params = $this->getRequest()->getParams();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];

		$checkusername = $model->getDataByUsername($params['Username']);
		if($checkusername->ID) {
			$result['message'] = 'Uesrname นี้ถูกใช้ไปแล้วกรุณาตรวจสอบ';
			$this->_helper->json($result);
		}

		$checkemail = $model->getDataByEmail($params['Email']);
		if($checkemail->ID) {
			$result['message'] = 'Email นี้ถูกใช้ไปแล้วกรุณาตรวจสอบ';
			$this->_helper->json($result);
		}
		
        $data = $model->save($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;

			$name = $params['TitleName'].$params['FirstName'].' '.$params['LastName'];
			System_Model_Log::action("webboard.user", "save", $name, $data->id);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
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
		$canSave = $sUser->isAllow('webboard.user.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Webboard_Model_User();
        $params = $this->getRequest()->getParams();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];

		$checkemail = $model->getDataByEmail($params['Email']);
		if($checkemail->ID) {
			if($checkemail->ID != $params['hdfID']) {
				$result['message'] = 'Email นี้ถูกใช้ไปแล้วกรุณาตรวจสอบ';
				$this->_helper->json($result);
			}
		}

        $data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;

			$name = $params['TitleName'].$params['FirstName'].' '.$params['LastName'];
			System_Model_Log::action("webboard.user", "update", $name, $data->id);
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
		$canSave = $sUser->isAllow('webboard.user.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}

        // Param
        $params = $this->getRequest()->getParams();
        
        // Update
        $model = new Webboard_Model_User();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
			// Log Acion
			System_Model_Log::action("webboard.user", "delete", $name, $params['id']);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
	}

	public function excelAction()
    {

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('webboard.user.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}

		$Param =  $this->getRequest()->getParams();
		
		//Advance Search
		$advancesearch['textsearch'] = urldecode($Param['textsearch']);
		$advancesearch['status'] = $Param['status'];
		$advancesearch['sendfromLogin'] = $Param['sendfromLogin'];
		$advancesearch['sendtoLogin'] = $Param['sendtoLogin'];
		$advancesearch['sendfromRegister'] = $Param['sendfromRegister'];
		$advancesearch['sendtoRegister'] = $Param['sendtoRegister'];

		$advancesearch['sendfromLogin'] = base64_decode($advancesearch['sendfromLogin']);
		$advancesearch['sendtoLogin'] = base64_decode($advancesearch['sendtoLogin']);
		$advancesearch['sendfromRegister'] = base64_decode($advancesearch['sendfromRegister']);
		$advancesearch['sendtoRegister'] = base64_decode($advancesearch['sendtoRegister']);
		
		if($advancesearch['textsearch'] == ' '){
		$advancesearch['textsearch'] = null;
		}

		$data = array();
		if($canView):
			$model = new Webboard_Model_User();
			$data = $model->getListExcel($advancesearch);
		endif;

		$this->view->data = $data ;

		// Set View
		Zend_Layout::resetMvcInstance();

		// Set Response
		header("Content-Type: application/vnd.ms-excel");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("content-disposition: attachment;filename=ข้อมูลรายชื่อสมาชิก.xls");

	}

	public function csvAction()
    {
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('webboard.user.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}

		$Param =  $this->getRequest()->getParams();

		//Advance Search
		$advancesearch['textsearch'] = urldecode($Param['textsearch']);
		$advancesearch['status'] = $Param['status'];
		$advancesearch['sendfromLogin'] = $Param['sendfromLogin'];
		$advancesearch['sendtoLogin'] = $Param['sendtoLogin'];
		$advancesearch['sendfromRegister'] = $Param['sendfromRegister'];
		$advancesearch['sendtoRegister'] = $Param['sendtoRegister'];

		$advancesearch['sendfromLogin'] = base64_decode($advancesearch['sendfromLogin']);
		$advancesearch['sendtoLogin'] = base64_decode($advancesearch['sendtoLogin']);
		$advancesearch['sendfromRegister'] = base64_decode($advancesearch['sendfromRegister']);
		$advancesearch['sendtoRegister'] = base64_decode($advancesearch['sendtoRegister']);
		
		if($advancesearch['textsearch'] == ' ') {
			$advancesearch['textsearch'] = null;
		}
		$data = array();
		if($canView):
			$model = new Webboard_Model_User();
			$data = $model->getListExcel($advancesearch);
		endif;
		$this->view->data = $data ;

		// Set View
		Zend_Layout::resetMvcInstance();
		header("Content-type: text/csv ; charset=utf-8");
		header("Content-Disposition: attachment; filename=ข้อมูลรายชื่อสมาชิก.csv ; charset=tis-620");
		header("Pragma: no-cache");
		header("Expires: 0");

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
		$canSave = $sUser->isAllow('webboard.user.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Webboard_Model_User();
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
		$canSave = $sUser->isAllow('webboard.user.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Webboard_Model_User();
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
		$canSave = $sUser->isAllow('webboard.user.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Webboard_Model_User();
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

