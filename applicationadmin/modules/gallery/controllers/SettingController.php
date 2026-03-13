<?php

class Gallery_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อห้องภาพ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม', 'ordering' => 'ลำดับการแสดงผล');
	protected $sortCategoryTypeOption = array ('name' => 'ชื่อหมวดหมู่ห้องแสดงภาพ', 'created' => 'วันที่สร้าง');
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
		$canView = $sUser->isAllow('gallery.setting.manage');
		$canAdd = $sUser->isAllow('gallery.setting.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}

		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->articlePerPage = System_Model_Setting::getValue("ArticlePerPage", "Gallery");
		$data->articleSortType = System_Model_Setting::getValue("ArticleSortType", "Gallery");
		$data->articleSortDirection = System_Model_Setting::getValue("ArticleSortDirection", "Gallery");
		$data->articleShowNoDataCategory = System_Model_Setting::getValue("ArticleShowNoDataCategory", "Gallery");
		$data->articleCanComment = System_Model_Setting::getValue("ArticleCanComment", "Gallery");
		$data->articlePublicComment = System_Model_Setting::getValue("ArticlePublicComment", "Gallery");
		$data->articleAmountColumn = System_Model_Setting::getValue("ArticleAmountColumn", "Gallery");
		$data->articleShowImage = System_Model_Setting::getValue("ArticleShowImage", "Gallery");
		$data->articleShowCategory = System_Model_Setting::getValue("ArticleShowCategory", "Gallery");
		$data->articleShowIntroText = System_Model_Setting::getValue("ArticleShowIntroText", "Gallery");
		$data->articleShowAmount = System_Model_Setting::getValue("ArticleShowAmount", "Gallery");
		
		$data->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Gallery");
		$data->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Gallery");
		$data->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Gallery");
		$data->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Gallery");
		$data->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Gallery");
		$data->categorySortType = System_Model_Setting::getValue("CategorySortType", "Gallery");
		$data->categorySortDirection = System_Model_Setting::getValue("CategorySortDirection", "Gallery");
		$data->SizeUploadFile = System_Model_Setting::getValue("SizeUploadFile", "Gallery");
				
		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortCategoryTypeOption = $this->sortCategoryTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->publicCommentOption = $this->publicCommentOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าห้องภาพ";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("gallery.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('gallery.setting.manage');
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
		$params["Gallery"] = array();
		$params["Gallery"]["ArticlePerPage"] = $requestParams['ArticlePerPage'];
		$params["Gallery"]["ArticleSortType"] = $requestParams['ArticleSortType'];
		$params["Gallery"]["ArticleSortDirection"] = $requestParams['ArticleSortDirection'];
		$params["Gallery"]["ArticleShowNoDataCategory"] = $requestParams['ArticleShowNoDataCategory'];
		$params["Gallery"]["ArticleCanComment"] = $requestParams['ArticleCanComment'];
		$params["Gallery"]["ArticlePublicComment"] = $requestParams['ArticlePublicComment'];
		$params["Gallery"]["ArticleAmountColumn"] = $requestParams['ArticleAmountColumn'];
		$params["Gallery"]["ArticleShowImage"] = $requestParams['ArticleShowImage'];
		$params["Gallery"]["ArticleShowCategory"] = $requestParams['ArticleShowCategory'];
		$params["Gallery"]["ArticleShowIntroText"] = $requestParams['ArticleShowIntroText'];
		$params["Gallery"]["ArticleShowAmount"] = $requestParams['ArticleShowAmount'];
		
		$params["Gallery"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["Gallery"]["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["Gallery"]["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["Gallery"]["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["Gallery"]["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];
		$params["Gallery"]["CategorySortType"] = $requestParams['CategorySortType'];
		$params["Gallery"]["CategorySortDirection"] = $requestParams['CategorySortDirection'];
		$params["Gallery"]["SizeUploadFile"] = $requestParams['maxsize'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("gallery.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

