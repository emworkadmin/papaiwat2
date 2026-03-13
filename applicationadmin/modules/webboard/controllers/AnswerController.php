<?php

class Webboard_AnswerController extends Zend_Controller_Action
{
	protected $lang = 'th';
	protected $StatusOption = array('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');
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
		$canView = $sUser->isAllow('webboard.answer.view');
		$canAdd = $sUser->isAllow('webboard.answer.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		$this->view->statusPostOption = $this->StatusPostOption;
		$this->view->statusOption = $this->StatusOption;
		
		$modeCategory = new Webboard_Model_Post();
		$categoryOption = $modeCategory->getOption($this->lang);

		$this->view->categoryOption = $categoryOption;
		
		$this->view->canAdd = $canAdd;
	    //Data to Layout
	    $this->view->pageName = "รายการคำตอบกระทู้";
	    $this->view->pageIcon= "fa fa-edit";
        $this->view->subMenus = array();
        
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการคำตอบกระทู้')
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
		 $canDelete = $sUser->isAllow('webboard.answer.manage');
		 $canEdit = $sUser->isAllow('webboard.answer.manage');
		 $canView = $sUser->isAllow('webboard.answer.view');
		 if (!$canView) {
			$this->_helper->redirector('index', 'answer', 'webboard');
		 }
		 
		 $itemid=  $this->_request->getParam('itemid');

		 $model = new Webboard_Model_Answer();
		 $data = $model->getData($itemid);

		 $modeCategory = new Webboard_Model_Post();
		 $categoryOption = $modeCategory->getOption($this->lang);
 
		 $this->view->categoryOption = $categoryOption;

		 $this->view->canDelete = $canDelete;
		 $this->view->canEdit = $canEdit;

		 $this->view->data = $data;
		 $this->view->statusOption = $this->StatusOption;
		 $this->view->statusPostOption = $this->StatusPostOption;

		 // Set View
		$this->view->pageName = "รายละเอียดคำตอบกระทู้";
		$this->view->pageIcon = "fa fa-edit";
	        
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'รายการคำตอบกระทู้', 'url' => $this->view->url(array('module' => 'webboard', 'controller' => 'answer', 'action' => 'index'), null, true)),
			array('name' => 'รายละเอียดคำตอบกระทู้')
        );
		System_Model_Log::action("webboard.answer", "view", $data->WebboardTitle, $itemid);
	}
	
	public function getlistAction() {

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->json($responce);
		}
		
		$canView = $sUser->isAllow('webboard.answer.view');

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
	    $advancesearch['sendfrom'] = $this->getRequest()->getParam('sendfrom');
	    $advancesearch['sendto'] = $this->getRequest()->getParam('sendto');

		if($canView):
			$model = new Webboard_Model_Answer();
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
				$d['Category'] = Emwork_Utility::notShowNull($item["TitleCategory"]);
				// $d['Department'] = Emwork_Utility::notShowNull($item["DepartmentName"]);	
				$d['Name'] = Emwork_Utility::notShowNull($item["FullName"]);
				$d['IsEnableCode'] = $item["IsEnable"];
				$d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MSSqlToDateThai($item["Created"], 'DT'));
				$d['IsEnable'] = Emwork_Utility::notShowNull($this->StatusOption[$item["IsEnable"]]);
				$d['StatusCode'] = $item["Status"];
				$d['Status'] = Emwork_Utility::notShowNull($this->StatusPostOption[$item["Status"]]);

	            $responce['data'][] = $d;
	        }
	    }

	    $this->_helper->json($responce);
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
		$canSave = $sUser->isAllow('webboard.answer.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        $defaultLanguageCode = $defaultLanguage["Code"];
        
        $model = new Webboard_Model_Answer();
        $params = $this->getRequest()->getParams();
        $params['DefaultLanguageCode'] = $defaultLanguage['Code'];

		$data = $model->update($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            $result['id'] = $data->id;
			System_Model_Log::action("webboard.answer", "update", $params['Title'], $params["hdfID"]);
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
		$canSave = $sUser->isAllow('webboard.answer.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}

        // Param
        $params = $this->getRequest()->getParams();

        // Update
		$model = new Webboard_Model_Answer();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
			// Log Acion
			$data = $model->getData($params['id']);
			System_Model_Log::action("webboard.answer", "delete", $data->WebboardTitle, $params["id"]);
        } else {
            $result['result'] = false;
            $result['message'] = 'ลบไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
	}
	
	public function excelAction()
    {
		  $Param =  $this->getRequest()->getParams();
		  $Param = json_decode($Param['t']);

		  
		  //Advance Search
		  $advancesearch['textsearch'] = $Param->textserch;
		  $advancesearch['status'] = $Param->status;
		  $advancesearch['sendfrom'] = $Param->sendfrom;
		  $advancesearch['sendto'] = $Param->sendto;
		  $advancesearch['book'] = $Param->book;

		  $advancesearch['sendfrom'] = base64_decode($advancesearch['sendfrom']);
		  $advancesearch['sendto'] = base64_decode($advancesearch['sendto']);

		  $model = new Guestbook_Model_Guestbook();
		  $data = $model->getListExcel($advancesearch);
		  $this->view->data = $data ;

			// Set View
			Zend_Layout::resetMvcInstance();

			// Set Response
			header("Content-Type: application/vnd.ms-excel");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("content-disposition: attachment;filename=-ข้อมูลการลงนาม.xls");

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
		$canSave = $sUser->isAllow('webboard.answer.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Webboard_Model_Answer();
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
		$canSave = $sUser->isAllow('webboard.answer.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Webboard_Model_Answer();
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
		$canSave = $sUser->isAllow('webboard.answer.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Webboard_Model_Answer();
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

