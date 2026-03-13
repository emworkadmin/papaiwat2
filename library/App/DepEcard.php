<?php

/**
 * Description of App_DepEcard
 *
 * @author
 */
class App_DepEcard {
	
	public function getEcard($wsdlUrl, $personCode, $ipAddress) {
		
		$result = new stdClass();
		$result->result = true;
		$result->errorMessage = null;
		$result->personInfo = null;	
		
		try {
			
			$client = new Zend_Soap_Client($wsdlUrl, array('soap_version' => SOAP_1_1));
			$client->resetSoapInputHeaders();
			$clientResult = $client->getCrdRequest("", $personCode, $ipAddress);
			
			if (count($clientResult)) {
				$result->personInfo = new stdClass();
				
				foreach ($clientResult as $info) {			
					$prefixCode = iconv('utf-8', 'ISO-8859-1', $info->REQUEST_PREFIX_CODE);
					$firstName = iconv('utf-8', 'ISO-8859-1', $info->REQUEST_FIRST_NAME_THAI);
					$lastName = iconv('utf-8', 'ISO-8859-1', $info->REQUEST_LAST_NAME_THAI);
			
					$result->personInfo->prefixCode = $prefixCode;
					$result->personInfo->firstName = $firstName;
					$result->personInfo->lastName = $lastName;
					
					break;
				}
			}
		} catch (Exception $e) {
			$result->result = false;
			$result->errorMessage = $e->getMessage();
		}
		
		return $result;
	}
	
}