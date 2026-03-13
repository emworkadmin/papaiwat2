<?php

class System_Model_Register {

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
	
	/**
	 * 
	 * @param string $username
	 * @return boolean	return TRUE ถ้ามี Username อยู่ในระบบ, return FALSE ถ้าเป็นกรณีอื่นๆ
	 */
	public function checkUsername($username) {
		$result = false;
		try {
			$select = $this->db->select()->from(array('tbl' => 'sys_user'), array('Username'))->where("Username = ?", $username)->where("IsDeleted = ?", 'N');
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			$result = !empty($data->Username);
		} catch (Exception $e) {
			
		}
		
		return $result;
	}
	
	/**
	 * 
	 * @param string $email
	 * @return boolean	return TRUE ถ้ามี Email อยู่ในระบบ, return FALSE ถ้าเป็นกรณีอื่นๆ
	 */
	public function checkEmail($email) {
		$result = false;
		try {
			$select = $this->db->select()->from(array('tbl' => 'sys_user'), array('Email'))->where("Email = ?", $email)->where("IsDeleted = ?", 'N');
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			$result = !empty($data->Email);
		} catch (Exception $e) {
			
		}
		
		return $result;
	}
	
	/**
	 *
	 * @param string $facebookId
	 * @return boolean	return TRUE ถ้ามี facebook Id อยู่ในระบบ, return FALSE ถ้าเป็นกรณีอื่นๆ
	 */
	public function checkFacebookId($facebookId) {
		$result = false;
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_user'), array(""))
			->joinInner(array("d" => "sys_user_more_detail"), "tbl.id = d.user_id", array("facebook_id"))
			->where("d.facebook_id = ?", $facebookId)
			->where("tbl.is_deleted = ?", 'N');
				
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
				
			$result = !empty($data->facebook_id);
		} catch (Exception $e) {
				
		}
	
