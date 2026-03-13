<?php

class Zend_View_Helper_Widget_Watsearch_Watsearch extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Watsearch_Watsearch ($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		
		// Option
		$regionOption = $this->getOption("Wat_Model_Region", "getOption", $lang);
		$provinceOption = $this->getOption("Wat_Model_Province", "getOption", $lang);
		$watTypeOption = $this->getOption("Wat_Model_Type", "getOption", $lang);
		
		$divID = "wat-search-panel-" . uniqid();

		$html = '';
		$html .= '<div id="' . $divID . '" class="wat-search-panel">' . "\n";
		$html .= '	<form class="form-horizontal" method="get" action="' . $this->view->url(array("module" => "wat", "controller" => "search", "action" => "index"), null, true) . '">' . "\n";
		$html .= '		<div class="form-group">' . "\n";
		$html .= '			<div class="col-md-4 search-text">' . "\n";
		$html .= '				' . $this->view->formText("q", null, array("class" => "form-control", "placeholder" => Emwork_Translate::text($lang, "wat", "search_text_placeholder"))) . '' . "\n";
		$html .= '			</div>' . "\n";
		$html .= '			<div class="col-md-2 search-region">' . "\n";
		$html .= '				' . $this->view->formSelect("region", null, array("class" => "form-control"), Emwork_Utility::addEmptyOption($regionOption, Emwork_Translate::text($lang, "wat", "search_filter_region_select"))) .'' . "\n";
		$html .= '			</div>' . "\n";
		$html .= '			<div class="col-md-2 search-province">' . "\n";
		$html .= '				' . $this->view->formSelect("province", null, array("class" => "form-control"), Emwork_Utility::addEmptyOption($provinceOption, Emwork_Translate::text($lang, "wat", "search_filter_province_select"))) .'' . "\n";
		$html .= '			</div>' . "\n";
		$html .= '			<div class="col-md-2 search-type">' . "\n";
		$html .= '				' . $this->view->formSelect("type", null, array("class" => "form-control"), Emwork_Utility::addEmptyOption($watTypeOption, Emwork_Translate::text($lang, "wat", "search_filter_type_select"))) .'' . "\n";
		$html .= '			</div>' . "\n";
		$html .= '			<div class="col-md-2 search-button">' . "\n";
		$html .= '				<button type="submit" class="btn btn-block" id="btnSearch"><em class="fa fa-search"></em> ' . Emwork_Translate::text($lang, "wat", "search_button_text") .'</button>' . "\n";
		$html .= '			</div>' . "\n";
		$html .= '		</div>' . "\n";
		$html .= '	</form>' . "\n";
		$html .= '</div>' . "\n";
				
		$script = '';
		$script .= '<link rel="stylesheet" href="'. $this->view->baseUrl('assets/wat/css/wat.css') .'">' . "\n";
		$script .= '<link rel="stylesheet" href="'. $this->view->baseUrl('plugins/select2/select2.min.css') .'">' . "\n";
		$script .= '<script src="' . $this->view->baseUrl('plugins/select2/select2.min.js') . '"></script>' . "\n";
		$script .= '<script>' . "\n";
		$script .= 'jQuery(function(){' . "\n";
		$script .= '$("#' . $divID . '.wat-search-panel select#region").select2({ minimumResultsForSearch: -1});' . "\n";
		$script .= '$("#' . $divID . '.wat-search-panel select#type").select2({ minimumResultsForSearch: -1});' . "\n";
		$script .= '$("#' . $divID . '.wat-search-panel select#province").select2();' . "\n";
		$script .= '$("#' . $divID . '.wat-search-panel select#region").change(function(){' . "\n";
		$script .= '	var selectValue = $(this).val();' . "\n";
		$script .= '	if(selectValue == ""){selectValue = "-1";}' . "\n";
		$script .= '		getData(" ' . $this->view->url(array("module" => "wat", "controller" => "search", "action" => "getprovinces"), null, true) . '" + "?id=" + selectValue, "#' . $divID . '.wat-search-panel select#province", "' . Emwork_Translate::text($lang, "wat", "search_filter_province_select") . '", "name", "id", "");' . "\n";
		$script .= '});' . "\n";
		$script .= '});' . "\n";
		$script .= '</script>' . "\n";
		
		echo $html;
		echo $script;
	}
	
	private function getOption($className, $methodName, $lang) {
		$option = array();
		
		if (class_exists($className)) {
			$obj = new $className();
			if (method_exists($obj, $methodName)) {
				$option = $obj->$methodName($lang);
			}
		}
		
		return $option;
	}
}