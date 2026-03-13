<?php
class Graph_IndexController extends Zend_Controller_Action
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
    
	protected function findMenu($url)
	{
		$menuID = null;
		$menus = System_Model_Menu::findMenuByUrl($url);
		if (count($menus) > 0) {
			$menuID = $menus[0]["ID"];
		}
		return $menuID;
    }

    function indexAction()
    {
		
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$model = new Graph_Model_Index();
		// $items = $model->getListInbound();
		//$itemsTop5 = $model->getListInboundTOP5();
		//$itemssumBymonth = $model->getListInboundBymonth();
		// $itemssumByyear = $model->getListInboundByyear();
		
		$itemsTop5[0]['Inbound']=number_format(17765163);	
		$itemsTop5[1]['Inbound']=number_format(4375922);
		$itemsTop5[2]['Inbound']=number_format(3235041);
		$itemsTop5[3]['Inbound']=number_format(2994695);
		$itemsTop5[4]['Inbound']=number_format(2950000);

		$itemsTop5[0]['CountryNameEN']='Chaina';
		$itemsTop5[1]['CountryNameEN']='Malaysia';
		$itemsTop5[2]['CountryNameEN']='India';
		$itemsTop5[3]['CountryNameEN']='Laos';
		$itemsTop5[4]['CountryNameEN']='Korea';

		$itemsTop5[0]['Icon']='assets/graph/img/Flag_National/china.png';
		$itemsTop5[1]['Icon']='assets/graph/img//Flag_National/malaysia.png';
		$itemsTop5[2]['Icon']='assets/graph/img//Flag_National/india.png';
		$itemsTop5[3]['Icon']='assets/graph/img//Flag_National/laos.png';
		$itemsTop5[4]['Icon']='assets/graph/img//Flag_National/south_korea.png';

		$itemsTop5B[0]['Inbound']=number_format(17765163);	
		$itemsTop5B[1]['Inbound']=number_format(4375922);
		$itemsTop5B[2]['Inbound']=number_format(3235041);
		$itemsTop5B[3]['Inbound']=number_format(2994695);
		$itemsTop5B[4]['Inbound']=number_format(2950000);

		$itemsTop5B[0]['CountryNameEN']='Chaina';
		$itemsTop5B[1]['CountryNameEN']='Malaysia';
		$itemsTop5B[2]['CountryNameEN']='India';
		$itemsTop5B[3]['CountryNameEN']='Laos';
		$itemsTop5B[4]['CountryNameEN']='Korea';

		$itemsTop5B[0]['Icon']='assets/graph/img/Flag_National/china.png';
		$itemsTop5B[1]['Icon']='assets/graph/img/Flag_National/malaysia.png';
		$itemsTop5B[2]['Icon']='assets/graph/img/Flag_National/india.png';
		$itemsTop5B[3]['Icon']='assets/graph/img/Flag_National/laos.png';
		$itemsTop5B[4]['Icon']='assets/graph/img/Flag_National/south_korea.png';

		// echo "<pre>";print_r($itemssumByyear);echo"</pre>";exit;
		// Set Global
		$menuID = $this->findMenu("/graph/index/index");
		Zend_Registry::set("MenuID", $menuID);

		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y") + 543] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") + 543 - $i] = date("Y") + 543 - $i;
		}

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );

		$this->view->year = $year;
		$this->view->export = $export;
		$this->view->items= $items;
		$this->view->itemsTop5= $itemsTop5;
		$this->view->itemsTop5B= $itemsTop5B;
		$this->view->itemssumBymonth= $itemssumBymonth;
		$this->view->itemssumByyear= $itemssumByyear;
		$this->view->pageTitle = 'ข้อมูลด้านสถานการณ์ แนวโน้มด้านการท่องเที่ยว การวิเคราะห์ข้อมูล';
		

		// $this->renderScript('index/research.phtml');
	}

	function guideAction()
    {
		
		// Log Access
		System_Model_Log::access();

		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y") + 543] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") + 543 - $i] = date("Y") + 543 - $i;
		}

		$export = array( "export" => "export", "pdf" => "pdf", "image" => "image" );

		$this->view->year = $year;
		$this->view->export = $export;
		
		$menuID = $this->findMenu("/graph/index/guide");
		Zend_Registry::set("MenuID", $menuID);
	}

	function travelrankAction()
    {
		
		// Log Access
		System_Model_Log::access();
		
		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y") + 543] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") + 543 - $i] = date("Y") + 543 - $i;
		}

		$export = array( "export" => "export", "pdf" => "pdf", "image" => "image" );

		$this->view->year = $year;
		$this->view->export = $export;
		
		$menuID = $this->findMenu("/graph/index/travelrank");
		Zend_Registry::set("MenuID", $menuID);
		
	}

	function researchAction()
    {
		
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		// echo "<pre>";print_r($itemssumByyear);echo"</pre>";exit;
		// Set Global
		$menuID = $this->findMenu("/graph/index/research");
		Zend_Registry::set("MenuID", $menuID);

		//$this->view->CSS = System_Model_Setting::getValue("ChartCustomCss", "OrgChart");
		$this->view->pageTitle = 'ข้อมูลงานวิจัยที่เกี่ยวข้องด้านการท่องเที่ยว';
		

		// $this->renderScript('index/research.phtml');
	}
	function stathotelAction()
    {
		
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		// echo "<pre>";print_r($itemssumByyear);echo"</pre>";exit;
		// Set Global
		$menuID = $this->findMenu("/graph/index/stathotel");
		Zend_Registry::set("MenuID", $menuID);

		//$this->view->CSS = System_Model_Setting::getValue("ChartCustomCss", "OrgChart");
		$this->view->pageTitle = 'ข้อมูลสถิติภาคธุรกิจโรงแรมและเกสต์เฮ้าส์';
		
		// $this->renderScript('index/research.phtml');
	}
	function travelplaceAction()
    {
		
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$model = new Graph_Model_Index();
		// Set Global
		$menuID = $this->findMenu("/graph/index/travelplace");
		Zend_Registry::set("MenuID", $menuID);
	
		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y") + 543] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") + 543 - $i] = date("Y") + 543 - $i;
		}

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );

		$this->view->year = $year;
		$this->view->export = $export;
		$this->view->pageTitle = 'ข้อมูลด้านการวางแผนด้านการท่องเที่ยวในเชิงพื้นที่ และการวางแผนรายสาขา';
	}

	function travelstatisticsAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y") + 543] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") + 543 - $i] = date("Y") + 543 - $i;
		}

		$export = array( "export" => "export", "pdf" => "pdf", "image" => "image" );

		$this->view->year = $year;
		$this->view->export = $export;
		
		// Set Global
		$menuID = $this->findMenu("/graph/index/travelstatistics");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->pageTitle = 'ข้อมูลสถิติการเดินทางเข้าประเทศ';		
	}

	function investmentAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y") + 543] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") + 543 - $i] = date("Y") + 543 - $i;
		}

		$export = array( "export" => "export", "pdf" => "pdf", "image" => "image" );

		$this->view->net = $this->number_format_short(rand(1000,1000000000));
		$this->view->inflow = $this->number_format_short(rand(1000,1000000000));
		$this->view->outflow = $this->number_format_short(rand(1000,1000000000));
		$this->view->year = $year;
		$this->view->export = $export;
		
		// Set Global
		$menuID = $this->findMenu("/graph/index/investment");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->pageTitle = 'ข้อมูลการลงทุนด้านการท่องเที่ยว';		
	}

	private function number_format_short( $n, $precision = 1 ) {
		if ($n < 999) {
			$n_format = number_format($n, $precision);
			$suffix = '';
		} else if ($n < 999999) {
			$n_format = number_format($n / 1000, $precision);
			$suffix = 'K';
		} else if ($n < 999999999) {
			$n_format = number_format($n / 1000000, $precision);
			$suffix = 'M';
		} else if ($n < 999999999999) {
			$n_format = number_format($n / 1000000000, $precision);
			$suffix = 'B';
		} else {
			$n_format = number_format($n / 1000000000000, $precision);
			$suffix = 'T';
		}
		if ( $precision > 0 ) {
			$dotzero = '.' . str_repeat( '0', $precision );
			$n_format = str_replace( $dotzero, '', $n_format );
		}
		return $n_format . $suffix;
	}
}
?>