<?php

class Story_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
	protected $publicCommentOption = array ('Y' => 'เผยแพร่ทันที', 'N' => 'รอพิจารณา');
	protected $componentTitle = "เรื่องราว";

	public function init()
	{
		$moduleConfig = new Zend_Config_Ini(APPLICATION_PATH . '/modules/story/configs/config.ini', 'text');
		$this->componentTitle = empty($moduleConfig->name) ? $this->componentTitle : $moduleConfig->name;
		$this->view->componentTitle = $this->componentTitle;
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
		$canView = $sUser->isAllow('story.setting.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$languages = System_Model_Setting::getLanguages();
		$defaultLanguage = System_Model_Setting::getDefaultLanguage();
		
		// Get Option
		$storyCategoryModel = new Story_Model_Category();
		$storyCategoryOption = $storyCategoryModel->getRecursiveOption($defaultLanguage["Code"]);
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->articlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Story");
		$data->articleSortType = System_Model_Setting::getValue("ArticleSortType", "Story");
		$data->articleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Story");
		$data->articleShowNoDataCategory = System_Model_Setting::getValue("ArticleShowNoDataCategory", "Story");
		$data->articleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Story");
		$data->articlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Story");
		$data->articleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Story");
		$data->articleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Story");
		$data->articleShowCategory = System_Model_Setting::getValue("ArticleShowCategory", "Story");
		$data->articleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Story");
		$data->articleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Story");
		$data->articleUseFacebookComment = System_Model_Setting::getValue("ArticleUseFacebookComment", "Story");
		
		$data->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Story");
		$data->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Story");
		$data->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Story");
		$data->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Story");
		$data->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Story");
				
		$data->searchByText = System_Model_Setting::getValue("SearchByText", "Story");
		$data->searchCategory1 = System_Model_Setting::getValue("SearchCategory1", "Story");
		$data->searchCategory2 = System_Model_Setting::getValue("SearchCategory2", "Story");
		$data->searchCategory3 = System_Model_Setting::getValue("SearchCategory3", "Story");
		
		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->publicCommentOption = $this->publicCommentOption;
		$this->view->storyCategoryOption = $storyCategoryOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่า" . $this->componentTitle;
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("story.setting", "view", null, null);
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
		$canAdd = $sUser->isAllow('story.setting.manage');
		if (!$canAdd) {
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
		$params["Story"] = array();
		$params["Story"]["ArticlePerPage"] = $requestParams['ArticlePerPage'];
		$params["Story"]["ArticleSortType"] = $requestParams['ArticleSortType'];
		$params["Story"]["ArticleSortDirection"] = $requestParams['ArticleSortDirection'];
		$params["Story"]["ArticleShowNoDataCategory"] = $requestParams['ArticleShowNoDataCategory'];
		$params["Story"]["ArticleCanComment"] = "N"; //$requestParams['ArticleCanComment'];
		$params["Story"]["ArticlePublicComment"] = $requestParams['ArticlePublicComment'];
		$params["Story"]["ArticleAmountColumn"] = $requestParams['ArticleAmountColumn'];
		$params["Story"]["ArticleShowImage"] = $requestParams['ArticleShowImage'];
		$params["Story"]["ArticleShowCategory"] = $requestParams['ArticleShowCategory'];
		$params["Story"]["ArticleShowIntroText"] = $requestParams['ArticleShowIntroText'];
		$params["Story"]["ArticleShowAmount"] = $requestParams['ArticleShowAmount'];
		//$params["Story"]["ArticleUseFacebookComment"] = $requestParams['ArticleUseFacebookComment'];
		
		$params["Story"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["Story"]["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["Story"]["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["Story"]["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["Story"]["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];
		
		$params["Story"]["SearchByText"] = $requestParams['SearchByText'];
		$params["Story"]["SearchCategory1"] = $requestParams['SearchCategory1'];
		$params["Story"]["SearchCategory2"] = $requestParams['SearchCategory2'];
		$params["Story"]["SearchCategory3"] = $requestParams['SearchCategory3'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("story.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

