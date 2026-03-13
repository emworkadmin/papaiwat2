<?php

class Orgchart_PersonController extends Zend_Controller_Action
{
    protected $publishOption = array ('Y' => 'เผยแพร่', 'N' => 'ไม่เผยแพร่');

	public function initAction()
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
		$canView = $sUser->isAllow('orgchart.person.view');
		$canAdd = $sUser->isAllow('orgchart.person.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}

        $InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("OrgchartPersonSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("OrgchartPersonSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
            $InfoSearch->isenable = $infoSearchNamespace->isenable;
            $InfoSearch->isWorkgroupId = $infoSearchNamespace->isWorkgroupId;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
            $InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
            
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("OrgchartPersonSearch");
        }

        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
        
        $modelOption = new Orgchart_Model_Workgroup();
        $workgroupOption = $modelOption->getOption($defaultLanguage["Code"]);

        // Send To View
        $this->view->publishOption = $this->publishOption;
        $this->view->workgroupOption = $workgroupOption;
        
        // Set View
        $this->view->canAdd = $canAdd;
        $this->view->pageName = "รายการสมาชิก";
        $this->view->pageIcon = "fa fa-newspaper-o";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'รายการสมาชิก')
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
		
		$canView = $sUser->isAllow('orgchart.person.view');

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
        $advancesearch['isWorkgroupId'] = $this->getRequest()->getParam('isWorkgroupId');

        $infoSearchNamespace = new Zend_Session_Namespace("OrgchartPersonSearch");
        $infoSearchNamespace->textsearch = $advancesearch['searchText'];
        $infoSearchNamespace->isenable = $advancesearch['isEnable'];
        $infoSearchNamespace->isWorkgroupId = $advancesearch['isWorkgroupId'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
        $infoSearchNamespace->tblOrderDir = $_order[0]['dir']; 

		if($canView):
			$model = new Orgchart_Model_Person();
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
                $d['GroupName'] = Emwork_Utility::notShowNull($item["GroupName"]);
                $d['IsEnableCode'] = $item["IsEnable"];
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->publishOption[$item["IsEnable"]]);
                $responce['data'][] = $d;
            }
        }
        $this->_helper->json($responce);
    }
    
	public function addAction() {

		// Log Access
		System_Model_Log::access ();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		$canAdd = $sUser->isAllow('orgchart.person.manage');
		
		if (!$canAdd) {
		   $this->_helper->redirector('index', 'person', 'orgchart');
		}
		
		$languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

        $modelOption = new Orgchart_Model_Workgroup();
        $workgroupOption = $modelOption->getOption($defaultLanguage["Code"]);
		
		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
        $this->view->publishOption = $this->publishOption;
        $this->view->workgroupOption = $workgroupOption;
		
		// Set View
		$this->view->pageName = "เพิ่มสมาชิก";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->subMenus = array ();
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url ( array ('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการสมาชิก','url' => $this->view->url ( array ('module' => 'orgchart', 'controller' => 'person', 'action' => 'index'), null, true )),
				array ('name' => 'เพิ่มสมาชิก') 
		);
    }

	public function saveAction() {

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
		$canAdd = $sUser->isAllow('orgchart.person.manage');
		if (!$canAdd) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Orgchart_Model_Person();
		$params = $this->getRequest ()->getParams ();
		
		$fileModel = new System_Model_Fileinfo();
		// Prepare Intro Image
		$introImageID = null;
		if (count($params['hdfIntroImage'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
			if ($fileData->ID != null) {
				$introImageID = $fileData->ID;
			}
		}
		$params['IntroImageID'] = $introImageID;
		
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
 
		$data = $model->save($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("orgchart.person", "save", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json( $result );
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
		$canDelete = $sUser->isAllow('orgchart.person.manage');
		$canEdit = $sUser->isAllow('orgchart.person.manage');
		$canView = $sUser->isAllow('orgchart.person.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'person', 'orgchart');
		}
		
		// Get Param
		$itemID = $this->_request->getParam('itemid');
		
		if (!$itemID) {
			$this->_helper->redirector('index', 'person', 'orgchart');
		}
		
		$model = new Orgchart_Model_Person();
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

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

        $modelOption = new Orgchart_Model_Workgroup();
        $workgroupOption = $modelOption->getOption($defaultLanguage["Code"]);
        
		// Send Rights to View
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;

		// Send Option and lookup To View
		$this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
        $this->view->publishOption = $this->publishOption;
        $this->view->workgroupOption = $workgroupOption;
		
		$this->view->data = $data;
        $this->view->dataDetails = $dataDetails;

		// Set View
		$this->view->pageName = "ปรับปรุงสมาชิก";
		$this->view->pageIcon = "glyphicon glyphicon-th-list";
		$this->view->breadcrumb = array (
				array ('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true )),
				array ('name' => 'รายการสมาชิก', 'url' => $this->view->url ( array ('module' => 'orgchart', 'controller' => 'person', 'action' => 'index'), null, true)),
				array ('name' => 'ปรับปรุงสมาชิก') 
		);
		
		// Log Acion
		System_Model_Log::action("orgchart.person", "view", $dataDetails[$defaultLanguage['Code']]->Title, $itemID);
    }

	public function updateAction() {

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
		$canSave = $sUser->isAllow('orgchart.person.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		$model = new Orgchart_Model_Person();
		$params = $this->getRequest ()->getParams ();
		
		$fileModel = new System_Model_Fileinfo();
		// Prepare Intro Image
		$introImageID = null;
		if (count($params['hdfIntroImage'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
			if ($fileData->ID != null) {
				$introImageID = $fileData->ID;
			}
		}
		$params['IntroImageID'] = $introImageID;
		
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];
		
		$data = $model->update($params);
		
		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			System_Model_Log::action("orgchart.person", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json( $result );
    }

	public function deleteAction() {

		// Init
		$result = array ();
		$result ['result'] = false;
		$result ['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('orgchart.person.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// Update
		$model = new Orgchart_Model_Person();
		$data = $model->delete($params);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$model = new Orgchart_Model_Chart();
			$data = $model->getDataByLanguage($params["id"], $defaultLanguage['Code']);
			System_Model_Log::action("orgchart.person", "delete", $data->Title, $params["id"]);
		} else {
			$result['result'] = false;
			$result['message'] = 'ลบไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
	
	public function ajaxlistAction() {
		
		$workgroupId = $this->getRequest()->getParam("id");
		$lang = $this->getRequest()->getParam("lang");
		
		if (empty($lang)) {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$lang = $defaultLanguage['Code'];
		}
		
		$result = array();
		if (empty($workgroupId)) {
			$this->_helper->json($result);
		}
		
		$model = new Orgchart_Model_Person();
		$data = $model->getOption($lang, $workgroupId);
		
		$i = 0;
		foreach ($data as $key => $value) {
			$result[$i]["id"] = $key;
			$result[$i]["name"] = $value;
			$i ++;
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
		$canSave = $sUser->isAllow('orgchart.person.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Orgchart_Model_Person();
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
		$canSave = $sUser->isAllow('orgchart.person.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Orgchart_Model_Person();
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
		$canSave = $sUser->isAllow('orgchart.person.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Orgchart_Model_Person();
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