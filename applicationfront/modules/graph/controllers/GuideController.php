<?php
class Graph_GuideController extends Zend_Controller_Action
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
    	
    	$model = new Graph_Model_Guide();

		$year = array( "-" => "ช่วงเวลา" );
		$year[date("Y")] = date("Y") + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$year[date("Y") - $i] = date("Y") + 543 - $i;
		}
		$this->view->year = $year;

		$export = array( "export" => "Export", "pdf" => "pdf", "image" => "image" );
		$this->view->export = $export;
		
		$dummyConfig = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'dummy');
		$isGuideDummy = $dummyConfig->guideDummy;
		$this->view->isGuideDummy = $isGuideDummy;

		$guide_police_year = null;
		$guide_law_year_max_year = $model->findGuideMaxYear();
		if ($isGuideDummy) {
			$guide_law_year = '2016';
		} else {
			$guide_law_year = $guide_law_year_max_year;
		}
		
		$years = array( "-" => "ช่วงเวลา" );
		$years[$guide_law_year_max_year] = $guide_law_year_max_year + 543;
		$i;
		for($i = 0;$i < 5; $i++) {
			$years[$guide_law_year_max_year - $i] = $guide_law_year_max_year + 543 - $i;
		}
		$this->view->years = $years;
		

		if (Zend_Session::namespaceIsset("GraphGuideSearch")) {
			$infoSearch = new Zend_Session_Namespace("GraphGuideSearch");

			$guide_police_year = $infoSearch->guide_police_year;
			$guide_law_year = $infoSearch->guide_law_year;

			Zend_Session::namespaceUnset("GraphGuideSearch");
		}
		$this->view->guide_police_year = $guide_police_year;
		$this->view->guide_law_year = $guide_law_year;

		// _guide_police

		// จำนวนตำรวจท่องเที่ยว
		$proliceAmount = $model->proliceAmount($guide_police_year);
		$this->view->proliceAmount = $proliceAmount;

		// ต่อนักท่องเที่ยว 1 ล้านคน 
		// $prolicepertouristAmount = $model->prolicepertouristAmount($guide_police_year);
		// $this->view->prolicepertouristAmount = $prolicepertouristAmount;

		// จำนวนอาสาสมัครท่องเที่ยว
		$volunteerAmount = $model->volunteerAmount($guide_police_year);
		$this->view->volunteerAmount = $volunteerAmount;

		// ต่อนักท่องเที่ยว 1 ล้านคน 
		// $volunteerpertouristAmount = $model->volunteerpertouristAmount($guide_police_year);
		// $this->view->volunteerpertouristAmount = $volunteerpertouristAmount;

		// จำนวนตำรวจท่องเที่ยว รายจังหวัด
		$policeinprovice = $model->policeinprovice($guide_police_year);
		$this->view->policeinprovice = $policeinprovice;

		// จำนวนอาสาสมัครตำรวจท่องเที่ยว รายจังหวัด 
		$volunteerinprovince = $model->volunteerinprovince($guide_police_year);
		$this->view->volunteerinprovince = $volunteerinprovince;
		$this->view->policeLatestYear = $isGuideDummy ? "2016" : $guide_law_year_max_year;


		// _guide_law

		// จำนวนมัคคุเทศก์ที่ถูกตรวจ 		
		if ($isGuideDummy) {
			$guideAmount = 93794;
		} else {
			$guideAmount = $model->guideAmount($guide_law_year);
		}
		$this->view->guideAmount = $guideAmount;

		// จำนวนที่ถูกจับกุม ปีปัจจุบัน , จำนวนมัคคุเทศก์ที่ถูกจับกุม
		if ($isGuideDummy) {
			$guidearrest = 195;
		} else {
			$guidearrest = $model->guidearrest($guide_law_year);
		}
		$this->view->guidearrest = $guidearrest;

		// หัวข้อการกระทำความผิด ของมัคคุเทศก์ที่ถูกจับ 		
		if ($isGuideDummy) {
			$topicguidearrest = $this->getTopicGuideArrestDummy();
		} else {
			$topicguidearrest = $model->topicguidearrest($guide_law_year);
		}
		$this->view->topicguidearrest = $topicguidearrest;
		
		$this->view->guideLatestYear = $isGuideDummy ? "2016" : $guide_law_year_max_year;
		$this->view->pageTitle = 'ข้อมูลบุคลากรด้านการท่องเที่ยว';
		
		$menuID = $this->findMenu("/graph/guide/index");
		Zend_Registry::set("MenuID", $menuID);
	}
	
	function getTopicGuideArrestDummy() {
		$data = array();
		$data[0][Amount] = 91;
		$data[0][AllegationDetailTH] = 'ประกอบอาชีพมัคคุเทศก์โดยมิได้รับอนุญาต';
		$data[1][Amount] = 33;
		$data[1][AllegationDetailTH] = 'ประกอบธุรกิจนำเที่ยวโดยไม่ได้รับอนุญาตจากนายทะเบียน';
		$data[2][Amount] = 29;
		$data[2][AllegationDetailTH] = 'ไม่ติดบัตรประจำตัวมัคคุเทศก์ขณะทำหน้าที่มัคคุเทศก์';
		$data[3][Amount] = 13;
		$data[3][AllegationDetailTH] = 'ไม่มีใบสั่งงานมัคคุเทศก์ขณะปฏิบัติหน้าที่';
		$data[4][Amount] = 10;
		$data[4][AllegationDetailTH] = 'เป็นมัคคุเทศก์ไม่ปฏิบัติตามระเบียบกำหนด(แต่งกายไม่สุภาพเรียบร้อย)';
		$data[5][Amount] = 7;
		$data[5][AllegationDetailTH] = 'ไม่แสดงใบอนุญาตประกอบธุรกิจนำเที่ยวไว้ในที่เปิดเผยเห็นได้ง่าย ณ สถานประกอบธุรกิจนำเที่ยว';
		$data[6][Amount] = 6;
		$data[6][AllegationDetailTH] = 'มัคคุเทศก์แต่ไม่ปฏิบัติตามระเบียบที่คณะกรรมการกำหนด';
		$data[7][Amount] = 4;
		$data[7][AllegationDetailTH] = 'ไม่รับผิดชอบงานในหน้าที่มัคคุเทศก์';
		$data[8][Amount] = 2;
		$data[8][AllegationDetailTH] = 'เป็นบุคคลต่างด้าวประกอบอาชีพมัคคุเทศก์โดยไม่ได้รับอนุญาต';
		return $data;
	}

	function searchAction(){

		$params = $this->getRequest()->getParams();
		$Year = json_decode($params['Year']);

		$guide_police_year = $Year->guide_police_year;
		$guide_law_year = $Year->guide_law_year;

		if($guide_police_year) { $guide_police_year = $guide_police_year; } else{ $guide_police_year = null; }
		if($guide_law_year) { $guide_law_year = $guide_law_year; } else{ $guide_law_year = null; }

		$infoSearch = new Zend_Session_Namespace("GraphGuideSearch");
		$infoSearch->guide_police_year = $guide_police_year;
		$infoSearch->guide_law_year = $guide_law_year;

		$this->_helper->redirector('index', 'guide', 'graph');
	}

	function policeAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();

		// Set Global
		$menuID = $this->findMenu("/graph/guide/index");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->pageTitle = 'ข้อมูลบุคลากรด้านการท่องเที่ยว';
	}
	
	function lawAction()
	{
		// Log Access
		System_Model_Log::access();

		// Zend Filter
		$filterInt = new Zend_Filter_Int();
		
		// Set Global
		$menuID = $this->findMenu("/graph/guide/index");
		Zend_Registry::set("MenuID", $menuID);

		$this->view->pageTitle = 'ข้อมูลบุคลากรด้านการท่องเที่ยว';
	}
}
?>