<?php

class Complain_IndexController extends Zend_Controller_Action
{

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
		
		$model = new Complain_Model_Index();
		$dataCaptcha = $model->getReCaptchakey();
		
		$fileModel = new System_Model_Fileinfo();
		
		$errorMessage = "";
			
		if ($this->_request->isPost()) {
			
			$filter = new Zend_Filter_StripTags();
			
			$params = array();
			$params['FullName'] = $filter->filter($this->_request->getPost('Fullname', ''));
			$params['Email'] = $filter->filter($this->_request->getPost('Email', ''));
			$params['Telephone'] = $filter->filter($this->_request->getPost('Telephone', ''));
			$params['Topic'] = $filter->filter($this->_request->getPost('Topic', ''));
			$params['Detail'] = $filter->filter($this->_request->getPost('Detail', ''));
			$params['hdfIntroImage'] = $this->_request->getPost('hdfIntroImage', '');
			$params['Lang'] = $this->lang;
			$params['recaptchaResponse'] = $filter->filter($this->_request->getPost('g-recaptcha-response', ''));

			// Check ReCaptcha
			$recaptchaSecretKey = $dataCaptcha['ReCaptchaSecretKey'];
			$recaptchaSiteVerify = 'https://www.google.com/recaptcha/api/siteverify';		
			$recaptchaReq = array();
			$recaptchaReq["secret"] = $recaptchaSecretKey;
			$recaptchaReq["response"] = $params["recaptchaResponse"];
			$recaptchaRes = Emwork_Utility::callAPI("POST", $recaptchaSiteVerify, $recaptchaReq);
			$recaptchaResArray = json_decode($recaptchaRes, true);
				
			// Check Param
			if (empty($params['FullName'])) {
				$errorMessage = Emwork_Translate::text($this->lang, 'complain', 'validate_title');
			} else if (empty($params['Email'])) {
				$errorMessage = Emwork_Translate::text($this->lang, 'complain', 'validate_email');
			} else if (empty($params['Telephone'])) {
				$errorMessage = Emwork_Translate::text($this->lang, 'complain', 'validate_telephone');
			} else if (empty($params['Topic'])) {
				$errorMessage = Emwork_Translate::text($this->lang, 'complain', 'validate_topic');
			} else if (empty($params['Detail'])) {
				$errorMessage = Emwork_Translate::text($this->lang, 'complain', 'validate_detail');
			} else if (!$recaptchaResArray["success"] && $recaptchaReq["response"]) {
				$errorMessage = Emwork_Translate::text($this->lang, 'complain', 'validate_recaptcha');
			} else {
				// Prepare ImageIntro
				$introImageID = null;
				if (count($params['hdfIntroImage'])) {
					unset($fileData);
					$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
					if ($fileData->ID != null) {
						$introImageID = $fileData->ID;
					}
				}
				$params['IntroImageID'] = $introImageID;
				
				// Save
				$data = $model->save($params);
				
				// Check Result
				if($data->result) {
					$this->sendMail($params);
					$this->_helper->redirector('finish', 'index', 'complain');
				} else {
					$errorMessage = Emwork_Translate::text($this->lang, 'complain', 'validate_error');
				}
			}
			
			// Non Data
			$data = new stdClass();
			if ($errorMessage != "") {
				$data->Fullname = $params['FullName'];
				$data->Email = $params['Email'];
				$data->Telephone = $params['Telephone'];
				$data->Topic = $params['Topic'];
				$data->Detail = $params['Detail'];

				if (count($params['hdfIntroImage'])) {
					$images = array();
					unset($fileData);
					$fileData = $fileModel->itemByHashName($params['hdfIntroImage'][0]);
					if ($fileData) {
						$images[] = array(
							"file_name" => $fileData->Name,
							"size" => $fileData->FileSize,
							"type" => $fileData->FileType,
							"hash_name" => $fileData->HashName
						);
					}
				}
				$data->IntroImage = $images;				
			}

			// Send Data to view
			$this->view->data = $data;
		}
		// Log Access
		System_Model_Log::access();
		
		$this->view->dataCaptcha = $dataCaptcha;
		
		// Send Data to view
		$this->view->errorMessage = $errorMessage;
		
