<?php

class Ebook_SettingController extends Zend_Controller_Action
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
		if ($sUser->getInfo()->UserType != 'B') {
			$this->_helper->redirector('index', 'index', 'default');
		}
		// TODO: Use isAllow
		$canView = $sUser->isAllow("ebook.setting.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->ebookPerPage = System_Model_Setting::getValue("EbookPerPage", "Ebook");
		$data->ebookSortType = System_Model_Setting::getValue("EbookSortType", "Ebook");
		$data->ebookSortDirection = System_Model_Setting::getValue("EbookSortDirection", "Ebook");
		$data->ebookShowNoDataCategory = System_Model_Setting::getValue("EbookShowNoDataCategory", "Ebook");
		$data->ebookCanComment = System_Model_Setting::getValue("EbookCanComment", "Ebook");
		$data->ebookPublicComment = System_Model_Setting::getValue("EbookPublicComment", "Ebook");
		$data->ebookAmountColumn = System_Model_Setting::getValue("EbookAmountColumn", "Ebook");
		$data->ebookShowImage = System_Model_Setting::getValue("EbookShowImage", "Ebook");
		$data->ebookShowCategory = System_Model_Setting::getValue("EbookShowCategory", "Ebook");
		$data->ebookShowIntroText = System_Model_Setting::getValue("EbookShowIntroText", "Ebook");
		$data->ebookShowAmount = System_Model_Setting::getValue("EbookShowAmount", "Ebook");
		
		$data->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Ebook");
		$data->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Ebook");
		$data->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Ebook");
		$data->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Ebook");
		$data->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Ebook");

		$data->maximumSizePDF = System_Model_Setting::getValue("MaximumSizePDF", "Ebook");
		$data->maximumSizeImage = System_Model_Setting::getValue("MaximumSizeImage", "Ebook");
				
		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->publicCommentOption = $this->publicCommentOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าหนังสือ";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าหนังสือ')
		);
		
		// Log Acion
		System_Model_Log::action("ebook.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('ebook.setting.manage');
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
		$params["Ebook"] = array();
		$params["Ebook"]["EbookPerPage"] = $requestParams['EbookPerPage'];
		$params["Ebook"]["EbookSortType"] = $requestParams['EbookSortType'];
		$params["Ebook"]["EbookSortDirection"] = $requestParams['EbookSortDirection'];
		$params["Ebook"]["EbookShowNoDataCategory"] = $requestParams['EbookShowNoDataCategory'];
		$params["Ebook"]["EbookCanComment"] = $requestParams['EbookCanComment'];
		$params["Ebook"]["EbookPublicComment"] = $requestParams['EbookPublicComment'];
		$params["Ebook"]["EbookAmountColumn"] = $requestParams['EbookAmountColumn'];
		$params["Ebook"]["EbookShowImage"] = $requestParams['EbookShowImage'];
		$params["Ebook"]["EbookShowCategory"] = $requestParams['EbookShowCategory'];
		$params["Ebook"]["EbookShowIntroText"] = $requestParams['EbookShowIntroText'];
		$params["Ebook"]["EbookShowAmount"] = $requestParams['EbookShowAmount'];
		
		$params["Ebook"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["Ebook"]["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["Ebook"]["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["Ebook"]["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["Ebook"]["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];

		$params["Ebook"]["MaximumSizeImage"] = $requestParams['MaximumSizeImage'];
		$params["Ebook"]["MaximumSizePDF"] = $requestParams['MaximumSizePDF'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("ebook.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

