<?php

class Sitemap_IndexController extends Zend_Controller_Action
{

	protected $lang;
	protected $statusOption = array("Y" => "ใช้", "N" => "ไม่ใช้");

    public function init()
	{
		// Get Languages
	    $lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
	    if (empty($lang)) {
	        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	        $lang = $defaultLanguage["Code"];
	    }
		$this->lang = $lang;
		
		// Send data to view
		$this->view->lang = $lang;
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
		$canView = $sUser->isAllow('sitemap.item.view');
		$canAdd = $sUser->isAllow('sietmap.item.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		$InfoSearch = new stdClass();
        if (Zend_Session::namespaceIsset("SitemapIndexSearch")) {
        	$infoSearchNamespace = new Zend_Session_Namespace("SitemapIndexSearch");
        	$InfoSearch->textsearch = $infoSearchNamespace->textsearch;
        	$InfoSearch->isenable= $infoSearchNamespace->isenable;
        	$InfoSearch->tblPage = $infoSearchNamespace->tblPage;
        	$InfoSearch->tblRow = $infoSearchNamespace->tblRow;
        	$InfoSearch->tblOrderCol = $infoSearchNamespace->tblOrderCol;
        	$InfoSearch->tblOrderDir = $infoSearchNamespace->tblOrderDir;
        	
        	$this->view->InfoSearch = $InfoSearch;
        	Zend_Session::namespaceUnset("SitemapIndexSearch");
		}
		
		$this->view->canAdd = $canAdd;
		$this->view->statusOption = $this->statusOption;

        $this->view->pageName = "รายการ Sitemap";
        $this->view->pageIcon= "fa fa-sitemap";
	    $this->view->subMenus = array();
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการ Sitemap')
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
		
		$canView = $sUser->isAllow('sitemap.item.view');
		
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
	    $advancesearch['isEnable'] = $this->getRequest()->getParam('isEnable');

		$infoSearchNamespace = new Zend_Session_Namespace("SitemapIndexSearch");
        $infoSearchNamespace->textsearch = $advancesearch['textsearch'];
        $infoSearchNamespace->isenable = $advancesearch['isEnable'];
        $infoSearchNamespace->tblPage = $_start;
        $infoSearchNamespace->tblRow = $_length;
        $infoSearchNamespace->tblOrderCol = $_order[0]['column'];
		$infoSearchNamespace->tblOrderDir = $_order[0]['dir'];
		
		if($canView):
			$model = new Sitemap_Model_Index();
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
				$d['Title'] = Emwork_Utility::notShowNull($item['Title']);
                $d['IsEnable'] = Emwork_Utility::notShowNull($this->statusOption[$item["IsEnable"]]);
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
		$canAdd = $sUser->isAllow('sietmap.item.manage');
		
		if (!$canAdd) {
		   $this->_helper->redirector('index', 'item', 'sietmap');
		}

		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		

		// menugroup_info
		$model = new Sitemap_Model_Index();
		$dataGroupMenu = $model->getMenugroupInfo();

		
        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;
		
		$this->view->dataGroupMenu = $dataGroupMenu;

		$this->view->statusOption = $this->statusOption;

		$this->view->pageName = "เพิ่ม Sitemap";
        $this->view->pageIcon= "fa fa-sitemap";
	    $this->view->subMenus = array();
	    $this->view->breadcrumb = array(
			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'รายการ Sitemap', 'url' => $this->view->url(array('module' => 'sitemap', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'เพิ่ม Sitemap')
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
		$canDelete = $sUser->isAllow('sietmap.item.manage');
		$canEdit = $sUser->isAllow('sietmap.item.manage');
		$canView = $sUser->isAllow('sitemap.item.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'item', 'sitemap');
		}

		$itemid = $this->getRequest()->getParam('itemid');

		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		// menugroup_info
		$model = new Sitemap_Model_Index();

		$data = $model->getData($itemid);
		$detail = $model->getDataByLanguage($data->ID);
		$groupMenu = $model->getDataGroupMenu($data->ID);

		$dataDetails = array();
        if(count($detail)>0) {
            foreach ($detail as $d) {
                $form['Title'] = empty($d['Title']) ? null : $d['Title'];
                $form['Detail'] = empty($d['Detail']) ? null : $d['Detail'];
                $dataDetails[$d['Lang']] = $form;
            }
		}
		$this->view->canDelete = $canDelete;
		$this->view->canEdit = $canEdit;


        $this->view->defaultLanguage = $defaultLanguage['Code'];
        $this->view->defaultLanguageName = $defaultLanguage['Name'];
		$this->view->languages = $languages;

		$this->view->dataGroupMenu = $dataGroupMenu;
		$this->view->data = $data;
		$this->view->dataDetails = $dataDetails;
		$this->view->groupMenu = $groupMenu;

		$this->view->statusOption = $this->statusOption;

		$this->view->pageName = "แก้ไข Sitemap";
        $this->view->pageIcon= "fa fa-sitemap";
	    $this->view->subMenus = array();
	    $this->view->breadcrumb = array(
			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'รายการ Sitemap', 'url' => $this->view->url(array('module' => 'sitemap', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'แก้ไข Sitemap')
	    ); 
		// Log Acion
			System_Model_Log::action("sitemap.index", "view", $dataDetails[$defaultLanguage['Code']]['Title'], $data->id);
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
		$canAdd = $sUser->isAllow('sietmap.item.manage');
		if (!$canAdd) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		$params = $this->getRequest()->getParams();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];

		$model = new Sitemap_Model_Index();
		$data = $model->save($params);

		if($data->result) {
			$result['result'] = true;
            $result['id'] = $data->ID;
			// Log Acion
			System_Model_Log::action("sitemap.index", "save", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
		}

		$this->_helper->json($result);
	}

	function updateAction()
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
		$canSave = $sUser->isAllow('sietmap.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}

		$defaultLanguage = System_Model_Setting::getDefaultLanguage();

		$params = $this->getRequest()->getParams();
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];

		$model = new Sitemap_Model_Index();
		$data = $model->update($params);
		
		if($data->result) {
			$result['result'] = true;
            $result['id'] = $data->ID;
			// Log Acion
			System_Model_Log::action("sitemap.index", "update", $params['params'][$defaultLanguage['Code']]['Title'], $data->id);
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
		$canSave = $sUser->isAllow('sietmap.item.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถลบได้';
		   $this->_helper->json($result);
		}

        // Param
		$params = $this->getRequest()->getParams();
        
        // Update
        $model = new Sitemap_Model_Index();
        $data = $model->delete($params);
        
        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("sitemap.index", "delete", $params['title'], $params["id"]);
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
		$canSave = $sUser->isAllow('sietmap.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Sitemap_Model_Index();
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
		$canSave = $sUser->isAllow('sietmap.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถคัดลอกได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params['defaultLanguage'] = $defaultLanguage['Code'];

    	// Update
    	$model = new Sitemap_Model_Index();
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
		$canSave = $sUser->isAllow('sietmap.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถลบได้';
			$this->_helper->json($result);
		}
    	
    	// Param
        $params = $this->getRequest()->getParams();

    	// Update
    	$model = new Sitemap_Model_Index();
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