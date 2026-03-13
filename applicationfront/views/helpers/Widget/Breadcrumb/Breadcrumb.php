<?php 

class Zend_View_Helper_Widget_Breadcrumb_Breadcrumb extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $lang = null;
	protected $menuID = null;
	protected $extraBreadcrumb = array();
	public function Widget_Breadcrumb_Breadcrumb($params)
	{
		$this->params = $params;		
		
		// Find Lang
		if (Zend_Registry::isRegistered('lang')) {
			$this->lang = Zend_Registry::get('lang');
		} else {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$this->lang = $defaultLanguage["Code"];
		}
		
		// Find MenuID
		if (Zend_Registry::isRegistered('MenuID')) {
			$this->menuID = Zend_Registry::get('MenuID');
		} else {
			$menuObj = System_Model_Menu::getHome();
			if ($menuObj->ID) {
				$this->menuID = $menuObj->ID;
			}
		}
		
		// Has Extra Breadcrumb
		if (Zend_Registry::isRegistered('ExtraBreadcrumb')) {
			if (is_array(Zend_Registry::get('ExtraBreadcrumb'))) {
				$this->extraBreadcrumb = Zend_Registry::get('ExtraBreadcrumb');
			}
		} 
		
		return $this;
	}
	
	public function display() {

		$urls = array();		
		$temps = array();
		
		if ($this->menuID != null) {
			
			if (count($this->extraBreadcrumb) > 0) {
				for ($i = count($this->extraBreadcrumb) - 1; $i >= 0; $i --) {
					$extraBreadcrumb = $this->extraBreadcrumb[$i];
					if (!empty($extraBreadcrumb["url"]) && !empty($extraBreadcrumb["name"])) {
						$temps[] = array(
								"url" => $extraBreadcrumb["url"],
								"name" => $extraBreadcrumb["name"]
						);
					}
				}
			}
		
			$menuModel = new System_Model_Menu();
			$menuObj = $menuModel->getDataByLanguage($this->menuID, $this->lang);		
			$temps[] = array(
					"url" => $menuObj->Url,
					"name" => $menuObj->Name				
			);
			
			while($menuObj->ParentID != null) {
				$menuObj = $menuModel->getDataByLanguage($menuObj->ParentID, $this->lang);
				$temps[] = array(
						"url" => $menuObj->Url,
						"name" => $menuObj->Name
				);
			}
			
			if ($menuObj->IsHome != 'Y') {
				$temps[] = array(
						"url" => "/",
						"name" => Emwork_Translate::text($this->lang, "wbreadcrumb", "home")
				);
			}
			
			$urls = array_reverse($temps);
		}
		
		$html = '';
		if (count($urls) > 0) {
			$html .= "<ol class='breadcrumb'>";
			for ($i = 0; $i < count($urls); $i++) {
				$url = $urls[$i];
				$urlUrl = $url["url"];
				$urlName = $url["name"];
				if ($i == 0) {
					$urlName = Emwork_Translate::text($this->lang, "wbreadcrumb", "home");
				}
				if ($i == count($urls) - 1) { // active
					$html .= "<li class='active'>" . $urlName;
				} else {
					$html .= "<li><a href='" . $urlUrl . "'>" . $urlName . "</a>";
				}
				$html .= "</li>";
			}
			$html .= "</ol>";
		}
		
		echo $html;
	}
}

