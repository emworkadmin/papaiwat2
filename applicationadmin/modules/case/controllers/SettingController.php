<?php
class Case_SettingController extends Zend_Controller_Action
{
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
		$canView = $sUser->isAllow('case.setting.manage');
		$canAdd = $sUser->isAllow('case.setting.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}

		// Get Data
		$data = new stdClass();
		$data->CaseCourtCode = System_Model_Setting::getValue("CaseCourtCode", "Case");

		// Get Option
		$model = new Case_Model_Case();
		$items = $model->getCourtCode();

		// Send Data to View
		$this->view->data = $data;
		
		// Set View
		$this->view->CourtCode = Emwork_Utility::addEmptyOption($items);
		$this->view->pageName = "ตั้งค่าระบบตารางนัดพิจารณาคดี";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบตารางนัดพิจารณาคดี')
		);
		
		// Log Acion
		System_Model_Log::action("case.setting", "view", null, null);
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
		$canAdd = $sUser->isAllow('case.setting.manage');
		if (!$canAdd) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		
		// Set Params
		$params = array();
		$params["Case"] = array();
		$params["Case"]["CaseCourtCode"] = $requestParams['CaseCourtCode'];

		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("case.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

