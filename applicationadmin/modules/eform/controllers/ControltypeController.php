<?php
class Eform_ControltypeController extends Zend_Controller_Action
{

    function init()
    {

    }

    public function dialogAction()
    {

    	$this->_helper->layout()->disableLayout();
    	
		// Log Access
		System_Model_Log::access ();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
		
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}

		$model = new Eform_Model_Controltype();
		$data = $model->getlist();
		$dataCaptcha = $model->getReCaptchakey();

		$this->view->data = $data;
		$this->view->dataCaptcha = $dataCaptcha;
	}
	
	public function dialogformAction()
	{

    	$this->_helper->layout()->disableLayout();
    	
		// Log Access
		System_Model_Log::access ();
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$this->_helper->redirector('index', 'index', 'default');
		}
		if ($sUser->getInfo()->UserType != "B") {
			$this->_helper->redirector('index', 'index', 'default');
		}
		
		$canDelete = $sUser->isAllow('form.item.manage');
		$canEdit = $sUser->isAllow('form.item.manage');
		$canView = $sUser->isAllow('form.item.view');
		if (!$canView) {
			$this->_helper->redirector('index', 'item', 'eform');
		}
		;
		if (!$canView) {
			$this->_helper->redirector('index', 'index', 'default');
		}

		$id = $this->getRequest()->getParam("id");

		// Get Params
		$data =  $this->getRequest()->getParam('data');
		$dataObj = json_decode($data, true);

		$this->view->id = $id;
		$this->view->dataObj = $dataObj;
	}

	public function saveAction()
	{
		// Init
		$result = array ();
		$result ['result'] = false;
		$result ['id'] = null;
		$result ['message'] = '';
		
		$sUser = System_Model_SUser::getInstance();
		if (!$sUser->hasData()) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		if ($sUser->getInfo()->UserType != "B") {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		$canSave = $sUser->isAllow('form.item.manage');
		if (!$canSave) {
			$result['message'] = 'ไม่สามารถบันทึกได้';
			$this->_helper->json($result);
		}
		
		$params = $this->getRequest()->getParams();
		$model = new Eform_Model_Controltype();

		$data = $model->save($params);

		// Set Result
		if ($data->result) {
			$result ['result'] = true;
			$result ['message'] = '';
			$result ['id'] = $data->id;
			
			// Log Acion
			$model = new Eform_Model_Item();
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$datainfo = $model->getDataByLanguage($params['hdfID'], $defaultLanguage['Code']);
			System_Model_Log::action("form.editform", "save", $datainfo->Title, $params['hdfID']);
		} else {
			$result ['result'] = false;
			$result ['message'] = 'บันทึกไม่สำเร็จ';
		}
		
		$this->_helper->json ( $result );				
	}
}
?>