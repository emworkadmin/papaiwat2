<?php
class Graph_TravelrankController extends Zend_Controller_Action
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
		
		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );
		$this->view->export = $export;

		$model = new Graph_Model_Travelrank();
		
		$dummyConfig = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'dummy');
		$this->view->TravelRankMixchartDummy = $dummyConfig->TravelRankMixchartDummy;
		$this->view->TravelRankFoodDummy = $dummyConfig->TravelRankFoodDummy;
		$this->view->TravelRankMiceDummy = $dummyConfig->TravelRankMiceDummy;
		$this->view->TravelRankBussinessDummy = $dummyConfig->TravelRankBussinessDummy;
		$this->view->TravelRankGuideDummy = $dummyConfig->TravelRankGuideDummy;

		$MixchartMaxYear = $model->findAccommodationMaxYear();
		$MixchartYear = $MixchartMaxYear;
		$MixcharyYears = array(null => "ช่วงเวลา");
		$MixcharyYears[$MixchartMaxYear] = $MixchartMaxYear + 543;
		for($i = 0; $i < 5; $i++) {
			$MixcharyYears[$MixchartMaxYear - $i] = $MixchartMaxYear + 543 - $i;
		}
		$this->view->MixcharyYears = $MixcharyYears;


		$FoodLatestYear = $model->findLatestYearRestaurant();
		$FoodMaxYear = $model->findfoodMaxYear();
		$FoodYear = $FoodMaxYear;
		$FoodYears = array(null => "ช่วงเวลา");
		$FoodYears[$FoodMaxYear] = $FoodMaxYear + 543;
		for($i = 0; $i < 5; $i++) {
			$FoodYears[$FoodMaxYear - $i] = $FoodMaxYear + 543 - $i;
		}
		$this->view->FoodYears = $FoodYears;

		$miceMaxYear = $model->findMiceMaxYear();
		$miceYear = $miceMaxYear;
		$miceYears = array(null => "ช่วงเวลา");
		for($i =0; $i < 5; $i++){
			$miceYears[$miceMaxYear - $i] = $miceMaxYear + 543 - $i;
		}
		$this->view->miceYears = $miceYears;


		$businessYear = null;
		$travelYear = null;

		if (Zend_Session::namespaceIsset("GraphTravelRankSearch")) {
			$infoSearch = new Zend_Session_Namespace("GraphTravelRankSearch");

			$MixchartYear = $infoSearch->MixchartYear;
			$FoodYear = $infoSearch->foodYear;
			$miceYear = $infoSearch->miceYear;
			$businessYear = $infoSearch->businessYear;
			$travelYear = $infoSearch->travelYear;

			$this->view->businessYear = $businessYear;
			$this->view->travelYear = $travelYear;

			Zend_Session::namespaceUnset("GraphTravelRankSearch");
		}

		if($dummyConfig->TravelRankMixchartDummy){
			$this->view->MixchartYear = $MixchartMaxYear;
		} else {
			$this->view->MixchartYear = $MixchartYear;
			$MixchartYear = $MixchartYear == null ? $MixchartMaxYear : $MixchartYear;
		}

		if($dummyConfig->TravelRankFoodDummy){
			$this->view->FoodYear = $FoodMaxYear;
		} else {
			$this->view->FoodYear = $FoodYear;
			$FoodYear = $FoodYear == null ? $FoodMaxYear : $FoodYear;
		}

		if($dummyConfig->TravelRankMiceDummy){
			$this->view->miceYear = $miceMaxYear;
		} else {
			$this->view->miceYear = $miceYear;
			$miceYear = $miceYear == null ? $miceMaxYear : $miceYear;
		}


		// _travelrank_mixchart
		if($dummyConfig->TravelRankMixchartDummy) {

			$this->view->HotelAmount = 8.25;
			$this->view->HotelAmountText = 'พัน';
			$this->view->RoomAmount = 3.11;
			$this->view->RoomAmountText = 'พัน';
			$this->view->ORAmount = 62.33;
			$this->view->GuestAmount = 63.2;
			$this->view->GuestAmountText = 'ล้าน';
			$this->view->LOSAmount = 2.25;
			$this->view->LOSAmountText = '';
			$this->view->PersonPerRoom = 1.99;
			$this->view->PersonPerRoomText = '';
			
			$guestByQuarter = array();
			$guestByQuarter[0][Amount] = 17600000;
			$guestByQuarter[1][Amount] = 15000000;
			$guestByQuarter[2][Amount] = 13100000;
			$guestByQuarter[3][Amount] = 17400000;
			$this->view->guestByQuarter = $guestByQuarter;

			$orByQuarter = array();
			$orByQuarter[0][Amount] = 70.2;
			$orByQuarter[1][Amount] = 60.3;
			$orByQuarter[2][Amount] = 53.1;
			$orByQuarter[3][Amount] = 65.7;
			$this->view->orByQuarter = $orByQuarter;
			
			$this->view->mixChartLatestYear = $MixchartMaxYear;

		} else {
			
			// จำนวนสถานที่พักแรม
			$HotelAmount = $model->HotelAmount($MixchartYear);
			$HotelAmountResult = $this->SetValueNumber($HotelAmount);
			$this->view->HotelAmount = $HotelAmountResult['number'];
			$this->view->HotelAmountText = $HotelAmountResult['numberText'];

			// จำนวนห้องพัก
			$RoomAmount = $model->RoomAmount($MixchartYear);
			$RoomAmountResult = $this->SetValueNumber($RoomAmount);
			$this->view->RoomAmount = $RoomAmountResult['number'];
			$this->view->RoomAmountText = $RoomAmountResult['numberText'];

			// อัตราการเข้าพักแรม (OR)
			$ORAmount = $model->ORAmount($MixchartYear);
			$this->view->ORAmount = $ORAmount;

			// ผู้เข้าพักแรม
			$GuestAmount = $model->GuestAmount($MixchartYear);
			$GuestAmountResult = $this->SetValueNumber($GuestAmount);
			$this->view->GuestAmount = $GuestAmountResult['number'];
			$this->view->GuestAmountText = $GuestAmountResult['numberText'];

			// ระยะเวลาเข้าพักเฉลี่ย (LOS)
			$LOSAmount = $model->LOSAmount($MixchartYear);
			$LOSAmountResult = $this->SetValueNumber($LOSAmount);
			$this->view->LOSAmount = $LOSAmountResult['number'];
			$this->view->LOSAmountText = $LOSAmountResult['numberText'];

			// จำนวนคนต่อห้องพัก
			$PersonPerRoom = $model->PersonPerRoom($MixchartYear);
			$PersonPerRoomResult = $this->SetValueNumber($PersonPerRoom);
			$this->view->PersonPerRoom = $PersonPerRoomResult['number'];
			$this->view->PersonPerRoomText = $PersonPerRoomResult['numberText'];

			// จำนวนผู้เข้าพักและอัตราการเข้าพัก (OR) รายไตรมาส
			$guestByQuarter = $model->GuestByQuarter($MixchartYear);
			$orByQuarter = $model->OrByQuarter($MixchartYear);
			$this->view->guestByQuarter = $guestByQuarter;
			$this->view->orByQuarter = $orByQuarter;
			
			$this->view->mixChartLatestYear = '2015';
		}


		// _travelrank_food
		if($dummyConfig->TravelRankFoodDummy){

			$RestaurantAmountResult = $this->SetValueNumber(6007);
			$this->view->RestaurantAmount = $RestaurantAmountResult['number'];
			$this->view->RestaurantAmountText = $RestaurantAmountResult['numberText'];
			
			$AvgFoodCostResult = $this->SetValueNumber(563.867574);
			$this->view->AvgFoodCost = $AvgFoodCostResult['number'];
			$this->view->AvgFoodCostText = $AvgFoodCostResult['numberText'];	

			$RestaurantPerProvince = array();
			$RestaurantPerProvince[0][Amount] = 815;
			$RestaurantPerProvince[0][ProvinceNameTH] = 'กรุงเทพมหานคร';
			$RestaurantPerProvince[1][Amount] = 306;
			$RestaurantPerProvince[1][ProvinceNameTH] = 'เชียงใหม่';
			$RestaurantPerProvince[2][Amount] = 272;
			$RestaurantPerProvince[2][ProvinceNameTH] = 'นครราชสีมา';
			$RestaurantPerProvince[3][Amount] = 264;
			$RestaurantPerProvince[3][ProvinceNameTH] = 'ชลบุรี';
			$RestaurantPerProvince[4][Amount] = 239;
			$RestaurantPerProvince[4][ProvinceNameTH] = 'ประจวบคีรีขันธ์';
			$RestaurantPerProvince[5][Amount] = 162;
			$RestaurantPerProvince[5][ProvinceNameTH] = 'สุราษฎร์ธานี';
			$RestaurantPerProvince[6][Amount] = 161;
			$RestaurantPerProvince[6][ProvinceNameTH] = 'ภูเก็ต';
			$RestaurantPerProvince[7][Amount] = 152;
			$RestaurantPerProvince[7][ProvinceNameTH] = 'สระแก้ว';
			$RestaurantPerProvince[8][Amount] = 142;
			$RestaurantPerProvince[8][ProvinceNameTH] = 'เชียงราย';
			$RestaurantPerProvince[9][Amount] = 130;
			$RestaurantPerProvince[9][ProvinceNameTH] = 'นครนายก';
			
			$this->view->RestaurantPerProvince = $RestaurantPerProvince;
			
			$this->view->FoodLatestYear = date('Y');

		} else {

			// จำนวนร้านอาหาร
			$RestaurantAmount = $model->RestaurantAmount($FoodMaxYear);
			$RestaurantAmountResult = $this->SetValueNumber($RestaurantAmount);
			$this->view->RestaurantAmount = $RestaurantAmountResult['number'];
			$this->view->RestaurantAmountText = $RestaurantAmountResult['numberText'];

			// ค่าใช้จ่ายเฉลี่ยต่อคนต่อวัน (ค่าอาหาร)
			$AvgFoodCost = $model->AvgFoodCost($FoodYear);
			$AvgFoodCostResult = $this->SetValueNumber($AvgFoodCost);
			$this->view->AvgFoodCost = $AvgFoodCostResult['number'];
			$this->view->AvgFoodCostText = $AvgFoodCostResult['numberText'];		

			// จำนวนร้านอาหารรายจังหวัด (ร้าน)
			$RestaurantPerProvince = $model->RestaurantPerProvince($FoodMaxYear);
			$this->view->RestaurantPerProvince = $RestaurantPerProvince;

			$this->view->FoodLatestYear = $FoodLatestYear;
		}

		// _travelrank_mice
		if($dummyConfig->TravelRankMiceDummy){

			$MiceIndustry['Data'] = array();
			$MiceIndustry['Data']['Conventions'] = array();
			$MiceIndustry['Data']['Conventions']['Color'] = 'rgba(103, 159, 174, 0.6)';
			$MiceIndustry['Data']['Conventions']['2557'] = array();
			$MiceIndustry['Data']['Conventions']['2557']['Name'] = 'Conventions';
			$MiceIndustry['Data']['Conventions']['2557']['Amount'] = '26737.00';
			$MiceIndustry['Data']['Conventions']['2558'] = array();
			$MiceIndustry['Data']['Conventions']['2558']['Name'] = 'Conventions';
			$MiceIndustry['Data']['Conventions']['2558']['Amount'] = '27339.00';
			$MiceIndustry['Data']['Conventions']['2559'] = array();
			$MiceIndustry['Data']['Conventions']['2559']['Name'] = 'Conventions';
			$MiceIndustry['Data']['Conventions']['2559']['Amount'] = '25789.00';
			$MiceIndustry['Data']['Exhibitions'] = array();
			$MiceIndustry['Data']['Exhibitions']['Color'] = 'rgba(250, 130, 131, 0.6)';
			$MiceIndustry['Data']['Exhibitions']['2557'] = array();
			$MiceIndustry['Data']['Exhibitions']['2557']['Name'] = 'Exhibitions';
			$MiceIndustry['Data']['Exhibitions']['2557']['Amount'] = '13616.00';
			$MiceIndustry['Data']['Exhibitions']['2558'] = array();
			$MiceIndustry['Data']['Exhibitions']['2558']['Name'] = 'Exhibitions';
			$MiceIndustry['Data']['Exhibitions']['2558']['Amount'] = '15214.00';
			$MiceIndustry['Data']['Exhibitions']['2559'] = array();
			$MiceIndustry['Data']['Exhibitions']['2559']['Name'] = 'Exhibitions';
			$MiceIndustry['Data']['Exhibitions']['2559']['Amount'] = '15241.00';
			$MiceIndustry['Data']['Incentives'] = array();
			$MiceIndustry['Data']['Incentives']['Color'] = 'rgba(254, 149, 102, 0.6)';
			$MiceIndustry['Data']['Incentives']['2557'] = array();
			$MiceIndustry['Data']['Incentives']['2557']['Name'] = 'Incentives';
			$MiceIndustry['Data']['Incentives']['2557']['Amount'] = '15274.00';
			$MiceIndustry['Data']['Incentives']['2558'] = array();
			$MiceIndustry['Data']['Incentives']['2558']['Name'] = 'Incentives';
			$MiceIndustry['Data']['Incentives']['2558']['Amount'] = '16136.00';
			$MiceIndustry['Data']['Incentives']['2559'] = array();
			$MiceIndustry['Data']['Incentives']['2559']['Name'] = 'Incentives';
			$MiceIndustry['Data']['Incentives']['2559']['Amount'] = '16217.00';
			$MiceIndustry['Data']['Meetings'] = array();
			$MiceIndustry['Data']['Meetings']['Color'] = 'rgba(2, 184, 171, 0.6)';
			$MiceIndustry['Data']['Meetings']['2557'] = array();
			$MiceIndustry['Data']['Meetings']['2557']['Name'] = 'Meetings';
			$MiceIndustry['Data']['Meetings']['2557']['Amount'] = '25173.00';
			$MiceIndustry['Data']['Meetings']['2558'] = array();
			$MiceIndustry['Data']['Meetings']['2558']['Name'] = 'Meetings';
			$MiceIndustry['Data']['Meetings']['2558']['Amount'] = '28397.00';
			$MiceIndustry['Data']['Meetings']['2559'] = array();
			$MiceIndustry['Data']['Meetings']['2559']['Name'] = 'Meetings';
			$MiceIndustry['Data']['Meetings']['2559']['Amount'] = '23445.00';
			$MiceIndustry['Data']['Mega Events'] = array();
			$MiceIndustry['Data']['Mega Events']['Color'] = 'rgba(254, 200, 15, 0.6)';
			$MiceIndustry['Data']['Mega Events']['2558'] = array();
			$MiceIndustry['Data']['Mega Events']['2558']['Name'] = 'Mega Events';
			$MiceIndustry['Data']['Mega Events']['2558']['Amount'] = '8789.00';
			$MiceIndustry['Data']['Mega Events']['2559'] = array();
			$MiceIndustry['Data']['Mega Events']['2559']['Name'] = 'Mega Events';
			$MiceIndustry['Data']['Mega Events']['2559']['Amount'] = '21799.00';
			$MiceIndustry['Year'] = array();
			$MiceIndustry['Year']['0'] = '2557';
			$MiceIndustry['Year']['1'] = '2558';
			$MiceIndustry['Year']['2'] = '2559';
			$this->view->MiceIndustry = $MiceIndustry;

			$MiceTraveler['Data'] = array();
			$MiceTraveler['Data']['Conventions'] = array();
			$MiceTraveler['Data']['Conventions']['Color'] = 'rgba(103, 159, 174, 0.6)';
			$MiceTraveler['Data']['Conventions']['2557'] = array();
			$MiceTraveler['Data']['Conventions']['2557']['Name'] = 'Conventions';
			$MiceTraveler['Data']['Conventions']['2557']['Amount'] = '287886.00';
			$MiceTraveler['Data']['Conventions']['2558'] = array();
			$MiceTraveler['Data']['Conventions']['2558']['Name'] = 'Conventions';
			$MiceTraveler['Data']['Conventions']['2558']['Amount'] = '294371.00';
			$MiceTraveler['Data']['Conventions']['2559'] = array();
			$MiceTraveler['Data']['Conventions']['2559']['Name'] = 'Conventions';
			$MiceTraveler['Data']['Conventions']['2559']['Amount'] = '298564.00';
			$MiceTraveler['Data']['Exhibitions'] = array();
			$MiceTraveler['Data']['Exhibitions']['Color'] = 'rgba(250, 130, 131, 0.6)';
			$MiceTraveler['Data']['Exhibitions']['2557'] = array();
			$MiceTraveler['Data']['Exhibitions']['2557']['Name'] = 'Exhibitions';
			$MiceTraveler['Data']['Exhibitions']['2557']['Amount'] = '157996.00';
			$MiceTraveler['Data']['Exhibitions']['2558'] = array();
			$MiceTraveler['Data']['Exhibitions']['2558']['Name'] = 'Exhibitions';
			$MiceTraveler['Data']['Exhibitions']['2558']['Amount'] = '174652.00';
			$MiceTraveler['Data']['Exhibitions']['2559'] = array();
			$MiceTraveler['Data']['Exhibitions']['2559']['Name'] = 'Exhibitions';
			$MiceTraveler['Data']['Exhibitions']['2559']['Amount'] = '180480.00';
			$MiceTraveler['Data']['Incentives'] = array();
			$MiceTraveler['Data']['Incentives']['Color'] = 'rgba(254, 149, 102, 0.6)';
			$MiceTraveler['Data']['Incentives']['2557'] = array();
			$MiceTraveler['Data']['Incentives']['2557']['Name'] = 'Incentives';
			$MiceTraveler['Data']['Incentives']['2557']['Amount'] = '240546.00';
			$MiceTraveler['Data']['Incentives']['2558'] = array();
			$MiceTraveler['Data']['Incentives']['2558']['Name'] = 'Incentives';
			$MiceTraveler['Data']['Incentives']['2558']['Amount'] = '254125.00';
			$MiceTraveler['Data']['Incentives']['2559'] = array();
			$MiceTraveler['Data']['Incentives']['2559']['Name'] = 'Incentives';
			$MiceTraveler['Data']['Incentives']['2559']['Amount'] = '263556.00';
			$MiceTraveler['Data']['Meetings'] = array();
			$MiceTraveler['Data']['Meetings']['Color'] = 'rgba(2, 184, 171, 0.6)';
			$MiceTraveler['Data']['Meetings']['2557'] = array();
			$MiceTraveler['Data']['Meetings']['2557']['Name'] = 'Meetings';
			$MiceTraveler['Data']['Meetings']['2557']['Amount'] = '232736.00';
			$MiceTraveler['Data']['Meetings']['2558'] = array();
			$MiceTraveler['Data']['Meetings']['2558']['Name'] = 'Meetings';
			$MiceTraveler['Data']['Meetings']['2558']['Amount'] = '262538.00';
			$MiceTraveler['Data']['Meetings']['2559'] = array();
			$MiceTraveler['Data']['Meetings']['2559']['Name'] = 'Meetings';
			$MiceTraveler['Data']['Meetings']['2559']['Amount'] = '258483.00';
			$MiceTraveler['Data']['Mega Events'] = array();
			$MiceTraveler['Data']['Mega Events']['Color'] = 'rgba(254, 200, 15, 0.6)';
			$MiceTraveler['Data']['Mega Events']['2558'] = array();
			$MiceTraveler['Data']['Mega Events']['2558']['Name'] = 'Mega Events';
			$MiceTraveler['Data']['Mega Events']['2558']['Amount'] = '110309.00';
			$MiceTraveler['Data']['Mega Events']['2559'] = array();
			$MiceTraveler['Data']['Mega Events']['2559']['Name'] = 'Mega Events';
			$MiceTraveler['Data']['Mega Events']['2559']['Amount'] = '272382.00';
			$MiceTraveler['Year'] = array();
			$MiceTraveler['Year']['0'] = '2557';
			$MiceTraveler['Year']['1'] = '2558';
			$MiceTraveler['Year']['2'] = '2559';
			$this->view->MiceTraveler = $MiceTraveler;

			$MiceIncomeActivity = array();
			$MiceIncomeActivity['0']['Amount'] = '25682.00';
			$MiceIncomeActivity['0']['MICETypeEN'] = 'Conventions';
			$MiceIncomeActivity['1']['Amount'] = '14677.00';
			$MiceIncomeActivity['1']['MICETypeEN'] = 'Exhibitions';
			$MiceIncomeActivity['2']['Amount'] = '16117.00';
			$MiceIncomeActivity['2']['MICETypeEN'] = 'Incentives';
			$MiceIncomeActivity['3']['Amount'] = '23380.00';
			$MiceIncomeActivity['3']['MICETypeEN'] = 'Meetings';
			$MiceIncomeActivity['4']['Amount'] = '13234.00';
			$MiceIncomeActivity['4']['MICETypeEN'] = 'Mega Events';
			$this->view->MiceIncomeActivity = $MiceIncomeActivity;
			
			$MiceTravelerActivity = array();
			$MiceTravelerActivity['0']['Amount'] = '297321.00';
			$MiceTravelerActivity['0']['MICETypeEN'] = 'Conventions';
			$MiceTravelerActivity['1']['Amount'] = '173922.00';
			$MiceTravelerActivity['1']['MICETypeEN'] = 'Exhibitions';
			$MiceTravelerActivity['2']['Amount'] = '261932.00';
			$MiceTravelerActivity['2']['MICETypeEN'] = 'Incentives';
			$MiceTravelerActivity['3']['Amount'] = '257767.00';
			$MiceTravelerActivity['3']['MICETypeEN'] = 'Meetings';
			$MiceTravelerActivity['4']['Amount'] = '165367.00';
			$MiceTravelerActivity['4']['MICETypeEN'] = 'Mega Events';
			$this->view->MiceTravelerActivity = $MiceTravelerActivity;
			
			$this->view->miceLatestYear = '2016';

		} else {
			// สถานการณ์การท่องเที่ยวอุตสาหกรรมไมซ์ (MICE Industry) รายได้ (ล้านบาท)
			// รายได้จากการท่องเที่ยวอุตสาหกรรมไมซ์ รายปี (ล้านบาท)
			$MiceIndustry = $model->MiceIndustry($miceMaxYear); 
			$this->view->MiceIndustry = $MiceIndustry;

			// สถานการณ์การท่องเที่ยวอุตสาหกรรมไมซ์ (MICE Industry) จำนวนนักเดินทาง (คน)
			// จำนวนนักเดินทางจากการท่องเที่ยวอุตสาหกรรมไมซ์ รายปี (คน)
			$MiceTraveler = $model->MiceTraveler($miceMaxYear);
			$this->view->MiceTraveler = $MiceTraveler;

			// สถานการณ์การท่องเที่ยวอุตสาหกรรมไมซ์ (MICE Industry) รายได้ แบ่งเป็นรายกิจกรรม (ล้านบาท)
			// รายได้จากการท่องเที่ยวอุตสาหกรรมไมซ์ รายกิจกรรม (ล้านบาท)
			$MiceIncomeActivity = $model->MiceIncomeActivity($miceYear);
			$this->view->MiceIncomeActivity = $MiceIncomeActivity;

			// สถานการณ์การท่องเที่ยวอุตสาหกรรมไมซ์ (MICE Industry) จำนวนนักเดินทาง แบ่งตามรายกิจกรรม 
			// จำนวนนักเดินทางจากการท่องเที่ยวอุตสาหกรรมไมซ์ รายกิจกรรม (คน)
			$MiceTravelerActivity = $model->MiceTravelerActivity($miceYear);
			$this->view->MiceTravelerActivity = $MiceTravelerActivity;
			
			$this->view->miceLatestYear = $miceMaxYear;
		}

		
		$guideLatestYear = $model->findGuideLatestYear();
		
		// _travelrank_business
		if($dummyConfig->TravelRankBussinessDummy){

			$TourismBusinessTypeAmount = array();
			$TourismBusinessTypeAmount[0][Amount] = '6603';
			$TourismBusinessTypeAmount[0][PersonType] = 'นิติบุคคล';
			$TourismBusinessTypeAmount[1][Amount] = '4951';
			$TourismBusinessTypeAmount[1][PersonType] = 'บุคคล';
			$this->view->TourismBusinessTypeAmount = $TourismBusinessTypeAmount;

			$TourismBusinessAmountResult = $this->SetValueNumber(11554);
			$this->view->TourismBusinessAmount = $TourismBusinessAmountResult['number'];
			$this->view->TourismBusinessAmountText = $TourismBusinessAmountResult['numberText'];
			
			$TouragenctAmoutResult = $this->SetValueNumber(10287);
			$this->view->TouragenctAmout = $TouragenctAmoutResult['number'];
			$this->view->TouragenctAmoutText = $TouragenctAmoutResult['numberText'];

			$TouragenctTypeAmout = array();
			$TouragenctTypeAmout['0']['Amount'] = '231';
			$TouragenctTypeAmout['0']['CategoryDescription'] = 'กรณีผ่านการอบรม';
			$TouragenctTypeAmout['1']['Amount'] = '5418';
			$TouragenctTypeAmout['1']['CategoryDescription'] = 'กรณีมัคคุเทศก์';
			$TouragenctTypeAmout['2']['Amount'] = '24';
			$TouragenctTypeAmout['2']['CategoryDescription'] = 'ก่อนกฎกระทรวงฯ';
			$TouragenctTypeAmout['3']['Amount'] = '4614';
			$TouragenctTypeAmout['3']['CategoryDescription'] = 'จบการศึกษาด้านการท่องเที่ยว';
			$this->view->TouragenctTypeAmout = $TouragenctTypeAmout;
			
			$this->view->guideLatestYear = date('Y');
			
		} else {

			// จำนวนผู้ประกอบธุรกิจนำเที่ยว รายประเภท (ราย)
			$TourismBusinessTypeAmount = $model->TourismBusinessTypeAmount($businessYear);
			$this->view->TourismBusinessTypeAmount = $TourismBusinessTypeAmount;

			// จำนวนผู้ประกอบธุรกิจนำเที่ยว
			$TourismBusinessAmount = $model->TourismBusinessAmount($businessYear);
			$TourismBusinessAmountResult = $this->SetValueNumber($TourismBusinessAmount);
			$this->view->TourismBusinessAmount = $TourismBusinessAmountResult['number'];
			$this->view->TourismBusinessAmountText = $TourismBusinessAmountResult['numberText'];	
			
			// จำนวนผู้นำเที่ยว
			$TouragenctAmout = $model->touragenctAmount($businessYear);
			$TouragenctAmoutResult = $this->SetValueNumber($TouragenctAmout);
			$this->view->TouragenctAmout = $TouragenctAmoutResult['number'];
			$this->view->TouragenctAmoutText = $TouragenctAmoutResult['numberText'];	

			// จำนวนผู้นำเที่ยว รายประเภท (คน)
			$TouragenctTypeAmout = $model->TouragenctTypeAmount($businessYear);
			$this->view->TouragenctTypeAmout = $TouragenctTypeAmout;
			
			$this->view->guideLatestYear = $guideLatestYear;
		}


		// _travelrank_guide_travel
		if($dummyConfig->TravelRankGuideDummy){

			$GuideAmountResult = $this->SetValueNumber(74744);
			$this->view->GuideAmount = $GuideAmountResult['number'];
			$this->view->GuideAmountText = $GuideAmountResult['numberText'];	

			$TouristAmountResult = $this->SetValueNumber(94040139);
			$this->view->TouristAmount = $TouristAmountResult['number'];
			$this->view->TouristAmountText = $TouristAmountResult['numberText'];

			$GuideTypeAmount = array();
			$GuideTypeAmount['0']['Amount'] = '20273';
			$GuideTypeAmount['0']['CategoryDescription'] = 'ประเภท เฉพาะพื้นที่ (ให้บริการนำเที่ยวแก่นักท่องเที่ยวไทยและต่างประเทศ)';
			$GuideTypeAmount['1']['Amount'] = '419';
			$GuideTypeAmount['1']['CategoryDescription'] = 'ประเภท เฉพาะพื้นที่ (ให้บริการนำเที่ยวเฉพาะนักท่องเที่ยวไทย)';
			$GuideTypeAmount['2']['Amount'] = '206';
			$GuideTypeAmount['2']['CategoryDescription'] = 'ประเภท เดินป่า (ให้บริการนำเที่ยวแก่นักท่องเที่ยวไทยและต่างประเทศในเขตป่า)';
			$GuideTypeAmount['3']['Amount'] = '49595';
			$GuideTypeAmount['3']['CategoryDescription'] = 'ประเภท ทั่วไป (ให้บริการนำเที่ยวแก่นักท่องเที่ยวไทยและต่างประเทศ)';
			$GuideTypeAmount['4']['Amount'] = '742';
			$GuideTypeAmount['4']['CategoryDescription'] = 'ประเภท ทั่วไป (ให้บริการนำเที่ยวเฉพาะนักท่องเที่ยวไทย)';
			$GuideTypeAmount['5']['Amount'] = '33';
			$GuideTypeAmount['5']['CategoryDescription'] = 'ประเภท ทางทะเล (ให้บริการนำเที่ยวแก่นักท่องเที่ยวไทยและต่างประเทศทางทะเล)';
			$GuideTypeAmount['6']['Amount'] = '3029';
			$GuideTypeAmount['6']['CategoryDescription'] = 'ประเภท ทางทะเลชายฝั่ง (ให้บริการนำเที่ยวแก่นักท่องเที่ยวไทยและต่างประเทศทางทะเลชายฝั่ง ไม่เกิน 40 ไมล์ทะเล)';
			$GuideTypeAmount['7']['Amount'] = '4';
			$GuideTypeAmount['7']['CategoryDescription'] = 'ประเภท ศิลปวัฒนธรรม (ให้บริการนำเที่ยวแก่นักท่องเที่ยวไทยและต่างประเทศด้านศิลปวัฒนธรรม)';
			$GuideTypeAmount['8']['Amount'] = '200';
			$GuideTypeAmount['8']['CategoryDescription'] = 'ประเภทเฉพาะ วัฒนธรรมท้องถิ่น (ให้บริการนำเที่ยวแก่นักท่องเที่ยวไทยและต่างประเทศด้านวัฒนธรรมท้องถิ่น)';
			$GuideTypeAmount['9']['Amount'] = '243';
			$GuideTypeAmount['9']['CategoryDescription'] = 'ประเภทเฉพาะ แหล่งท่องเที่ยวธรรมชาติ (ให้บริการนำเที่ยวแก่นักท่องเที่ยวไทยและต่างประเทศในแหล่งท่องเที่ยวธรรมชาติ)';
			$this->view->GuideTypeAmount = $GuideTypeAmount;

			$GuideLanguageAmount = array();
			$GuideLanguageAmount['0']['Amount'] = '47054';
			$GuideLanguageAmount['0']['LanguageTH'] = 'อื่นๆ';
			$GuideLanguageAmount['1']['Amount'] = '21588';
			$GuideLanguageAmount['1']['LanguageTH'] = 'จีน';
			$GuideLanguageAmount['2']['Amount'] = '4401';
			$GuideLanguageAmount['2']['LanguageTH'] = 'ญี่ปุ่น';
			$GuideLanguageAmount['3']['Amount'] = '1548';
			$GuideLanguageAmount['3']['LanguageTH'] = 'ฝรั่งเศส';
			$GuideLanguageAmount['4']['Amount'] = '825';
			$GuideLanguageAmount['4']['LanguageTH'] = 'เยอรมัน';
			$GuideLanguageAmount['5']['Amount'] = '705';
			$GuideLanguageAmount['5']['LanguageTH'] = 'ไทย';
			$GuideLanguageAmount['6']['Amount'] = '473';
			$GuideLanguageAmount['6']['LanguageTH'] = 'อินโดนีเซีย';
			$GuideLanguageAmount['7']['Amount'] = '462';
			$GuideLanguageAmount['7']['LanguageTH'] = 'รัสเซีย';
			$GuideLanguageAmount['8']['Amount'] = '432';
			$GuideLanguageAmount['8']['LanguageTH'] = 'มาลาวี';
			$GuideLanguageAmount['9']['Amount'] = '426';
			$GuideLanguageAmount['9']['LanguageTH'] = 'สเปน';
			$this->view->GuideLanguageAmount = $GuideLanguageAmount;
			
			$this->view->guideLatestYear = date('Y');
		} else {

			// จำนวนมัคคุเทศก์
			$GuideAmount = $model->GuideAmount($travelYear);
			$GuideAmountResult = $this->SetValueNumber($GuideAmount);
			$this->view->GuideAmount = $GuideAmountResult['number'];
			$this->view->GuideAmountText = $GuideAmountResult['numberText'];	

			// จำนวนนักท่องเที่ยว
			$TouristAmount = $model->TouristAmount($travelYear);
			$TouristAmountResult = $this->SetValueNumber($TouristAmount);
			$this->view->TouristAmount = $TouristAmountResult['number'];
			$this->view->TouristAmountText = $TouristAmountResult['numberText'];

			// ข้อมูลมัคคุเทศก์ แบ่งตามประเภทการให้บริการแก่นักท่องเที่ยว - แบ่งตามรายประเภทการให้บริการ
			$GuideTypeAmount = $model->GuideTypeAmount($travelYear);
			$this->view->GuideTypeAmount = $GuideTypeAmount;

			// ข้อมูลมัคคุเทศก์ แบ่งตามประเภทการให้บริการแก่นักท่องเที่ยว - แบ่งตามภาษาที่ให้บริการ 
			$GuideLanguageAmount = $model->GuideLanguageAmount($travelYear);
			$this->view->GuideLanguageAmount = $GuideLanguageAmount;
			
			$this->view->guideLatestYear = $guideLatestYear;
		}



		$this->view->pageTitle = "ข้อมูลธุรกิจบริการด้านการท่องเที่ยว";

		$menuID = $this->findMenu("/graph/travelrank/index");
		Zend_Registry::set("MenuID", $menuID);
	}

	function searchAction() {

		$params = $this->getRequest()->getParams();
		$Year = json_decode($params['Year']);

		$MixchartYear = $Year->MixchartYear;
		$foodYear = $Year->foodYear;
		$miceYear = $Year->miceYear;
		$businessYear = $Year->businessYear;
		$travelYear = $Year->travelYear;

		if($MixchartYear) { $MixchartYear = $MixchartYear; } else{ $MixchartYear = null; }
		if($foodYear) { $foodYear = $foodYear; } else{ $foodYear = null; }
		if($miceYear) { $miceYear = $miceYear; } else{ $miceYear = null; }
		if($businessYear) { $businessYear = $businessYear; } else{ $businessYear = null; }
		if($travelYear) { $travelYear = $travelYear; } else{ $travelYear = null; }

		$infoSearch = new Zend_Session_Namespace("GraphTravelRankSearch");
		$infoSearch->MixchartYear = $MixchartYear;
		$infoSearch->foodYear = $foodYear;
		$infoSearch->miceYear = $miceYear;
		$infoSearch->businessYear = $businessYear;
		$infoSearch->travelYear = $travelYear;

		$this->_helper->redirector('index', 'travelrank', 'graph');
	}

	function miceAction()
    {
		// Log Access
		System_Model_Log::access();
		
		$this->view->pageTitle = 'สถานการณ์อุตสาหกรรมไมซ์ (MICE Industry)';
				
		$menuID = $this->findMenu("/graph/travelrank/index");
		Zend_Registry::set("MenuID", $menuID);
	}

}
?>