<?php

class Webboard_RequirementController extends Zend_Controller_Action
{
	protected $lang = 'th';
	
	public function init() {
	    
	}
	
	public function indexAction() {
	    
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
		$canView = $sUser->isAllow('webboard.requirement.view');
		$canAdd = $sUser->isAllow('webboard.requirement.manage');
		if (!$canView) {
		   $this->_helper->redirector('index', 'index', 'default');
		}
        
        $model = new Webboard_Model_Requirement();
		$datarequirement = $model->getData();

		if($datarequirement){
			$datarequirement->check = 1 ;
		}else{
			$datarequirement->check = 0 ;
		}

		$this->view->data = $datarequirement;
		
		$this->view->canAdd = $canAdd;

	    // Data to Layout
		$this->view->pageName = "ข้อตกลงในการใช้งาน";
		$this->view->pageIcon = "fa fa-edit";
	    $this->view->breadcrumb = array(
	        array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
			array('name' => 'ข้อตกลงในการใช้งาน')
		);
		
		System_Model_Log::action("webboard.requirement", "view", $datarequirement->Title, $datarequirement->ID);
	}
	
	public function saveAction() {

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
		$canAdd = $sUser->isAllow('webboard.requirement.manage');
		if (!$canAdd) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}

	    $params = $this->_request->getParams();
	    
	    $model = new Webboard_Model_Requirement();
	    $data = $model->save($params);
	    
	    if ($data->result) {
	        $result['result'] = true;
	        $result['message'] = '';
			$result['id'] = $data->ID;
			
			$datarequirement = $model->getData();
			System_Model_Log::action("webboard.requirement", "save", $datarequirement->Title, $data->ID);
	    } else {
	        $result['result'] = false;
	        $result['message'] = 'บันทึกไม่สำเร็จ';
	    }
	    
	    $this->_helper->json($result);
	}
	
	public function updateAction() {
	    
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
		$canSave = $sUser->isAllow('webboard.requirement.manage');
		if (!$canSave) {
		   $result['message'] = 'ไม่สามารถบันทึกได้';
		   $this->_helper->json($result);
		}

		$params = $this->_request->getParams();
	    
	    $model = new Webboard_Model_Requirement();
	    $data = $model->update($params);
	    
	    if ($data->result) {
	        $result['result'] = true;
	        $result['message'] = '';
			$result['id'] = $data->ID;
			
			$datarequirement = $model->getData();
			System_Model_Log::action("webboard.requirement", "update", $datarequirement->Title, $data->ID);
	    } else {
	        $result['result'] = false;
	        $result['message'] = 'บันทึกไม่สำเร็จ';
	    }
	    
	    $this->_helper->json($result);
	}
	
}

