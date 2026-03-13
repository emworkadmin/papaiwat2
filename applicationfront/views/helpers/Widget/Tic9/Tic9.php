<?php

class Zend_View_Helper_Widget_Tic9_Tic9 extends Zend_View_Helper_Abstract
{
    protected $db;
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic9_Tic9 ($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {

        // setup database
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
        $this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());
        
        $lang = Zend_Registry::get('lang');
        
        // TODO: Find Max Year
        $uniqid = uniqid();
        
        $dataForeignInvestment04 = $this->getForeignInvestment04();
        $dataForeignInvestment04 = array_reverse($dataForeignInvestment04);
        if(count($dataForeignInvestment04)>0){
        	foreach($dataForeignInvestment04 as $k=>$v){
        		if(!empty($v['Amount'])){
        			$dataForeignInvestment04[$k]['Amount'] = $v['Amount'] / 1000000;
        		}
        	}
        }
        
        
        
		// ---------------------------------------------- Present Data ---------------------------------------------- //

		$script = '
<script src="' . $this->view->baseUrl('assets/chartjs/Chart.min.js') . '"></script>
<script>
	var ChartWidget9_' . $uniqid . ' = document.getElementById("ChartWidget9_' . $uniqid . '").getContext("2d");
    var myChartWidget9_' . $uniqid . '  = new Chart(ChartWidget9_' . $uniqid . ', {
        type: "bar",
        data: {
            labels: [';
		if (count($dataForeignInvestment04) > 0) {
			$index = 1;
			$loop = count($dataForeignInvestment04);
			foreach ($dataForeignInvestment04 as $v) {
				$script .= "'".$v['Y']."'";
				if($index != $loop){
					$script .= ",";
				}
				$index ++;
			}
		}
		$script .= '
            ],
            datasets: [{
                backgroundColor: "#2F4E97",
                borderColor: "#2F4E97",
                data: [';
                
		if(count($dataForeignInvestment04)>0) {
			$index = 1;
			$loop = count($dataForeignInvestment04);
			foreach($dataForeignInvestment04 as $v){
				$script .= "'".$v['Amount']."'";
				if($index != $loop){
					$script .= ",";
				}
				$index ++;
			}
		}
		$script .= '
                ],
            }]
        },
        options: {
            hover: {
                animationDuration: 0
            },
            animation: {
                duration: 1,
                onComplete: function() {
                    var chartInstance = this.chart,
                    ctx = chartInstance.ctx;
                    ctx.font = "10px",
                    ctx.textAlign = "center";
                    ctx.textBaseline = "bottom";
                    ctx.font = Chart.helpers.fontString(10, "normal", "Tahoma");
                    this.data.datasets.forEach(function(dataset, i) {
                        var meta = chartInstance.controller.getDatasetMeta(i);
                        meta.data.forEach(function(bar, index) {
                            var data = dataset.data[index];
                            data = data.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1,")
                            ctx.fillText(data, bar._model.x, bar._model.y);
                        });
                    });
                }
            },
            legend: {
                display: false
            },
            tooltips: {
                enabled: true,
                backgroundColor : "rgb(234, 234, 234)",
                titleFontColor : "black",
                bodyFontColor : "black"
            },
            responsive: true
        }
    });
</script>';

        $style = '
        <style>
		.Tic9Widget {font-family: "Mitr Light";} 
		.Tic9Widget .header {background-color: #999999; padding: 12px 0px; text-align: center; font-size: 20px; color: #fff; font-weight: bold;}
        .Tic9Widget .body {background-color: #fff; padding: 15px;}
        .TIC9.TIC-widget-footer {
            font-size: 16px;
            text-align: right;
        }
        </style>
        ';
        
        $html  = '';        
        $html .= '<div class="Tic9Widget">';
        $html .= '<div class="header" >แนวโน้มการลงทุนจากต่างประเทศ รายปี (ล้านบาท)</div>';
        $html .= '<div class="body">';        
        $html .= '<canvas id="ChartWidget9_' . $uniqid . '"></canvas>';
        $html .= '<div class="TIC9 TIC-widget-footer">ที่มา : ธนาคารแห่งประเทศไทย</div>';
        $html .= '<div class="TIC9 TIC-widget-footer">ข้อมูลอัพเดตล่าสุด ณ ปี 2559</div>';
        $html .= '</div>';
        $html .= '</div>';

		echo $style;
		echo $html;
		echo $script;

    }
    
    function getForeignInvestment04() {
		$data = 0;
		if ($this->db == null) {
			return $data;
		}
		try {

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
			->order("Y desc") // เพิ่มเติม
			->limit("5")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch ( Exception $e )  {
			
		}
		return $data;
	}

}

?>