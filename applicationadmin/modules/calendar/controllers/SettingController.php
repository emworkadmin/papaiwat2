<?php

class Calendar_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
	protected $publicCommentOption = array ('Y' => 'เผยแพร่ทันที', 'N' => 'รอพิจารณา');

	public function init()
	{ 
		
	}
	
	public function indexAction()
	{
		// Log Access
		System_Model_Log::access();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		// Check Action Rights
		$canView = $sUser->isAllow('calendar.setting.manage');
		$canAdd = $sUser->isAllow('calendar.setting.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->articlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Calendar");
		$data->articleSortType = System_Model_Setting::getValue("ArticleSortType", "Calendar");
		$data->articleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Calendar");
		$data->articleShowNoDataCategory = System_Model_Setting::getValue("ArticleShowNoDataCategory", "Calendar");
		$data->articleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Calendar");
		$data->articlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Calendar");
		$data->articleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Calendar");
		$data->articleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Calendar");
		$data->articleShowCategory = System_Model_Setting::getValue("ArticleShowCategory", "Calendar");
		$data->articleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Calendar");
		$data->articleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Calendar");
		
		$data->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Calendar");
		$data->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Calendar");
		$data->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Calendar");
		$data->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Calendar");
		$data->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Calendar");
				
		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->publicCommentOption = $this->publicCommentOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าปฏิทินกิจกรรม";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("calendar.setting", "view", null, null);
	}
	
	public function saveAction() {

		// Init
		$result = array();
		$result['result'] = false;
		$result['id'] = null;
		$result['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('calendar.setting.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		
		// Check Param
		
		// Set Params
		$params = array();
		$params["Calendar"] = array();
		$params["Calendar"]["ArticlePerPage"] = $requestParams['ArticlePerPage'];
		$params["Calendar"]["ArticleSortType"] = $requestParams['ArticleSortType'];
		$params["Calendar"]["ArticleSortDirection"] = $requestParams['ArticleSortDirection'];
		$params["Calendar"]["ArticleShowNoDataCategory"] = $requestParams['ArticleShowNoDataCategory'];
		$params["Calendar"]["ArticleCanComment"] = $requestParams['ArticleCanComment'];
		$params["Calendar"]["ArticlePublicComment"] = $requestParams['ArticlePublicComment'];
		$params["Calendar"]["ArticleAmountColumn"] = $requestParams['ArticleAmountColumn'];
		$params["Calendar"]["ArticleShowImage"] = $requestParams['ArticleShowImage'];
		$params["Calendar"]["ArticleShowCategory"] = $requestParams['ArticleShowCategory'];
		$params["Calendar"]["ArticleShowIntroText"] = $requestParams['ArticleShowIntroText'];
		$params["Calendar"]["ArticleShowAmount"] = $requestParams['ArticleShowAmount'];
		
		$params["Calendar"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["Calendar"]["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["Calendar"]["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["Calendar"]["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["Calendar"]["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("calendar.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

