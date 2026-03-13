<?php

class Webboard_ProfileController extends Zend_Controller_Action
{
	protected $lang;
	protected $defaultAttemptLoginAmount = 3;
	protected $sexOption = array("M"=> "ชาย", "F" => "หญิง");
	
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
		$this->sexOption = array("M"=> Emwork_Translate::text($this->lang, 'webboard', 'profile_sex_man'), "F" => Emwork_Translate::text($this->lang, 'webboard', 'profile_sex_female'));
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

		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];

		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;

		System_Model_Log::access();
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);

		$model = new Webboard_Model_Home();
		$data = $model->getData();
		$this->view->data = $data;
		$LoginWebboard = new stdClass ();


		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			
			// print_r($LoginWebboard->date);exit;
			$this->view->username = $LoginWebboard->username;
			$this->view->fullname = $LoginWebboard->fullname;
			$this->view->date = $LoginWebboard->date;
			// Zend_Session::namespaceUnset ( "LoginWebboard" );

		}
		// $this->_helper->viewRenderer->setNoRender();
		// $this->_helper->layout()->disableLayout();
		// $this->_helper->redirector('logout');

    }
    
    public function mypostAction()
	{

		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];

		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;

		System_Model_Log::access();
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);

		$model = new Webboard_Model_Home();
		$data = $model->getData();
		$this->view->data = $data;
		$LoginWebboard = new stdClass ();


		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;

			// print_r($LoginWebboard->date);exit;
			$this->view->username = $LoginWebboard->username;
			$this->view->fullname = $LoginWebboard->fullname;
			$this->view->date = $LoginWebboard->date;
			// Zend_Session::namespaceUnset ( "LoginWebboard" );

		}

		$filterInt = new Zend_Filter_Int();

		// Get Param
		$userid = $filterInt->filter($this->_request->getParam('userid'));
		$this->view->userid = $userid ;
		// $this->_helper->viewRenderer->setNoRender();
		// $this->_helper->layout()->disableLayout();
		// $this->_helper->redirector('logout');

	}
    
    
    public function profileAction()
	{

		$CK_user = $_COOKIE['ck_user'];
		$CK_pass = $_COOKIE['ck_pass'];

		$this->view->ck_user = $CK_user;
		$this->view->ck_pass = $CK_pass;

		System_Model_Log::access();
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);

		// $model = new Webboard_Model_Home();
		// $data = $model->getData();
		// $this->view->data = $data;
		// $LoginWebboard = new stdClass ();

		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;
			
			$model = new Webboard_Model_Profile();
			$data = $model->getDataByUserID($LoginWebboard->username,$LoginWebboard->passowrd);
			// echo"<pre>";print_r($data);exit;
			$this->view->data = $data ;
			$this->view->sexOption = Emwork_Utility::addEmptyOption($this->sexOption);
			// print_r($LoginWebboard->date);exit;
			$this->view->userid = $LoginWebboard->userid;
			$this->view->username = $LoginWebboard->username;
			$this->view->fullname = $LoginWebboard->fullname;
			$this->view->date = $LoginWebboard->date;
			// Zend_Session::namespaceUnset ( "LoginWebboard" );

		}

		$filterInt = new Zend_Filter_Int();

		// Get Param
		$userid = $filterInt->filter($this->_request->getParam('userid'));

		$this->view->userid = $userid ;
		// $this->_helper->viewRenderer->setNoRender();
		// $this->_helper->layout()->disableLayout();
		// $this->_helper->redirector('logout');

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

		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;
		}

		$params = $this->_request->getParams();

		$id = $LoginWebboard->userid;
		// Init
		$rows = 10 ;
		$page = $this->getRequest()->getParam('pageIndex', 1);

		$model = new Webboard_Model_Profile();

		$itemData = array();
		$itemData = $model->getListPost($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$id,$this->lang);
		$this->view->itemData = $itemData ;
		// print_r($itemData);exit;
		// $this->view->mainid = $itemData['CategoryID'] ;

		$responce["html"] = $this->view->render('profile/_getdatapost.phtml');
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

	public function deleteAction()
	{
		// Init
		$result = array();
		$result['result'] = false;
		$result['message'] = '';
		
		// Param
		$params = $this->getRequest()->getParams();

		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;
		}

		$params['id']=$LoginWebboard->userid ;

		// print_r($params);exit;

		// Update
		$model = new Webboard_Model_Profile();
		$data = $model->delete($params);

		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {
			Zend_Session::namespaceUnset ( "LoginWebboard" );
		}
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';

			// Log Acion
			// System_Model_Log::action("content.article", "delete", null, $params["id"]);
		} else {
			$result['result'] = false;
			$result['message'] = Emwork_Translate::text($this->lang, 'webboard', 'profile_validate_delete');
		}


		
		$this->_helper->json($result);
	}

	public function saveAction()
	{
		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		$params = $this->getRequest()->getParams();
		// $params = $this->_request->getParams();
		$fileModel = new System_Model_Fileinfo();
		$params = $this->getRequest()->getParams();
	   
		$file= null;
		if (count($params['hdfFileID'])) {
			unset($fileData);
			$fileData = $fileModel->itemByHashName($params['hdfFileID'][0]);
			if ($fileData->ID != null) {
				$file = $fileData->ID;
			}
		}
		$params['fileID'] = $file;

		new Zend_Session_Namespace("LoginWebboard");
		if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

			$LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
			$LoginWebboard->username = $LoginWebboardNamespace->username;
			$LoginWebboard->passowrd = $LoginWebboardNamespace->password;
			$LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
			$LoginWebboard->date = $LoginWebboardNamespace->Date;
			$LoginWebboard->userid = $LoginWebboardNamespace->userid;
		}

		$params['userid'] = $LoginWebboard->userid ;

		$model = new Webboard_Model_Profile();
		$data = $model->savepost($params);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
 
		} else {
			$result['result'] = false;
			$result['message'] = Emwork_Translate::text($this->lang, 'webboard', 'home_validate_error');
		}
		
		$this->_helper->json($result);
	}

	public function createpostAction() {
		// Log Access
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
			
			$this->view->userid = $LoginWebboard->userid;
			$this->view->username = $LoginWebboard->username;
			$this->view->fullname = $LoginWebboard->fullname;
			$this->view->date = $LoginWebboard->date;
			// Zend_Session::namespaceUnset ( "LoginWebboard" );

		}
	   // echo"<pre>"; print_r($data);exit;

		$modeCategory = new Webboard_Model_Profile();
		$categoryOption = $modeCategory->getOption($this->lang);

		$this->view->categoryOption = $categoryOption;

		$this->view->data = $data;
		$this->view->statusOption = $this->StatusOption;
		$this->view->statusPostOption = $this->StatusPostOption;
		
			
	   // Set View
	   $this->view->pageName = Emwork_Translate::text($this->lang, 'webboard', 'profile_post_detail');
	   $this->view->pageIcon = "fa fa-edit";

		   
	   $this->view->breadcrumb = array(
		   array('name' => Emwork_Translate::text($this->lang, 'webboard', 'profile_first_page'), 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
		   array('name' => Emwork_Translate::text($this->lang, 'webboard', 'profile_post_list'), 'url' => $this->view->url(array('module' => 'webboard', 'controller' => 'post', 'action' => 'index'), null, true)),
		   array('name' => Emwork_Translate::text($this->lang, 'webboard', 'profile_post_detail'))
	   );
	  
  }

  public function updateAction()
   {
	   // Init
	   $result = array();
	   $result['result'] = false;
	   $result['id'] = null;
	   $result['message'] = '';
	   
	   $model = new Webboard_Model_Profile();
	   $params = $this->getRequest()->getParams();

	   new Zend_Session_Namespace("LoginWebboard");
	   if (Zend_Session::namespaceIsset ( "LoginWebboard" )) {

		   $LoginWebboardNamespace = new Zend_Session_Namespace ( "LoginWebboard" );
		   $LoginWebboard->username = $LoginWebboardNamespace->username;
		   $LoginWebboard->passowrd = $LoginWebboardNamespace->password;
		   $LoginWebboard->fullname = $LoginWebboardNamespace->fullname;
		   $LoginWebboard->date = $LoginWebboardNamespace->Date;
		   $LoginWebboard->userid = $LoginWebboardNamespace->userid;
		   
	   }

	   $params['userid'] = $LoginWebboard->userid;
	//    print_r($params);exit;
	   
	   $data = $model->update($params);
	   
	   // Set Result
	   if ($data->result) {
		   $result['result'] = true;
		   $result['message'] = '';
		   $result['id'] = $data->id;

	   } else {
		   $result['result'] = false;
		   $result['message'] = Emwork_Translate::text($this->lang, 'webboard', 'home_validate_error');
	   }
	   
	   $this->_helper->json($result);
   }
   

	
}

