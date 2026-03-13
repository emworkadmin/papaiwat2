<?php
Class Emwork_Button_Button {

	public $script = array();
	private $otherButton = array();
	private $printButton = array();

	function __construct() {
		unset($this->otherButton);
		unset($this->printButton);
	}

	public function setSaveScript($script){
		$this->script['save'] .= $this->script['save'].' '.$script;
		if(!$this->checkNotNull($this->linkPath['save'])) {
			$this->script['save'] .= '$(".btnSave").button({icons: {primary: "ui-icon-disk"}});';
		}
	}

	public function setSaveTitle($title){
		$this->title['save'] = $title;
	}

	function OtherButton($linkPath, $title, $script, $cssClass){
		if(empty($title)) {
			$title = "(ไม่มีชื่อ)";
		}

		if (empty($cssClass)) {
			$cssClass = "btnOhter_" . count($this->$otherButton);
		}

		if (empty($script)) {
			$script = "jQuery('.$cssClass').button({icons: {primary: 'ui-icon-plus'}});";
		}
		$button = array();
		$button["title"] = $title;
		$button["cssClass"] = $cssClass;
		$button["script"] = $script;
		$this->otherButton[] = $button;
	}

	public function CustomButton($linkPath,$title ,$script,$icon){
		if($this->checkNotNull($title)) {
			$this->title['custom'] = $title;
		}

		if($this->checkNotNull($linkPath)) {
			$this->linkPath['custom'] =$linkPath;
			//$this->script['custom'] .= '$(".btnCustom").click(function(){ window.location="'.$linkPath.'"  }););';
		}

		if($this->checkNotNull($script))
			$this->script['custom'] .= $script;

	}

	function PrintButton($linkPath, $title ,$script, $cssClass = "btnPrint", $popupName = "print"){
		$printID = count($this->printButton);

		if(empty($title)) {
			$title = "พิมพ์";
		}

		if(empty($popupName)) {
			$popupName = "print";
		}

		if (empty($cssClass)) {
			$cssClass = "btnPrint_" . $printID;
		}

		if (!empty($linkPath)) {
			$script = '$(".' . $cssClass . '").click(function(){ window.open( "' . $linkPath . '","' . $popupName . '","location=0,status=0,scrollbars=1,resizable=yes,width=800,height=680"); });$(".' . $cssClass . '").button({icons: {primary: "ui-icon-print"}});';
		} else {
			$script = "jQuery('.$cssClass').button({icons: {primary: 'ui-icon-print'}});";
		}

		$button = array();
		$button["title"] = $title;
		$button["cssClass"] = $cssClass;
		$button["script"] = $script;
		$this->printButton[] = $button;
	}
	function AddButton($linkPath,$title ,$script = ""){
		if($this->checkNotNull($title))
			$this->title['add'] = $title;
		else {
			$this->title['add'] = 'เพิ่มรายการใหม่';
		}

		if($this->checkNotNull($linkPath)) {
			$this->linkPath['add'] = $linkPath;
			$this->script['add'] = (isset($this->script['add']) ? $this->script['add'] : '') . '$(".btnAdd").click(function(){ window.location="'.$linkPath.'"  });$(".btnAdd").button({icons: {primary: "ui-icon-plus"}});';
		}

		if($this->checkNotNull($script)){
			$this->linkPath['add'] = "-";
			$this->script['add'] = $script;
		}

	}

	function SaveButton($linkPath, $title = "",$script = "", $cssClass = "btnSave"){
		if($this->checkNotNull($title))
			$this->title['save'] = $title;
		else {
			$this->title['save'] = 'บันทึก';
		}

		$this->cssClass['save'] = $cssClass;
		if (empty($cssClass)) {
			$this->cssClass['save'] = "btnSave";
		}

		if($this->checkNotNull($linkPath)) {
			$this->linkPath['save'] = $linkPath;
			$this->script['save'] .= '$(".'.$this->cssClass['save'].'").click(function(){$("#form").attr("action","'.$linkPath.'"); $("#form").submit(); });$(".'.$this->cssClass['save'].'").button({icons: {primary: "ui-icon-disk"}});';
		}

		if($this->checkNotNull($script)){
			$this->linkPath['save'] = "-";
			$this->script['save'] = $script;
		}


	}

	function DeleteButton($linkPath,$title ,$script){
		if($this->checkNotNull($title))
			$this->title['del'] = $title;
		else {
			$this->title['del'] = 'ลบ';
		}
		if($this->checkNotNull($linkPath)) {
			$this->linkPath['del'] =$linkPath;
			$this->script['del'] .= '$(".btnDelete").click(function(){
					DialogDeleteConfirmResult();
		});
					$(".btnDelete").button({icons: {primary: "ui-icon-trash"}});
					function deleteConfirmResult(result){
					if(result==true){
					$("#form").attr("action","'.$linkPath.'"); $("#form").submit();
		}
		}
							';
		}

		if($this->checkNotNull($script))
			$this->script['del'] .= $script;


	}

	function ResetButton($title = ""){
		if($this->checkNotNull($title))
			$this->title['reset'] = $title;
		else {
			$this->title['reset'] = 'เริ่มใหม่';
		}
		//	$("input[type=text]").val("");
		$this->script['reset'] .= '$(".btnReset").click(function(){ $("input[type=text]").val(""); });$(".btnReset").button({icons: {primary: "ui-icon-arrowrefresh-1-n"}});';

	}

	function ReloadButton($linkPath,$title = 'เริ่มใหม่',$script = "", $cssClass = "btnReload"){
		if($this->checkNotNull($title))
			$this->title['reload'] = $title;
		else {
			$this->title['reload'] = 'เริ่มใหม่';
		}

		$this->cssClass['reload'] = $cssClass;
		if (empty($cssClass)) {
			$this->cssClass['reload'] = "btnReload";
		}

		if($this->checkNotNull($linkPath)) {
			$this->linkPath['reload'] = $linkPath;
			$this->script['reload'] =  (isset($this->script['reload']) ? $this->script['reload'] : "") . '$(".'.$this->cssClass['reload'].'").click(function(){window.location="'.$linkPath.'"; }).button({icons: {primary: "ui-icon-arrowrefresh-1-n"}});';
		} else {
			$this->linkPath['reload'] = "-";
			$this->script['reload'] = (isset($this->script['reload']) ? $this->script['reload'] : "") . '$(".'.$this->cssClass['reload'].'").click(function(){ location.reload(); }).button({icons: {primary: "ui-icon-arrowrefresh-1-n"}});';
		}

		if($this->checkNotNull($script)){
			$this->linkPath['reload'] = "-";
			$this->script['reload'] = $script;
		}


	}

	function BackButton($linkPath,$title = "", $script = ""){
		if($this->checkNotNull($title))
			$this->title['back'] = $title;
		else {
			$this->title['back'] = 'กลับ';
		}


		if($this->checkNotNull($linkPath)) {
			$this->linkPath['back'] =$linkPath;
			$this->script['back'] = (isset($this->script['back']) ? $this->script['back'] : "") . '$(".btnBack").click(function(){ window.location="'.$linkPath.'"  });$(".btnBack").button({icons: {primary: "ui-icon-circle-arrow-w"}});';
		}else {
			$this->linkPath['back'] = 'history.back();';
			$this->script['back'] = (isset($this->script['back']) ? $this->script['back'] : "") . '$(".btnBack").click(function(){history.back();  });$(".btnBack").button({icons: {primary: "ui-icon-circle-arrow-w"}});';
		}


		if($this->checkNotNull($script))
			$this->script['back'] .= $script;


	}

	function genJavasctipt(){
		$result = '<script>';
		if (isset($this->otherButton) &&count($this->otherButton) > 0){
			foreach ($this->otherButton as $otherButton) {
				$result.= $otherButton["script"];
			}
		}
		if (isset($this->printButton) && count($this->printButton) > 0){
			foreach ($this->printButton as $printButton) {
				$result.= $printButton["script"];
			}
		}
		if((isset($this->script['add']) && $this->checkNotNull($this->script['add'])) || (isset($this->linkPath['add']) && $this->checkNotNull($this->linkPath['add']))) {
			$result.= $this->script['add'];
		}
		if((isset($this->script['save']) && $this->checkNotNull($this->script['save'])) || (isset($this->linkPath['save']) && $this->checkNotNull($this->linkPath['save']))) {
			$result.= $this->script['save'];
		}
		if((isset($this->script['del']) && $this->checkNotNull($this->script['del'])) || (isset($this->linkPath['del']) && $this->checkNotNull($this->linkPath['del']))) {
			$result.= $this->script['del'];
		}
		if((isset($this->script['reset']) && $this->checkNotNull($this->script['reset'])) || (isset($this->linkPath['reset']) && $this->checkNotNull($this->linkPath['reset']))) {
			$result.= $this->script['reset'];
		}
		if((isset($this->script['reload']) && $this->checkNotNull($this->script['reload'])) || (isset($this->linkPath['reload']) && $this->checkNotNull($this->linkPath['reload']))) {
			$result.= $this->script['reload'];
		}
		if((isset($this->script['back']) && $this->checkNotNull($this->script['back'])) || (isset($this->linkPath['back']) && $this->checkNotNull($this->linkPath['back']))) {
			$result.= $this->script['back'];
		}
		if((isset($this->script['custom']) && $this->checkNotNull($this->script['custom'])) || (isset($this->linkPath['custom']) && $this->checkNotNull($this->linkPath['custom']))) {
			$result.= $this->script['custom'];
		}
		$result.='</script>';
		return $result;

	}

	function genButton(){
		$btnResult = '';
		if (isset($this->otherButton) && count($this->otherButton) > 0){
			foreach ($this->otherButton as $otherButton) {
				$btnResult.= '<button class = "' . $otherButton["cssClass"] . '" >' . $otherButton["title"] . '</button>';
			}
		}
		//if($this->checkNotNull($this->linkPath['print']) || $this->checkNotNull($this->script['print']) ) {
		//	$btnResult.= '<button class = "'.$this->cssClass['print'].'" >'.$this->title['print'].'</button>';
		//}
		if (isset($this->printButton) && count($this->printButton) > 0){
			foreach ($this->printButton as $printButton) {
				$btnResult.= '<button class = "' . $printButton["cssClass"] . '" >' . $printButton["title"] . '</button>';
			}
		}
		if((isset($this->linkPath['custom']) && $this->checkNotNull($this->linkPath['custom'])) ||
				((isset($this->script['custom'])) && $this->checkNotNull($this->script['custom']))) {
			$btnResult.= '<button class="btnCustom" >'.$this->title['custom'].'</button>';
		}
		if(isset($this->linkPath['add']) && $this->checkNotNull($this->linkPath['add'])) {
			$btnResult.= '<button class="btnAdd" >'.$this->title['add'].'</button>';
		}
		if((isset($this->linkPath['save']) && $this->checkNotNull($this->linkPath['save'])) ||
				(isset($this->script['save']) && $this->checkNotNull($this->script['save']))) {
			$btnResult.= '<button class = "'.$this->cssClass['save'].'" >'.$this->title['save'].'</button>';
		}
		if(isset($this->linkPath['del']) && $this->checkNotNull($this->linkPath['del'])) {
			$btnResult.= '<button class = "btnDelete" >'.$this->title['del'].'</button>';
		}
		if(isset($this->script['reset']) && $this->checkNotNull($this->script['reset'])) {
			$btnResult.= '<button class = "btnReset" >'.$this->title['reset'].'</button>';
		}
		if(isset($this->script['reload']) && $this->checkNotNull($this->script['reload'])) {
			$btnResult.= '<button class = "'.$this->cssClass['reload'].'" >'.$this->title['reload'].'</button>';
		}
		if(isset($this->linkPath['back']) && $this->checkNotNull($this->linkPath['back'])) {
			$btnResult.= '<button class = "btnBack" >'.$this->title['back'].'</button>';
		}
		$btnResult = '<div class="buttonArea">' . $btnResult . '</div>';
		return $btnResult;
	}

	function displayButton(){
		$displayButon = $this->genButton();

		$displayButon .= $this->genJavasctipt();
		return $displayButon;
	}

	function displayButtonNotJs(){
		$displayButon = $this->genButton();
		return $displayButon;
	}

	function checkNotNull($input){
		if($input=='' || $input==null || $input==' '){
			return false;
		}
		return true;
	}
}
?>