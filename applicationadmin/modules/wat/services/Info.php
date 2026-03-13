<?php
class Wat_Service_Info {
	
	protected static $extensionName = 'ฐานข้อมูลวัด';
	protected static $extensionCode = 'wat';
	
	function __construct() {
		try {
			
		} catch ( Exception $e ) {
			
		}
	}
	
	public static function getExtensionName() {
		return self::	$extensionName;
	}
	public static function getExtensionCode() {
		return self::	$extensionCode;
	}
	public static function getWatTypeOption() {
		
		$options = array();
		$options["1"] = "พระอารามหลวง";
		$options["2"] = "วัดราษฎร์";
		$options["3"] = "วัดร้าง";
		$options["4"] = "วัดป่า";
		return $options;
	}
	public static function getWatKindOption() {
		
		$options = array();
		$options["1"] = "วัดที่ได้รับพระราชทานวิสุงคามสีมา";
		$options["2"] = "สำนักสงฆ์";
		return $options;
	}
	public static function getWatClassOption() {
		
		$options = array();
		$options["1"] = "ชั้นเอก";
		$options["2"] = "ชั้นโท";
		$options["3"] = "ชั้นตรี";
		return $options;
	}
	public static function getDayOption() {
		
		$options = array();
		$options["1"] = "วันอาทิตย์";
		$options["2"] = "วันจันทร์";
		$options["3"] = "วันอังคาร";
		$options["4"] = "วันพุธ";
		$options["5"] = "วันพฤหัสบดี";
		$options["6"] = "วันศุกร์";
		$options["7"] = "วันเสาร์";
		return $options;
	}
}

?>
