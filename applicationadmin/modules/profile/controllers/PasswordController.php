<?php

class Profile_PasswordController extends Zend_Controller_Action
{

	/**
	 * Page
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
		
// 		// Check Rights
		$authData = $auth->getIdentity();
		if ($authData->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new Profile_Model_Profile();
		
		// Data
		$data = $model->getInfo($authData->UserID);

		// Send Data to view
		$this->view->data = $data;

		// Set View
		$this->view->pageName = "เปลี่ยนรหัสผ่าน";
		$this->view->pageIcon= "fa fa-key";
		$this->view->subMenus = array(
				array('name' => 'ข้อมูลส่วนตัว', 'active' => false, 'url' => $this->view->url(array('module' => 'profile', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'เปลี่ยนรหัสผ่าน', 'active' => true),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ข้อมูลส่วนตัว', 'url' => $this->view->url(array('module' => 'profile', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'เปลี่ยนรหัสผ่าน')
		);
	}

	/**
	 * Ajax action
	 */
	public function saveAction()
	{
		// Init
		$result = array();
		$result['result'] = false;
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

		// Model
		$model = new Profile_Model_Profile();
		
		// Param
		$params = $this->getRequest()->getParams();
		
		// TODO: Check Param
		$data = $model->getInfo($authData->UserID);
		if ($data->Password != md5($params['txtOldPassword'])) {
			$result['message'] = 'รหัสผ่านเดิมไม่ถูกต้อง';
			$this->_helper->json($result);
		}
		
		if ($params['txtNewPassword'] != $params['txtConfirmPassword']) {
			$result['message'] = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน โปรดกรอกให้ตรงกัน';
			$this->_helper->json($result);
		}
		
		// Save & Update
		$data = $model->updatePassword($params);
		
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

