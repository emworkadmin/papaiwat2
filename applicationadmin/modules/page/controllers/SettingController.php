<?php

class Page_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
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
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		// TODO: Use isAllow
		$canView = true; // $sUser->isAllow("system_setting");
		$canEdit = true; // $sUser->isAllow("system_setting");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->articlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Content");
		$data->articleSortType = System_Model_Setting::getValue("ArticleSortType", "Content");
		$data->articleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Content");
		$data->articleShowNoDataCategory = System_Model_Setting::getValue("ArticleShowNoDataCategory", "Content");
		$data->articleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Content");
		$data->articleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Content");
		$data->articleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Content");
		$data->articleShowCategory = System_Model_Setting::getValue("ArticleShowCategory", "Content");
		$data->articleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Content");
		$data->articleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Content");
		
		$data->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Content");
		$data->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Content");
		$data->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Content");
		$data->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Content");
		$data->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Content");
				
		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าบทความ";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("content.setting", "view", null, null);
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
		if ($sUser->getInfo()->UserType != 'B') {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}		
		if (false) { // if (!$sUser->isAllow("system_setting")) {
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
		$params["Content"] = array();
		$params["Content"]["ArticlePerPage"] = $requestParams['ArticlePerPage'];
		$params["Content"]["ArticleSortType"] = $requestParams['ArticleSortType'];
		$params["Content"]["ArticleSortDirection"] = $requestParams['ArticleSortDirection'];
		$params["Content"]["ArticleShowNoDataCategory"] = $requestParams['ArticleShowNoDataCategory'];
		$params["Content"]["ArticleCanComment"] = $requestParams['ArticleCanComment'];
		$params["Content"]["ArticleAmountColumn"] = $requestParams['ArticleAmountColumn'];
		$params["Content"]["ArticleShowImage"] = $requestParams['ArticleShowImage'];
		$params["Content"]["ArticleShowCategory"] = $requestParams['ArticleShowCategory'];
		$params["Content"]["ArticleShowIntroText"] = $requestParams['ArticleShowIntroText'];
		$params["Content"]["ArticleShowAmount"] = $requestParams['ArticleShowAmount'];
		
		$params["Content"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["Content"]["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["Content"]["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["Content"]["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["Content"]["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("content.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

