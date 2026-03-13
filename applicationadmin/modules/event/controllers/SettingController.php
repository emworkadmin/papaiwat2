<?php
class Event_SettingController extends Zend_Controller_Action
{
    protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'publishdate' => 'วันที่เผยแพร่'/*, 'viewamount' => 'ความนิยม'*/);
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
    protected $publicCommentOption = array ('Y' => 'เผยแพร่ทันที', 'N' => 'รอพิจารณา');
    
    function initAction()
    {

    }

    function indexAction()
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
		$canView = $sUser->isAllow('event.setting.manage');
		$canAdd = $sUser->isAllow('event.setting.manage');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
        $model = new System_Model_Setting();

		$data = new stdClass();
		$data->eventPerPage = System_Model_Setting::getValue("EventPerPage", "Event");
		$data->eventSortType = System_Model_Setting::getValue("EventSortType", "Event");
		$data->eventSortDirection = System_Model_Setting::getValue("EventSortDirection", "Event");
		$data->eventShowImage = System_Model_Setting::getValue("EventShowImage", "Event");
		$data->eventShowCategory = System_Model_Setting::getValue("EventShowCategory", "Event");
		$data->eventShowStartDate = System_Model_Setting::getValue("EventShowStartDate", "Event");
		$data->eventShowEndDate = System_Model_Setting::getValue("EventShowEndDate", "Event");
		$data->eventShowPublishDate = System_Model_Setting::getValue("EventShowPublishDate", "Event");
		$data->eventShowPlace = System_Model_Setting::getValue("EventShowPlace", "Event");
		$data->eventShowCreator = System_Model_Setting::getValue("EventShowCreator", "Event");

		$data->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Event");
		$data->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Event");
		$data->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Event");
		$data->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Event");
		$data->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Event");        

		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->publicCommentOption = $this->publicCommentOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่ากิจกรรม";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("event.setting", "view", null, null);        
    }

    function saveAction()
    {
        $result = array();
        $result['result'] = false;
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
		$canSave = $sUser->isAllow('event.setting.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}   

		// Param
        $requestParams = $this->getRequest()->getParams();
        
        // checkParams
        $params = array();
		$params["Event"] = array();
		$params["Event"]["EventPerPage"] = $requestParams['EventPerPage'];
		$params["Event"]["EventSortType"] = $requestParams['EventSortType'];
		$params["Event"]["EventSortDirection"] = $requestParams['EventSortDirection'];
		$params["Event"]["EventShowImage"] = $requestParams['EventShowImage'];
		$params["Event"]["EventShowCategory"] = $requestParams['EventShowCategory'];
		$params["Event"]["EventShowStartDate"] = $requestParams['EventShowStartDate'];
		$params["Event"]["EventShowEndDate"] = $requestParams['EventShowEndDate'];
		$params["Event"]["EventShowPublishDate"] = $requestParams['EventShowPublishDate'];
		$params["Event"]["EventShowPlace"] = $requestParams['EventShowPlace'];
		$params["Event"]["EventShowCreator"] = $requestParams['EventShowCreator'];

		$params["Event"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["Event"]["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["Event"]["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["Event"]["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["Event"]["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];

        // Model
		$model = new System_Model_Setting();

		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("event.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
    }
}
?>