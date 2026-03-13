<?php

class RegisterController extends Zend_Controller_Action
{
	protected $lang;
	protected $genderOption;
	
	public function init()
	{ 
		// Get Languages
	    //$lang = empty($_COOKIE['lang']) ? 'th' : $_COOKIE['lang'];
	    $lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
	    if (empty($lang)) {
	        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	        $lang = $defaultLanguage["Code"];
		}
		$this->lang = $lang;
		$this->view->lang = $lang;
		// Option
	   $this->genderOption= array(
            "M" => Emwork_Translate::text($this->lang, 'auth', 'register_option_man'),
            "F" => Emwork_Translate::text($this->lang, 'auth', 'register_option_female'),
		);
		
		// Set View
		$this->view->hidePageName = true;
		$this->view->hideBreadcrumb = true;
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
	
	/**
	 * Page : 
	 */
	public function indexAction()
	{
		// Send Data to Template
		$this->view->bodyClass = "register-body-index";
				
		$menuID = $this->findMenu("/register/index");
		Zend_Registry::set("MenuID", $menuID);

		// Get Params
		$params = $this->_request->getParams();
		
		require_once APPLICATION_ROOT . DS . 'library' . DS . 'Mobile_Detect.php';
		$detect = new Mobile_Detect();
		
		$this->view->isiOS = $detect->isiOS();
		
		// Send Data To View
		$this->view->lang = $this->lang;
		$this->view->genderOption = $this->genderOption;
		$this->view->ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");
	}
	
	public function saveAction()
	{
		// Log Api
		$ipAddress = Emwork_Utility::get_client_ip();
		$serviceCode = "register";
		$methodCode = "save";
		$methodType = $this->getRequest()->getMethod();
		$params = print_r($this->getRequest()->getParams(), true);

		// Init
		$result = new stdClass();
	
		if ($this->_request->isPost()) {
	
			// Init value
			$result->result = false;
			$result->message = "";
			$result->user_id = "";
	
			// Get Param
			$params = $this->_request->getParams();

			// Check ReCaptcha
			$recaptchaSiteVerify = 'https://www.google.com/recaptcha/api/siteverify';
			$recaptchaReq = array();
			$recaptchaReq["secret"] = System_Model_Setting::getValue("ReCaptchaSecretKey", "General");
			$recaptchaReq["response"] = $params["g-recaptcha-response"];
			$recaptchaRes = Emwork_Utility::callAPI("POST", $recaptchaSiteVerify, $recaptchaReq);
			$recaptchaResArray = json_decode($recaptchaRes, true);
			if (!$recaptchaResArray["success"]) {
				$result->message = Emwork_Translate::text($this->lang, 'auth', 'register_error_0');
				$this->_helper->json($result);
			}

			// Model
			$Model = new Profile_Model_Register();
			
			// Validate Data
			if ($Model->checkUsername($params['txtEmail'])) { // Check: username ห้ามซ้ำ
				$result->message = Emwork_Translate::text($this->lang, 'auth', 'register_error_1');
				$this->_helper->json($result);
			} elseif ($Model->checkEmail($params['txtEmail'])) { // Check: email ห้ามซ้ำ
				$result->message = Emwork_Translate::text($this->lang, 'auth', 'register_error_1');
				$this->_helper->json($result);
			}
			if(strlen($params['txtPassword'])<8){
				$result->message = Emwork_Translate::text($this->lang, 'auth', 'register_error_2');
				$this->_helper->json($result);
			}
			if($params['txtPassword']!= $params['txtConfirmPassword']){
				$result->message = Emwork_Translate::text($this->lang, 'auth', 'register_error_3');
				$this->_helper->json($result);
			}
			
			// prepare picture
			$profilePictureId= null;
			if (count($params['hdfprofile_picture'])) {
			    $modelFileInfo = new System_Model_Fileinfo();
			    $pictureData = $modelFileInfo->itemByHashName($params['hdfprofile_picture'][0]);
			    if ($pictureData->ID != null) {
			        $profilePictureId= $pictureData->ID;
			    }
			}
			$params['profilePictureId'] = $profilePictureId;
			

			// บันทึกข้อมูลการสมัคร
			$data = $Model->register($params);

			if($data->result) {
				
				if ($data->activateCode) {
					
					// จัดส่ง Email เพื่อให้ activate
					if($this->lang == 'th') {
						$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/profile/configs/template_th.ini', 'register_activate');
					} else {
						$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/profile/configs/template_en.ini', 'register_activate');
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
						$url = $siteUrl . 'register/activate/confirmcode/' . $data->activateCode;
					} else {
						$url = $siteUrl . '/register/activate/confirmcode/' . $data->activateCode;
					}

					$subject = $configTemplate->subject;
					$body = $configTemplate->body;
					$body = str_replace("{fullname}", $params['txtFirstName'] . ' ' . $params['txtLastName'], $body);
					$body = str_replace("{url}", $url, $body);
					$body = str_replace("{header_image_url}", $headerImageUrl, $body);
					$body = str_replace("{footer_image_url}", $footerImageUrl, $body);

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

					$transport = new Zend_Mail_Transport_Smtp($smtpHost, $config);

					// เตรียมข้อมูลในการส่งอีเมล์
					$mail = new Zend_Mail_InlineImages("UTF-8");
					$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
					$mail->setBodyHtml($body ,"UTF-8")
					->setFrom($smtpFromEmail, $smtpFromName)
					->addTo($params['txtEmail'])
					->setSubject($subject);
					
					// ทำการส่งอีเมล์
					$mail->send($transport);
						
					// Finish
					$result->result = true;
					$result->message = Emwork_Translate::text($this->lang, 'auth', 'register_error_4');
					$this->_helper->json($result);
						
				} else {
					$result->message = Emwork_Translate::text($this->lang, 'auth', 'register_error_5');
					$this->_helper->json($result);
				}
	
			}else{
				$result->message = Emwork_Translate::text($this->lang, 'auth', 'register_error_6');
				$this->_helper->json($result);
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
	public function finishAction()
	{
		$menuID = $this->findMenu("/banner/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);

		// Log Access
		System_Model_Log::access();
		
		// $this->view->pageName = $this->view->Translate('register', $this->lang, 'register_pagename_3');
		// $this->view->breadcrumb = array(
		// 		array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_1'), 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
		// 		array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_2'), 'url' => $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), null, true)),
		// 		array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_3'))
		// );
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
		
		$confirmCode = $this->getRequest()->getParam("confirmcode", "");

		$modelProfile = new Profile_Model_Register();
		
		$userObj = $modelProfile->getInfoActivateCode($confirmCode);

		$renderName = "activatesuccess";
		if ($userObj== null) {
			// แจ้งว่าไม่พบการยืนยัน
			$renderName = "activatefail";
		} else {
			// Active User
			$modelProfile->updateProperty($userObj->ID, array('IsEnable' => 'Y', 'ActivateCode' => null));
			
			// Send Mail
			if($this->lang == 'th') {
				$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/profile/configs/template_th.ini', 'register_finish');
			} else {
				$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/profile/configs/template_th.ini', 'register_finish');
			}

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

			$transport = new Zend_Mail_Transport_Smtp($smtpHost, $config);

			// Set URL
			if(substr($siteUrl, -1) == '/') {
				$homeUrl = $siteUrl . 'home/index';
				$loginUrl = $siteUrl . 'auth/login';
			} else {
				$homeUrl = $siteUrl . '/home/index';
				$loginUrl = $siteUrl . '/auth/login';
			}
			
			$fromName = $smtpFromName;
			$toEmail = $userObj->Email;
			$subject = $configTemplate->subject;
			$body = $configTemplate->body;
			$body = str_replace("{fullname}", $userObj->FirstName . ' ' . $userObj->LastName, $body);
			$body = str_replace("{login_url}", $loginUrl, $body);
			$body = str_replace("{home_url}", $homeUrl, $body);
			
			// เตรียมข้อมูลในการส่งอีเมล์
			$mail = new Zend_Mail_InlineImages("UTF-8");
			$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
			$mail->setBodyHtml($body ,"UTF-8")
			->setFrom(null, $fromName)
			->addTo($toEmail)
			->setSubject($subject);
			
			// ทำการส่งอีเมล์
			$mail->send($transport);
		}

		$this->view->pageName = "ยืนยันการลงทะเบียน";
		$this->view->breadcrumb = array(
				array('name' => "หน้าแรก", 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => "ลงชื่อเข้าใช้งานระบบ", 'url' => $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), null, true)),
				array('name' => "ยืนยันการลงทะเบียน")
		);
		$this->render($renderName);
	}


	/**
	 * Page : facebook register
	 */
	public function facebookAction()
	{
		// Get Params
		$params = $this->_request->getParams();
	
		// Get Session
		$sessionFacebook = new Zend_Session_Namespace('facebook');
		
		if(empty($sessionFacebook->facebook_id)){
			$this->_helper->redirector('login', 'auth');
		}
		
		// Upload Facebook Picture
		$dataPicture = $this->uploadfacebookPictureAction($sessionFacebook->facebook_id, $sessionFacebook->picture);
		if($dataPicture->result){
		    $fb_pictuer = array();
		    $fb_pictuer[] = array(
		        "file_name" => $dataPicture->data->name,
		        "size" => $dataPicture->data->filesize,
		        "type" => $dataPicture->data->filetype,
		        "hash_name" => $dataPicture->data->hashname,
		    );
		}else{
		    $fb_pictuer = null;
		}
		
		$data = new stdClass();
		$data->fb_name = $sessionFacebook->name;
		$data->fb_firstname = $sessionFacebook->first_name;
		$data->fb_lastname = $sessionFacebook->last_name;
		$data->fb_email = $sessionFacebook->email;
		$data->fb_picture = $fb_pictuer;
		
		// Get Option
		//$modelProvince = new Lookup_Model_Province();
		$provinceOption = array(); //$modelProvince->getOption($this->lang);

		// Send Data To View
		$this->view->data = $data;
		$this->view->sellerTypeOption = $this->sellerTypeOption;
		$this->view->provinceOption = Emwork_Utility::addEmptyOption($provinceOption, $this->view->Translate('profile', $this->lang, 'profile_option_select'));
	
		$this->view->pageName = $this->view->Translate('register', $this->lang, 'register_pagename_4');
		$this->view->breadcrumb = array(
				array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_1'), 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_2'), 'url' => $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), null, true)),
				array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_4'))
		);
	}
	
	private function uploadfacebookPictureAction($facebookId, $facebookPicture)
	{
        // Init
	    $result = new stdClass();
	    $result->result = false;
	    $result->message = '';
	    $result->data = new  stdClass();

	    // Download file
	    $tmpPath = APPLICATION_ROOT . DS . 'tmp' . DS . 'facebook' . DS . 'fb' . $facebookId . '.jpg';
	    $this->downloadFile($facebookPicture, $tmpPath);
	    
	    // Upload file
	    $uploadRootPath = APPLICATION_ROOT . DS . 'files';
	    $storePath = '/' . date('Y') . '/' . date('m') . '/' . date('d');
	    
	    $pathToUploads = $uploadRootPath . $storePath;
	    $pathToUploads = str_replace('/', DS, $pathToUploads);
	    $pathToUploads = str_replace("\\", DS, $pathToUploads);
	    
	    if ($this->createDirectory($pathToUploads)) {
	        
	        $baseName = 'fb'.$facebookId.'.jpg';
	        $fileSize = filesize($tmpPath);
	        $fileType = Emwork_Utility_File::returnMIMEType($baseName);
	        $fileName = pathinfo($baseName, PATHINFO_FILENAME);
	        $ext = pathinfo($baseName,  PATHINFO_EXTENSION);
	        $fileData = file_get_contents($tmpPath);
	        
	        $hashFileName = date('Ymd') . md5($fileName) . date('His');
	        $hashBaseName = $hashFileName . '.' . $ext;
	        
	        $resultFile = file_put_contents($pathToUploads . DS . $hashBaseName, $fileData);
	        if ($resultFile) {
	            $modelFile = new System_Model_Picture();
	            $data = $modelFile->save($baseName, $hashBaseName, $storePath, $fileSize, $ext, $fileType);
	            
	            if ($data->result) {
	                $dataResult = new stdClass();
	                $dataResult->id = $data->id;
	                $dataResult->name = $baseName;
	                $dataResult->hashname = $hashBaseName;
	                $dataResult->filesize = $fileSize;
	                $dataResult->filetype = $fileType;
	                $dataResult->fileurl = $dataSetting->site_url . $this->view->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get', 'file' => $hashBaseName), null, true);
	                
	                $result->result = true;
	                $result->data = $dataResult;
	            } else {
	                
	            }
	        }
	        @unlink($tmpPath);
	    }else {
	        $result->message = 'Create Folder Fail';
	    }
	    return $result;
	}
	
	private function downloadFile($url, $path)
	{
	    $newfname = $path;
	    $file = fopen ($url, 'rb');
	    if ($file) {
	        $newf = fopen ($newfname, 'wb');
	        if ($newf) {
	            while(!feof($file)) {
	                fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
	            }
	        }
	    }
	    if ($file) { fclose($file); }
	    if ($newf) { fclose($newf); }
	}
	
	private function createDirectory($pathToUploads)
	{
	    $result = false;
	    if (file_exists($pathToUploads)) {
	        $result = true;
	    } else {
	        $result = mkdir($pathToUploads, 0777, true);
	    }
	    return $result;
	}
	
	/**
	 * Page : register by facebook
	 */
	public function savefacebookAction()
	{
		// Init
		$result = array();
		$result['result'] = false;
		$result['message'] = '';
	
		// Get Params
		$params = $this->getRequest()->getParams();
		
		// Get Session
		$sessionFacebook = new Zend_Session_Namespace('facebook');
		
		// Prepare data
		$params['facebook_id'] = $sessionFacebook->facebook_id;
		$params['facebook_name'] = $sessionFacebook->facebook_name;
		$params['email'] = empty($sessionFacebook->email) ? $params['txtEmail'] : $sessionFacebook->email;
		
		// Model
		$model = new System_Model_Register();
		$modelPicture = new System_Model_Picture();
		
		// Prepare Profile Picture
		$profilePictureId= null;
		if (count($params['hdfprofile_picture'])) {
			$pictureData = $modelPicture->itemByHashName($params['hdfprofile_picture'][0]);
			if ($pictureData->id != null) {
				$profilePictureId= $pictureData->id;
			}
		}
		$params['profilePictureId'] = $profilePictureId;
		
		// Prepare shareholder list
		if(count($params['shareholder'])>0){
			foreach ($params['shareholder'] as $k => $v){
				if(empty($v['titlename']) && empty($v['firstname']) && empty($v['lastname'])){
					unset($params['shareholder'][$k]);
				}
			}
		}
		
		// Save
		$data = $model->registerFacebook($params);
	
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			
			// Destroy session
			Zend_Session::namespaceUnset('facebook');
			
		} else {
			$result['result'] = false;
			$result['message'] = $this->view->Translate('register', $this->lang, 'register_warning_send_email_4');
		}
	
		$this->_helper->json($result);
	}
	
	/**
	 * Page : facebook finish
	 */
	public function facebookfinishAction()
	{
		// Log Access
		System_Model_Log::access();
	
		$this->view->pageName = $this->view->Translate('register', $this->lang, 'register_pagename_3');
		$this->view->breadcrumb = array(
				array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_1'), 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_2'), 'url' => $this->view->url(array('module' => 'default', 'controller' => 'auth', 'action' => 'login'), null, true)),
				array('name' => $this->view->Translate('register', $this->lang, 'register_pagename_3'))
		);
	}

	
}

