<?php
class License_IntroController extends Zend_Controller_Action {
    
	
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
		$canEdit = $sUser->isAllow('license.intro.manage');
		$canView = $sUser->isAllow('license.intro.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}
        
        $languages = System_Model_Setting::getLanguages();
        $defaultLanguage = System_Model_Setting::getDefaultLanguage();

		// Model
		$model = new License_Model_Intro();

        // Get Data
		$data = $model->getInfo();
		
		// Detail
		$dataDetails = array();
		
		if (count($languages) > 0) {
			foreach ($languages as $lang) {
				$langCode = $lang['Code'];
				$detail = $model->getDataByLanguage($langCode);
				if (!empty($detail->Lang)) {
					$dataDetails[$langCode] = $detail;
				}
			}
		}
		
		$this->view->canEdit = $canEdit;

        // Send Option and lookup To View
        $this->view->defaultLanguage = $defaultLanguage['Code'];
		$this->view->defaultLanguageName = $defaultLanguage['Name'];
        $this->view->languages = $languages;
        $this->view->data = $data;
        $this->view->dataDetails = $dataDetails;

        // Set View
        $this->view->pageName = "ปรับปรุงข้อความเกริ่นนำ";
		$this->view->pageIcon = "fa fa-file-text-o";
		$this->view->subMenus = array();
        $this->view->breadcrumb = array(
            array('name' => 'หน้าแรก', 'url' => $this->view->url(array('module' => 'default', 'controller' => 'index', 'action' => 'index'), null, true)),
            array('name' => 'ปรับปรุงข้อความเกริ่นนำ')
        );		
	}

	function updateAction()
	{
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
		$canSave = $sUser->isAllow('license.intro.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$params = $this->getRequest()->getParams();

		$languages = System_Model_Setting::getLanguages();

        $defaultLanguage = System_Model_Setting::getDefaultLanguage();
		$defaultLanguageCode = $defaultLanguage["Code"];
		$params['DefaultLanguageCode'] = $defaultLanguage['Code'];

		// Save
		$model = new License_Model_Intro();
        $data = $model->update($params);

        // Set Result
        if ($data->result) {
            $result['result'] = true;
            $result['message'] = '';
            
            // Log Acion
            System_Model_Log::action("license.intro", "update", null, null);
        } else {
            $result['result'] = false;
            $result['message'] = 'บันทึกไม่สำเร็จ';
        }
        
        $this->_helper->json($result);
	}
}
?>