<?php

class System_ManageController extends Zend_Controller_Action
{
	protected $smtpAuthTypeOption = array('plain' => 'Plain', 'login' => 'Login');
	protected $smtpSslTypeOption = array('ssl' => 'SSL', 'tls' => 'TLS');
	
	public function init()
	{ 
		
	}
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Check Auth
		$auth = Zend_Auth::getInstance();
		if (!$auth->hasIdentity()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Rights
		$authData = $auth->getIdentity();
		if ($authData->user_type != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$rights = $this->view->rights($authData->UsergroupID);
		$canView = $rights->isAllow('setting_manage');
		
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = $model->getInfo();
		$data->languages = array();
		$dataLanguages = System_Model_Setting::getLanguages();
		if (count($dataLanguages) > 0) {
			foreach ($dataLanguages as $lang) {
				$data->languages[] = $lang['id'];
			}
		}
		
		$languageOption = array();
		$languageList = $model->getLanguageList();
		if (count($languageList) > 0) {
			foreach ($languageList as $language) {
				$languageOption[$language['id']] = $language['name'];
			}
		}
		
		// Send Data to View
		$this->view->data = $data;
		$this->view->smtpAuthTypeOption = $this->smtpAuthTypeOption;
		$this->view->smtpSslTypeOption = $this->smtpSslTypeOption;
		$this->view->languageOption = $languageOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าระบบ";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
	}
	
	public function saveAction() {

		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		// Check Auth
		$auth = Zend_Auth::getInstance();
		if (!$auth->hasIdentity()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Rights
		$authData = $auth->getIdentity();
		if ($authData->user_type != 'B') {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Check Action Rights
		$rights = $this->view->rights($authData->UsergroupID);
		$canSave = $rights->isAllow('setting_manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		
		// Param
		$params = $this->getRequest()->getParams();
		// Check Param
				
		// Model 
		$model = new System_Model_Setting();
		$data = $model->save($params);
		
		// Set Result
		if ($data->result) {
				
			$result['result'] = true;
			$result['message'] = '';
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

