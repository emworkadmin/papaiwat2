<?php

class ForgetpasswordController extends Zend_Controller_Action
{
	protected $expireHour = 48;
	protected $lang;

	public function init()
	{ 
		// Get Languages
		$lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
		if (empty($lang)) {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$lang = $defaultLanguage["Code"];
		}
		$this->lang = $lang;
		
		// Send data to view
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
		
		// Send Data to Template
		$this->view->bodyClass = "forgetpassword-body-index";
		
		$menuID = $this->findMenu("/forgetpassword/index");
		Zend_Registry::set("MenuID", $menuID);

		// Rights
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Get Languages
		// $lang = empty($_COOKIE['lang']) ? 'th' : $_COOKIE['lang'];
		$lang = $this->lang;
		
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
		$this->view->lang = $lang;
		$this->view->data = $data;
		
		// Set View
		$this->view->pageName = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_title');
		$this->view->hidePageName = true;
		$this->view->hideBreadcrumb = true;
	}
	
	public function saveAction()
	{
		// Init
		$result = new stdClass();
		
		// Init value
		$result->result = false;
		$result->message = "";
		
		// Get Languages
		// $lang = empty($_COOKIE['lang']) ? 'th' : $_COOKIE['lang'];
		$lang = $this->lang;
	
		if ($this->_request->isPost()) {
			// Get Param
			$paramEmail = $this->_request->getPost('txtEmail');

			$modelProfile = new Profile_Model_Register();
			$userObj = $modelProfile->getInfoByEmail($paramEmail);

			if ($userObj == null) {
				$result->message = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_error_1');
			} else {
			    
				if ($userObj->ActivateCode) {
					$result->message = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_error_2');
				} else {
						
					// สร้าง ActivateCode
					$activateCode = $uniqid . md5($uniqid . strtolower($userObj->Email) . date("Y-m-d H:i:s"));	
					$updateActivateCode = $modelProfile->updateProperty($userObj->ID, array('ActivateCode' => $activateCode, 'Modified' => date("Y-m-d H:i:s"), 'ModifiedBy' => $userObj->ID));
		
					if ($updateActivateCode) {
								

						// จัดส่ง Email เพื่อให้ activate
						if($this->lang == 'th') {
							$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/profile/configs/template_th.ini', 'forgetpassword_activate');
						} else {
							$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/profile/configs/template_en.ini', 'forgetpassword_activate');
						}


						// Mail Config
						$siteUrl = System_Model_Setting::getValue("SiteUrl", "General");
						$smtpHost = System_Model_Setting::getValue("Host", "SMTP");
						$smtpPort = System_Model_Setting::getValue("Port", "SMTP");
						$smtpFromEmail = System_Model_Setting::getValue("FromEmail", "SMTP");
						$smtpFromName = System_Model_Setting::getValue("FromName", "SMTP");
						$smtpIsSsl = System_Model_Setting::getValue("IsSsl", "SMTP");
						$smtpSslType = System_Model_Setting::getValue("SslType", "SMTP");
						$smtpIsAuth = System_Model_Setting::getValue("IsAuth", "SMTP");
						$smtpAuthType = System_Model_Setting::getValue("AuthType", "SMTP");
						$smtpAuthUsername = System_Model_Setting::getValue("AuthUsername", "SMTP");
						$smtpAuthPassword = System_Model_Setting::getValue("AuthPassword", "SMTP");		


						if(substr($siteUrl, -1) == '/') {
							$url = $siteUrl . 'forgetpassword/activate/confirmcode/' . $activateCode;
						} else {
							$url = $siteUrl . '/forgetpassword/activate/confirmcode/' . $activateCode;
						}
									
						// send mail
						$config = array(
							'port' => $smtpPort
						);
						if ($smtpIsAuth == 'Y') {
							$config["auth"] = $smtpAuthType;
							$config["username"] = $smtpAuthUsername;
							$config["password"] = $smtpAuthPassword;
						}
						if ($smtpIsSsl == 'Y') {
							$config["ssl"] = $smtpSslType;
						}

						$fromName = $smtpFromName;
						$fromEmail = $smtpFromName;
						$toEmail = $userObj->Email;
						$subject = $configTemplate->subject;
						$body = $configTemplate->body;
						$body = str_replace("{url}", $url, $body);

						$transport = new Zend_Mail_Transport_Smtp($smtpHost, $config);
						
						// เตรียมข้อมูลในการส่งอีเมล์
						$mail = new Zend_Mail_InlineImages("UTF-8");
						$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
						$mail->setBodyHtml($body ,"UTF-8")
						->setFrom($fromEmail, $fromName)
						->addTo($toEmail)
						->setSubject($subject);
						
						// ทำการส่งอีเมล์
						$mail->send($transport);
							
						$result->message = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_error_3');
					} else {
						$result->message = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_error_4');
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

		$menuID = $this->findMenu("/banner/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		// Log Access
		System_Model_Log::access();
		
		// Get Languages
		// $lang = empty($_COOKIE['lang']) ? 'th' : $_COOKIE['lang'];
		$lang = $this->lang;
		
		$activateCode = $this->getRequest()->getParam("confirmcode", "");
	
		$modelProfile = new Profile_Model_Register();
		
		$userObj = $modelProfile->getInfoActivateCode($activateCode);

		$pageName = "";
		$renderName = "activatesuccess";

		$data = new stdClass();
		$data->errorMessage = '';
		$data->successMessage = '';
		$data->code = '';
		
		// Display
		if (empty($userObj)) {
			// แจ้งว่าไม่พบการยืนยัน
			$pageName = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_title_2');
			$renderName = "activatefail";
		} else {
			// แสดงแบบฟอร์มให้กรอกรหัสผ่านใหม่
			$pageName = "ตั้งรหัสผ่านใหม่";
			$data->code = $activateCode;
				
			if ($this->_request->isPost()) { // Reset Password
				$paramPassword = $this->getRequest()->getParam("txtPassword", "");
				$paramConfirmPassword = $this->getRequest()->getParam("txtConfirmPassword", "");
				$paramCode = $this->getRequest()->getParam("hdfCode", "");
				
				$check = true;
				if(strlen($paramPassword)<8){
					$check = false;
					$data->errorMessage = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_error_5');
				}
				if ($paramPassword != $paramConfirmPassword) {
					$check = false;
					$data->errorMessage = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_error_6');
				}
				// Check Params
				if ($check) {
			
					$userPostObj = $modelProfile->getInfoActivateCode($paramCode);
			
				    if (empty($userPostObj)) {
						$data->errorMessage = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_error_7');
					} else {
							
						// Set New Password
						$modelProfile->updateProperty($userObj->ID, array('Password' => md5($paramPassword), 'ActivateCode' => null, 'Modified' => date("Y-m-d H:i:s"), 'ModifiedBy' => $userObj->id, "IsEnable" => "Y", "AttemptAmount" => 0));
						$pageName = Emwork_Translate::text($this->lang, 'auth', 'forgetpassword_title_3');
						$renderName = "activatefinish";
					}
				}
			} 
		}

		// Set Data to View
		$this->view->lang = $lang;
		$this->view->data = $data;
		
		$this->view->pageName = $pageName;
		$this->view->hideBreadcrumb = true;
		$this->render($renderName);
	}
}

