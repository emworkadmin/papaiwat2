<?php

class Zend_View_Helper_Widget_Tic8_Tic8 extends Zend_View_Helper_Abstract
{
    protected $db;
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic8_Tic8($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {

        // setup database
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
        $this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());
        
        $lang = Zend_Registry::get('lang');
        
		// $maxYear = '2015';
		$maxYearC = $this->getMaxYearFactConfidenceIndex();
		$maxYearO = $this->getMaxYearFactTCCIOverall();

        
        $confidenceIndex = $this->getConfidenceIndex($maxYearC);
        $overallRank = $this->getOverallRank($maxYearO);
        
		// ---------------------------------------------- Present Data ---------------------------------------------- //

		$script = '';

        $style = '
        <style>
		.Tic8Widget {font-family: "Mitr Light";} 
		.Tic8Widget .header {background: linear-gradient(to right, #29a8df, #2f4d96); padding: 12px 0px; text-align: center; font-size: 20px; color: #fff; font-weight: bold;}
		.Tic8Widget .body {background-color: #F2F2F2; padding: 15px;}
		.Tic8Widget .body .box {border: 1px solid #186E9D; padding: 7px; background-color: #fff; margin-bottom: 15px;}
		.Tic8Widget .body .box:last-child {margin-bottom: 0px;}
		.Tic8Widget .body .box .img {float: left; width: 120px; }
		.Tic8Widget .body .box .img img {width: 100%;}
		.Tic8Widget .body .box .info {text-align: center; margin-left: 135px;}
		.Tic8Widget .body .box .title {color: #186e9d;}
		.Tic8Widget .body .box .data-amount {font-family: "Kanit"; font-size: 56px;}
		.TIC8.TIC-widget-footer {
			font-size: 14px;
			text-align: right;
		}
        </style>
        ';
        
        $html  = '';
        
        $html .= '<div class="Tic8Widget">';
        $html .= '<div class="header" >ข้อมูลการลงทุนด้านการท่องเที่ยว</div>';
        $html .= '<div class="body">';
        
		$html .= '<div class="box"><div class="img"><img src="' . $this->view->baseUrl("assets/graph/img/icon_Home/icon_index.png") . '" alt="icon-index" /></div><div class="info"><div class="title">ดัชนีความเชื่อมั่นการท่องเที่ยว</div><div class="data-amount"><span>' . number_format($confidenceIndex, 1) . '</span></div></div><div class="TIC8 TIC-widget-footer">ที่มา : สภาอุตสาหกรรมการท่องเที่ยวแห่งประเทศไทย</div><div class="TIC8 TIC-widget-footer">ข้อมูลอัพเดตล่าสุด ณ ปี '.($maxYearC+543).'</div></div>';
        
        $html .= '<div class="box"><div class="img"><img src="' . $this->view->baseUrl("assets/graph/img/icon_Home/icon_rank.png") . '" alt="icon-rank" /></div><div class="info"><div class="title">อันดับความสามารถของประเทศไทย<br />ในการแข่งขันด้านการท่องเที่ยวระดับสากล</div><div class="data-amount"><span>' . number_format($overallRank, 0) . '</span></div></div><div class="TIC8 TIC-widget-footer">ที่มา : World Economic Forum</div><div class="TIC8 TIC-widget-footer">ข้อมูลอัพเดตล่าสุด ณ ปี '.($maxYearO+543).'</div></div>';
        
		$html .= '</div>
        </div>';
        
        

		echo $script;
		echo $style;
		echo $html;

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
	
	private function getMaxYearFactConfidenceIndex() {
// 		$selectMax = $this->db->select()
// 		->from(array("F" => "Investment.FactConfidenceIndex"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
// 		$stmtMax = $this->db->query($selectMax);
// 		$dataMax = $stmtMax->fetchObject();
// 		$year = $dataMax->year;
// 		return $year;

		$sql = "select max(substring(convert(nvarchar, F.DateKey), 1, 4)) Y
						from [Investment].[FactConfidenceIndex] F
						inner join [Investment].[DimConfidenceSubType] XD on F.ConfidenceSubTypeID = XD.ID
						where F.EstimationTypeID = 2 and XD.SubTypeNameEN = 'Overall Index' ";
		$stmt = $this->db->query($sql);
		$dataObj = $stmt->fetchObject();
		
		$data = empty($dataObj->Y) ? date("Y") : $dataObj->Y;
		
		return $data;
	}

	private function getMaxYearFactTCCIOverall($year) {
		$selectMax = $this->db->select()
		->from(array("F" => "Competitive.FactTCCIOverall"), array("year"=>"max(substring(convert(nvarchar, F.DateKey), 1, 4))"));
		$stmtMax = $this->db->query($selectMax);
		$dataMax = $stmtMax->fetchObject();
		$year = $dataMax->year;
		return $year;
	}

}

?>