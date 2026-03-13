<?php

class Zend_View_Helper_Widget_Tic6_Tic6 extends Zend_View_Helper_Abstract
{
    protected $db;
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic6_Tic6($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {

        // setup database
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
		$this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());

        $lang = Zend_Registry::get('lang');

		// ---------------------------------------------- Present Data ---------------------------------------------- //

        $script = '
            <script src="'. $this->view->baseUrl('assets/chartjs/Chart.min.js') . '"></script>
            <script>
                Chart.pluginService.register({
                    afterDatasetsDraw: function(chart) {
                        if (chart.config.options.showTooltips) {
                            var ctx = chart.ctx;
                            chart.data.datasets.forEach(function(dataset, i) {
                                var meta = chart.getDatasetMeta(i);
                                if (!meta.hidden) {
                                    meta.data.forEach(function(element, index) {
                                        ctx.fillStyle = "white";
                                        var fontSize = chart.config.options.showTooltips.fontSize || 10;
                                        var fontStyle = chart.config.options.showTooltips.fontStyle || "normal";
                                        var fontFamily = "Helvetica Neue";

                                        ctx.font = Chart.helpers.fontString(fontSize, fontStyle, fontFamily);

                                        dataset.data[index] += "";
                                        x = dataset.data[index].split(".");
                                        x1 = x[0];
                                        x2 = x.length > 1 ? "." + x[1] : "";
                                        var rgx = /(\d+)(\d{3})/;
                                        while (rgx.test(x1)) {
                                            x1 = x1.replace(rgx, "$1" + "," + "$2");
                                        }
                                        dataset.data[index] = x1 + x2;
                                        var txt= "";
                                        if(chart.config.options.showTooltips.showLabel) {
                                            txt = chart.data.labels[index] + " ";
                                        }
                                        var val = "";
                                        if(chart.config.options.showTooltips.showValue) {
                                            val = dataset.data[index].toString();
                                        }                        
                                        var dataString = txt + val + " %";
                                        ctx.textAlign = "center";
                                        ctx.textBaseline = "middle";
                                        var padding = 5;
                                        var position = element.tooltipPosition();
                                        ctx.fillText(dataString, position.x, position.y);
                                    });
                                }
                            });
                        }
                    }
                });
                            
                var TIC6_canvas = document.getElementById("TIC6_canvas");
                TIC6_canvas.height = 200;
                var myChart_TIC6_canvas = new Chart(TIC6_canvas, {
                    type: "doughnut",
                    data: {
                        // labels: ["ชาวไทย", "ชาวต่างประเทศ"],
                        datasets: [{
                            data: ['.number_format((2.7*100)/4.6,2).','.number_format((0.5*100)/4.6,2).','.number_format((0.5*100)/4.6,2).','.number_format((0.5*100)/4.6,2).','.number_format((0.4*100)/4.6,2).'],
                            backgroundColor: [
                                "#0071bd",
                                "#66cbc5",
                                "#52b2c0",
                                "#0492c2",
                                "#a9ddf3"
                            ],
                            borderColor: [
                                "#0071bd",
                                "#66cbc5",
                                "#52b2c0",
                                "#0492c2",
                                "#a9ddf3"
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        showTooltips: {
                            showLabel: false,
                            showValue: true,
                            fontSize: 14
                        },
                        responsive: true,
                        legend : {
                            display: false
                        },
                        tooltips: {
                            enabled: false
                        }
                    }
                });                
            </script>
            ';
        
        $style = '
            <style>
                #TIC6 .bg {
                    background: #999999;
                    display: inline-block;
                    padding: 15px 0;
                }
                #TIC6 .box-title {
                    color: white;
                }
                #TIC6 .box-title img {
                    width: 25px;
                    margin-right: 5px;
                }
                #TIC6 .box-detail {
                    padding-left: 30px;
                    min-height: 200px;
                }
                #TIC6 .box-detail .detail-title {
                    color: #faed22;
                    padding-left: 15px;
                }
                #TIC6 .box-detail ul {
                    color: white;
                    list-style-type: disc;
                    padding-left: 35px;
                }
                #TIC6 .box-detail ul div {
                    padding: 0;
                }   
                #TIC6 .box-chart {
                    min-height: 200px;
                }
                #TIC6 .seeAll {
                    // text-align: center;
                    padding-left: 15px;
                }
                #TIC6 .seeAll a {
                    padding: 0px 8px 0px 8px;
                    background: #3c8dbc;
                    border-radius: 5px;
                    color: white;
                    cursor: pointer;
                }
                #TIC6 .seeAll a>sub {
                    bottom: 0;
                }
                #TIC6 .sub-detail {
                    color: white;
                    padding-left: 15px;
                }
                #TIC6 .footer {
                    text-align: right;
                }
                #TIC6 .footer a {
                    color: white;
                    cursor: pointer;
                }
            </style>
        ';

        $html = '
        <div class="col-md-12 TIC6" id="TIC6">
            <div class="bg">

                <div class="box-title col-md-12">
                    <img alt="image" src="'. $this->view->baseURL('assets/graph/img/icon_Home/visitor_statistics.png') .'">สถิติผู้เดินทางเข้า-ออกประเทศแบ่งตามสัญชาติ
                </div>

                <div class="box-detail col-md-6">
                    <div class="detail-title">
                        <u>รายวัน</u>
                    </div>
                    <div>
                        <ul>
                            <li><div class="col-md-9">สัญชาติจีน</div><div class="col-md-3">'.number_format((2.7*100)/4.6,2).'%</div></li>
                            <li><div class="col-md-9">สัญชาติญี่ปุ่น</div><div class="col-md-3">'.number_format((0.5*100)/4.6,2).'%</div></li>
                            <li><div class="col-md-9">สัญชาติเกาหลีใต้</div><div class="col-md-3">'.number_format((0.5*100)/4.6,2).'%</div></li>
                            <li><div class="col-md-9">สัญชาติสหรัฐอเมริกา</div><div class="col-md-3">'.number_format((0.5*100)/4.6,2).'%</div></li>
                            <li><div class="col-md-9">สัญชาติอินเดีย</div><div class="col-md-3">'.number_format((0.4*100)/4.6,2).'%</div></li>
                        </ul>
                    </div>
                    <div class="seeAll">
                        <a href="/graph/travelstatistics/index" target="_blank"><sub>อ่านต่อทั้งหมด</sub></a>
                    </div>
                    <!--div class="sub-detail">
                        <sub>อัพเดทข้อมูลล่าสุด วันที่ 19 มกราคม 2561</sub>
                    </div-->
                </div>

                <div class="box-chart col-md-6 col-sm-12 col-xs-12">
                    <canvas id="TIC6_canvas" style="position: absolute; top: 50%; transform: translateY(-50%);"></canvas>
                </div>

                <div class="footer col-md-12 col-sm-12 col-xs-12">
                    <a href="/graph/travelstatistics/index" target="_blank"><sub>ดูสถิติผู้เดินทางเข้าออกประเทศทั้งหมด ></sub></a>
                </div>

            </div>
        </div>';

        echo $style;
        echo $html;
        echo $script;
    }

}

?>