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
	public static function getWatTypeOption($langCode) {
		
		$options = array();
		$options["1"] = Emwork_Translate::text($langCode, "wat", "wat_type_id_1_name");
		$options["2"] = Emwork_Translate::text($langCode, "wat", "wat_type_id_2_name");
		return $options;
	}
	public static function getWatKindOption($langCode) {
		
		$options = array();
		$options["1"] = Emwork_Translate::text($langCode, "wat", "wat_kind_id_1_name");
		$options["2"] = Emwork_Translate::text($langCode, "wat", "wat_kind_id_2_name");
		return $options;
	}
	public static function getWatClassOption($langCode) {
		
		$options = array();
		$options["1"] = Emwork_Translate::text($langCode, "wat", "wat_kind_id_1_name");
		$options["2"] = Emwork_Translate::text($langCode, "wat", "wat_kind_id_2_name");
		$options["3"] = Emwork_Translate::text($langCode, "wat", "wat_kind_id_3_name");
		return $options;
	}
	public static function getDayOption($langCode) {
		
		$options = array();
		$options["1"] = Emwork_Translate::text($langCode, "wat", "day_id_1_name");
		$options["2"] = Emwork_Translate::text($langCode, "wat", "day_id_2_name");
		$options["3"] = Emwork_Translate::text($langCode, "wat", "day_id_3_name");
		$options["4"] = Emwork_Translate::text($langCode, "wat", "day_id_4_name");
		$options["5"] = Emwork_Translate::text($langCode, "wat", "day_id_5_name");
		$options["6"] = Emwork_Translate::text($langCode, "wat", "day_id_6_name");
		$options["7"] = Emwork_Translate::text($langCode, "wat", "day_id_7_name");
		return $options;
	}
}

?>
