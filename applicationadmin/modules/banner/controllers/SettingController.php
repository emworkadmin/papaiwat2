<?php

class Banner_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม');
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
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		// TODO: Use isAllow
		$canView = $sUser->isAllow("banner.setting.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->itemSortSetting = System_Model_Setting::getValue("ItemSortSetting", "Banner");
		$data->itemSortType = System_Model_Setting::getValue("ItemSortType", "Banner");
		$data->itemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Banner");
		$data->itemBannerSizeSetting = System_Model_Setting::getValue("ItemBannerSizeSetting", "Banner");
		$data->itemBannerSizeHeight = System_Model_Setting::getValue("ItemBannerSizeHeight", "Banner");
		$data->itemBannerSizeWidth = System_Model_Setting::getValue("ItemBannerSizeWidth", "Banner");
		$data->itemShowTitleSetting = System_Model_Setting::getValue("ItemShowTitleSetting", "Banner");
		$data->itemShowTitle = System_Model_Setting::getValue("ItemShowTitle", "Banner");
		$data->itemShowIntroTextSetting = System_Model_Setting::getValue("ItemShowIntroTextSetting", "Banner");
		$data->itemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Banner");
		$data->itemCustomCssSetting = System_Model_Setting::getValue("ItemCustomCssSetting", "Banner");
		$data->itemCustomCss = System_Model_Setting::getValue("ItemCustomCss", "Banner");
				
		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าป้ายโฆษณา";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("banner.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('banner.setting.manage');
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
		$params["Banner"] = array();
		$params["Banner"]["ItemSortSetting"] = $requestParams['ItemSortSetting'];
		$params["Banner"]["ItemSortType"] = $requestParams['ItemSortType'];
		$params["Banner"]["ItemSortDirection"] = $requestParams['ItemSortDirection'];
		$params["Banner"]["ItemBannerSizeSetting"] = $requestParams['ItemBannerSizeSetting'];
		$params["Banner"]["ItemBannerSizeHeight"] = $requestParams['ItemBannerSizeHeight'];
		$params["Banner"]["ItemBannerSizeWidth"] = $requestParams['ItemBannerSizeWidth'];
		$params["Banner"]["ItemShowTitleSetting"] = $requestParams['ItemShowTitleSetting'];
		$params["Banner"]["ItemShowTitle"] = $requestParams['ItemShowTitle'];
		$params["Banner"]["ItemShowIntroTextSetting"] = $requestParams['ItemShowIntroTextSetting'];
		$params["Banner"]["ItemShowIntroText"] = $requestParams['ItemShowIntroText'];
		$params["Banner"]["ItemCustomCssSetting"] = $requestParams['ItemCustomCssSetting'];
		$params["Banner"]["ItemCustomCss"] = $requestParams['ItemCustomCss'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("banner.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

