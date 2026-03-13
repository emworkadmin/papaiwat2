<?php
class Stat_SettingController extends Zend_Controller_Action
{
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
        $canView = $sUser->isAllow('stat.stat.view');
        if (!$canView) {
           $this->_helper->redirector('index', 'index', 'default');
        }
        
        // Model
        $model = new System_Model_Setting();
        $fileModel = new System_Model_Fileinfo();
        
        // Get Data
        $data = new stdClass();
        $data->StatSiteOpenDate = System_Model_Setting::getValue("StatSiteOpenDate", "Stat");
        $data->StatBeginVisitor = System_Model_Setting::getValue("StatBeginVisitor", "Stat");
        $data->StatSiteModifiedDate = System_Model_Setting::getValue("StatSiteModifiedDate", "Stat");
        // $data->StatSiteVisitor = System_Model_Setting::getValue("StatSiteVisitor", "Stat");
        
        $data->StatSiteOpenDate = empty($data->StatSiteOpenDate) ? null : new DateTime($data->StatSiteOpenDate);
        $data->StatSiteModifiedDate = empty($data->StatSiteModifiedDate) ? null : new DateTime($data->StatSiteModifiedDate);
        
        $data->StatViewID = System_Model_Setting::getValue("ViewID", "Stat");
        $data->StatServiceAccount = array();
        $statServiceAccountFileID = System_Model_Setting::getValue("ServiceAccount", "Stat");
        if (!empty($statServiceAccountFileID)) {
        	$fileData = $fileModel->item($statServiceAccountFileID);
        	if ($fileData->ID) {
        		$data->StatServiceAccount[] = array('file_name'=> $fileData->Name, 'size'=> $fileData->FileSize, 'type'=> $fileData->FileType, 'hash_name'=> $fileData->HashName);
        	}
        }
        
		// Send Data to View
        $this->view->data = $data;
        
        // Set View
        $this->view->pageName = "ตั้งค่าระบบสถิติ";
        $this->view->pageIcon= "fa fa-gear";
        $this->view->subMenus = array();
        $this->view->breadcrumb = array(
        		array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
        		array('name' => 'ตั้งค่าระบบสถิติ')
        );
        
        // Log Acion
        System_Model_Log::action("stat.setting", "view", null, null);
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
		$canAdd = $sUser->isAllow('stat.stat.view');
		if (!$canAdd) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}
		
		// Model 
		$model = new System_Model_Setting();
		$fileModel = new System_Model_Fileinfo();
		
		// Param
		$requestParams = $this->getRequest()->getParams();
		
		$serviceAccountFileID = null;
		if (count($requestParams['hdfStatServiceAccount'])>0) {
			$fileData = $fileModel->itemByHashName($requestParams['hdfStatServiceAccount'][0]);
			if ($fileData->ID != null) {
				$serviceAccountFileID = $fileData->ID;
			}
		}		
		
		// Set Params
		$params = array();
		$params["Stat"] = array();
		//$params["Stat"]["StatSiteOpenDate"] =Emwork_Utility_DateTime::dateThaiToMySQL($requestParams['StatSiteOpenDate']);
		//$params["Stat"]["StatBeginVisitor"] = $requestParams['StatBeginVisitor'];
		//$params["Stat"]["StatSiteModifiedDate"] = Emwork_Utility_DateTime::dateThaiToMySQL($requestParams['StatSiteModifiedDate']);
		$params["Stat"]["ServiceAccount"] = $serviceAccountFileID;
		$params["Stat"]["ViewID"] = $requestParams['StatViewID'];

		// Save
		$data = $model->saveParams($params);
		
		// Set Result
		if ($data->result) {				
			$result['result'] = true;
			$result['message'] = '';
			
			// Log Acion
			System_Model_Log::action("stat.setting", "update", null, null);
		} else {
			$result['result'] = false;
			$result['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json($result);
	}
}

