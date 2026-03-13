<?php

class Zend_View_Helper_Widget_Fontsize_Fontsize extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Fontsize_Fontsize($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		
		//$request = new Zend_Controller_Request_Http();
		//$cFontSize = $request->getCookie('font_size');
		echo '<style>';
		echo '#addsize { font-size: 15px;cursor: pointer;}
				#addsize .fa-plus{ font-size: 10px;}
				#addsize:hover, #addsize:active {color: #a0a0a0;}
				#minussize { font-size: 15px;cursor: pointer;}
				#minussize .fa-minus{ font-size: 10px;margin-right:10px;}
				#minussize:hover, #minussize:active {color: #a0a0a0;}
				</style>';
		// ---------------------------------------------- Present Data ---------------------------------------------- //
		echo '<div class="navbar-right">';
		echo '<div class="sizefont" id="formfontsize">';
		echo '<span id="minussize"><em class="fa fa-font"></em><em class="fa fa-minus"></em></span><span id="addsize"><em class="fa fa-font"></em><em class="fa fa-plus"></em></span>';
		echo '<script>';
		echo 'jQuery(function(){';
		echo '	
				var min=6;
				var max=14;
				$("#addsize").click(function(){
					var code = $("html").css("font-size");
					code=code.split("px");
					code=parseInt(code[0]);
					if(code<max){
						code=code+2;
						//document.cookie="font_size="+code;
						$.cookie("font_size", code, {path: "/"});
					}
					$("html").css("font-size",code+"px");					
				});	
				$("#minussize").click(function(){
					var code = $("html").css("font-size");
					code=code.split("px");
					code=parseInt(code[0]);	
					if(code>min){
						code=code-2;
						//document.cookie="font_size="+code;
						$.cookie("font_size", code,  {path: "/"});
					}	
					$("html").css("font-size",code+"px");		
				});	
			});';
		echo '</script>';
		echo '</div>';
		echo '</div>';
	}
	
}