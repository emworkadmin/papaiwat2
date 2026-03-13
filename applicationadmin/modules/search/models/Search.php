<?php

class Search_Model_Search {
    
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
            //echo $e->getMessage();
        }
    }
    
    function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
        $data = array();

        try {
            $select = $this->db->select()
            ->from(array("tbl" => "search_history") , "")
            ->columns(array(
                "tbl.ID",
                "tbl.TextSearch",
                "tbl.StartDate",
                "tbl.EndDate",
                "tbl.Period",
                "tbl.Type",
                "COUNT(*) AS Sumsearch"
                //"tbl.IsEnable",
            ))
            ->group("tbl.TextSearch")
            ->group("tbl.StartDate")
            ->group("tbl.EndDate")
            ->group("tbl.Period")
            ->group("tbl.Type")
            ->where("tbl.IsDeleted = 'N'")
            ;
           
            // set criteria
            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where("tbl.TextSearch like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }
            
            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.IsEnable = ?", $advancesearch['status']);
            }
            
            if (!empty($advancesearch['period'])) {
                $select = $select->where("tbl.Period = ?", $advancesearch['period']);
            }
            
            if (!empty($advancesearch['sendfrom'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfrom']);
                $select = $select->where("tbl.Created >= ?", $value);
            }

            if (!empty($advancesearch['sendto'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendto']);
                $select = $select->where("tbl.Created <= ?", $value . " 23:59:59");
            }
            
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
            //echo $e->getMessage();
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
}

?>
