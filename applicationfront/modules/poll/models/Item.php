<?php

class Poll_Model_Item {
	
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

	function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'poll_item_info'), '')
			->joinLeft(array('d' => 'poll_item_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array('c' => 'poll_category_info'),"tbl.PollCategoryID = c.ID", '')
			->joinLeft(array('cd' => 'poll_category_detail'),"c.ID = cd.ID and cd.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"PollCategoryID" => "tbl.PollCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"OneUserPerVote" => "tbl.OneUserPerVote",
					"OneIPPerVote" => "tbl.OneIPPerVote",
					"VoteDelay" => "tbl.VoteDelay",
					"ShowIntroText" => "tbl.ShowIntroText",
					"ResultDisplay" => "tbl.ResultDisplay",
					"ResultPieSetting" => "tbl.ResultPieSetting",
					"ResultBarSetting" => "tbl.ResultBarSetting",
					"StartDate" => "tbl.StartDate",
					"EndDate" => "tbl.EndDate",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",

					"Lang" => "d.Lang",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",

					"CanVote"=>"(case when NOW() BETWEEN tbl.StartDate AND tbl.EndDate then 1 else 0 end)",
					"EndDiff"=>"(TIMESTAMPDIFF(MINUTE, NOW(), tbl.EndDate))",
					
					"PollCategoryTitle"=>"cd.Title"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->StartDate = empty($data->StartDate) ? null : new DateTime($data->StartDate);
				$data->EndDate = empty($data->EndDate) ? null : new DateTime($data->EndDate);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getQuestionListByLanguage($PollItemID='-1', $lang='th') {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'poll_question_info'), '')
			->joinLeft(array('d' => 'poll_question_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"Question" => "d.Question",
					"Description" => "d.Description"
			))
			->where("d.Description IS NOT NULL ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollItemID = ? ", $PollItemID)
			->order("tbl.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getOptionListByLanguage($PollQuestionID='-1', $lang='th') {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'poll_option_info'), '')
			->joinLeft(array('d' => 'poll_option_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"Name" => "d.Name"
			))
			->where("d.Name IS NOT NULL ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollQuestionID = ? ", $PollQuestionID)
			->order("tbl.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function save($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$request = Zend_Controller_Front::getInstance()->getRequest();
			$PollItemID = $params['hdfID'];

			if(count($params['Answers'])>0){

				$form = array();
				$form['PollItemID'] = $PollItemID;
				$form['UserID'] = empty($this->UserID) ? null : $this->UserID;
				$form['AnswerType'] = 'A';
				$form['ActionDate'] = date('Y-m-d H:i:s');
				$form['RemoteIP'] = $request->getServer('REMOTE_ADDR');
				$form['Created'] = date('Y-m-d H:i:s');
				$form['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
				$result = $this->db->insert("poll_submission_info", $form);
				$PollSubmissionID = $this->db->lastInsertId();

				foreach($params['Answers'] as $k=>$v){

					$formValue = array();
					$formValue['PollSubmissionID'] = $PollSubmissionID;
					$formValue['PollQuestionID'] = $k;
					$formValue['PollOptionID'] = $v;
					$formValue['Created'] = date('Y-m-d H:i:s');
					$formValue['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
					$this->db->insert("poll_submission_values", $formValue);

				}
			}
				
			if($result){
				$data->result = true;
				$data->id = $PollItemID;
			}
				
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function getLastSubmission($PollItemID, $RemoteIP=null, $OneUserPerVote=false) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->columns(array(
				"tbl.ID",
				"tbl.PollItemID",
				"tbl.UserID",
				"tbl.ActionDate",
				"tbl.RemoteIP",
				"ActionDiffHour" => "HOUR(TIMEDIFF(tbl.ActionDate,NOW()))"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollItemID = ? ", $PollItemID)
			->order("tbl.Created DESC")
			;
			if($RemoteIP && !$OneUserPerVote){
				$select = $select->where('tbl.RemoteIP = ? ', $RemoteIP);
			}
			if($OneUserPerVote && !$RemoteIP){
				$select = $select->where('tbl.UserID = ? ', (empty($this->UserID) ? '-1' : $this->UserID));
			}
			if($OneUserPerVote && $RemoteIP){
				$select = $select->where('tbl.UserID = ' . (empty($this->UserID) ? '-1' : $this->UserID) . ' OR ' . 'tbl.RemoteIP = "' . $RemoteIP . '"');
			}
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getSubmissionList($PollItemID) {
		$data = new stdClass();
		try {

			$selectAmount  = $this->db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->joinLeft(array('v' => 'poll_submission_values'),"tbl.ID = v.PollSubmissionID", '')
			->columns(array(
					"v.PollQuestionID",
					"Amount" => "count(tbl.PollItemID)"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->group("v.PollQuestionID")
			;

			$select = $this->db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->joinLeft(array('v' => 'poll_submission_values'),"tbl.ID = v.PollSubmissionID", '')
			->joinLeft(array('a' => $selectAmount),"v.PollQuestionID = a.PollQuestionID", '')
			->columns(array(
					"v.PollQuestionID",
					"v.PollOptionID",
					"Amount" => "count(tbl.PollItemID)",
					"Percentage" => "concat(round(( COUNT(tbl.PollItemID)/a.amount * 100 ),2),'%')"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollItemID = ? ", $PollItemID)
			->group("v.PollQuestionID")
			->group("v.PollOptionID")
			->group("a.amount")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
}

?>
