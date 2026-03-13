<?php

class Emsa_Model_Index {

	/**
	 * 
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;

	function __construct() {
		try {
			// setup database
			$this->db = Zend_Db_Table::getDefaultAdapter();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance();
			$this->auth = $authobj->getIdentity();
			$this->UserID = isset($this->auth->UserID) ? $this->auth->UserID : '';
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}

	public function getSystem() {
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
				$response->data->isReportProblem = $sendResult->data->isReportProblem;
				$response->data->isReportService = $sendResult->data->isReportService;
				$response->data->isChatOnline = $sendResult->data->isChatOnline;
				$response->data->isManual = $sendResult->data->isManual;
				$response->data->isTracking = $sendResult->data->isTracking;
				$response->data->isContract = $sendResult->data->isContract;
				$response->data->isReport = $sendResult->data->isReport;
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
}

?>
