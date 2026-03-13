<?php

class Zend_View_Helper_Widget_Tic1_Tic1 extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic1_Tic1($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');

		// ---------------------------------------------- Present Data ---------------------------------------------- //

		$script = '
			<link rel="stylesheet" href="'. $this->view->baseUrl("plugins/slick/slick.css") . '">
			<link rel="stylesheet" href="'. $this->view->baseUrl("plugins/slick/slick-theme.css") . '">
			<script type="text/javascript" src="'. $this->view->baseUrl("plugins/slick/slick.min.js") . '"></script>	
			<script type="text/javascript">
			jQuery(function() {
				recommendslide();
				function recommendslide() {
					$("#slide").slick({
						dots: false,
						arrows: true,
						nextArrow: $("#TIC1Next"),
						prevArrow: $("#TIC1Prev"),
						speed: 300,
						slidesToShow: 5,
						slidesToScroll: 5,
						responsive: [
							{
								breakpoint: 1200,
								settings: {
									slidesToShow: 4,
									slidesToScroll: 4,
								}
							},
							{
								breakpoint: 992,
								settings: {
									slidesToShow: 3,
									slidesToScroll: 3,
								}
							},
							{
								breakpoint: 768,
								settings: {
									slidesToShow: 2,
									slidesToScroll: 2,
								}
							},
							{
								breakpoint: 375,
								settings: {
									slidesToShow: 1,
									slidesToScroll: 1,
								}
							}
						]
					});
				}
			});
			</script>
		';

		$style = '
			<style>
				.slide-panel { position: relative; }
				#TIC1Next { 
					position: absolute;
					transform: translateY(-50%);
					top: 50%;
					right: 0;
					font-size: 40px;
					cursor: pointer;
					color: #C2C2C2;
					background: -webkit-linear-gradient(#00246f, #5aaeff, #00246f);
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;					
				}
				#TIC1Prev {
					position: absolute;
					transform: translateY(-50%);
					top: 50%;
					left: 0;
					font-size: 40px;
					cursor: pointer;
					color: #C2C2C2;
					background: -webkit-linear-gradient(#5aaeff, #00246f, #5aaeff);
					-webkit-background-clip: text;
					-webkit-text-fill-color: transparent;						
				}
				#TIC1 .TIC_SlideCard { 
					background: white;
					padding: 10px;
					border: 1px solid #6CA9D5;
					border-radius: 5px;
					margin: 15px;
				}
				#TIC1 .TIC_SlideCard .box-img {
					padding: 15px;
				}
				#TIC1 .TIC_SlideCard .box-text {
					text-align: center;
					font-size: 20px;
					color: black;
				}
				#TIC1 .TIC_SlideCard .box-img img {
					width: 50px;
					margin: auto;			
				}
			</style>
		';

		$html = '
		<div class="slide-panel col-md-12 TIC1" id="TIC1">
			<div class="row" id="slide">

			<div class="TIC_SlideCard">
				<div class="box-img"><img src="'.$this->view->baseUrl("/assets/graph/img/icon_Home/inbound_tourism_situation.png").'"></div>
				<div class="box-text">สถานการณ์<br>การท่องเที่ยวขาเข้า</div>
				<div class="box-text link"><a href="https://www.google.co.th" target="_blank">อ่านต่อทั้งหมด></a></div>
			</div>

			<div class="TIC_SlideCard">
				<div class="box-img"><img src="'.$this->view->baseUrl("/assets/graph/img/icon_Home/domestic_tourism_situation.png").'"></div>
				<div class="box-text">สถานการณ์<br>การท่องเที่ยวภายในประเทศ</div>
				<div class="box-text link"><a href="https://www.google.co.th" target="_blank">อ่านต่อทั้งหมด></a></div>
				</div>
			
			<div class="TIC_SlideCard">
				<div class="box-img"><img src="'.$this->view->baseUrl("/assets/graph/img/icon_Home/outbound_tourism_situation.png").'"></div>
				<div class="box-text">สถานการณ์<br>การท่องเที่ยวขาออก</div>
				<div class="box-text link"><a href="https://www.google.co.th" target="_blank">อ่านต่อทั้งหมด></a></div>
				</div>
			
			<div class="TIC_SlideCard">
				<div class="box-img"><img src="'.$this->view->baseUrl("/assets/graph/img/icon_Home/number_of_foreign_tourists_entering_the_country_daily.png").'"></div>
				<div class="box-text">จำนวนนักท่องเที่ยวต่างชาติที่เดินทางเข้าประเทศรายวัน</div>
				<div class="box-text link"><a href="https://www.google.co.th" target="_blank">อ่านต่อทั้งหมด></a></div>
				</div>
			
			<div class="TIC_SlideCard">
				<div class="box-img"><img src="'.$this->view->baseUrl("/assets/graph/img/icon_Home/occupancy_rate.png").'"></div>
				<div class="box-text">อัตราการเข้าพัก<br><span style="color:white;">#</span></div>
				<div class="box-text link"><a href="https://www.google.co.th" target="_blank">อ่านต่อทั้งหมด></a></div>
				</div>

			</div>
			<i id="TIC1Next" class="fa fa-angle-right"></i>
			<i id="TIC1Prev" class="fa fa-angle-left"></i>
		</div>
		';

		echo $script;
		echo $style;
		echo $html;

	}

}

?>