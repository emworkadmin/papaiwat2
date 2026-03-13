<?php

Class App_HelpLayout {

	protected $URL = '';
	private $defaultMenuGroupUrl = "javascript:void(0);";

	public function __construct() {
		$this->URL = $_SERVER['REDIRECT_URL'];
	}

	public function getHelpNavigator() {
		$navStr = "";
		switch($this->URL) {
			case "/helpsupport/index" :
				$navStr .= $this->generateNode("ศูนย์ความช่วยเหลือ");
				break;
			case "/helpsupport/category" :
				$navStr .= $this->generateNode("ศูนย์ความช่วยเหลือ", "/helpsupport/index", true);
				$navStr .= $this->generateCategoryNode($_REQUEST["cid"], false);
				break;
			case "/helpsupport/detail" :
				$navStr .= $this->generateNode("ศูนย์ความช่วยเหลือ", "/helpsupport/index", true);
				Zend_Loader::loadClass('helpsupport');
				$model = new helpsupport();
				$data = $model->itemHelpContent($_REQUEST["id"]);
				$navStr .= $this->generateCategoryNode($data->CategoryID, true);
				$navStr .= $this->generateNode($data->Title);
				break;
		}
		return $navStr;
	}
	private function generateNode($name, $url, $isLink = false) {
		$navStr = "";
		if ($isLink) {
			$navStr = " &gt; <a href='" . $url . "' title='" . $name . "'>" . $name . "</a>";
		} else {
			$navStr = " &gt; <span>" . $name . "</span>";
		}
		return $navStr;
	}
	private function generateCategoryNode($categoryID, $isLink = false) {
		Zend_Loader::loadClass('helpsupport');
		$model = new helpsupport();
		$data = $model->itemHelpCategory($categoryID);

		$navStr = $this->generateNode($data->Title, "/helpsupport/category?cid=" . $data->CategoryID, $isLink);
		if (!empty($data->ParentCategoryID)) {
			$navStr = $this->generateCategoryNode($data->ParentCategoryID, true) . $navStr;
		}
		return $navStr;
	}

	public function sideMenu() {
		Zend_Loader::loadClass('helpsupport');
		$model = new helpsupport();
		$dataList = $model->getCategoryList();

		return $dataList;
	}

	public function getServiceMenu($SystemID) {
		Zend_Loader::loadClass('menu');
		$model = new menu();
		$return = $model->getServiceMenu($this->URL);
		return $return;
	}
}

?>
