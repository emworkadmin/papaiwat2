<?php

Class Complain_Model_Stat {

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

    function getlistComplain($year, $month, $lang = 'th')
    {
        $data = array();
        try {

            // $selectData = $this->db->select()
            // ->from(array('tbl' => 'complain_info'), '')
            // ->columns(array(
            //     "Amount" => "count(tbl.ID)",
            //     "ResponseDate" => "ifnull(count(tbl.ResponseDate), 0)",
            //     "Day" => "DAY(tbl.Created)",
            //     "Month" => "MONTH(tbl.Created)",
            //     "Year" => "YEAR(tbl.Created)"
            // ))
            // ->where("tbl.IsEnable = 'Y'")
            // ->where("tbl.IsDeleted = 'N'")
            // ->order("tbl.Created DESC")
            // ->group("DAY(tbl.Created)")
            // ;
            // if($year) {
            //     $selectData = $selectData->where("YEAR(tbl.Created) = ?", $year);
            // }
            // if($month) {
            //     $selectData = $selectData->where("MONTH(tbl.Created) = ?", $month);
            // } else {
            //     $selectData = $selectData->where("MONTH(tbl.Created) = ?", date('m'));
            // }

            $year = $year == null ? date('Y') : $year;
            $month = $month == null ? date('m') : $month;
            $startDate = date('Y-') . $month . '-1';

            if(strlen($year)>4) {
                return $data;
            }
 
            $select = "
            SELECT DAY(a.Date) as Date, a.Date as FullDate, ifnull(d.Amount,0) as Amount, ifnull(d.ResponseDate,0) as Response
            FROM (
                SELECT LAST_DAY('{$startDate}') - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY AS DATE
                FROM (
                    SELECT 0 AS a UNION ALL
                    SELECT 1 UNION ALL
                    SELECT 2 UNION ALL
                    SELECT 3 UNION ALL
                    SELECT 4 UNION ALL
                    SELECT 5 UNION ALL
                    SELECT 6 UNION ALL
                    SELECT 7 UNION ALL
                    SELECT 8 UNION ALL
                    SELECT 9
                ) AS a 
                CROSS	JOIN (
                    SELECT 0 AS a UNION ALL
                    SELECT 1 UNION ALL
                    SELECT 2 UNION ALL
                    SELECT 3 UNION ALL
                    SELECT 4 UNION ALL
                    SELECT 5 UNION ALL
                    SELECT 6 UNION ALL
                    SELECT 7 UNION ALL
                    SELECT 8 UNION ALL
                    SELECT 9
                ) AS b 
                CROSS JOIN (
                    SELECT 0 AS a UNION ALL
                    SELECT 1 UNION ALL
                    SELECT 2 UNION ALL
                    SELECT 3 UNION ALL
                    SELECT 4 UNION ALL
                    SELECT 5 UNION ALL
                    SELECT 6 UNION ALL
                    SELECT 7 UNION ALL
                    SELECT 8 UNION ALL
                    SELECT 9
                ) AS c
            ) AS a
            Left Join (
                SELECT COUNT(tbl.ID) AS Amount, IFNULL(COUNT(tbl.ResponseDate), 0) AS ResponseDate, DAY(tbl.Created) AS Day, MONTH(tbl.Created) AS Month, YEAR(tbl.Created) AS Year
                FROM complain_info AS tbl
                WHERE (tbl.IsEnable = 'Y') AND (tbl.IsDeleted = 'N') AND (YEAR(tbl.Created) =  '{$year}') AND (MONTH(tbl.Created) = '{$month}')
                GROUP BY DAY(tbl.Created), MONTH(tbl.Created), YEAR(tbl.Created)
            ) d on DAY(a.DATE) = d.Day
            
            WHERE a.Date BETWEEN '{$startDate}' AND LAST_DAY('{$startDate}')
            ORDER BY a.Date;
            ";

            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        } catch (Exception $e) {

        }
        return $data;
    }

}

?>