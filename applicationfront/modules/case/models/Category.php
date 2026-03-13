<?php
class Case_Model_Category {
	
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
			$config = new Zend_Config_Ini(APPLICATION_ROOT . DS .'applicationadmin'. DS .'modules' . DS . 'case' . DS . 'configs' . DS . 'config.ini', 'database');
			$this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			$this->db = null;
		}
	}
	
	public function getDataByLanguage() {
		$data = new stdClass();
		$today=date("Y-m-d");
		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'coj_case'), "")
			->columns(array(
					"ID" => "tbl.ID",
					"BlackCasePrefix" => "tbl.BlackCasePrefix",
					"BlackCaseNo" => "tbl.BlackCaseNo",
					"BlackCaseYear" => "tbl.BlackCaseYear",
					"RedCasePrefix" => "tbl.RedCasePrefix",
					"RedCaseNo" => "tbl.RedCaseNo",
					"RedCaseYear" => "tbl.RedCaseYear",
					"TrialDate" => "tbl.TrialDate",
					"TrialRoom" => "tbl.TrialRoom",
					"TrialReason" => "tbl.TrialReason",
					"SubpoenaType" => "tbl.SubpoenaType",
					"Injunction" => "tbl.Injunction",
					"CourtCode" => "tbl.CourtCode",
			))
			->where("tbl.IsDeleted = ?",'N')
			->where("tbl.TrialDate > ?",$today)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	public function getPrefixBlack($id) {

		$selectBlack = $this->db->select()->distinct()
		->from(array('tbl' => 'coj_case'), "")
		->columns(array(
				"Prefix" => "tbl.BlackCasePrefix",
		))
		;
		$stmt = $this->db->query($selectBlack);
		$data = $stmt->fetchAll();
		$result = array ();
	
		foreach ( $data as $value ) {
			$result [$value ["Prefix"]] = $value ["Prefix"];
		}
		return $result;
	}
	public function getPrefixRed($id) {

		$selectRed = $this->db->select()->distinct()
		->from(array('dt' => 'coj_case'), "")
		->columns(array(
				"Prefix" => "dt.RedCasePrefix",
		))
		;
		$stmt = $this->db->query($selectRed);
		$data = $stmt->fetchAll();
		$result = array ();
	
		foreach ( $data as $value ) {
			$result [$value ["Prefix"]] = $value ["Prefix"];
		}
		return $result;
	}

	function getList($advancesearch,$caseSettingCourtCode, $page, $rows) {
		$data = new stdClass();
		$today=date("Y-m-d");
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'coj_case'), "")
			->columns(array(
					"ID" => "tbl.ID",
					"BlackCasePrefix" => "tbl.BlackCasePrefix",
					"BlackCaseNo" => "tbl.BlackCaseNo",
					"BlackCaseYear" => "tbl.BlackCaseYear",
					"RedCasePrefix" => "tbl.RedCasePrefix",
					"RedCaseNo" => "tbl.RedCaseNo",
					"RedCaseYear" => "tbl.RedCaseYear",
					"TrialDate" => "tbl.TrialDate",
					"TrialRoom" => "tbl.TrialRoom",
					"TrialReason" => "tbl.TrialReason",
					"SubpoenaType" => "tbl.SubpoenaType",
					"Injunction" => "tbl.Injunction",
					"CourtCode" => "tbl.CourtCode",
			))
			->where("tbl.IsDeleted = ?",'N')
			->order("DATE(tbl.TrialDate) ASC")
			;
			if(!empty($caseSettingCourtCode)){
				$select=$select->where("tbl.CourtCode = ?",$caseSettingCourtCode);
			}
			if($advancesearch){
				if($advancesearch['BlackCasePrefix']){
					$select=$select->where("tbl.BlackCasePrefix = ?",$advancesearch['BlackCasePrefix']);
				}
				if($advancesearch['BlackCaseNo']){
					$select=$select->where("tbl.BlackCaseNo = ?",$advancesearch['BlackCaseNo']);
				}
				if($advancesearch['BlackCaseYear']){
					$select=$select->where("tbl.BlackCaseYear = ?",$advancesearch['BlackCaseYear']);
				}
				if($advancesearch['RedCasePrefix']){
					$select=$select->where("tbl.RedCasePrefix = ?",$advancesearch['RedCasePrefix']);
				}
				if($advancesearch['RedCaseNo']){
					$select=$select->where("tbl.RedCaseNo = ?",$advancesearch['RedCaseNo']);
				}
				if($advancesearch['RedCaseYear']){
					$select=$select->where("tbl.RedCaseYear = ?",$advancesearch['RedCaseYear']);
				}
				if($advancesearch['casebeginDate']){

					$start=Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['casebeginDate']);
					// $start=$start."23:59:59";
					$select=$select->where("DATE(tbl.TrialDate) >= ?",$start);

				}else{$select=$select->where("DATE(tbl.TrialDate) => ?",$today);}
				if($advancesearch['caseendDate']){

					$end=Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['caseendDate']);
					// $end=$end."23:59:59";
					$select=$select->where("DATE(tbl.TrialDate) <= ?",$end);
				}
			}
		
			$records = $this->getListCount ( $select );

			$select = $this->db->select ()
			->from ( array ('tbs' => $select ), array ('*'))
			->columns ( array ("RECORD" => "abs($records)" ) )
			->limitPage ($page, $rows )
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["TrialDate"]  = empty($data[$i]["TrialDate"]) ? null : new DateTime($data[$i]["TrialDate"]);
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
}

?>
