<?php
class Graph_InternalController extends Zend_Controller_Action
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
		
		$model = new Graph_Model_Internal();
		
		$menuID = $this->findMenu("/graph/internal/index");
		Zend_Registry::set("MenuID", $menuID);

		// Search
		$maxYear = $model->findMaxYear();
		$yearInbound = $maxYear;
		//if ($yearInbound > 2015) {
		//	$yearInbound = 2015;
		//}
		if (Zend_Session::namespaceIsset("GraphInternalSearch")) {
			$infoSearch = new Zend_Session_Namespace("GraphInternalSearch");
			$yearInbound = $infoSearch->yearInbound;
			Zend_Session::namespaceUnset("GraphInternalSearch");
		}
		$this->view->yearInbound = $yearInbound;
		
		// Tourist Amount
		//$touristAmount = $model->touristAmount($yearInbound);
		$touristAmount = $model->touristAmount($maxYear);
		$this->view->touristAmount = $touristAmount;

		// Travel Amount
		//$travelAmount = $model->travelAmount($yearInbound);
		$travelAmount = $model->travelAmount($maxYear);
		$this->view->travelAmount = $travelAmount;

		// Live Time Average
		//$livetimeAverage = $model->livetimeAverage($yearInbound);
		$livetimeAverage = $model->livetimeAverage($maxYear);
		$this->view->livetimeAverage = $livetimeAverage;

		// Charges Average
		//$chargesAverage = $model->chargesAverage($yearInbound);
		$chargesAverage = $model->chargesAverage($maxYear);
		$this->view->chargesAverage =  $chargesAverage;

		// Income Province Amount
		$incomeprovinceAmount = $model->incomeprovinceAmount($yearInbound);
		$this->view->incomeprovinceAmount = $incomeprovinceAmount;

		// Income Travel Amount
		$incometravelAmount = $model->incometravelAmount();
		$incometravelAmount = array_reverse($incometravelAmount);
		$this->view->incometravelAmount = $incometravelAmount;

		// All Tourist Amount
		$alltouristAmount = $model->alltouristAmount();
		$alltouristAmount = array_reverse($alltouristAmount);
		$this->view->alltouristAmount = $alltouristAmount;

		
		$years = array( "-" => "ช่วงเวลา" );
		$years[$maxYear] = $maxYear + 543;
		$i;
		for($i = 0;$i < 4; $i++) {
			$years[$maxYear - $i] = $maxYear + 543 - $i;
		}
		
		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );

		$this->view->years = $years;
		$this->view->latestYear = $maxYear;
		$this->view->export = $export;

		$this->view->pageTitle = 'ข้อมูลการท่องเที่ยวภายในประเทศ (Internal Tourism Time Series)';
		

		// $this->renderScript('index/research.phtml');
	}

	function searchAction(){

		$yearInbound = $this->getRequest()->getParam('Year');

		if($yearInbound){ $yearInbound = $yearInbound; }else{ $yearInbound = null; }
		$infoSearch = new Zend_Session_Namespace("GraphInternalSearch");
		$infoSearch->yearInbound = $yearInbound;

		$this->_helper->redirector('index', 'internal', 'graph');
	}


}
?>