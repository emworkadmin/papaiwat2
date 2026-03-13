<?php

class Guestbook_GuestbookController extends Zend_Controller_Action
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
		$id = $filterInt->filter($this->_request->getParam('id'));

		$modelInstantMessage = new Guestbook_Model_Guestbook();
		$instantOption = $modelInstantMessage->getOption($this->lang,$id);

		$Data = $modelInstantMessage->getData($this->lang, $id);
		$Title = $modelInstantMessage->getTitle($this->lang, $id);

		$this->view->Status = $Data->IsEnable == "Y" || $Data->IsDeleted == "N";
		$this->view->Title = $Title->Title ;
		$this->view->id = $id ;
		$this->view->instantOption = $instantOption ;

		$menuID = $this->findMenu("/guestbook/guestbook/index/id/" . $id);
		Zend_Registry::set("MenuID", $menuID);
	}

	public function getlistAction() {
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
	
		$sord = 'DESC';

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		// Get Param
		$id = $filterInt->filter($this->_request->getParam('id'));

		// Init
		$rows = 10 ;
		$page = $this->getRequest()->getParam('pageIndex', 1);

		$model = new Guestbook_Model_Guestbook();

		$itemData = array();
		$itemData = $model->getListGuestbook($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$id,$this->lang);

		$this->view->itemData = $itemData ;
		$responce["html"] = $this->view->render('guestbook/_getdata.phtml');

		// $this->view = $text ;
		// $this->view = $period ;
		// $this->view = $start; 
		// $this->view = $end;

		if (count($itemData) > 0) {
			$responce["total"] = $itemData[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $rows);
		}
		$responce['pageIndex'] = $page;

		$this->view->total = $responce["total"] ;

		$this->_helper->json($responce);
	}

	public function saveAction() {

		
		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		$model = new Guestbook_Model_Guestbook();
		$params = $this->getRequest()->getParams();
		$data = $model->save($params);

		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			$result['id'] = $data->id;
			
			// Log Acion
		  //   System_Model_Log::action("ebook.ebook", "save", $params['params'][$defaultLanguageCode]['Title'], $data->id);
			
//             $modelEbook = new Ebook_Model_Ebook();
//             $dataInfo = $modelEbook->getFullData($data->ID);
//             $modelEbook->saveHistory($data->ID, 'new', $sUser->getInfo()->ID, $sUser->getInfo()->DepartmentID, $dataInfo);
		} else {
			$result['result'] = false;
			$result['message'] = Emwork_Translate::text($this->lang, 'guestbook', 'validate_error');
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

