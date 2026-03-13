<?php

class Zend_View_Helper_Widget_Share_Share extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Share_Share($params)
	{
		$this->params = $params;
		return $this;
	}
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		$addthisID=$this->params['addtihsID'];

		if (!empty($addthisID)) {
			// ---------------------------------------------- Present Data ---------------------------------------------- //
			// <!-- Go to www.addthis.com/dashboard to customize your tools -->
			echo '<script src="//s7.addthis.com/js/300/addthis_widget.js#pubid='.$addthisID.'"></script>';
		}
	}
	
}