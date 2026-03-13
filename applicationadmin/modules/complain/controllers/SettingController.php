<?php

class Complain_SettingController extends Zend_Controller_Action
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
        if ($sUser->getInfo()->UserType != 'B') {
            $this->_helper->redirector('index', 'index', 'default');
        }
        // TODO: Use isAllow
        $canView = $sUser->isAllow("complain.setting.manage");
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }
		
        $languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			
		$data = new stdClass();
		$data->ResponseMessageDefault = System_Model_Setting::getValue("ResponseMessageDefault", "Complain");
		$data->ButtonResponseMessageDefault = System_Model_Setting::getValue("ButtonResponseMessageDefault", "Complain");
		foreach($languages as $lang) {
			$data->ResponseMessage[$lang['Code']] = System_Model_Setting::getValue("ResponseMessage" . $lang['Code'], "Complain");
			$data->ButtonResponseMessage[$lang['Code']] = System_Model_Setting::getValue("ButtonResponseMessage" . $lang['Code'], "Complain");
		}
				
		// Send Data to View
		$this->view->data = $data;
		$this->view->languages = $languages;

		// Set View
		$this->view->pageName = "ตั้งค่าร้องเรียน";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array(
			array('name' => 'ตั้งค่าร้องเรียน', 'active' => true, 'url' => $this->view->url(array('module' => 'complain', 'controller' => 'setting', 'action' => 'index'), null, true)),
			array('name' => 'ตั้งค่าอีเมล์', 'active' => false, 'url' => $this->view->url(array('module' => 'complain', 'controller' => 'setting', 'action' => 'settingemail'), null, true)),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าร้องเรียน')
		);

		// Log Acion
		System_Model_Log::action("complain.setting", "view", null, null);
	}

	public function updateAction()
	{	
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
		$canSave = $sUser->isAllow('complain.setting.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
	    
		$requestParams = $this->getRequest()->getParams();
		$languages = System_Model_Setting::getLanguages();

		// Set Params
		$params = array();
		$params["Complain"] = array();


		$params["Complain"]["AdminSubject"] = System_Model_Setting::getValue("AdminSubject", "Complain");
		$params["Complain"]["AdminBody"] = System_Model_Setting::getValue("AdminBody", "Complain");

		$params["Complain"]["UserFromEmail"] = System_Model_Setting::getValue("UserFromEmail", "Complain");
		$params["Complain"]["UserFromName"] = System_Model_Setting::getValue("UserFromName", "Complain");
		$params["Complain"]["UserSendEmail"] = System_Model_Setting::getValue("UserSendEmail", "Complain");
		$params["Complain"]["UserSubject"] = System_Model_Setting::getValue("UserSubject", "Complain");
		$params["Complain"]["UserBody"] = System_Model_Setting::getValue("UserBody", "Complain");		

		$params["Complain"]["ResponseMessageDefault"] = $requestParams['ResponseMessageDefault'];
		$params["Complain"]["ButtonResponseMessageDefault"] = $requestParams['ButtonResponseMessageDefault'];

		foreach($languages as $lang) {
			$params["Complain"]["ResponseMessage{$lang['Code']}"] = $requestParams["ResponseMessage{$lang['Code']}"];
			$params["Complain"]["ButtonResponseMessage{$lang['Code']}"] = $requestParams["ButtonResponseMessage{$lang['Code']}"];
		}

		
		$model = new System_Model_Setting();
		$data = $model->saveParams($params);

		if ($data->result) {
	        $result['result'] = true;
	        $result['message'] = '';
	        $result['id'] = $data->ID;
	        
	        // Log Acion
	        System_Model_Log::action("complain.setting", "update", null, null);
	    } else {
	        $result['result'] = false;
	        $result['message'] = 'บันทึกไม่สำเร็จ';
	    }
	    
	    $this->_helper->json($result);
	}

	function settingemailAction()
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
        $canView = $sUser->isAllow("complain.setting.manage");
        if (!$canView) {
            $this->_helper->redirector('index', 'index', 'default');
        }

		$data = new stdClass();
		$data->AdminSubject = System_Model_Setting::getValue("AdminSubject", "Complain");
		$data->AdminBody = System_Model_Setting::getValue("AdminBody", "Complain");

		$data->UserFromEmail = System_Model_Setting::getValue("UserFromEmail", "Complain");
		$data->UserFromName = System_Model_Setting::getValue("UserFromName", "Complain");
		$data->UserSendEmail = System_Model_Setting::getValue("UserSendEmail", "Complain");
		$data->UserSubject = System_Model_Setting::getValue("UserSubject", "Complain");
		$data->UserBody = System_Model_Setting::getValue("UserBody", "Complain");

		$this->view->data = $data;

		// Set View
		$this->view->pageName = "ตั้งค่าร้องเรียน";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array(
			array('name' => 'ตั้งค่าร้องเรียน', 'active' => false, 'url' => $this->view->url(array('module' => 'complain', 'controller' => 'setting', 'action' => 'index'), null, true)),
			array('name' => 'ตั้งค่าอีเมล์', 'active' => true, 'url' => $this->view->url(array('module' => 'complain', 'controller' => 'setting', 'action' => 'settingemail'), null, true)),
		);
		$this->view->breadcrumb = array(
			array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'ตั้งค่าร้องเรียน')
		);
		// Log Acion
		System_Model_Log::action("complain.settingemail", "view", null, null);
	}

	public function settingemailsaveAction() {

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
		$canSave = $sUser->isAllow('complain.setting.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		$languages = System_Model_Setting::getLanguages();

		// Param
		$requestParams = $this->getRequest()->getParams();
		
		// Set Params
		$params = array();
		$params["Complain"] = array();
		$params["Complain"]['AdminSubject'] = $requestParams['ItemAdminSubject'];
		$params["Complain"]['AdminBody'] = $requestParams['ItemAdminBody'];

		$params["Complain"]['UserFromEmail'] = $requestParams['ItemUserFromEmail'];
		$params["Complain"]['UserFromName'] = $requestParams['ItemUserFromName'];
		$params["Complain"]['UserSendEmail'] = $requestParams['ItemUserSendEmail'];
		$params["Complain"]['UserSubject'] = $requestParams['ItemUserSubject'];
		$params["Complain"]['UserBody'] = $requestParams['ItemUserBody'];

		$params["Complain"]['ResponseMessageDefault'] = System_Model_Setting::getValue("ResponseMessageDefault", "Complain");
		$params["Complain"]['ButtonResponseMessageDefault'] = System_Model_Setting::getValue("ButtonResponseMessageDefault", "Complain");
		foreach($languages as $lang) {
			$params["Complain"]['ResponseMessage'.$lang["Code"]] = System_Model_Setting::getValue("ResponseMessage" . $lang['Code'], "Complain");
			$params["Complain"]['ButtonResponseMessage'.$lang['Code']] = System_Model_Setting::getValue("ButtonResponseMessage" . $lang['Code'], "Complain");
		}
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("complain.settingemail", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}

}

?>