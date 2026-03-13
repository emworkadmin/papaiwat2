<?php
class Graph_TravelplaceController extends Zend_Controller_Action
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
	
	function SetValueNumber($number)
	{
		if ($number >= 1000000) {
			$number = $number / 1000000;
			$numberText = 'ล้าน';
		} elseif ($number >= 1000) {
			$number = $number / 1000;
			$numberText = 'พัน';
		} else {
			$number = $number;
			$numberText = '';
		}
		$result['number'] = $number;
		$result['numberText'] = $numberText;
		return $result;
	}

    function indexAction()
    {
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$model = new Graph_Model_Index();

		// Set Global
		$menuID = $this->findMenu("/graph/travelplace/index");
		Zend_Registry::set("MenuID", $menuID);

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );

		$model = new Graph_Model_Travelplace();
		$dummyConfig = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'dummy');
		$this->view->TravelPlaceNationDummy = $dummyConfig->TravelPlaceNationDummy;
		$this->view->TravelPlaceLocalDummy = $dummyConfig->TravelPlaceLocalDummy;
		
		$nationalParkMaxYear = $model->findNationalParkMaxYear();
		$nationalParkYear = $nationalParkMaxYear;
		$nationalParkYears = array(null => "ช่วงเวลา");
		$nationalParkYears[$nationalParkMaxYear] = $nationalParkMaxYear + 543;
		for($i = 0;$i < 5; $i++) {
			$nationalParkYears[$nationalParkMaxYear - $i] = $nationalParkMaxYear + 543 - $i;
		}
		$this->view->nationalParkYears = $nationalParkYears;
		$this->view->nationalParkLatestYear = $dummyConfig->TravelPlaceNationDummy ? "2016" : $nationalParkMaxYear;

		$incomdeMaxYear = $model->findIncomdeMaxYear();

		if (Zend_Session::namespaceIsset("TravelplaceSearch")) {
			$infoSearch = new Zend_Session_Namespace("TravelplaceSearch");
			$nationalParkYear = $infoSearch->nationalParkYear;

			Zend_Session::namespaceUnset("TravelplaceSearch");
		}

		if($dummyConfig->TravelPlaceNationDummy){
			$this->view->nationalParkYear = $nationalParkMaxYear;
		} else {
			$this->view->nationalParkYear = $nationalParkYear;
			$nationalParkYear = $nationalParkYear == null ? $nationalParkMaxYear : $nationalParkYear;
		}

		// จำนวนแหล่งท่องเที่ยวในพื้นที่อนุรักษ์
		$nationalparkAmount = $model->nationalparkAmount();
		$this->view->nationalparkAmount = $nationalparkAmount['0']['Amount'];

		// จำนวนนักท่องเที่ยวที่เดินทางมาเที่ยวในพื้นที่อนุรักษ์
		$nationalparktouristAmount = $model->nationalparktouristAmount($nationalParkMaxYear); 
		if($nationalparktouristAmount){
			$this->view->nationalparktouristAmount = $nationalparktouristAmount;
		}else{
			$this->view->nationalparktouristAmount = 12186458;
		}

		// ร้อยละความพึงพอใจของนักท่องเที่ยวในพื้นที่อนุรักษ์
		$nationalparkPercent = $model->nationalparkPercent($nationalParkMaxYear);
		if ($nationalparkPercent>0) {
			$this->view->nationalparkPercent = $nationalparkPercent;
		} else {
			$this->view->nationalparkPercent = '79.46'; 
		}
	
		if($dummyConfig->TravelPlaceNationDummy){

			$nationaltouristAmount = array();
			$nationaltouristAmount['0']['Amount'] = '4514499';
			$nationaltouristAmount['0']['TravelerNationalityTypeNameTH'] = 'ชาวต่างประเทศ';
			$nationaltouristAmount['1']['Amount'] = '12174500';
			$nationaltouristAmount['1']['TravelerNationalityTypeNameTH'] = 'ชาวไทย';
			$this->view->nationaltouristAmount = $nationaltouristAmount;
			$this->view->nationaltouristThaiAmount = "8525214";
			$this->view->nationaltouristForeignersAmount = "3661224";

			$nationalparktouristStatic = array();
			$nationalparktouristStatic['0']['Amount'] = '1245281';
			$nationalparktouristStatic['0']['M'] = 'ม.ค.';
			$nationalparktouristStatic['1']['Amount'] = '1101149';
			$nationalparktouristStatic['1']['M'] = 'ก.พ.';
			$nationalparktouristStatic['2']['Amount'] = '2156111';
			$nationalparktouristStatic['2']['M'] = 'มี.ค.';
			$nationalparktouristStatic['3']['Amount'] = '2145120';
			$nationalparktouristStatic['3']['M'] = 'เม.ย.';
			$nationalparktouristStatic['4']['Amount'] = '1400351';
			$nationalparktouristStatic['4']['M'] = 'พ.ค.';
			$nationalparktouristStatic['5']['Amount'] = '1481110';
			$nationalparktouristStatic['5']['M'] = 'มิ.ย.';
			$nationalparktouristStatic['6']['Amount'] = '2110196';
			$nationalparktouristStatic['6']['M'] = 'ก.ค.';
			$nationalparktouristStatic['7']['Amount'] = '1211727';
			$nationalparktouristStatic['7']['M'] = 'ส.ค.';
			$nationalparktouristStatic['8']['Amount'] = '706346';
			$nationalparktouristStatic['8']['M'] = 'ก.ย.';
			$nationalparktouristStatic['9']['Amount'] = '1301628';
			$nationalparktouristStatic['9']['M'] = 'ต.ค.';
			$nationalparktouristStatic['10']['Amount'] = '1081673';
			$nationalparktouristStatic['10']['M'] = 'พ.ย.';
			$nationalparktouristStatic['11']['Amount'] = '748307';
			$nationalparktouristStatic['11']['M'] = 'ธ.ค.';
			$this->view->nationalparktouristStatic = $nationalparktouristStatic;

			$mostnationalparktravelAmount = array();
			$mostnationalparktravelAmount['0']['Amount'] = '1731573';
			$mostnationalparktravelAmount['0']['NationalParkNameTH'] = 'หาดนพรัตน์ธารา-หมู่เกาะพีพี';
			$mostnationalparktravelAmount['1']['Amount'] = '1371741';
			$mostnationalparktravelAmount['1']['NationalParkNameTH'] = 'อ่าวพังงา';
			$mostnationalparktravelAmount['2']['Amount'] = '1276373';
			$mostnationalparktravelAmount['2']['NationalParkNameTH'] = 'เขาใหญ่';
			$mostnationalparktravelAmount['3']['Amount'] = '806133';
			$mostnationalparktravelAmount['3']['NationalParkNameTH'] = 'ดอยอินทนนท์';
			$mostnationalparktravelAmount['4']['Amount'] = '691721';
			$mostnationalparktravelAmount['4']['NationalParkNameTH'] = 'เอราวัณ';
			$mostnationalparktravelAmount['5']['Amount'] = '667465';
			$mostnationalparktravelAmount['5']['NationalParkNameTH'] = 'น้ำตกพลิ้ว';
			$mostnationalparktravelAmount['6']['Amount'] = '583012';
			$mostnationalparktravelAmount['6']['NationalParkNameTH'] = 'น้ำตกเจ็ดสาวน้อย';
			$mostnationalparktravelAmount['7']['Amount'] = '490913';
			$mostnationalparktravelAmount['7']['NationalParkNameTH'] = 'หมู่เกาะสิมิลัน';
			$mostnationalparktravelAmount['8']['Amount'] = '452147';
			$mostnationalparktravelAmount['8']['NationalParkNameTH'] = 'ตาดโตน';
			$mostnationalparktravelAmount['9']['Amount'] = '342144';
			$mostnationalparktravelAmount['9']['NationalParkNameTH'] = 'เขาสก';
			$this->view->mostnationalparktravelAmount = $mostnationalparktravelAmount;

		} else {
			// จำนวนนักท่องเที่ยวในพื้นที่อนุรักษ์
			$nationaltouristAmount = $model->nationaltouristAmount($nationalParkYear);
			$this->view->nationaltouristAmount = $nationaltouristAmount;
			$this->view->nationaltouristThaiAmount = "0";
			$this->view->nationaltouristForeignersAmount = "0";
			if (count($nationaltouristAmount) > 0) {
				foreach ($nationaltouristAmount as $nationaltouristAmountItem) {
					if ($nationaltouristAmountItem["CountryID"] == "1") {
						$this->view->nationaltouristThaiAmount = $nationaltouristAmountItem["Amount"];
					} elseif ($nationaltouristAmountItem["CountryID"] == "2") {
						$this->view->nationaltouristForeignersAmount = $nationaltouristAmountItem["Amount"];
					}
				}
			}

			// สถิติแนวโน้มของนักท่องเที่ยวที่เดินทางมาเที่ยวในพื้นที่อนุรักษ์
			$nationalparktouristStatic = $model->nationalparktouristStatic($nationalParkYear);
			$this->view->nationalparktouristStatic = $nationalparktouristStatic;

			// เขตพื้นที่อนุรักษ์ที่มีนักท่องเที่ยวมาเยี่ยมเยือนมากที่สุด (ล้านคน)
			$mostnationalparktravelAmount = $model->mostnationalparktravelAmount($nationalParkYear);
			$this->view->mostnationalparktravelAmount = $mostnationalparktravelAmount;
		}
		
		




		// อพท.
		if($dummyConfig->TravelPlaceLocalDummy){

			$this->view->incomebeforeAmount = 1019502;

			$this->view->incomeafterAmount = 1946904;

			$rategrowthincomeAmount = $model->rategrowthincomeAmount(1946904, 1019502);
			$this->view->rategrowthincomeAmount = $rategrowthincomeAmount;
			
			$communitydevelopmentplanData = array();
			$communitydevelopmentplanData['0']['Amount'] = '-28.51219725828';
			$communitydevelopmentplanData['0']['Y'] = '2556';
			$communitydevelopmentplanData['1']['Amount'] = '90.966196076013';
			$communitydevelopmentplanData['1']['Y'] = '2557';
			$this->view->communitydevelopmentplanData = $communitydevelopmentplanData;

		} else {
			// Income Before Amount
			$incomebeforeAmount = $model->incomebeforeAmount($incomdeMaxYear);
			if($incomebeforeAmount['0']['Amount']){
				$incomebeforeAmount = $incomebeforeAmount['0']['Amount'];
			}else{
				$incomebeforeAmount = 1019502;
			}
			$this->view->incomebeforeAmount = $incomebeforeAmount;

			// Income After Amount
			$incomeafterAmount = $model->incomeafterAmount($incomdeMaxYear);
			if($incomeafterAmount['0']['Amount']){
				$incomeafterAmount = $incomeafterAmount['0']['Amount'];
			}else{
				$incomeafterAmount = 1946904;
			}
			$this->view->incomeafterAmount = $incomeafterAmount;

			// Rate Growth Income Amount
			$rategrowthincomeAmount = $model->rategrowthincomeAmount($incomeafterAmount, $incomebeforeAmount);
			$this->view->rategrowthincomeAmount = $rategrowthincomeAmount;

			// Community Development Plan Data
			$communitydevelopmentplanData = $model->communitydevelopmentplanData($incomdeMaxYear);
			$this->view->communitydevelopmentplanData = $communitydevelopmentplanData;
		}
		$this->view->incomeLatestYear = $dummyConfig->TravelPlaceLocalDummy ? "2014" : $incomdeMaxYear;

		$this->view->export = $export;
		$this->view->pageTitle = 'ข้อมูลด้านการท่องเที่ยวในพื้นที่อนุรักษ์และเขตพื้นที่พิเศษ';
	}

	function placenationAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$model = new Graph_Model_Index();

		// Set Global
		$menuID = $this->findMenu("/graph/travelplace/index");
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
		$this->view->pageTitle = 'ข้อมูลสถิติด้านการวางแผนด้านการท่องเที่ยวในพื้นที่เขตอนุรักษ์';
	}

	function placelocalAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		$model = new Graph_Model_Index();

		// Set Global
		$menuID = $this->findMenu("/graph/travelplace/index");
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
		$this->view->pageTitle = 'ข้อมูลสถิติด้านการวางแผนด้านการท่องเที่ยวในพื้นที่เขตชุมชน';
	}

	function searchAction(){

		$nationalParkYear = $this->getRequest()->getParam('nationalParkYear');

		if($nationalParkYear){ $nationalParkYear = $nationalParkYear; }else{ $nationalParkYear = null; }
		$infoSearch = new Zend_Session_Namespace("TravelplaceSearch");
		$infoSearch->nationalParkYear = $nationalParkYear;

		$this->_helper->redirector('index', 'travelplace', 'graph');
	}

}
?>