<?php

/**
 * Description of App_ThaibulkSms
 *
 * @author
 */
class App_ThaibulkSms {

	private $username = '';
	private $password = '';
	private $message = '';
	private $sender = '';
	private $force = 'standard';
	private $url = 'http://www.thaibulksms.com/sms_api.php';

	/**
	 * @param string $username
	 */
	public function setUsername($username) {
		if (!empty($username)) {
			$this->username = $username;
		}
	}

	/**
	 * @param string $password
	 */
	public function setPassword($password) {
		if (!empty($password)) {
			$this->password = $password;
		}
	}

	/**
	 * @param string $url
	 */
	public function setUrl($url) {
		if (!empty($url)) {
			$this->url = $url;
		}
	}

	/**
	 * @param string $force
	 */
	public function setForce($force) {
		if (!empty($force)) {
			if (in_array($force, array('standard', 'premium'))) {
				$this->force = $force;
			}
		}
	}

	/**
	 * @param string $message
	 */
	public function setMessage($message) {
		if (!empty($message)) {
			$this->message = $message;
		}
	}

	/**
	 * @param string $sender
	 */
	public function setSender($sender) {
		if (!empty($sender)) {
			$this->sender = $sender;
		}
	}

	/**
	 * @param string $username
	 * @param string $password
	 * @param string $force
	 * @param string $url
	 * @param string $sender
	 * @param string $message
	 */
	public function __construct($username = '', $password = '', $force = 'standard', $url = '', $sender = '', $message = '') {
		$this->setUsername($username);
		$this->setPassword($password);
		$this->setUrl($url);
		$this->setMessage($message);
		$this->setForce($force);
		$this->setSender($sender);
	}

	public function remain() {

		$result->result = false;
		if (empty($this->username) || empty($this->password)) {
			$result->errormessage = "ไม่มี Username หรือ Password";
			return $result;
		}
		 
		$data_string = "username={$this->username}&password={$this->password}";
		$data_string .= "&tag=credit_remain" . ($force == 'premium' ? "_premium" : "");

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		$remain = curl_exec ($ch);
		curl_close ($ch);
		 
		return $remain;
	}

	/**
	 * @param string $msisdn
	 * @return stdClass
	 */
	public function send($msisdn) {

		$result->result = false;
		 
		if (empty($msisdn)) {
			$result->errormessage = "กรุณากรอกหมายเลขโทรศัพท์";
			return $result;
		}
		 
		if (is_array($msisdn)) {
			$msisdn = implode(',', $msisdn);
		}
		 
		if (empty($this->username) || empty($this->password)) {
			$result->errormessage = "ไม่มี Username หรือ Password";
			return $result;
		}
		$ScheduledDelivery = date("ymdHi");
		$data_string = "username={$this->username}&password={$this->password}&msisdn=$msisdn";
		$data_string .= "&message={$this->message}";
		//$data_string .= "&sender={$this->sender}";
		$data_string .= "&ScheduledDelivery=$ScheduledDelivery&force={$this->force}";

		$agent = "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.4) Gecko/20030624 Netscape/7.1 (ax)";
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		$resultXml = curl_exec ($ch);
		curl_close ($ch);

		$xmlObj = new App_ThaibulkSms_XmlToArray($resultXml);
		$xml = $xmlObj->createArray();
		 
		$count = count($xml['SMS']['QUEUE']);
		 
		if($count > 0){
			$count_pass = 0;
			$count_fail = 0;
			$used_credit = 0;

			$temp = array();
			for($i=0;$i<$count;$i++){
				if($xml['SMS']['QUEUE'][$i]['Status']){
					$count_pass++;
					$used_credit += $xml['SMS']['QUEUE'][$i]['UsedCredit'];

					$temp[] = array(
							"Msisdn" => $xml['SMS']['QUEUE'][$i]['Msisdn'],
							"Transaction" => $xml['SMS']['QUEUE'][$i]['Transaction'],
							"UsedCredit" => $xml['SMS']['QUEUE'][$i]['UsedCredit'],
							"RemainCredit" => $xml['SMS']['QUEUE'][$i]['RemainCredit']
					);
				}else{
					$count_fail++;
				}
			}

			$result->result = true;
			$result->passAmount = $count_pass;
			$result->usedCredit = $used_credit;
			$result->failAmount = $count_fail;
			$result->details = $temp;
		}else{
			$result->result = false;
			$result->errormessage = $xml['SMS']['Detail'];
		}
		 
		return $result;
	}
}