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
class App_Resources_DataService
{
	public static function TitleClinic($lang) {
		return (strtolower($lang) == "en") ? "Data Service: Clinic" : "บริการข้อมูล: คลินิก";
	}
	public static function TitleHospital($lang) {
		return (strtolower($lang) == "en") ? "Data Service: Hospital" : "บริการข้อมูล: โรงพยาบาล";
	}
	public static function TitleOperator($lang) {
		return (strtolower($lang) == "en") ? "Data Service: Operator" : "บริการข้อมูล: ผู้ประกอบโรคศิลปะ";
	}
	public static function TitleOperatorLcs($lang) {
		return (strtolower($lang) == "en") ? "Data Service: Doctor" : "บริการข้อมูล: ผู้ปฏิบัติงาน";
	}
	public static function TitleManager($lang) {
		return (strtolower($lang) == "en") ? "Data Service: Manager" : "บริการข้อมูล: ผู้ดำเนินการ";
	}
	public static function TitlePermitee($lang) {
		return (strtolower($lang) == "en") ? "Data Service: Permitee" : "บริการข้อมูล: ผู้ประกอบกิจการ";
	}

	public static function InfoMedicalDetail($lang) {
		return (strtolower($lang) == "en") ? "Hospital Detail" : "ข้อมูลสถานพยาบาล";
	}
	public static function InfoClinicDetail($lang) {
		return (strtolower($lang) == "en") ? "Hospital Detail" : "ข้อมูลคลินิก";
	}
	public static function InfoHospitalDetail($lang) {
		return (strtolower($lang) == "en") ? "Hospital Detail" : "ข้อมูลโรงพยาบาล";
	}
	public static function InfoOperatorDetail($lang) {
		return (strtolower($lang) == "en") ? "Operator Detail" : "ข้อมูลผู้ประกอบโรคศิลปะ";
	}
	public static function InfoOperatorLcsDetail($lang) {
		return (strtolower($lang) == "en") ? "Doctor Detail" : "ข้อมูลผู้ปฏิบัติงาน";
	}
	public static function InfoPermiteeDetail($lang) {
		return (strtolower($lang) == "en") ? "Permitee Detail" : "ข้อมูลผู้ประกอบกิจการ";
	}
	public static function InfoManagerDetail($lang) {
		return (strtolower($lang) == "en") ? "Manager Detail" : "ข้อมูลผู้ดำเนินการ";
	}
	public static function InfoCertificateOperatorDetail($lang) {
		return (strtolower($lang) == "en") ? "Certificate Detail" : "ข้อมูลใบประกอบโรคศิลปะ";
	}
	public static function InfoMedicalOperatorDetail($lang) {
		return (strtolower($lang) == "en") ? "Operation Detail" : "ข้อมูลการปฏิบัติงาน";
	}
	public static function InfoManageDetail($lang) {
		return (strtolower($lang) == "en") ? "Manage Detail" : "ข้อมูลดำเนินการ";
	}
	public static function InfoPermitDetail($lang) {
		return (strtolower($lang) == "en") ? "Permit Detail" : "ข้อมูลประกอบกิจการ";
	}
	public static function InfoMedicalName($lang) {
		return (strtolower($lang) == "en") ? "" : "ชื่อสถานพยาบาล";
	}
	public static function InfoHospitalName($lang) {
		return (strtolower($lang) == "en") ? "Hospital Name" : "ชื่อโรงพยาบาล";
	}
	public static function InfoClinicName($lang) {
		return (strtolower($lang) == "en") ? "Clinic Name" : "ชื่อคลินิก";
	}
	public static function InfoMedicalConfig($lang) {
		return (strtolower($lang) == "en") ? "Medical Config" : "ลักษณะสถานพยาบาล";
	}
	public static function InfoPermitteeName($lang) {
		return (strtolower($lang) == "en") ? "Permitee Name" : "ชื่อผู้ประกอบกิจการ";
	}
	public static function InfoManagerName($lang) {
		return (strtolower($lang) == "en") ? "Manager Name" : "ชื่อผู้ดำเนินการ";
	}
	public static function InfoBedNo($lang) {
		return (strtolower($lang) == "en") ? "Bed Amount" : "จำนวนเตียง";
	}
	public static function InfoAddress($lang) {
		return (strtolower($lang) == "en") ? "Address" : "สถานที่ตั้ง";
	}
	public static function InfoOption($lang) {
		return (strtolower($lang) == "en") ? "Other Serivce" : "การบริการเพิ่มเติม";
	}
	public static function InfoWorkHour($lang) {
		return (strtolower($lang) == "en") ? "Worktime" : "เวลาทำการ";
	}
	public static function InfoTel($lang) {
		return (strtolower($lang) == "en") ? "Tel." : "เบอร์โทรศัพท์";
	}
	public static function InfoFax($lang) {
		return (strtolower($lang) == "en") ? "Fax" : "เบอร์โทรสาร";
	}
	public static function InfoEmail($lang) {
		return (strtolower($lang) == "en") ? "E-mail" : "อีเมล์";
	}
	public static function InfoWebsite($lang) {
		return (strtolower($lang) == "en") ? "Website" : "เว็บไซต์";
	}
	public static function InfoMap($lang) {
		return (strtolower($lang) == "en") ? "Map" : "แผนที่";
	}
	public static function InfoOperatorFullName($lang) {
		return (strtolower($lang) == "en") ? "Operator Name" : "ชื่อผู้ประกอบโรคศิลปะ";
	}
	public static function InfoOperatorLcsFullName($lang) {
		return (strtolower($lang) == "en") ? "Doctor Name" : "ชื่อผู้ปฏิบัติงาน";
	}
	public static function InfoPermiteeFullName($lang) {
		return (strtolower($lang) == "en") ? "Permitee Name" : "ชื่อผู้ประกอบกิจการ";
	}
	public static function InfoManagerFullName($lang) {
		return (strtolower($lang) == "en") ? "Manager Name" : "ชื่อผู้ดำเนินการ";
	}

