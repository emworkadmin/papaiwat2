<?php

class Zend_View_Helper_Widget_Search_Search extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Search_Search($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');

		// // --------------------------------------------- Get Widget Item -------------------------------------------- //

		// echo '<style>'.$CustomCSS.'</style>';

		echo '<div class="widget-kit-Search">'  . "\n";
		
		echo '<form class="searchPanel form-horizontal" id="formWidgetSearch" method="GET" action="' . $this->view->url(array('module' => 'search', 'controller' => 'search', 'action' => 'index'), null, true) . '">'  . "\n";

		echo '<div class="form-group">'  . "\n";
		echo '	<div class="col-md-12">'  . "\n";
		echo '		<div class="input-group">'  . "\n";


		echo '			<input type="text" name="text" id="textsearch" value="" class="form-control required valid" placeholder="'.Emwork_Translate::text($lang, 'wsearch', 'search_placeholder').'" title="'.Emwork_Translate::text($lang, 'wsearch', 'search_placeholder').'" aria-required="true" aria-invalid="false">'  . "\n";
		echo '			<span class="input-group-btn">'  . "\n";
		echo '			<button type="submit" class="btn btn-primary" id="btnSearch">'  . "\n";
		echo '			<em class="fa fa-search"></em> <span class="hidden-xs">'.Emwork_Translate::text($lang, 'general', 'button_search').'</span>'  . "\n";
		echo '			</button>'  . "\n";
		echo '			</span>'  . "\n";

		echo '		</div>'  . "\n";
		echo '	</div>'  . "\n";
		echo '</div>'  . "\n";

		echo '</form>'  . "\n";

		echo '</div>'  . "\n";

// 		echo "<script>" . "\n";
// 		echo "jQuery(function(){" . "\n";
// 		echo "	$('#btnSearch').click(function() {" . "\n";
// 		echo "$('#formSearch #textsearch').val();" . "\n";
// 		//	$this->url(array('module' => 'search', 'controller' => 'search', 'action' => 'index'), null, true);
// 		// echo " window.location = $this->view->url(array('module' => 'content', 'controller' => 'category', 'action' => 'index'), null, true), null, true ";
// 		echo "		window.location = '".$this->view->url(array('module' => 'search', 'controller' => 'search', 'action' => 'index'), null, true)."?text="."' + $('#formSearch #textsearch').val()" . "\n";
// 		echo "	});" . "\n";
// 		echo "});" . "\n";
// 		echo "</script>" . "\n";
		
	}
}