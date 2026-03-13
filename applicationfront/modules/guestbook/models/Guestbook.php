<?php

class Guestbook_Model_Guestbook {
    
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

    function getsave($text ,$period ,$start ,$end ,$lang) {

        if($this->UserID=''){
            $userid = null ;
        }else{
            $userid = $this->UserID ;
        }

        $modified = date("Y-m-d H:i:s");
        $datestart = str_replace("/","-",$start);
        $dateend = str_replace("/","-",$end);
        $result = new stdClass();
        $data->result = false;

        try {

            $form = array();
            $where = array();
            $form['Lang']  = $lang;
            $form['UserID'] = empty($this->UserID) ? null : $this->UserID;
            $form['TextSearch'] = $text;
            $form['StartDate'] = $datestart;
            $form['EndDate'] = $dateend;
            $form['Period'] = empty($period) ? null : $period;

            $form['Created'] = $modified;
            $form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
            
            $result = $this->db->insert("search_history", $form);
            
            if($result) {
                $data->ID = $this->db->lastInsertId();
                $data->result = true;
            }

        } catch (Exception $e) {
            $data->result = false;
            $data->message = $e->getMessage();
        }
        
    }

    function getListGuestbook($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$id, $lang ) {

		try {

            $select = $this->db->select()
            ->from(array("tbl" => "guestbook_message") , "")
            ->joinLeft(array("gd" => "guestbook_book_detail"), "tbl.BookInfoID = gd.ID and tbl.Lang = gd.Lang", "")
            ->columns(array(
                "tbl.ID",
                "tbl.TitleName",
                "tbl.FirstName",
                "tbl.LastName",
                "Title" => "gd.Title",
                "tbl.Detail",
                "tbl.DepartmentName",
                "tbl.Created",
                "tbl.IsEnable",
            ))
            ->where("tbl.BookInfoID = '".$id."'")
            ->where("tbl.Lang = '".$lang."'")
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ->order("tbl.Created DESC")
            ;

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

            $no = $records - (($page-1)*10);

			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
                    $data[$i]['ID'] =  $no;
                    $data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
                    $no-- ;
				}
            }
            
            // if (count($data) > 0) {
			// 	for ($i = 0; $i < count($data); $i ++) {
			// 		$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
			// 	}
            // }
            
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


    public function getOption($lang, $id) {
        $select = $this->db->select()
        ->from(array('tbl' => 'guestbook_book_instant_message'), '')
        ->joinLeft(array('d' => 'guestbook_instant_message_detail'),"tbl.InstantMessageID = d.ID", '')
        ->columns(array(
            "ID" => "tbl.ID",
            "Lang" => "d.Lang",
            "Title" => "d.Title",
        ))
        ->where("tbl.IsDeleted = ?", "N")
        ->where("tbl.IsEnable = ?", "Y")
        ->where("tbl.BookInfoID = ?", $id)
        ->where("d.Lang = ?", $lang)
        ->order("convert(d.Title USING TIS620)  ASC")
        ;
        $stmt = $this->db->query($select);
        $data = $stmt->fetchAll();
        $result = array();

        foreach ($data as $value) {
            $result[$value["ID"]] = $value["Title"];
        }
        return $result;
    }

    public function getData($lang, $id) {
        $select = $this->db->select()
        ->from(array('tbl' => 'guestbook_book_info'), '')
        ->joinLeft(array('d' => 'guestbook_book_detail'),"tbl.ID = d.ID", '')
        ->columns(array(
            "ID" => "tbl.ID",
            "Lang" => "d.Lang",
            "Title" => "d.Title",
            "IsEnable" => "tbl.IsEnable",
            "IsDeleted" => "tbl.IsDeleted"
        ))
        ->where("tbl.IsDeleted = ?", "N")
        ->where("tbl.IsEnable = ?", "Y")
        ->where("tbl.ID = ?", $id)
        ->where("d.Lang = ?", $lang)
        ->order("convert(d.Title USING TIS620)  ASC")
        ;
        $stmt = $this->db->query($select);
        $data = $stmt->fetchObject();

        return $data;
    }

    public function getTitle($lang, $id) {
        $select = $this->db->select()
        ->from(array('tbl' => 'guestbook_book_info'), '')
        ->joinLeft(array('d' => 'guestbook_book_detail'),"tbl.ID = d.ID", '')
        ->columns(array(
            "Title" => "d.Title",
        ))
        ->where("tbl.ID = ?", $id)
        ->where("d.Lang = ?", $lang)
        ->order("convert(d.Title USING TIS620)  ASC")
        ;
        $stmt = $this->db->query($select);
        $data = $stmt->fetchObject();

        return $data;
    }
    
    function save($params) {

        $modified = date("Y-m-d H:i:s");
		$data = new stdClass();
		$data->result = false;
		try {

            $id = '';
            $id = $this->db->lastInsertId();
			$form = array();
            $form['ID'] = $id;
            $form['TitleName'] = $params['TitleName'];
            $form['FirstName'] = $params['FirstName'];
            $form['LastName'] = $params['LastName'];
            $form['Lang'] = $params['lang'];
            $form['BookInfoID'] = $params['id'];
            $form['Detail'] = $params['Detail'];
            $form['DepartmentName'] = $params['DepartmentName'];
			$form['Created'] = $modified;
			$form['IsEnable']= 'Y';
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("guestbook_message", $form);
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

}

?>
