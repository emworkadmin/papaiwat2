<?php

class IndexController extends Zend_Controller_Action
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
	
	public function indexAction()
	{
		System_Model_Log::access();
		//$this->_helper->redirector('index', 'home', 'default');
		
		
		// MenuDefault / Home
		$menuDefault = System_Model_Menu::getHome();
		
		$homeLanguageMenuID = System_Model_Setting::getValue($this->lang . "MenuID", "HomeLanguage");
		if (!empty($homeLanguageMenuID)) {
			$menuModel = new System_Model_Menu();
			$menuObj = $menuModel->getInfo($homeLanguageMenuID);
			if (!empty($menuObj->Url)) {
				$menuDefault = $menuObj;
			}
		}
		
		$menuDefaultUrl = $menuDefault->Url;
		
		$isUseLanguageCode = false;
		if (Zend_Registry::isRegistered("IsUseLanguageCode")) {
			$isUseLanguageCode = Zend_Registry::get("IsUseLanguageCode");
		}
		if ($isUseLanguageCode) {
			if (Zend_Registry::isRegistered('lang')) {
				$lang = Zend_Registry::get('lang');
				$menuDefaultUrl = "/" . $lang . $menuDefaultUrl;
			}
		}
		
		// IntroPage
		$introPageData = System_Service_Intropage::getActive();

		if ($introPageData != null) {
			
			$now = new DateTime();
			if (($introPageData->PublishDate != null && $introPageData->PublishDown != null && ($introPageData->PublishDate > $now || $introPageData->PublishDown < $now)) ||
					($introPageData->PublishDate != null && $introPageData->PublishDate > $now && $introPageData->PublishDown == null ) || 
					($introPageData->PublishDate == null && $introPageData->PublishDown != null && $introPageData->PublishDown < $now)) {
				// Set View
				$this->_helper->layout()->disableLayout();
				$this->_helper->viewRenderer->setNoRender();
				
				//$this->getResponse()->setRedirect($menuDefault->Url);
				$this->getResponse()->setRedirect($menuDefaultUrl);
			}
			
			$this->_helper->_layout->setLayout('layoutplain');			
			$languages = System_Model_Setting::getLanguageActives();

			$this->view->isUseLanguageCode = $isUseLanguageCode;
			$this->view->languages = $languages;
			$this->view->introPageData = $introPageData;
			$this->view->menuDefault = $menuDefault;
		} else {
			// Set View
			$this->_helper->layout()->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			
			$this->getResponse()->setRedirect($menuDefaultUrl);
		}
		
		
		
// 		if ($menuDefault) {
// 			$this->getResponse()->setRedirect($menuDefault->Url);
// 		}
		
	}
}

