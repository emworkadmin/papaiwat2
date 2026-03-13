<?php

class Zend_View_Helper_Widget_Tic3_Tic3 extends Zend_View_Helper_Abstract
{
    protected $db;
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic3_Tic3($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {

		// setup database
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
		$this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());

        $lang = Zend_Registry::get('lang');
		// $year = '2015';
		
		$year = $this->getMaxYearrAccommodationType();
        
        // Widget 3.1 แหล่งท่องเที่ยวในเขตพื้นที่อนุรักษ์
        $NationalPark = $this->getNationalPark();

        // Widget 3.2 แหล่งท่องเที่ยวในเขตพื้นที่พิเศษ
		$SiteName = $this->getSiteName();
		if (empty($SiteName)) {
			$SiteName = 293; // ------------- Fix data
		}
		
        // Widget 3.3 บริการด้านการท่องเที่ยวที่ผ่านมาตรฐาน 
        $StandardCertificate = $this->getStandardCertificate();

        // Widget 3.4 สถิติจำนวนบริการด้านการท่องเที่ยวโดยรวม (ร้านอาหาร) 
        $Restaurant = $this->getRestaurant();
        $RestaurantLatestYear = $this->getLatestYearRestaurant();
        $RestaurantLatestYear = (empty($RestaurantLatestYear) ? date("Y") : $RestaurantLatestYear);

        // Widget 3.5 สถิติจำนวนบริการด้านการท่องเที่ยวโดยรวม (สถานพักแรม) 
        $accommodationAmt = $this->getAccommodation($year);
        

		// ---------------------------------------------- Present Data ---------------------------------------------- //

		$script = '';

		$style = '
			<style>
				div#TIC3 {
					background-image: url("'. $this->view->baseUrl("assets/graph/img/Background/BG3.png") .'");
					height: 100%; 
					background-position: center;
					background-repeat: no-repeat;
					background-size: cover;
				}
				.Situation_seeAll { 
					margin-bottom: 15px; 
					margin-top: 15px; 
					color: white; 
					background: linear-gradient(to right, #29a8df, #2d5299); 
					padding: 7px 30px 2px; border-radius: 7px; 
					border: 1px solid white; 
				}
				.Situation_seeAll a {
					font-family: "Kanit Medium";
					color: white;
				}
				.textarea {
					text-align: justify;
					font-family: "Kanit Medium";
					font-size: 15px;
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
				.TIC3 .sum {
					margin-top: 10px;
					font-size: 20px;
					font-family: "Kanit";
				}
				.TIC3 .pn {
					font-family: "Mitr Light";
					font-size: 15px;
				}
				.subtexttic3 {
					color: white;
					margin-top: 10px;
					font-family: "Mitr Light";
					font-size: 11px;
				}
				@media (max-width: 992px) {
					.textpic {
						margin-top: 0px;
						margin-bottom: 0px;
					}
                }
                .hedaer-tic3 {
                    text-align: right;
                    margin-top: 15px;
                    font-size: 18px;
                    color: white;
                    font-family: "Mitr Light";
                    font-style: italic;
                }
                .textpic {
                    color: white;
                    text-align: center;
                    margin-top: 30px;
                    margin-bottom: 50px;
                }
                span.tic3 {
                    font-size: 15px;
				}
				.TIC3.TIC-widget-footer {
					color: white;
					margin-top: 10px;
					font-family: "Mitr Light";
					font-size: 11px;
				}
			</style>
		';

		$html = '
        <div class="slide-panel container TIC3" id="TIC3">
			<div class="hedaer-tic3 col-md-12">
				<!--div>* อัพเดทข้อมูลเมื่อวันที่ 25 มกราคม 2561</div-->
			</div>
			<div class="col-md-1"> </div>

			<div class="textpic col-md-2">
				<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/green.png") .'"></div>
				<div class="sum">'.number_format($NationalPark).' <span class="pn"> แหล่ง</span></div>
				<div class = "subtexttic3">แหล่งท่องเที่ยวในเขตพื้นที่อนุรักษ์</div>
				<div class="TIC3 TIC-widget-footer">ข้อมูลอัพเดตล่าสุด ณ ปี 2559</div>
			</div>	

			<div class="textpic col-md-2">
				<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/beach.png") .'"></div>
				<div class="sum">'.number_format($SiteName).' <span class="pn">แหล่ง</span></div>
				<div class = "subtexttic3">แหล่งท่องเที่ยวในเขตพื้นที่พิเศษ</div>
				<div class="TIC3 TIC-widget-footer">ข้อมูลอัพเดตล่าสุด ณ ปี 2561</div>
			</div>

			<div class="textpic col-md-2">
				<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/location.png") .'"></div>
				<div class="sum">'.number_format($StandardCertificate).' <span class="pn">แหล่ง</span></div>
				<div class = "subtexttic3">บริการด้านการท่องเที่ยว<br>ที่ผ่านมาตรฐาน</div>
				<div class="TIC3 TIC-widget-footer">ข้อมูลอัพเดตล่าสุด ณ ปี 2559</div>
			</div>	

			<div class="textpic col-md-2">
				<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/food.png") .'"></div>
				<div class="sum">'.number_format($Restaurant).' <span class="pn"> ร้าน </span></div>
				<div class = "subtexttic3">สถิติจำนวนบริการ<br>ด้านการท่องเที่ยวโดยรวม<br>(ร้านอาหาร)</div>
				<div class="TIC3 TIC-widget-footer">ข้อมูลอัพเดตล่าสุด ณ ปี ' . ($RestaurantLatestYear + 543) . '</div>
			</div>	

			<div class="textpic col-md-2">
				<div class="img"><img alt="image" src="'. $this->view->baseUrl("assets/graph/img/icon_Home/hotel.png") .'"></div>
				<div class="sum">'.number_format($accommodationAmt, 0).' <span class="pn">แห่ง</span></div>
				<div class = "subtexttic3">สถิติจำนวนบริการ<br>ด้านการท่องเที่ยวโดยรวม<br>(สถานพักแรม)</div>
				<div class="TIC3 TIC-widget-footer">ข้อมูลอัพเดตล่าสุด ณ ปี '.($year+543).'</div>
			</div>	

			<div class="col-md-1"> </div>

			<div class="clearfix"></div>
		</div>
		';

		echo $script;
		echo $style;
		echo $html;

    }
    
    // Widget 3.1 แหล่งท่องเที่ยวในเขตพื้นที่อนุรักษ์
    private function getNationalPark() {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$select = $this->db->select()
			->from(array("D" => "AreaPlan.DimNationalPark"), array("Amount"=>"count(D.NationalParkID)"))
			->where("D.NationalParkTypeID <> 0")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
            // select count(d.NationalParkID) Amount from AreaPlan.DimNationalPark d where NationalParkTypeID <> 0
			
			$data = $data->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

    // Widget 3.2 แหล่งท่องเที่ยวในเขตพื้นที่พิเศษ 
    private function getSiteName() {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$select = $this->db->select()
			->from(array("F" => "AreaPlan.FactLocalTourismSites"), array("Amount"=>"count(F.SiteNameID)"))
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
            // select count(f.SiteNameID) Amount from AreaPlan.FactLocalTourismSites F
			
			$data = $data->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

    // Widget 3.3 บริการด้านการท่องเที่ยวที่ผ่านมาตรฐาน 
    private function getStandardCertificate() {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$select = $this->db->select()
			->from(array("F" => "AreaPlan.FactStandardCertificate"), array("Amount"=>"count(*)"))
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
            // select count(*) Amount from AreaPlan.FactStandardCertificate F
			
			$data = $data->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

    // Widget 3.4 สถิติจำนวนบริการด้านการท่องเที่ยวโดยรวม (ร้านอาหาร) 
    private function getRestaurant() {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$select = $this->db->select()
			->from(array("F" => "AreaPlan.DimRestaurant"), array("Amount"=>"count(*)"))
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
            // select count(*) Amount from AreaPlan.DimRestaurant F
			
			$data = $data->Amount;
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

    // Widget 3.5 สถิติจำนวนบริการด้านการท่องเที่ยวโดยรวม (สถานพักแรม) 
	private function getAccommodation($year) {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {
			$sql = "select sum(F.NumberOfAccommodation) Amount
						from Trend.FactTravelerAccommodationType F
						inner join dbo.DimProvince D on F.ProvinceID = D.ID
						where F.DateKey = (select max(T.DateKey) D from Trend.FactTravelerAccommodationType T where T.DateKey like '{year}%')
						and D.ClusterCode > 0  ";
			$sql = str_replace("{year}", $year, $sql);
			
			$stmt = $this->db->query($sql);
			$dataObj = $stmt->fetchObject();
			$data = $dataObj->Amount;
			
		} catch ( Exception $e )  {
			
		}
		return $data;
	}
	
	private function getMaxYearrAccommodationType($year) {
		$selectMax = $this->db->select()
		->from(array("F" => "Trend.FactTravelerAccommodationType"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
		$stmtMax = $this->db->query($selectMax);
		$dataMax = $stmtMax->fetchObject();
		$year = $dataMax->year;
		return $year;
	}
	
	private function getLatestYearRestaurant() {
		$selectMax = $this->db->select()
		->from(array("F" => "AreaPlan.DimRestaurant"), array("year"=>"max(Year(F.RowStartDate))"));
		$stmtMax = $this->db->query($selectMax);
		$dataMax = $stmtMax->fetchObject();
		$year = $dataMax->year;
		return $year;
	}

}

?>