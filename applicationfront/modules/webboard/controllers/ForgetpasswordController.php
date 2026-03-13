<?php

class Webboard_ForgetpasswordController extends Zend_Controller_Action
{
	protected $expireHour = 48;
	protected $lang = null;
	
	public function init()
	{ 
		// Get Languages
		$lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
		if (empty($lang)) {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$lang = $defaultLanguage["Code"];
		}
		$this->lang = $lang;
		$this->view->lang = $lang;

	}
	
	protected function findMenu($url)
	{
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
	}
	
	public function indexAction()
	{		
		// Rights
		// $auth = Zend_Auth::getInstance();
		// if ($auth->hasIdentity()) {
		// 	$this->_helper->redirector('index', 'index', 'default');
		// }
		
		$data = new stdClass();
		$data->errorMessage = '';
		$data->successMessage = '';
		$data->paramEmail = '';
		
		if ($this->_request->isPost()) {
			// Nothing ...
		} else {
			// Log Access
			System_Model_Log::access();
		}

		// Set Data to View
		$this->view->data = $data;
		
		// Set View
		$this->view->hidePageName = true;
		$this->view->hideBreadcrumb = true;
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function saveAction()
	{
		// Init
		$result = new stdClass();
		
		// Init value
		$result->result = false;
		$result->message = "";
	
		if ($this->_request->isPost()) {
			// Get Param
			$paramEmail = $this->_request->getPost('txtEmail');

			$userObj = Webboard_Model_Users::getInfoByEmail($paramEmail);
			// print_r($userObj );exit;
			if ($userObj == null) {
				$result->message = Emwork_Translate::text($this->lang, 'webboard', 'forgetpassword_validate_email');
			} else {
				if ($userObj->ActivateCode) {
					$result->message = Emwork_Translate::text($this->lang, 'webboard', 'forgetpassword_validate_activecode');
				} else {
					// สร้าง ActivateCode
					$activateCode = $uniqid . md5($uniqid . strtolower($userObj->Email) . date("Y-m-d H:i:s"));	
					$updateActivateCode = Webboard_Model_Users::updateProperty($userObj->ID, array('ActivateCode' => $activateCode, 'Modified' => date("Y-m-d H:i:s"), 'ModifiedBy' => $userObj->ID));

					if ($updateActivateCode) {
						// จัดส่ง Email เพื่อให้ activate
							
						$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/webboard/configs/template.'.$this->lang.'.ini', 'forgetpassword_activate');
						
						$settingSiteUrl = System_Model_Setting::getValue('SiteUrl', 'General');
						$settingFromName = System_Model_Setting::getValue('FromName', 'SMTP');
						$settingFromEmail = System_Model_Setting::getValue('FromEmail', 'SMTP');
						$settingPort = System_Model_Setting::getValue('Port', 'SMTP');
						$settingIsAuth = System_Model_Setting::getValue('IsAuth', 'SMTP');
						$settingAuthType = System_Model_Setting::getValue('AuthType', 'SMTP');
						$settingAuthUsername = System_Model_Setting::getValue('AuthUsername', 'SMTP');
						$settingAuthPassword = System_Model_Setting::getValue('AuthPassword', 'SMTP');
						$settingIsSsl = System_Model_Setting::getValue('IsSsl', 'SMTP');
						$settingSslType = System_Model_Setting::getValue('SslType', 'SMTP');
						$settingHost = System_Model_Setting::getValue('Host', 'SMTP');
						
						$url = $settingSiteUrl . '/'.$this->lang.'/webboard/forgetpassword/activate/confirmcode/' . $activateCode;
						// $headerImageUrl =  $settingSiteUrl . '/images/other/mail-header.jpg';
						// $footerImageUrl =  $settingSiteUrl . '/images/other/mail-footer.jpg';
						
						$fromName = $settingFromName;
						$fromEmail = $settingFromEmail;
						$toEmail = $userObj->Email;
						$subject = $configTemplate->subject;
						$body = $configTemplate->body;
						$body = str_replace("{fullname}", $userObj->FirstName.' '.$userObj->LastName, $body);
						$body = str_replace("{userid}", $userObj->Username, $body);
						$body = str_replace("{url}", $url, $body);
						// $body = str_replace("{header_image_url}", $headerImageUrl, $body);
						// $body = str_replace("{footer_image_url}", $footerImageUrl, $body);
						$priority = $configTemplate->priority;
						
						// send mail
						$config = array(
								'port' => $settingPort
						);
						if ($settingIsAuth == 'Y') {
							$config["auth"] = $settingAuthType;
							$config["username"] = $settingAuthUsername;
							$config["password"] = $settingAuthPassword;
						}
						if ($settingIsSsl == 'Y') {
							$config["ssl"] = $settingSslType;
						}
						
						$transport = new Zend_Mail_Transport_Smtp($settingHost, $config);
						
						// เตรียมข้อมูลในการส่งอีเมล์
						$mail = new Zend_Mail_InlineImages("UTF-8");
						$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
						$mail->setBodyHtml($body ,"UTF-8")
						->setFrom($fromEmail, $fromName)
						->addTo($toEmail)
						->setSubject($subject);
						
						// ทำการส่งอีเมล์
						$mail->send($transport);
							
						$result->message = Emwork_Translate::text($this->lang, 'webboard', 'forgetpassword_confirm_1');
					} else {
						$result->message = Emwork_Translate::text($this->lang, 'webboard', 'forgetpassword_validate_error');
					}
				}
			}
		} else {
			// not post
		}
	
		// Return value
		$this->_helper->json($result);
	}

	/**
	 * Page :
	 */
	public function activateAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$activateCode = $this->getRequest()->getParam("confirmcode", "");
	
		$userObj = Webboard_Model_Users::getInfoActivateCode($activateCode);

		$pageName = "";
		$renderName = "activatesuccess";
		
		$data = new stdClass();
		$data->errorMessage = '';
		$data->successMessage = '';
		$data->code = '';
		
		// Display
		if (empty($userObj)) {
			// แจ้งว่าไม่พบการยืนยัน
			$renderName = "activatefail";
		} else {
			// แสดงแบบฟอร์มให้กรอกรหัสผ่านใหม่
			$data->code = $activateCode;
			if ($this->_request->isPost()) { // Reset Password
				$paramPassword = $this->getRequest()->getParam("txtPassword", "");
				$paramConfirmPassword = $this->getRequest()->getParam("txtConfirmPassword", "");
				$paramCode = $this->getRequest()->getParam("hdfCode", "");
				
				$check = true;
				if(strlen($paramPassword)<8){
					$check = false;
					$data->errorMessage = Emwork_Translate::text($this->lang, 'webboard', 'forgetpassword_validate_active_password');
				}
				if ($paramPassword != $paramConfirmPassword) {
					$check = false;
					$data->errorMessage = Emwork_Translate::text($this->lang, 'webboard', 'forgetpassword_validate_active_password_2');
				}
				// Check Params
				if ($check) {
			
					$userPostObj = Webboard_Model_Users::getInfoActivateCode($paramCode);
					
				    if (empty($userPostObj)) {
						$data->errorMessage = Emwork_Translate::text($this->lang, 'webboard', 'forgetpassword_default_error');
					} else {
							
						// Set New Password
					    Webboard_Model_Users::updateProperty($userObj->ID, array('Password' => md5($paramPassword), 'ActivateCode' => null, 'Modified' => date("Y-m-d H:i:s"), 'ModifiedBy' => $userObj->ID, "IsEnable" => "Y", "AttemptAmount" => 0));

						$renderName = "activatefinish";
					}
				}
			} 
		}

		// Set Data to View
		$this->view->data = $data;
		
		$this->view->hideBreadcrumb = true;
		$this->render($renderName);
	}
}

