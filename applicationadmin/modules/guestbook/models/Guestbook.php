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
    
    function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
        $data = array();

        try {
            $select = $this->db->select()
            ->from(array("tbl" => "guestbook_message") , "")
            ->joinLeft(array('d' => 'guestbook_book_info'),"tbl.BookInfoID = d.ID and tbl.Lang = '{$lang}' ", '')
            ->joinLeft(array('td' => 'guestbook_book_detail'),"td.ID = d.ID and td.Lang = '{$lang}' ", '')
            ->columns(array(
                "tbl.ID",
                "tbl.TitleName",
                "tbl.FirstName",
                "tbl.LastName",
                "tbl.Detail",
                "tbl.DepartmentName",
                "tbl.Created",
                "tbl.IsEnable",

                "td.Title"
            ))
            ->where("tbl.IsDeleted = 'N'")
            ->where("d.IsDeleted = 'N'")
            ;
            
            // set criteria
            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where("tbl.Detail like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->orwhere("tbl.DepartmentName like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->orwhere("td.Title like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }
            
            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.IsEnable = ?", $advancesearch['status']);
            }

            if (!empty($advancesearch['book'])) {
                $select = $select->where("tbl.BookInfoID = ?", $advancesearch['book']);
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

    function getData($ID,$lang='th') {
	    $data = new stdClass();
	    try {
	        $select = $this->db->select()
            ->from(array("tbl" => "guestbook_message"), "*")
            ->joinLeft(array('d' => 'guestbook_book_info'),"tbl.BookInfoID = d.ID and tbl.Lang = '{$lang}' ", '')
            ->where("tbl.ID = ?", $ID)  
            ->where("d.IsDeleted = 'N'")
            ;
            
	        $stmt = $this->db->query($select);  
            $data = $stmt->fetchObject();

            // print_r($data);exit;
            
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
        ->from(array('tbl' => 'guestbook_book_info'), '')
        ->joinLeft(array('d' => 'guestbook_book_detail'),"tbl.ID = d.ID", '')
        ->columns(array(
            "ID" => "tbl.ID",
            "Lang" => "d.Lang",
            "Title" => "d.Title",
        ))
        ->where("tbl.IsDeleted = ?", "N")
        // ->where("tbl.IsEnable = ?", "Y")
        ->where("d.Lang = ?", $lang)
        ->order("convert(d.Title USING TIS620)  ASC")
        ;
        // echo $select;exit;
        $stmt = $this->db->query($select);
        $data = $stmt->fetchAll();
        $result = array();
        
        foreach ($data as $value) {
            $result[$value["ID"]] = $value["Title"];
        }
        return $result;
    }

    public function getOptionMenu($lang) {
        $select = $this->db->select()
        ->from(array('tbl' => 'guestbook_book_info'), '')
        ->joinLeft(array('d' => 'guestbook_book_detail'),"tbl.ID = d.ID", '')
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
            
            $params['Date'] =  Emwork_Utility_DateTime::dateThaiToMySQL( $params['Date']);
            $params['Date'] .= empty($params['Time']) ? " 0:00" : " " . str_replace(" : ", ":", $params['Time']);

            // print_r($params);exit;

			$form = array();
            $where = array();
            $form['TitleName'] = $params['TitleName'];
            $form['FirstName'] = $params['FirstName'];
            $form['LastName'] = $params['LastName'];
            $form['BookInfoID'] = $params['Book'];
            $form['Detail'] = $params['Detail'];
            $form['DepartmentName'] = $params['DepartmentName'];
            $form['Created'] = $params['Date'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $params['IsStatus'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("guestbook_message", $form, $where);
										
			
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
			$result = $this->db->update("guestbook_message", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
    }
    
    function getListExcel($advancesearch, $lang = 'th') {
        $data = array();

        try {
            $select = $this->db->select()
            ->from(array("tbl" => "guestbook_message") , "")
            ->joinLeft(array("gd" => "guestbook_book_info"), "gd.ID = tbl.BookInfoID and tbl.Lang ='{$lang}'  ", "")
            ->joinLeft(array("d" => "guestbook_book_detail"), "gd.ID = d.ID and d.Lang ='{$lang}'  ", "")
            ->columns(array(
                "tbl.ID",
                "tbl.TitleName",
                "tbl.FirstName",
                "tbl.LastName",
                "d.Title",
                "tbl.Detail",
                "tbl.DepartmentName",
                "tbl.Created",
                "tbl.IsEnable",
            ))
            ->where("tbl.IsDeleted = 'N'")
            ->where("gd.IsDeleted = 'N'")
            ;
            
            // set criteria
            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where("tbl.Detail like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->orwhere("tbl.DepartmentName like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->orwhere("d.Title like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }

            if (!empty($advancesearch['book'])) {
                $select = $select->where("tbl.BookInfoID = ?", $advancesearch['book']);
            }
                    
            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.IsEnable = ?", $advancesearch['status']);
            }
            
            if (!empty($advancesearch['sendfrom'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfrom']);
                $select = $select->where("tbl.Created >= ?", $value);
            }

            if (!empty($advancesearch['sendto'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendto']);
                $select = $select->where("tbl.Created <= ?", $value . " 23:59:59");
            }
            
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
					$affRow += $this->db->update("guestbook_message", $form, $where);
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
					->from(array('tbl'=>'guestbook_message'), array(
                        'TitleName',
                        'FirstName',
                        'LastName',
                        'Lang',
                        'BookInfoID',
                        'Detail',
                        'DepartmentName'
				      ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['TitleName'] = empty($dataInfo->TitleName) ? null : $dataInfo->TitleName;
					$form['FirstName'] = empty($dataInfo->FirstName) ? null : $dataInfo->FirstName;
					$form['LastName'] = empty($dataInfo->LastName) ? null : $dataInfo->LastName;
					$form['Lang'] = empty($dataInfo->Lang) ? null : $dataInfo->Lang;
					$form['BookInfoID'] = empty($dataInfo->BookInfoID) ? null : $dataInfo->BookInfoID;
					$form['Detail'] = empty($dataInfo->Detail) ? null : ($dataInfo->Detail.' (Copy)');
					$form['DepartmentName'] = empty($dataInfo->DepartmentName) ? null : $dataInfo->DepartmentName;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("guestbook_message", $form);

				} // End Foreach
			} // End Count
			
		} catch (Exception $e) {
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
					$affRow += $this->db->update("guestbook_message", $form, $where);
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
