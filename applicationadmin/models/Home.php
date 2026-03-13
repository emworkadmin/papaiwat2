<?php
Class Home {

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

	function getvisiter()
	{
		$data = new stdClass();
		$data->VisitorBeginVisitor = System_Model_Setting::getValue("VisitorBeginVisitor", "Visitor");
		$data->VisitorSiteOpenDate = System_Model_Setting::getValue("VisitorSiteOpenDate", "Visitor");
		$startdate = $data->VisitorSiteOpenDate;
		$BeginAmount = $data->VisitorBeginVisitor;

		$select = $this->db->select()
		->from(array('tbl' => 'log_access'), "")
		->columns(array(
			"Amount" => " COUNT(tbl.ID)",
		))
		->where("tbl.SiteType = 'F' ")
		->where("tbl.IsAjax = 'N' ")
		->order("tbl.created ASC")
		;

		if($startdate) {
			$select = $select->where("DATE_FORMAT(tbl.created,'%Y%m') >= ?",$startdate);
		}

		$stmt = $this->db->query($select);	
		$data = $stmt->fetchObject();
		
		$data = $data->Amount + $BeginAmount;

		return $data;
	}

    function getuseronline($option = array())
    {
		try {

			$date = date("Y-m-d H:i:s", strtotime('-5 minutes'));

			$select = $this->db->select()
			->from(array("tbl" => "log_access"), "")
			->columns(array(
				"Amount" => "count(distinct tbl.SessionID)"
			))
			->where("tbl.SiteType = 'F'")
			// ->where("tbl.Created >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")
			// ->where("tbl.Created >= ?", $date)
			;

            if($option["now"]) {
                $select = $select->where("tbl.Created >= ?", $date);
            }

			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
    }

	function getuseramount()
	{
		try {
			$select = $this->db->select()
			->from(array("tbl" => "sys_user"), array("Amount" => "count(tbl.ID)"))
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {

		}
		return $data->Amount;
	}
	
    function getlastlogin()
    {
		try {

// 			$selectLoginlist = $this->db->select()
// 			->from(array("tbl" => "log_securities"), "")
// 			->joinLeft(array("s" => "sys_user"), "tbl.UserID = s.ID", "")
// 			->columns(array(
// 				"UserID",
// 				"Created"
// 			))
// 			->where("s.IsEnable = 'Y'")
// 			->where("s.IsDeleted = 'N'")
// 			->where("tbl.LogType = 'Login'")
// 			->where("tbl.SiteType = 'B'")
// 			->where("tbl.UserID IS NOT NULL")
// 			->order("tbl.Created DESC")
// 			;

// 			$selectAction = $this->db->select()
// 			->from(array("lsec" => $selectLoginlist), "")
// 			->joinLeft(array("s" => "sys_user"), "lsec.UserID = s.ID", "")
// 			->columns(array(
// 				"lsec.UserID",
// 				"lsec.Created",
// 				"FullName" => "CONCAT(s.TitleName,' ',s.FirstName,' ',s.LastName)"
// 			))
// 			->where("s.IsEnable = 'Y'")
// 			->where("s.IsDeleted = 'N'")
// 			->group(array("lsec.UserID"))
// 			->limit(5)
//             ;

			$selectLoginlist = $this->db->select()
			->from(array("tbl" => "log_securities"), "")
			->columns(array(
					"UserID",
					"Created" => "max(tbl.Created)"
			))
			->where("tbl.LogType = 'Login'")
			->where("tbl.SiteType = 'B'")
			->where("tbl.UserID IS NOT NULL")
			->group("tbl.UserID")
			;
			
			$selectAction = $this->db->select()
			->from(array("lsec" => $selectLoginlist), "")
			->joinInner(array("s" => "sys_user"), "lsec.UserID = s.ID", "")
			->columns(array(
					"lsec.UserID",
					"lsec.Created",
					"FullName" => "CONCAT(ifnull(s.TitleName, ''),s.FirstName,' ',s.LastName)"
			))
			->where("s.IsEnable = 'Y'")
			->where("s.IsDeleted = 'N'")
			->order("lsec.Created DESC")
			->limit(5)
			;

			$stmt = $this->db->query($selectAction);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}
	
	function getHotNewslist($lang = 'th', $CategoryArticleID = null)
	{
		try {
			$select = $this->db->select()
			->from(array("tbl" => "content_article_info"), "")
			->joinLeft(array("d" => "content_article_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->columns(array(
				"tbl.ID",
				"Title" => "d.Title",
				"Amount" => "tbl.ViewAmount"
			))
			->where("tbl.IsDeleted = 'N'")
			->order("Amount DESC")
			->limit(5)
			;

			if(!empty($CategoryArticleID)) {
				$select = $select
				->joinLeft(array("cca" => "content_category_article"), "tbl.ID = cca.ContentArticleID", "")
				->where("cca.ContentCategoryID = ?", $CategoryArticleID)
				;
			}

			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}
	
	function getComplain($lang = 'th')
	{
		try {

            $select = $this->db->select()
            ->from(array("tbl" => "complain_info") , "")
            ->columns(array(
                "tbl.ID",
                "DateComplain" => "tbl.ComplainDate",
                "Topic" => "tbl.ComplainTopic",
                "FullName" => "tbl.FullName",
                "Status" => "tbl.Status"
            ))
			->where("tbl.isDeleted = 'N'")
			->order("tbl.Created DESC")
			->limit(5)
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	function getpoll($lang = 'th')
	{
		try {

			$select = $this->db->select()
			->from(array("tbl" => "poll_item_info"), "")
			->joinLeft(array("d" => "poll_item_detail"), "tbl.ID = d.ID AND d.Lang = '{$lang}'", "")
			->joinLeft(array("ps" => "poll_submission_info"), "tbl.ID = ps.PollItemID" , "")
			->columns(array(
				"tbl.ID",
				"d.Title",
				"Amount" => "IFNULL(COUNT(tbl.ID), 0)",
			))
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			->order("Amount DESC")
			->group("tbl.ID")
			->limit(5)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	function getquestion($lang = 'th')
	{
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
			->order("tbl.QuestionDate DESC")
			->limit(5)
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

}
?>