<?php

class Emwork_Form_Form {

	public function __construct($options = null) {

	}

	public function _getSelectOption($name, $option, $selected = "", $attr = "", $label = "", $class = "") {

		if (!empty($label)) {
			$html[] = '<label class = "lbl' . $name . '" for = "lst' . $name . '" >' . $label . '</label>';
		}
		$html[] = '<select class = "' . $class . '" id = "lst' . $name . '" name = "lst' . $name . '" ' . $attr . ' >';
		if ($option) {
			foreach ($option as $key => $value):
			$seletedText = '';
			if ($key == $selected)
				$seletedText = ' selected = "selected" ';
			$html[] = '<option value = "' . $key . '" ' . $seletedText . ' >' . $value . '</option>';
			endforeach;
		}
		$html[] = '</select>';
		return implode("", $html);
	}

	public function _getRDOOption($name, $option, $selected = '', $attr = '', $class = '') {
		//'.$attr.'
		$count = 1;
		foreach ($option as $key => $value):
		$seletedText = '';
		if ($key == $selected)
			$seletedText = ' checked = "checked" ';

		$html[] = '<div ' . $class . ' >';
		$html[] = '<input class = "checkbox " id = "rdl' . $name . ($count) . '" name = "rdl' . $name . '" type = "radio" value = "' . $key . '" ' . $seletedText . ' />';
		$html[] = '<label name = "lbl' . $name . '"  for = "rdl' . $name . ($count++) . '" >&nbsp;' . $value . '</label>';
		$html[] = '</div>';
		endforeach;
		return implode("", $html);
	}

	public function _getDatePicker($name, $label, $class = "") {
		$html[] = '<div>';
		if (!empty($label))$html[] = '<label class = "lbl' . $name . '" for = "txt' . $name . '">' . $label . '</label>';
		$html[] = '<input name = "txt' . $name . '" type = "text" class = "datepicker ' . $class . '" id = "txt' . $name . '" >';
		$html[] = '</div>';
		return implode("", $html);
	}

	/**
	 * Generate Checkbox
	 *
	 * @access public
	 * @param checkboxname ,
	 * @param svalue ,
	 * @param checked ,
	 * @param label ,
	 * @param classname
	 * @return checkboxhtml
	 */
	public function _getCheckBox($name,$value,$checked ,$label,$class , $attr){
		$checkedTxt='';
		if( !empty($checked)) $checkedTxt = ' checked ';
		 
		$html[] = '&nbsp;<input '.$checkedTxt.' '. $attr .' value = "'.$value.'" name = "chk' . $name . '" type = "checkbox" id = "chk' . $name . '" >';
		if (!empty($label))
			$html[] = '<label class = "lbl' . $name .' '. $class . '" for = "chk' . $name . '">&nbsp;' . $label . '</label>';


		return implode("", $html);
	}

}

?>
