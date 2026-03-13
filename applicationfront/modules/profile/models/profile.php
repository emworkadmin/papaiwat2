<?php
class Profile_Model_Profile {
	
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

    function getInfoByID($id)
    {
        $data = new stdClass();
        try {

		    $select = $this->db->select()
		    ->from(array('tbl' => 'sys_user'), array('*'))
		    ->joinLeft(array('fi' => "file_info"), "tbl.ProfileImageID = fi.ID", "")
		    ->columns(array(
		        "ProfileImageFileName" => "fi.Name",
		        "ProfileImageHashName" => "fi.HashName",
		        "ProfileImageFileType" => "fi.FileType",
		        "ProfileImageFileSize" => "fi.FileSize",
		        "ProfileImageFileExtension" => "fi.Extension"
		    ))
		    ->where("tbl.IsDeleted = ?", "N")
		    ->where("tbl.UserFrontType = ?", "F")
		    ->where("tbl.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
            $data = $stmt->fetchObject();
            
        } catch (Exception $e) {

        }
        return $data;
	}
	
	function updateProfilePicture($params) {

	    $data = new stdClass();
	    $data->result = false;
	    try {
	        
	        $moreDetail = array();
	        $whereMoreDetail = array();
	        $moreDetail['ProfileImageID'] = empty($params['profilePictureId']) ? null : $params['profilePictureId'];
	        $moreDetail['Modified'] = date('Y-m-d H:i:s');
			$moreDetail['ModifiedBy'] = $this->UserID;
			
			$whereMoreDetail["ID = ?"] = $this->UserID;
	        $result = $this->db->update('sys_user', $moreDetail, $whereMoreDetail);
	        
	        if($result){
	            $data->result = true;
	        }
	        
	    } catch (Exception $e) {
	        $data->result = false;
	        $data->message = $e->getMessage();
	    }
	    return $data;
	}

	function update($params)
	{
		$data = new stdClass();
	    $data->result = false;
	    try {
			if($params['lang'] == 'th') {
				$BirthDate = empty($params['txtBirthDate']) ? null : Emwork_Utility_DateTime::dateThaiToMySQL($params['txtBirthDate']);
			} else {
				$BirthDate = empty($params['txtBirthDate']) ? null : Emwork_Utility_DateTime::dateEngToMySQL($params['txtBirthDate']);
			}

			$form = array();
			$where = array();

			$form['TitleName'] = empty($params['txtTitle']) ? null : $params['txtTitle'];
			$form['FirstName'] = $params['txtFirstName'];
			$form['LastName'] = $params['txtLastName'];

			$form['Gender'] = $params['lstGender'];

			if($BirthDate) {
		      $form['BirthDate'] = $BirthDate;
			}

			$form['Telephone'] = $params['txtTelephone'];
			$form['Fax'] = empty($params['txtFax']) ? null : $params['txtFax'];
			$form['Mobile'] = empty($params['txtMobile']) ? null : $params['txtMobile'];
			
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $this->UserID;

			$result = $this->db->update("sys_user", $form, $where);
	        
	        if($result) {
	            $data->result = true;
	        }

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function updatePassword($params) {

		$data = new stdClass();
		$data->result = false;
		try {
			
			$form = array();
			$where = array();
			$form['Password'] = md5( $params['txtNewPasswordFirst'] );
			$form['Modified'] = date('Y-m-d H:i:s');
			$form['ModifiedBy'] = $this->UserID;
			$where["ID = ?"] = $this->UserID;
			$result = $this->db->update('sys_user', $form, $where);
			
			if($result){
				$data->result = true;
				$data->id = $this->UserID;
			}
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;		
	}	

}
?>