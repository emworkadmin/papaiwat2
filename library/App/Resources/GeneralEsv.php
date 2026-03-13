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
class App_Resources_GeneralEsv
{
	public static function PrivacyLabel($lang) {
		return (strtolower($lang) == "en") ? "Privacy Policy" : "เงื่อนไขการรับบริการ";
	}
	public static function ContactLabel($lang) {
		return (strtolower($lang) == "en") ? "Contact Us" : "ติดต่อ";
	}
	public static function AllRightsReservedLabel($lang) {
		return (strtolower($lang) == "en") ? "@2010, Bureau of Sanatorium Healing Art (BSH). All rights reserved." : "สงวนลิขสิทธิ์ พ.ศ.2553 สำนักสถานพยาบาลและการประกอบโรคศิลปะ (Bureau of Sanatorium Healing Art (BSH))";
	}
}
