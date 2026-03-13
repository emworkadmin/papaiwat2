<?php
class License_Model_Search {

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
		
	public function getProcedureByLanguage($itemID, $lang, $textSearch, $deptID=null) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('m' => 'license_item_info'), "")
			->joinInner(array("c" => "license_procedure_info"), "m.ID = c.LicenseItemID", "")
			->joinInner(array("cd" => "license_procedure_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->joinLeft(array("d" => "license_department_info"), "c.LicenseDepartmentID = d.ID", "")
			->joinLeft(array("dd" => "license_department_detail"), "d.ID = dd.ID and dd.Lang = '{$lang}' ", "")
			->columns(array(
					"c.ID",
					"c.LicenseItemID",
					"c.IconType",
					"c.StartDate",
					"c.EndDate",
					"c.PublishDate",
					"c.PublishDown",
					"c.Url",
					"c.Ordering",
					"cd.Lang",
					"cd.Title",
					"cd.Created",
					"cd.Modified",
					"c.IsEnable",
					"DepartmentName" => "dd.Name",
			))
			->where("ifnull(cd.Title, '') <> '' ")
			->where("m.ID = ?", $itemID)
			->where("c.IsEnable = 'Y' ")
			->where("c.IsDeleted = 'N' ")
			->order("c.Ordering")
			;
			
			if (!empty($textSearch)) {
				$select = $select->where("cd.Title like ? ", ("%" . $textSearch . "%"));
			}
			if (!empty($deptID)) {
				$select = $select->where("c.LicenseDepartmentID = ? ", $deptID);
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
					$data[$i]["PublishDate"] = empty($data[$i]["PublishDate"]) ? null : new DateTime($data[$i]["PublishDate"]);
					$data[$i]["PublishDown"] = empty($data[$i]["PublishDown"]) ? null : new DateTime($data[$i]["PublishDown"]);
					$data[$i]["StartDate"] = empty($data[$i]["StartDate"]) ? null : new DateTime($data[$i]["StartDate"]);
					$data[$i]["EndDate"] = empty($data[$i]["EndDate"]) ? null : new DateTime($data[$i]["EndDate"]);
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
			$select = $this->db->select()->distinct()
			->from(array("tbl" => "license_item_info"), "")
			->joinLeft(array("d" => "license_item_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinLeft(array("c" => "license_category_info"), "tbl.LicenseCategoryID = c.ID", "")
			->joinLeft(array("cd" => "license_category_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->joinLeft(array("p" => "license_procedure_info"), "tbl.ID = p.LicenseItemID", "")
			->joinLeft(array("pd" => "license_procedure_detail"), "p.ID = pd.ID and pd.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Title" => "d.Title",
					"CategoryTitle" => "cd.Title"
			))
			->where("ifnull(d.Title, '') <> '' ")
			->where("ifnull(pd.Title, '') <> '' ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("p.IsEnable = 'Y' ")
			->where("p.IsDeleted = 'N' ")
			->where("tbl.PublishDate <= ? ", date("Y-m-d H:i"))
			->where("tbl.PublishDown is null or tbl.PublishDown >= ? ", date("Y-m-d H:i"))
			;
			if (!empty($advancesearch['CategoryID'])) {
				$select = $select->where("tbl.LicenseCategoryID = ? ", $advancesearch['CategoryID']);
			}
			if (!empty($advancesearch['License'])) {
				$select = $select->where("d.Title like ? ", ("%" . $advancesearch['License'] . "%"));
			}
			if (!empty($advancesearch['Procedure'])) {
				$select = $select->where("pd.Title like ? ", ("%" . $advancesearch['Procedure'] . "%"));
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

			if (in_array($rowSort, array('Title', "CategoryTitle"))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["PublishDate"] = empty($data[$i]["PublishDate"]) ? null : new DateTime($data[$i]["PublishDate"]);
					$data[$i]["PublishDown"] = empty($data[$i]["PublishDown"]) ? null : new DateTime($data[$i]["PublishDown"]);
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
	
	
	function getListWithIssue($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
	{
		$data = array();
		try {
			$select = $this->db->select()->distinct()
			->from(array("tbl" => "license_item_info"), "")
			->joinLeft(array("d" => "license_item_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinLeft(array("i" => "license_issue_info"), "tbl.LicenseIssueID = i.ID", "")
			->joinLeft(array("id" => "license_issue_detail"), "i.ID = id.ID and id.Lang = '{$lang}' ", "")
			->joinLeft(array("c" => "license_category_info"), "i.LicenseCategoryID = c.ID", "")
			->joinLeft(array("cd" => "license_category_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->joinLeft(array("p" => "license_procedure_info"), "tbl.ID = p.LicenseItemID", "")
			->joinLeft(array("pd" => "license_procedure_detail"), "p.ID = pd.ID and pd.Lang = '{$lang}' ", "")
			->joinLeft(array("dept" => "license_department_info"), "p.LicenseDepartmentID = dept.ID", "")
			->joinLeft(array("deptd" => "license_department_detail"), "dept.ID = deptd.ID and deptd.Lang = '{$lang}' ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"PublishDate" => "tbl.PublishDate",
					"PublishDown" => "tbl.PublishDown",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Title" => "d.Title",
					"CategoryTitle" => "cd.Title",
					"IssueID" => "i.ID",
					"IssueName" => "id.Name",
					"IssueOrdering" => "i.Ordering",
					"IssueSourceUrl" => "i.SourceUrl",
			))
			->where("ifnull(d.Title, '') <> '' ")
			->where("ifnull(pd.Title, '') <> '' ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("p.IsEnable = 'Y' ")
			->where("p.IsDeleted = 'N' ")
			->where("i.IsEnable = 'Y' ")
			->where("i.IsDeleted = 'N' ")
			->where("tbl.PublishDate <= ? ", date("Y-m-d H:i"))
			->where("tbl.PublishDown is null or tbl.PublishDown >= ? ", date("Y-m-d H:i"))
			;
			if (!empty($advancesearch['CategoryID'])) {
				$select = $select->where("i.LicenseCategoryID = ? ", $advancesearch['CategoryID']);
			}
			if (!empty($advancesearch['IssueID'])) {
				$select = $select->where("tbl.LicenseIssueID = ? ", $advancesearch['IssueID']);
			}
			if (!empty($advancesearch['DepartmentID'])) {
				$select = $select->where("p.LicenseDepartmentID = ? ", $advancesearch['DepartmentID']);
			}
			if (!empty($advancesearch['License'])) {
				$select = $select->where("d.Title like ? ", ("%" . $advancesearch['License'] . "%"));
			}
			if (!empty($advancesearch['Procedure'])) {
				$select = $select->where("pd.Title like ? ", ("%" . $advancesearch['Procedure'] . "%"));
			}
			
			$records = $this->getListWithIssueCount ( $select );
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
			
			$select = $select->order("IssueOrdering asc");
			if (in_array($rowSort, array('Title', "CategoryTitle", "DepartmentName", "IssueName"))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["PublishDate"] = empty($data[$i]["PublishDate"]) ? null : new DateTime($data[$i]["PublishDate"]);
					$data[$i]["PublishDown"] = empty($data[$i]["PublishDown"]) ? null : new DateTime($data[$i]["PublishDown"]);
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getListWithIssueCount($select)
	{
		$select = $this->db->select()
		->from(array("T" => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
}
?>
