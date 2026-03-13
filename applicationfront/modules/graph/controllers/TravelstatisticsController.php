<?php
class Graph_TravelstatisticsController extends Zend_Controller_Action
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

		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y")] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") - $i] = date("Y") + 543 - $i;
		}

		$export = array( "export" => "export", "pdf" => "pdf", "image" => "image" );

		// GraphTravelstatisticsSearch
		$checkPointYear = null;
		$inOutYear = null;
		$numberTouristsYear = null;
		if (Zend_Session::namespaceIsset("GraphTravelstatisticsSearch")) {
			$infoSearch = new Zend_Session_Namespace("GraphTravelstatisticsSearch");
			$checkPointYear = $infoSearch->checkPointYear;
			$inOutYear = $infoSearch->inOutYear;
			$numberTouristsYear = $infoSearch->numberTouristsYear;

			$this->view->checkPointYear = $checkPointYear;
			$this->view->inOutYear = $inOutYear;
			$this->view->numberTouristsYear = $numberTouristsYear;

			Zend_Session::namespaceUnset("GraphTravelstatisticsSearch");
		}

		// Get Data
		$model = new Graph_Model_Travelstatistics();
		$dataInboundArrivals = $model->getInboundArrivals($inOutYear); // จำนวนผู้เดินทางเข้า (คน)
		$dataOutboundArrivals = $model->getOutboundArrivals($inOutYear); // จำนวนผู้เดินทางออก (คน)
		$dataInboundArrivalsNationality = $model->getInboundArrivalsNationality($inOutYear); // จำนวนผู้เดินทางเข้าจำแนกตามสัญชาติ
		$dataOutboundArrivalsNationality = $model->getOutboundArrivalsNationality($inOutYear); // จำนวนผู้เดินทางออกจำแนกตามสัญชาติ
		$dataTouristsArrivals = $model->getTouristsArrivals($checkPointYear); // อัตราการใช้สนามบิน

		// Debug Zone
		if(false){
			echo 'dataInboundArrivals : <pre>'; print_r($dataInboundArrivals);  echo '</pre>';
			echo 'dataOutboundArrivals : <pre>'; print_r($dataOutboundArrivals);  echo '</pre>';
			echo 'dataInboundArrivalsNationality : <pre>'; print_r($dataInboundArrivalsNationality);  echo '</pre>';
			echo 'dataOutboundArrivalsNationality : <pre>'; print_r($dataOutboundArrivalsNationality);  echo '</pre>';
			echo 'dataTouristsArrivals : <pre>'; print_r($dataTouristsArrivals);  echo '</pre>';
			exit;
		}

		$this->view->InboundArrivals = $dataInboundArrivals;
		$this->view->OutboundArrivals = $dataOutboundArrivals;
		$this->view->InboundArrivalsNationality = $dataInboundArrivalsNationality;
		$this->view->OutboundArrivalsNationality = $dataOutboundArrivalsNationality;
		$this->view->TouristsArrivals = $dataTouristsArrivals;

		$this->view->year = $year;
		$this->view->export = $export;
		
		// Set Global
		$menuID = $this->findMenu("/graph/travelstatistics/index");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->pageTitle = 'ข้อมูลสถิติการเดินทางเข้า-ออกประเทศ';        
	}
	
	function searchAction(){

		$checkPointYear = $this->getRequest()->getParam('checkPointYear');
		$inOutYear = $this->getRequest()->getParam('inOutYear');
		$numberTouristsYear = $this->getRequest()->getParam('numberTouristsYear');

		if($checkPointYear){ $checkPointYear = $checkPointYear; }else{ $checkPointYear = null; }
		if($inOutYear){ $inOutYear = $inOutYear; }else{ $inOutYear = null; }
		if($numberTouristsYear){ $numberTouristsYear = $numberTouristsYear; }else{ $numberTouristsYear = null; }

		$infoSearch = new Zend_Session_Namespace("GraphTravelstatisticsSearch");
		$infoSearch->checkPointYear = $checkPointYear;
		$infoSearch->inOutYear = $inOutYear;
		$infoSearch->numberTouristsYear = $numberTouristsYear;

		$this->_helper->redirector('index', 'travelstatistics', 'graph');
	}

	function inoutAction()
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

		$this->view->pageTitle = 'จำนวนผู้เดินทางเข้าและออกประเทศไทย';
	}

	function checkpointAction()
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

		$this->view->pageTitle = 'ข้อมูลสถิติจำนวนนักท่องเที่ยวต่างชาติรายด่าน';    		
	}

	function numbertouristsAction()
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

		$this->view->pageTitle = 'ข้อมูลสถิติจำนวนนักท่องเที่ยวชาวต่างชาติ รายสัญชาติ'; 		
	}
}
?>