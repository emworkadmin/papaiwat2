<?php

class Emsa_ChatonlineController extends Zend_Controller_Action
{
    
    public function init()
    {
        
    }
    
    public function indexAction()
    {
    	// Log Access
    	System_Model_Log::access();
    	$this->_helper->layout()->disableLayout();
    	
    	$sUser = System_Model_SUser::getInstance();
    	if (!$sUser->hasData()) {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	if ($sUser->getInfo()->UserType != "B") {
    		$this->_helper->redirector('index', 'index', 'default');
    	}
    	
    	$this->view->title = "สอบถามการใช้งาน";
    	$this->view->emsaSenderName = $sUser->getInfo()->FirstName . ' ' . $sUser->getInfo()->LastName;
    	$this->view->emsaSenderUserGroup = $sUser->getInfo()->UserGroupName;
    }
    
    public function finishAction()
    {
    	// Log Access
    	System_Model_Log::access();
    	$this->_helper->layout()->disableLayout();
    	
    	$this->view->title = "สอบถามการใช้งาน";
    }
}

