<?php
class Orgchart_Model_Item {
	
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
	
	function getInfo($id) {
		$data = new stdClass ();
		try {
			$select = $this->db->select ()
			->from(array ('tbs' => 'orgchart_chart_info'), array (
					"ID",
					"ChartDetail",
					"Settings",
					"Ordering" ,
					"IsDeleted",
					"IsEnable" 
			))
			->where("tbs.IsEnable = 'Y'")
			->where("tbs.IsDeleted = 'N'")
			->where("tbs.ID = ? ", $id)
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}

	function getDetail($id, $lang = 'th')
	{
		$data = new stdClass();
		try {
			$select = $this->db->select ()
			->from(array ('tbl' => 'orgchart_chart_detail'), array (
					"ID",
					"Title"
			))
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			->where("tbl.ID = ? ", $id)
			->where("tbl.Lang = ? ", $lang)
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch (Exception $e) {

		}
		return $data;
	}

	public function getDataWorkgroupByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'orgchart_workgroup_info'), "")
			->joinLeft(array("d" => "orgchart_workgroup_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Lang" => 'd.Lang',
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",
					"Detail" => "d.Detail",
					"Ordering" => "tbl.Ordering",
					"IsDeleted" => "tbl.IsDeleted",
					"IsEnable" => "tbl.IsEnable",
					
					"IntroImageID" => "tbl.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			// ->where("tbl.IsEnable = 'Y'")
			// ->where("tbl.IsDeleted = 'N'")
			->where("tbl.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}		

	public function getDataPersonByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'orgchart_person_info'), '')
			->joinLeft(array('d' => 'orgchart_person_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('w' => 'orgchart_workgroup_info'), 'tbl.WorkgroupID = w.ID', '')
			->joinLeft(array('wd' => 'orgchart_workgroup_detail') , "w.ID = wd.ID and wd.Lang = '{$lang}' " , '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"Lang" => 'd.Lang',
					"Title" => "d.Title",
					"WorkgroupID" => "tbl.WorkgroupID",
					"WorkgroupName" => "wd.Title",
					"Job" => "d.Job",
					"Department" => "d.Department",
					"Detail" => "d.Detail",
					"IsDeleted" => "tbl.IsDeleted",
					"IsEnable" => "tbl.IsEnable",

					"IntroImageID" => "tbl.IntroImageID",
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			// ->where("tbl.IsEnable = 'Y'")
			// ->where("tbl.IsDeleted = 'N'")
			->where("tbl.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}	

}
?>