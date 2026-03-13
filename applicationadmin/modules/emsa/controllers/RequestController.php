<?php

class Emsa_RequestController extends Zend_Controller_Action
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
        
        // Get data system
        $dataSystem = $this->getSystem();

        // Send data to view
        $this->view->title = "แจ้งให้เราทราบถึงสิ่งที่เกิดขึ้น";
        $this->view->projectCode = (empty($dataSystem->data->ProjectCode) ? '-' : $dataSystem->data->ProjectCode);
        $this->view->departmentCode = (empty($dataSystem->data->DepartmentCode) ? '-' : $dataSystem->data->DepartmentCode);
        $this->view->emsaSenderName = $sUser->getInfo()->FirstName . ' ' . $sUser->getInfo()->LastName;
        $this->view->emsaSenderUserGroup = $sUser->getInfo()->UserGroupName;
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
    
    public function sendAction()
    {
    	$response = new stdClass();
    	$response->result = false;
    	$response->message = "";
    	$response->receiveNo = "";
    	
    	// Init
    	$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'emsa');
    	$emsaKey = $emsaConfig->key;
    	
    	// Param
    	$params = $this->getRequest()->getParams();
    	
    	// Prepare File
    	$emsaReportFile = array();
    	if(count($params['hdfemsaReportFile'])){
    		$siteUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    		foreach($params['hdfemsaReportFile'] as $HashName){
    			$path = $siteUrl . $this->view->url(array("module" => "default", "controller" => "file", "action" => "get", "file" => $HashName), null, true);
    			$emsaReportFile[] = $path;//$this->fileToBase64($path);
    		}
    	}
    	
    	try {
    		
    		// Init
    		$apiConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
    		$url = $apiConfig->sendreportproblem;
    		
    		// Set Body
    		$body = new stdClass();
    		$body->emsaKey = $emsaKey;
    		$body->emsaTitle = $params['emsaTitle'];
    		$body->emsaDetail = $params['emsaDetail'];
    		$body->emsaUrl = $params['emsaUrl'];
    		$body->emsaScreenCode = $params['emsaScreenCode'];
    		if($params['emsaChkScreen']){
    			$body->emsaScreenData = $params['emsaScreenData'];
    		}
    		$body->emsaReportFile = $emsaReportFile;
    		$body->emsaSenderName = $params['emsaSenderName'];
    		if($params['emsaChkSystem']){
	    		$body->emsaSystemBrowser = $params['emsaSystemBrowser'];
	    		$body->emsaSystemVersion = $params['emsaSystemVersion'];
    		}
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
    			$response->receiveNo = $sendResult->data->receiveNo;
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
    	
    	// Param
    	$receiveNo = $this->getRequest()->getParam('receiveNo');
    	
    	$this->view->receiveNo = $receiveNo;
    	$this->view->title = "เลขที่แจ้งรับของท่าน";
    }
    
    private function fileToBase64($path)
    {
	    $type = pathinfo($path, PATHINFO_EXTENSION);
	    $data = file_get_contents($path);
	    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
	    return $base64;
    }

}

