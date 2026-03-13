<?php

class Webboard_Model_Home {
    
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
                "tbl.FirstName",
                "tbl.LastName",
                "tbl.LastLoginDate",
                "tbl.AttemptAmount",
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
            
            if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
                    // $data[$i]['ID'] =  $no;
                    $data->LastLoginDate = empty($data->LastLoginDate) ? null : new DateTime($data->LastLoginDate);
				}
            }



	    } catch (Exception $e) {
	        
	    }
	    return $data;
    }

    function getCategory($id,$lang) {
	    $data = new stdClass();
	    try {
	        $select = $this->db->select()
            ->from(array("tbl" => "webboard_category_info"), "")
            ->joinLeft(array("gd" => "webboard_category_detail"), "tbl.ID = gd.ID ", "")
            ->columns(array(
                "tbl.ID",
                "gd.Title",
                "tbl.Created",
                "tbl.IsEnable",
            ))
            ->where("tbl.ID = '".$id."'")
            ->where("gd.Lang = '".$lang."'")
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ;
	        $stmt = $this->db->query($select);
            $data = $stmt->fetchObject();



	    } catch (Exception $e) {
	        
	    }
	    return $data;
    }


    function getTopic($iid) {

	    $data = new stdClass();
	    try {
	        $select = $this->db->select()
            ->from(array("tbl" => "webboard_post"), "")
            ->joinLeft(array("gd" => "webboard_user"), "tbl.UserID = gd.ID ", "")
            ->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
            ->columns(array(
                "tbl.Title",
                "gd.FirstName",
                "gd.LastName",
                "tbl.ViewAmount",
                "tbl.Detail",
                "tbl.Created",
                "tbl.IsEnable",

                "FileInfoID" => "tbl.FileInfoID",
                "FileInfoFileName" => "fi.Name",
                "FileInfoHashName" => "fi.HashName",
                "FileInfoFileType" => "fi.FileType",
                "FileInfoFileSize" => "fi.FileSize",
                "FileInfoFileExtension" => "fi.Extension",

            ))
            ->where("tbl.ID = ?", $iid)
            // ->where("gd.Lang = '".$lang."'")
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ;
            // echo($select);exit;

	        $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();
            
            
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
                    // $data[$i]['ID'] =  $no;
                    $data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
				}
            }

	    } catch (Exception $e) {
            // print_r($e);exit;
	       
	    }
	    return $data;
    }


    function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$id, $lang ) {

		try {

            $selectAmount = $this->db->select()->from(array('tbl' => "webboard_post"), '')
			// ->joinInner(array("a" => "guestbook_book_info"), "tbl.BookInfoID = a.ID" , "")
			->columns(array(
				"tbl.CategoryID",
				"Amount" => "count(distinct tbl.ID)"
				))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->group("tbl.CategoryID")
            ;

            $selectAmountView = $this->db->select()->from(array('tbl' => "webboard_post"), '')
			// ->joinInner(array("a" => "guestbook_book_info"), "tbl.BookInfoID = a.ID" , "")
			->columns(array(
				"tbl.CategoryID",
				"Amount" => "sum(tbl.ViewAmount)"
				))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->group("tbl.CategoryID")
            ;

            $selectReply = $this->db->select()->from(array('tbl' => "webboard_reply"), '')
			->joinInner(array("a" => "webboard_post"), "tbl.PostID = a.ID" , "")
			->columns(array(
				"a.CategoryID",
				"Amount" => "count(tbl.ID)"
				))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->group("a.CategoryID")
            ;

            // echo($selectReply);exit;


            $select = $this->db->select()
            ->from(array("tbl" => "webboard_category_info"), "")
            ->joinLeft(array("gd" => "webboard_category_detail"), "tbl.ID = gd.ID", "")
            ->joinLeft(array("us" => "sys_user"), "tbl.CreatedBy = us.ID", "")
            ->joinLeft(array('a' => $selectAmount), "tbl.ID = a.CategoryID", "")
            ->joinLeft(array('b' => $selectAmountView), "tbl.ID = b.CategoryID", "")
            ->joinLeft(array('c' => $selectReply), "tbl.ID = c.CategoryID", "")
            ->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
            ->columns(array(
				"tbl.ID",
                "gd.Lang",
                "gd.Detail",
                "us.FirstName",
                "Amount" => "ifnull(a.Amount, 0)",
                "AmountView" => "ifnull(b.Amount, 0)",
                "AmountReply" => "ifnull(c.Amount, 0)",
                "gd.Title",
                "tbl.IsEnable",

                "IntroImageID" => "tbl.IntroImageID",
                "IntroImageFileName" => "fi.Name",
                "IntroImageHashName" => "fi.HashName",
                "IntroImageFileType" => "fi.FileType",
                "IntroImageFileSize" => "fi.FileSize",
                "IntroImageFileExtension" => "fi.Extension",

                "tbl.Created"
            ))
            ->where("gd.Lang = '".$lang."'")
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ;

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
                "tbl.IsEnable",
                "tbl.Created"
            ))
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ->where("tbl.Status = 'Y'")
            ->where("tbl.CategoryID = '".$id."'")
            ;
            
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

    function getfilter($select) {
        try {
            $select = $this->db->select()
            ->from(array("tbl" => "webboard_filterword"), "")
            ->columns(array(
                "tbl.ID",
                "tbl.Detail",
                "tbl.Replace"
            ))
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ;

            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        }catch (Exception $e) {
			echo $e->getMessage();
        }

		return $data;
    }

    function count($iid) {
        try {
            $select = $this->db->select()
            ->from(array("tbl" => "webboard_reply"), "")
            ->columns(array(
                "tbl.ID"
            ))
            ->where("tbl.Status = 'Y'")
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ->where("tbl.PostID = '".$iid."'")
            ;

            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

        }catch (Exception $e) {
			echo $e->getMessage();
        }

		return $data;
    }

    function savereply($params,$userid) {
        $modified = date("Y-m-d H:i:s");
		$data = new stdClass();
		$data->result = false;
		try {

            $id ='';
			$form = array();
			$form['PostID'] = $params['iid'];
            $form['Detail'] = $params['Detail'];
            $form['Status'] = 'W';
            $form['Created'] = $modified;
            $form['UserID'] = $userid;
			$form['CreatedBy']= $userid;
			$form['IsEnable']= 'N';
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("webboard_reply", $form);
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

    function getListReply($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch,$iid, $lang ) {

		try {

            // $selectAmount = $this->db->select()->from(array('tbl' => "webboard_reply"), '')
			// // ->joinInner(array("a" => "guestbook_book_info"), "tbl.BookInfoID = a.ID" , "")
			// ->columns(array(
			// 	"tbl.PostID",
			// 	"Amount" => "count(distinct tbl.ID)"
			// 	))
			// ->where("tbl.IsDeleted = 'N' ")
			// ->where("tbl.IsDeleted = 'N' ")
			// ->group("tbl.PostID")
            // ;
            
            
            $select = $this->db->select()
            ->from(array("tbl" => "webboard_reply"), "")
            ->joinLeft(array('u' => "webboard_user"), "tbl.UserID = u.ID", "")
            // ->joinLeft(array('a' => $selectAmount), "tbl.ID = a.PostID", "")
            ->columns(array(
                "tbl.ID",
                "tbl.PostID",
                "tbl.Detail",
                "u.FirstName",
                "u.LastName",
                "tbl.Status",
                "tbl.IsEnable",
                "tbl.Created"
            ))
            ->where("tbl.Status = 'Y'")
            ->where("tbl.IsEnable = 'Y'")
            ->where("tbl.IsDeleted = 'N'")
            ->where("tbl.PostID = '".$iid."'")
            ;
            
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

            // $no = $records - (($page-1)*10);
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
                    $data[$i]['ID'] =  $i+1;
                    $data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
                    // $no-- ;
				}
            }

            // if (count($data) > 0) {
			// 	for ($i = 0; $i < count($data); $i ++) {
			// 		$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
			// 	}
            // }
            
		} catch (Exception $e) {
            // print_r($e);exit;
			echo $e->getMessage();
        }

		return $data;
    }	

    public static function saveView($iid,$userID) {
		$data = new stdClass();
		$data->result = false;
		try {
			$db = Zend_Db_Table::getDefaultAdapter();
			$request = Zend_Controller_Front::getInstance()->getRequest();
			
			$created = date("Y-m-d H:i:s");
			$form = array();

            //$form["ActionDate"] = $created;
            $form["PostID"] = $iid;
			$form["Created"] = $created;
			if (!empty($userID)) {
                $form["CreatedBy"] = $userID;
                $form["UserID"] = $userID;
            }	
            		
			$affectedRows = $db->insert("webboard_post_view", $form);
			
			if ($affectedRows) {
				$select = $db->select()->from(array("tbl" => "webboard_post_view"), array("TotalView" => "count(tbl.ID)"))
				->where("PostID = ?", $iid)
				;
				$stmt = $db->query($select);
				$dataTotal = $stmt->fetchObject();
				
				$formSummary = array();
				$whereSummary = array();
				$formSummary["ViewAmount"] = $dataTotal->TotalView;
				$whereSummary["ID = ?"] = $iid;
				$affectedRows = $db->update("webboard_post", $formSummary, $whereSummary);
			}
			$data->result = true;
		} catch (Exception $e) {

		}
		return $data;
	}


}

?>
