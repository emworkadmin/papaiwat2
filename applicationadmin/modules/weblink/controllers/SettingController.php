<?php

class Weblink_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
	protected $showTypeOption = array ('cards' => 'แบบคอลัมน์เว็บลิงค์', 'lists' => 'แบบรายการหมวดหมู่');

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
		$canView = $sUser->isAllow('weblink.setting.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->itemShowType = System_Model_Setting::getValue("ItemShowType", "Weblink");
		$data->itemSortType = System_Model_Setting::getValue("ItemSortType", "Weblink");
		$data->itemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Weblink");
		$data->itemWeblinkSizeHeight = System_Model_Setting::getValue("ItemWeblinkSizeHeight", "Weblink");
		$data->itemWeblinkSizeWidth = System_Model_Setting::getValue("ItemWeblinkSizeWidth", "Weblink");
		$data->itemShowTitle = System_Model_Setting::getValue("ItemShowTitle", "Weblink");
		$data->itemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Weblink");
		$data->itemShowButton = System_Model_Setting::getValue("ItemShowButton", "Weblink");
		$data->itemCustomCss = System_Model_Setting::getValue("ItemCustomCss", "Weblink");

		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->showTypeOption = $this->showTypeOption;

		// Set View
		$this->view->pageName = "ตั้งค่าเว็บลิงค์";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("weblink.setting", "view", null, null);
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
		$canAdd = $sUser->isAllow('weblink.setting.manage');
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
		$params["Weblink"] = array();
		$params["Weblink"]["ItemShowType"] = $requestParams['ItemShowType'];
		$params["Weblink"]["ItemSortType"] = $requestParams['ItemSortType'];
		$params["Weblink"]["ItemSortDirection"] = $requestParams['ItemSortDirection'];
		$params["Weblink"]["ItemWeblinkSizeHeight"] = $requestParams['ItemWeblinkSizeHeight'];
		$params["Weblink"]["ItemWeblinkSizeWidth"] = $requestParams['ItemWeblinkSizeWidth'];
		$params["Weblink"]["ItemShowTitle"] = $requestParams['ItemShowTitle'];
		$params["Weblink"]["ItemShowIntroText"] = $requestParams['ItemShowIntroText'];
		$params["Weblink"]["ItemShowButton"] = $requestParams['ItemShowButton'];
		$params["Weblink"]["ItemCustomCss"] = $requestParams['ItemCustomCss'];

		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("weblink.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

