<?php

class Calendar_Model_Comment {
	
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
			->from(array('tbl' => 'calendar_article_comment'), '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->columns(array(
					"ID" => "tbl.ID",
					"CalendarArticleID" => "tbl.CalendarArticleID",
					"MemberID" => "tbl.MemberID",
					"Lang" => "tbl.Lang",
					"AccessFrom" => "tbl.AccessFrom",
					"RemoteIP" => "tbl.RemoteIP",
					"GeoIP" => "tbl.GeoIP",
					"ActionDate" => "tbl.ActionDate",
					"Rating" => "tbl.Rating",
					"Message" => "tbl.Message",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"FullName" => "concat(ifnull(u.TitleName,''), ifnull(u.FirstName,''), ' ', ifnull(u.LastName,''))"
			))
			->where("tbl.IsDeleted ='N'")
			;
			
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}
			if (!empty($advancesearch['articleID'])) {
				$select = $select->where("tbl.CalendarArticleID = ? ", $advancesearch['articleID']);
			}
			
			$records = $this->getListCount($select);
			
			if (empty($sidx)) {
				$sidx = "ActionDate";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			//->order("$rowSort $sord")
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
					$where['CalendarArticleID = ? '] = $id;
					$where['ID = ? '] = $cID;
					$affRow += $this->db->update("calendar_article_comment", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
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
			
			$affRow = 0;
			if (count($params['ids']) > 0 && !empty($params['itemid'])) {
				$id = $params['itemid'];
				foreach ($params['ids'] as $cID) {
					$form = array();
					$where = array();
					$form['IsDeleted'] = 'Y';
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['CalendarArticleID = ? '] = $id;
					$where['ID = ? '] = $cID;
					$affRow += $this->db->update("calendar_article_comment", $form, $where);
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
