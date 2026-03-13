<?php
class Graph_Model_Travelstatistics {
	
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
			$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
			$this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			$this->db = null;
		}
	}


	/**
	 * Arrivals by Port
	 * จำนวนผู้เดินทางเข้า (คน)
	 */
	function getInboundArrivals($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Trend.FactDailyTourismPlace"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Trend.FactDailyTourismPlace"), "")
			->columns(array(
				"Amount"=>"sum(F.InboundArrival)",
			))
			->where("F.DateKey like '{$year}%'")
			;
			$stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();

			// $sql  = "";
			// $sql .= "select sum(F.InboundArrival) Amount";
			// $sql .= "from Trend.FactDailyTourismPlace F";
			// $sql .= "where F.DateKey like '{$year}%'";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * Arrivals by Port
	 * จำนวนผู้เดินทางออก (คน)
	 */
	function getOutboundArrivals($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Trend.FactDailyTourismPlace"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Trend.FactDailyTourismPlace"), "")
			->columns(array(
				"Amount"=>"sum(F.OutboundArrival)",
			))
			->where("F.DateKey like '{$year}%'")
			;
			$stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();

			// $sql  = "";
			// $sql .= "select sum(F.OutboundArrival) Amount";
			// $sql .= "from Trend.FactDailyTourismPlace F";
			// $sql .= "where F.DateKey like '{$year}%'";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * Arrivals by Port
	 * จำนวนผู้เดินทางเข้าจำแนกตามสัญชาติ
	 */
	function getInboundArrivalsNationality($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Trend.FactDailyTourismPlace"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Trend.FactDailyTourismPlace"), "")
			->joinInner(array("D" => "dbo.DimTravelerNationalityType"),"F.NationalityTypeID = D.ID", "")
			->columns(array(
				"Amount"=>"sum(F.InboundArrival)",
				"D.TravelerNationalityTypeNameTH"
			))
			->where("F.DateKey like '{$year}%'")
			->group("D.TravelerNationalityTypeNameTH")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			// $sql  = "";
			// $sql .= "select sum(F.InboundArrival) Amount, D.TravelerNationalityTypeNameTH";
			// $sql .= "from Trend.FactDailyTourismPlace F";
			// $sql .= "inner join dbo.DimTravelerNationalityType D on F.NationalityTypeID = D.Id";
			// $sql .= "where F.DateKey like '{$year}%'";
			// $sql .= "group by D.TravelerNationalityTypeNameTH";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
			
			// $data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * Arrivals by Port
	 * จำนวนผู้เดินทางออกจำแนกตามสัญชาติ
	 */
	function getOutboundArrivalsNationality($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Trend.FactDailyTourismPlace"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Trend.FactDailyTourismPlace"), "")
			->joinInner(array("D" => "dbo.DimTravelerNationalityType"),"F.NationalityTypeID = D.ID", "")
			->columns(array(
				"Amount"=>"sum(F.OutboundArrival)",
				"D.TravelerNationalityTypeNameTH"
			))
			->where("F.DateKey like '{$year}%'")
			->group("D.TravelerNationalityTypeNameTH")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			// $sql  = "";
			// $sql .= "select sum(F.OutboundArrival) Amount, D.TravelerNationalityTypeNameTH";
			// $sql .= "from Trend.FactDailyTourismPlace F";
			// $sql .= "inner join dbo.DimTravelerNationalityType D on F.NationalityTypeID = D.Id";
			// $sql .= "where F.DateKey like '{$year}%'";
			// $sql .= "group by D.TravelerNationalityTypeNameTH";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
			
			// $data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * Tourists by Port
	 * อัตราการใช้สนามบิน
	 */
	function getTouristsArrivals($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Trend.FactDailyTourismPlace"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Trend.FactDailyTourismPlace"), "")
			->joinInner(array("D" => "Trend.DimPlace"),"F.PlaceID = D.ID", "")
			->columns(array(
				"Amount"=>"sum(F.InboundArrival + F.OutboundArrival)",
				"D.PlaceNameTH"
			))
			->where("F.DateKey like '{$year}%'")
			->group("D.PlaceNameTH")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			// $sql  = "";
			// $sql .= "select sum(F.InboundArrival + F.OutboundArrival) Amount, D.PlaceNameTH";
			// $sql .= "from Trend.FactDailyTourismPlace F";
			// $sql .= "inner join Trend.DimPlace D on F.PlaceID = D.ID";
			// $sql .= "where F.DateKey like '{$year}%'";
			// $sql .= "group by D.PlaceNameTH";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
			
			// $data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

}
?>