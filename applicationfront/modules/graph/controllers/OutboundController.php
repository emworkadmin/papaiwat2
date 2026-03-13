<?php
class Graph_OutboundController extends Zend_Controller_Action
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
	protected function checkAmountNo($amount){
		$data=array();
		if ($amount >= 1000000) {
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
	function searchAction(){

		$rangtime = $this->getRequest()->getParam('rangtime');

		if($rangtime){ $rangtime = $rangtime; }else{ $rangtime = null; }

		$infoSearch = new Zend_Session_Namespace("GraphOutboundSearch");
		$infoSearch->rangtime = $rangtime;

		$this->_helper->redirector('index', 'outbound', 'graph');
	}
    function indexAction()
    {
		
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$model = new Graph_Model_Outbound();

		// GraphOutboundSearch
		$maxYear = $model->findMaxYear();
		$year = $maxYear;
		$rangtime = $year;
		if (Zend_Session::namespaceIsset("GraphOutboundSearch")) {
			$infoSearch = new Zend_Session_Namespace("GraphOutboundSearch");
			$rangtime = $infoSearch->rangtime;
			$rangtime=$rangtime;
			$year=$rangtime;
			Zend_Session::namespaceUnset("GraphOutboundSearch");
		}
		$this->view->rangtime = $rangtime;
		
		//DepartureAmount
		//$DepartureAmount=$model->getDepartureAmount($year);
		$DepartureAmount=$model->getDepartureAmount($maxYear);
		$DepartureAmount=$this->checkAmount($DepartureAmount);
		$this->view->DepartureAmount=$DepartureAmount;

		//RevenueAmount
		//$RevenueAmount=$model->getRevenueAmount($year);
		$RevenueAmount=$model->getRevenueAmount($maxYear);
		$RevenueAmount=$this->checkAmount($RevenueAmount);		
		$this->view->RevenueAmount=$RevenueAmount;

		//StayAvgAmount
		//$StayAvgAmount=$model->getStayAvgAmount($year);
		$StayAvgAmount=$model->getStayAvgAmount($maxYear);
		$StayAvgAmount=$this->checkAmount($StayAvgAmount);
		$this->view->StayAvgAmount=$StayAvgAmount;

		//ExpressPerDayAmount
		//$ExpressPerDayAmount=$model->getExpressPerDayAmount($year);
		$ExpressPerDayAmount=$model->getExpressPerDayAmount($maxYear);
		$this->view->ExpressPerDayAmount=$ExpressPerDayAmount;
		
		//RevenueByCountry
		//$RevenueByCountry = $model->getRevenueByCountry($rangtime);
		$RevenueByCountry = $model->getRevenueByCountry($year);
		if(!empty($RevenueByCountry)){
			for($i=0;$i<count($RevenueByCountry);$i++){
				$amount=$this->checkAmountNo($RevenueByCountry[$i]['Amount']);
				$RevenueByCountry[$i]['Amount'] = $amount['Amount'];
				$RevenueByCountry[$i]['AmountText'] = $amount['AmountText'];
			}
		}
		$this->view->RevenueByCountry = $RevenueByCountry;

		//RevenueByYear
		$RevenueByYear = $model->getRevenueByYear();
		$RevenueByYear = array_reverse($RevenueByYear);
		if(!empty($RevenueByYear)){
			for($i=0;$i<count($RevenueByYear);$i++){
				$amount=$this->checkAmountNo($RevenueByYear[$i]['Amount']);
				$RevenueByYear[$i]['Amount'] = $amount['Amount'];
				$RevenueByYear[$i]['AmountText'] = $amount['AmountText'];
				$RevenueByYear[$i]['Y'] = $RevenueByYear[$i]['Y']+543;
			}
		}
		$this->view->RevenueByYear = $RevenueByYear;

		//DepartureByYear
		$DepartureByYear = $model->getDepartureByYear();
		$DepartureByYear = array_reverse($DepartureByYear);
		if(!empty($DepartureByYear)){
			for($i=0;$i<count($DepartureByYear);$i++){
				$amount=$this->checkAmountNo($DepartureByYear[$i]['Amount']);
				$DepartureByYear[$i]['Amount'] = number_format($amount['Amount'],2);
				$DepartureByYear[$i]['AmountText'] = $amount['AmountText'];
				$DepartureByYear[$i]['Y'] = $DepartureByYear[$i]['Y']+543;
			}
		}
		$this->view->DepartureByYear = $DepartureByYear;

		// Set Global
		$menuID = $this->findMenu("/graph/outbound/index");
		Zend_Registry::set("MenuID", $menuID);

		$years = array( "-" => "ช่วงเวลา" );
		$years[$maxYear] = $maxYear + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$years[$maxYear - $i] = $maxYear + 543 - $i;
		}

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );
		
		$this->view->years = $years;
		$this->view->latestYear = $maxYear;
		$this->view->export = $export;

		$this->view->pageTitle = 'ข้อมูลนักท่องเที่ยวชาวไทยที่เดินทางไปต่างประเทศ (Outbound Tourism)';
		

	}
}
?>