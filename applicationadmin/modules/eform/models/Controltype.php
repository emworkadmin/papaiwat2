<?php
class Eform_Model_Controltype {

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

    function getlist()
    {
        $data = array();
        try {
            $select = $this->db->select()
            ->from(array("tbl" => "eform_control_type"), "")
            ->columns(array(
                "tbl.ID",
                "tbl.Name"
            ))
            ->order("tbl.ID ASC")
            ;
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();
        } catch(Exception $e) {

        }
        return $data;
    }

    function save($params) 
    {
        $data = new stdClass();
        $data->result = false;
        try {
            $form = array();
            $where = array();
            $form['IsDeleted'] = 'Y';
            $where['EformFormID = ?'] = $params['hdfID'];
            $this->db->update("eform_form_control", $form, $where);
            if(count($params['params']['ControlParams']) > 0) {
                $i = 0;
                foreach($params['params']['ControlParams'] as $val ) {
                    $obj = json_decode($val, true);         
                    $i ++;
                    $form = array();
                    $where = array();
                    $form['EformFormID'] = $params['hdfID'];
                    $form['EformControlTypeID'] = $obj['EformControlTypeID'];
                    $form['Ordering'] = $i;
                    $form['IsEnable'] = 'Y';
                    $form['IsDeleted'] = 'N';
                    $where['ID = ?'] = $obj['ID'];
                    $affectedRows = $this->db->update("eform_form_control", $form, $where);
                    if ($affectedRows == 0) {
                        $this->db->insert("eform_form_control", $form);
                        $obj["ID"] = $this->db->lastInsertId();
                    }
                    foreach($obj as $k => $v) {
                        if($k != 'EformControlTypeID' && $k != 'ID') {
                            $formDetail = array();
                            $whereDetail = array();
                            $formDetail['EformControlID'] = $obj["ID"];
                            $formDetail['Name'] = $k;
                            $formDetail['Value'] = $v;
                            if($k == 'OPTION') {
                                $option = array();
                                $option = empty($v) ? null : $v;
                                $formDetail['Value'] = json_encode($option);
                            }
                            if($k == 'DROPDOWN') {
                                $dropdown = array();
                                $dropdown = empty($v) ? null : $v;
                                $formDetail['Value'] = json_encode($dropdown);
                            }                                
                            if ($affectedRows == 0) {
                                $this->db->insert("eform_form_control_property", $formDetail);
                            } else {
                                $whereDetail['EformControlID = ?'] = $obj["ID"];
                                $whereDetail['Name = ?'] = $k;
                                $this->db->update("eform_form_control_property", $formDetail, $whereDetail);                                
                            }
                        }
                    }
                }
            }
            $data->result = true;
        } catch (Exception $e) {
			//echo $e->getMessage();
            $data->result = false;
        }
        return $data;
    }

    function getReCaptchakey() {
		$result = array();
		
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sys_setting'), "")
			->columns(array(
				"tbl.Name",
				"tbl.Value"
			))
			->where("tbl.GroupName = 'General'")
			->where("tbl.Name LIKE '%Captcha%'")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			foreach ($data as $key => $value) {
				$result[$value["Name"]] = $value["Value"]; 
			}
			
		} catch (Exception $e) {
			//echo $e->getMessage();
		}
		return $result;
	}
}
?>