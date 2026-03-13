<?php
class Graph_Model_Outbound {
	
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
	
	function findMaxYear() {
		$data = date('Y');
		if ($this->db == null) {
			return $data;
		}
		try {
			
			$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Trend.FactTourismReceiptOutbounds F ";
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
    
	function getDepartureAmount($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
// 			$sql = "select sum(DepartureNumber) Amount
// 			from Trend.FactTourismReceiptOutbounds F
// 			where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptOutbounds)+'%'";
			$sql = "select sum(DepartureNumber) Amount
						from Trend.FactTourismReceiptOutbounds F
						where F.DateKey like '{year}%' ";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	function getRevenueAmount($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
// 			$sql = "select sum(RevenueAmt) Amount
// 			from Trend.FactTourismReceiptOutbounds F
// 			where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptOutbounds)+'%'";
			$sql = "select sum(RevenueAmt) Amount
						from Trend.FactTourismReceiptOutbounds F
						where F.DateKey like '{year}%' ";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}	
	function getStayAvgAmount($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
			
		}
		try {
			//$sql = "select avg(F.LengthOfStayDays) as Amount from Trend.FactTourismReceiptOutbounds F where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptOutbounds)+'%'";
			$sql = "select 
						        case when isnull(sum(F.DepartureNumber), 0) = 0 then 0 else sum(F.LengthOfStayDays * F.DepartureNumber) / sum(F.DepartureNumber) end Amount
						from Trend.FactTourismReceiptOutbounds F
						where F.DateKey like '{year}%' ";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	function getExpressPerDayAmount($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			//$sql = "select avg(F.PerCapitaSpendingPerDayAmt) as Amount from Trend.FactTourismReceiptOutbounds F where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptOutbounds)+'%'";
			$sql = "select 
						        case when isnull(sum(F.LengthOfStayDays * F.DepartureNumber), 0) = 0 then 0 else sum(F.LengthOfStayDays * F.DepartureNumber * F.PerCapitaSpendingPerDayAmt) / sum(F.LengthOfStayDays * F.DepartureNumber) end Amount
						from Trend.FactTourismReceiptOutbounds F
						where F.DateKey like '{year}%' ";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	function getRevenueByCountry($year) {
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F"=>"Trend.FactTourismReceiptOutbounds"),array("year"=>"LEFT(max(convert(nvarchar, F.DateKey)),4)"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}
			$select = $this->db->select()
			->from(array("F" => "Trend.FactTourismReceiptOutbounds"), "")
			->joinInner(array("C" => "dbo.DimCountry"),"F.CountryID = C.ID", "")
			->columns(array(
				"Amount"=>"sum(F.RevenueAmt)",
				"CountryNameTH"=>"C.CountryNameTH",
			))
			->where("F.DateKey like '{$year}%'")
			->group("C.CountryNameTH")
			->order("Amount desc")
			->limit("10") 
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			// $sql = "select top(10) sum(RevenueAmt) Amount, C.CountryNameTH
			// from Trend.FactTourismReceiptOutbounds F
			// inner join dbo.DimCountry C on f.CountryID = C.ID
			// where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptOutbounds)+'%'
			// group by C.CountryNameTH
			// order by Amount desc";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
		}catch ( Exception $e )  {
			
		}
		return $data;
	}
	function getRevenueByYear() {
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select top (10) sum(F.RevenueAmt) Amount, substring(convert(nvarchar,F.DateKey), 1, 4) Y
			from Trend.FactTourismReceiptOutbounds F
			group by substring(convert(nvarchar,F.DateKey), 1, 4)
			order by Y desc";
			$stmt = $this->db->query($sql);
			$data = $stmt->fetchAll();
		}catch ( Exception $e )  {
			
		}
		return $data;
	}
	function getDepartureByYear() {
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select top (10) sum(F.DepartureNumber) Amount, substring(convert(nvarchar,F.DateKey), 1, 4) Y
			from Trend.FactTourismReceiptOutbounds F
			group by substring(convert(nvarchar,F.DateKey), 1, 4)
			order by Y desc";
			$stmt = $this->db->query($sql);
			$data = $stmt->fetchAll();
		}catch ( Exception $e )  {
			
		}
		return $data;
	}
}
?>