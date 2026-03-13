<?php

class Webboard_RegisterController extends Zend_Controller_Action
{
	protected $sexOption = array("M"=> "ชาย", "F" => "หญิง");
	protected $dayOption = array(
			'01' => '1', '02' => '2', '03' => '3', '04' => '4', '05' => '5', '06' => '6', '07' => '7', '08' => '8', '09' => '9', '10' => '10',
			'11' => '11', '12' => '12', '13' => '13', '14' => '14', '15' => '15', '16' => '16', '17' => '17', '18' => '18', '19' => '19', '20' => '20',
			'21' => '21', '22' => '22', '23' => '23', '24' => '24', '25' => '25', '26' => '26', '27' => '27', '28' => '28', '29' => '29', '30' => '30',
			'31' => '31',
	);
	protected $monthOption = array(
		'01' => 'มกราคม' ,
		'02' => 'กุมภาพันธ์' ,
		'03' => 'มีนาคม' ,
		'04' => 'เมษายน' ,
		'05' => 'พฤษภาคม' ,
		'06' => 'มิถุนายน' ,
		'07' => 'กรกฎาคม' ,
		'08' => 'สิงหาคม' ,
		'09' => 'กันยายน' ,
		'10' => 'ตุลาคม' ,
		'11' => 'พฤศจิกายน' ,
		'12' => 'ธันวาคม' 
	);
	protected $yearOption;

	
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

		// Set View
		$this->view->hidePageName = true;
		//$this->view->hideBreadcrumb = true;
		
		$nowYear = date('Y');
		$year = array();
		for($i=0; $i<100; $i++){
			$YearAD = ($nowYear)-$i;
			$YearBC = ($nowYear+543)-$i;
			$year[$YearAD] = $YearBC;
		}
		$this->yearOption = $year;

		$this->sexOption = array("M"=> Emwork_Translate::text($this->lang, 'webboard', 'profile_sex_man'), "F" => Emwork_Translate::text($this->lang, 'webboard', 'profile_sex_female'));
		$this->monthOption = array(
			'01' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_1'),
			'02' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_2'),
			'03' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_3'),
			'04' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_4'),
			'05' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_5'),
			'06' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_6'),
			'07' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_7'),
			'08' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_8'),
			'09' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_9'),
			'10' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_10'),
			'11' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_11'),
			'12' => Emwork_Translate::text($this->lang, 'webboard', 'register_month_12') 
		);
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
		
		// Log Access
		System_Model_Log::access();
		
		// Get Params
		$params = $this->_request->getParams();
		
		// Mobile Detect
		require_once APPLICATION_ROOT . DS . 'library' . DS . 'Mobile_Detect.php';
		$detect = new Mobile_Detect();
		$this->view->isiOS = $detect->isiOS();
		
		// Send Data To View
		$this->view->sexOption = Emwork_Utility::addEmptyOption($this->sexOption);
		// $this->view->dayOption = Emwork_Utility::addEmptyOption($this->dayOption, 'วัน');
		// $this->view->monthOption = Emwork_Utility::addEmptyOption($this->monthOption, 'เดือน');
		// $this->view->yearOption = Emwork_Utility::addEmptyOption($this->yearOption, 'ปี');
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function saveAction()
	{
		// Init
		$result = new stdClass();
	
		if ($this->_request->isPost()) {
	
			// Init value
			$result->result = false;
			$result->message = "";
			$result->user_id = "";
	
			// Get Param
			$params = $this->_request->getParams();

			// Model
			$Model = new Webboard_Model_Register();
			// $modelPicture = new System_Model_Picture();
			
			// Validate Data
			if ($Model->checkUsername($params['txtUser'])) { // Check: username ห้ามซ้ำ
				$result->message = Emwork_Translate::text($this->lang, 'webboard', 'register_validate_email');
				$this->_helper->json($result);
			} elseif ($Model->checkEmail($params['txtEmail'])) { // Check: email ห้ามซ้ำ
				$result->message = Emwork_Translate::text($this->lang, 'webboard', 'register_validate_email');
				$this->_helper->json($result);
			}
			if(strlen($params['txtPassword'])<8){
				$result->message = Emwork_Translate::text($this->lang, 'webboard', 'register_validate_password');
				$this->_helper->json($result);
			}
			if($params['txtPassword']!= $params['txtConfirmPassword']){
				$result->message = Emwork_Translate::text($this->lang, 'webboard', 'register_validate_password_2');
				$this->_helper->json($result);
			}

			// บันทึกข้อมูลการสมัคร
			$data = $Model->register($params);

			if($data->result){
				
				if ($data->activateCode) {
					
					// จัดส่ง Email เพื่อให้ activate
					$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/webboard/configs/template.'.$this->lang.'.ini', 'register_activate');
					
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
					$url = $settingSiteUrl . '/th/webboard/register/activate/confirmcode/' . $data->activateCode;
					$headerImageUrl =  $settingSiteUrl . '/images/other/mail-header.jpg';
					$footerImageUrl =  $settingSiteUrl . '/images/other/mail-footer.jpg';
						
					$fromName = $settingFromName;
					$fromEmail = $settingFromEmail;
					$toEmail = $params['txtEmail'];
					$subject = $configTemplate->subject;
					$body = $configTemplate->body;
					$body = str_replace("{fullname}", $params['txtFirstName'] . ' ' . $params['txtLastName'], $body);
					$body = str_replace("{url}", $url, $body);
					$body = str_replace("{header_image_url}", $headerImageUrl, $body);
					$body = str_replace("{footer_image_url}", $footerImageUrl, $body);

					
					// Get User By Email
					$users = new Webboard_Model_Users();
					$user = $users->getInfoByEmail($toEmail);
					
					if (empty($user->Email)) {
						$result->message = Emwork_Translate::text($this->lang, 'webboard', 'register_no_email');
						$this->_helper->json($result);
					} else {
	
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
							
						// Finish
						$result->result = true;
						$result->message = Emwork_Translate::text($this->lang, 'webboard', 'register_validate_email_1');
						$this->_helper->json($result);
					}
						
				} else {
					$result->message = Emwork_Translate::text($this->lang, 'webboard', 'register_validate_email_2');
					$this->_helper->json($result);
				}
	
			}else{
				$result->message = Emwork_Translate::text($this->lang, 'webboard', 'register_validate_email_3');
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
		// Log Access
		System_Model_Log::access();
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);
	}
	
	/**
	 * Page : 
	 */
	public function activateAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);
		
