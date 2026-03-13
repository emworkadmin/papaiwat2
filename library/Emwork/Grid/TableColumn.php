<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 *
 * @category   Emwork
 * @package    Emwork_Grid
 * @copyright  Copyright (c)
 * @license
 * @version  1.0
 *
 * 21 Feb 2012
 */

/**
 * @category   Emwork
 * @package    Emwork_Grid
 * @copyright  Copyright (c)
 * @license
 */
Class Emwork_Grid_TableColumn
{

	private $name = 'Column';
	private $label = '';
	private $index = '';
	private $width = 150;
	private $align = 'left';
	private $formatter = '';
	private $fixed = true;
	private $hidden = false;
	private $sorttype = '';
	private $editable = false;
	private $sortable = true;
	private $resizable = true;

	public function setName($name)
	{
		$this->name = $name;
		if (empty($this->name)) {
			$this->name = "Column";
		}
	}
	public function setLabel($label)
	{
		$this->label = $label;
	}
	public function getLabel()
	{
		return $this->label;
	}
	public function setIndex($index)
	{
		$this->index = $index;
	}
	public function setAlign($align)
	{
		$align = strtolower($align);
		if ($align == "left" || $align == "center" || $align == "right") {
			$this->align = $align;
		}
	}
	public function setWidth($width)
	{
		if (!empty($width)) {
			$this->width = $width;
		}
	}
	public function setFormatter($formatter)
	{
		if (!empty($formatter)) {
			$this->formatter = $formatter;
		}
	}
	public function setHidden($hidden)
	{
		if (!is_null($hidden)) {
			$this->hidden = $hidden;
		}
	}
	public function setSorttype($sorttype)
	{
		if (!empty($sorttype)) {
			$this->sorttype = $sorttype;
		}
	}
	public function setFixed($fixed)
	{
		if (!is_null($fixed)) {
			$this->fixed = $fixed;
		}
	}
	public function setEditable($editable)
	{
		if (!is_null($editable)) {
			$this->editable = $editable;
		}
	}
	public function setSortable($sortable)
	{
		if (!is_null($sortable)) {
			$this->sortable = $sortable;
		}
	}
	public function setResizeable($resizeable)
	{
		if (!is_null($resizeable)) {
			$this->resizable = $resizeable;
		}
	}

	public function __construct($name, $label = '', $index = '', $align = 'left', $width = 150, $fixed = true, $formatter = '', $hidden = false, $sortable = true, $editable = false, $resizeable = true)
	{
		$this->setName($name);
		$this->setLabel($label);
		$this->setIndex($index);
		$this->setAlign($align);
		$this->setWidth($width);
		$this->setFixed($fixed);
		$this->setFormatter($formatter);
		$this->setHidden($hidden);
		$this->setSortable($sortable);
		$this->setEditable($editable);
		$this->setResizeable($resizeable);
	}

	public function rederColumn()
	{
		$prepareColModel[] = ' name: "' . $this->name . '"';
		$prepareColModel[] = ' jsonmap: "cell.' . $this->name . '"';  // json data type

		if (!empty($this->label))
			$prepareColModel[] = ' label: "' . $this->label . '"';
		if (!empty($this->width)) {
			if ($this->fixed) {
				$prepareColModel[] = ' width: ' . $this->width;
			} else {
				$prepareColModel[] = ' width: 150';
			}
		}
		if (!empty($this->align)){
			$prepareColModel[] = ' align: "' . $this->align . '"';
		}
		if (!empty($this->sorttype)) {
			$prepareColModel[] = ' sorttype: "' . $this->sorttype . '"';
		}
		if (!empty($this->formatter))
			$prepareColModel[] .= ' formatter: ' . $this->formatter;
		if (!empty($this->fixed)) {
			//$prepareColModel[] .= ' fixed: '.$this->fixed;
			$prepareColModel[] = ' fixed: true';
		}
		//$prepareColModel[] = ' editable: ' . ($this->editable );
		$prepareColModel[] = ' hidden: ' . ($this->hidden ? "true" : "false");
		$prepareColModel[] = ' sortable: ' . ($this->sortable ? "true" : "false");
		$prepareColModel[] = ' resizable: ' . ($this->resizable ? "true" : "false");

		$colModel = '{' . implode(",", $prepareColModel) . '}';

		return $colModel;
	}

}

?>