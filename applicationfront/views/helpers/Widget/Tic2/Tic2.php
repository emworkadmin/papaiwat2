<?php

class Zend_View_Helper_Widget_Tic2_Tic2 extends Zend_View_Helper_Abstract
{
	protected $db;
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic2_Tic2($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {

		// setup database
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
		$this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());

		$lang = Zend_Registry::get('lang');

		// $year = "2015"; // Fix data ------------------------

		$YearInbounds = $this->getMaxYearInbounds();
		$YearRevenue = $this->getMaxYearRevenue();

		// Widget 2.1 จำนวนนักท่องเที่ยวต่างชาติ 
		$inboundTouristAmt = $this->getInboundTourist($YearInbounds);

		// Widget 2.2 รายได้จากนักท่องเที่ยวต่างชาติ
		$inboundRevenueAmt = $this->getInboundRevenue($YearInbounds);
		
		// Widget 2.2.2
		$inboundSpendingAmt = $this->getInboundSpending($YearInbounds);
		
		// Widget 2.3.0
		$internalTouristAmt = $this->getInternalTourist($YearRevenue);

		// Widget 2.3 รายได้จากนักท่องเที่ยวภายในประเทศ
		$internalRevenueAmt = $this->getInternalRevenue($YearRevenue);

		// Widget 2.4 ค่าใช้จ่ายเฉลี่ยภายในประเทศต่อคนต่อวัน
		$internalAvgExpenditureAmt = $this->getInternalAvgExpenditure($YearRevenue);

		// ---------------------------------------------- Present Data ---------------------------------------------- //

		$script = '';

		$style = '
			<style>

				div#TIC2 {
					background-image: url("'. $this->view->baseUrl("assets/graph/img/Background/BG1.png") .'");
					height: 100%; 
					background-position: center;
					background-repeat: no-repeat;
					background-size: cover;
				}
				.Situation_seeAll_Tic2 {
					margin-bottom: 3px;
					margin-top: 15px;
					color: white;
					background: linear-gradient(to right, #29a8df, #2d5299);
					padding: 5px 45px 5px;
					border-radius: 7px;
					border: 1px solid white;
					font-size: 16px;
				}
				.texticon {
					text-align: center;
					margin-top: 55px;
					margin-bottom: 50px;
				}
				.Situation_seeAll_Tic2 a {
					font-family: "Mitr Light";
					color: white;
				}
				.textarea-tic2 {
					font-family: "Tahoma";
					font-size: 12px;
					margin-top: 15px;
					
				}
				.text1 {
					margin-top: 50px;
					margin-bottom: 50px;
				}
				.title-text {
					font-family: "Mitr Light";
					font-size: 18px;
				}
				.TIC2 .sum {
					margin-top: 10px;
					font-size: 20px;
					font-family: "Kanit";
				}
				.TIC2 .pn {
					font-family: "Mitr Light";
					font-size: 15px;
				}
				.subtext {
					color: #4292c6;
					margin-top: 10px;
					font-family: tahoma;
					font-size: 11px;
				}
				@media (max-width: 992px) {
					.texticon {
						margin-top: 0px;
						margin-bottom: 0px;
					}
				}
				.TIC2.TIC-widget-footer {
					text-align: right; 
					padding-bottom: 15px;
					font-family: "Mitr Light";
					font-size: 15px;
				}
			</style>
		';

		$html = '
		<div class="slide-panel container TIC2" id="TIC2">
			<div class="col-md-1"> </div>

			<div class="tic2-main col-md-10">

		';		

		$html .= '	
					
				<div class="texticon col-md-2">
					<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/tourism.png") .'"></div>
					<div class="sum">'.number_format($inboundTouristAmt, 2).'</div>
					<div class="pn">ล้านคน</div>
					<div class = "subtext">จำนวนนักท่องเที่ยว<br>ชาวต่างชาติ</div>
				</div>	

				<div class="texticon col-md-2">
					<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/moneyhand.png") .'"></div>
					<div class="sum">'.number_format($inboundRevenueAmt,2).'</div>
					<div class="pn">ล้านบาท</div>
					<div class = "subtext">รายได้จากนักท่องเที่ยว<br>ชาวต่างชาติ</div>
				</div>

				<div class="texticon col-md-2">
					<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/expenses.png") .'"></div>
					<div class="sum">'.number_format($inboundSpendingAmt,2).'</div>
					<div class="pn">บาท/วัน</div>
					<div class = "subtext">ค่าใช้จ่ายเฉลี่ยของ<br>นักท่องเที่ยวชาวต่างชาติ<br>(ต่อคนต่อวัน)</div>
				</div>

				<div class="texticon col-md-2">
					<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/touristthai.png") .'"></div>
					<div class="sum">'.number_format($internalTouristAmt / 1000000, 2).'</div>
					<div class="pn">ล้านคน</div>
					<div class = "subtext">จำนวนนักท่องเที่ยวชาวไทย<br>ที่ท่องเที่ยวภายในประเทศ</div>
				</div>

				<div class="texticon col-md-2">
					<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/exchang.png") .'"></div>
					<div class="sum">'.number_format($internalRevenueAmt,2).'</div>
					<div class="pn">ล้านบาท</div>
					<div class = "subtext">รายได้จากนักท่องเที่ยวชาวไทย<br>ที่ท่องเที่ยวภายในประเทศ</div>
				</div>	

				<div class="texticon col-md-2">
					<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/money.png") .'"></div>
					<div class="sum">' . number_format($internalAvgExpenditureAmt, 2) . '</div>
					<div class="pn">บาท/วัน</div>
					<div class = "subtext">ค่าใช้จ่ายของการ<br>ท่องเที่ยวภายในประเทศ<br>(ต่อคนต่อวัน)</div>
				</div>	

			</div>

			<div class="col-md-1"> </div>

			<div class="clearfix"></div>

			<div class="TIC2 TIC-widget-footer"><div>ที่มา : กองเศรษฐกิจการท่องเที่ยวและกีฬา</div><div>ข้อมูลอัพเดตล่าสุด ณ ปี '.($YearInbounds+543).'</div></div>
		</div>
		';

		echo $script;
		echo $style;
		echo $html;
	}

