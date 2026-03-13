<?php
/**
 * EM D2MS
 *
 * @copyright  Copyright (c) 2018 EMWORK Group
 * @license	EMWORK Group
 * @version	1.0.0 AuthController.php 2018-03-03
 * @author	EMWORK Group
 */

class AuthController extends Zend_Controller_Action
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
		
		$this->_helper->redirector('logout');
	}
	
	public function loginAction()
	{		
		$this->_helper->_layout->setLayout('layoutplain');
		
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
			
				$recaptchaResArray = array();
				
				if (!empty($ReCaptchaSiteKey)) {
					$reCaptcha = $this->getRequest()->getParam('g-recaptcha-response');
					$recaptchaSiteVerify = 'https://www.google.com/recaptcha/api/siteverify';
					$recaptchaReq = array();
					$recaptchaReq["secret"] = System_Model_Setting::getValue("ReCaptchaSecretKey", "General");
					$recaptchaReq["response"] = $reCaptcha;
					$recaptchaRes = Emwork_Utility::callAPI("POST", $recaptchaSiteVerify, $recaptchaReq);
					$recaptchaResArray = json_decode($recaptchaRes, true);
				}
				
				if (!$recaptchaResArray["success"] && !empty($ReCaptchaSiteKey)) {
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
							
							// Redirect					
							$this->_helper->redirector('index', 'index');
						
						} else { // ถูกบล๊อคการใช้งาน
		
							// Log securities
							System_Model_Log::securitry($data->UserID, "Login", $data->Username, null, "Fail", "Lock", $_SERVER["REMOTE_ADDR"]);
		
							// Error Message
							$errorMessage = "บัญชีผู้ใช้ถูกระงับการใช้งาน หากต้องการเข้าใข้งาน กรุณาติดต่อเจ้าหน้าที่";
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
	
	public function logoutAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$authobj = Zend_Auth::getInstance();
		if ($authobj != null) {
			$auth = $authobj->getIdentity();
			if (!empty($auth->UserID)) {
				System_Model_Log::securitry($auth->UserID, "Logoff", $auth->Username, null, "Success", "", $_SERVER["REMOTE_ADDR"]);
			}
		}

		Zend_Auth::getInstance()->clearIdentity();
		foreach(Zend_Session::getIterator() as $value) {
			Zend_Session::namespaceUnset($value);
		}
		Zend_Session::forgetMe();
		
		$this->_helper->redirector('login');
	}
}