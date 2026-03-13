<?php

class Eform_ItemController extends Zend_Controller_Action
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
    
    function indexAction()
    {

		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		
		$model = new Eform_Model_Item();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);

		if($dataDetail->PublishDate >= date('Y-m-d H:i:s')) {
			$this->renderScript('item/_comming.phtml');
			return;
		}

		if($dataDetail->PublishDown && $dataDetail->PublishDown <= date('Y-m-d H:i:s')) {
			$this->renderScript('item/_timeout.phtml');
			return;
		}

		$ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
		
		$tmp_EformControlTypeID = array();
		$dataType  = $model->type($dataDetail->ID);
		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			$dataType[$key]['NAME_'] = $typeProperty['NAME'];
			$dataType[$key]['LABEL_'] = $typeProperty['LABEL'];
			$dataType[$key]['PLACEHOLDER_'] = $typeProperty['PLACEHOLDER'];
			$dataType[$key]['DESCRIPTION_'] = $typeProperty['DESCRIPTION'];
			$dataType[$key]['REQUIRED_'] = $typeProperty['REQUIRED'];
			$dataType[$key]['VALIDATIONRULE_'] = $typeProperty['VALIDATIONRULE'];
			$dataType[$key]['SIZE_'] = $typeProperty['SIZE'];
			$dataType[$key]['MAXLENGTH_'] = $typeProperty['MAXLENGTH'];
			$dataType[$key]['VALIDATE_'] = $typeProperty['VALIDATE'];
			$dataType[$key]['COLS_'] = $typeProperty['COLS'];
			$dataType[$key]['ROWS_'] = $typeProperty['ROWS'];
			$dataType[$key]['DROPDOWN_'] = $typeProperty['DROPDOWN'];
			$dataType[$key]['OPTION_'] = $typeProperty['OPTION'];
			$dataType[$key]['FLOW_'] = $typeProperty['FLOW'];
			$dataType[$key]['FILESIZE_'] = $typeProperty['FILESIZE'];
			$dataType[$key]['FILETYPE_'] = $typeProperty['FILETYPE'];
			$dataType[$key]['MULTIPLE_'] = $typeProperty['MULTIPLE'];
			$dataType[$key]['RESETBUTTON_'] = $typeProperty['RESETBUTTON'];
			$dataType[$key]['RESETLABEL_'] = $typeProperty['RESETLABEL'];
			$dataType[$key]['MESSAGE_'] = $typeProperty['MESSAGE'];
			if($value['EformControlTypeID'] == 10 && !$ReCaptchaSiteKey) {
				unset($dataType[$key]);
			}
			array_push($tmp_EformControlTypeID, $dataType[$key]['EformControlTypeID']);
		}
		
		$this->view->dataDetail = $dataDetail;
		$this->view->tmp_EformControlTypeID = $tmp_EformControlTypeID;
		$this->view->ID = $id;
		$this->view->dataType = $dataType;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->ItemCustomCss = $dataDetail->CustomCss;
		$this->view->ReCaptchaSiteKey = $ReCaptchaSiteKey;
	
		// Set Global
		$menuID = $this->findMenu("/eform/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
    }
	
	function saveAction()
	{
		// init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = Emwork_Translate::text($this->lang, 'eform', 'validate_error_2');
		
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
				$result['message'] = Emwork_Translate::text($this->lang, 'eform', 'validate_recaptcha');
				$this->_helper->json($result);
			}
		}

		$model = new Eform_Model_Item();
		$data = $model->save($params);

		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;

			$this->sendmail($params);

		} else {
			$result['result'] = false;
			$result['message'] = Emwork_Translate::text($this->lang, 'eform', 'validate_error_2');
		}

		$this->_helper->json($result);
	}

	private function sendmail($params)
	{

		$model = new Eform_Model_Item();
		$dataDetail = $model->getDataMail($params['hdfID']);

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

		/* Admin */
		$AdminFromEmail = $this->replace_deep_tag($dataDetail->AdminFromEmail, $params['params']); // ส่งจากอีเมล์
		$AdminSendEmail = $this->replace_deep_tag($dataDetail->AdminSendEmail, $params['params']); // ส่งอีเมล์ถึง
		$AdminSendEmails = array();
		$AdminSendEmailTemps = explode(",", $AdminSendEmail);
		if (count($AdminSendEmailTemps) > 0) {
			foreach ($AdminSendEmailTemps as $AdminSendEmailTemp) {
				$AdminSendEmailTemp = trim($AdminSendEmailTemp);
				if ($this->checkEmail($AdminSendEmailTemp)) {
					$AdminSendEmails[] = $AdminSendEmailTemp;
				}
			}
		}

		if( ($this->checkEmail($AdminFromEmail)) && (count($AdminSendEmails) > 0) ) {

			$AdminFromName = $this->replace_deep_tag($dataDetail->AdminFromName, $params['params']); // ชื่อผู้ส่ง
			$AdminSubject = $this->replace_deep_tag($dataDetail->AdminSubject, $params['params']); // ชื่อเรื่อง
			$AdminBody = $this->replace_deep_tag($dataDetail->AdminBody, $params['params']); // ข้อความตอบกลับ
			
			// เตรียมข้อมูลในการส่งอีเมล์
			$mail = new Zend_Mail_InlineImages("UTF-8");
			$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
			$mail->setBodyHtml(nl2br($AdminBody) ,"UTF-8")
			->setFrom($AdminFromEmail, $AdminFromName)
			//->addTo($AdminSendEmail)
			->addTo($AdminSendEmails)
			->setSubject($AdminSubject)
			;
			
			// ทำการส่งอีเมล์
			$mail->send($transport);
		}

		/* User */
		$UserFromEmail = $this->replace_deep_tag($dataDetail->UserFromEmail, $params['params']); // ส่งจากอีเมล์
		$UserSendEmail = $this->replace_deep_tag($dataDetail->UserSendEmail, $params['params']); // ส่งอีเมล์ถึง

		if( ($this->checkEmail($UserFromEmail)) && $this->checkEmail(($UserSendEmail)) ) {
			$UserFromName = $this->replace_deep_tag($dataDetail->UserFromName, $params['params']); // ชื่อผู้ส่ง
			$UserSubject = $this->replace_deep_tag($dataDetail->UserSubject, $params['params']); // ชื่อเรื่อง
			$UserBody = $this->replace_deep_tag($dataDetail->UserBody, $params['params']); // ข้อความตอบกลับ

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

	function checkEmail($email) {
		if ( strpos($email, '@') !== false ) {
		   $split = explode('@', $email);
		   return (strpos($split['1'], '.') !== false ? true : false);
		}
		else {
		   return false;
		}
	 }

	public function finishAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();
        
        // Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		
		$model = new Eform_Model_Item();
		$data = $model->getDataByLanguage($id, $this->lang);

		// Send Data to view
		$this->view->id = $data->ID;
		$this->view->pageTitle = empty($data->Title) ? '.' : $data->Title;
		$this->view->ResponseMessage = $data->ResponseMessage;
		
		$menuID = $this->findMenu("/eform/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}

	function previewAction()
	{

		$this->_helper->layout()->disableLayout();
		
		// Log Access
		System_Model_Log::access();

		$params = $this->getRequest()->getParams();

		$id = $params['hdfID'];

		unset($params['module']);
		unset($params['controller']);
		unset($params['action']);
		unset($params['hdfID']);
		unset($params['lang']);
		unset($params['g-recaptcha-response']);

		$model = new Eform_Model_Item();
		$fileModel = new System_Model_Fileinfo();
		
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		$dataType  = $model->type($id);
		foreach($dataType as $key => $value) {
			$typeProperty = $model->typeProperty($value['ID']);
			$dataType[$key]['NAME_'] = $typeProperty['NAME'];
			$dataType[$key]['LABEL_'] = $typeProperty['LABEL'];
			if($dataType[$key]['EformControlTypeID'] == 7) {
				if(count($params['hdf'.$typeProperty['NAME']])>0) {
					foreach($params['hdf'.$typeProperty['NAME']] as $k) {
						$fileData = $fileModel->itemByHashName($k);
						$dataType[$key]['VALUE_']['NAME'] = $fileData->Name;
						$dataType[$key]['VALUE_']['HASHNAME'] = $fileData->HashName;
					}
				}
			} elseif (in_array($dataType[$key]['EformControlTypeID'],array(3,4,5))) {
				$dataType[$key]['VALUE_'] = $params['params'][$typeProperty['NAME']];
			} elseif($dataType[$key]['EformControlTypeID'] == 6) {
				$dataType[$key]['VALUE_'] = '********';
			} else {
				$dataType[$key]['VALUE_'] = empty($params['params'][$typeProperty['NAME']]) ? '-' : $params['params'][$typeProperty['NAME']];
			}
		}

		$this->view->ID = $id;
		$this->view->dataType = $dataType;
		$this->view->pageTitle = $dataDetail->Title;
	}

}
?>