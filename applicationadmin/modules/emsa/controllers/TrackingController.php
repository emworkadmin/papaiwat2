<?php

class Emsa_TrackingController extends Zend_Controller_Action
{
    
    public function init()
    {
        
    }
    
    public function indexAction()
    {
    	// Log Access
    	System_Model_Log::access();
    	$this->_helper->layout()->disableLayout();
    	
    	$this->view->title = "ตรวจสอบสถานะ Online";
    }
    
    public function searchAction()
    {
    	$response = new stdClass();
    	$response->result = false;
    	$response->isProject = null;
    	
    	// Get Param
    	$CustomerCode = $this->getRequest()->getParam("emsaTicketID");

    	// Check CustomerCode
    	$data = $this->getdetail($CustomerCode, true);
    	
    	// Send data to view
    	$response->result = $data->result;
    	$response->message = $data->message;
    	$response->CustomerCode = trim($CustomerCode);
    	
    	// Return Result
    	$this->_helper->json($response);
    }
    
    private function getdetail($CustomerCode, $isCheck)
    {
    	$response = new stdClass();
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->reportproblemdetail;
    		
    		// Set Body
    		$body = new stdClass();
    		$body->emsaKey = $emsaKey;
    		$body->customerCode = trim($CustomerCode);
    		$body->isCheck = $isCheck;
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
    			if($isCheck){
    				$response->result = $sendResult->result;
    			}else{
    				$response = $sendResult->data;
    			}
    		}else{
    			if($isCheck){
    				$response->result = $sendResult->result;
    				$response->message = $sendResult->message;
    			}
    		}
    		
    		if(curl_errno($ch)){
    			//$response->message = 'ขออภัย การเชื่อมต่อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ';
    			//echo 'Curl error: '.curl_error($ch) . "\n"; curl_close($ch); exit();
    		}
    		
    	} catch (Exception $e) {
    		//$response->message = 'ขออภัย การเชื่อมต่อผิดพลาดกรุณาติดต่อผู้ดูแลระบบ';
    		//print_r($e->getMessage()); exit();
    	}
    	
    	return $response;
    }
    
    public function listAction()
    {
    	// Log Access
    	System_Model_Log::access();
    	$this->_helper->layout()->disableLayout();
    	
    	// Get data system
    	$dataSystem = $this->getSystem();
    	
    	// Send data to view
    	$this->view->title = "ผลการตรวจสอบสถานะ";
    	$this->view->data = $dataSystem->data;
    }
    
    private function getSystem() {
    	$response = new stdClass();
    	$response->result = false;
    	$response->message = "";
    	$response->data = new stdClass();
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->getsystem;
    		
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
    			$response->result = true;
    			$response->data->ProjectCode = $sendResult->data->ProjectCode;
    			$response->data->ProjectName = $sendResult->data->ProjectName;
    			$response->data->DepartmentCode = $sendResult->data->DepartmentCode;
    			$response->data->DepartmentName = $sendResult->data->DepartmentName;
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
    
    public function getlistAction()
    {
    	$response = new stdClass();
    	$response->result = false;
    	$response->message = "";
    	$response->data = new stdClass();
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	$pageIndex = $params['pageIndex'];
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->reportproblemlist;
    		
    		// Set Body
    		$body = new stdClass();
    		$body->emsaKey = $emsaKey;
    		$body->pageIndex = $pageIndex;
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
    		$response->result = true;
    		$response->data = $sendResult;
    		
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
    
    public function detailAction()
    {
    	// Log Access
    	System_Model_Log::access();
    	$this->_helper->layout()->disableLayout();
    	
    	// Param
    	$CustomerCode = $this->getRequest()->getParam('id');
    	
    	// Get Data
    	$data = $this->getdetail($CustomerCode, false);
    	
    	// Send data to view
    	$this->view->title = "ผลการตรวจสอบสถานะ";
    	$this->view->data = $data;
    }
    
}

