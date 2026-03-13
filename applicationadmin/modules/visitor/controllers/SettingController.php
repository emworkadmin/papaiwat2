<?php
class Visitor_SettingController extends Zend_Controller_Action
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
		$canView = $sUser->isAllow('visitor.setting.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
			
	
		// Model
		$model = new System_Model_Setting();
	
		// Get Data
		$data = new stdClass();
		$data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
		$data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
		$data->VisitorSiteModifiedDate = System_Model_Setting::getValue("VisitorSiteModifiedDate", "Visitor");
		$data->VisitorSiteVisitor = System_Model_Setting::getValue("VisitorSiteVisitor", "Visitor");

		$data->VisitorSiteOpenDate = empty($data->VisitorSiteOpenDate) ? null : new DateTime($data->VisitorSiteOpenDate);
		$data->VisitorSiteModifiedDate = empty($data->VisitorSiteModifiedDate) ? null : new DateTime($data->VisitorSiteModifiedDate);

		// Send Data to View
		$this->view->data = $data;
		
		// Set View
		$this->view->pageName = "ตั้งค่าระบบสถิติการเยี่ยมชมเว็บไซต์";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบสถิติการเยี่ยมชมเว็บไซต์')
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
		$canAdd = $sUser->isAllow('visitor.setting.manage');
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
		$params["Visitor"] = array();
		$params["Visitor"]["VisitorSiteOpenDate"] =Emwork_Utility_DateTime::dateThaiToMySQL($requestParams['VisitorSiteOpenDate']);
		$params["Visitor"]["VisitorBeginVisitor"] = $requestParams['VisitorBeginVisitor'];
		$params["Visitor"]["VisitorSiteModifiedDate"] = Emwork_Utility_DateTime::dateThaiToMySQL($requestParams['VisitorSiteModifiedDate']);
		// $params["Visitor"]["VisitorSiteVisitor"] = $requestParams['VisitorSiteVisitor'];

		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("Visitor.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

