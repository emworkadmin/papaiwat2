<?php
class Vrv_Model_Category {
	
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
	public function getOption($lang) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'vrv_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'vrv_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Title"];
		}
		return $result;
	}
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array ();
		try {
			
			// set query
			$select = $this->db->select ()
			->from ( array ('tbs' => 'vrv_category_info'), '')
			->joinLeft ( array ("d" => "vrv_category_detail" ), "tbs.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft(array("fi" => "file_info"), "tbs.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbs.ID",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Ordering" => "tbs.Ordering",
					"Created" => "tbs.Created",
					"CreatedBy" => "tbs.CreatedBy",
					"Modified" => "tbs.Modified",
					"ModifiedBy" => "tbs.ModifiedBy",
					"IsEnable" => "tbs.IsEnable",
					"IsDeleted" => "tbs.IsDeleted",
					
					"IntroImageID" => "tbs.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where ( "tbs.IsEnable = 'Y' " )
			->where ( "tbs.IsDeleted = 'N' " )
			;
			
			// set criteria			
			if (! empty ( $advancesearch ['parent_id'] )) {
				$select = $select->where ( "tbs.ParentID = ? ", $advancesearch ['parent_id'] );
			} else {
				$select = $select->where ( "tbs.ParentID is null ");
			}
			
			// find total
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
			if (in_array($rowSort, array('Title'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
		return $data;
	}
	function getListCount($select) {
		$select = $this->db->select ()
		->from ( array ('T' => $select), array ("RECORDS" => "count(T.ID)") )
		;
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchObject ();
		return $data->RECORDS;
	}
	
	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'vrv_category_info'), array (
					"ID",
					"Ordering",
					"CssClass",
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

	function getDetails($id) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbs' => 'vrv_category_info'), "")
			->joinLeft(array("d" => "vrv_category_detail"), "tbs.ID = d.ID", "")
			->columns(array(
					"d.ID",
					"d.Name" ,
					"d.IsEnable",
					"d.Lang"
			))
			->where ( "tbs.IsDeleted = 'N'" )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	public function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'vrv_category_info'), "")
			->joinLeft(array("md" => "vrv_category_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->joinLeft(array("fi" => "file_info"), "m.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "m.ID",
					"Lang" => 'md.Lang',
					"ParentID" => 'm.ParentID',
					"Title" => "md.Title",
					"IntroText" => "md.IntroText",
					"Detail" => "md.Detail",
					"Ordering" => "m.Ordering",
					"CssClass" => "m.CssClass",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable",
					
					"IntroImageID" => "m.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("m.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getAllKeywordByItem($vrvItemID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'vrv_item_tags'), '')
			->columns(array(
					"Title" => "tbl.Title"
			))
			->where("tbl.VrvItemID = ? ", $vrvItemID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getSettings($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'vrv_category_info'), array (
					"Settings"
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
