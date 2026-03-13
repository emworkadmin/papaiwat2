<?php
class Wat_Model_Search {
	
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
			$this->db = Zend_Db_Table::getDefaultAdapter ();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			
		}
	}
	
	static function saveHistory ($lang, $searchText, $searchRegion, $searchProvince, $searchType, $pageIndex = 1, $resultAmount = 0) {
		
		$data = new stdClass();
		$data->result = false;
		
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			
			if (empty($pageIndex) || ($pageIndex <= 0)) {
				$pageIndex = 1;
			}
			if (empty($resultAmount) || ($resultAmount <= 0)) {
				$resultAmount = 0;
			}
			if (empty($searchRegion) || ($searchRegion <= 0)) {
				$searchRegion = null;
			}
			if (empty($searchProvince) || ($searchProvince <= 0)) {
				$searchProvince = null;
			}
			if (empty($searchType) || ($searchType <= 0)) {
				$searchType = null;
			}
			
			$authobj = Zend_Auth::getInstance();
			$userId = null;
			if ($authobj->hasIdentity()) {
				$userId = $authobj->getIdentity()->UserID;
			}
			
			$actionDateTime = date("Y-m-d H:i:s");
			$actionDateTimeArr = explode(" ", $actionDateTime);
			$actionDate = $actionDateTimeArr[0];
			$actionTime = $actionDateTimeArr[1];
			
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$form = array();
			$form['Lang']  = $lang;
			$form['SessionID']  = Zend_Session::getId();
			$form['UserID']  = $userId;
			$form['RemoteIP']  = $request->getServer('REMOTE_ADDR');
			if (!empty($form["RemoteIP"])) {
				if (function_exists('geoip_country_code_by_name')) {
					$countryCode = geoip_country_code_by_name($form["RemoteIP"]);
					$form["GeoIP"] = $countryCode;
				}
			}
			$form['SearchText']  = $searchText;
			$form['SearchRegion']  = $searchRegion;
			$form['SearchProvince']  = $searchProvince;
			$form['SearchType']  = $searchType;
			$form['PageIndex']  = $pageIndex;
			$form['ResultAmount']  = $resultAmount;
			$form['ActionDate']  = $actionDate;
			$form['ActionTime']  = $actionTime;
			
			$form['Created'] = $actionDateTime;
			$form['CreatedBy'] = $userId;
			
			$result = $db->insert("wat_search_history", $form);
			
			if($result) {
				$data->result = true;
			}
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
}

?>
