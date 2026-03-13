<?php

class Zend_View_Helper_Widget_Tic5_Tic5 extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic5_Tic5($params)
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

		$Top5RevenueByNational = $this->getTop5RevenueByNational($maxYear);

		// ---------------------------------------------- Present Data ---------------------------------------------- //
		
		$script = '';
		$html='<style>
		.inboundSec2B .dataB:nth-child(2) .rankB {color: #000;}
		.inboundSec2B .dataB:nth-child(3) .rankB {color: #89a0ae;}
		.inboundSec2B .dataB:nth-child(4) .rankB {color: #cb894f;}
		.inboundSec2B .dataB:nth-child(5) .rankB {color: #babbbf;}
		.inboundSec2B .dataB:nth-child(6) .rankB {color: #babbbf;}
		.inboundSec2B{
			margin-bottom:40px;
			padding: 20px 20px 4px 2px;
			font-family: "Kanit Medium";
			
		}
		.headtop5B .row{margin-right: 5px;text-align: right;}
		.Headtop5B{
			margin-top: 20px;
			font-size: 6rem;
			color: #0071bc;
			font-style: italic;
		}
		.amountB{
			color: #0071bc;
			font-style: italic;
			margin-bottom: 10px;
		}
		.dataB{    
			background: #fff;
			border-radius: 50px;
			margin-right: 25px;
			margin-left: 20px;
			margin-top:10px;
		}
		.IconB{
			position: relative;
			padding-top: 2px; 
		}
		.nameIconB{
			margin-left: 37%;
			font-size: 1.5rem;
			margin-top: 9%;
			font-style: italic;
		}
		.dateUpdateB{
			font-size:1.5rem;
			font-style: italic;
			text-align: left;
			color:#74a7bc;
			padding-left: 25px;
		}
		.TopIconB{position:absolute;padding-left: 5px;}
		.NationIconB{
			width:47px;
			position:absolute;
			margin-top: 5px;
			margin-left: 10px;
		}
		.rankB{text-align:center;font-style: italic;padding-top: 10%;}
		.rankWordB{font-size: 1.5rem;}
		.rankNumB{font-size: 2rem;}
		.buttonB{
			text-align: center;
			margin-top: 5px;
			margin-bottom: 40px;
			margin-left: 34%;
			margin-right: 66%;
		}
		#btviewB{
			font-size: 1.5rem;
			background: linear-gradient(#87c43f,#4db848);
			border: 0px;
			border-radius: 5px;
			width:170px;
			height:45px;
			padding-top: 11px;
		}
		#btviewB a{
			color:#fff;
		}
		.SumPopleB{
			font-size: 1rem;
		}
		.topimgB{
			position:relative;
		}
		.topimgB .GlobalIconB{
			width:6rem;
			position: absolute;
			right: 160px;
			top: 12px;
		}
		/*Bar Chart*/
		.meterB { 
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
		.meterB > span {
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
		.blueB > span {
			position: absolute;
			top: 0;
			left: 0;
			border-radius: 20px;
			background-color: #ff7271;
		}
		.rateBarTitleB {padding-top: 23px;}
		.rateBarTitleB .rateB{ width: 80px; display: inline-block; font-size: 1.4rem; color: #9E9E9E; text-align: right; }
		.rateBarTitleB .rateB span { color: #535259; margin-right: 5px; }
		.rateBarTitleB .barB { width: calc(100% - 80px); display: inline-block; float: left;  background-color: #9E9E9E;    width: 100%; }
		/*End Bar Chart*/
		@media only screen and (max-width:1200px) {
			.NationIconB{width:47px;}
			.rankNumB{font-size: 2rem;}
			.rankB{padding-top: 16%;    padding-right: 5%;}
			.nameIconB{
				margin-left: 37%;    
				font-size: 1.5rem;
				margin-top: 12%;}
		}
		@media only screen and (max-width:1025px) {
			.text1B{
				padding-bottom: 29px;
				padding-top: 29px;
			}
			.nameIconB{   margin-left: 50%;}
		}
		@media only screen and (max-width:999px) {
			.top1B {
				margin-top: 10px;
				width: 100%;
			}
			.sec1B{
				padding-left: 0px;
			}
			.inboundSec2B{    
				float: left;
				width: 100%;
			}
			.buttonB{    
				float: left;
				width: 100%;
			}
			.chart1B{    
				float: left;
					width: 100%;
			}
			.chart2B{
				float: left;
				width: 100%;
			}
			.barB{
				padding-left: 5%;
				padding-right: 5%;
			}
			.TopIconB {
				padding-left: 0px;
				left: 5%;
				top: 9%;
			}
			.NationIconB {
				width: 47px;
				top: 4px;
				left: 2px;
			}
			.nameIconB{		
				margin-left: 50%;
				margin-top: 7%;}
			.rankB {
				padding-top:10%;
			}
		}
		@media only screen and (max-width:769px) {
			.top1B {
				margin-top: 10px;
				width: 100%;
			}
			.sec1B{
				padding-left: 0px;
			}
			.inboundSec2B{    
				float: left;
				width: 100%;
			}
			.buttonB{    
				float: left;
				width: 100%;
				margin-left: 25%;
			}
			.chart1B{    
				float: left;
					width: 100%;
			}
			.chart2B{
				float: left;
				width: 100%;
			}
			.barB{
				padding-left: 5%;
				padding-right: 5%;
			}
			.TopIconB{
				padding-left: 0px;
				left: 2%;
				top: 10%;
				width: 11%;
			}
			.NationIconB{
				width: 9%;
				top: 12px;
				left: 10px;}
			.nameIconB{		
				margin-left: 37%;
				margin-top: 10%;}
			.rankB{
				padding-top:0%;
			}
		}
		.TIC5.TIC-widget-footer {
			text-align: right;
			padding-bottom: 15px;
			font-family: "Mitr Light";
			font-size: 15px;
			padding-top: 28px;
			color: #1189cc;
		}
		@media only screen and (max-width:426px) {
			.NationIconB{
				width: 17%;
				top: 7px;
				left: 2px;
			}
			.TopIconB{
				width: 20%;
			}
		}
		</style>';
		
		$html .= '<div class="slide-panel TIC5" id="TIC5">'; // slide panel
		$html .= '<div class="top5 inboundSec2B">'; // top 
		
		// Header
		$html .= '<div class="headtop5B">
						<div class="row">
							<!--div class="dateUpdateB">*อัพเดตข้อมูลเมื่อวันที่ 25 มกราคม 2561</div-->
							<div class="topimgB">
								<img alt="image" class="GlobalIconB" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Global.png').'">
								<span class="Headtop5B">TOP 5</span>
							</div>
							<div class="amountB">รายได้จากนักท่องเที่ยวชาวต่างชาติ</div>
						</div>
					</div>';
			for($i=0;$i<count($Top5RevenueByNational);$i++):
				$percentValue=number_format(($Top5RevenueByNational[$i]['Amount']*100)/$Top5RevenueByNational[0]['Amount'],2);	
				$amount = 0;
				$amountText = '';
				if ($Top5RevenueByNational[$i]['Amount'] >= 1000000000) {
					$amount = $Top5RevenueByNational[$i]['Amount'] / 1000000000;
					$amountText = 'พันล้าน';
				} elseif ($Top5RevenueByNational[$i]['Amount'] >= 1000000) {
					$amount = $Top5RevenueByNational[$i]['Amount'] / 1000000;
					$amountText = 'ล้าน';
				} elseif ($Top5RevenueByNational[$i]['Amount'] >= 1000) {
					$amount = $Top5RevenueByNational[$i]['Amount'] / 1000;
					$amountText = 'พัน';
				} else {
					$amount = $Top5RevenueByNational[$i]['Amount'];
					$amountText = '';					
				}	
			$html .=
			'<div class="row dataB">
				<div class="col-md-5">
					<div class="row IconB">';
						if($i==0):
							$html .='<img alt="image" class="TopIconB" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking1.png').'">';
						elseif($i==1):
							$html .='<img alt="image" class="TopIconB" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking2.png').'">';
						elseif($i==2):
							$html .='<img alt="image" class="TopIconB" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking3.png').'">';
						elseif($i==3):
							$html .='<img alt="image" class="TopIconB" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking4.png').'">';
						elseif($i==4):
							$html .='<img alt="image" class="TopIconB" src="'.$this->view->baseUrl('assets/graph/img/icon_web/Ranking5.png').'">';
						endif;	
							$html .='<img alt="image" class="NationIconB" src="'.$this->view->baseUrl('assets/graph/img/Flag_National/'.strtolower(str_replace(" ","_",$Top5RevenueByNational[$i]['CountryNameEN'])).'.png').'">';
							$html .='<div class="nameIconB">'.$Top5RevenueByNational[$i]['CountryNameEN'].'</div>';

						$html .='</div>	
				</div>
				<div class="col-md-4">
					<div class="row barB">
						<div class="rateBarTitleB">
							<div class="SumPopleB">'.number_format($amount,2).' '.$amountText.'บาท</div>	
							<div class="meterB blueB barB ">
								<span style="width :'.$percentValue.'%;" title ="'.$percentValue.'%"></span>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="row">
						<div class="rankB">	
							<span class="rankWordB">Ranking</span>
							<span class="rankNumB" >'.($i+1).'</span>
						</div>
					</div>
				</div>
			</div>';
			endfor; 
			
			// Button
	$html .='
			<div class="TIC5 TIC-widget-footer"><div>ที่มา : กองเศรษฐกิจการท่องเที่ยวและกีฬา</div><div>ข้อมูลอัพเดตล่าสุด ณ ปี '.($maxYear+543).'</div></div>
			<div class="row buttonB">
				<div id="btviewB"><a href="/graph/inbound/index" target="_blank">ดูข้อมูลสถิติทั้งหมด</a></div>
			</div>';
	$html .= '</div>'; // end top
	$html .= '</div>'; // end slide panel
	echo $html;
	}

	function getTop5RevenueByNational($year) {
		$data = array ();
		if ($this->db == null) {
			return $data;
		}
		try {
			// $sql = "select top(5) sum(F.RevenueAmt) Amount , C.CountryNameTH, C.CountryNameEN
			// from Trend.FactTourismReceiptInbounds F
			// left join dbo.DimCountry C on f.CountryID = C.ID
			// where F.DateKey like (select LEFT(max(DateKey),4) from Trend.FactTourismReceiptInbounds)+'%'
			// group by C.CountryNameTH, C.CountryNameEN
			// order by Amount desc";
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