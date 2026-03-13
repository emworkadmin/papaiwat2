<?php
Class Emwork_Grid_Gridcol {
	private $name = '';
	private $index = '';
	private $width = '';
	private $align = '"left"';
	private $formatter = '';
	private $fixed = 'true';
	private $hidden = 'false';
	private $sorttype = '';
	private $editable = 'false';
	private $sortable = 'true';

	public function __construct($name, $index = '', $align = 'left',$width = 150,$fixed = true,$formatter = "", $hidden = false, $sorttype = '', $editable = false){
		$this->name=$name;
		$this->index=$index;
		$this->width=$width;
		$this->align=$align;
		$this->fixed=$fixed;
		$this->formatter=$formatter;
		$this->hidden=$hidden;
		$this->sorttype=$sorttype;
		$this->editable=$editable;
	}

	public function setName($name){
		$this->formatter=$name;
	}

	public function setFormatter($formatter){
		$this->formatter=$formatter;
	}

	public function setHidden($hidden){
		$this->hidden=$hidden;
	}

	public function setSorttype($sorttype){
		$this->sorttype=$sorttype;
	}

	public function setEditable($editable){
		$this->editable=$editable;
	}
	public function setSortable($sortable){
		$this->sortable = $sortable;
	}

	private function genGridCol(){
		if($this->checkNotNull($this->name))
			$prepareColModel[] .= ' name: "'.$this->name.'"';
		if($this->checkNotNull($this->index))
			$prepareColModel[] .= ' label: "'.$this->index.'"';
		if($this->checkNotNull($this->width))
			$prepareColModel[] .= ' width: '.$this->width;
		if($this->checkNotNull($this->align))
			$prepareColModel[] .= ' align: "'.$this->align.'"';
		if($this->checkNotNull($this->sorttype))
			$prepareColModel[] .= ' sorttype: "'.$this->sorttype.'"';
		if($this->checkNotNull($this->hidden))
			$prepareColModel[] .= ' hidden: '.$this->hidden;
		if($this->checkNotNull($this->formatter))
			$prepareColModel[] .= ' formatter: '.$this->formatter;
		if($this->checkNotNull($this->fixed))
			$prepareColModel[] .= ' fixed: '.$this->fixed;
		if($this->checkNotNull($this->editable))
			$prepareColModel[] .= ' editable: '.$this->editable;
		if($this->checkNotNull($this->sortable))
			$prepareColModel[] .= ' sortable: '.$this->sortable;

		$colModel = '{' . implode(",", $prepareColModel) .'}';

		return $colModel;
	}

	public function rederGridCol(){
		$result = $this->genGridCol();
		return $result;
	}
	public function rederGridColMap(){

		if($this->checkNotNull($this->name))
			$prepareColModel[] = ' name: "'.$this->name.'"';
		if($this->checkNotNull($this->name))
			$prepareColModel[] = ' jsonmap: "cell.'.$this->name.'"';
		if($this->checkNotNull($this->index))
			$prepareColModel[] = ' label: "'.$this->index.'"';
		if($this->checkNotNull($this->width)) {
			if ($this->fixed == 'true') {
				$prepareColModel[] = ' width: '.$this->width;
			} else {
				$prepareColModel[] = ' width: 150';
			}
		}
		if($this->checkNotNull($this->align))
			$prepareColModel[] = ' align: "'.$this->align.'"';
		if($this->checkNotNull($this->sorttype))
			$prepareColModel[] = ' sorttype: "'.$this->sorttype.'"';
		if($this->checkNotNull($this->hidden))
			$prepareColModel[] = ' hidden: '.$this->hidden;
		if($this->checkNotNull($this->formatter))
			$prepareColModel[] = ' formatter: '.$this->formatter;
		if($this->checkNotNull($this->fixed)) {
			//$prepareColModel[] = ' fixed: '.$this->fixed;
			$prepareColModel[] = ' fixed: true';
		}
		if($this->checkNotNull($this->editable))
			$prepareColModel[] = ' editable: '.$this->editable;
		if($this->checkNotNull($this->sortable))
			$prepareColModel[] = ' sortable: '.$this->sortable;

		$colModel = '{' . implode(",", $prepareColModel) .'}';

		return $colModel;
	}

	private function checkNotNull($input){
		if($input=='' || $input==null || $input==' '){
			return false;
		}
		return true;
	}
}
?>