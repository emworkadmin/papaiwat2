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
 * Static methods for convert Thai Date
 *
 * @category   Emwork
 * @package    Emwork_Utility
 * @copyright
 * @license
 */
class Emwork_Utility_CheckDigit
{
    public static function gtin($str) {
    	$digit = '';
    	
    	if (!empty($str)) {
    		$sum = 0;
    		$tempStr = strrev($str);
    		for($i = 1 ; $i <= strlen($tempStr); $i ++) {
    			if ($i % 2) {
    				$sum += 3 * substr($tempStr, $i - 1, 1);
    			} else {
    				$sum += 1 * substr($tempStr, $i - 1, 1);
    			}
    		}
    		$temp = $sum % 10;
    		$digit = 10 - $temp;
    	}
    	
    	return $digit;
    }
}