	// Widget 2.1 จำนวนนักท่องเที่ยวต่างชาติ 
	private function getInboundTourist($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$select = $this->db->select()
			->from(array("F" => "Trend.FactTourismReceiptInbounds"), array("Amount"=>"sum(F.ArrivalNumber)"))
			->where("F.DateKey like '{$year}%'")
			;
			$stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount/1000000;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	// Widget 2.2 รายได้จากนักท่องเที่ยวต่างชาติ
	private function getInboundRevenue($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$select = $this->db->select()
			->from(array("F" => "Trend.FactTourismReceiptInbounds"), array("Amount"=>"sum(F.RevenueAmt)"))
			->where("F.DateKey like '{$year}%'")
			;
			$stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount/1000000;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	// Widget 2.2.2 ค่าใช้จ่าเฉลี่ยของนักท่องเที่ยวชาวต่างชาติ(ต่อคนต่อวัน)
	private function getInboundSpending($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select case when isnull(sum(F.LengthOfStayDays * F.ArrivalNumber), 0) = 0 then 0 else sum(F.LengthOfStayDays * F.ArrivalNumber * F.PerCapitaSpendingPerDayAmt) / sum(F.LengthOfStayDays * F.ArrivalNumber) end Amount
						from Trend.FactTourismReceiptInbounds F
						inner join dbo.DimCountry DC on F.CountryID = DC.ID
						where F.DateKey like '{year}%'
						and DC.CountryCode <> '764'  ";
			$sql = str_replace("{year}", $year, $sql);
			
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			$data = $dataObj->Amount;
		
		} catch ( Exception $e )  {
			
		}
		return $data;
		
	}
	
	
	// 2.3.0 Widget 2 จำนวนนักท่องเที่ยวชาวไทยที่ท่องเที่ยวภายในประเทศ 
	private function getInternalTourist($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select sum(F.TravelerCount) Amount
						from Trend.FactTravelerRevenue F
						where F.DateKey like '{year}%' ";
			
			$sql = str_replace("{year}", $year, $sql);
			
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			$data = $dataObj->Amount;
		} catch ( Exception $e )  {			
			
		}
		return $data;		
	}
	

