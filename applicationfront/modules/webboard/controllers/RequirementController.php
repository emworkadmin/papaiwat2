<?php

class Webboard_RequirementController extends Zend_Controller_Action
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
	
	public function indexAction()
	{
		System_Model_Log::access();
		
		$menuID = $this->findMenu("/webboard/home/index");
		Zend_Registry::set("MenuID", $menuID);

		$model = new Webboard_Model_Requirement();
        $data = $model->getData();
		$this->view->data = $data;

		

	}
	
}

