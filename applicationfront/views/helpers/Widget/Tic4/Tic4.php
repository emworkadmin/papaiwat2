<?php

class Zend_View_Helper_Widget_Tic4_Tic4 extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic4_Tic4($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {
		// setup database
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
		$this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());

		// $maxYear = '2015';
		$maxYear = $this->getMaxYearReceiptInbounds();
		
		$Top5TouristByNational = $this->getTop5TouristByNational($maxYear);
		// ---------------------------------------------- Present Data ---------------------------------------------- //
		 
		$script = '';
		$html='<style>
		.inboundSec2 .data:nth-child(2) .rank {color: #000;}
		.inboundSec2 .data:nth-child(3) .rank {color: #89a0ae;}
		.inboundSec2 .data:nth-child(4) .rank {color: #cb894f;}
		.inboundSec2 .data:nth-child(5) .rank {color: #babbbf;}
		.inboundSec2 .data:nth-child(6) .rank {color: #babbbf;}
		.inboundSec2{
			margin-bottom:40px;
			padding: 20px 20px 4px 2px;
			font-family: "Kanit Medium";
			
		}
		.headtop5 .row{margin-left: 5px;}
		.Headtop5{
			margin-top: 20px;
			font-size: 6rem;
			color: #0071bc;
			font-style: italic;
			margin-left: 60px;
		}
		.amount{
			color: #0071bc;
			font-style: italic;
			margin-bottom: 10px;
		}
		.data{    
			background: #fff;
			border-radius: 50px;
			margin-right: 25px;
			margin-left: 20px;
			margin-top:10px;
		}
		.Icon{
			position: relative;
			padding-top: 2px; 
		}
		.nameIcon{
			margin-left: 37%;
			font-size: 1.5rem;
			margin-top: 9%;
			font-style: italic;
		}
		.dateUpdate{
			font-size:1.5rem;
			font-style: italic;
			text-align: right;
			color:#5e4178;
			margin-right: 10px;
			
		}
		.TopIcon{position:absolute;padding-left: 5px;}
		.NationIcon{
			width:47px;
			position:absolute;
			margin-top: 5px;
			margin-left: 10px;
		}
		.rank{text-align:center;font-style: italic;padding-top: 10%;}
		.rankWord{font-size: 1.5rem;}
		.rankNum{font-size: 2rem;}
		.button{
			text-align: center;
			margin-top: 5px;
			margin-bottom: 40px;
			margin-left: 34%;
			margin-right: 66%;
		}
		#btview{
			font-size: 1.5rem;
			background: linear-gradient(#26a7e0,#0a7fc5);
			border: 0px;
			border-radius: 5px;
			width:170px;
			height:45px;
			padding-top: 11px;
		}
		#btview a{
			color:#fff;
		}
		.SumPople{
			font-size: 1rem;
		}
		.topimg{
			position:relative;
		}
		.topimg .GlobalIcon{
			width:6rem;
			position: absolute;
			top: 12px;
		}
		/*Bar Chart*/
		.meter { 
			height: 5px;
			position: relative;
			background: #EFEFEF;
			-moz-border-radius: 25px;
			-webkit-border-radius: 25px;
			border-radius: 25px;
			padding: 5px;
			box-shadow: inset 0 -1px 1px rgba(255,255,255,0.3);
			margin-bottom: 15px;
		}
		.meter > span {
			display: block;
			height: 100%;
			border-top-right-radius: 8px;
			border-bottom-right-radius: 8px;
			border-top-left-radius: 20px;
			border-bottom-left-radius: 20px;
			background-color: rgb(43,194,83);
			position: relative;
			overflow: hidden;
		}
		.blue > span {
			position: absolute;
			top: 0;
			left: 0;
			border-radius: 20px;
			background-color: #ff7271;
		}
		.rateBarTitle {padding-top: 23px;}
		.rateBarTitle .rate { width: 80px; display: inline-block; font-size: 1.4rem; color: #9E9E9E; text-align: right; }
		.rateBarTitle .rate span { color: #535259; margin-right: 5px; }
		.rateBarTitle .bar { width: calc(100% - 80px); display: inline-block; float: left;background-color: #9E9E9E;    width: 100%; }
		/*End Bar Chart*/
		@media only screen and (max-width:1200px) {
			.NationIcon{width:47px;}
			.rankNum{font-size: 2rem;}
			.rank{padding-top: 16%;    padding-right: 5%;}
			.nameIcon {
				margin-left: 50%; 
				font-size: 1.5rem;
				margin-top: 11%;}
		}
		@media only screen and (max-width:1025px) {
			.text1 {
				padding-bottom: 29px;
				padding-top: 29px;
			}
			.nameIcon{   margin-left: 50%;}
		}
		@media only screen and (max-width:999px) {
			.top1 {
				margin-top: 10px;
				width: 100%;
			}
			.sec1{
				padding-left: 0px;
			}
			.inboundSec2{    
				float: left;
				width: 100%;
			}
			.button{    
				float: left;
				width: 100%;
			}
			.chart1{    
				float: left;
				width: 100%;
			}
			.chart2{
				float: left;
				width: 100%;
			}
			.bar{
				padding-left: 5%;
				padding-right: 5%;
			}
			.TopIcon {
				padding-left: 0px;
				left: 5%;
				top: 9%;
			}
			.NationIcon {
				width: 47px;
				top: 4px;
				left: 2px;}
			.nameIcon{		
				margin-left: 50%;
				margin-top: 7%;}
			.rank {
				padding-top:10%;
			}
		}
		@media only screen and (max-width:769px) {
			.top1 {
				margin-top: 10px;
				width: 100%;
			}
			.sec1{
				padding-left: 0px;
			}
			.inboundSec2{    
				float: left;
				width: 100%;
			}
			.button{    
				float: left;
				width: 100%;
				margin-left: 25%;
			}
			.chart1{    
				float: left;
					width: 100%;
			}
			.chart2{
				float: left;
				width: 100%;
			}
			.bar{
				padding-left: 5%;
				padding-right: 5%;
			}
			.TopIcon {
				padding-left: 0px;
				left: 2%;
				top: 10%;
				width: 11%;
			}
			.NationIcon {
				width: 9%;
				top: 12px;
				left: 10px;
			}
			.nameIcon{		
				margin-left: 40%;
				margin-top: 10%;
			}
			.rank {
				padding-top:0%;
			}
		}
		.TIC4.TIC-widget-footer {
			text-align: right;
			padding-bottom: 15px;
			font-family: "Mitr Light";
			font-size: 15px;
			padding-top: 28px;
			color: #1189cc;
		}
		@media only screen and (max-width:426px) {
			.NationIcon {
				width: 17%;
				top: 7px;
				left: 2px;
			}
			.TopIcon{
				width: 20%;
			}
		}
		</style>';
		
		$html .= '<div class="slide-panel TIC4" id="TIC4">'; // slide panel
		$html .= '<div class="top5 inboundSec2">'; // top 
		
		// Header
		$html .= '<div class="headtop5">
						<div class="row">
							<!--div class="dateUpdate">*อัพเดตข้อมูลเมื่อวันที่ 25 มกราคม 2561</div-->
							<div class="topimg">
								<img alt="image" class="GlobalIcon" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Global.png').'">
								<span class="Headtop5">TOP 5</span>
							</div>
							<div class="amount">จำนวนนักท่องเที่ยวชาวต่างชาติ</div>
						</div>
					</div>';
			for($i=0;$i<count($Top5TouristByNational);$i++) :
				$percentValue=number_format(($Top5TouristByNational[$i]['Amount']*100)/$Top5TouristByNational[0]['Amount'],2);	
			$html .=
			'<div class="row data">
				<div class="col-md-5">
					<div class="row Icon">';
						if($i==0):
							$html .='<img alt="image" class="TopIcon" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking1.png').'">';
						elseif($i==1):
							$html .='<img alt="image" class="TopIcon" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking2.png').'">';
						elseif($i==2):
							$html .='<img alt="image" class="TopIcon" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking3.png').'">';
						elseif($i==3):
							$html .='<img alt="image" class="TopIcon" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking4.png').'">';
						elseif($i==4):
							$html .='<img alt="image" class="TopIcon" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking5.png').'">';
						endif;	
						$html .='<img alt="image" class="NationIcon" src="'.$this->view->baseUrl('assets/graph/img/Flag_National/'.strtolower(str_replace(" ","_",$Top5TouristByNational[$i]['CountryNameEN'])).'.png').'">	
						<div class="nameIcon">'.$Top5TouristByNational[$i]['CountryNameEN'].'</div>
					</div>	
				</div>
				<div class="col-md-4">
					<div class="row bar">
						<div class="rateBarTitle">
							<div class="SumPople">จำนวน&nbsp;'.number_format($Top5TouristByNational[$i]['Amount']).'&nbsp;คน</div>	
							<div class="meter blue bar">
								<span style="width :'.$percentValue.'%;" title ="'.$percentValue.'%"></span>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="row">
						<div class="rank">	
							<span class="rankWord">Ranking</span>
							<span class="rankNum">'.($i+1).'</span>
						</div>
					</div>
				</div>
			</div>';
			endfor;
			
			// Button
			$html .=
			'<div class="TIC4 TIC-widget-footer"><div>ที่มา : กองเศรษฐกิจการท่องเที่ยวและกีฬา</div><div>ข้อมูลอัพเดตล่าสุด ณ ปี '.($maxYear+543).'</div></div>
			<div class="row button">
				<div id="btview"><a href="/graph/inbound/index" target="_blank">ดูข้อมูลสถิติทั้งหมด</a></div>
			</div>';
	$html .= '</div>'; // end top
	$html .= '</div>'; // end slide panel
	echo $html;

	}
	
	function getTop5TouristByNational($year) {
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
			// $sql = "select top(5) sum(F.ArrivalNumber) Amount , C.CountryNameEN
			// from Trend.FactTourismReceiptInbounds F
			// left join dbo.DimCountry C on f.CountryID = C.ID
			// where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptInbounds)+'%'
			// group by C.CountryNameEN
			// order by Amount desc";
			$sql = "select top(5) sum(F.ArrivalNumber) Amount , C.CountryNameEN
			from Trend.FactTourismReceiptInbounds F
			left join dbo.DimCountry C on f.CountryID = C.ID
			where F.DateKey like '{year}%'
			group by C.CountryNameEN
			order by Amount desc";
			$sql = str_replace("{year}", $year, $sql);
			$stmt = $this->db->query($sql); 
			$data = $stmt->fetchAll();
		}catch ( Exception $e )  {
			
		}
		return $data;
	}

	protected function checkAmount($amount){
		$data=array();
		if ($amount >= 1000000000) {
			$data["Amount"] = $amount / 1000000000;
			$data["AmountText"] = 'พันล้าน';
		} elseif ($amount >= 1000000) {
			$data["Amount"] = $amount / 1000000;
			$data["AmountText"] = 'ล้าน';
		} elseif ($amount >= 1000) {
			$data["Amount"] = $amount / 1000;
			$data["AmountText"] = 'พัน';
		}else {
			$data["Amount"] = $amount;
			$data["AmountText"] = '';
		}
		return $data;
	}

	private function getMaxYearReceiptInbounds($year) {
		$selectMax = $this->db->select()
		->from(array("F" => "Trend.FactTourismReceiptInbounds"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"))
		->where("F.AuditKey > 0")
		;
		$stmtMax = $this->db->query($selectMax);
		$dataMax = $stmtMax->fetchObject();
		$year = $dataMax->year;
		return $year;
	}

}

?>