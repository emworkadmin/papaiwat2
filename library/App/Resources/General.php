<?php
/**
 * App Library
 *
 * @category   App
 * @package    App_Resources
 * @copyright
 * @license
 * @version
 */

/**
 *
 * @category   App
 * @package    App_Resources
 * @copyright
 * @license
 */
class App_Resources_General
{
	public static function EmailLabel() {
		return "อีเมล์";
	}
	public static function WriteAtFromEsv() {
		return "ธุรกรรมทางอินเทอร์เน็ต";
	}

	public static function TransactionContactTypeLabel() {
		return "ช่องทางที่ผู้รับบริการติดต่อ";
	}
	public static function TransactionReturnTypeLabel() {
		return "ช่องทางที่ผู้รับบริการแสดงความจำนงในการขอรับใบอนุญาต/หนังสือรับรอง/เอกสารอื่นๆ";
	}

	public static function WaitingSaveLabel() {
		return "รอการบันทึก";
	}

	public static function notShowNull($input, $displayNull = '-'){
		$input = trim($input);
		if(empty($input))
			$result = $displayNull;
		else
			$result = $input;

		return $result;
	}
	public static function repeatString($initValue, $length = 1, $strRepeat = "0"){
		if (empty($initValue))
			$initValue = "";
		for($i = 0; strlen($initValue) < $length; $i++ ) {
			$initValue = "" . $strRepeat . $initValue;
		}
		if (strlen($initValue) > $length)
			$initValue = substr($initValue, strlen($initValue) + $length);
		return $initValue;
	}

	public static function WriteAtText($operationType, $departmentName) {
		$str = $departmentName;
		switch(strtoupper($operationType)) {
			case "B" : $str = "สำนักสถานพยาบาลและการประกอบโรคศิลปะ"; break;
			case "O" : $str = "ศูนย์บริการธุรกิจสุขภาพ"; break;
			case "P" : $str = $departmentName; break;
		}
		return $str;
	}

	// Authorization
	public static function DenyAddLabel() {
		return "ไม่ได้รับสิทธิ์เพิ่ม";
	}
	public static function DenyAccessLabel() {
		return "ไม่ได้รับสิทธิ์ในส่วนนี้";
	}
	public static function DenyAccessStepLabel() {
		return "คุณยังไม่สามารถเข้ามาทำงานในสถานะนี้ได้ กรุณาดำเนินการตามขั้นตอน";
	}
}
