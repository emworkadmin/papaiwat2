<?php 
class License_ProcedureController extends Zend_Controller_Action
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
		$this->_helper->redirector('list', 'procedure', 'license');
	}
	
	public function listAction() {
		
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		$searchCategory = $filterInt->filter($this->_request->getParam('category'));
		
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
		$this->view->searchLicense = $searchLicense;
		$this->view->searchProcedure = $searchProcedure;
		$this->view->categoryOption = Emwork_Utility::addEmptyOption($categoryOption, Emwork_Translate::text($this->lang, 'license', 'search_category_placeholder'));
		
		$menuID = $this->findMenu("/license/procedure/list");
		Zend_Registry::set("MenuID", $menuID);
	}
	
	public function getlistAction() {
		
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['resultText'] = Emwork_Translate::text($this->lang, "license", "search_result_notfound");
		$responce['html'] = '';
		
		// Setting
		$procedurePerPage = System_Model_Setting::getValue("ProcedurePerPage", "License");
		$procedurePerPage = empty($procedurePerPage) ? 15 : $procedurePerPage;
		$procedureSortDirection = System_Model_Setting::getValue("ProcedureSortDirection", "License");
		$procedureSortDirection = empty($procedureSortDirection) ? 'asc' : $procedureSortDirection;
		$procedureSortType = System_Model_Setting::getValue("ProcedureSortType", "License");
		$procedureSortType = empty($procedureSortType) ? "title" : $procedureSortType;
		
		$sortType = 'Created';
		switch ($procedureSortType) {
			case 'created' :
				$sortType = 'Created';
				break;
			case 'publishdate' :
				$sortType = 'PublishDate';
				break;
			case 'startdate' :
				$sortType = 'StartDate';
				break;
			case 'title' :
				$sortType = 'Title';
				break;
			case 'modified' :
				$sortType = 'Modified';
				break;
		}
		$sortDirection = in_array($procedureSortDirection, array('asc', 'desc')) ? $procedureSortDirection : 'desc';
		
		// Init
		$pageSize = empty($procedurePerPage) ? 10 : $procedurePerPage;
		$pageIndex = $this->getRequest()->getParam('pageIndex', 1);
		
		// Search
		$textstr = $this->getRequest()->getParam('search');
		$searchCategoryID = $this->getRequest()->getParam('category');
		$searchIssueID = $this->getRequest()->getParam('issue');
		$searchDepartmentID = $this->getRequest()->getParam('department');		
		$searchLicense = ($this->_request->getParam('license'));
		$searchProcedure = ($this->_request->getParam('procedure'));
		
		$advancesearch = array();
		$advancesearch["CategoryID"] = $searchCategoryID;
		$advancesearch["IssueID"] = $searchIssueID;
		$advancesearch["DepartmentID"] = $searchDepartmentID;
		$advancesearch["License"] = $searchLicense;
		$advancesearch["Procedure"] = $searchProcedure;
		
		$model = new License_Model_Procedure();
		$items = $model->getList($sortType, $sortDirection, $pageSize, $pageIndex, $textstr, $filter, $advancesearch, $this->lang);
		
		// Set Setting to
		$this->view->defaultImageUrl = $this->view->baseUrl("images/other/no-image.jpg");
		$this->view->commentUrl = System_Model_Setting::getValue("CommentUrl", "License");
		
		// Send Data to view
		if (count($items) > 0) {
			$this->view->items = $items;
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
			$responce["html"] = $this->view->render('procedure/_procedure.phtml');
			
			$responce['resultText'] = Emwork_Translate::text($this->lang, "license", "search_result_amount_label");
			$responce['resultText'] = str_replace("{amount}", number_format($responce["total"]), $responce['resultText']);
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