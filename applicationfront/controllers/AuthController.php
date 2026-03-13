<?php
/**
 * SMART D2MS
 *
 * @copyright  Copyright (c) 2018 EMWORK Group
 * @license	EMWORK Group
 * @version	1.0.0 AuthController.php 2018-02-20
 * @author	EMWORK Group
 */

class AuthController extends Zend_Controller_Action
{
	private $defaultAttemptLoginAmount = 99;
	private $lang;
	
	public function init()
	{ 
		// Get languages
		// $this->lang = empty($_COOKIE['lang']) ? 'th' : $_COOKIE['lang'];
		if (Zend_Registry::isRegistered('lang')) {
			$this->lang = Zend_Registry::get('lang');
		} else {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$this->lang = $defaultLanguage["Code"];
		}

		$this->view->lang = $this->lang;
	}
	
	public function indexAction()
	{
		$this->_helper->redirector('logout');
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
	
	private function checkReCaptcha($reCaptcha) {
		$recaptchaSiteVerify = 'https://www.google.com/recaptcha/api/siteverify';
		$recaptchaReq = array();
		$recaptchaReq["secret"] = System_Model_Setting::getValue("ReCaptchaSecretKey", "General");
		$recaptchaReq["response"] = $reCaptcha;
		$recaptchaRes = Emwork_Utility::callAPI("POST", $recaptchaSiteVerify, $recaptchaReq);
		$recaptchaResArray = json_decode($recaptchaRes, true);
		if (!$recaptchaResArray["success"]) {
			return false;
		}
		return true;
	}
		
	/**
	 * 
	 */
	public function loginAction() 
	{
		
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$authObj = $auth->getIdentity();
			if (!empty($authObj->UserID)) {
				$menuDefault = System_Model_Menu::getHome();
				$this->getResponse()->setRedirect($menuDefault->Url);
				//$this->_helper->redirector('index', 'home', 'default');
			}
		}
		
		$ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");

		// Get languages
		// $lang = empty($_COOKIE['lang']) ? 'th' : $_COOKIE['lang'];
		$lang = $this->lang;
		//  Get Cookie
		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];
		
		// Get Session
		// $sessionFacebook = new Zend_Session_Namespace('facebook_error');
		// $is_facebook = $sessionFacebook->is_facebook;
		
		$errorMessage = "";

