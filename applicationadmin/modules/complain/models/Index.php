<?php

Class Complain_Model_Index {

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

    function getList($sidx, $sord = 'asc', $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
    {
        $data = array();
       
        try {
          
            $select = $this->db->select()
            ->from(array("tbl" => "complain_info") , "")
            ->columns(array(
                "tbl.ID",
                "DateComplain" => "tbl.ComplainDate",
                "Topic" => "tbl.ComplainTopic",
                "FullName" => "tbl.FullName",
                "Status" => "tbl.Status"
            ))
            ->where("tbl.IsDeleted = 'N'")
            ;

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where("tbl.ComplainTopic like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }
            
            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.Status = ?", $advancesearch['status']);
            }
            
            if (!empty($advancesearch['sendfrom'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfrom']);
                $select = $select->where("tbl.ComplainDate >= ?", $value);
            }

            if (!empty($advancesearch['sendto'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendto']);
                $select = $select->where("tbl.ComplainDate <= ?", $value . " 23:59:59");
            }

            // find total
            $records = $this->getListCount($select);

            // set ordering and total            
            if (empty($sidx)) {
                $sidx = "ID";
            }
            $rowSort = $sidx;
            
            $select = $this->db->select()
            ->from(array('tbl' => $select), array('*'))
            ->columns(array("RECORD" => "abs($records)"))
            ->order("$rowSort $sord")
            ->limitPage($page, $rows)
            ;
            
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $data;
    }

    function getListCount($select) {
        $select = $this->db->select()
        ->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
        ;
        $stmt = $this->db->query($select);
        $data = $stmt->fetchObject();
        return $data->RECORDS;
    }

    public function getData($id)
    {
        $data = new stdClass();

        try {

			$select = $this->db->select()
            ->from(array("tbl" => "complain_info") , "*")
            ->where("tbl.ID = ?", $id)
            ;
            $stmt = $this->db->query($select);
            $data = $stmt->fetchObject();

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $data;
    }

    function update($params)
    {
        $data = new stdClass();
        $data->result = false;

        $authobj = Zend_Auth::getInstance();
        $auth = $authobj->getIdentity();
        $UserID = isset($auth->UserID) ? $auth->UserID : '';

        try {
            $modified = date("Y-m-d H:i:s");
            $id = $params['hdfID'];

            $form = array();
            $where = array();

            $form['ResponseDetail'] = $params['ResponseDetail'];
            $form['Status'] = $params['Status'];
            $form['ResponseDate'] = $modified;
            $form['ResponseUserID'] = $UserID;
            $where['ID = ?'] = $id;
			$result = $this->db->update("complain_info", $form, $where); 
			
            if($result) {
                $data->result = true;
                $data->ID = $id;
            }

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $data;
    }

    function savelog($params)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $authobj = Zend_Auth::getInstance();
        $auth = $authobj->getIdentity();
        $UserID = isset($auth->UserID) ? $auth->UserID : '';

        $result = new stdClass();
        $result->result = false;
        $result->message = "";
        try {

            $modified = date("Y-m-d H:i:s");

            $form = array();
            $form['ComplainID'] = $params['hdfID'];
            $form['ResponseDetail'] = $params['ResponseDetail'];
            $form['Status'] = $params['Status'];
            $form['Created'] = $modified;
            $form['Createdby'] = $UserID;

            $result->result = $db->insert("complain_response_log", $form);

        } catch (Exception $e) {
            $result->message = $e->getMessage();
        }
        return $result;
    }

    function getresponseList($sidx, $sord = 'asc', $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
    {
        $data = array();
       
        try {
          
            $select = $this->db->select()
            ->from(array("tbl" => "complain_response_log") , "")
            ->joinleft(array("su" => "sys_user"), "tbl.CreatedBy = su.ID", "")
            ->columns(array(
                "tbl.ID",
                "tbl.ResponseDetail",
                "tbl.Status",
                "tbl.Created",
                "FullName" => "CONCAT(su.TitleName,' ',su.FirstName,' ',su.LastName)"
            ))
            ->where("tbl.IsDeleted = 'N'")
            ->where("tbl.ComplainID = ?", $advancesearch['ComplainID'])
            ;

            // find total
            $records = $this->getListCount($select);

            // set ordering and total            
            if (empty($sidx)) {
                $sidx = "Created";
            }
            $rowSort = $sidx;
            
            $select = $this->db->select()
            ->from(array('tbl' => $select), array('*'))
            ->columns(array("RECORD" => "abs($records)"))
            ->order("$rowSort $sord")
            ->limitPage($page, $rows)
            ;
            
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $data;
    }

}

?>