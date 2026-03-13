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
