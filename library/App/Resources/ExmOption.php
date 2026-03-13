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
class App_Resources_ExmOption
{
	public static function ExamResultOption() {
		return array("P" => "ผ่าน", "F" => "ไม่ผ่าน", "A" => "ขาดสอบ", "N" => "ไม่ได้บันทึกผล");
	}

	public static function CertificateStatusOption() {
		return array("W" => "รอการอนุมัติ", "N" => "ใช้งาน", "B" => "สั่งพัก", "C" => "ยกเลิก");
	}
	public static function CertificateTypeOption() {
		return array("A" => "(ก)", "B" => "(ข)", "C" => "(ค)");
	}
}
