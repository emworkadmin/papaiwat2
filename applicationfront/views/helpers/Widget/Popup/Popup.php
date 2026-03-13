<?php

class Zend_View_Helper_Widget_Popup_Popup extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Popup_Popup($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');

		// --------------------------------------------- Get Widget Item -------------------------------------------- //

		$selectWI = $db->select()
		->from(array('tbl' => 'popup_info'), '')
		->joinleft(array('d'=>'popup_detail'),'tbl.ID=d.ID','')
		->columns(array(
				"ID" => "tbl.ID",
				"CustomCss" => "tbl.CustomCss",

				"Width" => "tbl.Width",
				"Height" => "tbl.Height",
				"BackgroundColor" => "tbl.BackgroundColor",
				"Padding" => "tbl.Padding",

				"Detail"=>"d.detail"

		))
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted ='N' ")
		->where("d.Lang =?",$lang)
		->where("tbl.ID =?",$this->params['popup']);
		$stmtWI = $db->query($selectWI);
		$dataWI = $stmtWI->fetchObject();


		// ------------------------------------------------- Setting ------------------------------------------------ //

		$CustomCSS = empty($DisplaySettings['settingCustomCSS']) ? '' : $DisplaySettings['settingCustomCSS'];
		$csspopup=$dataWI->CustomCss;
		$Detailpopup=$dataWI->Detail;

		$Width=$dataWI->Width;
		$Height=$dataWI->Height;
		$BackgroundColor=$dataWI->BackgroundColor;
		$Padding=$dataWI->Padding;

		if(!$Width){
			$Width = "50%";
		}

		if(!$Padding){
			$Padding = "30px";
		}

		if(!$BackgroundColor){
			$BackgroundColor = "white";
		}


		//  print_r($csspopup);exit;
		// ---------------------------------------------- Present Data ---------------------------------------------- //

		// echo '<link rel="stylesheet" type="text/css" href="/assets/wPopup/css/styles.css">';
		// echo '<style type="text/
		//echo '<style>'.$CustomCSS.'</style>';

		//echo '<style> .w-popup img {width: 100%;} </style>';

		//echo '<meta name="viewport" content="width=device-width" />';
		echo '<div class="widget-kit-Popup">'; // div widget-kit-Popup
		echo '<div class="row">'; // div row
		
		echo '
		<div class="w-popup" style="z-index:1000;" id="popuptest">
			<div class="w-popupWindow">
				<div class="w-popup_close">
					<em class="fa fa-times" aria-hidden="true"></em>
				</div>
				<div class="w-popup_detail">' 
						.$Detailpopup.'
				</div>
			</div>
		</div>
		';
		echo '</div>'; // END: div widget-kit-Popup
		echo '</div>'; // END: div widget-kit-Popup
		
		echo '
				  <script>
					$(document).ready(function(){
					$("#popuptest").appendTo("body");
    				$( ".w-popup_close" ).click(function() {
                	$( ".w-popup" ).fadeOut( 1200 );
  					});
 					});
				  </script>
				  ';
		echo '<style>
		.w-popup {
			position:fixed;
			width:100%;
			height:100%;
			left:0px;
			right:0px;
			top: 0px;
			bottom:0px;
			margin:0;
			background-color:rgba(102,95,95,0.65);
			border-width: 10px;
			}';
		echo' 
		.w-popup .w-popupWindow .w-popup_detail{
			font-size:auto;
			font-weight:bold;
			color:green;
			min-width:100%;
			min-height:auto;
			}';
		echo '
		.w-popup .w-popupWindow .w-popup_detail img {
			border: 0;
			padding: '.$Padding.';
			height: '.$Height.';
		}';
		echo '
		.w-popup .w-popupWindow{
			background-color:'.$BackgroundColor.';
			width:'.$Width.';
			margin:0px auto;
			margin-top:80px;
			text-align: center;
		}
		.w-popup .w-popup_close{
			text-align: right;
			position: relative;
			top: -25px;
			right: -17px;
		}
		.w-popup .w-popup_close .fa{
			box-shadow: black 1px 1px;
			border-radius: 50%;
			background-color: white;
			width: 40px;
			border: 2px solid #000000;
			text-align: center;
			height: 40px;
			font-size: 26px;
		}
		.w-popup .w-popup_close .fa-times:before{
			position: relative;
			top: 5px;
		}
		.w-popup img {width: 100%;}
		';
		echo ''.$csspopup.'</style>';
		echo '<style>'.$CustomCSS.'</style>';
		
	}
	
}