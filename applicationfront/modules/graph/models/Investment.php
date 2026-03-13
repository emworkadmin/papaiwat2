<?php
class Graph_Model_Investment {
	
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
	
	function findConfidenceIndexMaxYear() {
		$data = date('Y');
		if ($this->db == null) {
			return $data;
		}
		try {			
			$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from [Investment].[FactConfidenceIndex] F 
						inner join [Investment].[DimConfidenceSubType] XD on F.ConfidenceSubTypeID = XD.ID 
						where F.EstimationTypeID = 2 and XD.SubTypeNameEN = 'Overall Index' ";
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	function getConfidenceIndex($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select avg(F.value) Amount
						from [Investment].[FactConfidenceIndex] F
						inner join [Investment].[DimConfidenceSubType] D on F.ConfidenceSubTypeID = D.ID
						where F.EstimationTypeID = 2 
						and F.DateKey = (
						        select max(X.DateKey) 
						        from [Investment].[FactConfidenceIndex] X 
						        inner join [Investment].[DimConfidenceSubType] XD on X.ConfidenceSubTypeID = XD.ID 
						        where X.EstimationTypeID = 2 and XD.SubTypeNameEN = 'Overall Index' and X.DateKey like '{year}%'
						        )
						and D.SubTypeNameEN = 'Overall Index' ";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	function findOverallRankMaxYear() {
		$data = date('Y');
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Competitive.FactTCCIOverall F ";
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	function getOverallRank($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select case when AveragePercentileRank > 0 and AveragePercentileRank < 1 then 1 when AveragePercentileRank = 0 then 0 when AveragePercentileRank is null then null else ROUND(AveragePercentileRank,0) end AveragePercentileRank
						from (
						select sum(F.PercentileRank) / count(F.PercentileRank) AveragePercentileRank
						from Competitive.FactTCCIOverall F
						inner join dbo.DimCountry DC on F.CountryID = DC.ID
						where F.DateKey like '{year}%'
						and DC.CountryCode = '764'
						) F ";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->AveragePercentileRank;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	
	
	function findForeignInvestmentMaxYear() {
		$data = date('Y');
		if ($this->db == null) {
			return $data;
		}
		try {
			
			$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Investment.FactFDIData F ";
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * เงินลงทุนขาเข้าสุทธิ (Net)
	 */
	function getForeignInvestment($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$Inflow = $this->getForeignInvestmentInflow($year);
			$Outflow = $this->getForeignInvestmentOutflow($year);

			$data = (empty($Inflow) ? 0 : $Inflow) - (empty($Outflow) ? 0 : $Outflow);

		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * เงินลงทุนขาเข้า (Inflow)
	 */
	function getForeignInvestmentInflow($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Investment.FactFDIData"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Investment.FactFDIData"), array("Amount"=>"sum(F.FDIAmount)"))
			->where("F.FlowTypeID = 1")
			->where("F.DateKey like '{$year}%'")
			;
			$stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * เงินลงทุนขาเข้า (Outflow)
	 */
	function getForeignInvestmentOutflow($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Investment.FactFDIData"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Investment.FactFDIData"), array("Amount"=>"sum(F.FDIAmount)"))
			->where("F.FlowTypeID = 2")
			->where("F.DateKey like '{$year}%'")
			;
			$stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();

			// $sql = "select sum(F.FDIAmount) Amount from Investment.FactFDIData F where F.FlowTypeID = 2 and F.DateKey like '{$year}%'";
			// $stmt = $this->db->query($sql);
			// $dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * สัดส่วนปริมาณเงินลงทุน รายสัญชาติ (บาท)
	 */
	function getForeignInvestment01($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Investment.FactFDIData"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Investment.FactFDIData"), "")
			->joinInner(array("DC" => "dbo.DimCountry"),"F.CountryID = DC.ID", "")
			->columns(array(
				"Amount"=>"sum(case when F.FlowTypeID = 1 then F.FDIAmount else 0 - F.FDIAmount end)",
				"DC.ContinentNameTH"
			))
			->where("F.DateKey like '{$year}%'")
			->group("DC.ContinentNameTH")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

			// $sql  = "";
			// $sql .= "select sum(case when F.FlowTypeID = 1 then F.FDIAmount else 0 - F.FDIAmount end) Amount, DC.ContinentNameTH";
			// $sql .= "from Investment.FactFDIData F";
			// $sql .= "inner join dbo.DimCountry DC on F.CountryID = DC.ID";
			// $sql .= "where F.DateKey like '{$year}%'";
			// $sql .= "group by DC.ContinentNameTH";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
			
			// $data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * เงินลงทุนขาเข้าสุทธิ รายสาขาธุรกิจ (บาท)
	 */
	function getForeignInvestment02($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Investment.FactFDIData"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Investment.FactFDIData"), "")
			->joinInner(array("D" => "Investment.DimFDISector"),"F.SectorID = D.ID", "")
			->columns(array(
				"Amount"=>"sum(case when F.FlowTypeID = 1 then F.FDIAmount else 0 - F.FDIAmount end)",
				"D.SectorNameTH"
			))
			->where("F.DateKey like '{$year}%'")
			->group("D.SectorNameTH")
			->order("Amount DESC")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

			// $sql  = "";
			// $sql .= "select sum(case when F.FlowTypeID = 1 then F.FDIAmount else 0 - F.FDIAmount end) Amount, D.SectorNameTH";
			// $sql .= "from Investment.FactFDIData F";
			// $sql .= "inner join Investment.DimFDISector D on F.SectorID = D.ID";
			// $sql .= "where F.DateKey like '{$year}%'";
			// $sql .= "group by D.SectorNameTH";
			// $sql .= "order by Amount desc";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
			
			// $data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * เงินลงทุนโดยตรง รายสัญชาติ
	 */
	function getForeignInvestment03($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Investment.FactFDIData"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Investment.FactFDIData"), "")
			->joinInner(array("DC" => "dbo.DimCountry"),"F.CountryID = DC.ID", "")
			->columns(array(
				"Amount"=>"sum(case when F.FlowTypeID = 1 then F.FDIAmount else 0 - F.FDIAmount end)",
				"DC.CountryNameTH"
			))
			->where("F.DateKey like '{$year}%'")
			->group("DC.CountryNameTH")
			->order("Amount DESC")
			->limit("10") // เพิ่มเติม
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

			// $sql  = "";
			// $sql .= "select sum(case when F.FlowTypeID = 1 then F.FDIAmount else 0 - F.FDIAmount end) Amount, DC.CountryNameTH";
			// $sql .= "from Investment.FactFDIData F";
			// $sql .= "inner join dbo.DimCountry DC on F.CountryID = DC.ID";
			// $sql .= "where F.DateKey like '{$year}%'";
			// $sql .= "group by DC.CountryNameTH";
			// $sql .= "order by Amount Desc;";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
			
			// $data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * แนวโน้มการลงทุนจากต่างประเทศ รายปี
	 */
	function getForeignInvestment04($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Investment.FactFDIData"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Investment.FactFDIData"), "")
			->columns(array(
				"Amount"=>"sum(F.FDIAmount)",
				"Y"=>"substring(convert(nvarchar, F.DateKey), 1, 4) + 543", // เพิ่มเติม
				"F.FlowTypeID"
			))
			->where("F.FlowTypeID = 1") // เพิ่มเติม 
			->group("substring(convert(nvarchar, F.DateKey), 1, 4)")
			->group("F.FlowTypeID")
			->order("substring(convert(nvarchar, F.DateKey), 1, 4) + 543") // เพิ่มเติม
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

			// $sql  = "";
			// $sql .= "select sum(F.FDIAmount) Amount, substring(convert(nvarchar, F.DateKey), 1, 4) Y, F.FlowTypeID";
			// $sql .= "from Investment.FactFDIData F";
			// $sql .= "group by substring(convert(nvarchar, F.DateKey), 1, 4), F.FlowTypeID";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
			
			// $data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	
	
	function findExchangeRatesMaxYear() {
		$data = date('Y');
		if ($this->db == null) {
			return $data;
		}
		try {
			
			$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from Competitive.FactExchangeRates F ";
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			
			$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	/**
	 * Exchange Rates
	 */
	function getExchangeRates($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			if(empty($year)){
				$selectMax = $this->db->select()
				->from(array("F" => "Competitive.FactExchangeRates"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
				$stmtMax = $this->db->query($selectMax);
				$dataMax = $stmtMax->fetchObject();
				$year = $dataMax->year;
			}

			$select = $this->db->select()
			->from(array("F" => "Competitive.FactExchangeRates"), "")
			->joinInner(array("DC" => "Competitive.DimCurrency"),"F.CurrencyId = DC.ID", "")
			->joinInner(array("C" => "dbo.DimCountry"),"DC.CountryID = C.ID", "")
			->columns(array(
				"Amount"=>"avg(F.ExchangeRate)",
				"DC.Currency",
				"DC.CurrencySequence",
				"C.CountryNameTH",
				"C.CountryNameEN"
			))
			->where("DateKey like '{$year}%'")
			->group("DC.Currency")
			->group("DC.CurrencySequence")
			->group("C.CountryNameTH")
			->group("C.CountryNameEN")
			->order("DC.CurrencySequence")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

			// $sql  = "";
			// $sql .= "select avg(F.ExchangeRate) Amount, DC.Currency, DC.CurrencySequence, C.CountryNameTH, C.CountryNameEN";
			// $sql .= "from Competitive.FactExchangeRates F";
			// $sql .= "inner join Competitive.DimCurrency DC on F.CurrencyId = DC.ID";
			// $sql .= "inner join dbo.DimCountry C on DC.CountryID = C.ID";
			// $sql .= "where DateKey like '{$year}%'";
			// $sql .= "group by DC.Currency, DC.CurrencySequence, C.CountryNameTH, C.CountryNameEN";
			// $sql .= "order by DC.CurrencySequence";
			// $stmt = $this->db->query($sql);
			// $data = $stmt->fetchAll();
			
			// $data = $dataObj->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
}
?>