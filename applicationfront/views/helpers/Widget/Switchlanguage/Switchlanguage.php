<?php

class Zend_View_Helper_Widget_Switchlanguage_Switchlanguage extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Switchlanguage_Switchlanguage($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		
		// Language List
		$languageList = System_Model_Setting::getLanguageActives();
		if (count($languageList) <= 1) {
			return;
		}
		
		// Active Language
		$activeLanguage = '';
		if (Zend_Registry::isRegistered('lang')) {
			$activeLanguage = Zend_Registry::get('lang');
		} else {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$activeLanguage = $defaultLanguage["Code"];
		}
		
		// IsUseLanguageCode
		$isUseLanguageCode = false;
		if (Zend_Registry::isRegistered("IsUseLanguageCode")) {
			$isUseLanguageCode = Zend_Registry::get("IsUseLanguageCode");
		}
		
		// Get Setting 
		$settingDisplayType = isset($this->params["displaytype"]) && !empty($this->params["displaytype"]) ? $this->params["displaytype"] : "V";
		$settingDisplayNameType = isset($this->params["displayname"]) && !empty($this->params["displayname"]) ? $this->params["displayname"] : "name";
		$settingFlag = isset($this->params["showflag"]) && !empty($this->params["showflag"]) ? $this->params["showflag"] : "Y";
		if ($settingDisplayNameType == "no" && $settingFlag == "N") {
			$settingFlag = "Y";
		}

		$filter = new Zend_Filter_HtmlEntities();
		$_requestUri = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
		$_requestUri = $filter->filter($_requestUri);
		
		echo '<link rel="stylesheet" type="text/css" href="' . $this->view->baseUrl("assets/wswitchlanguage/css/widget.css") . '" />';
		
		echo '<div class="switchlang-box">';
		
		if ($settingDisplayType == "H") {
			foreach ($languageList as $l) {
				$nameLang = ($settingDisplayNameType == "no") ? "" : ( ($settingDisplayNameType == 'name') ? $l["NameLocal"] : strtoupper($l["Code"]) );
				$flag = ($settingFlag == 'Y') ? "<img alt=\"image\" src='" . $this->view->baseUrl("assets/wswitchlanguage/img/flags/" . $l["Code"] . ".png") . "' /> " : "";
				$requestUriLang = $_requestUri;
				if ($isUseLanguageCode) {
					$requestUriLang = str_replace("/" . $activeLanguage . "/", "/" . $l['Code'] . "/", $_requestUri);
				}
				echo '<span><a href="' . $requestUriLang . '" data-lang="' . $l['Code'] . '" >' . $flag . '' . $nameLang . '</a></span>';
			}
		} else {
			
			echo "<div>";
			
			echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true" >';
			
			// ShowFlag
			if ($settingFlag == 'Y') {
				echo "<img alt=\"image\" src='" . $this->view->baseUrl("assets/wswitchlanguage/img/flags/" . $activeLanguage . ".png") . "' /> ";
			}
			
			// Loop For Active
			foreach ($languageList as $l) {
				if ($activeLanguage == $l["Code"]) {
					echo ($settingDisplayNameType == "no") ? "" : ( ($settingDisplayNameType == 'name') ? $l["NameLocal"] : strtoupper($l["Code"]) );
				}
			}
			
			echo ' <em class="fa fa-caret-down fa-lg"></em>';			
			
			echo '</a>';
			
			// List
			echo '<ul class="dropdown-menu" role="menu">';
			
			// Loop Lang
			foreach ($languageList as $l) {
				$nameLang = ($settingDisplayNameType == "no") ? "" : ( ($settingDisplayNameType == 'name') ? $l["NameLocal"] : strtoupper($l["Code"]) ); 
				$flag = ($settingFlag == 'Y') ? "<img alt=\"image\" src='" . $this->view->baseUrl("assets/wswitchlanguage/img/flags/" . $l["Code"] . ".png") . "' /> " : "";
				$requestUriLang = $_requestUri;
				if ($isUseLanguageCode) {
					$requestUriLang = str_replace("/" . $activeLanguage . "/", "/" . $l['Code'] . "/", $_requestUri);
				}
				echo '<li><a href="' . $requestUriLang . '" data-lang="' . $l['Code'] . '" >' . $flag . '' . $nameLang . '</a></li>';
			}
			
			echo '</ul>';
			
			echo "</div>";
		}
		
		echo "</div>";
		
	}
	
}
