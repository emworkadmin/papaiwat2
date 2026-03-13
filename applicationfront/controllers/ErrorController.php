<?php

class ErrorController extends Zend_Controller_Action
{

	public function errorAction()
	{
		$this->_helper->_layout->setLayout('layoutplain');
		 
		$priority = "";
		$renderName = "error";
		$errors = $this->_getParam('error_handler');
		$httpResponseCode = $this->getResponse()->getHttpResponseCode();

		if (!$errors || !$errors instanceof ArrayObject) {
			$this->view->message = 'You have reached the error page';
			return;
		}

		switch ($errors->type) {
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
			case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
				// 404 error -- controller or action not found
				$this->getResponse()->setHttpResponseCode(404);
				$priority = Zend_Log::NOTICE;
				$this->view->message = 'Page not found';
					
				$renderName = "pagenotfound";
				break;
			default:
				// application error
				$this->getResponse()->setHttpResponseCode(500);
				$priority = Zend_Log::CRIT;
				$this->view->message = 'Application error';
				break;
		}

		// Log exception, if logger available
		if ($log = $this->getLog()) {
			$arr = $errors->request->getParams();
			unset($arr["layoutFullContent"]);
			unset($arr["layoutContent"]);
			$params = print_r($arr, true);			
			$log->log($errors->exception, $priority, array("params" => $params));
			//$log->log($this->view->message, $priority, $errors->exception);
			//$log->log('Request Parameters', $priority, $errors->request->getParams());
		}

		// conditionally display exceptions
		//if ($this->getInvokeArg('displayExceptions') == true) {
		//$this->view->exception = $errors->exception;
		//}
		$this->view->showException = false; //$this->getInvokeArg('displayExceptions');
		//print_r($errors->exception->getCode());exit;
		$this->view->exception = $errors->exception;

		$this->view->notShowMainMenu = true;
		$this->view->notShowProfileBar = true;
			
		$this->view->request   = $errors->request;
		$this->render($renderName);
	}

	/**
	 * 
	 * @return NULL|Zend_Log
	 */
	public function getLog()
	{
		$filename = "front_error_log-" . date("Ymd");
		$stream = @fopen(APPLICATION_ROOT .  "/tmp/log/" . $filename, 'a', false);
		if (! $stream) {
			return null;
		}
		 
		$writer = new Zend_Log_Writer_Stream($stream);
		
		$format = '[%timestamp%] %priorityName% (%priority%): %message%  ' . PHP_EOL . 'Params:' . PHP_EOL . '%params%' . PHP_EOL;
		$formatter = new Zend_Log_Formatter_Simple($format);
		$writer->setFormatter($formatter);
		
		$logger = new Zend_Log($writer);
		
		return $logger;
	}

}

