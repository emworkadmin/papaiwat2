<?php 


class Weblink_ItemController extends Zend_Controller_Action
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
		$model = new Weblink_Model_Category();
		$dataDetail = $model->getDataByLanguage($id, $this->lang);

		// Setting
		$defaultSetting = new stdClass;
		$defaultSetting->ItemShowType = System_Model_Setting::getValue("ItemShowType", "Weblink");
		$defaultSetting->ItemSortType = System_Model_Setting::getValue("ItemSortType", "Weblink");
		$defaultSetting->ItemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Weblink");
		$defaultSetting->ItemWeblinkSizeHeight = System_Model_Setting::getValue("ItemWeblinkSizeHeight", "Weblink");
		$defaultSetting->ItemWeblinkSizeWidth = System_Model_Setting::getValue("ItemWeblinkSizeWidth", "Weblink");
		$defaultSetting->ItemShowTitle = System_Model_Setting::getValue("ItemShowTitle", "Weblink");
		$defaultSetting->ItemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Weblink");
		$defaultSetting->ItemShowButton = System_Model_Setting::getValue("ItemShowButton", "Weblink");
		$defaultSetting->ItemCustomCss = System_Model_Setting::getValue("ItemCustomCss", "Weblink");

		$categorySetting = json_decode($dataDetail->Settings);

		if($categorySetting->ItemShowTypeSetting == 'C') {
			$defaultSetting->ItemShowType = $categorySetting->ItemShowType;
		}
		if($categorySetting->ItemSortSetting == 'C') {
			$defaultSetting->ItemSortType = $categorySetting->ItemSortType;
			$defaultSetting->ItemSortDirection = $categorySetting->ItemSortDirection;
		}
		if($categorySetting->ItemWeblinkSizeSetting == 'C') {
			$defaultSetting->ItemWeblinkSizeHeight = $categorySetting->ItemWeblinkSizeHeight;
			$defaultSetting->ItemWeblinkSizeWidth = $categorySetting->ItemWeblinkSizeWidth;
		}
		if($categorySetting->ItemShowTitleSetting == 'C') {
			$defaultSetting->ItemShowTitle = $categorySetting->ItemShowTitle;
		}
		if($categorySetting->ItemShowIntroTextSetting == 'C') {
			$defaultSetting->ItemShowIntroText = $categorySetting->ItemShowIntroText;
		}
		if($categorySetting->ItemShowButtonSetting == 'C') {
			$defaultSetting->ItemShowButton = empty($categorySetting->ItemShowButton) ? 'Y' : $categorySetting->ItemShowButton;
		}
		if($categorySetting->ItemCustomCssSetting == 'C') {
			$defaultSetting->ItemCustomCss = $categorySetting->ItemCustomCss;
		}

		// Get Data List
		$dataList = $this->getlist($id, $defaultSetting->ItemSortType, $defaultSetting->ItemSortDirection, $defaultSetting->ItemShowType);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->dataList = $dataList;
		$this->view->pageTitle = $dataDetail->Title;
		$this->view->setting = $defaultSetting;

		$menuID = $this->findMenu("/weblink/item/index/id/" . $id);

		Zend_Registry::set("MenuID", $menuID);
	}
	
	private function getlist($id, $weblinkSettingItemSortType, $weblinkSettingItemSortDirection, $showType) 
	{ 
		$sortType = 'Created';
		switch ($weblinkSettingItemSortType) {
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
		$sortDirection = in_array($weblinkSettingItemSortDirection, array('asc', 'desc')) ? $weblinkSettingItemSortDirection : 'desc';
			
		// Model
		$model = new Weblink_Model_Item();
		$advancesearch = array();
		if($showType == 'lists'){
			$catIds = $model->getRecursiveCategoryIds($id);
			$advancesearch["isCatIds"] = $catIds;
		}else{
			$advancesearch["isCat"] = $id;
		}

		// Init
		$pageSize = 1000;
		$pageIndex = 1;
		
		// Search
		$textstr = '';
		
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Prepare Data
		if($showType == 'lists'){
			$result = array();
			if(count($items)>0){
				foreach($items as $k=>$v){
					if(!$result[$v['CategoryID']]){
						$result[$v['CategoryID']] = array();
						$result[$v['CategoryID']]['ID'] = $v['CategoryID'];
						$result[$v['CategoryID']]['Title'] = $v['CategoryTitle'];
						$result[$v['CategoryID']]['Items'] = array();
					}
					$result[$v['CategoryID']]['Items'][$v['ID']] = array();
					$result[$v['CategoryID']]['Items'][$v['ID']]['ID'] = $v['ID'];
					$result[$v['CategoryID']]['Items'][$v['ID']]['Title'] = $v['Title'];
					$result[$v['CategoryID']]['Items'][$v['ID']]['Url'] = $v['Url'];
					$result[$v['CategoryID']]['Items'][$v['ID']]['LinkTarget'] = $v['LinkTarget'];
				}
			}
			$items = $result;
		}

		// Return
		return $items;
	}
	
	function clickAction() {

		$result = array ();
		$result ['result'] = false;
		$result ['message'] = '';

		$id = $this->getRequest()->getParam('id');
		$model = new Weblink_Model_Item();
		$data = $model->saveClick($id);

		if($data->result) {
			$result ['result'] = true;
			$result ['message'] = $data->message;
		}

		$this->_helper->json($result);
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