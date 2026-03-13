<?php
class System_SendmailController extends Zend_Controller_Action {
		
	function init() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$this->getResponse()->clearAllHeaders();
		$this->getResponse()->setHeader('Content-Type', 'application/json; charset=utf-8');
	}
	
	function indexAction() {
		
	}
	
	/**
	 * 
	 */
	function complainAction() {
		
		set_time_limit ( 600 );		

		$request = $this->getRequest();
		$_accessKey = $request->getParam('key', '');
		$_accessSecret = $request->getParam('secret', '');
		
		// Access Config
		$accessConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/job.ini', 'access');
		$accessKey = $accessConfig->key;
		$accessSecret = $accessConfig->secret;
			
		if ($_accessKey != $accessKey || $_accessSecret != $accessSecret) {
			exit;
		}
		
		// ComplainID
		$_itemId = $request->getParam('id', '');
		$_lang = $request->getParam('lang', '');
		
		$modelComplain = new Complain_Model_Complain();
		$dataComplain = $modelComplain->getInfo($_itemId);
		
		if (empty($dataComplain->contact_email)) {
			exit;
		}
		
		// 
		if($_lang == 'th'){
			$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/configs/template_th.ini', 'complain');
		}else{
			$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/configs/template_en.ini', 'complain');
		}
		$setting = System_Model_Setting::getInfo();
		
		$fromEmail = $setting->smtp_from_email;
		$fromName = $setting->smtp_from_name;
		$toEmail = $dataComplain->contact_email;
		$subject = $configTemplate->subject;
		$body = $configTemplate->body;
		$body = str_replace("{issuecode}", $dataComplain->issue_code, $body);
		$priority = $configTemplate->priority;
			
		// Get User By Email
		$users = new System_Model_Users();
		$user = $users->getInfoByEmail($toEmail);
		
		// send mail
		$config = array(
			'port' => $setting->smtp_port
		);
		if ($setting->smtp_use_auth == 'Y') {
			$config["auth"] = $setting->smtp_auth_type;
			$config["username"] = $setting->smtp_auth_username;
			$config["password"] = $setting->smtp_auth_password;
		}
		if ($setting->smtp_use_ssl == 'Y') {
			$config["ssl"] = $setting->smtp_ssl_type;
		}
	
		$transport = new Zend_Mail_Transport_Smtp($setting->smtp_host, $config);
	
		// --------- ส่งเมล์ให้ผู้ร้องเรียน --------- //
		// เตรียมข้อมูลในการส่งอีเมล์
		$mail = new Zend_Mail("UTF-8");
		$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
		$mail->setBodyHtml($body ,"UTF-8")
		->setFrom($fromEmail, $fromName)
		->addTo($toEmail)
		->setSubject($subject);
	
		// ส่งอีเมล์
		$mail->send($transport);
		// --------- END: ส่งเมล์ให้ผู้ร้องเรียน --------- //
		
		// --------- ส่งเมล์ให้ผู้ดูแลระบบ --------- //
		$adminList = System_Model_Users::getListByAction('complain_notify');		
		if (count($adminList) > 0) {
			$configAdminTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/configs/template_th.ini', 'complainadmin');
			$subject = $configAdminTemplate->subject;
			$body = $configAdminTemplate->body;
			$body = str_replace("{issuecode}", $dataComplain->issue_code, $body);
			foreach ($adminList as $admin) {
				if (empty($admin['email'])) {
					continue;
				}
				
				$toEmail = $admin['email'];
				
				// เตรียมข้อมูลในการส่งอีเมล์
				$mail = new Zend_Mail("UTF-8");
				$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
				$mail->setBodyHtml($body ,"UTF-8")
				->setFrom($fromEmail, $fromName)
				->addTo($toEmail)
				->setSubject($subject);
				
				// ส่งอีเมล์
				$mail->send($transport);
			}
		}		
		// --------- END: ส่งเมล์ให้ผู้ดูแลระบบ --------- //
			
		// Finish
		$result = new stdClass();
		$result->result = true;
		$this->_helper->json($result);
	}
	
	function newsAction() {
	
		set_time_limit ( 600 );
	
		$request = $this->getRequest();
		$_accessKey = $request->getParam('key', '');
		$_accessSecret = $request->getParam('secret', '');
		$newsId = $this->_request->getParam('newsid');
		$senderName = $this->_request->getParam('sendername');
		$emails = $this->_request->getParam('emails');
		$message = $this->_request->getParam('message');
		$lang = $this->_request->getParam('lang');
	
		// Access Config
		$accessConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/job.ini', 'access');
		$accessKey = $accessConfig->key;
		$accessSecret = $accessConfig->secret;
			
		if ($_accessKey != $accessKey || $_accessSecret != $accessSecret) {
			echo "access deny"; 
			exit;
		}
	
		//
		if($_lang == 'th'){
			$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/configs/template_th.ini', 'news');
		}else{
			$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/configs/template_en.ini', 'news');
		}
		$setting = System_Model_Setting::getInfo();
		
		// Model
		$model = new News_Model_News();
		$data = $model->getNewsInfo($newsId , $lang);
		
		if (empty($data->id)) {
			echo "news empty.";
			exit;
		}
		
		$url = $setting->site_url . $this->view->url(array('module' => 'news', 'controller' => 'detail', 'action' => 'index', 'itemid' => $data->id), null, true);
	
		$fromEmail = $setting->smtp_from_email;
		$fromName = $setting->smtp_from_name;
		$toEmail = $dataComplain->contact_email;
		// Subject
		$subject = $configTemplate->subject;
		$subject = str_replace("{title}", $data->title, $subject);
		// Body
		$body = $configTemplate->body;
		$body = str_replace("{sender_name}", $senderName, $body);
		$body = str_replace("{message}", $message, $body);
		$body = str_replace("{url}", $url, $body);
		$priority = $configTemplate->priority;
	
		// send mail
		$config = array(
				'port' => $setting->smtp_port
		);
		if ($setting->smtp_use_auth == 'Y') {
			$config["auth"] = $setting->smtp_auth_type;
			$config["username"] = $setting->smtp_auth_username;
			$config["password"] = $setting->smtp_auth_password;
		}
		if ($setting->smtp_use_ssl == 'Y') {
			$config["ssl"] = $setting->smtp_ssl_type;
		}
	
		$transport = new Zend_Mail_Transport_Smtp($setting->smtp_host, $config);
	
		$emailArray = explode(',', $emails);
		foreach ($emailArray as $email) {
			$email = trim($email);

			// เตรียมข้อมูลในการส่งอีเมล์
			$mail = new Zend_Mail("UTF-8");
			$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
			$mail->setBodyHtml($body ,"UTF-8")
			->setFrom($fromEmail, $fromName)
			->addTo($email)
			->setSubject($subject);			
			// ทำการส่งอีเมล์
			$mail->send($transport);
		}
			
		// Finish
		$result = new stdClass();
		$result->result = true;		
		$this->_helper->json($result);
	}
}