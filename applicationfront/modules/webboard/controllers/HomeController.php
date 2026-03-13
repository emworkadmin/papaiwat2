<?php

class Webboard_HomeController extends Zend_Controller_Action
{
	protected $lang;
	protected $defaultAttemptLoginAmount = 3;
	
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

		System_Model_Log::access();
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);

		$model = new Webboard_Model_Home();
		$data = $model->getData();
		$this->view->data = $data;
		$LoginWebboard = new stdClass ();

		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];

		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;
		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;
			// print_r($LoginWebboard->date);exit;
			$this->view->userid = $LoginWebboard->userid;
			$this->view->username = $LoginWebboard->username;
			$this->view->fullname = $LoginWebboard->fullname;
			$this->view->date = $LoginWebboard->date;
			// Zend_Session::namespaceUnset ( "LoginWebboard" );

		}
		// $this->_helper->viewRenderer->setNoRender();
		// $this->_helper->layout()->disableLayout();
		// $this->_helper->redirector('logout');

	}

	public function loginAction()
	{	

		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];

		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;

		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;
			// print_r($LoginWebboard->date);exit;
			$this->view->userid = $LoginWebboard->userid;
			$this->view->username = $LoginWebboard->username;
			$this->view->fullname = $LoginWebboard->fullname;
			$this->view->date = $LoginWebboard->date;
			// Zend_Session::namespaceUnset ( "LoginWebboard" );

		}

		System_Model_Log::access();
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);

		$model = new Webboard_Model_Home();
		$data = $model->getData();
		$this->view->data = $data;

		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];

		// if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {
		// 	$this->_helper->redirector('index', 'home', 'webboard');
		// }
		// $auth = Zend_Auth::getInstance();
		// if ($auth->hasIdentity()) {
		// 	$authObj = $auth->getIdentity();
		// 	if (!empty($authObj->UserID)) {
		// 		$this->_helper->redirector('index', 'home', 'webboard');
		// 	}
		// }

		$errorMessage = "";

		if ($this->_request->isPost()) {

			$filter = new Zend_Filter_StripTags();
			$username = $filter->filter($this->_request->getPost('username', ''));
			$password = $filter->filter($this->_request->getPost('password', ''));
			
			$rememberme = $filter->filter($this->_request->getPost('keep_login'));
			

			// Check Param
			if (empty($username) || empty($password)) {
				// LoginFail
				$errorMessage = Emwork_Translate::text($this->lang, 'webboard', 'home_validate_login_fail');

				// System_Model_Log::securitry("Login", null, $username, null, "Fail", "", $_SERVER["REMOTE_ADDR"]);
			} else {


				$model = new Webboard_Model_Home();
				$data = $model->getDataByUserID($username,$password);
				// echo "<pre>";print_r($data);exit;
				// print_r($data);exit;
				if ($data->Username && $data->Password && $data->IsEnable = 'Y' && $data->IsDeleted = 'N' && $data->AttemptAmount < 3) {

					
					//  Add Cookie
					if($rememberme==true){
						$CK_user = $username;
						$CK_pass = $password;
						$days = 30;
						$second = 86400;
						setcookie('ck_user', $CK_user, time() + ($second * $days), "/");
						setcookie('ck_pass', $CK_pass, time() + ($second * $days), "/");

						$CK_user = $_COOKIE['ck_user'];
						$CK_pass = $_COOKIE['ck_pass'];
	
					}else{
							setcookie('ck_user', $CK_pass, time() + (1), "/");
							setcookie('ck_pass', $CK_pass, time() + (1), "/");
						unset($_COOKIE['ck_user']);
						unset($_COOKIE['ck_pass']);
					}
						
					

					$LoginNamespace = new Zend_Session_Namespace("LoginWebboard");
					$LoginNamespace->username = $username;
					$LoginNamespace->password = $password;

					if ($data->IsEnable == "Y") {
						
						// Set General Data
						$data->WebboardFullName = $data->FirstName . ' ' . $data->LastName;
						$data->UserWebboardID = $data->ID;
						$LoginNamespace->fullname = $data->WebboardFullName;
						$LoginNamespace->userid = $data->UserWebboardID;

						if($data->LastLoginDate){
							if($this->lang == 'th') {
								$data->LastLoginDate = (Emwork_Utility_DateTime::MSSqlToDateThai( $data->LastLoginDate, 'DT_Nd')).' '.$data->LastLoginDate->format('H:i').' ' . Emwork_Translate::text($lang, 'webboard', 'home_end_type_date');
							} else {
								$data->LastLoginDate = (Emwork_Utility_DateTime::MSSqlToDateEng( $data->LastLoginDate, 'DT_Nd')).' '.$data->LastLoginDate->format('H:i').' ' . Emwork_Translate::text($lang, 'webboard', 'home_end_type_date');
							}
							$LoginNamespace->Date = $data->LastLoginDate;
						}

						Webboard_Model_Users::updateProperty($data->UserWebboardID, array("LastLoginDate" => date("Y-m-d H:i:s"), "AttemptAmount" => "0", "IsEnable" => "Y"));
							
						$this->_helper->redirector('index', 'home', 'webboard');
					
					} else { // ถูกบล๊อคการใช้งาน

	
						// Error Message
						$errorMessage = Emwork_Translate::text($this->lang, 'webboard', 'home_validate_cannot_login');
					}
				} else {
					$isEnable = "Y";
					$tempUserId = null;
					$tempUsername = null;
					$userObject = Webboard_Model_Users::getInfoByUsername($username);
					// print_r($userObject);exit;
					if ($userObject != null && !empty($userObject->ID)) {
						$tempUserId = $userObject->ID;
						$tempUsername = $userObject->Username;
						$attemptLoginAmount = $userObject->AttemptAmount;
						$attemptLoginAmount += 1;
						if ($attemptLoginAmount >= $this->defaultAttemptLoginAmount){
							$isEnable = "N";
						}
						Webboard_Model_Users::updateProperty($tempUserId, array("LastAttemptDate" => date("Y-m-d H:i:s"), "AttemptAmount" => $attemptLoginAmount, "IsEnable" => $isEnable));
					}
					
					// LoginFail
					if ($isEnable == "N") {
						// failure: User is Locked Out.
						$errorMessage = Emwork_Translate::text($this->lang, 'webboard', 'home_validate_cannot_login');

					} else {
						// failure: clear database row from session
						$errorMessage = Emwork_Translate::text($this->lang, 'webboard', 'home_validate_login_fail_2');

					}
				}
			}
		} else {

			// Log Access
			System_Model_Log::access();
		}

		
		// Set Data to view
		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;
		
		$this->view->errorMessage = $errorMessage;
		// Set View
		$this->view->hidePageName = true;
		$this->view->hideBreadcrumb = true;
	}

		
	public function logoutAction()
	{
		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {
			Zend_Session::namespaceUnset ( "LoginWebboard" );
		}
		$this->_helper->redirector('index', 'home', 'webboard');

	}

	public function getlistAction() {
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
	
		$sord = 'DESC';

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		// Init
		$rows = 10 ;
		$page = $this->getRequest()->getParam('pageIndex', 1);

		$model = new Webboard_Model_Home();

		$itemData = array();
		$itemData = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$id,$this->lang);
		$this->view->itemData = $itemData ;

		$filter = $model->getfilter();

		$this->view->filter = $filter ;
		$responce["html"] = $this->view->render('home/_getdata.phtml');

		if (count($itemData) > 0) {
			$responce["total"] = $itemData[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $rows);
		}
		$responce['pageIndex'] = $page;

		$this->view->total = $responce["total"] ;

		$this->_helper->json($responce);
	}

		
	public function postAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Set menu
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);

		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];

		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;

		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;
			// print_r($LoginWebboard->date);exit;
			$this->view->userid = $LoginWebboard->userid;
			$this->view->username = $LoginWebboard->username;
			$this->view->fullname = $LoginWebboard->fullname;
			$this->view->date = $LoginWebboard->date;
			// Zend_Session::namespaceUnset ( "LoginWebboard" );

		}


		$filterInt = new Zend_Filter_Int();

		// Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));

		$model = new Webboard_Model_Home();
		$data = $model->getData();
		$this->view->data = $data;


		$this->view->id = $id ;

		$model = new Webboard_Model_Home();
		$data = $model->getCategory($id,$this->lang);
		$this->view->title = $data->Title;

	}

	public function topicAction()
	{

		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];

		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;
		
		new Zend_Session_Namespace ( "LoginWebboard" );
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {
			
			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;
			// print_r($LoginWebboard->date);exit;
			$this->view->userid = $LoginWebboard->userid;
			$this->view->username = $LoginWebboard->username;
			$this->view->fullname = $LoginWebboard->fullname;
			$this->view->date = $LoginWebboard->date;
			// Zend_Session::namespaceUnset ( "LoginWebboard" );

		}

		$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
		$LoginWebboard->userid = $LoginWebboardNamespace->userid;
		System_Model_Log::access();

		$filterInt = new Zend_Filter_Int();

		// Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));
		$iid = $filterInt->filter($this->_request->getParam('iid'));

		Webboard_Model_Home::saveView($iid, $LoginWebboard->userid);

		$this->view->id = $id ;
		$this->view->iid = $iid ;

		$model = new Webboard_Model_Home();
		$filter = $model->getfilter();
		$itemData = count($model->count($iid));
		$data = $model->getTopic($iid);
		

		if($filter) {
			// echo"00";exit;
			foreach ($filter as $item) {
				// $title = str_replace($item['Detail'],'<s>'.$item['Replace'].'</s>', $title);
				$data['0']['Detail'] = str_replace($item['Detail'],'<s>'.$item['Replace'].'</s>', $data['0']['Detail']);
			};
		};
		// echo"<pre>";print_r($data['0']['Detail']);exit;

		// print_r($data);exit;
		if (!empty($data['0']['FileInfoHashName'])) :
			// echo "0";exit;
			$imageUrl = $this->view->url(array("module" => "default", "controller" => "file", "action" => "get","file" => $data['0']['FileInfoHashName']), null, true);
		endif;

		$this->view->imageUrl = $imageUrl;
		// echo($imageUrl);exit;
		
		// echo "<pre>";print_r($data['0']);exit;
		$this->view->reply = $itemData;
		$this->view->data = $data['0'];
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);

	}


	public function listpostAction() {
		
		// Zend Filter
		// $filterInt = new Zend_Filter_Int();

		// Get Param
		// $id = $filterInt->filter($this->_request->getParam('id'));

		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
	
		$sord = 'DESC';

		// Zend Filter
		// $filterInt = new Zend_Filter_Int();

		// Get Param
		// $id = $filterInt->filter($this->_request->getParam('id'));

		$id = $this->getRequest()->getParam('id');
		// Init
		$rows = 10 ;
		$page = $this->getRequest()->getParam('pageIndex', 1);

		$model = new Webboard_Model_Home();

		$itemData = array();
		$itemData = $model->getListPost($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$id,$this->lang);
		$this->view->itemData = $itemData ;
		$this->view->mainid = $id ;

		$filter = $model->getfilter();

		$this->view->filter = $filter ;

		$responce["html"] = $this->view->render('home/_getdatapost.phtml');
		// $this->view = $text ;
		// $this->view = $period ;
		// $this->view = $start; 
		// $this->view = $end;
		// print_r($responce["html"]);exit;
		if (count($itemData) > 0) {
			$responce["total"] = $itemData[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $rows);
		}
		$responce['pageIndex'] = $page;


		$this->view->total = $responce["total"] ;

		$this->_helper->json($responce);
	}

	public function listreplyAction() {
		
		// Zend Filter
		// $filterInt = new Zend_Filter_Int();

		// Get Param
		// $id = $filterInt->filter($this->_request->getParam('id'));

		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
	
		$sord = 'DESC';

		// Zend Filter
		// $filterInt = new Zend_Filter_Int();

		// Get Param
		// $id = $filterInt->filter($this->_request->getParam('id'));

		$iid = $this->getRequest()->getParam('iid');

		// Init
		$rows = 10 ;
		$page = $this->getRequest()->getParam('pageIndex', 1);

		$model = new Webboard_Model_Home();

		$filter = $model->getfilter();

		$this->view->filter = $filter ;
		
		$itemData = array();
		$itemData = $model->getListReply($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$iid,$this->lang);


		$this->view->itemData = $itemData ;
		// $this->view->mainid = $id ;

		$responce["html"] = $this->view->render('home/_getdatareply.phtml');
		// $this->view = $text ;
		// $this->view = $period ;
		// $this->view = $start; 
		// $this->view = $end;
		// print_r($responce["html"]);exit;
		if (count($itemData) > 0) {
			$responce["total"] = $itemData[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $rows);
		}
		$responce['pageIndex'] = $page;


		$this->view->total = $responce["total"] ;

		$this->_helper->json($responce);
	}

	public function savereplyAction() {

		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {
			
			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;

			$params = $this->_request->getParams();
			$model = new Webboard_Model_Home();
			$data = $model->savereply($params,$LoginWebboard->userid);

			// Set Result
			if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
			
			} else {
				$result['result'] = false;
				$result['message'] = Emwork_Translate::text($this->lang, 'webboard', 'home_validate_error');
			}

		} 	
		$this->_helper->json($result);
	}
	
}

