<?php
cLass Orgchart_SettingController extends Zend_Controller_Action {

	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');

	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $defaultCustomizeOption = array ('D' => 'ค่าพื้นฐาน', 'C' => 'กำหนดเอง');
    protected $targetOption = array ('_blank' => '_blank', '_self' => '_self');
        
    public function initAction()
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
		$canView = $sUser->isAllow("orgchart.setting.manage");
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
		
		// Model
		$model = new System_Model_Setting();
		
		// Get Data
		$data = new stdClass();
		$data->ChartPerPage = System_Model_Setting::getValue("ChartPerPage", "OrgChart");
		$data->ChartSortType = System_Model_Setting::getValue("ChartSortType", "OrgChart");
		$data->ChartSortDirection = System_Model_Setting::getValue("ChartSortDirection", "OrgChart");
		$data->ChartShowNoDataPerson = System_Model_Setting::getValue("ChartShowNoDataPerson", "OrgChart");
		$data->ChartAmountColumn = System_Model_Setting::getValue("ChartAmountColumn", "OrgChart");
		$data->ChartShowImage = System_Model_Setting::getValue("ChartShowImage", "OrgChart");
		$data->ChartShowTitle = System_Model_Setting::getValue("ChartShowTitle", "OrgChart");
		$data->ChartShowIntroText = System_Model_Setting::getValue("ChartShowIntroText", "OrgChart");
		$data->ChartShowAmount = System_Model_Setting::getValue("ChartShowAmount", "OrgChart");

		$data->PersonShowImage = System_Model_Setting::getValue("PersonShowImage", "OrgChart");
		$data->PersonShowTitle = System_Model_Setting::getValue("PersonShowTitle", "OrgChart");
		$data->PersonShowJob = System_Model_Setting::getValue("PersonShowJob", "OrgChart");
		$data->PersonShowWorkgroup = System_Model_Setting::getValue("PersonShowWorkgroup", "OrgChart");
		$data->PersonShowDepartment = System_Model_Setting::getValue("PersonShowDepartment", "OrgChart");
		$data->PersonShowLink = System_Model_Setting::getValue("PersonShowLink", "OrgChart");
		$data->PersonLinkTarget = System_Model_Setting::getValue("PersonLinkTarget", "OrgChart");
		$data->PersonVerticalLevel = System_Model_Setting::getValue("PersonVerticalLevel", "OrgChart");
		$data->PersonVisibleLevel = System_Model_Setting::getValue("PersonVisibleLevel", "OrgChart");
		$data->PersonCustomCss = System_Model_Setting::getValue("PersonCustomCss", "OrgChart");

		// $data->PersonDetailCustomCss = System_Model_Setting::getValue("PersonDetailCustomCss", "OrgChart");
				
		// Send Data to View
		$this->view->data = $data;

		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->defaultCustomizeOption = $this->defaultCustomizeOption;
		$this->view->targetOption = $this->targetOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่าแผนผัง";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array('name' => 'ตั้งค่าระบบ')
		);
		
		// Log Acion
		System_Model_Log::action("orgchart.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('orgchart.setting.manage');
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
		$params['OrgChart'] = array();
		$params['OrgChart']['ChartPerPage'] = $requestParams['ChartPerPage'];
		$params['OrgChart']['ChartSortType'] = $requestParams['ChartSortType'];
		$params['OrgChart']['ChartSortDirection'] = $requestParams['ChartSortDirection'];
		$params['OrgChart']['ChartShowNoDataPerson'] = $requestParams['ChartShowNoDataPerson'];
		$params['OrgChart']['ChartAmountColumn'] = $requestParams['ChartAmountColumn'];
		$params['OrgChart']['ChartShowImage'] = $requestParams['ChartShowImage'];
		$params['OrgChart']['ChartShowTitle'] = $requestParams['ChartShowTitle'];
		$params['OrgChart']['ChartShowIntroText'] = $requestParams['ChartShowIntroText'];
		$params['OrgChart']['ChartShowAmount'] = $requestParams['ChartShowAmount'];
		$params['OrgChart']['PersonVerticalLevel'] = $requestParams['PersonVerticalLevel'];
		$params['OrgChart']['PersonVisibleLevel'] = $requestParams['PersonVisibleLevel'];

		$params['OrgChart']['PersonShowImage'] = $requestParams['PersonShowImage'];
		$params['OrgChart']['PersonShowTitle'] = $requestParams['PersonShowTitle'];
		$params['OrgChart']['PersonShowJob'] = $requestParams['PersonShowJob'];
		$params['OrgChart']['PersonShowWorkgroup'] = $requestParams['PersonShowWorkgroup'];
		$params['OrgChart']['PersonShowDepartment'] = $requestParams['PersonShowDepartment'];
		$params['OrgChart']['PersonShowLink'] = $requestParams['PersonShowLink'];
		$params['OrgChart']['PersonLinkTarget'] = $requestParams['PersonLinkTarget'];
		$params['OrgChart']['PersonCustomCss'] = $requestParams['PersonCustomCss'];

		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("orgchart.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}

}
?>