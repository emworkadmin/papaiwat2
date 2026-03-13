<?php 


class Banner_ItemController extends Zend_Controller_Action
{
	protected $lang;
	protected $mainCategoryID = '1';

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
		$id = $filterInt->filter($this->_request->getParam('id'));

		// Get Data
		$model = new Banner_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);

		// Setting
		$defaultSetting = new stdClass;
		$defaultSetting->ItemSortType = System_Model_Setting::getValue("ItemSortType", "Banner");
		$defaultSetting->ItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Banner");
		$defaultSetting->ItemBannerSizeHeight = System_Model_Setting::getValue("ItemBannerSizeHeight", "Banner");
		$defaultSetting->ItemBannerSizeWidth = System_Model_Setting::getValue("ItemBannerSizeWidth", "Banner");
		$defaultSetting->ItemShowTitle = System_Model_Setting::getValue("ItemShowTitle", "Banner");
		$defaultSetting->ItemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Banner");
		$defaultSetting->ItemCustomCss = System_Model_Setting::getValue("ItemCustomCss", "Banner");

		$categorySetting = json_decode($dataDetail->Settings);
		if($categorySetting->ItemSortSetting == 'C'){
			$defaultSetting->ItemSortType = $categorySetting->ItemSortType;
			$defaultSetting->ItemSortDirection = $categorySetting->ItemSortDirection;
		}
		if($categorySetting->ItemBannerSizeSetting == 'C'){
			$defaultSetting->ItemBannerSizeHeight = $categorySetting->ItemBannerSizeHeight;
			$defaultSetting->ItemBannerSizeWidth = $categorySetting->ItemBannerSizeWidth;
		}
		if($categorySetting->ItemShowTitleSetting == 'C'){
			$defaultSetting->ItemShowTitle = $categorySetting->ItemShowTitle;
		}
		if($categorySetting->ItemShowIntroTextSetting == 'C'){
			$defaultSetting->ItemShowIntroText = $categorySetting->ItemShowIntroText;
		}
		if($categorySetting->ItemCustomCssSetting == 'C'){
			$defaultSetting->ItemCustomCss = $categorySetting->ItemCustomCss;
		}

		// Get Data List
		$dataList = $this->getlist($id, $defaultSetting->ItemSortType, $defaultSetting->ItemSortDirection);

		// Send Data to view
		$this->view->id = $id;
		$this->view->dataList = $dataList;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->setting = $defaultSetting;
		
		$menuID = $this->findMenu("/banner/item/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}
	
	private function getlist($id, $bannerSettingItemSortType, $bannerSettingItemSortDirection) 
	{ 
		$sortType = 'Created';
		switch ($bannerSettingItemSortType) {
			case 'created' : 
				$sortType = 'Created';
				break;
			case 'title' :
			case 'name' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($bannerSettingItemSortDirection, array('asc', 'desc')) ? $bannerSettingItemSortDirection : 'desc';
				
		// Init
		$pageSize = 1000;
		$pageIndex = 1;
		
		// Search
		$textstr = '';
		
		$advancesearch = array();
		$advancesearch["isCat"] = $id;
		
		$model = new Banner_Model_Item();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Return
		return $items;
	}
	
	protected function findMenu($url) {
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
	}

	public function clickAction()
	{
		$result = array ();
		$result ['result'] = false;
		$result ['message'] = '';
		
		$id = $this->getRequest()->getParam("id");
		$model = new Banner_Model_Item();
		$data = $model->saveClick($id);

		if($data->result) {
			$result ['result'] = true;
			$result ['message'] = $data->message;
		}

		$this->_helper->json($result);
	}
}
?>