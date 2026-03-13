<?php

class Webboard_Model_Requirement {
    
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
    
    function save($params) {

		$data = new stdClass();
		$data->result = false;
		try {

			$param = $params['params'];
			$id = $params[''];
            $modified = date("Y-m-d H:i:s");
            // $params['Date'] =  Emwork_Utility_DateTime::dateThaiToMySQL( $params['Date']);
            // $params['Date'] .= empty($params['Time']) ? " 0:00" : " " . str_replace(" : ", ":", $params['Time']);

			$form = array();
            $where = array();
            // $form['TitleName'] = $params['TitleName'];
            // $form['FirstName'] = $params['FirstName'];
            // $form['LastName'] = $params['LastName'];
            $form['Title'] = $params['txtTitle'];
            $form['Detail'] = $params['MoreDetail'];
            $form['Created'] = $modified;
            $form['Modified'] = $modified;
            $form['CreatedBy']= $this->UserID;
			$where['ID = ? '] = $id;
            $result = $this->db->insert("webboard_requirement", $form, $where);
            $id = $this->db->lastInsertId();
										
			
			if($result){
				$data->id = $id;
				$data->result = true;
			}
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

    
	function update($params) {

		$data = new stdClass();
		$data->result = false;
		try {

			$param = $params['params'];
			$id = $params['hdfID'];
            $modified = date("Y-m-d H:i:s");
			$form = array();
			$where = array();
			$form['Title'] = $params['txtTitle'];
            $form['Detail'] = $params['MoreDetail'];
			$form['Modified'] = $modified;
            $form['ModifiedBy']= $this->UserID;
			$where['ID = ? '] = $id;
			$result = $this->db->update("webboard_requirement", $form, $where);
										
			
			if($result){
				$data->id = $id;
				$data->result = true;
			}
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function getData() {
	    $data = new stdClass();
	    try {
	        $select = $this->db->select()
            ->from(array("tbl" => "webboard_requirement"), "*");
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

}

?>
