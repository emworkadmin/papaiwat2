<?php


class ForgetpasswordController extends Zend_Controller_Action
{
	public function init()
	{ 
		
	}
	
	public function indexAction()
	{

		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout()->disableLayout();
		
		$this->_helper->redirector('logout');
	}
	
	public function loginAction()
	{		
		$this->_helper->_layout->setLayout('layoutplain');
		
		if ($this->_request->isPost()) {
			$this->_helper->redirector('index', 'index');
		}
	}
	
	public function logoutAction()
	{

		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout()->disableLayout();
		
		$this->_helper->redirector('login');
	}
}