		$confirmCode = $this->getRequest()->getParam("confirmcode", "");

		$userObj = Webboard_Model_Users::getInfoActivateCode($confirmCode);

		$renderName = "activatesuccess";
		if ($userObj== null) {
			// แจ้งว่าไม่พบการยืนยัน
			$renderName = "activatefail";
		} else {
			// Active User
			Webboard_Model_Users::updateProperty($userObj->ID, array('IsEnable' => 'Y', 'ActivateCode' => null));
			
			// Send Mail
			$configTemplate = new Zend_Config_Ini(APPLICATION_PATH . '/modules/webboard/configs/template.'.$this->lang.'.ini', 'register_finish');
			
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
			
			// $headerImageUrl =  $settingSiteUrl . '/images/other/mail-header.jpg';
			// $footerImageUrl =  $settingSiteUrl . '/images/other/mail-footer.jpg';
			
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

			// Set URL
			$homeUrl = $settingSiteUrl . '/'.$this->lang.'/webboard/home/index';
			$loginUrl = $settingSiteUrl . '/'.$this->lang.'/webboard/auth/login';
			// $headerImageUrl =  $settingSiteUrl . '/images/other/mail-header.jpg';
			// $footerImageUrl =  $settingSiteUrl . '/images/other/mail-footer.jpg';
			
			$fromName = $settingFromName;
			$fromEmail = $settingFromEmail;
			$toEmail = $userObj->Email;
			$subject = $configTemplate->subject;
			$body = $configTemplate->body;
			$body = str_replace("{fullname}", $userObj->Fullname, $body);
			$body = str_replace("{login_url}", $loginUrl, $body);
			$body = str_replace("{home_url}", $homeUrl, $body);
			// $body = str_replace("{header_image_url}", $headerImageUrl, $body);
			// $body = str_replace("{footer_image_url}", $footerImageUrl, $body);
			
			// เตรียมข้อมูลในการส่งอีเมล์
			$mail = new Zend_Mail_InlineImages("UTF-8");
			$mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);
			$mail->setBodyHtml($body ,"UTF-8")
			->setFrom($fromEmail, $fromName)
			->addTo($toEmail)
			->setSubject($subject);
			
			// ทำการส่งอีเมล์
			$mail->send($transport);
		}

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
		
		// Mobile Detect
		require_once APPLICATION_ROOT . DS . 'library' . DS . 'Mobile_Detect.php';
		$detect = new Mobile_Detect();
		$this->view->isiOS = $detect->isiOS();
		
		// Send Data To View
		$this->view->sexOption = Emwork_Utility::addEmptyOption($this->sexOption);
		$this->view->dayOption = Emwork_Utility::addEmptyOption($this->dayOption, Emwork_Translate::text($this->lang, 'webboard', 'register_day'));
		$this->view->monthOption = Emwork_Utility::addEmptyOption($this->monthOption, Emwork_Translate::text($this->lang, 'webboard', 'register_month'));
		$this->view->yearOption = Emwork_Utility::addEmptyOption($this->yearOption, Emwork_Translate::text($this->lang, 'webboard', 'register_year'));
		
		// Send Data To View
		$this->view->data = $data;
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
			if ($pictureData->ID != null) {
				$profilePictureId= $pictureData->ID;
			}
		}
		$params['profilePictureId'] = $profilePictureId;
		
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
			$result['message'] = Emwork_Translate::text($this->lang, 'webboard', 'register_validate_email_3');
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
	
	}
	
}