	public static function ListHospitalName($lang) {
		return (strtolower($lang) == "en") ? "Hospital Name" : "ชื่อโรงพยาบาล";
	}
	public static function ListClinicName($lang) {
		return (strtolower($lang) == "en") ? "Clinic Name" : "ชื่อคลินิก";
	}
	public static function ListMedicalName($lang) {
		return (strtolower($lang) == "en") ? "Medical Name" : "ชื่อสถานพยาบาล";
	}
	public static function ListOperatorName($lang) {
		return (strtolower($lang) == "en") ? "Operator Name" : "ชื่อผู้ประกอบโรคศิลปะ";
	}
	public static function ListOperatorLcsName($lang) {
		return (strtolower($lang) == "en") ? "Doctor Name" : "ชื่อผู้ปฏิบัติงาน";
	}
	public static function ListManagerName($lang) {
		return (strtolower($lang) == "en") ? "Manager Name" : "ชื่อผู้ดำเนินการ";
	}
	public static function ListPermiteeName($lang) {
		return (strtolower($lang) == "en") ? "Permitee Name" : "ชื่อผู้ประกอบกิจการ";
	}
	public static function ListOperatorFieldName($lang) {
		return (strtolower($lang) == "en") ? "License Field" : "สาขาใบประกอบโรคศิลปะ";
	}
	public static function ListProvinceName($lang) {
		return (strtolower($lang) == "en") ? "Province Name" : "จังหวัด";
	}
	public static function ListDistrictName($lang) {
		return (strtolower($lang) == "en") ? "District Name" : "เขต / อำเภอ";
	}
	public static function ListMedicalConfigName($lang) {
		return (strtolower($lang) == "en") ? "Medical Config Name" : "ลักษณะ";
	}
	public static function ListWorkTime($lang) {
		return (strtolower($lang) == "en") ? "Work Time" : "วันเวลาปฏิบัติงาน";
	}

	public static function SearchLabel($lang) {
		return (strtolower($lang) == "en") ? "Search: " : "ค้นหา: ";
	}
	public static function SearchSubmitButton($lang) {
		return (strtolower($lang) == "en") ? "Search" : "ค้นหา";
	}
	public static function SearchResetButton($lang) {
		return (strtolower($lang) == "en") ? "Reset" : "เริ่มใหม่";
	}
	public static function SearchAdvanceLabel($lang) {
		return (strtolower($lang) == "en") ? "Advance Search" : "ค้นหาขั้นสูง";
	}
	public static function SearchHospitalName($lang) {
		return (strtolower($lang) == "en") ? "Hospital Name" : "ชื่อโรงพยาบาล";
	}
	public static function SearchClinicName($lang) {
		return (strtolower($lang) == "en") ? "Clinic Name" : "ชื่อคลินิก";
	}
	public static function SearchMedicalConfig($lang) {
		return (strtolower($lang) == "en") ? "Medical Config" : "ลักษณะสถานพยาบาล";
	}
	public static function SearchMedicalOtherService($lang) {
		return (strtolower($lang) == "en") ? "Other Service" : "การบริการเพิ่มเติม";
	}
	public static function SearchProvinceName($lang) {
		return (strtolower($lang) == "en") ? "Province Name" : "ชื่อจังหวัด";
	}
	public static function SearchDistrictName($lang) {
		return (strtolower($lang) == "en") ? "District Name" : "ชื่อเขต / อำเภอ";
	}
	public static function SearchSubDistrictName($lang) {
		return (strtolower($lang) == "en") ? "Sub District Name" : "ชื่อแขวง / ตำบล";
	}
	public static function SearchOpearatorFieldName($lang) {
		return (strtolower($lang) == "en") ? "Field" : "สาขา";
	}
	public static function SearchStreet($lang) {
		return (strtolower($lang) == "en") ? "Street" : "ถนน";
	}
	public static function SearchPermiteeType($lang) {
		return (strtolower($lang) == "en") ? "Permitee Type" : "ประเภทบุคคล";
	}

	public static function PleaseFillSearch($lang) {
		return (strtolower($lang) == "en") ? "Please Fill Filter." : "กรุณากรอกการค้นหา";
	}
	public static function NotFound($lang) {
		return (strtolower($lang) == "en") ? "Not Found." : "ไม่พบข้อมูล";
	}
	public static function PleaseSelect($lang) {
		return (strtolower($lang) == "en") ? "-- Please Select --" : "-- โปรดเลือก --";
	}
	public static function HintSearch($lang) {
		$str = "";
		if (strtolower($lang) == "en") {
			$str = "- Fill out the required information and press the search button.";
			$str .= "<br />";
			$str .= "- Should not be used in a way that information is incorrect.";
		} else {
			$str = "- กรอกข้อมูลที่ต้องการและกดปุ่มค้นหา";
			$str .= "<br />";
			$str .= "- ไม่ควรนำข้อมูลไปใช้ในทางที่ไม่ถูกต้อง";
		}
		return $str;
	}
}
