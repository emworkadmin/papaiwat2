<?php

class Webboard_Model_Filter {
    
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
            ->from(array("tbl" => "webboard_filterword"), "")
            ->columns(array(
				"tbl.ID",
				"tbl.Detail",
				"tbl.Replace",
                "tbl.IsEnable",
            ))
            ->where("tbl.IsDeleted = 'N'")
			;
			
            // set criteria
            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where("tbl.Detail like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
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

    function save($params) {

        $modified = date("Y-m-d H:i:s");
		$data = new stdClass();
		$data->result = false;
		try {

			$id = '';
			$form = array();
			$form['Replace'] = $params['Replace'];
			$form['Detail'] = $params['Detail'];
			$form['Created'] = $modified;
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $params['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("webboard_filterword", $form);
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
    
    function getInfo($id) {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'webboard_category_info'), '')
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
    
    function getData($id) {

		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'webboard_filterword'), '')
			// ->joinLeft(array('d' => 'webboard_category_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"tbl.ID",
					"Replace" => "tbl.Replace",
					"Detail" => "tbl.Detail",
					"tbl.IsEnable"
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
			$form['Replace'] = $params['Replace'];
			$form['Detail'] = $params['Detail'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $params['IsEnable'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("webboard_filterword", $form, $where);
						
			
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
			$result = $this->db->update("webboard_filterword", $form, $where);
			
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
					$affRow += $this->db->update("webboard_filterword", $form, $where);
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
					->from(array('tbl'=>'webboard_filterword'), array(
						'Replace',
						'Detail'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['Replace'] = empty($dataInfo->Replace) ? null : $dataInfo->Replace;
					$form['Detail'] = empty($dataInfo->Detail) ? null : $dataInfo->Detail;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("webboard_filterword", $form);

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
					$affRow += $this->db->update("webboard_filterword", $form, $where);
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