		$is_facebook = false;
		if ($is_facebook){
			
			// $errorMessage = $sessionFacebook->error_massage;

			// // Destroy Session
			// Zend_Session::namespaceUnset('facebook_error');
						
		} else {
		
			if ($this->_request->isPost()) {
	
				$filter = new Zend_Filter_StripTags();
				$reCaptcha = $filter->filter($this->_request->getPost('g-recaptcha-response'));
				if(!$reCaptcha && $ReCaptchaSiteKey) {
					$errorMessage = Emwork_Translate::text($this->lang, 'auth', 'validate_error_3');
				} else {
					
// 					if (false) { //if($ReCaptchaSiteKey) {
// 						// Check ReCaptcha
// 						$recaptchaSiteVerify = 'https://www.google.com/recaptcha/api/siteverify';		
// 						$recaptchaReq = array();
// 						$recaptchaReq["secret"] = System_Model_Setting::getValue("ReCaptchaSecretKey", "General");
// 						$recaptchaReq["response"] = $reCaptcha;
// 						$recaptchaRes = Emwork_Utility::callAPI("POST", $recaptchaSiteVerify, $recaptchaReq);
// 						$recaptchaResArray = json_decode($recaptchaRes, true);
// 						if (!$recaptchaResArray["success"]) {
// 							$errorMessage = Emwork_Translate::text($this->lang, 'auth', 'validate_error_3');
// 						}
// 					} else {

					if (empty($ReCaptchaSiteKey) || ($ReCaptchaSiteKey && $this->checkReCaptcha($reCaptcha))) {
						$username = $filter->filter($this->_request->getPost('username'));
						$password = $filter->filter($this->_request->getPost('password'));

						$username = empty($username) ? '-1' : $username;
						$password = empty($password) ? '-1' : $password;
						
						// get Post check Remember?
						$rememberme = $filter->filter($this->_request->getPost('keep_login'));
						
						// Authentication
						$db = Zend_Db_Table::getDefaultAdapter();						 
			
						$authAdapter = new Zend_Auth_Adapter_DbTable($db);
						$authAdapter->setTableName('sys_user');
						$authAdapter->setIdentityColumn('Username');
						$authAdapter->setCredentialColumn('Password');
						
						// Set the input credential values to authenticate against
						$authAdapter->setIdentity($username);
						$authAdapter->setCredential(md5($password));  
						
						$select = $authAdapter->getDbSelect();
						$select->where("IsDeleted = 'N'");
						$select->where("UserFrontType = 'F'");

						// do the authentication
						$result = $auth->authenticate($authAdapter);

						if($result) {
							//  Add Cookie
							if($rememberme==true){
								$CK_user = $username;
								$CK_pass = $password;
								$days = 30;
								$second = 86400;
								setcookie('ck_user', $CK_user, time() + ($second * $days), "/");
								setcookie('ck_pass', $CK_pass, time() + ($second * $days), "/");
							}else{
								setcookie('ck_user', $CK_pass, time() + (1), "/");
								setcookie('ck_pass', $CK_pass, time() + (1), "/");
								unset($_COOKIE['ck_user']);
								unset($_COOKIE['ck_pass']);
							}
							
						}
						
						// Model
						$userModel = new Profile_Model_Profile();
					
						if ($result->isValid()) {
							
							// Set Data
							// (not the password though!)
							$data = $authAdapter->getResultRowObject(
									array(
											"ID",
											"UsergroupID",
											"Username",
											"FirstName",
											"LastName",
											"IsEnable", 
											"AttemptAmount", 
											"LastLoginDate",
											"Created",
											"Modified"
									),
									null);
							
							if ($data->IsEnable == "Y") {
								
								// Get Data Member
								$modelProfile = new Profile_Model_Profile();
								$dataProfile = $modelProfile->getInfoByID($data->ID);
								
								// Set General Data
								$data->UserID= $data->ID;
								$data->fullname = $data->FirstName . ' ' . $data->LastName;
								$data->ProfileImageHashName = $dataProfile->ProfileImageHashName;
								$data->UserFrontType = $dataProfile->UserFrontType;
								
								$auth->getStorage()->write($data);
								
								// Update Last login date
								System_Model_Users::updateProperty($data->UserID, array("LastLoginDate" => date("Y-m-d H:i:s"), "AttemptAmount" => "0", "IsEnable" => "Y"));
								
								// Log securities
								System_Model_Log::securitry($data->UserID, "Login", $data->Username, null, "Success", "", $_SERVER["REMOTE_ADDR"]);
								
								//  Redirect
								$menuDefault = System_Model_Menu::getHome();
								$this->getResponse()->setRedirect($menuDefault->Url);
							
							} else { // ถูกบล๊อคการใช้งาน
			
								// Log securities
								System_Model_Log::securitry($data->UserID, "Login", $data->Username, null, "Fail", "Lock", $_SERVER["REMOTE_ADDR"]);
			
								// Error Message
								$errorMessage = Emwork_Translate::text($this->lang, 'auth', 'validate_error_1');
							}
						} else {
							$isEnable = "Y";
							$tempUserId = null;
							$tempUsername = $username;
							$userObject = System_Model_Users::getInfoByUsername($username);
							if ($userObject != null && !empty($userObject->ID)) {
								$tempUserId = $userObject->ID;
								$tempUsername = $userObject->Username;
								
								$attemptLoginAmount = $userObject->AttemptAmount;
								$attemptLoginAmount += 1;
								
								$siteLoginAttemptAmount = System_Model_Setting::getValue("LoginAttemptAmount", "Site");
								//if ($attemptLoginAmount >= $this->defaultAttemptLoginAmount){
								if (!empty($siteLoginAttemptAmount) && ($attemptLoginAmount >= $siteLoginAttemptAmount + 1)) {
									$isEnable = "N";
								}
								System_Model_Users::updateProperty($tempUserId, array("LastAttemptDate" => date("Y-m-d H:i:s"), "AttemptAmount" => $attemptLoginAmount, "IsEnable" => $isEnable));
							}
							
							// LoginFail
							if ($isEnable == "N") {
								// failure: User is Locked Out.
								$errorMessage = Emwork_Translate::text($this->lang, 'auth', 'validate_error_1');
								// Log securities
								System_Model_Log::securitry($tempUserId, "Login", $tempUsername, null, "Fail", "Lock", $_SERVER["REMOTE_ADDR"]);
							} else {
								// failure: clear database row from session
								$errorMessage = Emwork_Translate::text($this->lang, 'auth', 'validate_error_2');
								// Log securities
								System_Model_Log::securitry($tempUserId, "Login", $tempUsername, $password, "Fail", "", $_SERVER["REMOTE_ADDR"]);
							}
						}
					} else {
						$errorMessage = Emwork_Translate::text($this->lang, 'auth', 'validate_error_3');
					}
				}
			} else {
				
			}
			
		} // End check facebook
		
		
		// Set Data to view
		$this->view->errorMessage = $errorMessage;
		
