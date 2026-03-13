<?php

class Event_Model_Mail {
	
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
    
    function save($params)
    {
        $data = new stdClass();
        $data->result = false;
        try {

            $form = array();
            $form['EventEventID'] = $params['hdfEventID'];
            $form['UserID'] = empty($this->UserID) ? null : $this->UserID;
            $form['Name'] = $params['Name'];
            $form['Email'] = $params['Email'];
            $form['Detail'] = $params['Detail'];
            $form['Created'] = date("Y-m-d H:i:s");
            $form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
            $form['IsEnable'] = 'Y';
            $form['IsDeleted'] = 'N';
            $result = $this->db->insert("event_event_subscriber", $form);

			if($result) {
				$data->result = true;
				$data->id = $params['hdfEventID'];
			}            

        } catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
        }
        return $data;
    }
    
}

?>