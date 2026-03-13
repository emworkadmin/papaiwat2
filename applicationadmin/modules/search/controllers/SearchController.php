<?php

class Search_SearchController extends Zend_Controller_Action
{
	protected $lang = 'th';
	protected $StatusOption = array('Y' => 'ใช้งาน', 'N' => 'ไม่ใช้งาน');
	protected $TypeOption = array('content' => 'บทความ', 'event' => 'กิจกรรม' , 'gallery' => 'รูปภาพ','tag' => 'คำสำคัญ');  
	protected $PeriodOptin = array('1' => '1 วัน', '7' => '7 วัน', '15' => '15 วัน', '30' => '30 วัน', '60' => '60 วัน', '90' => '90 วัน');
	
	public function init() {
	    
	}
	
	public function indexAction() {
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('search.search.view');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}

	    // Log Access
	    System_Model_Log::access();
	    
	    $sUser = System_Model_SUser::getInstance();
	    if (!$sUser->hasData()) {
	        $this->_helper->redirector('index', 'index', 'default');
	    }
	    
	    $this->view->periodOption = $this->PeriodOptin;
		$this->view->statusOption = $this->StatusOption;
		$this->view->typeOption = $this->TypeOption;
	    
	    //Data to Layout
	    $this->view->pageName = "การค้นหาข้อมูล";
	    $this->view->pageIcon= "fa fa-search";
        $this->view->subMenus = array();
        
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
	        array('name' => 'รายการการค้นหาข้อมูล')
        );

	}
	
	public function getlistAction() {
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->json($responce);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->json($responce);
		}
		
		$canView = $sUser->isAllow('search.search.view');

	    // Init
	    $_draw = $this->getRequest()->getParam('draw');
	    $_start = $this->getRequest()->getParam('start'); // Rowindex
	    $_length = $this->getRequest()->getParam('length'); // Page Size
	    $_order = $this->getRequest()->getParam('order'); // Ordering
	    $_columns = $this->getRequest()->getParam('columns'); // Columns
	    $orderIndex = $_order[0]['column']; // Find Ordering Index
	    $sidx = $_columns[$orderIndex]['data']; // Find Ordering Column
	    $sord = $_order[0]['dir']; // Find Ordering Direction
	    $rows = $_length;	// Assign to Page Size (Row)
	    $page = ($_start / $rows) + 1; // Assign to Page Index
	    
	    // Basic Search
	    $textstr = $this->getRequest()->getParam('textsearch');
	    $textstr = $textstr;
	    $filter = '';
	    
	    //Advance Search
	    $advancesearch['textsearch'] = $textstr;
	    $advancesearch['status'] = $this->getRequest()->getParam('status');
	    $advancesearch['period'] = $this->getRequest()->getParam('period');
	    $advancesearch['sendfrom'] = $this->getRequest()->getParam('sendfrom');
	    $advancesearch['sendto'] = $this->getRequest()->getParam('sendto');

		if($canView) {

			$model = new Search_Model_Search();
			$data = $model->getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch);
		}
	    $i=$_start;
	    $responce = array();
	    $responce['draw'] = $_draw;
	    $responce['recordsTotal'] = 0;
	    $responce['recordsFiltered'] = 0;
	    $responce['data'] = array();
	    
	    if ($canView && $data) {
	        $responce['recordsTotal'] = $data[0]["RECORD"];
	        $responce['recordsFiltered'] = $data[0]["RECORD"];
	        
	        foreach($data as $item) {
				
	            $i++;
	            $d = array();
	            $d['DT_RowId'] = $item["ID"];
	            //$d['ID'] = empty($item["ID"]) ? "" : $item["ID"];
	            $d['NO'] = $i;
	            $d['TextSearch'] = Emwork_Utility::notShowNull(urldecode($item["TextSearch"]));
				$d['Period'] = Emwork_Utility::notShowNull($this->PeriodOptin[$item["Period"]]);
				$d['Type'] = Emwork_Utility::notShowNull($this->TypeOption[$item["Type"]]);
				$d['Sumsearch'] = Emwork_Utility::notShowNull($item["Sumsearch"]);
	            // $d['IsEnable'] = Emwork_Utility::notShowNull($this->StatusOption[$item["IsEnable"]]);
				$d['StartDate'] = Emwork_Utility::notShowNull($item['StartDate']);
				$d['EndDate'] = Emwork_Utility::notShowNull($item['EndDate']);
	            $responce['data'][] = $d;
	        }
	    }

	    $this->_helper->json($responce);
	}
	
}

