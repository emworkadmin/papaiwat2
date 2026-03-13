<?php

class Zend_View_Helper_Widget_Tic7_Tic7 extends Zend_View_Helper_Abstract
{
    protected $db;
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tic7_Tic7($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {

        // setup database
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'modules' . DS . 'graph' . DS . 'configs' . DS . 'config.ini', 'database');
        $this->db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());
        
        $lang = Zend_Registry::get('lang');
        
        $ExchangeRates = $this->getExchangeRates(null);
        // echo '<pre>'; print_r($ExchangeRates); echo '</pre>'; exit;

		// ---------------------------------------------- Present Data ---------------------------------------------- //

		$script = '';

        $style = '
        <style>
        .Tic7_table, .Tic7_table table {
            text-align: center;
        }
        #widget {
            border-collapse: collapse;
            width: 100%;
            font-family: "Mitr Light";
        }
        .Tic7_table td {
            font-family: "Mitr Light";
            border-bottom: 1px solid;
            border-color: #ddd;
            background-color: #f2f2f2;
            padding-top: 5px;
            padding-bottom: 5px;
            font-size: 16px;
            font-weight: normal;
        }
        .Tic7_table tr.head {
            color: #00c0ef;
            border-bottom: 3px solid;
            border-color: #ccc;
            font-weight: normal;
        }
        .Tic7_table tr.head td {
            font-family: "Mitr Light";
            font-size: 16px;
            width: 1%;
            font-weight: normal;
        }
        .Tic7_table tr.footer-table td {
            font-family: "Mitr Light";
            font-size: 13px;
            font-weight: normal;
            padding: 7px 0;
        }
        .Tic7_table tr.footer-table td a {
            font-family: "Mitr Light";
            font-size: 17px;
            font-weight: normal;
            color: #000;
        }   
        .Tic7_table tr.footer-table td.link {
            text-align: right;
            padding: 10px;
        }
        .Tic7_table #widget th {
            font-size: 20px;
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: center;
            background: linear-gradient(to right, #29a8df, #2f4d96);
            color: white;
        }
        @media (max-width: 440px) {
            .Tic7_table tr.footer-table td {
                font-size: 10px;
            }
        }
        @media (max-width: 370px) {
            .Tic7_table tr.footer-table td {
                font-size: 8px;
            }
        }
        </style>
        ';
        
        $html  = '';

        $html .= '
        <div class="Tic7_table">
            <table id="widget">

            <thead class="head">
                <tr >
                    <th colspan="3">อัตราแลกเปลี่ยนเงินตราระหว่างประเทศ</th>
                </tr>
            </thead>
            
            <tbody>
                <tr class="head">
                    <td>ประเทศ</td>
                    <td>สกุลเงิน</td>
                    <td>อัตราแลกเปลี่ยน</td>
                </tr>
        ';

        if(count($ExchangeRates)>0){
            foreach($ExchangeRates as $v){

        $html .= '
                <tr>
                    <td>'.$v['CountryNameEN'].'</td>
                    <td>'.$v['Currency'].'</td>
                    <td>'.number_format($v['Amount'],4).'</td>
                </tr>
        ';

            }
        }

        $html .= '
                <tr class="footer-table">
                    <td>
                        
                    </td>
                    <td colspan="2" class="link"><a href="/graph/investment/index" target="_blank"><sub>ดูอัตราแลกเปลี่ยนเงินตราต่างประเทศทั้งหมด ></sub></a></td>
                </tr>
            </tbody>
            </table>
        </div>
        ';
        
        $html_bakup = '
        <div class="Tic7_table">
            <table id="widget">

            <thead class="head">
                <tr >
                    <th colspan="3">อัตราแลกเปลี่ยนเงินตราระหว่างประเทศ</th>
                </tr>
            </thead>
            
            <tbody>
                
                <tr class="head">
                    <td>สกุลเงิน</td>
                    <td>ราคารับซื้อ</td>
                    <td>ราคาขาย</td>
                </tr>

            
                <tr>
                    <td>USA</td>
                    <td>'.number_format(35.32,2).'</td>
                    <td>'.number_format(35.34,2).'</td>
                </tr>
                <tr>
                    <td>EUR</td>
                    <td>'.number_format(44.27,2).'</td>
                    <td>'.number_format(44.57,2).'</td>
                </tr>

                <tr>
                    <td>GBP</td>
                    <td>'.number_format(58.51,2).'</td>
                    <td>'.number_format(58.46,2).'</td>
                </tr>
                
                <tr>
                    <td>CNY</td>
                    <td>'.number_format(4.98,2).'</td>
                    <td>'.number_format(4.99,2).'</td>
                </tr>
                
                <tr>
                    <td>JPY</td>
                    <td>'.number_format(34.17,2).'</td>
                    <td>'.number_format(34.42,2).'</td>
                </tr>

                <tr>
                    <td>RUB</td>
                    <td>'.number_format(1.08,2).'</td>
                    <td>'.number_format(1.10,2).'</td>
                </tr>                

                <tr class="footer-table">
                    <td>อัตเดตข้อมูลล่าสุด วันที่ 19 มกราคม 2561</td>
                    <td colspan="2" class="link"><a href="/graph/investment/index" target="_blank"><sub>ดูอัตราแลกเปลี่ยนเงินตราต่างประเทศทั้งหมด ></sub></a></td>
                </tr>
            </tbody>
            </table>
        
        </div>
		';

		echo $script;
		echo $style;
		echo $html;

    }
    
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
            ->where("C.CountryNameEN in ('United States','European Union','United Kingdom','China','Japan','Russia')")
			->group("DC.Currency")
			->group("DC.CurrencySequence")
			->group("C.CountryNameTH")
			->group("C.CountryNameEN")
            ->order("(CASE C.CountryNameEN WHEN 'United States' THEN 1 WHEN 'European Union' THEN 2 WHEN 'United Kingdom' THEN 3 WHEN 'China' THEN 4 WHEN 'Japan' THEN 5 WHEN 'Russia' THEN 6 END)")
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

?>te)",
				"DC.Currency",
				"DC.CurrencySequence",
				"C.CountryNameTH",
				"C.CountryNameEN"
			))
            ->where("DateKey like '{$year}%'")
            ->where("C.CountryNameEN in ('United States','European Union','United Kingdom','China','Japan','Russia')")
			->group("DC.Currency")
			->group("DC.CurrencySequence")
			->group("C.CountryNameTH")
			->group("C.CountryNameEN")
            ->order("(CASE C.CountryNameEN WHEN 'United States' THEN 1 WHEN 'European Union' THEN 2 WHEN 'United Kingdom' THEN 3 WHEN 'China' THEN 4 WHEN 'Japan' THEN 5 WHEN 'Russia' THEN 6 END)")
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