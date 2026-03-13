<?php

class Zend_View_Helper_Widget_Case_Case extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Case_Case($params)
	{
		$this->params = $params;
		return $this;
    }

	public function display() {	

		$dataSum=$this->getData();
		$record=empty($dataSum[0]['RECORD']) ? 0:$dataSum[0]['RECORD'];

		$lang = Zend_Registry::get('lang');
		// ---------------------------------------------- Present Data ---------------------------------------------- //
		$style ='
		<style>
			.infocardcase {
				background:#c7c7c7;
				padding:10px;
				border-radius: 10px;
				margin-bottom:30px;
			}
			.infocardcase .caseHead {
				font-weight: bold;
			}
			.pattern {
				margin-top: 65px;
			}
			.head-case {
				background: red;
				color: #fff;
				padding: 6px;
			}
			.texthead {
				font-size: 2.5rem;
				font-weight: bold;
			}
			.head{
				background: #fff;
				line-height: 42px;
			}
			#slideCase {
				padding: 25px 0;
			}
			.slick-dots {
				z-index: 1;
			}
			.backGround-Case {
				overflow: hidden;
			}
			.Amount {
				margin-left: 15px;
			}
			.CaseAmount {
				background: #f0f0f0;
				line-height: 35px;
			}
			.Fordata {
				float: left;
			}
			.Nodata {
				margin-left: 15px;
			}
			#viewAllCase {
				padding: 5px;
				background: #c3c3c3;
				color: #38acff;
				border-radius: 20px;
				line-height: 18px;
				margin: 6px;
				cursor: pointer;
			}
		</style>' . "\n";

		$html  ='<div class="backGround-Case">';
		$html .='	<div class="Case">';
		$html .='		<div class="head">';
		$html .='			<span class="head-case"><em class="fa fa-newspaper-o"></em></span>';	
		$html .='			<span class="texthead"> '. Emwork_Translate::text($lang,'wcase','title') .'</span>';
		$html .='			<div class="pull-right plusbox">';
		$html .='				<a class="fa fa-plus" id="viewAllCase" href="'.$this->view->baseUrl('th/case/category').'" target="_blank"></a>';	
		$html .='			</div>';	
		$html .='		</div>';
		$html .='		<div class="CaseAmount">';
		$html .='			<span class="Amount">'.Emwork_Translate::text($lang,'wcase','amount_case').' = '.$record.'</span>';		
		$html .='		</div>';

		$html .='		<div class="row" id="slideCase">';
						if($record>0):
						for($i=1;$i<=ceil($record/9);$i++):
							$data=$this->getData($i);
		$html .='				<div class="Fordata">';
								if(count($data)>0):
									foreach ($data as $item) :
										$blackNo=$item['BlackCasePrefix'].$item['BlackCaseNo'].'/'.$item['BlackCaseYear'];
										$trialReason=$item['TrialReason'];
										$trialRoom=$item['TrialRoom'];
										$trialDate=Emwork_Utility_DateTime::MSSqlToDateThai($item['TrialDate']);
										$trialtime=$item['TrialDate']->format('H:i');
										$subpoenaType=$item['SubpoenaType'];
										$injunction=$item['Injunction'];
		$html.=' 					<div class="col-md-4 col-sm-6 col-xs-12 case-card-list">
										<div class="infocardcase">
											<div class="caseHead">
												'.$blackNo.'
											</div>
											<div class="row">
												<div class="col-md-4">
													<span class="caseHead">'.Emwork_Translate::text($lang,'wcase','remark').'</span>
												</div>
												<div class="col-md-8">
													'.$trialReason.'
												</div>
											</div>
											<div class="row">
												<div class="col-md-4">
													<span class="caseHead">'.Emwork_Translate::text($lang,'wcase','room_case').'</span>
												</div>
												<div class="col-md-8">
													'.$trialRoom.'
												</div>
											</div>
											<div class="row">
												<div class="col-md-4">
													<span class="caseHead">'.Emwork_Translate::text($lang,'wcase','date_case').'</span>
												</div>
												<div class="col-md-8">
													<span>'.$trialDate.'&nbsp;'.Emwork_Translate::text($lang,'wcase','time').'&nbsp;'.$trialtime.Emwork_Translate::text($lang,'wcase','type_time').'</span>
												</div>
											</div>
											<div class="row">
												<div class="col-md-4">
													<span class="caseHead">'.Emwork_Translate::text($lang,'wcase','result_letter').'</span>
												</div>
												<div class="col-md-8">
													'.$subpoenaType.'
												</div>
											</div>
											<div class="row">
												<div class="col-md-4">
													<span class="caseHead">'.Emwork_Translate::text($lang,'wcase','command_case').'</span>
												</div>
												<div class="col-md-8">
													'.$injunction.'
												</div>
											</div>
										</div>
									</div>';
									endforeach;
								endif;
		$html .='				</div>';
						endfor;
						else:
		$html .='			<div class="Nodata col-md-12">
								'.Emwork_Translate::text($lang,'wcase','no_data').'
							</div>';
						endif;
		$html .='		</div>';										
		$html .='	</div>';
		$html .='</div>';

		$script = '<link rel="stylesheet" href="'.$this->view->baseUrl('plugins/slick/slick-theme.css').'">
					<link rel="stylesheet" href="'.$this->view->baseUrl('plugins/slick/slick-theme.css').'">
					<script src="'.$this->view->baseUrl('plugins/slick/slick.min.js').'"></script>';
		$script .='<script>';
		$script .= '	jQuery(function(){';
		$script .= '		$("#slideCase").slick({
								dots: true,
								arrows: false,
								infinite: true,
								slidesToShow: 1,
								autoplay:false,
							});
						});
					';
		$script .='</script>';
		echo $style;
		echo $script;
		echo $html;
	}

	private function getData ($page){	
		// setup database
		$config = new Zend_Config_Ini(APPLICATION_ROOT . DS .'applicationadmin'. DS .'modules' . DS . 'case' . DS . 'configs' . DS . 'config.ini', 'database');
		$db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());	

		// ---------------------------------------------- Setting Data ---------------------------------------------- //
		$setting = new stdClass();
		$setting->SettingCourtCode = System_Model_Setting::getValue("CaseCourtCode", "Case");

		// TODO: Prepare Data
		$today=date("Y-m-d");
		$select = $db->select()
		->from(array('tbl' => 'coj_case'), "")
		->columns(array(
				"ID" => "tbl.ID",
				"BlackCasePrefix" => "tbl.BlackCasePrefix",
				"BlackCaseNo" => "tbl.BlackCaseNo",
				"BlackCaseYear" => "tbl.BlackCaseYear",
				"RedCasePrefix" => "tbl.RedCasePrefix",
				"RedCaseNo" => "tbl.RedCaseNo",
				"RedCaseYear" => "tbl.RedCaseYear",
				"TrialDate" => "tbl.TrialDate",
				"TrialRoom" => "tbl.TrialRoom",
				"TrialReason" => "tbl.TrialReason",
				"SubpoenaType" => "tbl.SubpoenaType",
				"Injunction" => "tbl.Injunction",
				"CourtCode" => "tbl.CourtCode",
		))
		->where("tbl.IsDeleted = ?",'N')
		->where("DATE(tbl.TrialDate) >= ?",$today)
		->order("DATE(tbl.TrialDate) ASC")
		->limit("27") 
		;
		if(!empty($setting->SettingCourtCode)){
			$select=$select->where("tbl.CourtCode = ?",$setting->SettingCourtCode);
		}
		$records = $this->getListCount($select);

		$select = $db->select ()
		->from ( array ('tbs' => $select ), array ('*'))
		->columns ( array ("RECORD" => "abs($records)" ) )
		->limitPage ($page, 9)
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchAll();

		if (count($data) > 0) {
			for ($i = 0; $i < count($data); $i ++) {
				$data[$i]["TrialDate"]  = empty($data[$i]["TrialDate"]) ? null : new DateTime($data[$i]["TrialDate"]);
			}
		}
		return $data;
	} 
	function getListCount($select) {
		// setup database
		$config = new Zend_Config_Ini(APPLICATION_ROOT . DS .'applicationadmin'. DS .'modules' . DS . 'case' . DS . 'configs' . DS . 'config.ini', 'database');
		$db = Zend_Db::factory($config->db->adapter, $config->db->params->toArray());

		$select = $db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
}

?>