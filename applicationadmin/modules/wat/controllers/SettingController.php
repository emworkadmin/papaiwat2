<?php

class Wat_SettingController extends Zend_Controller_Action
{
	protected $extensionName;
	protected $yesNoOption = array ('Y' => 'ใช่', 'N' => 'ไม่ใช่');
	protected $sortTypeOption = array ('name' => 'ชื่อ', 'created' => 'วันที่สร้าง', 'modified' => 'วันที่ปรับปรุง', 'publishdate' => 'วันที่เผยแพร่', 'viewamount' => 'ความนิยม');
	protected $sortDirectionOption = array ('asc' => 'น้อยไปมาก', 'desc' => 'มากไปน้อย');
	protected $columnAmountOption = array ('1' => '1 คอลัมน์', '2' => '2 คอลัมน์', '3' => '3 คอลัมน์', '4' => '4 คอลัมน์');
	protected $publicCommentOption = array ('Y' => 'เผยแพร่ทันที', 'N' => 'รอพิจารณา');

	public function init()
	{
		$this->extensionName = Wat_Service_Info::getExtensionName();
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
		// Use isAllow
		$canView = $sUser->isAllow("wat.setting.manage");
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}

		// Model
		$model = new System_Model_Setting();
		$fileModel = new System_Model_Fileinfo();
		
		// Get Data
		$data = new stdClass();
		$data->itemPerPage = System_Model_Setting::getValue("ItemPerPage", "Wat");
		$data->itemPerPage = $data->itemPerPage == null ? 10 : $data->itemPerPage;
		$data->itemSortType = System_Model_Setting::getValue("ItemSortType", "Wat");
		$data->itemSortType = $data->itemSortType == null ? "name" : $data->itemSortType;
		$data->itemSortDirection = System_Model_Setting::getValue("ItemSortDirection", "Wat");
		$data->itemSortDirection = $data->itemSortDirection == null ? "asc" : $data->itemSortDirection;
		//$data->itemShowNoDataCategory = System_Model_Setting::getValue("ItemShowNoDataCategory", "Wat");
		//$data->itemCanComment = System_Model_Setting::getValue("ItemCanComment", "Wat");
		//$data->itemPublicComment = System_Model_Setting::getValue("ItemPublicComment", "Wat");
		$data->itemAmountColumn = System_Model_Setting::getValue("ItemAmountColumn", "Wat");
		$data->itemAmountColumn = $data->itemAmountColumn == null ? "1" : $data->itemAmountColumn;
		$data->itemShowImage = System_Model_Setting::getValue("ItemShowImage", "Wat");
		$data->itemShowImage = $data->itemShowImage == null ? "Y" : $data->itemShowImage;
		//$data->itemShowCategory = System_Model_Setting::getValue("ItemShowCategory", "Wat");
		$data->itemShowIntroText = System_Model_Setting::getValue("ItemShowIntroText", "Wat");
		$data->itemShowIntroText = $data->itemShowIntroText == null ? "Y" : $data->itemShowIntroText;
		//$data->itemShowAmount = System_Model_Setting::getValue("ItemShowAmount", "Wat");
		
		$data->categoryPerPage = System_Model_Setting::getValue("CategoryPerPage", "Wat");
		$data->categoryPerPage = $data->categoryPerPage == null ? "10" : $data->categoryPerPage;
		$data->categoryAmountColumn = System_Model_Setting::getValue("CategoryAmountColumn", "Wat");
		$data->categoryAmountColumn = $data->categoryAmountColumn == null ? "1" : $data->categoryAmountColumn;
		$data->categoryShowImage = System_Model_Setting::getValue("CategoryShowImage", "Wat");
		$data->categoryShowImage = $data->categoryShowImage == null ? "Y" : $data->categoryShowImage;
		//$data->categoryShowCategory = System_Model_Setting::getValue("CategoryShowCategory", "Wat");
		$data->categoryShowIntroText = System_Model_Setting::getValue("CategoryShowIntroText", "Wat");
		$data->categoryShowIntroText = $data->categoryShowIntroText == null ? "Y" : $data->categoryShowIntroText;
		
