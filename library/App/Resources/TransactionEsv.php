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
class App_Resources_TransactionEsv
{
	public static function HelpStep01($lang) {
		return "คุณสามารถยื่นทำธุรกรรมโดยการเลือกธุรกรรมที่อยู่ด้านล่าง กรุณากรอกข้อมูลที่ต้องการในช่องที่กำหนด และกรอกข้อมูลในช่องที่มีเครื่องหมาย '<span style='color:Red'>*</span>' ให้ครบถ้วน เนื่องจากเป็นข้อมูลที่สำคัญในการทำธุรกรรม";
	}
	public static function HelpStep02($lang) {
		return "กรุณาตรวจสอบความถูกต้องของข้อมูลก่อนกดปุ่ม '<b>ยืนยัน</b>' เพื่อดำเนินการทำรายการธุรกรรมในขั้นตอนต่อไป<br />หากคุณยังไม่ต้องการยื่นธุรกรรมทันที ให้นำเครื่องหมายถูกออกจาก '<b>ยื่นส่งธุรกรรม</b>' หรือกดปุ่ม '<b>กลับ</b>' เพื่อย้อนกลับไปแก้ไขข้อมูล หรือกดปุ่ม '<b>ยกเลิก</b>' เพื่อยกเลิกการยื่นธุรกรรม";
	}
	public static function HelpStep03($lang) {
		return "คุณได้ทำธุรกรรมเสร็จสิ้นแล้ว โดยสถานะการทำธุรกรรมจะอยู่ด้านล่าง และหากต้องการทำธุรกรรมอืนต่อ กดปุ่ม '<b>ยื่นธุรกรรมอื่น</b>'";
	}

	public static function SendEvidenceAddress1($lang) {
		return "กรุณาส่งหลักฐานดังกล่าวมาที่<br /> งานบริการรวมศูนย์ สำนักสถานพยาบาลและการประกอบโรคศิลปะ กรมสนับสนุนบริการสุขภาพ กระทรวงสาธารณะสุข<br /> ถนนติวานนท์ อำเภอเมือง จังหวัดนนทบุรี 11000";
	}
	public static function SendEvidenceAddress2($lang) {
		return "โดยการยื่นธุรกรรมจะถือว่าเสร็จสมบูรณ์เมื่อสำนักสถานพยาบาล และการประกอบโรคศิลปะ ได้รับหลักฐานครบถ้วน";
	}

	public static function NoteStep02Label($lang) {
		return "หมายเหตุ";
	}
	public static function NoteStep02Detail($lang) {
		return "1. การทำธุรกรรมอาจต้องรอการพิจารณาจากเจ้าหน้าที่<br />2. หากเกิดความล่าช้า กรุณาติดต่อเจ้าหน้าที่";
	}
}
