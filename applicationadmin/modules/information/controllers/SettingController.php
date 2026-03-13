<?php
class Information_SettingController extends Zend_Controller_Action
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
		$canView = $sUser->isAllow("information.setting.manage");
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
	
		// Model
		$model = new System_Model_Setting();
	
		// Get Data
		$data = new stdClass();
		$data->InformationPerPage = System_Model_Setting::getValue("InformationPerPage", "Information");
		$data->InformationSortType = System_Model_Setting::getValue("InformationSortType", "Information");
		$data->InformationSortDirection = System_Model_Setting::getValue("InformationSortDirection", "Information");
		$data->InformationAmountColumn = System_Model_Setting::getValue("InformationAmountColumn", "Information");
		$data->InformationShowAmount = System_Model_Setting::getValue("InformationShowAmount", "Information");
		$data->InformationShowDetail = System_Model_Setting::getValue("InformationShowDetail", "Information");
		
		$data->DepartmentShowCategory = System_Model_Setting::getValue("DepartmentShowCategory", "Information");
		$data->DepartmentShowJurisdiction = System_Model_Setting::getValue("DepartmentShowJurisdiction", "Information");
		$data->DepartmentShowAddress = System_Model_Setting::getValue("DepartmentShowAddress", "Information");
		$data->DepartmentShowDetail = System_Model_Setting::getValue("DepartmentShowDetail", "Information");
		$data->DepartmentShowTel = System_Model_Setting::getValue("DepartmentShowTel", "Information");
		$data->DepartmentShowFax = System_Model_Setting::getValue("DepartmentShowFax", "Information");
		$data->DepartmentShowEmail = System_Model_Setting::getValue("DepartmentShowEmail", "Information");
		$data->DepartmentShowWebsite = System_Model_Setting::getValue("DepartmentShowWebsite", "Information");
		$data->DepartmentShowDocuments = System_Model_Setting::getValue("DepartmentShowDocuments", "Information");
		$data->DepartmentShowMap = System_Model_Setting::getValue("DepartmentShowMap", "Information");
		$data->DepartmentShowImage = System_Model_Setting::getValue("DepartmentShowImage", "Information");

		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าระบบจัดการข้อมูลหน่วยงาน";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("information.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('information.setting.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}

		// Model 
		$model = new System_Model_Setting();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		
		// Set Params
		$params = array();
		$params["Information"] = array();
		$params["Information"]["InformationPerPage"] = $requestParams['InformationPerPage'];
		$params["Information"]["InformationSortType"] = $requestParams['InformationSortType'];
		$params["Information"]["InformationSortDirection"] = $requestParams['InformationSortDirection'];
		$params["Information"]["InformationAmountColumn"] = $requestParams['InformationAmountColumn'];
		$params["Information"]["InformationShowImage"] = $requestParams['InformationShowImage'];
		$params["Information"]["InformationShowDetail"] = $requestParams['InformationShowDetail'];

		$params["Information"]["DepartmentShowCategory"] = $requestParams['DepartmentShowCategory'];
		$params["Information"]["DepartmentShowJurisdiction"] = $requestParams['DepartmentShowJurisdiction'];
		$params["Information"]["DepartmentShowAddress"] = $requestParams['DepartmentShowAddress'];
		$params["Information"]["DepartmentShowDetail"] = $requestParams['DepartmentShowDetail'];
		$params["Information"]["DepartmentShowTel"] = $requestParams['DepartmentShowTel'];
		$params["Information"]["DepartmentShowFax"] = $requestParams['DepartmentShowFax'];
		$params["Information"]["DepartmentShowEmail"] = $requestParams['DepartmentShowEmail'];
		$params["Information"]["DepartmentShowWebsite"] = $requestParams['DepartmentShowWebsite'];
		$params["Information"]["DepartmentShowDocuments"] = $requestParams['DepartmentShowDocuments'];
		$params["Information"]["DepartmentShowMap"] = $requestParams['DepartmentShowMap'];
		$params["Information"]["DepartmentShowImage"] = $requestParams['DepartmentShowImage'];

		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("information.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

