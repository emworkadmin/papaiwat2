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
class App_Resources_GeneralOption
{
	public static function CheckboxOption() {
		return array(1, 0);
	}

	public static function IdentificationTypeOption() {
		return array("T" => "เลขประจำตัวประชาชน", "P" => "เลขที่หนังสือเดินทาง");
	}
	public static function GenderOption() {
		return array("M" => "ชาย", "F" => "หญิง");
	}
	public static function IsSameCensusOption() {
		return array("Y" => "เหมือนทะเบียนบ้าน", "N" => "ต่างจากทะเบียนบ้าน");
	}
	public static function AddressTypeOption() {
		return array("H" => "บ้าน", "O" => "สำนักงาน");
	}

	/**
	 * ข้อมูลเดียวกันกับในตาราง LkpContactType
	 * @return array    ข้อมูลเดียวกันกับในตาราง LkpContactType
	 */
	public static function ContactOption() {
		return array("B" => "Back Office", "O" => "OSS", "P" => "สสจ.", "E" => "e-Service");
	}
	public static function ContactListOption() {
		return array("B" => "B", "O" => "OSS", "P" => "PRO", "E" => "ESV");
	}

	public static function ReceiveByOption() {
		return array("S" => "รับด้วยตนเอง", "A" => "มอบอำนาจ");
	}

	public static function ComplainReportPeriodOption() {
		return array("1" => "1 เดือน", "3" => "3 เดือน", "6" => "6 เดือน", "12" => "12 เดือน");
	}

	public static function DataStatusOption() {
		return array("W" => "รอการพิจารณา", "R" => "ไม่ผ่านการตรวจสอบ", "A" => "ผ่านการตรวจสอบ");
	}

	public static function MonthOption() {
		return array(
				"1" => "มกราคม",
				"2" => "กุมภาพันธ์",
				"3" => "มีนาคม",
				"4" => "เมษยายน",
				"5" => "พฤษภาคม",
				"6" => "มิถุนายน",
				"7" => "กรกฎาคม",
				"8" => "สิงหาคม",
				"9" => "กันยายน",
				"10" => "ตุลาคม",
				"11" => "พฤศจิกายน",
				"12" => "ธันวาคม");
	}
	public static function MonthShortOption() {
		return array(
				"1" => "ม.ค.",
				"2" => "ก.พ.",
				"3" => "มี.ค.",
				"4" => "เม.ย.",
				"5" => "พ.ค.",
				"6" => "มิ.ย.",
				"7" => "ก.ค.",
				"8" => "ส.ค.",
				"9" => "ก.ย.",
				"10" => "ต.ค.",
				"11" => "พ.ย.",
				"12" => "ธ.ค.");
	}
	public static function QuarterFiscalOption() {
		array(
				"4" => "ตุลาคม - ธันวาคม", "1" => "มกราคม - มีนาคม",
				"2" => "เมษายน - มิถุนายน", "3" => "กรกฎาคม - กันยายน");
	}
}
