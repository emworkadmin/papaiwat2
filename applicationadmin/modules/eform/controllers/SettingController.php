<?php

class Eform_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่');
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
		$canView = $sUser->isAllow("form.setting.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
	
		// Get Data
		$data = new stdClass();
		$data->eformPerPage = System_Model_Setting::getValue("EformPerPage", "Eform");
		$data->eformSortType = System_Model_Setting::getValue("EformSortType", "Eform");
		$data->eformSortDirection = System_Model_Setting::getValue("EformSortDirection", "Eform");
		$data->eformShowNoDataCategory = System_Model_Setting::getValue("EformShowNoDataCategory", "Eform");
		$data->eformAmountColumn = System_Model_Setting::getValue("EformAmountColumn", "Eform");
		$data->eformShowImage = System_Model_Setting::getValue("EformShowImage", "Eform");
		$data->eformShowCategory = System_Model_Setting::getValue("EformShowCategory", "Eform");
		$data->eformShowIntroText = System_Model_Setting::getValue("EformShowIntroText", "Eform");
		$data->eformShowAmount = System_Model_Setting::getValue("EformShowAmount", "Eform");
		
		$data->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Eform");
		$data->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Eform");
		$data->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Eform");
		$data->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Eform");
		$data->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Eform");

		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าแบบฟอร์ม";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("form.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('form.setting.manage');
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
		$params["Eform"] = array();
		$params["Eform"]["EformPerPage"] = $requestParams['EformPerPage'];
		$params["Eform"]["EformSortType"] = $requestParams['EformSortType'];
		$params["Eform"]["EformSortDirection"] = $requestParams['EformSortDirection'];
		$params["Eform"]["EformShowNoDataCategory"] = $requestParams['EformShowNoDataCategory'];
		$params["Eform"]["EformAmountColumn"] = $requestParams['EformAmountColumn'];
		$params["Eform"]["EformShowImage"] = $requestParams['EformShowImage'];
		$params["Eform"]["EformShowCategory"] = $requestParams['EformShowCategory'];
		$params["Eform"]["EformShowIntroText"] = $requestParams['EformShowIntroText'];
		$params["Eform"]["EformShowAmount"] = $requestParams['EformShowAmount'];
		
		$params["Eform"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["Eform"]["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["Eform"]["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["Eform"]["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["Eform"]["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("eform.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

