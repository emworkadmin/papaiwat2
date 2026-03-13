<?php 


class Faq_QuestionController extends Zend_Controller_Action
{
	protected $lang;
	protected $mainCategoryID = '1';

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
	
	public function indexAction()
	{
		$errorMessage = "";
		if ($this->_request->isPost()) {

			$filter = new Zend_Filter_StripTags();
			$_Fullname = $filter->filter($this->_request->getPost('Fullname', ''));
			$_Email = $filter->filter($this->_request->getPost('Email', ''));
			$_Question = $filter->filter($this->_request->getPost('Question', ''));

			// Prepare Data
			$_Email = $this->test_input($_POST["Email"]);

			// Check Param
			if (empty($_Fullname)) {
				$errorMessage = Emwork_Translate::text($this->lang ,'faq', 'validate_title');
			} else if (empty($_Email)) {
				$errorMessage = Emwork_Translate::text($this->lang ,'faq', 'validate_email');
			} else if (!filter_var($_Email, FILTER_VALIDATE_EMAIL)) {
				$errorMessage = Emwork_Translate::text($this->lang, 'faq', 'validate_email_pattern');
			} else if (empty($_Question)) {
				$errorMessage = Emwork_Translate::text($this->lang, 'faq', 'validate_question');
			} else {

				// Save
				$model = new Faq_Model_Question();
				$data = $model->save($this->lang, $_Fullname, $_Email, $_Question);

				// Result
				if($data->result){
					$this->sendmailUser($_Fullname, $_Email);
					$this->sendmailAdmin($_Fullname, $_Email);

					$this->_helper->redirector('finish', 'question', 'faq');
				}else{
					$errorMessage = Emwork_Translate::text($this->lang, 'faq', 'validate_error');
				}
			}
		} else {
			// Log Access
			System_Model_Log::access();
		}
		
		// Set Data to view
		$this->view->errorMessage = $errorMessage;
		$this->view->Fullname = $_Fullname;
		$this->view->Email = $_Email;
		$this->view->Question = $_Question;

		$menuID = $this->findMenu("/faq/question/index");

		Zend_Registry::set("MenuID", $menuID);
	}

	private function sendmailUser($senderName,$senderEmail) {

		// Mail Config
		$siteUrl = System_Model_Setting::getValue("SiteUrl", "Site");
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
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
		
		if($this->lang=='th'){
			$template = new Zend_Config_Ini(APPLICATION_PATH . '/modules/faq/configs/template.ini', 'new_question_user_th');
			$headerImageUrl =  $siteUrl . '/assets/faq/img/mail-header-th.jpg';
			$footerImageUrl =  $siteUrl . '/assets/faq/img/mail-footer-th.jpg';
		}else{
			$template = new Zend_Config_Ini(APPLICATION_PATH . '/modules/faq/configs/template.ini', 'new_question_user_en');
			$headerImageUrl =  $siteUrl . '/assets/faq/img/mail-header-en.jpg';
			$footerImageUrl =  $siteUrl . '/assets/faq/img/mail-footer-en.jpg';
		}

		$subject = $template->subject;
		$subject= str_replace("{site_name}", $siteName, $subject);
		$body = $template->body;
		$body = str_replace("{site_name}", $siteName, $body);
		$body = str_replace("{sender_name}", $senderName, $body);
		$body = str_replace("{header_image_url}", $headerImageUrl, $body);
		$body = str_replace("{footer_image_url}", $footerImageUrl, $body);
		
		// Send Mail
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
		
		// เตรียมข้อมูลในการส่งอีเมล์
		$mail = new Zend_Mail_InlineImages("UTF-8");
		$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
		$mail->setBodyHtml($body ,"UTF-8")
		->setFrom($smtpFromEmail, $smtpFromName)
		->addTo($senderEmail)
		->setSubject($subject);
		
		// ทำการส่งอีเมล์
		$mail->send($transport);
	}

	private function sendmailAdmin($senderName,$senderEmail) {

		// Mail Config
		$siteUrl = System_Model_Setting::getValue("SiteUrl", "Site");
		$siteName = System_Model_Setting::getValue("SiteName", "Site");
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
		
		$template = new Zend_Config_Ini(APPLICATION_PATH . '/modules/faq/configs/template.ini', 'new_question_admin');
		$headerImageUrl =  $siteUrl . '/assets/faq/img/mail-header-th.jpg';
		$footerImageUrl =  $siteUrl . '/assets/faq/img/mail-footer-th.jpg';

		$linkUrl = $siteUrl . 'admin/faq/question/index';
		
		// Get Email
		$model = new Faq_Model_Question();
		$data = $model->getAdminEmail();

		if(count($data)>0){
			foreach($data as $d){

				$subject = $template->subject;
				$subject= str_replace("{site_name}", $siteName, $subject);
				$subject= str_replace("{sender_name}", $senderName, $subject);
				$body = $template->body;
				$body = str_replace("{site_name}", $siteName, $body);
				$body = str_replace("{sender_name}", $senderName, $body);
				$body = str_replace("{link_url}", $linkUrl, $body);
				$body = str_replace("{admin_name}", ($d['FirstName'].' '.$d['LastName']), $body);
				$body = str_replace("{header_image_url}", $headerImageUrl, $body);
				$body = str_replace("{footer_image_url}", $footerImageUrl, $body);
				
				// Send Mail
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
				
				// เตรียมข้อมูลในการส่งอีเมล์
				$mail = new Zend_Mail_InlineImages("UTF-8");
				$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
				$mail->setBodyHtml($body ,"UTF-8")
				->setFrom($smtpFromEmail, $smtpFromName)
				->addTo($d['Email'])
				->setSubject($subject);
				
				// ทำการส่งอีเมล์
				$mail->send($transport);

			}
		}
	}
	
	public function finishAction()
	{
		// Log Access
		System_Model_Log::access();
		

		$menuID = $this->findMenu("/faq/question/finish");

		Zend_Registry::set("MenuID", $menuID);
	}
	
	protected function findMenu($url) {
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
	}

	protected function test_input($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}

}
?>