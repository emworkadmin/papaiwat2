<?php

class Poll_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'startdate' => 'วันที่เริ่ม', 'enddate' => 'วันที่สิ้นสุด');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
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
		$canView = $sUser->isAllow('poll.setting.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->itemPerPage = System_Model_Setting::getValue("ItemPerPage", "Poll");
		$data->itemSortType = System_Model_Setting::getValue("ItemSortType", "Poll");
		$data->itemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Poll");
		$data->itemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "Poll");
		$data->itemShowImage = System_Model_Setting::getValue("ItemShowImage", "Poll");
		$data->itemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Poll");
		
		// Send Data to View
		$this->view->data = $data;
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าระบบ";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("poll.setting", "view", null, null);
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
		$canAdd = $sUser->isAllow('poll.setting.manage');
		if (!$canAdd) {
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
		$params["Poll"] = array();
		$params["Poll"]["ItemPerPage"] = $requestParams['ItemPerPage'];
		$params["Poll"]["ItemSortType"] = $requestParams['ItemSortType'];
		$params["Poll"]["ItemSortDirection"] = $requestParams['ItemSortDirection'];
		$params["Poll"]["ItemAmountColumn"] = $requestParams['ItemAmountColumn'];
		$params["Poll"]["ItemShowImage"] = $requestParams['ItemShowImage'];
		$params["Poll"]["ItemShowIntroText"] = $requestParams['ItemShowIntroText'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("poll.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

