<?php

class System_ManageController extends Zend_Controller_Action
{
	protected $statusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $smtpAuthTypeOption = array('plain' => 'Plain', 'login' => 'Login');
	protected $smtpSslTypeOption = array('ssl' => 'SSL', 'tls' => 'TLS');
	
	public function init()
	{ 
		
	}
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();		
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		// Use isAllow
		$canView = $sUser->isAllow("setting.system.manage");
		$canEdit = $sUser->isAllow("setting.system.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		$fileModel = new System_Model_Fileinfo();
		
		// Get Data
		$data = new stdClass();
		$data->siteName = System_Model_Setting::getValue("SiteName", "Site");
		$data->siteDescription = System_Model_Setting::getValue("Description", "Site");
		$data->siteKeyword = System_Model_Setting::getValue("Keyword", "Site");
		$data->siteFavicon = array();
		$faviconFileInfoID = System_Model_Setting::getValue("Favicon", "Site");
		if (!empty($faviconFileInfoID)) {
			$fileData = $fileModel->item($faviconFileInfoID);
			if ($fileData->ID) {
				$data->siteFavicon[] = array('file_name'=> $fileData->Name, 'size'=> $fileData->FileSize, 'type'=> $fileData->FileType, 'hash_name'=> $fileData->HashName);
			}
		}
		$data->siteLogo = array();
		$logoFileInfoID = System_Model_Setting::getValue("Logo", "Site");
		if (!empty($logoFileInfoID)) {
			$fileData = $fileModel->item($logoFileInfoID);
			if ($fileData->ID) {
				$data->siteLogo[] = array('file_name'=> $fileData->Name, 'size'=> $fileData->FileSize, 'type'=> $fileData->FileType, 'hash_name'=> $fileData->HashName);
			}
		}
		$data->siteLoginAttemptAmount = System_Model_Setting::getValue("LoginAttemptAmount", "Site");
		
		$data->smtpHost = System_Model_Setting::getValue("Host", "SMTP");
		$data->smtpPort = System_Model_Setting::getValue("Port", "SMTP");
		$data->smtpFromEmail = System_Model_Setting::getValue("FromEmail", "SMTP");
		$data->smtpFromName = System_Model_Setting::getValue("FromName", "SMTP");
		$data->smtpIsSsl = System_Model_Setting::getValue("IsSsl", "SMTP");
		$data->smtpSslType = System_Model_Setting::getValue("SslType", "SMTP");
		$data->smtpIsAuth = System_Model_Setting::getValue("IsAuth", "SMTP");
		$data->smtpAuthType = System_Model_Setting::getValue("AuthType", "SMTP");
		$data->smtpAuthUsername = System_Model_Setting::getValue("AuthUsername", "SMTP");
		$data->smtpAuthPassword = System_Model_Setting::getValue("AuthPassword", "SMTP");		
		
		$data->analyticGoogleAnalytic = System_Model_Setting::getValue("GoogleAnalytic", "Analytic");
		
		$data->facebookAppID = System_Model_Setting::getValue("AppID", "Facebook");
		
		$data->googleMapApiKey = System_Model_Setting::getValue("ApiKey", "GoogleMap");
		
		$data->reCaptchaSiteKey = System_Model_Setting::getValue("ReCaptchaSiteKey", "General");	
		$data->reCaptchaSecretKey = System_Model_Setting::getValue("ReCaptchaSecretKey", "General");		
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$data->HomeMenus = array();
		if (count($languages) > 0) {
			$menuModel = new System_Model_Menu();
			foreach ($languages as $language) {
				if ($language["Code"] != $defaultLanguage["Code"]) {
					$data->HomeMenus[$language["Code"]] = array("MenuID" => "", "MenuGroupID" => "", "LanguageName" => $language["Name"]);
					$data->HomeMenus[$language["Code"]]["MenuID"] = System_Model_Setting::getValue($language["Code"] . "MenuID", "HomeLanguage");
					if (!empty($data->HomeMenus[$language["Code"]]["MenuID"])) {
						$menuObj = $menuModel->getInfo($data->HomeMenus[$language["Code"]]["MenuID"]);
						$data->HomeMenus[$language["Code"]]["MenuGroupID"] = $menuObj->MenuGroupID;
					}
				}
			}
		}
		$menuGroupModel = new System_Model_Menugroup();
		$menuGroupOption = $menuGroupModel->getOption();
		$this->view->menuGroupOption = $menuGroupOption;
		
		// Send Data to View
		$this->view->data = $data;
		$this->view->smtpAuthTypeOption = $this->smtpAuthTypeOption;
		$this->view->smtpSslTypeOption = $this->smtpSslTypeOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าระบบ";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array(
				array('name' => 'ตั้งค่าระบบ', 'active' => true),
				array('name' => 'ตั้งค่าภาษา', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'manage', 'action' => 'language'), null, true)),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
	}
	
	public function saveAction() {

		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}		
		if ($sUser->getInfo()->UserType != 'B') {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}		
		if (!$sUser->isAllow("setting.system.manage")) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		$fileModel = new System_Model_Fileinfo();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		
		// Check Param
		
		// Set Params
		$params = array();
		$params["Site"] = array();
		$params["Site"]["SiteName"] = $requestParams['txtSiteName'];
		$params["Site"]["Description"] = $requestParams['txtSiteDescription'];
		$params["Site"]["Keyword"] = $requestParams['txtSiteKeyword'];
		$faviconFileInfoID = null;
		if (count($requestParams['hdfSiteFavicon'])>0) {
			$fileData = $fileModel->itemByHashName($requestParams['hdfSiteFavicon'][0]);
			if ($fileData->ID != null) {
				$faviconFileInfoID = $fileData->ID;
			}
		}		
		$params["Site"]["Favicon"] = $faviconFileInfoID;
		$logoFileInfoID = null;
		if (count($requestParams['hdfSiteLogo'])>0) {
			$fileData = $fileModel->itemByHashName($requestParams['hdfSiteLogo'][0]);
			if ($fileData->ID != null) {
				$logoFileInfoID = $fileData->ID;
			}
		}		
		$params["Site"]["Logo"] = $logoFileInfoID;
		$params["Site"]["LoginAttemptAmount"] = empty($requestParams['txtSiteLoginAttemptAmount']) ? null : $requestParams['txtSiteLoginAttemptAmount'];
		
		$params["SMTP"] = array();
		$params["SMTP"]["Host"] = $requestParams['txtSmtpHost'];
		$params["SMTP"]['Port'] = empty($requestParams['txtSmtpPort']) ? 25 : $requestParams['txtSmtpPort'];
		$params["SMTP"]['FromName'] = $requestParams['txtSmtpFromName'];
		$params["SMTP"]['FromEmail'] = $requestParams['txtSmtpFromEmail'];
		$params["SMTP"]['IsSsl'] = $requestParams['rdlSmtpUseSsl'];
		$params["SMTP"]['SslType'] = $params["SMTP"]['IsSsl'] == 'Y' ? $requestParams['lstSmtpSslType'] : null;
		$params["SMTP"]['IsAuth'] = $requestParams['rdlSmtpUseAuth'];
		$params["SMTP"]['AuthType'] = $params["SMTP"]['IsAuth'] == 'Y' ? $requestParams['lstSmtpAuthType'] : null;
		$params["SMTP"]['AuthUsername'] = $params["SMTP"]['IsAuth'] == 'Y' ? $requestParams['txtSmtpAuthUsername'] : null;
		if ($params["SMTP"]['IsAuth'] == 'Y') {
			if (empty($requestParams['txtSmtpAuthPassword'])) {
				$params["SMTP"]['AuthPassword'] = System_Model_Setting::getValue("AuthPassword", "SMTP");
			} else {
				$params["SMTP"]['AuthPassword'] = $requestParams['txtSmtpAuthPassword'];
			}
		} elseif ($params["SMTP"]['IsAuth'] != 'Y') {
			$params["SMTP"]['AuthPassword'] = null;
		}
		
		$params["Analytic"] = array();
		$params["Analytic"]["GoogleAnalytic"] = $requestParams['txtAnalyticGoogleAnalytic'];
		
		$params["Facebook"] = array();
		$params["Facebook"]["AppID"] = $requestParams['txtFacebookAppID'];
		
		$params["GoogleMap"] = array();
		$params["GoogleMap"]["ApiKey"] = $requestParams['txtGoogleMapApiKey'];
		
		$params["General"] = array();
		$params["General"]["ReCaptchaSiteKey"] = $requestParams['txtGeneralReCaptchaSiteKey'];
		$params["General"]["ReCaptchaSecretKey"] = $requestParams['txtGeneralReCaptchaSecretKey'];
		
		// Setting: HomeLanguage
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$params["HomeLanguage"] = array();
		if (count($languages) > 0) {
			foreach ($languages as $language) {
				if ($language["Code"] != $defaultLanguage["Code"]) {
					if (isset($requestParams[$language["Code"] . 'lstHomeMenuID'])) {
						$params["HomeLanguage"][$language["Code"] . "MenuID"] = $requestParams[$language["Code"] . 'lstHomeMenuID'];
					}
				}
			}
		}		
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("system.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
	
	public function languageAction() {
		
		// Log Access
		System_Model_Log::access();

		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		// Change to isAllow
		$canView = $sUser->isAllow("setting.system.managelanguage");
		$canEdit = $sUser->isAllow("setting.system.managelanguage");
		
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Set View
		$this->view->statusOption = Emwork_Utility::addEmptyOption($this->statusOption);
		
		// Set View
		$this->view->pageName = "ตั้งค่าภาษา";
		$this->view->pageIcon= "fa fa-language";
		$this->view->subMenus = array(
				array('name' => 'ตั้งค่าระบบ', 'active' => false, 'url' => $this->view->url(array('module' => 'system', 'controller' => 'manage', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าภาษา', 'active' => true),
		);
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าภาษา')
		);
	}
	
	public function getlanguagelistAction() {
		$filterInt = new Zend_Filter_Int();
		
		// Init Return Value
		$responce = array();
		$responce['draw'] = 0;
		$responce['recordsTotal'] = 0;
		$responce['recordsFiltered'] = 0;
		$responce['data'] = array();
		
		$sUser = System_Model_SUser::getInstance();		
		if (!$sUser->hasData()) {
			$this->_helper->json($responce);
		}		
		$canView = $sUser->isAllow("setting.system.managelanguage");		
	
		// Init
		$_draw = $this->getRequest()->getParam('draw');		
		$_start = $this->getRequest()->getParam('start'); // Rowindex		
		$_length = $this->getRequest()->getParam('length'); // Page Size		
		$_order = $this->getRequest()->getParam('order'); // Ordering		
		$_columns = $this->getRequest()->getParam('columns'); // Columns		
		$orderIndex = $_order[0]['column']; // Find Ordering Index		
		$sidx = $_columns[$orderIndex]['data']; // Find Ordering Column		
		$sord = $_order[0]['dir']; // Find Ordering Direction		
		$rows = $_length;	// Assign to Page Size (Row)		
		$page = ($_start / $rows) + 1; // Assign to Page Index
	
		// Basic Search
		$textstr = $this->getRequest()->getParam('textsearch');
		$textstr = $textstr;
		$filter = ''; // $this->getRequest()->getParam('filter');
		
		//Advance Search
		$advancesearch['searchText'] = $textstr;
		$advancesearch['enable'] = $this->getRequest()->getParam('enable');
		
		$model = new System_Model_Setting();
		$data = $model->getLanguageList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
		
		$i=$_start;
		$responce['draw'] = $_draw;
		$responce['recordsTotal'] = 0;
		$responce['recordsFiltered'] = 0;
		$responce['data'] = array();
		if ($canView && $data) {
			$responce['recordsTotal'] = $data[0]["RECORD"];
			$responce['recordsFiltered'] = $data[0]["RECORD"];
			
			foreach($data as $item){
				$i++;
				$d = array();
				$d['DT_RowId'] = $item["ID"];
				$d['NO'] = $i;
				$d['Name'] = Emwork_Utility::notShowNull($item["Name"]);
				$d['NameLocal'] = Emwork_Utility::notShowNull($item["NameLocal"]);
				$d['IsDefault'] = Emwork_Utility::notShowNull($item["IsDefault"]);
				$d['IsRequired'] = Emwork_Utility::notShowNull($item["IsRequired"]);
				$d['Ordering'] = Emwork_Utility::notShowNull($item["Ordering"]);
				$d['IsEnable'] = Emwork_Utility::notShowNull($item["IsEnable"]);
	
				$responce['data'][] = $d;
			}
		}
	
		$this->_helper->json($responce);
	}
	
	public function updatelanguageAction() {

		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}		
		if ($sUser->getInfo()->UserType != 'B') {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}		
		if (!$sUser->isAllow("setting.system.managelanguage")) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		$params = array();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		$paramType = $this->getRequest()->getParam('type', '');
		$paramID = $this->getRequest()->getParam('id', '');
		$paramValue = $this->getRequest()->getParam('value', '');
		
		// Check Param
		if (empty($paramID) || empty($paramType) || empty($paramValue)) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$languageInfo = $model->getLanguageInfo($paramID);
		$params['ID'] = $paramID;
		
		if ($paramType == 'default' && $paramValue == 'Y') {
			$params['IsDefault'] = 'Y';
			$params['IsEnable'] = 'Y';
			$params['IsRequired'] = 'Y';
		}
		if ($paramType == 'required') {
			if ($paramValue == 'Y') {
				$params['IsEnable'] = 'Y';
				$params['IsRequired'] = 'Y';
			} else {
				if ($languageInfo->IsDefault == 'Y') {
					$result['message'] = 'ไม่สามารถกำหนดเป็นไม่จำเป็นได้ เนื่องจากตั้งเป็นค่าเริ่มต้นอยู่ จะต้องกำหนดค่าเริ่มต้นให้ภาษาอื่นก่อน';
					$this->_helper->json($result);
				} else {
					$params['IsRequired'] = 'N';
				}
			}
		}
		if ($paramType == 'enable') {
			if ($paramValue == 'Y') {
				$params['IsEnable'] = 'Y';
			} else {
				if ($languageInfo->IsDefault == 'Y') {
					$result['message'] = 'ไม่สามารถกำหนดเป็นไม่ใช้งานได้ เนื่องจากตั้งเป็นค่าเริ่มต้นอยู่ จะต้องกำหนดค่าเริ่มต้นให้ภาษาอื่นก่อน';
					$this->_helper->json($result);
				} else {
					$params['IsEnable'] = 'N';
				}
				if ($languageInfo->IsRequired == 'Y') {
					$result['message'] = 'ไม่สามารถกำหนดเป็นไม่ใช้งานได้ เนื่องจากตั้งเป็นจำเป็นอยู่ จะต้องกำหนดค่าไม่จำเป็นก่อน';
					$this->_helper->json($result);
				} else {
					$params['IsEnable'] = 'N';
				}
			}
		}
		
		// Set Params
		
		// Save
		$data = $model->saveLanguageParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("system.language", "update", null, $params['ID']);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

