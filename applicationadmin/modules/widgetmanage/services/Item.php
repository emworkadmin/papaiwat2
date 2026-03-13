<?php
class Widgetmanage_Service_Item {
	
	/**
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	public static function getOption() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption();
		
		return $widgetmanageItemOption;
	}
	public static function getOptionSlideSet() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('slideset');
		
		return $widgetmanageItemOption;
	}
	public static function getOptionSlideShow() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('slideshow');
		
		return $widgetmanageItemOption;
	}
	public static function getOptionGrid() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('grid');
		
		return $widgetmanageItemOption;
	}
	public static function getOptionTab() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('tab');
		
		return $widgetmanageItemOption;
	}
	public static function getOptionCollapse() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('collapse');
		
		return $widgetmanageItemOption;
	}
	public static function getOptionKenburns() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('kenburns');
		
		return $widgetmanageItemOption;
	}
	public static function getOptionMarquee() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('marquee');
		
		return $widgetmanageItemOption;
	}
	public static function getOptionStory() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('story');
		
		return $widgetmanageItemOption;
	}
	public static function getOptionCalendar() {
		// Get Option
		$widgetmanageItemModel = new Widgetmanage_Model_Item();
		$widgetmanageItemOption = $widgetmanageItemModel->getOption('calendar');
		
		return $widgetmanageItemOption;
	}
}

?>
