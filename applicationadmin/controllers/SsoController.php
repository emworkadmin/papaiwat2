<?php
/**
 * EM D2MS
 *
 * @copyright  Copyright (c) 2018 EMWORK Group
 * @license	EMWORK Group
 * @version	1.0.0 SsoController.php 2018-08-28
 * @author	EMWORK Group
 */

class SsoController extends Zend_Controller_Action
{
	protected $defaultAttemptLoginAmount = 3;
	
	public function init()
	{ 
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$this->view->lang = $defaultLanguage['Code'];
	}
	
	public function indexAction()
	{

		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout()->disableLayout();
		
		// Get Param 
		$callBackUrl = $this->getRequest()->getParam("callback");
		$siteKey = $this->getRequest()->getParam("sitekey");
		
		$callBackUrl = empty($callBackUrl) ? $_SERVER["HTTP_REFERER"] : urldecode($callBackUrl);
		
		if (empty($callBackUrl)) {
			// TODO : Redirect to fail : Can't find Site
			//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/fail?status=sitefail"));
			$this->_redirect("/sso/fail?status=redirectfail");
		}
		
		$ssoNamespace = new Zend_Session_Namespace("SSO");
		$ssoNamespace->callBackUrl = $callBackUrl;
		$ssoNamespace->siteKey = $siteKey;
		
		$ssoModel = new Sso();
		$ssoSiteObj = $ssoModel->getSiteInfoByKey($siteKey);
		
		
		
		if (empty($ssoSiteObj->ID)) {
			// TODO : Redirect to fail : Can't find Site
			//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/fail?status=sitefail"));
			$this->_redirect("/sso/fail?status=sitefail");
		}
		$ssoNamespace->siteID = $ssoSiteObj->ID;
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			// TODO: Redirect to Login
			//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/login"));
			$this->_redirect("/sso/login");
		}
		if ($sUser->getInfo()->UserType != "B") {
			// TODO: Redirect to fail : Can't Login Back
			//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/fail?status=notright"));
			$this->_redirect("/sso/fail?status=notright");
		}
		
		$isAllow = $ssoModel->isUserAllowSite($ssoSiteObj->ID, $sUser->getInfo()->ID);
		
		if ($isAllow) {
			$ssoModel->saveAllow($ssoSiteObj->ID, $sUser->getInfo()->ID);
			// TODO: Save Token 
			//$token = $ssoModel->createToken($ssoSiteObj->ID, $sUser->getInfo()->ID);
			// TODO: Redirect to call back with token
			//$returnUrl = $callBackUrl . (strpos($callBackUrl, "?") === false ? "?" : "&") . "token=" . $token;
			//$this->getResponse()->setRedirect($returnUrl);
			//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/token"));
		} 
		$this->_redirect("/sso/token");
	}
	
	public function failAction() {		
		
		$this->_helper->_layout->setLayout('layoutplain');
		
		// Get Param
		$status = $this->getRequest()->getParam("status");
		$message = "";
		if ($status == 'sitefail') {
			$message = "ไม่สามารถระบุเว็บไซต์ต้นทางได้";
		} elseif ($status == 'notright') {
			$message = "ไม่มีสิทธิ์เข้าใช้งาน";
		} elseif ($status == 'redirectfail') {
			$message = "ไม่สามารถระบุเว็บไซต์ปลายทาง";
		} else {
			$message = "ไม่สามารถระบุปัญหาได้";
		}
		$this->view->message = $message;
	}
	
	public function getinfoAction() {
		
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout()->disableLayout();
		
		// Get Param
		$token = $this->getRequest()->getParam("token");
		$siteKey = $this->getRequest()->getParam("sitekey");
		
		$ssoModel = new Sso();
		$tokenObj = $ssoModel->checkToken($siteKey, $token);
		
		$result = new stdClass();
		$result->result = false;
		
		if ($tokenObj->Token) {
			// TODO: Get UserData
			$result->result = true;
			$result->user_id = $tokenObj->UserID;
		} else {
			$result->result = false;
		}
		
		$resultStr = base64_encode($siteKey . base64_encode(json_encode($result)) . base64_encode($siteKey));
		echo $resultStr;
		
		// Debug
		if (false) {
			print_r($result);
			echo "<br />";
			echo $resultStr;
			echo "<br />";
			$decode = base64_decode($resultStr);
			echo $decode;
			echo "<br />";
			$decode1 = substr($decode, strlen($siteKey));
			echo $decode1;
			echo "<br />";
			$decode2 = str_replace(base64_encode($siteKey), '', $decode1);
			$decode3 = base64_decode($decode2);
			$decode4 = json_decode($decode3);
			print_r($decode4);
		}
	}
	
