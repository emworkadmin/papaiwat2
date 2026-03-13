<?php
class License_Model_Procedure {

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
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "license_item_info"), "")
			->joinInner(array("d" => "license_item_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinInner(array("i" => "license_issue_info"), "tbl.LicenseIssueID = i.ID", "")
			->joinInner(array("id" => "license_issue_detail"), "i.ID = id.ID and id.Lang = '{$lang}' ", "")
			->joinInner(array("c" => "license_category_info"), "i.LicenseCategoryID = c.ID", "")
			->joinInner(array("cd" => "license_category_detail"), "c.ID = cd.ID and cd.Lang = '{$lang}' ", "")
			->joinInner(array("p" => "license_procedure_info"), "tbl.ID = p.LicenseItemID", "")
			->joinInner(array("pd" => "license_procedure_detail"), "p.ID = pd.ID and pd.Lang = '{$lang}' ", "")
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
					"ProcedureTitle" => "pd.Title",
					"p.StartDate",
					"p.EndDate",
					"p.Url",
					"p.UrlComment",
					"p.IconType"
			))
			->where("ifnull(pd.Title, '') <> '' ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("p.IsEnable = 'Y' ")
			->where("p.IsDeleted = 'N' ")
			->where("tbl.PublishDate <= ? ", date("Y-m-d H:i"))
			->where("tbl.PublishDown is null or tbl.PublishDown >= ? ", date("Y-m-d H:i"))
			->where("p.PublishDate <= ? ", date("Y-m-d H:i"))
			->where("p.PublishDown is null or p.PublishDown >= ? ", date("Y-m-d H:i"))
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

			if (in_array($rowSort, array('Title', "CategoryTitle", "ProcedureTitle"))) {
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
					$data[$i]["StartDate"] = empty($data[$i]["StartDate"]) ? null : new DateTime($data[$i]["StartDate"]);
					$data[$i]["EndDate"] = empty($data[$i]["EndDate"]) ? null : new DateTime($data[$i]["EndDate"]);
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
}
?>
