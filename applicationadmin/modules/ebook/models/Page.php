<?php

class Ebook_Model_Page {
	
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
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch) {
		$data = array();
		
		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'ebook_page_info'), '')
			->columns(array(
				"ID" => "tbl.ID",
				"Title" => "tbl.Title",
				"PageIndex" => "tbl.PageIndex",
				"Created" => "tbl.Created",
				"Modified" => "tbl.Modified",
				"IsEnable" => "tbl.IsEnable",
					
			))
			->where("tbl.IsDeleted ='N'")
			;
			
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}
			if (!empty($advancesearch['bookID'])) {
				$select = $select->where("tbl.EbookBookID = ? ", $advancesearch['bookID']);
			}
			
			$records = $this->getListCount($select);
			
			if (empty($sidx)) {
				$sidx = "ActionDate";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->limitPage($page, $rows)
			;
			if (in_array($rowSort, array('Message'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["ActionDate"] = empty($data[$i]["ActionDate"]) ? null : new DateTime($data[$i]["ActionDate"]);
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
		} catch (Exception $e) {

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
	
	function updateStatus($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$affRow = 0;
			if (count($params['ids']) > 0 && !empty($params['itemid'])) {
				$id = $params['itemid'];
				$status = $params['status'];
				foreach ($params['ids'] as $cID) {
					$form = array();
					$where = array();
					$form['IsEnable'] = $status;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['EbookBookID = ? '] = $id;
					$where['ID = ? '] = $cID;
					$affRow += $this->db->update("ebook_page_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function deletebook($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$affRow = 0;
			if (count($params['ids']) > 0 && !empty($params['itemid'])) {
				$id = $params['itemid'];
				foreach ($params['ids'] as $cID) {
					$form = array();
					$where = array();
					$form['IsDeleted'] = 'Y';
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['EbookBookID = ? '] = $id;
					$where['ID = ? '] = $cID;
					$affRow += $this->db->update("ebook_page_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
    }
    
    function save($params) {
		$data = new stdClass ();
		$data->result = false;
		try {

			$select = $this->db->select()
			->from(array('tbl' => 'ebook_page_info'), '')
			->columns(array(
					"PageIndex" => "tbl.PageIndex",				
			))
			->where("tbl.IsDeleted ='N'")
			->where("tbl.EbookBookID = ? " , $params['itemid'])
			->order('tbl.PageIndex DESC')
			->limit(1);
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			$index = intval($data->PageIndex)+1;
			
			$id = '';
			$param = $params['params'];			
			$modified = date("Y-m-d H:i:s");

			$form = array ();
			$form ['FileInfoID'] = $params['BookImageID'];
            $form ['EbookBookID'] = $params['itemid'];
            $form ['PageIndex'] = $index;
            $form ['Title'] = $params['Title'] . ' ' . $index;
			$form ['Created'] = $modified;
			$form ['CreatedBy'] = $this->UserID;
			$form ['IsEnable'] = 'Y';
			$form ['IsDeleted'] = 'N';
			$result = $this->db->insert ( "ebook_page_info", $form);
			$id = $this->db->lastInsertId ();
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
            }
            
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}

	function update($params)
	{
		$data = new stdClass ();
		$data->result = false;

		try {

			$modified = date("Y-m-d H:i:s");

			$form = array ();
			$where = array ();
			
			$form['PageIndex'] = $params['PageIndex'];
			$form['FileInfoID'] = $params['BookImageID'];
            $form['Title'] = $params['Title'];
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
			$where['ID = ?'] = $params['hdfID'];
			$result = $this->db->update( "ebook_page_info", $form, $where);
			if($result){
				$data->result = true;	
				$data->id = $params['hdfID'];
			}
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}

	function savepdf($params) {

		$data = new stdClass ();
		$data->result = false;
		try {

			$select = $this->db->select()
			->from(array('tbl' => 'ebook_page_info'), '')
			->columns(array(
					"PageIndex" => "tbl.PageIndex",				
			))
			->where("tbl.IsDeleted ='N'")
			->where("tbl.EbookBookID = ? " , $params['itemid'])
			->where("EbookBookID = ? " , $params['itemid'])
			->order('tbl.PageIndex DESC')
			->limit(1);
			;

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			$index = intval($data->PageIndex)+1;
						
			$id = '';
			$param = $params['params'];			
			$modified = date("Y-m-d H:i:s");
			
			$form = array ();
			$form ['EbookBookID'] = $params['itemid'];
            $form ['PageIndex'] = $index;
            $form ['FileInfoID'] = $params['ImagepdfID'];
            $form ['Title'] = $params['Title'] . ' ' . $index;
			$form ['Created'] = $modified;
			$form ['CreatedBy'] = $this->UserID;
			$form ['IsEnable'] = 'Y';
			$form ['IsDeleted'] = 'N';
			$result = $this->db->insert ( "ebook_page_info", $form);
			$id = $this->db->lastInsertId ();
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
            }
            
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}

	public function getfilepdf($id) {
		$select = $this->db->select()
		->from ( array ('tbs' => 'file_info' ), array ('HashName','Path'))
		->where ( "tbs.ID = ?", $id )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		;
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchObject ();
		return $data;
	}

	function getBookPage($id)
	{
		$select = $this->db->select()
		->from(array("tbl" => "ebook_page_info"), "")
		->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID" ,"")
		->columns(array(
			"tbl.ID",
			"tbl.Title",
			"tbl.PageIndex",

			"FileID" => "fi.ID",
			"FileName" => "fi.Name",
			"HashName" => "fi.HashName",
			"FileType" => "fi.FileType",
			"FileSize" => "fi.FileSize"
		))
		->where("tbl.ID = ?", $id)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data;
	}

}

?>
