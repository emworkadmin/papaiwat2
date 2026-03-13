<?php

class License_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('title' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'modified' => 'วันที่ปรับปรุง', 'publishdate' => 'วันที่เผยแพร่', 'ordering' => 'ตามลำดับ', 'startdate' => 'วันที่เปิดรับฟังความติดเห็น');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');

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
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		// TODO: Use isAllow
		$canView = $sUser->isAllow("license.setting.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
	
		// Get Data
		$data = new stdClass();
		$data->CategoryColumn = System_Model_Setting::getValue("CategoryColumn", "License");
		$data->CategoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "License");
		$data->CategorySortType = System_Model_Setting::getValue("CategorySortType", "License");
		$data->CategorySortDirection = System_Model_Setting::getValue("CategorySortDirection", "License");
		
		$data->LicensePerPage = System_Model_Setting::getValue("LicensePerPage", "License");
		$data->LicenseSortType = System_Model_Setting::getValue("LicenseSortType", "License");
		$data->LicenseSortDirection = System_Model_Setting::getValue("LicenseSortDirection", "License");
		
		$data->ProcedurePerPage = System_Model_Setting::getValue("ProcedurePerPage", "License");
		$data->ProcedureSortType = System_Model_Setting::getValue("ProcedureSortType", "License");
		$data->ProcedureSortDirection = System_Model_Setting::getValue("ProcedureSortDirection", "License");
		
		$data->SearchPerPage = System_Model_Setting::getValue("SearchPerPage", "License");
		
		$data->CommentUrl = System_Model_Setting::getValue("CommentUrl", "License");

		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->categorySortTypeOption = $this->sortTypeOption;
		unset($this->view->categorySortTypeOption['startdate']);
		unset($this->view->categorySortTypeOption['publishdate']);
		$this->view->licenseSortTypeOption = $this->sortTypeOption;
		unset($this->view->licenseSortTypeOption['startdate']);
		unset($this->view->licenseSortTypeOption['ordering']);
		$this->view->procedureSortTypeOption = $this->sortTypeOption;
		unset($this->view->procedureSortTypeOption['ordering']);
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่า";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่า')
		);
		
		// Log Acion
		System_Model_Log::action("license.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('license.setting.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		
		// Check Param
		
		// Set Params
		$params = array();
		$params["License"] = array();
		$params["License"]["CategoryColumn"] = $requestParams['CategoryColumn'];
		$params["License"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["License"]["CategorySortType"] = $requestParams['CategorySortType'];
		$params["License"]["CategorySortDirection"] = $requestParams['CategorySortDirection'];
		$params["License"]["LicensePerPage"] = $requestParams['LicensePerPage'];
		$params["License"]["LicenseSortType"] = $requestParams['LicenseSortType'];
		$params["License"]["LicenseSortDirection"] = $requestParams['LicenseSortDirection'];
		
		$params["License"]["ProcedurePerPage"] = $requestParams['ProcedurePerPage'];
		$params["License"]["ProcedureSortType"] = $requestParams['ProcedureSortType'];
		$params["License"]["ProcedureSortDirection"] = $requestParams['ProcedureSortDirection'];
		
		$params["License"]["SearchPerPage"] = $requestParams['SearchPerPage'];
		
		$params["License"]["CommentUrl"] = $requestParams['CommentUrl'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("license.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

