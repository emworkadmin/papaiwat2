<?php
/**
 * Description of ItemEditor
 *
 * @author jon
 */
class App_forms_ItemEditor extends Zend_Form
{

	public function __construct($itemId , $session, $mode = 'create')
	{
		$this->name = new Zend_Form_Element_Text('name');
		$this->name->setLabel("item name:")
		->setRequired(true);

		$this->description = new Zend_Form_Element_Textarea('description');
		$this->description->setLabel("item description:")
		->setRequired(true);

		$this->id = new Zend_Form_Element_Hidden('id');
		$this->id->setValue($itemId);


		if (array_key_exists($itemId, $session->items))
			$this->mapItemToForm($session->items[$itemId]);

		$this->submit = new Zend_Form_Element_Submit($mode);

		$this->addElements(array(
				$this->name,
				$this->description,
				$this->id

		));

		$this->setDecorators(array(
				'FormElements',
				array('HtmlTag', array('tag' => 'dl')),
				'Form'
		));

	}
	private function mapItemToForm(App_ItemDto $item)
	{
		$this->name->setValue($item->name);
		$this->description->setValue($item->description);
		$this->id->setValue($item->id);

	}
	public function MapFormToItemDto()
	{
		return
		new App_ItemDto(
				$this->name->getValue(),
				$this->description->getValue(),
				$this->id->getValue()
		);
	}
}