	// Widget 2.3 รายได้จากนักท่องเที่ยวภายในประเทศ 
	private function getInternalRevenue($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$select = $this->db->select()
			->from(array("F" => "Trend.FactTravelerRevenue"), array("Amount"=>"sum(F.Revenue)"))
			->where("F.DateKey like '{$year}%'")
			;
			$stmt = $this->db->query($select);
			$dataObj = $stmt->fetchObject();
			
			$data = $dataObj->Amount/1000000;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	// Widget 2.4 ค่าใช้จ่ายเฉลี่ยภายในประเทศต่อคนต่อวัน
	private function getInternalAvgExpenditure($year) {		
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select 
						        case when isnull(sum(F.TotalDays), 0) = 0 then 0 else sum(F.TotalExpenditure) / sum(F.TotalDays) end Amount
						from (
						        select 
						                F.AvgExpenditure *
						                (
						                SELECT top 1 (v1.AvgLengthOfStayDay * v1.TravelerCount) + v2.TravelerCount
						                FROM 
						                        [Trend].[FactTravelerSubTypeRevenue] v1
						                        inner join [Trend].[FactTravelerSubTypeRevenue] v2 on
						                                v1.DateKey = v2.DateKey
						                                and v1.ProvinceID = v2.ProvinceID
						                                and v1.TravelerNationalityTypeID = v2.TravelerNationalityTypeID
						                                and v2.TravelerSubTypeID = 2
						                WHERE 
						                        v1.DateKey = F.[DateKey]
						                        AND v1.ProvinceID = F.[ProvinceID]
						                        and v1.TravelerNationalityTypeID = F.TravelerNationalityTypeID
						                        and v1.TravelerSubTypeID = 1
						                ) as TotalExpenditure,
						                (SELECT top 1 (v1.AvgLengthOfStayDay * v1.TravelerCount) + v2.TravelerCount
						                FROM 
						                        [Trend].[FactTravelerSubTypeRevenue] v1
						                        inner join [Trend].[FactTravelerSubTypeRevenue] v2 on
						                                v1.DateKey = v2.DateKey
						                                and v1.ProvinceID = v2.ProvinceID
						                                and v1.TravelerNationalityTypeID = v2.TravelerNationalityTypeID
						                                and v2.TravelerSubTypeID = 2
						                WHERE 
						                        v1.DateKey = F.[DateKey]
						                        AND v1.ProvinceID = F.[ProvinceID]
						                        and v1.TravelerNationalityTypeID = F.TravelerNationalityTypeID
						                        and v1.TravelerSubTypeID = 1) as TotalDays,
						                F.*
						        from Trend.FactTravelerRevenue F
						) F 
						inner join dbo.DimProvince DP on F.ProvinceID = DP.ID
						where F.DateKey like '{year}%'
						and DP.ClusterCode <> -1 ";
			
			$sql = str_replace("{year}", $year, $sql);
			
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			$data = $dataObj->Amount;
			
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

	private function getMaxYearInbounds($year) {
		$selectMax = $this->db->select()
		->from(array("F" => "Trend.FactTourismReceiptInbounds"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"))
		->where("F.AuditKey > 0")
		;
		$stmtMax = $this->db->query($selectMax);
		$dataMax = $stmtMax->fetchObject();
		$year = $dataMax->year;
		return $year;
	}

	private function getMaxYearRevenue($year) {
		$selectMax = $this->db->select()
		->from(array("F" => "Trend.FactTravelerRevenue"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"))
		->where("F.AuditKey > 0")
		;
		$stmtMax = $this->db->query($selectMax);
		$dataMax = $stmtMax->fetchObject();
		$year = $dataMax->year;
		return $year;
	}
}

?>