		// Create Facebook Login URL
		// $setting = System_Model_Setting::getInfo();
		// require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'autoload.php';
		// $facebookConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/external.ini', 'facebook');
		// $fb = new Facebook\Facebook([
		// 		'app_id' => $facebookConfig->appId,
		// 		'app_secret' => $facebookConfig->appSecret,
		// 		'default_graph_version' => $facebookConfig->appVersion
		// ]);
		// $fbHelper = $fb->getRedirectLoginHelper();
		// $fbPermissions = ['email']; // Optional permissions
		// $fbLoginUrl = $fbHelper->getLoginUrl($setting->site_url . '/auth/fbcallback', $fbPermissions);
		
		// Send Cookie to view
		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;
		// Set View
		$this->view->lang = $lang;
		$this->view->hidePageName = true;
		$this->view->hideBreadcrumb = true;
		$this->view->fbLoginUrl = $fbLoginUrl;
		$this->view->ReCaptchaSiteKey = $ReCaptchaSiteKey;
		// Send Data to Template
		$this->view->bodyClass = "auth-body-login";
		
		$menuID = $this->findMenu("/auth/login");
		Zend_Registry::set("MenuID", $menuID);
	}
	
	/**
	 * 
	 * เชื่อมต่อ Facebook
	 */
	public function fbcallbackAction() {
		
		/******************************** ส่วนการเชื่อมต่อ Facebook **********************************/
		require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'autoload.php';
		
		$facebookConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/external.ini', 'facebook');
		$fb = new Facebook\Facebook([
				'app_id' => $facebookConfig->appId,
				'app_secret' => $facebookConfig->appSecret,
				'default_graph_version' => $facebookConfig->appVersion
		]);

		$helper = $fb->getRedirectLoginHelper();
		if (isset($_GET['state'])) {
			$helper->getPersistentDataHandler()->set('state', $_GET['state']);
		}
		
		// นำค่า AccessToken มา
		try {
			$accessToken = $helper->getAccessToken();
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			// When Graph returns an error
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			// When validation fails or other local issues
		}

		// ตรวจสอบว่า AccessToken
		if (! isset($accessToken)) {
			if ($helper->getError()) {
				header('HTTP/1.0 401 Unauthorized');
				echo "Error: " . $helper->getError() . "\n";
				echo "Error Code: " . $helper->getErrorCode() . "\n";
				echo "Error Reason: " . $helper->getErrorReason() . "\n";
				echo "Error Description: " . $helper->getErrorDescription() . "\n";
				// กรณีผุ็ใช้งานยกเลิกการ Permission ระบบจะ Redirect กลับหน้า Login
				if($helper->getErrorReason() == 'user_denied'){
					$this->_helper->redirector('login', 'auth');
				}
			} else {
				header('HTTP/1.0 400 Bad Request');
				echo 'Bad request';
			}
		}
		 
		// The OAuth 2.0 client handler helps us manage access tokens
		$oAuth2Client = $fb->getOAuth2Client();
		 
		// Get the access token metadata from /debug_token
		$tokenMetadata = $oAuth2Client->debugToken($accessToken);
		 
		// Validation (these will throw FacebookSDKException's when they fail)
		$tokenMetadata->validateAppId($facebookConfig->appId); // Replace {app-id} with your app id
		
		// If you know the user ID this access token belongs to, you can validate it here
		$tokenMetadata->validateExpiration();
		 
		if (! $accessToken->isLongLived()) {
			// Exchanges a short-lived access token for a long-lived one
			try {
				$accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
			} catch (Facebook\Exceptions\FacebookSDKException $e) {
				//echo "<p>Error getting long-lived access token: " . $helper->getMessage() . "</p>\n\n";
			}
		}
		
		// นำ AccessToken มาหาข้อมูล
		$facebookSessioin = new Zend_Session_Namespace("facebook");
		$facebookSessioin->fb_access_token = (string) $accessToken;
		
		// get facebook id and facebook info
		$fb->setDefaultAccessToken($accessToken->getValue());
		try {
			$response = $fb->get('/me?fields=id,name,email,first_name,last_name,picture.width(720).height(720).as(picture_large)');
			$plainOldArray = $response->getDecodedBody();
		
			$result = new stdClass();
			$facebookSessioin->facebook_id = $plainOldArray['id'];
			$facebookSessioin->facebook_name = $plainOldArray['name'];
			$facebookSessioin->first_name = $plainOldArray['first_name'];
			$facebookSessioin->last_name = $plainOldArray['last_name'];
			$facebookSessioin->email = $plainOldArray['email'];
			$facebookSessioin->picture = $plainOldArray['picture_large']['data']['url'];
		
		} catch(Facebook\Exceptions\FacebookResponseException $e) {

		} catch(Facebook\Exceptions\FacebookSDKException $e) {

		}

		/************************************ ส่วนการ Login **************************************/
		
		// Model
		$model = new System_Model_Register();
		
		if(!empty($plainOldArray['id'])){
		//if(!empty($plainOldArray['id']) && !empty($plainOldArray['email'])){
				
			if ($model->checkFacebookId($plainOldArray['id'])) { // Check: facebook ID

				// Model
				$modelUser = new System_Model_Users();
				$data = $modelUser->getInfoByFacebookID($plainOldArray['id']);
				
				// --------------------------------------------------------- เข้าสู่ระบบ --------------------------------------------------------- //
				if ($data->is_enable == "Y") {
					
					$this->loginfacebook($plainOldArray['id']);
					
				// ----------------------------------------------------- ถูกบล๊อคการใช้งาน --------------------------------------------------- //
				}else{
					
					// Log securities
					System_Model_Log::securitry($data->id, "Login", $data->username, null, "Fail", "Lock", $_SERVER["REMOTE_ADDR"]);
				
					$facebookErrorSessioin = new Zend_Session_Namespace("facebook_error");
					$facebookErrorSessioin->is_facebook = true;
					$facebookErrorSessioin->error_massage = Emwork_Translate::text($this->lang, 'auth', 'validate_error_1');
				
					$this->_helper->redirector('login', 'auth');
					
				}
			
			} else {

				$checkValid = false;
				if ($model->checkUsername($plainOldArray['email'])) { // Check: username ห้ามซ้ำ
					$checkValid = true;
				}
				if ($model->checkEmail($plainOldArray['email'])) {  // Check: email ห้ามซ้ำ
					$checkValid = true;
				}
				
				// --------------------------------------------- พบ E-mail นี้มีอยู่ในระบบแล้ว --------------------------------------------- //
				if($checkValid){
					
					$facebookErrorSessioin = new Zend_Session_Namespace("facebook_error");
					$facebookErrorSessioin->is_facebook = true;
					$facebookErrorSessioin->error_massage = $this->view->Translate('general', $this->lang, 'auth_error_email_duplicate');
					
					$this->_helper->redirector('login', 'auth');
				
				// ------------------------------------------------------ สมัครสมาชิก ------------------------------------------------------ //
				}else{
					
					$this->_helper->redirector('facebook', 'register');
					
				}
				
			}
			
		}else{
			
			$facebookErrorSessioin = new Zend_Session_Namespace("facebook_error");
			$facebookErrorSessioin->is_facebook = true;
			$facebookErrorSessioin->error_massage = $this->view->Translate('general', $this->lang, 'auth_error_connect_facebook_fail');
			
			$this->_helper->redirector('login', 'auth');
			
		}
		
	}
	
	private  function loginfacebook($facebookID) {
		
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$authObj = $auth->getIdentity();
			if (!empty($authObj->UserID)) {
				$this->_helper->redirector('index', 'home', 'default');
			}
		}
		
		// Model
		$modelUser = new System_Model_Users();
		$data = $modelUser->getInfoByFacebookID($facebookID);
		
		// Get Data Member
		$modelProfile = new Profile_Model_Profile();
		$dataProfile = $modelProfile->getInfo($data->id);

		// Set General Data
		$data->UserID = $data->id;
		$data->username = $data->username;
		$data->fullname = $data->first_name . ' ' . $data->last_name;
		$data->memberFullnameTH= $dataProfile->memberFullnameTH;
		$data->memberFullnameEN= $dataProfile->memberFullnameEN;
		$data->member_id = $dataProfile->member_id;
		$data->is_seller= $dataProfile->is_seller;

		$auth->getStorage()->write($data);

		// Update Last login date
		System_Model_Users::updateProperty($data->UserID, array("last_login_date" => date("Y-m-d H:i:s"), "attempt_amount" => "0", "is_enable" => "Y"));

		// Log securities
		System_Model_Log::securitry($data->UserID, "Login", $data->username, null, "Success", "", $_SERVER["REMOTE_ADDR"]);

		// Redirect
		$this->_helper->redirector('index', 'index');
	}
	
	public function logoutAction()
	{
		
		$authobj = Zend_Auth::getInstance();
		if ($authobj != null) {
			$auth = $authobj->getIdentity();
			if (!empty($auth->UserID)) {
				System_Model_Log::securitry($auth->UserID, "Logoff", $auth->username, null, "Success", "", $_SERVER["REMOTE_ADDR"]);
			}
		}

		Zend_Auth::getInstance()->clearIdentity();
		foreach(Zend_Session::getIterator() as $value) {
			Zend_Session::namespaceUnset($value);
		}
		Zend_Session::forgetMe();

		//  Redirect
		$menuDefault = System_Model_Menu::getHome();
		$this->getResponse()->setRedirect($menuDefault->Url);
	}
}

