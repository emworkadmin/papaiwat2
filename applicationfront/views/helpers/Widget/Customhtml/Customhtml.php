<?php

class Zend_View_Helper_Widget_Customhtml_Customhtml extends Zend_View_Helper_Abstract
{
	protected $params = array();
	
	public function Widget_Customhtml_Customhtml($params)
	{
		$this->params = $params;
		
		return $this;
	}
	
	public function display() {
		echo $this->params["message"];
	}
}