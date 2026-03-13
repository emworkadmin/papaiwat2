<?php

class RegisterController extends Zend_Controller_Action
{
	public function init()
	{ 
		
	}
	
	public function indexAction()
	{

		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout()->disableLayout();
		
		$this->_helper->redirector('form');
	}
	
	public function formAction()
	{		
		$this->_helper->_layout->setLayout('layoutplain');
		
		if ($this->_request->isPost()) {
			$this->_helper->redirector('preview');
		}
	}
	
	public function previewAction()
	{
		$this->_helper->_layout->setLayout('layoutplain');
		
		if ($this->_request->isPost()) {
			$this->_helper->redirector('finish');
		}
	}
	
	public function finishAction()
	{
		$this->_helper->_layout->setLayout('layoutplain');
	}
	
	public function activationAction()
	{
		$this->_helper->_layout->setLayout('layoutplain');
	}
}

