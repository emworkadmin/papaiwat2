<?php
/**
 * EMWORK Library
 *
 * @category   Emwork
 * @package    Emwork_Utility
 * @copyright
 * @license
 * @version
 */

/**
 * 
 *
 * @category   Emwork
 * @package    Emwork_Utility
 * @copyright
 * @license
 */
class Emwork_Utility_Password
{
    private $special = "@#$%";

    public static function specialCharacter() {
        $special = "@#$%";
        return $special;
    }

    /**
     *
     * @param int       $minLength
     * @param int       $maxLength
     * @param boolean   $hasUpper
     * @param boolean   $hasDigis
     * @param boolean   $hasSpecial
     * @param string    $lang
     * @return array 
     */
    public static function passwordPolicy($minLength = 4, $maxLength = 16, $hasUpper = true, $hasDigis = true, $hasSpecial = true) {
        $special = "@#$%";
        $hintInit = "กรอกตัวอักษร";
        $hint = $hintInit;
        $minLength = empty($minLength) ? 4 : $minLength;
        $maxLength = empty($maxLength) ? 16 : $maxLength;
        //$regularInit = "^(?=.*[a-z])";
        $regularInit = "/^[a-zA-Z0-9{$special}]{{$minLength},{$maxLength}}$/.test(value)";
        $regular = $regularInit;
        if ($hasUpper) {
            $hint = ($hint == $hintInit ? "กรอกตัวอักษรภาษาอังกฤษตัวเล็ก" : $hint) . " ตัวใหญ่";
            $regular .= " && /[A-Z]+/.test(value)";
            //$regular .= "(?=.*[A-Z])";
        }
        if ($hasDigis) {
            $hint = ($hint == $hintInit ? "กรอกตัวอักษรภาษาอังกฤษตัวเล็ก" : $hint) . " ตัวเลข";
            $regular .= " && /[\d]+/.test(value)";
            //$regular .= "(?=.*\d)";
        }
        if ($hasSpecial) {
            $hint = ($hint == $hintInit ? "กรอกตัวอักษรภาษาอังกฤษตัวเล็ก" : $hint) . " อักขระพิเศษ (มีดังนี้ $special)";
            $regular .= " && /[{$special}]+/.test(value)";
            //$regular .= "(?=.*[{$specail}])";
        }
        $hint .= " โดยมีความยาว {$minLength} - {$maxLength} ตัวอักษร";
        if ($regularInit == $regular) {
            $regular = "/^[a-zA-Z0-9{$special}]{{$minLength},{$maxLength}}$/.test(value)";
        } else {
            //$regular .= ".{{$minLength},{$maxLength}}$";
        }

        $data = new stdClass();
        $data->hint = $hint; //"กรอกตัวอักษรภาษาอังกฤษตัวเล็ก ตัวใหญ่ อักขระพิเศษ และตัวเลข โดยมีความยาว 6 - 16 ตัวอักษร";
        $data->regular = $regular; //"((?=.*[a-z])(?=.*\d)(?=.*[A-Z])(?=.*[@#$%]).{6,16})";
        $data->message = "กรอกรูปแบบรหัสผ่านไม่ถูกต้อง";
        return $data;
    }

    /**
     *
     * @param int $length
     * @param boolean $hasUpper
     * @param boolean $hasDigis
     * @param boolean $hasSpecial
     * @return string
     */
    public static function generatePassword($length, $hasUpper, $hasDigis, $hasSpecial) {

        $upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$lower = "abcdefghijklmnopqrstuvwxyz";
	$number = "0123456789";
        $special = "@#$%";

        if ($length > 12)
            $length = 12;

	if($hasUpper){
		$seed_length += 26;
		$seed .= $upper;
	}
	if(true){
		$seed_length += 26;
		$seed .= $lower;
	}
	if($hasDigis){
		$seed_length += 10;
		$seed .= $number;
	}
	if($hasSpecial){
		$seed_length +=strlen($special);
		$seed .= $special;
	}
	for($x=1; $x <= $length; $x++){
		$password .= $seed{rand(0,$seed_length-1)};
	}
	return($password);
    }
}
