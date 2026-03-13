<?php
class Event_MailController extends Zend_Controller_Action
{

    protected $auth;
    protected $lang;
    
    public function init()
	{
		// Auth
		$auth = Zend_Auth::getInstance();
		$authData = $auth->getIdentity();
		$this->auth = $authData;

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
        
    public function dialogAction()
    {
        $this->_helper->layout()->disableLayout();

		// Log Access
		System_Model_Log::access();

        // Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$eventid = $filterInt->filter($this->_request->getParam('eventid'));

        $this->view->eventID = $eventid;
	}    

	public function saveAction()
	{
		// init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = Emwork_Translate::text($this->lang, 'event', 'validate_error');
		
		$params = $this->getRequest()->getParams();
		$params['lang'] = $this->lang;
		unset($params['module']);
		unset($params['controller']);
		unset($params['action']);

		if($params['g-recaptcha-response']) {
			// Check ReCaptcha
			$recaptchaSiteVerify = 'https://www.google.com/recaptcha/api/siteverify';		
			$recaptchaReq = array();
			$recaptchaReq["secret"] = System_Model_Setting::getValue("ReCaptchaSecretKey", "General");
			$recaptchaReq["response"] = $params["g-recaptcha-response"];
			$recaptchaRes = Emwork_Utility::callAPI("POST", $recaptchaSiteVerify, $recaptchaReq);
			$recaptchaResArray = json_decode($recaptchaRes, true);
			if (!$recaptchaResArray["success"]) {
				$result['message'] = Emwork_Translate::text($this->lang, 'event', 'validate_recaptcha');
				$this->_helper->json($result);
			}
		}

		$model = new Event_Model_Mail();

		$data = $model->save($params);

		if ($data->result) {
			$result['result'] = true;
			$result['message'] = Emwork_Translate::text($this->lang, 'event', 'validate_success');
			$result['id'] = $data->id;
			$this->sendmail($params);
		} else {
			$result['result'] = false;
			$result['message'] = Emwork_Translate::text($this->lang, 'event', 'validate_error');
		}
		$this->_helper->json($result);		
	}
	
	private function sendmail($params)
	{

		$model = new Event_Model_Event();
		$data = $model->getDataByLanguage($params['hdfEventID'], $this->lang);
		
		if($data->EmailRecipient) {

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

			$subject = Emwork_Translate::text($this->lang, 'event', 'mail_subject') . ' ' . $data->Title;
			
			// เตรียมข้อมูลในการส่งอีเมล์
			$mail = new Zend_Mail_InlineImages("UTF-8");
			$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
			$mail->setBodyHtml(nl2br($params['Detail']),"UTF-8")
			->setFrom($params['Email'], $params['Name'])
			->addTo($data->EmailRecipient)
			->setSubject($subject);
			
			// ทำการส่งอีเมล์
			$mail->send($transport);
		}

	}

}
?>