		$data->IntroImage = array();
		$introImageID = System_Model_Setting::getValue("IntroImage", "Wat");
		if (!empty($introImageID)) {
			$fileData = $fileModel->item($introImageID);
			if ($fileData->ID) {
				$data->IntroImage[] = array('file_name'=> $fileData->Name, 'size'=> $fileData->FileSize, 'type'=> $fileData->FileType, 'hash_name'=> $fileData->HashName);
			}
		}
		$data->CoverImage = array();
		$coverImageID = System_Model_Setting::getValue("CoverImage", "Wat");
		if (!empty($coverImageID)) {
			$fileData = $fileModel->item($coverImageID);
			if ($fileData->ID) {
				$data->CoverImage[] = array('file_name'=> $fileData->Name, 'size'=> $fileData->FileSize, 'type'=> $fileData->FileType, 'hash_name'=> $fileData->HashName);
			}
		}

		//$data->newlimitdata = System_Model_Setting::getValue("Newlimitdata", "Wat");
		//$data->newdistancedate = System_Model_Setting::getValue("Newdistancedate", "Wat");
		//$data->updatelimitdata = System_Model_Setting::getValue("Updatelimitdata", "Wat");
		//$data->updatedistancedate = System_Model_Setting::getValue("Updatedistancedate", "Wat");

		// Send Data to View
		$this->view->data = $data;
		
		$this->view->yesNoOption = $this->yesNoOption;
		$this->view->sortTypeOption = $this->sortTypeOption;
		$this->view->sortDirectionOption = $this->sortDirectionOption;
		$this->view->columnAmountOption = $this->columnAmountOption;
		$this->view->publicCommentOption = $this->publicCommentOption;
		
		// Set View
		$this->view->pageName = "ตั้งค่า";
		$this->view->pageIcon= "fa fa-gear";
		$this->view->subMenus = array();
		$this->view->breadcrumb = array(
				array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
				array ( 'name' => $this->extensionName ), 
				array('name' => 'ตั้งค่า')
		);
		
		// Log Acion
		System_Model_Log::action("wat.setting", "view", null, null);
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
		$canSave = $sUser->isAllow('wat.setting.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		$fileModel = new System_Model_Fileinfo();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		
		// Check Param
		
		// Set Params
		$params = array();
		$params["Wat"] = array();
		$params["Wat"]["ItemPerPage"] = $requestParams['ItemPerPage'];
		$params["Wat"]["ItemSortType"] = $requestParams['ItemSortType'];
		$params["Wat"]["ItemSortDirection"] = $requestParams['ItemSortDirection'];
		$params["Wat"]["ItemShowNoDataCategory"] = $requestParams['ItemShowNoDataCategory'];
		$params["Wat"]["ItemCanComment"] = $requestParams['ItemCanComment'];
		$params["Wat"]["ItemPublicComment"] = $requestParams['ItemPublicComment'];
		$params["Wat"]["ItemAmountColumn"] = $requestParams['ItemAmountColumn'];
		$params["Wat"]["ItemShowImage"] = $requestParams['ItemShowImage'];
		$params["Wat"]["ItemShowCategory"] = $requestParams['ItemShowCategory'];
		$params["Wat"]["ItemShowIntroText"] = $requestParams['ItemShowIntroText'];
		$params["Wat"]["ItemShowAmount"] = $requestParams['ItemShowAmount'];
		
		$params["Wat"]["CategoryPerPage"] = $requestParams['CategoryPerPage'];
		$params["Wat"]["CategoryAmountColumn"] = $requestParams['CategoryAmountColumn'];
		$params["Wat"]["CategoryShowImage"] = $requestParams['CategoryShowImage'];
		$params["Wat"]["CategoryShowCategory"] = $requestParams['CategoryShowCategory'];
		$params["Wat"]["CategoryShowIntroText"] = $requestParams['CategoryShowIntroText'];
		
		$params["Wat"]["Newlimitdata"] = $requestParams['Newlimitdata'];
		$params["Wat"]["Newdistancedate"] = $requestParams['Newdistancedate'];
		$params["Wat"]["Updatelimitdata"] = $requestParams['Updatelimitdata'];
		$params["Wat"]["Updatedistancedate"] = $requestParams['Updatedistancedate'];
		
		$coverImageID = null;
		if (count($requestParams['hdfCoverImage'])>0) {
			$fileData = $fileModel->itemByHashName($requestParams['hdfCoverImage'][0]);
			if ($fileData->ID != null) {
				$coverImageID = $fileData->ID;
			}
		}
		$params["Wat"]["CoverImage"] = $coverImageID;
		$introImageID = null;
		if (count($requestParams['hdfIntroImage'])>0) {
			$fileData = $fileModel->itemByHashName($requestParams['hdfIntroImage'][0]);
			if ($fileData->ID != null) {
				$introImageID = $fileData->ID;
			}
		}
		$params["Wat"]["IntroImage"] = $introImageID;
		
		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("wat.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

