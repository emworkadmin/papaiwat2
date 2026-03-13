<?php

class Search_SearchController extends Zend_Controller_Action
{
	protected $total;
	protected $lang;
	protected $StatusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $TypeOption = array('content' => 'บทความ', 'event' => 'กิจกรรม' , 'gallery' => 'รูปภาพ','tag' => 'คำสำคัญ');  
	protected $PeriodOption = array('1' => '1 วัน', '7' => '7 วัน', '15' => '15 วัน', '30' => '30 วัน', '60' => '60 วัน', '90' => '90 วัน');

	public function init()
	{
		// Get Languages
		$lang = Zend_Registry::isRegistered("lang") ? Zend_Registry::get("lang") : "";
		
	    if (empty($lang)) {
	        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
	        $lang = $defaultLanguage["Code"];
	    }
		$this->lang = $lang;

		$this->StatusOption = array(
			"Y" => Emwork_Translate::text($lang, 'search', 'status_y'),
			"N" => Emwork_Translate::text($lang, 'search', 'status_n')
		);

		$this->TypeOption = array(
			"content" => Emwork_Translate::text($lang, 'search', 'type_content'),
			"event" => Emwork_Translate::text($lang, 'search', 'type_event'),
			"gallery" => Emwork_Translate::text($lang, 'search', 'type_gallery'),
			"tag" => Emwork_Translate::text($lang, 'search', 'type_tag')
		);

		$this->PeriodOption = array(
			'1' => Emwork_Translate::text($lang, 'search', 'period_1'),
			'7' => Emwork_Translate::text($lang, 'search', 'period_7'),
			'15' => Emwork_Translate::text($lang, 'search', 'period_15'),
			'30' => Emwork_Translate::text($lang, 'search', 'period_30'),
			'60' => Emwork_Translate::text($lang, 'search', 'period_60'),
			'90' => Emwork_Translate::text($lang, 'search', 'period_90')
		);

		// Send data to view
		$this->view->lang = $lang;
	}
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		// Zend Filter
		$filterInt = new Zend_Filter_StripTags();
		
        //Get Param
		$param['text'] = $filterInt->filter($this->_request->getParam('text'));
		$param['period'] = $filterInt->filter($this->_request->getParam('period'));
		$param['start'] = $filterInt->filter($this->_request->getParam('start'));
		$param['end'] = $filterInt->filter($this->_request->getParam('end'));
		$param['type'] = $filterInt->filter($this->_request->getParam('type'));

		$this->view->statusOption = $this->StatusOption;
		$this->view->periodOption = Emwork_Utility::addEmptyOption($this->PeriodOption, Emwork_Translate::text($this->lang, 'general', 'emptyoption'));
		$this->view->typeOption = $this->TypeOption;

		$this->view->text = $param['text'];
		$this->view->period = $param['period'];
		$this->view->start = base64_encode($param['start']);
		$this->view->end = base64_encode($param['end']);
		$this->view->type = $param['type'];
		
		$menuID = $this->findMenu("/search/search/index");
		Zend_Registry::set("MenuID", $menuID);
	}

	public function getlistAction() {
		
		$responce = array();
		$responce['total'] = 0;
		$responce['totalPage'] = 0;
		$responce['pageIndex'] = 0;
		$responce['html'] = '';
	
		$sord = 'asc';
				
		// Init
		$rows = 10 ;
		$page = $this->getRequest()->getParam('pageIndex', 1);

		$filterInt = new Zend_Filter_StripTags();
		$model = new Search_Model_Search();

		$params = $this->getRequest()->getParams();

		// Search
		$text = $this->_request->getParam('text');
		$period = $filterInt->filter($this->_request->getParam('period'));
		$type = $this->_request->getParam('type');
		$start = $this->_request->getParam('start');
		$end = $this->_request->getParam('end');


		if($period!='') {
			$date = new Datetime($now);
			$date->sub(new DateInterval('P'.$period.'D'));	
			$now = date("Y-m-d");
			$dataperiod = $date->format('Y-m-d');	
		} else {
			$dataperiod = $period ;
		}
	
		$textstr = $text;
		$advancesearch = array();
		$advancesearch['txtsearch'] = $text;
		$advancesearch['period'] = $dataperiod;
		$advancesearch['start'] = $start;
		$advancesearch['end'] = $end;
		$advancesearch['type'] = $type;

		$itemData = array();
		$itemData = $model->getListContent($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $this->lang);

		$this->getsave($text ,$period ,$start ,$end ,$type);
		$this->view->itemData = $itemData ;
		$this->view->textseart = $text;
		$responce["html"] = $this->view->render('search/_getdata.phtml');
		if (count($itemData) > 0) {
			$responce["total"] = $itemData[0]["RECORD"];
			$responce['totalPage'] = ceil($responce["total"] / $rows);
		}
		$responce['pageIndex'] = $page;
		$this->view->total = $responce["total"] ;
		$this->_helper->json($responce);
	}

	public function getsave($text ,$period ,$start ,$end ,$type){

		$lang = $this->lang ;
		$model = new Search_Model_search();
		$model->getsave($text ,$period ,$start ,$end ,$lang ,$type);
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

