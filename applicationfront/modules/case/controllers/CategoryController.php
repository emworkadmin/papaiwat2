<?php 


class Case_CategoryController extends Zend_Controller_Action
{
	protected $auth;
	protected $lang;
	protected $mainCategoryID = 2;

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
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		
		// Get Param
		$id = $filterInt->filter($this->_request->getParam('id',1));

		// Get Info
		$model = new Case_Model_Category();
		$dataDetail = $model->getDataByLanguage();
		$dataPrefixBlack = $model->getPrefixBlack();
		$dataPrefixRed = $model->getPrefixRed();

		// Send Data to view
		$this->view->type = 'cards';
		$this->view->id = $id;
		$this->view->dataPrefixBlack = Emwork_Utility::addEmptyOption($dataPrefixBlack, Emwork_Translate::text($this->lang, 'general', 'emptyoption'));
		$this->view->dataPrefixRed = Emwork_Utility::addEmptyOption($dataPrefixRed, Emwork_Translate::text($this->lang, 'general', 'emptyoption'));
		$this->view->AmountCase = count($dataDetail);		

		$menuID = $this->findMenu("/case/category/index");
		
		Zend_Registry::set("MenuID", $menuID);
	}	
	public function getcaseAction() {

		$items=new stdclass();
		$mainCategoryID = $this->mainCategoryID;
		$filterInt = new Zend_Filter_Int();
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
		
		$mainSiteUrl = System_Model_Setting::getValue("MainSiteUrl", "General");
		
		// Setting
		$caseSettingCourtCode = System_Model_Setting::getValue("CaseCourtCode", "Case");

		// Search
		$pageIndex = $this->getRequest()->getParam('pageIndex',9);
		$advancesearch=array();
		$advancesearch['BlackCasePrefix'] = $this->getRequest()->getParam('BlackCasePrefix');
		$advancesearch['BlackCaseNo'] = $this->getRequest()->getParam('BlackCaseNo');
		$advancesearch['BlackCaseYear'] = $this->getRequest()->getParam('BlackCaseYear');
		$advancesearch['RedCasePrefix'] = $this->getRequest()->getParam('RedCasePrefix');
		$advancesearch['RedCaseNo'] = $this->getRequest()->getParam('RedCaseNo');
		$advancesearch['RedCaseYear'] = $this->getRequest()->getParam('RedCaseYear');
		$advancesearch['casebeginDate'] =empty($this->getRequest()->getParam('casebeginDate')) ? Emwork_Utility_DateTime::MySQLToDateThai(date('Y-m-d')):$this->getRequest()->getParam('casebeginDate');
		$advancesearch['caseendDate'] = $this->getRequest()->getParam('caseendDate');
		$type =  $this->getRequest()->getParam('type','cards');
		$pageSize=9;

		$model = new Case_Model_Category();
		$items = $model->getList($advancesearch,$caseSettingCourtCode,$pageIndex,$pageSize);


		// print_r(count($items));exit;
		$this->view->items = $items;
		$this->view->AmountCase = $items[0]["RECORD"];		

		if($type=='cards'){
            $responce["html"] = $this->view->render('category/_cards.phtml');
        }else{
            $responce["html"] = $this->view->render('category/_rows.phtml');
        }
		if (count($items) > 0) {
			$responce["total"] = $items[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $pageSize);
		}
		$responce['pageIndex'] = $pageIndex;
		// echo"<pre>";print_r($responce);echo"</pre>";exit;
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