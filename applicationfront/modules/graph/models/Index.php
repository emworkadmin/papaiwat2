<?php
class Graph_Model_Index {
	
	/**
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;
	protected $serverWH="192.168.1.238";
	protected $databaseWH="TICLabDataWarehouse";
	protected $usernameWH="sa";
	protected $passwordWH="p@33w0rd";
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
    
	function getListInbound() {
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT SUM(InboundArrival) AS SumInbound FROM	[TICLabDataWarehouse].[Trend].[FactDailyTourismPlace] WHERE DateKey>20170311;";
			$query = $conn->query($sql);
			$data=$query->fetchObject();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListInboundTOP5() {
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT TOP(5)  V.CountryID,C.CountryNameTH,C.CountryNameEN,V.Inbound
			 FROM (SELECT [CountryID],SUM(OriginalInboundArrival) as Inbound FROM [TICLabDataWarehouse].[Trend].[FactDailyTourismNationality] GROUP BY [CountryID]) as V
			 LEFT JOIN TICLabDataWarehouse.dbo.DimCountry as C ON C.ID = V.CountryID
			 ORDER BY v.Inbound DESC;";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListBudget() {
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT SUM([Revenue]) AS Budget FROM [TICLabDataWarehouse].[Trend].[FactTravelerRevenue]";
			$query = $conn->query($sql);
			$data=$query->fetchObject();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListBudgetTOP5() {
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT TOP(5)  V.CountryID,C.CountryNameTH,C.CountryNameEN,V.Inbound
			 FROM (SELECT [CountryID],SUM(OriginalInboundArrival) as Inbound FROM [TICLabDataWarehouse].[Trend].[FactDailyTourismNationality] GROUP BY [CountryID]) as V
			 LEFT JOIN TICLabDataWarehouse.dbo.DimCountry as C ON C.ID = V.CountryID
			 ORDER BY v.Inbound DESC;";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListInboundBymonth() { //ยังไม่เคลียร์เรื่องการแสดงค่ารายปี ตอนนี้ Fix ไป
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT  C.MonthNumberOfYear as NumberMonth,'MonthName' = 
			CASE
			  WHEN C.MonthNumberOfYear = 1 THEN 'JAN' 
			  WHEN C.MonthNumberOfYear = 2 THEN 'FEB' 
			  WHEN C.MonthNumberOfYear = 3 THEN 'MAR' 
			  WHEN C.MonthNumberOfYear = 4 THEN 'APR' 
			  WHEN C.MonthNumberOfYear = 5 THEN 'MAY' 
			  WHEN C.MonthNumberOfYear = 6 THEN 'JUN' 
			  WHEN C.MonthNumberOfYear = 7 THEN 'JUL' 
			  WHEN C.MonthNumberOfYear = 8 THEN 'AUG' 
			  WHEN C.MonthNumberOfYear = 9 THEN 'SEP' 
			  WHEN C.MonthNumberOfYear = 10 THEN 'OCT' 
			  WHEN C.MonthNumberOfYear = 11 THEN 'NOV' 
			  WHEN C.MonthNumberOfYear = 12 THEN 'DEC' 
			END,
			SUM(V.Inbound) as Inbound
			FROM (SELECT DateKey,SUM(OriginalInboundArrival) as Inbound FROM [TICLabDataWarehouse].[Trend].[FactDailyTourismNationality] WHERE DateKey BETWEEN 20170101 AND 20180101  GROUP BY [DateKey]) as V
			LEFT JOIN TICLabDataWarehouse.dbo.DimDate as C ON C.DateKey = V.DateKey
			group by C.MonthNumberOfYear";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListInboundByyear() { //ยังไม่เคลียร์เรื่องการแสดงค่ารายปี ตอนนี้ Fix ไป
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT  C.[YearCalendarEN] as NumberYear,SUM(V.Inbound) as Inbound
			FROM (SELECT DateKey,SUM(OriginalInboundArrival) as Inbound FROM [TICLabDataWarehouse].[Trend].[FactDailyTourismNationality] GROUP BY [DateKey]) as V
			LEFT JOIN TICLabDataWarehouse.dbo.DimDate as C ON C.DateKey = V.DateKey
			group by C.[YearCalendarEN]";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListBudgetByyear() { //ยังไม่เคลียร์เรื่องการแสดงค่ารายปี ตอนนี้ Fix ไป
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT  C.[YearCalendarEN] as NumberYear,SUM(V.BudgetInbound) as Inbound
			FROM (SELECT DateKey,SUM([Revenue]) as BudgetInbound FROM [TICLabDataWarehouse].[Trend].[FactTravelerRevenue] GROUP BY [DateKey]) as V
			LEFT JOIN TICLabDataWarehouse.dbo.DimDate as C ON C.DateKey = V.DateKey
			group by C.[YearCalendarEN]";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListBudgetBymonth() { //ยังไม่เคลียร์เรื่องการแสดงค่ารายปี ตอนนี้ Fix ไป
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT  C.MonthNumberOfYear as NumberMonth,'MonthName' = 
			CASE
			  WHEN C.MonthNumberOfYear = 1 THEN 'JAN' 
			  WHEN C.MonthNumberOfYear = 2 THEN 'FEB' 
			  WHEN C.MonthNumberOfYear = 3 THEN 'MAR' 
			  WHEN C.MonthNumberOfYear = 4 THEN 'APR' 
			  WHEN C.MonthNumberOfYear = 5 THEN 'MAY' 
			  WHEN C.MonthNumberOfYear = 6 THEN 'JUN' 
			  WHEN C.MonthNumberOfYear = 7 THEN 'JUL' 
			  WHEN C.MonthNumberOfYear = 8 THEN 'AUG' 
			  WHEN C.MonthNumberOfYear = 9 THEN 'SEP' 
			  WHEN C.MonthNumberOfYear = 10 THEN 'OCT' 
			  WHEN C.MonthNumberOfYear = 11 THEN 'NOV' 
			  WHEN C.MonthNumberOfYear = 12 THEN 'DEC' 
			END,
			SUM(V.BudgetInbound) as BudgetInbound
			FROM (SELECT DateKey,SUM([Revenue]) as BudgetInbound FROM [TICLabDataWarehouse].[Trend].[FactTravelerRevenue] WHERE DateKey BETWEEN 20120101 AND 20130000  GROUP BY [DateKey]) as V
			LEFT JOIN TICLabDataWarehouse.dbo.DimDate as C ON C.DateKey = V.DateKey
			group by C.MonthNumberOfYear";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListSumLocalSite() { //ยังไม่เคลียร์เรื่องการแสดงค่ารายปี ตอนนี้ Fix ไป
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT COUNT([SiteNameID]) AS AmountSite FROM [TICLabDataWarehouse].[AreaPlan].[FactLocalTourismSites]";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListStat() { //ยังไม่เคลียร์เรื่องการแสดงค่ารายปี ตอนนี้ Fix ไป
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT SUM([SatisfactionValue]) AS SatisfactionValue  FROM [TICLabDataWarehouse].[Competitive].[FactNationalParkSatisfaction] WHERE DateKey BETWEEN 20160101 AND 20161201";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
	function getListSumTourists() { //ยังไม่เคลียร์เรื่องการแสดงค่ารายปี ตอนนี้ Fix ไป
		$data = array ();
		try {
			$server = $this->serverWH;
			$database = $this->databaseWH;
			$username = $this->usernameWH;
			$password = $this->passwordWH;
			$conn = new PDO( "sqlsrv:server=".$server." ; Database=".$database."", $username, $password);
			$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$sql = "SELECT SUM([TourVolumn]) AS TourVolumn FROM [TICLabDataStaging].[dbo].[v_Trend_TouristsByMonthSourceNew] WHERE Year=2559 ";
			$query = $conn->query($sql);	
			$data=$query->fetchAll();
		}catch ( Exception $e )  {
			print_r($e);exit;
		}
			return $data;
	}
}
?>