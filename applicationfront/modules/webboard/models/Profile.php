<?php

class Webboard_Model_Profile {
    
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

function getListPost($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$id, $lang ) {

    try {

        $selectAmount = $this->db->select()->from(array('tbl' => "webboard_reply"), '')
        // ->joinInner(array("a" => "guestbook_book_info"), "tbl.BookInfoID = a.ID" , "")
        ->columns(array(
            "tbl.PostID",
            "Amount" => "count(distinct tbl.ID)"
            ))
        ->where("tbl.Status = 'Y' ")
        ->where("tbl.IsEnable = 'Y' ")
        ->where("tbl.IsDeleted = 'N' ")
        ->group("tbl.PostID")
        ;
        
        $select = $this->db->select()
        ->from(array("tbl" => "webboard_post"), "")
        ->joinLeft(array('u' => "webboard_user"), "tbl.UserID = u.ID", "")
        ->joinLeft(array('a' => $selectAmount), "tbl.ID = a.PostID", "")
        ->columns(array(
            "tbl.ID",
            "tbl.Title",
            "tbl.ViewAmount",
            "tbl.IconID",
            "u.FirstName",
            "u.LastName",
            "tbl.Detail",
            "Amount" => "ifnull(a.Amount, 0)",
            "tbl.Status",
            "tbl.IsEnable",
            "tbl.Created",
            "tbl.CategoryID"
        ))
        ->where("tbl.IsEnable = 'Y'")
        ->where("tbl.IsDeleted = 'N'")
        ->where("tbl.UserID = '".$id."'")
        ;
        // echo($select);exit;
        // echo($select);exit;
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

        if (count($data) > 0) {
            for ($i = 0; $i < count($data); $i ++) {
                // $data[$i]['ID'] =  $no;
                $data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
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

function delete($params) {
    $data = new stdClass();
    $data->result = false;
    try {

        $modified = date("Y-m-d H:i:s");
        
        $form = array();
        $where = array();
        $where['ID = ? '] = $params['id'];
        $result = $this->db->delete("webboard_user",$where);
        
        $data->result = $result > 0;
        
    } catch (Exception $e) {
        $data->result = false;
        $data->message = $e->getMessage();
    }
    return $data;
}

function getDataByUserID($username,$password) {

	    $data = new stdClass();
	    try {
	        $select = $this->db->select()
            ->from(array("tbl" => "webboard_user"), "")
            // ->joinLeft(array("gd" => "webboard_category_detail"), "tbl.ID = gd.ID ", "")
            ->columns(array(
                "tbl.ID",
                "tbl.Username",
                "tbl.Password",
                "tbl.TitleName",
                "tbl.Email",
                "tbl.FirstName",
                "tbl.LastName",
                "tbl.LastLoginDate",
                "tbl.AttemptAmount",
                "tbl.IdentityCardID",
                "tbl.Address",
                "tbl.Telephone",
                "tbl.Gender",
                "tbl.IsEnable",
                "tbl.IsDeleted"

            ))
            ->where("tbl.Username = '".$username."'")
            ->where("tbl.Password = '".md5($password)."'")
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ;

	        $stmt = $this->db->query($select);
            $data = $stmt->fetchObject();
            
            // if (count($data) > 0) {
			// 	for ($i = 0; $i < count($data); $i ++) {
            //         // $data[$i]['ID'] =  $no;
            //         $data->LastLoginDate = empty($data->LastLoginDate) ? null : new DateTime($data->LastLoginDate);
			// 	}
            // }



	    } catch (Exception $e) {
	        
	    }
	    return $data;
    }

    function update($params) {

		$data = new stdClass();
		$data->result = false;
		try {

			$id = $params['userid'];
			$modified = date("Y-m-d H:i:s");

			$form = array();
            $where = array();
            
			$form['TitleName'] = $params['txtTitle'];
			$form['FirstName'] = $params['txtFirstName'];
            $form['LastName'] = $params['txtLastName'];
            $form['Telephone'] = $params['txtTelephone'];
            $form['Address'] = $params['txtAddress'];
            $form['ModifiedBy']= $id;
            $form['Modified']= $modified;
            $where['ID = ? '] = $id;
            // echo "<pre>";print_r($form);print_r($where);exit;
			$result = $this->db->update("webboard_user", $form, $where);

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
    
    public function getOption($lang) {
        $select = $this->db->select()
        ->from(array('tbl' => 'webboard_category_info'), '')
        ->joinLeft(array('d' => 'webboard_category_detail'),"tbl.ID = d.ID", '')
        ->columns(array(
            "ID" => "tbl.ID",
            "Lang" => "d.Lang",
            "Title" => "d.Title",
        ))
        ->where("tbl.IsDeleted = ?", "N")
        ->where("tbl.IsEnable = ?", "Y")
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

    function savepost($params) {
		$data = new stdClass();
		$data->result = false;
		try {

			$id = $params[''];
            $modified = date("Y-m-d H:i:s");

			$form = array();
            $where = array();

            $form['FileInfoID'] = $params['fileID'];
            $form['Title'] = $params['txtTitle'];
            $form['CategoryID'] = $params['Category'];
            $form['Detail'] = $params['Detail'];
            $form['IconID'] = $params['IconID'];
            $form['Created'] = $modified;

            $form['UserID']= $params['userid'];
            $form['ModifiedBy']= $params['userid'];
            $form['Status']= 'W';
			$form['IsEnable']= 'Y';
            $where['ID = ? '] = $id;
            
            $result = $this->db->insert("webboard_post", $form, $where);
            $id = $this->db->lastInsertId();

            $formupdate = array();
            $whereupdate = array();
			
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