<?php 


class Faq_ItemController extends Zend_Controller_Action
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
		
		// Get Param
		$id = $this->getRequest()->getParam("id");
		// $id = null;

		// Get Data List
		$dataList = $this->getlist($id);

		// Get Setting
		$faqDisplaySetting = System_Model_Setting::getValue("FaqDisplaySetting", "FAQ");

		// Send Data to view
		$this->view->id = $id;
		$this->view->dataList = $dataList;
		$this->view->faqDisplaySetting = $faqDisplaySetting;
		$this->view->pageTitle = Emwork_Translate::text($this->lang, 'faq', 'title_1').Emwork_Translate::text($this->lang, 'faq', 'title_2');
		
		$menuID = $this->findMenu("/faq/item/index");

		Zend_Registry::set("MenuID", $menuID);
	}
	
	private function getlist($id) 
	{ 
		// Init
		$pageSize = 1000;
		$pageIndex = 1;
		// $sortType = 'Ordering';
		// $sortDirection = 'desc';

		// Get Setting
		$setting = new stdClass();
		$setting->faqDisplaySetting = System_Model_Setting::getValue("FaqDisplaySetting", "FAQ");
		$setting->faqMainCategorySortDirection = System_Model_Setting::getValue("FaqMainCategorySortDirection", "FAQ");
		$setting->faqSubCategorySortDirection = System_Model_Setting::getValue("FaqSubCategorySortDirection", "FAQ");
		$setting->faqSortType = System_Model_Setting::getValue("FaqSortType", "FAQ");
		$setting->faqSortDirection = System_Model_Setting::getValue("FaqSortDirection", "FAQ");
		$setting->faqLimit = System_Model_Setting::getValue("FaqLimit", "FAQ");
		
		// Prepare Setting
		$setting->faqLimit = empty($setting->faqLimit) ? 1000 : $setting->faqLimit;

		switch($setting->faqSortType){
			case 'ordering':
				$sortType = 'tbl.Ordering';
				break;
			case 'name':
				$sortType = 'Topic';
				break;
			case 'created':
				$sortType = 'tbl.Created';
				break;
			case 'publishdate':
				$sortType = 'tbl.PublishDate';
				break;
			default :
				$sortType = 'tbl.Ordering';
				break;
		}
		$sortDirection = empty($setting->faqSortDirection) ? 'asc' : $setting->faqSortDirection;
		
		// Search
		$textstr = '';
		
		$advancesearch = array();
		$advancesearch["FaqCategoryID"] = $id;
		$advancesearch["FaqMainCategorySortDirection"] = empty($setting->faqMainCategorySortDirection) ? 'asc' : $setting->faqMainCategorySortDirection;
		$advancesearch["FaqSubCategorySortDirection"] = empty($setting->faqSubCategorySortDirection) ? 'asc' : $setting->faqSubCategorySortDirection;

		
		// Get Data List
		$model = new Faq_Model_Item();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Prepare Data
		$result = array();
		if(count($items)>0){
			foreach($items as $k=>$v){
				
				if(empty($result[$v['FaqCategoryID']])){
					$result[$v['FaqCategoryID']]['mainId'] = $v['FaqCategoryID'];
					$result[$v['FaqCategoryID']]['mainName'] = $v['CategoryName'];
					$result[$v['FaqCategoryID']]['subList'] = array();	
				}
				
				if(empty($result[$v['FaqCategoryID']]['subList'][$v['FaqSubCategoryID']])){
					$result[$v['FaqCategoryID']]['subList'][$v['FaqSubCategoryID']]['subId'] = $v['FaqSubCategoryID'];
					$result[$v['FaqCategoryID']]['subList'][$v['FaqSubCategoryID']]['subName'] = $v['SubCategoryName'];
					$result[$v['FaqCategoryID']]['subList'][$v['FaqSubCategoryID']]['faqList'] = array();
				}

				if(
					$setting->faqLimit && 
					(count($result[$v['FaqCategoryID']]['subList'][$v['FaqSubCategoryID']]['faqList']) < $setting->faqLimit)
				){
					$result[$v['FaqCategoryID']]['subList'][$v['FaqSubCategoryID']]['faqList'][$k]['Id'] = $v['ID'];
					$result[$v['FaqCategoryID']]['subList'][$v['FaqSubCategoryID']]['faqList'][$k]['Topic'] = $v['Topic'];
					$result[$v['FaqCategoryID']]['subList'][$v['FaqSubCategoryID']]['faqList'][$k]['Detail'] = $v['Detail'];
				}

			}
		}
		
		// Return
		return $result;
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