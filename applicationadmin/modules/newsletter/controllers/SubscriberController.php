<?php

class Newsletter_SubscriberController extends Zend_Controller_Action
{
    protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
        
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
        $canView = $sUser->isAllow('newsletter.subscriber.view');
        $canAdd = $sUser->isAllow('newsletter.subscriber.manage');
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }


        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("NewsletterSubscriberSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("NewsletterSubscriberSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("NewsletterSubscriberSearch");
        }
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        // Send To View
        $this->view->statusOption = $this->statusOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการผู้สมัคร";
        $this->view->pageIcon = "fa fa-user";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการผู้สมัคร')
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
        $canView = $sUser->isAllow('newsletter.subscriber.view');
        
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
        $advancesearch['textsearch'] = $textstr;
        $advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');
        
        $infoSearchNamespace = new Zend_Session_Namespace("NewsletterSubscriberSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->isenable= $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
        
        $model = new Newsletter_Model_Subscriber();
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
                $d['Name'] = Emwork_Utility::notShowNull($item["Name"]);
                $d['Email'] = Emwork_Utility::notShowNull($item["Email"]);
                $d['CategoryName'] = Emwork_Utility::notShowNull($item["CategoryName"]);
                $d['Created'] = Emwork_Utility::notShowNull(Emwork_Utility_DateTime::MySqlToDateThai($item["Created"], 'DT'));
                $d['IsEnableCode'] = $item["IsEnable"];
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->statusOption[$item["IsEnable"]]);
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);
    }

    function reportcsvAction()
    {
        $model = new Newsletter_Model_Subscriber();
        $data = $model->getlistreport();

        $html = "Name,Email,Created,IsEnable"."\n";
        foreach($data as $k => $v) {
            $html .= Emwork_Utility::notShowNull($v["Name"]) . ",";
            $html .= Emwork_Utility::notShowNull($v["Email"]) . ",";
            $html .= Emwork_Utility::notShowNull($v["Created"]) . ",";
            $html .= $this->statusOption[$v['IsEnable']] . "\n";
        }

        $this->view->html = $html;

		// Set view
		Zend_Layout::resetMvcInstance();

		// // Set Response
        header("Content-type: application/csv");
        header("Expires: 0");
        header("Content-Disposition: attachment; filename=Memberlist.csv");
        header("Pragma: no-cache");
    }

    function addAction()
    {
		// Log Access
		System_Model_Log::access ();
		
		$sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->redirector('index', 'index', 'default');
        }
        $canAdd = $sUser->isAllow('newsletter.subscriber.manage');

        if (!$canAdd) {
            $this->_helper->redirector('index', 'subscriber', 'newsletter');
        }
        
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $model = new Newsletter_Model_Category();
        $categoryOption = $model->getOption($defaultLanguage['Code']);

        // Send Option and lookup To View
        $this->view->statusOption = $this->statusOption;
        $this->view->categoryOption = $categoryOption;

        // echo "<pre>";
        // print_r($categoryOption);
        // exit;

        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "เพิ่มผู้สมัคร";
        $this->view->pageIcon = "   ";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array ('name' => 'รายการผู้สมัคร','url' => $this->view->url ( array ('module' => 'newsletter', 'controller' => 'subscriber', 'action' => 'index'), null, true )),
            array('name' => 'เพิ่มผู้สมัคร')
            
        );
    }

    function editAction()
    {
		// Log Access
		System_Model_Log::access ();
		
		$sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $this->_helper->redirector('index', 'index', 'default');
        }
        if ($sUser->getInfo()->UserType != "B") {
            $this->_helper->redirector('index', 'index', 'default');
        }
        $canDelete = $sUser->isAllow('newsletter.subscriber.manage');
        $canEdit = $sUser->isAllow('newsletter.subscriber.manage');
        $canView = $sUser->isAllow('newsletter.subscriber.view');
        if (!$canView) {
            $this->_helper->redirector('index', 'subscriber', 'newsletter');
        }
		
        // Get Param
        $itemID = $this->_request->getParam('itemid');
        
        // Model
        if (!$itemID) {
        	$this->_helper->redirector('index', 'item', 'newsletter');
        }

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        $model = new Newsletter_Model_Subscriber();
        $data = $model->getInfo($itemID);

        $dataCats = $model->getDataCategory($data->ID);

        $this->view->data = $data;
        $this->view->dataCats = $dataCats;

        $modelCategory = new Newsletter_Model_Category();
        $categoryOption = $modelCategory->getOption($defaultLanguage['Code']);

        foreach($categoryOption as $key => $value) {
            $categoryOption[$key] = array();
            if(in_array($key, $dataCats)) {
                $categoryOption[$key]['Name'] = $value;
                $categoryOption[$key]['Status'] = 'Y';
            } else {
                $categoryOption[$key]['Name'] = $value;
                $categoryOption[$key]['Status'] = 'N';
            }
        }

        // Send Option and lookup To View
        $this->view->statusOption = $this->statusOption;
        $this->view->categoryOption = $categoryOption;

        // Set View
        $this->view->canEdit = $canEdit;
        $this->view->pageName = "ปรับปรุงผู้สมัคร";
        $this->view->pageIcon = "fa fa-user";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array ('name' => 'รายการผู้สมัคร','url' => $this->view->url ( array ('module' => 'newsletter', 'controller' => 'subscriber', 'action' => 'index'), null, true )),
            array('name' => 'ปรับปรุงผู้สมัคร')
        );        

        // Log Acion
        System_Model_Log::action("newsletter.subscriber", "view", $data->Email, $itemID);
    }

    function saveAction()
    {
        // Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        $canAdd = $sUser->isAllow('newsletter.subscriber.manage');
        if (!$canAdd) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $params = $this->getRequest()->getParams();
		
        $model = new Newsletter_Model_Subscriber();

        $checkEmail = $model->checkEmail($params['Email']);
        if($checkEmail->ID) {
            $result['message'] = 'อีเมล์นี้ถูกใช้ไปแล้ว';
			$this->_helper->json($result);
        }

		$data = $model->save($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("newsletter.subscriber", "save", $params['Email'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ($result);
    }

    function updateAction()
    {
        // Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
        if (!$sUser->hasData()) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        if ($sUser->getInfo()->UserType != "B") {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        $canSave = $sUser->isAllow('newsletter.subscriber.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถบันทึกได้';
            $this->_helper->json($result);
        }
        
        $params = $this->getRequest()->getParams();

        $model = new Newsletter_Model_Subscriber();

        $checkEmail = $model->checkEmail($params['Email'], $params['hdfID']);
        if($checkEmail->ID) {
            $result['message'] = 'อีเมล์นี้ถูกใช้ไปแล้ว';
			$this->_helper->json($result);
        }
        
        $data = $model->update($params);
        
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
            System_Model_Log::action("newsletter.subscriber", "update", $params['Email'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );
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
        $canSave = $sUser->isAllow('newsletter.subscriber.manage');
        if (!$canSave) {
            $result['message'] = 'ไม่สามารถลบได้';
            $this->_helper->json($result);
        }
        
        // Param
		$params = $this->getRequest()->getParams();
        
        // Update
        $model = new Newsletter_Model_Subscriber();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("newsletter.subscriber", "delete",  $params["Email"], $params["id"]);
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
		$canSave = $sUser->isAllow('newsletter.subscriber.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Newsletter_Model_Subscriber();
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
		$canSave = $sUser->isAllow('newsletter.subscriber.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Newsletter_Model_Subscriber();
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
		$canSave = $sUser->isAllow('newsletter.subscriber.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Newsletter_Model_Subscriber();
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

?>