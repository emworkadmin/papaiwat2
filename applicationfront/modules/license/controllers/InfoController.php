<?php 
class License_InfoController extends Zend_Controller_Action
{
	protected $lang;

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
	}
	
	public function indexAction()
	{
		$this->_helper->redirector('category', 'info', 'license');
	}
	
	public function categoryAction()
	{
	
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
        $filterInt = new Zend_Filter_Int();

		// Get Info
		$model = new License_Model_Category();
		$categoryOption=$model->getOption($this->lang);
		
		$departmentModel = new License_Model_Department();
		$departmentOption = $departmentModel->getOption($this->lang);
		$this->view->departmentOption = $departmentOption;
		
		$introModel = new License_Model_Intro();
		$introData =$introModel->getDataByLanguage($this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->introData = $introData;
		$this->view->categoryOption = Emwork_Utility::addEmptyOption($categoryOption, Emwork_Translate::text($this->lang, 'license', 'search_category_placeholder'));
				
		$menuID = $this->findMenu("/license/info/category");
		Zend_Registry::set("MenuID", $menuID);
		
	}
	public function getcategorylistAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
		
		// Setting
		$categoryColumn = System_Model_Setting::getValue("CategoryColumn", "License");
		$categoryColumn = empty($categoryColumn) ? 3 : $categoryColumn;
		$categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "License");
		$categoryPerPage = empty($categoryPerPage) ? 12 : $categoryPerPage;
		$categorySortDirection = System_Model_Setting::getValue("CategorySortDirection", "License");
		$categorySortDirection = empty($categorySortDirection) ? "asc" : $categorySortDirection;
		$categorySortType = System_Model_Setting::getValue("CategorySortType", "License");
		$categorySortType = empty($categorySortType) ? "ordering" : $categorySortType;

		$sortType = 'Created';
		switch ($categorySortType) {
			case 'created' : 
				$sortType = 'Created';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
			case 'ordering' :
				$sortType = 'Ordering';
				break;
			case 'title' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($categorySortDirection, array('asc', 'desc')) ? $categorySortDirection : 'desc';
				
		// Init
		$pageSize = empty($categoryPerPage) ? 10 : $categoryPerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);

		// Search
		$textstr = $this->getRequest()->getParam('search');
		$CategoryID = $this->getRequest()->getParam('Category');
		$Title = $this->getRequest()->getParam('Title');

		$id = $filterInt->filter($this->getRequest()->getParam('id'));

		$advancesearch = array();
		$advancesearch["CategoryID"] = $CategoryID;
		$advancesearch["Title"] = $Title;
		
		$model = new License_Model_Category();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to 
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->categoryColumn = $categoryColumn;
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->items = $items;
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
			$responce["html"] = $this->view->render('info/_ccards.phtml');
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}
	
	public function licensesAction() {
		
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		$id = $filterInt->filter($this->_request->getParam('id'));
		
		if (empty($id)) {
			$this->_helper->redirector('category', 'info', 'license');
		}
		
		// Get Info
		$model = new License_Model_Category();
		$categoryOption=$model->getOption($this->lang);
		$categoryData = $model->getDataByLanguage($id, $this->lang);
		
		$departmentModel = new License_Model_Department();
		$departmentOption = $departmentModel->getOption($this->lang);
		$this->view->departmentOption = $departmentOption;
		
		if ($categoryData->IsEnable != 'Y' && $categoryData->IsDeleted == 'N') {
			$this->_helper->redirector('category', 'info', 'license');
		}
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->categoryData = $categoryData;
		$this->view->categoryOption = Emwork_Utility::addEmptyOption($categoryOption, Emwork_Translate::text($this->lang, 'license', 'search_category_placeholder'));
		
		$menuID = $this->findMenu("/license/info/category");
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/license/info/licenses/id/' . $id, 'name' => $categoryData->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	public function getlicenseslistAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
		
		// Setting
		$licensePerPage = System_Model_Setting::getValue("LicensePerPage", "License");
		$licensePerPage = $licensePerPage == null ? 10 : $licensePerPage;
		$licenseSortDirection = System_Model_Setting::getValue("LicenseSortDirection", "License");
		$licenseSortDirection = empty($licenseSortDirection) ? "asc" : $licenseSortDirection;
		$licenseSortType = System_Model_Setting::getValue("LicenseSortType", "License");
		$licenseSortType = empty($licenseSortType) ? 'title' : $licenseSortType;
		
		$commentUrl = System_Model_Setting::getValue("CommentUrl", "License");
		
		$sortType = 'Created';
		switch ($licenseSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
			case 'title' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($licenseSortDirection, array('asc', 'desc')) ? $licenseSortDirection : 'desc';
		
		// Init
		$pageSize = empty($licensePerPage) ? 10 : $licensePerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$CategoryID = $this->getRequest()->getParam('Category');
		$Title = $this->getRequest()->getParam('Title');
		
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		
		$advancesearch = array();
		$advancesearch["CategoryID"] = $id;
		$advancesearch["Title"] = $Title;
		
		$model = new License_Model_Item();
		//$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		$items = $model->getListWithIssue($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->categoryColumn = $categoryColumn;
		$this->view->commentUrl = $commentUrl;
		
		// Send Data to view
		$this->view->id = $id;
		if (count($items) > 0) {	
			for ($i = 0; $i < count($items); $i++) {
				$licenseID = $items[$i]["ID"];
				$procedures = $model->getProcedureByLanguage($licenseID, $this->lang);
				$items[$i]["Procedures"] = $procedures;
			}
			
			$issues = array();
			for ($i = 0; $i < count($items); $i++) {
				if (!array_key_exists($items[$i]["IssueID"], $issues)) {
					$issues[$items[$i]["IssueID"]] = new stdClass();
					$issues[$items[$i]["IssueID"]]->ID = $items[$i]["IssueID"];
					$issues[$items[$i]["IssueID"]]->Name = $items[$i]["IssueName"];
					$issues[$items[$i]["IssueID"]]->SourceUrl = $items[$i]["IssueSourceUrl"];
					$issues[$items[$i]["IssueID"]]->Ordering = $items[$i]["IssueOrdering"];
					$issues[$items[$i]["IssueID"]]->Licenses = array();
				}
				$issues[$items[$i]["IssueID"]]->Licenses[] = $items[$i];
			}
			
			$this->view->items = $items;
			$this->view->issues = $issues;
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
			//$responce["html"] = $this->view->render('info/_licenses.phtml');
			$responce["html"] = $this->view->render('info/_licensesWithIssue.phtml');
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}
	
	public function licenseAction() {
		
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		$id = $filterInt->filter($this->_request->getParam('id'));
		
		if (empty($id)) {
			$this->_helper->redirector('category', 'info', 'license');
		}
		
		// Get Info
		$model = new License_Model_Item();
		$itemData = $model->getDataByLanguage($id, $this->lang);
		
		$categoryModel = new License_Model_Category();
		$categoryOption=$categoryModel->getOption($this->lang);
		$categoryData = $categoryModel->getDataByLanguage($itemData->LicenseCategoryID, $this->lang);
		
		$departmentModel = new License_Model_Department();
		$departmentOption = $departmentModel->getOption($this->lang);
		$this->view->departmentOption = $departmentOption;
		
		if ($itemData->IsEnable != 'Y' && $itemData->IsDeleted == 'N' && $categoryData->IsEnable != 'Y' && $categoryData->IsDeleted == 'N') {
			$this->_helper->redirector('category', 'info', 'license');
		}
		
		$commentUrl = System_Model_Setting::getValue("CommentUrl", "License");
		
		$procedures = $model->getProcedureByLanguage($itemData->ID, $this->lang);
		
		// Send Data to view
		$this->view->id = $id;
		$this->view->itemData = $itemData;
		$this->view->procedures = $procedures;
		$this->view->commentUrl = $commentUrl;
		$this->view->categoryData = $categoryData;
		$this->view->categoryOption = Emwork_Utility::addEmptyOption($categoryOption, Emwork_Translate::text($this->lang, 'license', 'search_category_placeholder'));
		
		$menuID = $this->findMenu("/license/info/category");
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/license/info/licenses/id/' . $itemData->LicenseCategoryID, 'name' => $categoryData->Title);
		$extraBreadcrumb[] = array('url' => '/license/info/license/id/' . $id, 'name' => $itemData->Title);
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	public function searchAction() {
		
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		$searchCategory = $filterInt->filter($this->_request->getParam('category'));
		$searchDepartment = $filterInt->filter($this->_request->getParam('department'));
		$searchIssue = $filterInt->filter($this->_request->getParam('issue'));
		
		$searchLicense = ($this->_request->getParam('license'));
		$searchProcedure = ($this->_request->getParam('procedure'));
				
		// Get Info
		$model = new License_Model_Category();
		$categoryOption=$model->getOption($this->lang);
		
		$departmentModel = new License_Model_Department();
		$departmentOption = $departmentModel->getOption($this->lang);
		$this->view->departmentOption = $departmentOption;
		
		// Send Data to view
		$this->view->searchCategory = $searchCategory;
		$this->view->searchIssue = $searchIssue;
		$this->view->searchDepartment = $searchDepartment;
		$this->view->searchLicense = $searchLicense;
		$this->view->searchProcedure = $searchProcedure;
		$this->view->categoryOption = Emwork_Utility::addEmptyOption($categoryOption, Emwork_Translate::text($this->lang, 'license', 'search_category_placeholder'));
		
		$menuID = $this->findMenu("/license/info/category");
		Zend_Registry::set("MenuID", $menuID);
		
		$extraBreadcrumb = array();
		$extraBreadcrumb[] = array('url' => '/license/info/search', 'name' => Emwork_Translate::text($this->lang, "license", "info_search_title_label"));
		Zend_Registry::set("ExtraBreadcrumb", $extraBreadcrumb);
	}
	
	public function getsearchlistAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['resultText'] = Emwork_Translate::text($this->lang, "license", "search_result_notfound");
		$responce['html'] = '';
		
		// Setting
		$licensePerPage = System_Model_Setting::getValue("SearchPerPage", "License");
		$licensePerPage = empty($licensePerPage) ? 20 : $licensePerPage;
		$licenseSortDirection = System_Model_Setting::getValue("LicenseSortDirection", "License");
		$licenseSortDirection = empty($licenseSortDirection) ? "asc" : $licenseSortDirection;
		$licenseSortType = System_Model_Setting::getValue("LicenseSortType", "License");
		$licenseSortType = empty($licenseSortType) ? 'title' : $licenseSortType;
		
		$commentUrl = System_Model_Setting::getValue("CommentUrl", "License");
		
		$sortType = 'Created';
		switch ($licenseSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
			case 'title' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($licenseSortDirection, array('asc', 'desc')) ? $licenseSortDirection : 'desc';
		
		// Init
		$pageSize = empty($licensePerPage) ? 10 : $licensePerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$searchCategoryID = $this->getRequest()->getParam('category');
		$searchIssueID = $this->getRequest()->getParam('issue');
		$searchDepartmentID = $this->getRequest()->getParam('department');		
		$searchLicense = ($this->_request->getParam('license'));
		$searchProcedure = ($this->_request->getParam('procedure'));
		
		$id = $filterInt->filter($this->getRequest()->getParam('id'));
		
		$advancesearch = array();
		$advancesearch["CategoryID"] = $searchCategoryID;
		$advancesearch["IssueID"] = $searchIssueID;
		$advancesearch["DepartmentID"] = $searchDepartmentID;
		$advancesearch["License"] = $searchLicense;
		$advancesearch["Procedure"] = $searchProcedure;
		
		$model = new License_Model_Search();
		//$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		$items = $model->getListWithIssue($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->commentUrl = $commentUrl;
		
		// Send Data to view
		$this->view->id = $id;
		if (count($items) > 0) {
			for ($i = 0; $i < count($items); $i++) {
				$licenseID = $items[$i]["ID"];
				$procedures = $model->getProcedureByLanguage($licenseID, $this->lang, $searchProcedure, $searchDepartmentID);
				$items[$i]["Procedures"] = $procedures;
			}
			
			$issues = array();
			for ($i = 0; $i < count($items); $i++) {
				if (!array_key_exists($items[$i]["IssueID"], $issues)) {
					$issues[$items[$i]["IssueID"]] = new stdClass();
					$issues[$items[$i]["IssueID"]]->ID = $items[$i]["IssueID"];
					$issues[$items[$i]["IssueID"]]->Name = $items[$i]["IssueName"];
					$issues[$items[$i]["IssueID"]]->Ordering = $items[$i]["IssueOrdering"];
					$issues[$items[$i]["IssueID"]]->SourceUrl = $items[$i]["IssueSourceUrl"];
					$issues[$items[$i]["IssueID"]]->Licenses = array();
				}
				$issues[$items[$i]["IssueID"]]->Licenses[] = $items[$i];
			}
			
			$this->view->items = $items;
			$this->view->issues = $issues;
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
			//$responce["html"] = $this->view->render('info/_licenses.phtml');
			$responce["html"] = $this->view->render('info/_licensesWithIssue.phtml');
			
			$responce['resultText'] = Emwork_Translate::text($this->lang, "license", "search_result_amount_label");
			$responce['resultText'] = str_replace("{amount}", number_format($responce["total"]), $responce['resultText']);
		}
		$responce['pageIndex'] = $pageIndex;
		$this->_helper->json($responce);
	}
	
	
	
	// Ajax Cas
	public function issuelistAction(){
		
		$categoryID = $this->getRequest()->getParam("id");
		$lang = $this->getRequest()->getParam("lang");
		
		if (empty($categoryID)) {
			$this->_helper->json(array());
		}
		
		if (empty($lang)) {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$lang = $defaultLanguage['Code'];
		}
		
		$model = new License_Model_Issue();
		$data = $model->getOption($categoryID, $lang);
		
		$i = 0;
		$result= array();
		foreach ($data as $key => $value) {
			$result[$i]["id"] = $key;
			$result[$i]["name"] = $value;
			$i ++;
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