		return $result;
	}
	
	public function register($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			// ---------------------------------------- Insert user ---------------------------------------- //
			$form = array();
			$form['Username'] = $params['txtEmail'];
			$form['Password'] = md5($params['txtPassword']);
			//$form['title_name_id'] = 'O';
			//$form['title_name_other'] = empty($params['txtTitleOtherTH']) ? null : $params['txtTitleOtherTH'];
			$form['FirstName'] = empty($params['txtFirstNameTH']) ? null : $params['txtFirstNameTH'];
			$form['LastName'] = empty($params['txtLastNameTH']) ? null : $params['txtLastNameTH'];
			$form['Email'] = $params['txtEmail'];
			$form['UserType'] = 'F';
			$form['Created'] = date('Y-m-d H:i:s');
			$form['IsEnable'] = 'N';
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert('sys_user', $form);
			$userId = $this->db->lastInsertId();
			
			// สร้าง Activate code
			$uniqid = uniqid();
			$activateCode= $uniqid . md5($uniqid . $userId);
			
			// Update created by
			$form = array();
			$form['CreatedBy'] = $userId;
			$form['ActivateCode'] = $activateCode;
			$where['ID = ?'] = $userId;
			$result = $this->db->update('sys_user', $form, $where);
						
			$data->result = $result > 0;
			$data->id = $userId;
			$data->activateCode= $activateCode;
			
		} catch (Exception $e) {
			
		}
		return $data;		
	}
	
	public function registerFacebook($params) {
		//echo '<pre>'; print_r($params); echo '</pre>'; exit();
		
		$data = new stdClass();
		$data->result = false;
		try {
				
			// ---------------------------------------- Insert user ---------------------------------------- //
			$form = array();
			$form['username'] = $params['email'];
			$form['title_name_id'] = 'O';
			$form['title_name_other'] = empty($params['txtTitleOtherTH']) ? null : $params['txtTitleOtherTH'];
			$form['first_name'] = empty($params['txtFirstNameTH']) ? null : $params['txtFirstNameTH'];
			$form['last_name'] = empty($params['txtLastNameTH']) ? null : $params['txtLastNameTH'];
			$form['email'] = $params['email'];
			$form['user_type'] = 'F';
			$form['created'] = date('Y-m-d H:i:s');
			$form['is_enable'] = 'Y';
			$form['is_deleted'] = 'N';
			$result = $this->db->insert('sys_users', $form);
			$userId = $this->db->lastInsertId();
				
			// Update created by
			$form = array();
			$form['created_by'] = $userId;
			$where['id = ?'] = $userId;
			$result = $this->db->update('sys_users', $form, $where);
			
			// --------------------------------------- Insert member --------------------------------------- //
			$user = array();
			$whereUser = array();
			
			$member['user_id'] = $userId;
			$member['title_name_th_id'] = 'O';
			$member['title_name_other_th'] = empty($params['txtTitleOtherTH']) ? null : $params['txtTitleOtherTH'];
			$member['first_name_th'] = empty($params['txtFirstNameTH']) ? null : $params['txtFirstNameTH'];
			$member['last_name_th'] = empty($params['txtLastNameTH']) ? null : $params['txtLastNameTH'];
			$member['title_name_en_id'] = 'O';
			$member['title_name_other_en'] = empty($params['txtTitleOtherEN']) ? null : $params['txtTitleOtherEN'];
			$member['first_name_en'] = empty($params['txtFirstNameEN']) ? null : $params['txtFirstNameEN'];
			$member['last_name_en'] = empty($params['txtLastNameEN']) ? null : $params['txtLastNameEN'];
			$member['is_seller'] = empty($params['isSeller']) ? 'N' : $params['isSeller'];
			$member['seller_type'] = empty($params['txtSellerType']) ? null : $params['txtSellerType'];
			$member['juristic_name_th'] = empty($params['txtJuristicNameTH']) ? null : $params['txtJuristicNameTH'];
			$member['juristic_name_en'] = empty($params['txtJuristicNameEN']) ? null : $params['txtJuristicNameEN'];
			$member['registered_capital'] = empty($params['txtRegisteredCapital']) ? null : str_replace(',', '', $params['txtRegisteredCapital']);
			$member['introduce_th'] = empty($params['txtIntroduceTH']) ? null : $params['txtIntroduceTH'];
			$member['introduce_en'] = empty($params['txtIntroduceEN']) ? null : $params['txtIntroduceEN'];
			$member['identification_number'] = empty($params['txtIdentification']) ? null : $params['txtIdentification'];
			$member['address_th'] = empty($params['txtAddressTH']) ? null : $params['txtAddressTH'];
			$member['address_en'] = empty($params['txtAddressEN']) ? null : $params['txtAddressEN'];
			$member['province_code'] = empty($params['lstProvince']) ? null : $params['lstProvince'];
			$member['district_code'] = empty($params['lstDistrict']) ? null : $params['lstDistrict'];
			$member['subdistrict_code'] = empty($params['lstSubdistrict']) ? null : $params['lstSubdistrict'];
			$member['postcode'] = empty($params['txtPostcode']) ? null : $params['txtPostcode'];
			$member['telephone'] = empty($params['txtTelephone']) ? null : $params['txtTelephone'];
			$member['telephone_ext'] = empty($params['txtTelephoneExt']) ? null : $params['txtTelephoneExt'];
			$member['fax'] = empty($params['txtFax']) ? null : $params['txtFax'];
			$member['mobile'] = empty($params['txtMobile']) ? null : $params['txtMobile'];
			$member['website'] = empty($params['txtWebsite']) ? null : $params['txtWebsite'];
			$member['facebook_url'] = empty($params['txtFacebookUrl']) ? null : $params['txtFacebookUrl'];
			$member['lineid'] = empty($params['txtLineId']) ? null : $params['txtLineId'];
			$member['shareholders'] = empty($params['shareholder']) ? json_encode(array()) : json_encode($params['shareholder']);
			$member['created'] = date('Y-m-d H:i:s');
			$member['created_by'] = $userId;
			$member['is_enable'] = 'Y';
			$member['is_deleted'] = 'N';
			$result = $this->db->insert("member_info", $member);
			$memberId = $this->db->lastInsertId();
				
			// --------------------------------------- Insert more detail --------------------------------------- //
			$moreDetail = array();
			$moreDetail['user_id'] = $userId;
			$moreDetail['facebook_id'] = $params['facebook_id'];
			$moreDetail['facebook_name'] = $params['facebook_name'];
			$moreDetail['profile_picture_id'] = empty($params['profilePictureId']) ? null : $params['profilePictureId'];
			$moreDetail['auth_type'] = 'fb';
			$moreDetail['created'] = date('Y-m-d H:i:s');
			$moreDetail['created_by'] = $userId;
			$moreDetail['is_enable'] = 'Y';
			$moreDetail['is_deleted'] = 'N';
			$this->db->insert('sys_user_more_detail', $moreDetail);
				
			$data->result = $result > 0;
			$data->id = $userId;
				
		} catch (Exception $e) {
				
		}
		return $data;
	}
	function getMemberinfoLog($memberID) {
		
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'member_info'), array('tbl.*'))
			->where("tbl.id = ? ", $memberID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	function getMemberInterestIpTypeLog($memberID) {
		
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'member_interest_ip_type'), array('tbl.*'))
			->where("tbl.member_id = ? ", $memberID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getMemberInterestProductCategoryLog($memberID) {
		
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'member_interest_product_category'), array('tbl.*'))
			->where("tbl.member_id = ? ", $memberID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getMemberImageLog($memberID) {
		
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'member_image'), array('tbl.*'))
			->where("tbl.member_id = ? ", $memberID)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function updatelogmemberadd($allid) {
		
		$result= new stdClass();
		try {
			$form = array();
			$form['member_id'] = $allid['memberid'];
			$form['path'] = $allid['url'];
			$form['access_type'] = 'F';
			$form['platform'] = 'W';
			$form['created'] = date('Y-m-d H:i:s');
			$form['created_by'] = $allid['userid'];
			$form['is_enable'] = 'Y';
			$form['is_deleted'] = 'N';
			$result->result = $this->db->insert('log_member', $form);
		} catch (Exception $e) {
			
		}
		return $result;
	}
	
	function updatelogmemberaddM($allid) {
		
		$result= new stdClass();
		try {
			$form = array();
			$form['member_id'] = $allid['memberid'];
			$form['path'] = $allid['url'];
			$form['access_type'] = 'F';
			$form['platform'] = 'M';
			$form['created'] = date('Y-m-d H:i:s');
			$form['created_by'] = $allid['userid'];
			$form['is_enable'] = 'Y';
			$form['is_deleted'] = 'N';
			$result->result = $this->db->insert('log_member', $form);
		} catch (Exception $e) {
			
		}
		return $result;
	}
	
}

?>