		$menuID = $this->findMenu("/complain/index/index");
		Zend_Registry::set("MenuID", $menuID);
    }
	
	public function finishAction()
	{
		$ResponseMessageDefault = System_Model_Setting::getValue("ResponseMessageDefault", "Complain");
		$ButtonResponseMessageDefault = System_Model_Setting::getValue("ButtonResponseMessageDefault", "Complain");

		$ResponseMessage = array();
		$ButtonResponseMessage = array();
		$languages = System_Model_Setting::getLanguages();
		foreach($languages as $lang) {
			$ResponseMessage[$lang['Code']] = System_Model_Setting::getValue("ResponseMessage" . $lang['Code'], "Complain");
			$ButtonResponseMessage[$lang['Code']] = System_Model_Setting::getValue("ButtonResponseMessage" . $lang['Code'], "Complain");
		}

		$Message = empty($ResponseMessage[$this->lang]) ? $ResponseMessageDefault : $ResponseMessage[$this->lang];
		$Button = empty($ButtonResponseMessage[$this->lang]) ? $ButtonResponseMessageDefault : $ButtonResponseMessage[$this->lang];

		$this->view->responeMessage = $Message;
		$this->view->responeButton = $Button;

		$menuID = $this->findMenu("/complain/index/index");
		Zend_Registry::set("MenuID", $menuID);
	}

	private function sendmail($params)
	{

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

		// Config Mail
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
			
		$transport = new Zend_Mail_Transport_Smtp($smtpHost, $config);

		$model = new Complain_Model_Index();
		$getAdmin = $model->getAdmin();

 		// ชื่อเรื่อง
		$AdminSubject = System_Model_Setting::getValue("AdminSubject", "Complain");
		$AdminSubject = $this->replace_deep_tag($AdminSubject, $params);

 		// ข้อความตอบกลับ
		$AdminBody = System_Model_Setting::getValue("AdminBody", "Complain");
		$AdminBody = $this->replace_deep_tag($AdminBody, $params);
		
		if(count($getAdmin)>0) {
			foreach($getAdmin as $k => $v) {

				// เตรียมข้อมูลในการส่งอีเมล์
				$mail = new Zend_Mail_InlineImages("UTF-8");
				$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
				$mail->setBodyHtml(nl2br($AdminBody) ,"UTF-8")
				->setFrom($smtpFromEmail, $smtpFromName)
				->addTo($v['Email'])
				->setSubject($AdminSubject);
				
				// ทำการส่งอีเมล์
				$mail->send($transport);
			}
		}

		/* User */
		$UserFromEmail = System_Model_Setting::getValue("UserFromEmail", "Complain");
		$UserFromEmail = $this->replace_deep_tag($UserFromEmail, $params);
		$UserFromEmail = empty($UserFromEmail) ? $smtpFromEmail : $UserFromEmail;

		$UserFromName = System_Model_Setting::getValue("UserFromName", "Complain");
		$UserFromName = $this->replace_deep_tag($UserFromName, $params);
		$UserFromName = empty($UserFromName) ? $smtpFromName : $UserFromName;

		$UserSendEmail = System_Model_Setting::getValue("UserSendEmail", "Complain");
		if($UserSendEmail) {
			$UserSendEmail = $this->replace_deep_tag($UserSendEmail, $params);

			$UserSubject = System_Model_Setting::getValue("UserSubject", "Complain");
			$UserSubject = $this->replace_deep_tag($UserSubject, $params);
	
			$UserBody = System_Model_Setting::getValue("UserBody", "Complain");
			$UserBody = $this->replace_deep_tag($UserBody, $params);
	
			// เตรียมข้อมูลในการส่งอีเมล์
			$mail = new Zend_Mail_InlineImages("UTF-8");
			$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
			$mail->setBodyHtml(nl2br($UserBody) ,"UTF-8")
			->setFrom($UserFromEmail, $UserFromName)
			->addTo($UserSendEmail)
			->setSubject($UserSubject);
			
			// ทำการส่งอีเมล์
			$mail->send($transport);
		}

	}

	function replace_deep_tag($body, $params)
	{
		if(strpos($body, "{")>=0 && strpos($body, "}")>strpos($body, "{")) {
			$open = strpos($body, "{");
			$replace_search = substr($body, $open);
			$close = strpos($replace_search, "}");
			$replace_search = substr($replace_search, 0, $close + 1);
			$replace_result = Emwork_Utility::notShowNull($params[substr($replace_search , 1, -1)]);
			$result_Body = str_replace($replace_search, $replace_result, $body);
			return $this->replace_deep_tag($result_Body, $params);
		} else {
			return $body;
		}
	}

}

?>