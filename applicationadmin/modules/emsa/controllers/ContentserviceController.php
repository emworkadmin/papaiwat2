<?php

class Emsa_ContentserviceController extends Zend_Controller_Action
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
    	 
    	// Get Data
    	$data = $this->getContentCradit();
    	//echo '<pre>'; print_r($data); echo '</pre>'; exit();
    	
    	// Send data to view
    	$this->view->title = "Content Service"; 
    	$this->view->data = $data; 
    	$this->view->emsaSenderName = $sUser->getInfo()->FirstName . ' ' . $sUser->getInfo()->LastName;
    	$this->view->emsaSenderUserGroup = $sUser->getInfo()->UserGroupName;
    }
    
    private function getContentCradit() {
    	$response = new stdClass();
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->getcontentcradit;
    		
    		// Set Body
    		$body = new stdClass();
    		$body->emsaKey = $emsaKey;
    		$payload = json_encode($body);
    		
    		// Set Header
    		$header = array();
    		$header[] = 'Content-Type:application/json';
    		$header[] = 'Content-Length: ' . strlen($payload);
    		
    		// Set URL
    		$ch = curl_init($url);
    		
    		// Setup request to send json via POST.
    		curl_setopt( $ch, CURLOPT_POST, true);
    		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
    		curl_setopt( $ch, CURLOPT_HEADER, false);
    		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
    		
    		// Return response instead of printing.
    		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    		
    		// Send request.
    		$sendResult = curl_exec($ch);
    		
    		// Decode Result
    		$sendResult = json_decode($sendResult);
    		
    		// Save Result
    		if($sendResult->result){
    			$response = $sendResult->data;
    		}else{
    			$response->message = $sendResult->message;
    		}
    		
    		if(curl_errno($ch)){
    			$response->message = 'ขออภัย การเชื่อมต่อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ';
    			//echo 'Curl error: '.curl_error($ch) . "\n";
    			//curl_close($ch);
    		}
    		
    	} catch (Exception $e) {
    		$response->message = 'ขออภัย การเชื่อมต่อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ';
    		//print_r($e->getMessage()); exit();
    	}
    	
    	return $response;
    }
    
    public function sendAction()
    {
    	$response = new stdClass();
    	$response->result = false;
    	$response->message = "";
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	$siteUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    	
    	// Prepare File
    	$emsaReportFile = array();
    	if((count($params['hdfemsaFile01'])>0) && ($params['emsaContentServiceType'] == 1)){
    		foreach($params['hdfemsaFile01'] as $HashName){
    			$path = $siteUrl . $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $HashName), null, true);
    			$emsaReportFile[] = $path;
    		}
    	}
    	if((count($params['hdfemsaFile01'])>0) && ($params['emsaContentServiceType'] == 2)){
    		foreach($params['hdfemsaFile02'] as $HashName){
    			$path = $siteUrl . $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $HashName), null, true);
    			$emsaReportFile[] = $path;
    		}
    	}
    	if((count($params['hdfemsaFile01'])>0) && ($params['emsaContentServiceType'] == 3)){
    		foreach($params['hdfemsaFile04'] as $HashName){
    			$path = $siteUrl . $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $HashName), null, true);
    			$emsaReportFile[] = $path;
    		}
    	}
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->sendreportservice;
    		
    		// Set Body
    		$body = new stdClass();
    		$body->emsaKey = $emsaKey;
    		$body->emsaContentServiceType = $params['emsaContentServiceType'];
    		if($params['emsaContentServiceType'] == 1){
    			$body->emsaTitle = $params['emsaTitle01'];
    			$body->emsaDetail = $params['emsaDetail01'];
    			$body->emsaRemark = $params['emsaRemark01'];
    		}
    		if($params['emsaContentServiceType'] == 2){
    			$body->emsaTitle = $params['emsaTitle02'];
    			$body->emsaDetail = $params['emsaDetail02'];
    			$body->emsaPublicStartDate = $params['emsaPublicStartDate'];
    			$body->emsaPublicEndDate = $params['emsaPublicEndDate'];
    		}
    		if($params['emsaContentServiceType'] == 3){
    			$body->emsaTitle = $params['emsaTitle03'];
    			$body->emsaDetail = $params['emsaDetail03'];
    		}
    		$body->emsaFile = $emsaReportFile;
    		$body->emsaSenderName = $params['emsaSenderName'];
    		$payload = json_encode($body);
    		
    		// Set Header
    		$header = array();
    		$header[] = 'Content-Type:application/json';
    		$header[] = 'Content-Length: ' . strlen($payload);
    		
    		// Set URL
    		$ch = curl_init($url);
    		
    		// Setup request to send json via POST.
    		curl_setopt( $ch, CURLOPT_POST, true);
    		curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
    		curl_setopt( $ch, CURLOPT_HEADER, false);
    		curl_setopt( $ch, CURLOPT_HTTPHEADER, $header);
    		
    		// Return response instead of printing.
    		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    		
    		// Send request.
    		$sendResult = curl_exec($ch);
    		
    		// Decode Result
    		$sendResult = json_decode($sendResult);
    		
    		// Save Result
    		if($sendResult->result){
    			$response->result = true;
    		}else{
    			$response->message = $sendResult->message;
    		}
    		
    		if(curl_errno($ch)){
    			$response->message = 'ขออภัย การเชื่อมต่อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ';
    			//echo 'Curl error: '.curl_error($ch) . "\n"; curl_close($ch); exit();
    		}
    		
    	} catch (Exception $e) {
    		$response->message = 'ขออภัย การเชื่อมต่อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ';
    		//print_r($e->getMessage()); exit();
    	}
    	
    	$this->_helper->json($response);
    }
    
    public function resultAction()
    {
    	// Log Access
    	System_Model_Log::access();
    	$this->_helper->layout()->disableLayout();
    	
    	$this->view->receiveNo = "6101263218";
    	$this->view->title = "Content Service - เสร็จสิ้น";
    }
}