// 	public function allowAction() {
// 		$this->_helper->_layout->setLayout('layoutplain');		
// 		$sUser = System_Model_SUser::getInstance();
// 		if (!$sUser->hasData()) {			
// 		}
// 		if ($sUser->getInfo()->UserType != "B") {			
// 		}		
// 		if (Zend_Session::namespaceIsset("SSO")) {
// 			$ssoNamespace = new Zend_Session_Namespace("SSO");
// 			$siteID = $ssoNamespace->siteID;
// 			$callBackUrl = $ssoNamespace->callBackUrl;			
// 			$this->view->callBackUrl = $callBackUrl;			
// 			if ($this->_request->isPost()) {				
// 			}			
// 		} else {
// 			//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/fail"));
// 			$this->_redirect("/sso/fail");
// 		}
// 	}
	
	public function tokenAction() {
		
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout()->disableLayout();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			
		}
		if ($sUser->getInfo()->UserType != "B") {
			
		}
		
		if (Zend_Session::namespaceIsset("SSO")) {
			$ssoNamespace = new Zend_Session_Namespace("SSO");
			$siteID = $ssoNamespace->siteID;
			$callBackUrl = $ssoNamespace->callBackUrl;
			
			$ssoModel = new Sso();
			$token = $ssoModel->createToken($siteID, $sUser->getInfo()->ID);
			$returnUrl = $callBackUrl . (strpos($callBackUrl, "?") === false ? "?" : "&") . "token=" . $token;
			//$this->getResponse()->setRedirect($returnUrl);
			$this->_redirect($returnUrl);
			
		} else {
			//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/fail"));
			$this->_redirect("/sso/fail");
		}
	}
	
	public function loginAction()
	{		
		$this->_helper->_layout->setLayout('layoutplain');
		
		if (!Zend_Session::namespaceIsset("SSO")) {
			//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/fail"));
			$this->_redirect("/sso/fail");
		} else {
			$ssoNamespace = new Zend_Session_Namespace("SSO");
			$callBackUrl = $ssoNamespace->callBackUrl;
			$this->view->callBackUrl = $callBackUrl;
		}
		
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$authObj = $auth->getIdentity();
			if (!empty($authObj->UserID)) {
				$this->_helper->redirector('index', 'index', 'default');
			}
		}

		$ReCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");

		$errorMessage = "";
		if ($this->_request->isPost()) {

			$filter = new Zend_Filter_StripTags();
			$username = $filter->filter($this->_request->getPost('username', ''));
			$password = $filter->filter($this->_request->getPost('password', ''));
						
			// Check Param
			if (empty($username) || empty($password)) {
				// LoginFail
				$errorMessage = 'กรุณากรกอกบัญชีผู้ใช้งานให้ถูกต้อง';

				System_Model_Log::securitry("Login", null, $username, null, "Fail", "", $_SERVER["REMOTE_ADDR"]);
			} else {
			
				$reCaptcha = $this->getRequest()->getParam('g-recaptcha-response');
				$recaptchaSiteVerify = 'https://www.google.com/recaptcha/api/siteverify';
				$recaptchaReq = array();
				$recaptchaReq["secret"] = System_Model_Setting::getValue("ReCaptchaSecretKey", "General");
				$recaptchaReq["response"] = $reCaptcha;
				$recaptchaRes = Emwork_Utility::callAPI("POST", $recaptchaSiteVerify, $recaptchaReq);
				$recaptchaResArray = json_decode($recaptchaRes, true);
				if (!$recaptchaResArray["success"] && $ReCaptchaSiteKey) {
					$errorMessage = 'reCaptcha ไม่ถูกต้อง !';
				} else {
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
					$select->where("(IsDeleted = 'N')");
					$select->where("(UserType = 'B')");
					
					// do the authentication
					$result = $auth->authenticate($authAdapter);
					
					if ($result->isValid()) {
						
						// Set Data (not the password though!)
						$data = $authAdapter->getResultRowObject(
								array(
										"ID",
										"UsergroupID",
										"Username",
										"TitleName",
										"FirstName",
										"LastName",
										"IsEnable", 
										"AttemptAmount", 
										"LastLoginDate",
										"Created",
										"Modified",
										"UserType"
								),
								null);
						
						if ($data->IsEnable == "Y") {
							
							// Set General Data
							$data->UserID = $data->ID;
							$data->UserGroupID = $data->UsergroupID;
							$data->FullName = $data->FirstName . ' ' . $data->LastName;
													
							$auth->getStorage()->write($data);
		
							// Update Last login date
							System_Model_Users::updateProperty($data->UserID, array("LastLoginDate" => date("Y-m-d H:i:s"), "AttemptAmount" => "0", "IsEnable" => "Y"));
								
							// Log securities
							System_Model_Log::securitry($data->UserID, "Login", $data->Username, null, "Success", "", $_SERVER["REMOTE_ADDR"]);
							
							
							if (Zend_Session::namespaceIsset("SSO")) {
								$ssoNamespace = new Zend_Session_Namespace("SSO");
								$siteID = $ssoNamespace->siteID;
								
								$ssoModel = new Sso();
								$ssoModel->saveAllow($siteID, $data->UserID);
								
								//$this->getResponse()->setRedirect($this->view->baseUrl("/sso/token"));
								$this->_redirect("/sso/token");
							}
						
						} else { // ถูกบล๊อคการใช้งาน
		
							// Log securities
							System_Model_Log::securitry($data->UserID, "Login", $data->Username, null, "Fail", "Lock", $_SERVER["REMOTE_ADDR"]);
		
							// Error Message
							$errorMessage = "บัญชีผู้ใช้ถูกระงับการใช้งาน หากต้องการเข้าใข้งาน กรุณาติดต่อเจ้าหน้าที่";
						}
					} else {
						$isEnable = "Y";
						$tempUserId = null;
						$tempUsername = null;
						$userObject = System_Model_Users::getInfoByUsername($username);
						if ($userObject != null && !empty($userObject->ID)) {
							$tempUserId = $userObject->ID;
							$tempUsername = $userObject->Username;
							
							$attemptLoginAmount = $userObject->AttemptAmount;
							$attemptLoginAmount += 1;
							if ($attemptLoginAmount >= $this->defaultAttemptLoginAmount){
								$isEnable = "N";
							}
							System_Model_Users::updateProperty($tempUserId, array("LastAttemptDate" => date("Y-m-d H:i:s"), "AttemptAmount" => $attemptLoginAmount, "IsEnable" => $isEnable));
						}
						
						// LoginFail
						if ($isEnable == "N") {
							// failure: User is Locked Out.
							$errorMessage = 'บัญชีผู้ใช้ถูกระงับการใช้งาน หากต้องการเข้าใช้งาน กรุณาติดต่อเจ้าหน้าที่';
							// Log securities
							System_Model_Log::securitry($tempUserId, "Login", $tempUsername, null, "Fail", "Lock", $_SERVER["REMOTE_ADDR"]);
						} else {
							// failure: clear database row from session
							$errorMessage = 'เข้าสู่ระบบไม่สำเร็จ';
							// Log securities
							System_Model_Log::securitry($tempUserId, "Login", $tempUsername, $password, "Fail", "", $_SERVER["REMOTE_ADDR"]);
						}
					}
				}
			}
		} else {

			// Log Access
			System_Model_Log::access();
		}
		
		
		// Set Data to view
		$this->view->errorMessage = $errorMessage;
		// Set View
		$this->view->hidePageName = true;
		$this->view->hideBreadcrumb = true;
		$this->view->ReCaptchaSiteKey = $ReCaptchaSiteKey;
	}
}