<?php 


class Story_SearchController extends Zend_Controller_Action
{
	protected $auth;
	protected $lang;
	protected $mainCategoryID;
	protected $extensionName;
	protected $extensionCode;

	public function init()
	{
		// Auth
		$auth = Zend_Auth::getInstance();
		$authData = $auth->getIdentity();
		$this->auth = $authData;

		// Get Languages
	    $lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
	    if (empty($lang)) {
	        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	        $lang = $defaultLanguage["Code"];
	    }
		$this->lang = $lang;
		
		// Send data to view
		$this->view->lang = $lang;
// 		$this->extensionName = Wat_Service_Info::getExtensionName();
// 		$this->extensionCode = Wat_Service_Info::getExtensionCode();
	}
	
	public function indexAction()
	{
// 		if(!System_Service_Element::isActive($this->extensionCode)) {
// 			throw new Zend_Controller_Action_Exception('This page does not exist', 404);
// 			return;
// 		}
		
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		$filterStripTags = new Zend_Filter_StripTags();
		
		// Get Param
		$searchParams = array();
		$searchParams['q'] = $filterStripTags->filter($this->_request->getParam('q'));
		$searchParams['cat1'] = $filterInt->filter($this->_request->getParam('cat1'));
		$searchParams['cat2'] = $filterInt->filter($this->_request->getParam('cat2'));
		$searchParams['cat3'] = $filterInt->filter($this->_request->getParam('cat3'));
		
		// Setting
		$searchByText = System_Model_Setting::getValue("SearchByText", "Story");
		$searchCategory1 = System_Model_Setting::getValue("SearchCategory1", "Story");
		$searchCategory2 = System_Model_Setting::getValue("SearchCategory2", "Story");
		$searchCategory3 = System_Model_Setting::getValue("SearchCategory3", "Story");
		
		$catAmount = 0;
		$catAmount += empty($searchCategory1) ? 0 : 1;
		$catAmount += empty($searchCategory2) ? 0 : 1;
		$catAmount += empty($searchCategory3) ? 0 : 1;
		
		$colText = 0;
		$colCat = 0;
		$colBtn = 12;
		
		if ($catAmount == 0) { // บังคับแสดง TextSearch
			$colText = 10;
			$colBtn = 2;
		} else {
			if ($searchByText == 'Y') {
				if ($catAmount == 3) {
					$colText = 4;
					$colCat = 2;
					$colBtn = 2;
				} elseif ($catAmount == 2) {
					$colText = 4;
					$colCat = 3;
					$colBtn = 2;
				} else {
					$colText = 7;
					$colCat = 3;
					$colBtn = 2;
				}
			} else {
				if ($catAmount == 3) {
					$colCat = 3;
					$colBtn = 3;
				} elseif ($catAmount == 2) {
					$colCat = 5;
					$colBtn = 2;
				} else {
					$colCat = 9;
					$colBtn = 3;
				}
			}
		}

		// Get Info
		$model = new Story_Model_Category();
		$category1Option = empty($searchCategory1) ? array() : $model->getOption($this->lang, $searchCategory1);
		$category2Option = empty($searchCategory2) ? array() : $model->getOption($this->lang, $searchCategory2);
		$category3Option = empty($searchCategory3) ? array() : $model->getOption($this->lang, $searchCategory3);
		
		// Page Title
		$pageTitle = Emwork_Translate::text($this->lang, "story", "search_title");
				
		// Send Data to view
		$this->view->pageTitle = $pageTitle;
		$this->view->searchParams = $searchParams;
		$this->view->colText = $colText;
		$this->view->colCat = $colCat;
		$this->view->colBtn = $colBtn;
		$this->view->searchCategory1 = $searchCategory1;
		$this->view->searchCategory2 = $searchCategory2;
		$this->view->searchCategory3 = $searchCategory3;
		$this->view->category1Option = $category1Option;
		$this->view->category2Option = $category2Option;
		$this->view->category3Option = $category3Option;
		
		// Send Data to Template
		$this->view->bodyClass = "story-body-search";
		
		$menuID = $this->findMenu("/story/search/index");
		Zend_Registry::set("MenuID", $menuID);
	}
	
	// Get Search List	
	public function getlistAction()
	{	
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");		
		
		// Setting
		$storySettingArticleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Story");
		$storySettingArticleAmountColumn = empty($storySettingArticleAmountColumn) ? 1 : $storySettingArticleAmountColumn;
		$storySettingArticleAmountColumn = empty($storySettingArticleAmountColumn) || $storySettingArticleAmountColumn <= 1 ? 1 : ($storySettingArticleAmountColumn >= 4 ? 4 : $storySettingArticleAmountColumn);
		
		$storySettingArticlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Story");
		$storySettingArticlePerPage = empty($storySettingArticlePerPage) ? 10 : $storySettingArticlePerPage;

		$storySettingArticleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Story");
		$storySettingArticleShowAmount = empty($storySettingArticleShowAmount) ? "Y" : $storySettingArticleShowAmount;

		$storySettingArticleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Story");
		$storySettingArticleShowImage = empty($storySettingArticleShowImage) ? "Y" : $storySettingArticleShowImage;

		$storySettingArticleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Story");
		$storySettingArticleShowIntroText = empty($storySettingArticleShowIntroText) ? "Y" : $storySettingArticleShowIntroText;

		$storySettingArticleSortType = System_Model_Setting::getValue("ArticleSortType", "Story");
		$storySettingArticleSortType = empty($storySettingArticleSortType) ? 'title' : $storySettingArticleSortType;

		$storySettingArticleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Story");
		$storySettingArticleSortDirection = empty($storySettingArticleSortDirection) ? 'asc' : $storySettingArticleSortDirection;
		
		$sortType = 'Created';
		switch ($storySettingArticleSortType) {
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
		$sortDirection = in_array($storySettingArticleSortDirection, array('asc', 'desc')) ? $storySettingArticleSortDirection : 'desc';
		
		// Init
		$pageSize = empty($storySettingArticlePerPage) ? 10 : $storySettingArticlePerPage;


		// Search
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		$textstr = $this->getRequest()->getParam('q');
		$cat1 = $filterInt->filter($this->getRequest()->getParam('cat1'));
		$cat2 = $filterInt->filter($this->getRequest()->getParam('cat2'));
		$cat3 = $filterInt->filter($this->getRequest()->getParam('cat3'));
		
		$advancesearch = array();
		$advancesearch['searchText'] = $textstr;
		$advancesearch['catIds'] = array();
		if (!empty($cat1)) {
			$advancesearch['catIds'][] = $cat1;
		}
		if (!empty($cat2)) {
			$advancesearch['catIds'][] = $cat2;
		}
		if (!empty($cat3)) {
			$advancesearch['catIds'][] = $cat3;
		}

		$model = new Story_Model_Article();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);

		// Set Setting to view

		//$this->view->currentCategoryID = $currentCategoryID;
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->storySettingArticleAmountColumn = $storySettingArticleAmountColumn;
		$this->view->storySettingArticleShowImage = $storySettingArticleShowImage == 'Y';
		$this->view->storySettingArticleShowIntroText = $storySettingArticleShowIntroText == 'Y';

		// Send Data to view
		$this->view->items = $items;
		if ($storySettingArticleAmountColumn == 1) {
			$responce["html"] = $this->view->render('search/_arows.phtml');
		} else {
			$responce["html"] = $this->view->render('search/_acards.phtml');
		}
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
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