<?php

class Guestbook_Model_Book {
    
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

			$selectAmount = $this->db->select()->from(array('tbl' => "guestbook_message"), '')
			// ->joinInner(array("a" => "guestbook_book_info"), "tbl.BookInfoID = a.ID" , "")
			->columns(array(
				"tbl.BookInfoID",
				"Amount" => "count(distinct tbl.ID)"
				))
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.IsDeleted = 'N' ")
			->group("tbl.BookInfoID")
			;

            $select = $this->db->select()
            ->from(array("tbl" => "guestbook_book_info"), "")
			->joinLeft(array("gd" => "guestbook_book_detail"), "tbl.ID = gd.ID", "")
			->joinLeft(array('a' => $selectAmount), "tbl.ID = a.BookInfoID", "")
            ->columns(array(
				"tbl.ID",
				"Amount" => "ifnull(a.Amount, 0)",
                "gd.Lang",
                "gd.Title",
                "tbl.IsEnable",
            ))
            ->where("gd.Lang = '".$lang."'")
            ->where("tbl.IsDeleted = 'N'")
			;
			
            // set criteria
            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where("tbl.Title like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }

            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.IsEnable = ?", $advancesearch['status']);
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

    public function getRecursiveOption($lang, $id = null) {
		$select = $this->db->select()
		->from ( array ('tbs' => 'guestbook_book_info' ), array ('ID') )
		->joinInner ( array ('d' => 'guestbook_book_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
        ->order ( "convert(d.Title USING TIS620)  ASC" );
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
            $result[$value["ID"]] = $value["Title"];
		}
		return $result;

    }

    function save($params) {

        $modified = date("Y-m-d H:i:s");
		$data = new stdClass();
		$data->result = false;
		try {
			
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
            $param = $params['params'];
            $id = '';
			$id = $this->db->lastInsertId();
			$form = array();
			$form['ID'] = $id;
			$form['Created'] = $modified;
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("guestbook_book_info", $form);
			$id = $this->db->lastInsertId();

			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Title'] = $value['Title'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("guestbook_book_detail", $formDetail);
				}
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
    
    function getInfo($id) {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'guestbook_book_info'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"IsEnable" => "tbl.IsEnable",
			))
			->where("tbl.ID = ? " , $id)
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();

		} catch (Exception $e) {
			
		}
		return $data;
    }
    
    function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'guestbook_book_info'), '')
			->joinLeft(array('d' => 'guestbook_book_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"Lang" => "d.Lang",
					"Title" => "d.Title",
			))
			->where("tbl.ID = ? " ,$id)
            ;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();

		} catch (Exception $e) {
			
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
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("guestbook_book_info", $form, $where);
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $value['Title'];
					$formDetail['Modified'] = $modified;
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("guestbook_book_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("guestbook_book_detail", $formDetail);
					}
				}
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
			$result = $this->db->update("guestbook_book_info", $form, $where);
			
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
					$affRow += $this->db->update("guestbook_book_info", $form, $where);
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

					// $selectInfo = $this->db->select ()
					// ->from(array('tbl'=>'guestbook_book_info'), array('ID'))
					// ->where("tbl.ID = ? ", $id)
					// ;
					// $stmtInfo = $this->db->query($selectInfo);
					// $dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("guestbook_book_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'guestbook_book_detail'), array('Lang','Title'))
						->where("tbl.ID = ? ", $id)
						;
						$stmtDetail = $this->db->query($selectDetail);
						$dataDetail = $stmtDetail->fetchAll();

						if(count($dataDetail)>0){
							foreach($dataDetail as $d){
								$form = array();
								$form['ID'] = $tmpID;
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Title'] = empty($d['Title']) ? null : $d['Title'];
								if($defaultLanguage == $d['Lang']){
									$form['Title'] = $form['Title'] . ' (Copy)';
								}
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("guestbook_book_detail", $form);
							}
						}
					}

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
					$affRow += $this->db->update("guestbook_book_info", $form, $where);
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
