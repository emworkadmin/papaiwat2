<?php 


class Page_ItemController extends Zend_Controller_Action
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
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		
		// Get Param
		$id = $filterInt->filter($this->getRequest()->getParam("id"));
		
		// Get Info
		$model = new Page_Model_Item();
		$data = $model->getInfo($id);
		$dataDetail = $model->getDataByLanguage($id, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->data = $data;
		$this->view->dataDetail = $dataDetail;
		$this->view->isPageManage = true;
		
		// Set to Template
		$this->view->ogTitle = $dataDetail->OpenGraphTitle;
		$this->view->ogDesc = $dataDetail->OpenGraphDescription;
		//$this->view->ogImage = $dataDetail->OpenGraphDescription;
				
		$menuID = $this->findMenu("/page/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}
	
	protected function findMenu($url) {
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
	}
}
?>