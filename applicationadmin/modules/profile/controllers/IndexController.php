<?php

class Profile_IndexController extends Zend_Controller_Action
{
	
	protected $genderOption = array('M' => 'ชาย', 'F' => 'หญิง');
	
	/**
	 * Index: 
	 */
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
		if ($authData->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new Profile_Model_Profile();
	
		// Data
		$data = $model->getInfo($authData->UserID);
	
		$this->view->data = $data;
		$this->view->genderOption = $this->genderOption;
		
		// Set View
		$this->view->pageName = "ข้อมูลส่วนตัว";
		$this->view->pageIcon= "fa fa-user";
		$this->view->subMenus = array(
				array('name' => 'ข้อมูลส่วนตัว', 'active' => true),
				array('name' => 'เปลี่ยนรหัสผ่าน', 'active' => false, 'url' => $this->view->url(array('module' => 'profile', 'controller' => 'password', 'action' => 'index'), null, true)),
			
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ข้อมูลส่วนตัว')
		);
	}

	
	public function updateAction()
	{
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
		if ($authData->UserType != 'B') {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
	
		// Param
		$params = $this->getRequest()->getParams();
		
		// Save & Update
		$model = new Profile_Model_Profile();
		$data = $model->update($params);
	
		// Set Result
		if ($data->result) {	
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
	
		$this->_helper->json($result);
	}
	/**
	 * Ajax action
	 */
	public function saveAction()
	{
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
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// TODO: Check Param
		
		// Save & Update
		$model = new Profile_Model_Profile();
		$data = $model->updateGeneral($params);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
		} else {
			$result['result'] = false;
			$result['message'] = $data->message;
		}

		$this->_helper->json($result);	
	}
}

