<?php

class Faq_Model_Question {
	
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
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {

			$select = $this->db->select()
			->from(array('tbl' => 'faq_question'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"FullName" => "tbl.FullName",
					"Question" => "tbl.Question",
					"QuestionDate" => "tbl.QuestionDate",
					"Status" => "tbl.Status"
			))
			->where("tbl.IsDeleted ='N'")
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("(tbl.FullName like " . $this->db->quote("%" . $val . "%" ) . " OR tbl.Email like " . $this->db->quote("%" . $val . "%" ) . " OR tbl.Question like " . $this->db->quote("%" . $val . "%" ) . ")");
			}
			if (!empty($advancesearch['status'])) {
				$select = $select->where("tbl.Status = ? ", $advancesearch['status']);
			}
			
			$records = $this->getListCount($select);
			
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->limitPage($page, $rows)
			;
			if (in_array($rowSort, array('FullName','Email','Question'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
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
	
	function getInfo($id) {
		$data = new stdClass();
		try {
			$AnswerName = "concat(ifnull(u.TitleName,''), ifnull(u.FirstName,''), ' ', ifnull(u.LastName,''))";

			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'faq_question'), '')
			->joinLeft(array('u' => 'sys_user') , "tbl.AnswerBy = u.ID" , '')
			->columns(array(
					"ID" => "tbl.ID",
					"Lang" => "tbl.Lang",
					"FullName" => "tbl.FullName",
					"Email" => "tbl.Email",
					"Question" => "tbl.Question",
					"QuestionDate" => "tbl.QuestionDate",
					"QuestionBy" => "tbl.QuestionBy",
					"Status" => "tbl.Status",
					"Answer" => "tbl.Answer",
					"AnswerDate" => "tbl.AnswerDate",
					"AnswerBy" => "tbl.AnswerBy",
					"AnswerName" => $AnswerName,

					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
			))
			->where("tbl.ID = ? " , $id)
			->where("tbl.IsDeleted ='N'")
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function update($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$id = $params['hdfID'];

			$form = array();
			$where = array();
			$form['Status'] = $params['Status'];
			$form['Answer'] = $params['Answer'];
			if($params['Status'] == 'A'){
				$form['AnswerDate'] = date("Y-m-d H:i:s");
				$form['AnswerBy'] = $this->UserID;
			}
			$form['Modified'] = date("Y-m-d H:i:s");
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $params['IsEnable'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("faq_question", $form, $where);
			
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
			$result = $this->db->update("faq_question", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
}
?>
