<?php
Class Sso {

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
			
        }
	}

	function getSiteInfo($siteID)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sso_site'), array("*"))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.ID = ? ", $siteID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getSiteInfoByKey($siteKey)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sso_site'), array("*"))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.SiteKey = ? ", $siteKey)
			;
			$stmt = $this->db->query($select);	
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	
	function isUserAllowSite($siteID, $userID)
	{
		$data = false;
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sso_site_user'), array("*"))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.AllowStatus = 'A' ")
			->where("tbl.SsoSiteID = ? ", $siteID)
			->where("tbl.UserID = ? ", $userID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data = true;
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function saveAllow ($siteID, $userID) {
		$result = new stdClass();
		$result->result = false;
		try {
			
			$actionDate = date("Y-m-d H:i:s");
			
			$form = array();
			$form["SsoSiteID"] = $siteID;
			$form["UserID"] = $userID;
			$form["AllowStatus"] = 'A';
			$form["Created"] = $actionDate;			
			$affRow = $this->db->insert("sso_site_user", $form);
			if ($affRow) {
				$result->result = true;
			}
		} catch (Exception $e) {
			
		}
		return $result;
	}
	
	function createToken($siteID, $userID)
	{
		$data = "";
		try {
			$token = $this->generateToken(32);
			$actionDate = date("Y-m-d H:i:s");
			$expiredDate = new DateTime($actionDate);
			$expiredDate->add(new DateInterval('PT5M'));
			
			$form = array();
			$form["ActionDate"] = $actionDate;
			$form["Token"] = $token;
			$form["SsoSiteID"] = $siteID;
			$form["UserID"] = $userID;
			$form["ExpiredDate"] = $expiredDate->format("Y-m-d H:i:s");
			$form["Created"] = $actionDate;
			
			$affRow = $this->db->insert("sso_token", $form);
			if ($affRow > 0) {
				$data = $token;
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function generateToken($len) {
		$token = "";
		$str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
		$strLen = strlen($str);
		if (empty($len) || strlen($len) >= 32) {
			$len = 32;
		}
		while (strlen($token) < $len) {
			$rand = rand(0, $strLen);
			$token .= substr($str, $rand, 1);
		}
		$token = bin2hex($token);
		
		return $token;
	}
	
	function checkToken($siteKey, $token) {		
		$data = new stdClass();
		try {
			$date = date("Y-m-d H:i:s");
			$select = $this->db->select()
			->from(array('tbl' => 'sso_token'), array("*"))
			->joinInner(array("s" => "sso_site"), "tbl.SsoSiteID = s.ID", "")
			->where("s.IsEnable = 'Y' ")
			->where("s.IsDeleted = 'N' ")
			->where("s.SiteKey = ? ", $siteKey)
			->where("tbl.Token = ? ", $token)
			->where("tbl.ExpiredDate >= ? ", $date)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->Token)) {
				$data->ActionDate = empty($data->ActionDate) ? null : new DateTime($data->ActionDate);
				$data->ExpiredDate = empty($data->ExpiredDate) ? null : new DateTime($data->ExpiredDate);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
}
?>