<?php
class License_Model_Issue {

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
			$this->db = Zend_Db_Table::getDefaultAdapter ();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			
		}
	}

	public function getOption($categoryID, $lang) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'license_issue_info' ), array ('ID') )
		->joinInner ( array ('d' => 'license_issue_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Name') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Name USING TIS620)  ASC" );
		
		if (!empty($categoryID)) {
			$select = $select->where("tbs.LicenseCategoryID = ?", $categoryID);
		}
		
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Name"];
		}
		return $result;
	}
	
	public function getLicenseByLanguage($issueID, $lang) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('m' => 'license_issue_info'), "")
			->joinInner(array("c" => "license_item_info"), "m.ID = c.LicenseIssueID", "")
			->joinInner(array("cd" => "license_item_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->joinInner(array("d" => "license_department_info"), "c.LicenseDepartmentID = d.ID", "")
			->joinInner(array("dd" => "license_department_detail"), "d.ID = dd.ID and dd.Lang = '{$lang}' ", "")
			->columns(array(
					"c.ID",
					"c.LicenseIssueID",
					"c.PublishDate",
					"c.PublishDown",
					"cd.Lang",
					"cd.Title",
					"cd.Created",
					"cd.Modified",
					"c.IsEnable",
					"DepartmentName" => "dd.Name"
			))
			->where("ifnull(cd.Title, '') <> '' ")
			->where("m.ID = ?", $itemID)
			->where("c.IsEnable = 'Y' ")
			->where("c.IsDeleted = 'N' ")
			->where("c.PublishDate <= ? ", date("Y-m-d H:i"))
			->where("c.PublishDown is null or c.PublishDown >= ? ", date("Y-m-d H:i"))
			->order("cd.Title")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
					$data[$i]["PublishDate"] = empty($data[$i]["PublishDate"]) ? null : new DateTime($data[$i]["PublishDate"]);
					$data[$i]["PublishDown"] = empty($data[$i]["PublishDown"]) ? null : new DateTime($data[$i]["PublishDown"]);
				}
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "license_issue_info"), "")
			->joinLeft(array("d" => "license_issue_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinLeft(array("c" => "license_category_info"), "tbl.LicenseCategoryID = c.ID", "")
			->joinLeft(array("cd" => "license_category_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Name" => "d.Name",
					"SourceUrl" => "tbl.SourceUrl",
					"CategoryTitle" => "cd.Title"
			))
			->where("ifnull(d.Name, '') <> '' ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			;
			if (!empty($advancesearch['CategoryID'])) {
				$select = $select->where("tbl.LicenseCategoryID = ? ", $advancesearch['CategoryID']);
			}
			
			$records = $this->getListCount ( $select );
			// set ordering and total
			if (empty ( $sidx )) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select ()
			->from ( array ('tbs' => $select ), array ('*'))
			->columns ( array ("RECORD" => "abs($records)" ) )
			->limitPage ( $page, $rows )
			;
			
			if (in_array($rowSort, array('Name', "CategoryTitle"))) {
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
			
		}
		return $data;
	}
	
	function getlistCount($select)
	{
		$select = $this->db->select()
		->from(array("T" => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}

	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'license_issue_info'), array (
					"ID",
					"Ordering" ,
					"SourceUrl" ,
					"IsDeleted",
					"IsEnable" 
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}
}
?>