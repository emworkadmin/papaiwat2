<?php
class Graph_InboundController extends Zend_Controller_Action
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
	protected function checkAmount($amount){
		$data=array();
		if ($amount >= 1000000000) {
			$data["Amount"] = $amount / 1000000000;
			$data["AmountText"] = 'พันล้าน';
		} elseif ($amount >= 1000000) {
			$data["Amount"] = $amount / 1000000;
			$data["AmountText"] = 'ล้าน';
		} elseif ($amount >= 1000) {
			$data["Amount"] = $amount / 1000;
			$data["AmountText"] = 'พัน';
		}else {
			$data["Amount"] = $amount;
			$data["AmountText"] = '';
		}
		return $data;
	}
    function indexAction()
    {
		
		// Log Access
		System_Model_Log::access();
		
		// TODO: Find Lated Year;
		$year = '2015';
		
		$inboundModel = new Graph_Model_Inbound();
		
		$maxYear = $inboundModel->findMaxYear();
		$year = empty($maxYear) ? $year : $maxYear;
		
		// Amount
		$touristAmount = $inboundModel->getTouristAmount($year);		
		if ($touristAmount >= 1000000) {
			$this->view->touristAmount = $touristAmount / 1000000;
			$this->view->touristAmountText = 'ล้าน';
		} elseif ($touristAmount >= 1000) {
			$this->view->touristAmount = $touristAmount / 1000;
			$this->view->touristAmountText = 'พัน';
		} else {
			$this->view->touristAmount = $touristAmount;
			$this->view->touristAmountText = '';
		}
		
		$revenueAmount = $inboundModel->getRevenueAmount($year);
		if ($revenueAmount >= 1000000) {
			$this->view->revenueAmount = $revenueAmount / 1000000;
			$this->view->revenueAmountText = 'ล้าน';
		} elseif ($revenueAmount >= 1000) {
			$this->view->revenueAmount = $revenueAmount / 1000;
			$this->view->revenueAmountText = 'พัน';
		} else {
			$this->view->revenueAmount = $revenueAmount;
			$this->view->revenueAmountText = '';
		}
		
		$stayAvgAmount = $inboundModel->getStayAvgAmount($year);
		if ($stayAvgAmount >= 1000000) {
			$this->view->stayAvgAmount = $stayAvgAmount / 1000000;
			$this->view->stayAvgAmountText = 'ล้าน';
		} elseif ($stayAvgAmount >= 1000) {
			$this->view->stayAvgAmount = $stayAvgAmount / 1000;
			$this->view->stayAvgAmountText = 'พัน';
		} else {
			$this->view->stayAvgAmount = $stayAvgAmount;
			$this->view->stayAvgAmountText = '';
		}
		
		$expressPerDayAmount = $inboundModel->getExpressPerDayAmount($year);
		if ($expressPerDayAmount >= 1000000) {
			$this->view->expressPerDayAmount = $expressPerDayAmount / 1000000;
			$this->view->expressPerDayAmountText = 'ล้าน';
		} else {
			$this->view->expressPerDayAmount = $expressPerDayAmount;
			$this->view->expressPerDayAmountText = '';
		}
		
		// Top 5 Tourist
		$top5TouristByNational = $inboundModel->getTop5TouristByNational($year);
		$this->view->top5TouristByNational = $top5TouristByNational;
		
		// Top 5 Revenue
		$top5RevenueByNational = $inboundModel->getTop5RevenueByNational($year);
		$this->view->top5RevenueByNational = $top5RevenueByNational;

		//Tourist By Year
		$TouristByYear = $inboundModel->getTouristByYear();
		if(!empty($TouristByYear)){
			for($i=0;$i<count($TouristByYear);$i++){
				$amount=$this->checkAmount($TouristByYear[$i]['InboundByYear']);
				$TouristByYear[$i]['InboundByYear'] = $amount['Amount'];
				$TouristByYear[$i]['AmountText'] = $amount['AmountText'];
				$TouristByYear[$i]['Y']=$TouristByYear[$i]['Y']+543;
				$Touristvalue[$i]['value']=$TouristByYear[$i]['InboundByYear'];
			}
		}
		$maxvalue=max($Touristvalue);
		$this->view->TouristByYear = $TouristByYear;
		$this->view->Touristvalue= round($maxvalue['value']);

		//Revenue By Year
		$RevenueByYear = $inboundModel->getRevenueByYear();
		if(!empty($RevenueByYear)){
			for($i=0;$i<count($RevenueByYear);$i++){
				$amount=$this->checkAmount($RevenueByYear[$i]['RevenueByYear']);
				$RevenueByYear[$i]['RevenueByYear'] = $amount['Amount'];
				$RevenueByYear[$i]['AmountText'] = $amount['AmountText'];
				$RevenueByYear[$i]['Y']=$RevenueByYear[$i]['Y']+543;
			}
		}
		// echo "<pre>";print_r($RevenueByYear);echo "</pre>";exit;
		$this->view->RevenueByYear = $RevenueByYear;

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$model = new Graph_Model_Index();		
		
		// Set Global
		$menuID = $this->findMenu("/graph/inbound/index");
		Zend_Registry::set("MenuID", $menuID);

		$years = array( "-" => "ช่วงเวลา" );
		$years[$maxYear] = $maxYear + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$years[$maxYear - $i] = $maxYear + 543 - $i;
		}

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );

		$this->view->year = $years;
		$this->view->latestYear = $maxYear;
		$this->view->export = $export;
		$this->view->pageTitle = 'ข้อมูลนักท่องเที่ยวชาวต่างชาติที่เดินทางเข้าประเทศไทย (Inbound Tourism)';
	}
	function powerbiAction()
    {
		
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$model = new Graph_Model_Index();

		// echo "<pre>";print_r($itemssumByyear);echo"</pre>";exit;
		// Set Global
		$menuID = $this->findMenu("/graph/inbound/index");
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
		$this->view->pageTitle = 'รายได้จากนักท่องเที่ยวต่างชาติที่เดินทางเข้าประเทศไทย';
		// $this->renderScript('index/research.phtml');
	}
}
?>