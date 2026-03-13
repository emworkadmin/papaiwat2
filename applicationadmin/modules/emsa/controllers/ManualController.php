<?php

class Emsa_ManualController extends Zend_Controller_Action
{
    
    public function init()
    {
        
    }
    
    public function indexAction()
    {
    	// Log Access
    	System_Model_Log::access();
    	$this->_helper->layout()->disableLayout();
    	
    	// Get Data
    	$data = $this->getManuallist();
    	
    	// Send data to view
    	$this->view->title = "คู่มือการใช้งาน";
    	$this->view->data = $data;
    }
    
    private function getManuallist() {
    	$response = array();
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->getmanuallist;
    		
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
    		$sendResult = json_decode($sendResult, true);
    		
    		// Save Result
    		if($sendResult['result']){
    			$response = $sendResult['data'];
    		}
    		
    		if(curl_errno($ch)){
    			//$response->message = 'ขออภัย การเชื่อมต่อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ';
    			//echo 'Curl error: '.curl_error($ch) . "\n";
    			//curl_close($ch);
    		}
    		
    	} catch (Exception $e) {
    		//$response->message = 'ขออภัย การเชื่อมต่อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ';
    		//print_r($e->getMessage()); exit();
    	}
    	
    	return $response;
    }
    
    public function detailAction()
    {
    	// Log Access
    	System_Model_Log::access();
    	$this->_helper->layout()->disableLayout();
    	
    	// Get Param
    	$params = $this->getRequest()->getParams();
    	$ID = $params['id'];
    	
		// Get Data
    	$data = $this->getManualdetail($ID);
    	
    	// Send Data to View
    	$this->view->data = $data;
    	
    	$this->view->title = "คู่มือการใช้งาน: ".$data->Title;
    }
    
    private function getManualdetail($ID) {
    	$response = new stdClass();
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->getmanualdetail;
    		
    		// Set Body
    		$body = new stdClass();
    		$body->emsaKey = $emsaKey;
    		$body->id = $ID;
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
    
    public function updatemanualviewsAction() {
    	$response = new stdClass();
    	$response->result = false;
    	$response->message = "";
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->updatemanualviews;
    		
    		// Get params
    		$params = $this->getRequest()->getParams();
    		$id = $params["id"];
    		
    		// Set Body
    		$body = new stdClass();
    		$body->emsaKey = $emsaKey;
    		$body->id = $id;
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
    		$this->_helper->json($sendResult);
    		// Save Result
    		if($sendResult->result){
    			$response->result = true;
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
    	
    	// Return Result
    	$this->_helper->json($responce);
    }
}

