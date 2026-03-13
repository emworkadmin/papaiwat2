<?php

class Webboard_IndexController extends Zend_Controller_Action
{
	public function init()
	{ 
		
	}
	
	public function indexAction()
	{
		$this->_helper->redirector('index', 'home', 'webboard');
		
	}
}

