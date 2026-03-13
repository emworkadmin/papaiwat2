<?php

class Faq_SettingController extends Zend_Controller_Action
{
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('ordering' => 'ลำดับการแสดงผล', 'name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	// protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
	// protected $publicCommentOption = array ('Y' => 'เผยแพร่ทันที', 'N' => 'รอพิจารณา');
	protected $displayOption = array ('Y' => 'แสดงรายการ FAQ', 'N' => 'ซ่อนรายการ FAQ');

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
		$canView = $sUser->isAllow("faq.setting.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->faqDisplaySetting = System_Model_Setting::getValue("FaqDisplaySetting", "FAQ");
		$data->faqMainCategorySortDirection = System_Model_Setting::getValue("FaqMainCategorySortDirection", "FAQ");
		$data->faqSubCategorySortDirection = System_Model_Setting::getValue("FaqSubCategorySortDirection", "FAQ");
		$data->faqSortType = System_Model_Setting::getValue("FaqSortType", "FAQ");
		$data->faqSortDirection = System_Model_Setting::getValue("FaqSortDirection", "FAQ");
		$data->faqLimit = System_Model_Setting::getValue("FaqLimit", "FAQ");

		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		// $this->view->columnAmountOption = $this->columnAmountOption;
		// $this->view->publicCommentOption = $this->publicCommentOption;
		$this->view->displayOption = $this->displayOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่า FAQ";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("faq.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('faq.setting.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		
		// Set Params
		$params = array();
		$params["FAQ"] = array();
		$params["FAQ"]["FaqDisplaySetting"] = $requestParams['FaqDisplaySetting'];
		$params["FAQ"]["FaqMainCategorySortDirection"] = $requestParams['FaqMainCategorySortDirection'];
		$params["FAQ"]["FaqSubCategorySortDirection"] = $requestParams['FaqSubCategorySortDirection'];
		$params["FAQ"]["FaqSortType"] = $requestParams['FaqSortType'];
		$params["FAQ"]["FaqSortDirection"] = $requestParams['FaqSortDirection'];
		$params["FAQ"]["FaqLimit"] = $requestParams['FaqLimit'];
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("faq.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

