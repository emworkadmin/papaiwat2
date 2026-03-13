<?php

class Emsa_ReportController extends Zend_Controller_Action
{
    
    public function init()
    {
        
    }
    
    public function indexAction()
    {
        // Log Access
        System_Model_Log::access();
        $this->_helper->layout()->disableLayout();
        
        // Send data to view
        $this->view->title = "Report";
        $this->view->monthOption = Emwork_Utility::addEmptyOption($this->getMonthOption(),'ทั้งหมด');
        $this->view->yearOption = Emwork_Utility::addEmptyOption($this->getYearOption(),'ทั้งหมด');
    }
    
    private function getMonthOption() {
    	$response = new stdClass();
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->getmonth;
    		
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
    
    private function getYearOption() {
    	$response = new stdClass();
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->getyear;
    		
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
    
    public function getlistAction() {
    	
    	$filterInt = new Zend_Filter_Int();
    	
    	$responce = array();
    	$responce['total'] = 0;
    	$responce['html'] = '';
    	
    	$params = $this->getRequest()->getParams();
    	$filterYear = $params["year"];
    	$filterMonth = $params["month"];

    	// Get Data
    	$data = $this->getReport($filterYear,$filterMonth);
    	
    	// Send Data to view
    	$this->view->data = $data;
    	$responce["html"] = $this->view->render('report/_rows.phtml');
    	
    	// Return Result
    	$this->_helper->json($responce);
    }
    
    private function getReport($filterYear,$filterMonth) {
    	$response = array();
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->getreport;
    		
    		// Set Body
    		$body = new stdClass();
    		$body->emsaKey = $emsaKey;
    		$body->filterMonth = $filterMonth;
    		$body->filterYear = $filterYear;
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
    
    public function updatereportdownloadsAction() {
    	$response = new stdClass();
    	$response->result = false;
    	$response->message = "";
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->updatereportdownloads;

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

