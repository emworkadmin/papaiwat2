<?php
class Graph_Model_Inbound {
	
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
    	$data = '2015';
    	if ($this->db == null) {
    		return $data;
    	}
    	try {
    		$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) As Y from Trend.FactTourismReceiptInbounds F where F.AuditKey > 0 ";
    		$stmt = $this->db->query($sql);
    		$dataObj = $stmt->fetchObject();    		
    		$data = $dataObj->Y;
    	} catch ( Exception $e )  {
    		
    	}
    	return $data;
    }
    
	function getTouristAmount($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select sum(F.ArrivalNumber) As Amount from Trend.FactTourismReceiptInbounds F where F.DateKey like '{year}%'";
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
			$sql = "select sum(F.RevenueAmt) As Amount from Trend.FactTourismReceiptInbounds F where F.DateKey like '{year}%'";
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
			$sql = "select
						        case when isnull(sum(F.ArrivalNumber), 0) = 0 then 0 else sum(LengthOfStayDays * ArrivalNumber) / sum(F.ArrivalNumber) end Amount
						from Trend.FactTourismReceiptInbounds F
						inner join dbo.DimCountry DC on F.CountryID = DC.ID
						where F.DateKey like '{year}%'
						and DC.CountryCode <> '764'";
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
			$sql = "select case when isnull(sum(F.LengthOfStayDays * F.ArrivalNumber), 0) = 0 then 0 else sum(F.LengthOfStayDays * F.ArrivalNumber * F.PerCapitaSpendingPerDayAmt) / sum(F.LengthOfStayDays * F.ArrivalNumber) end Amount
						from Trend.FactTourismReceiptInbounds F
						inner join dbo.DimCountry DC on F.CountryID = DC.ID
						where F.DateKey like '{year}%'
						and DC.CountryCode <> '764' ";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	function getTop5TouristByNational($year) {
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
// 			$sql = "select top(5) sum(F.ArrivalNumber) Amount , C.CountryNameTH, C.CountryNameEN
// 			from Trend.FactTourismReceiptInbounds F
// 			left join dbo.DimCountry C on f.CountryID = C.ID
// 			where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptInbounds)+'%'
// 			group by C.CountryNameTH, C.CountryNameEN
// 			order by Amount desc";
			$sql = "select top(5) sum(F.ArrivalNumber) Amount , C.CountryNameTH, C.CountryNameEN
			from Trend.FactTourismReceiptInbounds F
			left join dbo.DimCountry C on f.CountryID = C.ID
			where F.DateKey like '{year}%'
			group by C.CountryNameTH, C.CountryNameEN
			order by Amount desc";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql); 
			$data = $stmt->fetchAll();
		}catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	function getTop5RevenueByNational($year) {
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
// 			$sql = "select top(5) sum(F.RevenueAmt) Amount , C.CountryNameTH, C.CountryNameEN
// 			from Trend.FactTourismReceiptInbounds F
// 			left join dbo.DimCountry C on f.CountryID = C.ID
// 			where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptInbounds)+'%'
// 			group by C.CountryNameTH, C.CountryNameEN
// 			order by Amount desc";
			$sql = "select top(5) sum(F.RevenueAmt) Amount , C.CountryNameTH, C.CountryNameEN
			from Trend.FactTourismReceiptInbounds F
			left join dbo.DimCountry C on f.CountryID = C.ID
			where F.DateKey like '{year}%'
			group by C.CountryNameTH, C.CountryNameEN
			order by Amount desc";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$data = $stmt->fetchAll();
		}catch ( Exception $e )  {
			
		}
		return $data;
	}

	function getTouristByYear() {
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select sum(F.ArrivalNumber) as InboundByYear , substring(convert(nvarchar,F.DateKey), 1, 4) Y
			from Trend.FactTourismReceiptInbounds F
			where substring(convert(nvarchar,F.DateKey), 1, 4) not like '2016%'
			group by substring(convert(nvarchar,F.DateKey), 1, 4)
			order by Y asc";
			$stmt = $this->db->query($sql);
			$data = $stmt->fetchAll();
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
			$sql = "select sum(F.RevenueAmt) as RevenueByYear, substring(convert(nvarchar,F.DateKey), 1, 4) Y
			from Trend.FactTourismReceiptInbounds F
			where substring(convert(nvarchar,F.DateKey), 1, 4) not like '2016%'
			group by substring(convert(nvarchar,F.DateKey), 1, 4)
			order by Y asc";
			$stmt = $this->db->query($sql);
			$data = $stmt->fetchAll();
		}catch ( Exception $e )  {
			
		}
		return $data;
	}
}
?>