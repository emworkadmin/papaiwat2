<?php
/**
 * EMWORK Library
 *
 * @category   Emwork
 * @package    Emwork
 * @copyright
 * @license
 * @version
 */

/**
 * Static methods for convert Thai Date
 *
 * @category   Emwork
 * @package    Emwork
 * @copyright
 * @license
 */
class Emwork_Utility
{
    public static function notShowNull($input, $displayNull = '-'){
        $input = trim($input);
        if(empty($input))
            $result = $displayNull;
        else
            $result = $input;

        return $result;
    }
    
    public static function getValueClass($data, $property, $displayNull = '-'){
    	if (!isset($data->$property)) {
    		return $displayNull;
    	}
    	$input = trim($data->$property);
    	if(empty($input))
    		$result = $displayNull;
    	else
    		$result = $input;
    
    	return $result;
    }
    
    public static function startsWith($haystack, $needle)
    {
    	return !strncmp($haystack, $needle, strlen($needle));
    }
    
    public static function endsWith($haystack, $needle)
    {
    	$length = strlen($needle);
    	if ($length == 0) {
    		return true;
    	}
    
    	return (substr($haystack, -$length) === $needle);
    }
    
    // Perform a 32bit left shift
    public static function leftShift32($number, $steps) {
    	// convert to binary (string)
    	$binary = decbin($number);
    	// left-pad with 0's if necessary
    	$binary = str_pad($binary, 32, "0", STR_PAD_LEFT);
    	// left shift manually
    	$binary = $binary.str_repeat("0", $steps);
    	// get the last 32 bits
    	$binary = substr($binary, strlen($binary) - 32);
    	// if it's a positive number return it
    	// otherwise return the 2's complement
    	return ($binary{0} == "0" ? bindec($binary) :
    	-(pow(2, 31) - bindec(substr($binary, 1))));
    }

	/**
	 * 
	 * @param array $option
	 * @param string $emtpyStr
	 * @return array <string, array>
	 */
    public static function addEmptyOption($option, $emtpyStr = '-- โปรดเลือก --'){
    	$newOption[''] = empty($emtpyStr) ? '-- โปรดเลือก --' : $emtpyStr;
    	if ($option) {
	    	foreach ($option as $key => $val) {
	    		$newOption[$key] = $val;
	    	}
    	}
    
    	return $newOption;
    }
    
    
    public static function readClob($field){
    	if ($field == null) 
    		return "";
    	return $field->read($field->size());
    } 
    
    // Function to get the client IP address
    public static function get_client_ip() {
    	$ipaddress = '';
    	if ($_SERVER['HTTP_CLIENT_IP'])
    		$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    	else if($_SERVER['HTTP_X_FORWARDED_FOR'])
    		$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    	else if($_SERVER['HTTP_X_FORWARDED'])
    		$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    	else if($_SERVER['HTTP_FORWARDED_FOR'])
    		$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    	else if($_SERVER['HTTP_FORWARDED'])
    		$ipaddress = $_SERVER['HTTP_FORWARDED'];
    	else if($_SERVER['REMOTE_ADDR'])
    		$ipaddress = $_SERVER['REMOTE_ADDR'];
    	else
    		$ipaddress = 'UNKNOWN';
    	return $ipaddress;
    }
    
    /**
     * 
     * @param string $url
     * @param array $params
     */
    public static function sendAsynchronous($url, $params) {
		
		//$url = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . "/activity/member/sendmail";

		foreach ($params as $key => &$val) {
			if (is_array($val)) $val = implode(',', $val);
			$post_params[] = $key.'='.urlencode($val);
		}
		$post_string = implode('&', $post_params);
		
		$parts=parse_url($url);
		
		$fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30);
		
		//pete_assert(($fp!=0), "Couldn’t open a socket to ".$url." (".$errstr.")");
		assert(($fp!=0), "Couldn’t open a socket to ".$url." (".$errstr.")");
		
		$out = "POST ".$parts['path']." HTTP/1.1\r\n";
		$out.= "Host: ".$parts['host']."\r\n";
		$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out.= "Content-Length: ".strlen($post_string)."\r\n";
		$out.= "Connection: Close\r\n\r\n";
		if (isset($post_string)) $out.= $post_string;
		
		fwrite($fp, $out);
		fclose($fp);
    }
    
    public static function callAPI($method, $url, $data = false)
    {
    	$curl = curl_init();
    
    	switch ($method)
    	{
    		case "POST":
    			curl_setopt($curl, CURLOPT_POST, 1);
    
    			if ($data)
    				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    			break;
    		case "PUT":
    			curl_setopt($curl, CURLOPT_PUT, 1);
    			break;
    		default:
    			if ($data)
    				$url = sprintf("%s?%s", $url, http_build_query($data));
    	}
    
    	// Optional Authentication:
    	//curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    	//curl_setopt($curl, CURLOPT_USERPWD, "username:password");
    
    	curl_setopt($curl, CURLOPT_URL, $url);
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
    	$result = curl_exec($curl);
    
    	curl_close($curl);
    
    	return $result;
    }
}
