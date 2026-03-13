<?php

class Popup_IndexController extends Zend_Controller_Action
{
    protected $statusOption = array(
        "Y" => "ใช้งาน", 
        "N" => "ไม่ใช้งาน"
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
		$canView = $sUser->isAllow('popup.item.view');
		$canAdd = $sUser->isAllow('popup.item.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}

        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("PopupIndexSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("PopupIndexSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->Status = $infoSearchNamespace->Status;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("PopupIndexSearch");
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$this->view->canAdd=$canAdd;
        $this->view->statusOption = $this->statusOption;

        $this->view->pageName = "รายการ Popup";
        $this->view->pageIcon= "fa fa-bell-o";
	    $this->view->subMenus = array();
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการ Popup')
	    );

    }

    public function getlistAction()
    {

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->json($responce);
		}
		
		$canView = $sUser->isAllow('popup.item.view');

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
		
		//Repage hold value
		$searchNamespace = new Zend_Session_Namespace("PopupIndexSearch");
		
		$searchNamespace->searchText = $advancesearch['textsearch'];
		$searchNamespace->status = $advancesearch['Status'];
		$searchNamespace->tblPage = $_start;
		$searchNamespace->tblRow = $_length;
		$searchNamespace->tblOrderCol = $_order[0]['column'];
        $searchNamespace->tblOrderDir = $_order[0]['dir'];

		if($canView):
			$model = new Popup_Model_Index();
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
	            $d['Status'] = Emwork_Utility::notShowNull($this->statusOption[$item["Status"]]);
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
		$canAdd = $sUser->isAllow('popup.item.manage');
		
		if (!$canAdd) {
		   $this->_helper->redirector('index', 'item', 'popup');
		}

		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;

		$this->view->statusOption = $this->statusOption;

		$this->view->pageName = "เพิ่ม Popup";
        $this->view->pageIcon= "fa fa-bell-o";
	    $this->view->subMenus = array();
	    $this->view->breadcrumb = array(
			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'รายการ Popup', 'url' => $this->view->url(array('module' => 'popup', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'เพิ่ม Popup')
	    ); 
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
		$canDelete = $sUser->isAllow('popup.item.manage');
		$canEdit = $sUser->isAllow('popup.item.manage');
		$canView = $sUser->isAllow('popup.item.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'item', 'popup');
		}

		$itemid = $this->getRequest()->getParam('itemid');

		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		// menugroup_info
		$model = new Popup_Model_Index();
		$data = $model->getData($itemid);

		$dataDetails = array();
		if (count($languages) > 0) {
			foreach ($languages as $lang) {
				$langCode = $lang['Code'];
				$detail = $model->getDataByLanguage($data->ID, $langCode);
				foreach($detail as $item){
					if (!empty($item['Lang'])) {
						$dataDetails[$langCode] = $item;
					
					}
				}
			}
		}

		$this->view->canEdit=$canEdit;
		$this->view->canDelete=$canDelete;
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;

		$this->view->data = $data;
		$this->view->dataDetails = $dataDetails;
		$this->view->statusOption = $this->statusOption;

		$this->view->pageName = "แก้ไข Popup";
        $this->view->pageIcon= "fa fa-bell-o";
	    $this->view->subMenus = array();
	    $this->view->breadcrumb = array(
			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'รายการ Popup', 'url' => $this->view->url(array('module' => 'popup', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'แก้ไข Popup')
		); 
		
		// Log Acion
		System_Model_Log::action("popup.index", "view", $data->Title, $itemID);
	}

	function updateAction()
	{
        // Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
	
		// Param
		$params = $this->getRequest()->getParams();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('popup.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
        // Prepare Intro Image
        $modelFile = new System_Model_Fileinfo();
	    $introImageID= null;
	    if (count($params['hdfIntroImage'])) {
	        unset($fileData);
	        $fileData = $modelFile->itemByHashName($params['hdfIntroImage'][0]);
	        if ($fileData->ID != null) {
	            $introImageID = $fileData->ID;
	        }
	    }
        $params['IntroImageID'] = $introImageID;
        
        $model = new Popup_Model_Index();
        $data = $model->update($params);
		
        if ($data->result) {
	        $result['result'] = true;
	        $result['message'] = '';
	        $result['id'] = $data->ID;
			
			// Log Acion
			System_Model_Log::action("popup.index", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
	    } else {
	        $result['result'] = false;
	        $result['message'] = 'บันทึกไม่สำเร็จ';
        }

        $this->_helper->json($result);
	}
	
	function saveAction()
	{
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
		$canAdd = $sUser->isAllow('popup.item.manage');
		if (!$canAdd) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		$params = $this->getRequest()->getParams();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];

		$model = new Popup_Model_Index();
		$data = $model->save($params);

		if($data->result) {
			$result['result'] = true;
            $result['id'] = $data->ID;
			// Log Acion
			System_Model_Log::action("popup.index", "save", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
		}

		$this->_helper->json($result);
	}

	public function deleteAction() {
	    $params = $this->getRequest()->getParams();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('popup.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}

	    $model = new Popup_Model_Index();
	    $data = $model->delete($params);
	    
	    $data->result = ture;
	    if ($data->result) {
	        $result['result'] = true;
	        $result['message'] = '';
			// Log Acion
			System_Model_Log::action("popup.index", "delete", $params['Title'], $params['itemid']);
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
		$canSave = $sUser->isAllow('popup.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Popup_Model_Index();
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
		$canSave = $sUser->isAllow('popup.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Popup_Model_Index();
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
		$canSave = $sUser->isAllow('popup.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Popup_Model_Index();
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