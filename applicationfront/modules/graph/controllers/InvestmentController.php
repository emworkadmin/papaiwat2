<?php
class Graph_InvestmentController extends Zend_Controller_Action
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
		
		// Get Data
		$model = new Graph_Model_Investment();

		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y")] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") - $i] = date("Y") + 543 - $i;
		}
		unset($year[2018]);
		unset($year[2017]);

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );

		// GraphInvestmentSearch
		$situationMaxYear = $model->findForeignInvestmentMaxYear();
		$situationYear = $situationMaxYear;
		//$exchangeYear = null;
		$exchangeMaxYear = $model->findExchangeRatesMaxYear();
		$exchangeYear = $exchangeMaxYear;
		if (Zend_Session::namespaceIsset("GraphInvestmentSearch")) {
			$infoSearch = new Zend_Session_Namespace("GraphInvestmentSearch");
			$situationYear = $infoSearch->situationYear;
			$exchangeYear = $infoSearch->exchangeYear;
			Zend_Session::namespaceUnset("GraphInvestmentSearch");
		}
		$this->view->situationYear = $situationYear;
		$this->view->exchangeYear = $exchangeYear;
		
		$fdiYears = array( "-" => "ช่วงเวลา" );
		for($i = 0;$i < 5; $i++) {
			$fdiYears[$situationMaxYear - $i] = $situationMaxYear + 543 - $i;
		}
		$exchangeYears = array( "-" => "ช่วงเวลา" );
		for($i = 0;$i < 5; $i++) {
			$exchangeYears[$exchangeMaxYear - $i] = $exchangeMaxYear + 543 - $i;
		}
		
		$confidenceIndexMaxYear = $model->findConfidenceIndexMaxYear();
		$confidenceIndex = $model->getConfidenceIndex($confidenceIndexMaxYear);
		
		$overallRankMaxYear = $model->findOverallRankMaxYear();
		$overallRank = $model->getOverallRank($overallRankMaxYear);
		
		$dataForeignInvestment = $model->getForeignInvestment($situationYear); // เงินลงทุนขาเข้าสุทธิ (Net)
		$dataForeignInvestmentInflow = $model->getForeignInvestmentInflow($situationYear); // เงินลงทุนขาเข้า (Inflow)
		$dataForeignInvestmentOutflow = $model->getForeignInvestmentOutflow($situationYear); // เงินลงทุนขาเข้า (Outflow)
		$dataForeignInvestment01 = $model->getForeignInvestment01($situationYear); // สัดส่วนปริมาณเงินลงทุน รายสัญชาติ (บาท)
		$dataForeignInvestment02 = $model->getForeignInvestment02($situationYear); // เงินลงทุนขาเข้าสุทธิ รายสาขาธุรกิจ (บาท)
		$dataForeignInvestment03 = $model->getForeignInvestment03($situationYear); // เงินลงทุนโดยตรง รายสัญชาติ
		$dataForeignInvestment04 = $model->getForeignInvestment04($situationYear); // แนวโน้มการลงทุนจากต่างประเทศ รายปี
		$dataExchangeRates = $model->getExchangeRates($exchangeYear); //Exchange Rates

		// Prepare Data
		$dataForeignInvestment = $dataForeignInvestment / 1000000;
		$dataForeignInvestmentInflow = $dataForeignInvestmentInflow / 1000000;
		$dataForeignInvestmentOutflow = $dataForeignInvestmentOutflow / 1000000;
		if(count($dataForeignInvestment01)>0){
			foreach($dataForeignInvestment01 as $k=>$v){
				if(!empty($v['Amount'])){
					$dataForeignInvestment01[$k]['Amount'] = $v['Amount'] / 1000000;
				}
			}
		}
		if(count($dataForeignInvestment02)>0){
			foreach($dataForeignInvestment02 as $k=>$v){
				if(!empty($v['Amount'])){
					$dataForeignInvestment02[$k]['Amount'] = $v['Amount'] / 1000000;
				}
			}
		}
		if(count($dataForeignInvestment03)>0){
			foreach($dataForeignInvestment03 as $k=>$v){
				if(!empty($v['Amount'])){
					$dataForeignInvestment03[$k]['Amount'] = $v['Amount'] / 1000000;
				}
			}
		}
		if(count($dataForeignInvestment04)>0){
			foreach($dataForeignInvestment04 as $k=>$v){
				if(!empty($v['Amount'])){
					$dataForeignInvestment04[$k]['Amount'] = $v['Amount'] / 1000000;
				}
			}
		}
		if(count($ExchangeRates)>0){
			foreach($ExchangeRates as $k=>$v){
				if(!empty($v['Amount'])){
					$ExchangeRates[$k]['Amount'] = $v['Amount'] / 1000000;
				}
			}
		}

		// Debug Zone
		if(false){
			echo 'dataForeignInvestment : <pre>'; print_r($dataForeignInvestment);  echo '</pre>';
			echo 'dataForeignInvestmentInflow : <pre>'; print_r($dataForeignInvestmentInflow);  echo '</pre>';
			echo 'dataForeignInvestmentOutflow : <pre>'; print_r($dataForeignInvestmentOutflow);  echo '</pre>';
			echo 'dataForeignInvestment01 : <pre>'; print_r($dataForeignInvestment01);  echo '</pre>';
			echo 'dataForeignInvestment02 : <pre>'; print_r($dataForeignInvestment02);  echo '</pre>';
			echo 'dataForeignInvestment03 : <pre>'; print_r($dataForeignInvestment03);  echo '</pre>';
			echo 'dataForeignInvestment04 : <pre>'; print_r($dataForeignInvestment04);  echo '</pre>';
			echo 'dataExchangeRates : <pre>'; print_r($dataExchangeRates);  echo '</pre>';
			exit;
		}

		// Send data to view
		$this->view->confidenceIndex = $confidenceIndex;
		$this->view->overallRank = $overallRank;
		
		$this->view->ForeignInvestment = $dataForeignInvestment;
		$this->view->ForeignInvestmentInflow = $dataForeignInvestmentInflow;
		$this->view->ForeignInvestmentOutflow = $dataForeignInvestmentOutflow;
		$this->view->ForeignInvestment01 = $dataForeignInvestment01;
		$this->view->ForeignInvestment02 = $dataForeignInvestment02;
		$this->view->ForeignInvestment03 = $dataForeignInvestment03;
		$this->view->ForeignInvestment04 = $dataForeignInvestment04;
		$this->view->ExchangeRates = $dataExchangeRates;

		$this->view->year = $year;
		$this->view->situationYear = $situationYear;
		$this->view->exchangeYear = $exchangeYear;
		$this->view->fdiYears = $fdiYears;
		$this->view->exchangeYears = $exchangeYears;
		$this->view->export = $export;
		$this->view->confidenceIndexLatestYear = $confidenceIndexMaxYear;
		$this->view->overallRankLatestYear = $overallRankMaxYear;
		$this->view->situationLatestYear = $situationMaxYear;
		$this->view->exchangeLatestYear = $exchangeMaxYear;

		// Set Global
		$menuID = $this->findMenu("/graph/investment/index");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->pageTitle = 'ข้อมูลการลงทุนด้านการท่องเที่ยว';        
	}

	function searchAction(){

		$situationYear = $this->getRequest()->getParam('situationYear');
		$exchangeYear = $this->getRequest()->getParam('exchangeYear');

		if($situationYear){ $situationYear = $situationYear; }else{ $situationYear = null; }
		if($exchangeYear){ $exchangeYear = $exchangeYear; }else{ $exchangeYear = null; }

		$infoSearch = new Zend_Session_Namespace("GraphInvestmentSearch");
		$infoSearch->situationYear = $situationYear;
		$infoSearch->exchangeYear = $exchangeYear;

		$this->_helper->redirector('index', 'investment', 'graph');
	}
	
	function exchangeAction()
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

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );

		$this->view->year = $year;
		$this->view->export = $export;
		
		// Set Global
		$menuID = $this->findMenu("/graph/investment/index");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->pageTitle = 'ข้อมูลสถิติอัตราแลกเปลี่ยนเงินตราต่างประเทศ';
	}

	function situationAction()
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

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );

		$this->view->year = $year;
		$this->view->export = $export;
		
		// Set Global
		$menuID = $this->findMenu("/graph/investment/index");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->pageTitle = 'ข้อมูลสถิติสถานการณ์การลงทุนโดยตรงจากต่างประเทศ';
	}

}
?>