<?php

class Webboard_Model_Post {
    
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
    
    function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
        $data = array();

        try {
            $select = $this->db->select()
            ->from(array("tbl" => "webboard_post") , "")
            ->joinLeft(array("gd" => "webboard_category_detail"), "tbl.CategoryID = gd.ID ", "")
            ->joinLeft(array("us" => "webboard_user"), "tbl.UserID = us.ID ", "")
            ->joinLeft(array("su" => "sys_user"), "tbl.UserID = su.ID", "")
            ->columns(array(
                "tbl.ID",
                "TitleName" => "ifnull(us.TitleName, su.TitleName)",
                "FirstName" => "ifnull(us.FirstName, su.FirstName)",
                "LastName" => "ifnull(us.LastName, su.LastName)",
                "tbl.Title",
                "TitleCategory" => "gd.Title",
                "tbl.Status",
                "tbl.Created",
                "tbl.IsEnable",
            ))
            ->where("tbl.IsDeleted = 'N'")
            ->where("gd.Lang = '".$lang."'")
            ;
            
            // set criteria

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where("tbl.Title like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }   
            
            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.IsEnable = ?", $advancesearch['status']);
            }

            if (!empty($advancesearch['statuspost'])) {
                $select = $select->where("tbl.Status = ?", $advancesearch['statuspost']);
            }

            if (!empty($advancesearch['category'])) {
                $select = $select->where("tbl.CategoryID = ?", $advancesearch['category']);
            }
            
            if (!empty($advancesearch['sendfrom'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfrom']);
                $select = $select->where("tbl.Created >= ?", $value);
            }

            if (!empty($advancesearch['sendto'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendto']);
                $select = $select->where("tbl.Created <= ?", $value . " 23:59:59");
            }


            // echo($advancesearch['sendfrom']);
            // echo $select ;
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

            if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
                    $data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
                    $data[$i]["FullName"] = $data[$i]["TitleName"]." ".$data[$i]["FirstName"]." ".$data[$i]["LastName"];
				}
            }

            
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

    function getData($ID) {
	    $data = new stdClass();
	    try {
	        $select = $this->db->select()
            ->from(array("tbl" => "webboard_post"), "*")
            ->joinLeft(array("us" => "webboard_user"), "tbl.UserID = us.ID ", "")
            ->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
            ->columns(array(
                "us.TitleName",
                "us.FirstName",
                "us.LastName",

                "IntroImageFileName" => "fi.Name",
                "IntroImageHashName" => "fi.HashName",
                "IntroImageFileType" => "fi.FileType",
                "IntroImageFileSize" => "fi.FileSize",
                "IntroImageFileExtension" => "fi.Extension",

            ))
	        ->where("tbl.ID = ?", $ID)
	        ;
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

    function save($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
            }
            
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
            $form['FileInfoID'] = $params['fileID'];
            $form['Title'] = $params['Title'];
            $form['CategoryID'] = $params['Category'];
            $form['Detail'] = $params['Detail'];
            $form['IconID'] = $params['IconID'];
            $form['Created'] = $modified;

            // $form['Modified'] = $modified;
            $form['UserID']= $this->UserID;
            $form['ModifiedBy']= $this->UserID;
            $form['Status']= $params['IsStatusPost'];
			$form['IsEnable']= $params['IsStatus'];
            $result = $this->db->insert("webboard_post", $form);
            $id = $this->db->lastInsertId();

            $formupdate = array();
            $whereupdate = array();
            
            if($params['IsStatusPost'] == 'Y'){
                $formupdate['Modified']= $modified;
                $whereupdate['ID = ? '] = $params['Category'];
                $result = $this->db->update("webboard_category_info", $formupdate, $whereupdate);
            }
										
			
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
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = $params['hdfID'];
            $modified = date("Y-m-d H:i:s");
			$form = array();
            $where = array();
            $form['FileInfoID'] = $params['fileID'];
            $form['Title'] = $params['Title'];
            $form['CategoryID'] = $params['Category'];
            $form['Detail'] = $params['Detail'];
            $form['IconID'] = $params['IconID'];
			$form['Modified'] = $modified;
            $form['ModifiedBy']= $this->UserID;
            $form['Status']= $params['IsStatusPost'];
			$form['IsEnable']= $params['IsStatus'];
			$where['ID = ? '] = $id;
            $result = $this->db->update("webboard_post", $form, $where);
            
            $formupdate = array();
            $whereupdate = array();

            if($params['IsStatusPost'] == 'Y'){
            $formupdate['Modified']= $modified;
			$whereupdate['ID = ? '] = $params['Category'];
            $result = $this->db->update("webboard_category_info", $formupdate, $whereupdate);
            }
										
			
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

	function delete($params) {
		$data = new stdClass();
        $data->result = false;
        
		try {

			$modified = date("Y-m-d H:i:s");
			
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("webboard_post", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
    }

    function listPublish($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			$status = $params['status'];
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {
					$form = array ();
					$where = array ();
					$form['IsEnable'] = empty($status) ? 'N' : $status;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['ID = ? '] = $id;
					$affRow += $this->db->update("webboard_post", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function listCopy($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			$defaultLanguage = $params['defaultLanguage'];
			
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {

					$selectInfo = $this->db->select ()
					->from(array('tbl'=>'webboard_post'), array(
						'UserID',
						'Title',
                        'ViewAmount',
                        'IconID',
                        'FileInfoID',
                        'CategoryID',
                        'Status',
                        'Detail'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['UserID'] = empty($dataInfo->UserID) ? null : $dataInfo->UserID;
					$form['Title'] = empty($dataInfo->Title) ? null : ($dataInfo->Title.' (Copy)');
					$form['ViewAmount'] = 0;
					$form['IconID'] = empty($dataInfo->IconID) ? null : $dataInfo->IconID;
					$form['FileInfoID'] = empty($dataInfo->FileInfoID) ? null : $dataInfo->FileInfoID;
					$form['CategoryID'] = empty($dataInfo->CategoryID) ? null : $dataInfo->CategoryID;
					$form['Status'] = empty($dataInfo->Status) ? null : $dataInfo->Status;
					$form['Detail'] = empty($dataInfo->Detail) ? null : $dataInfo->Detail;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
                    $form['IsEnable'] = 'N';
					$data->result = $this->db->insert("webboard_post", $form);

				} // End Foreach
			} // End Count
			
		} catch (Exception $e) {
            print_r($e);exit;
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function listDelete($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {
					$form = array ();
					$where = array ();
					$form ['IsDeleted'] = 'Y';
					$form ['Modified'] = $modified;
					$form ['ModifiedBy'] = $this->UserID;
					$where ['ID = ? '] = $id;
					$affRow += $this->db->update("webboard_post", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

}

?>
