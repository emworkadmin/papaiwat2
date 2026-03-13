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
class Emwork_Utility_DateTime
{
    /**
     *
     * @param  string        $date that has format : dd/mm/yyyy .
     * @param  int|boolean   $isThaiYear - OPTIONAL flag to tell $date is Thai Year or not.
     * @return string
     * @throws Zend_Exception
     */
    public static function toSqlSrvDate($date, $isThaiYear = 1) 
    {
 
    	$result = "";
    	/*
    	if($date != '') {
	 		//$date = new DateTime($date); 
	 		//print_r($date);exit;
	    	$date = $date->format('Y-m-d H:i:s');
	        $dateArray = explode("-", $date);
	        //print_r($dateArray);exit;
	        if (count($dateArray) != 3)
	            return null;
	       
	        $day = $dateArray[0]; //echo $date;exit;
	        $month = $dateArray[1];
	        $year = $dateArray[2];
	        if ($isThaiYear)
	            $year -= 543;
	        $str = "$year-$month-$day";
	        try {
	            $dateObj = new DateTime($str);
	            $str = $dateObj->format("Y-m-d");
	        } catch (Exception $e) {
	            $str = null;
	        }  
    	}*/
    	
	    /*
    	if($date != ''){
    		$split = explode("/",$date);
    		$thaiYear = ($split[2])-543;
    		if ($split[2] >=  2300) {
    			$split[2] = $thaiYear;
    		} 	
    		$result = $split[2] .'-'. str_repeat('0',2 - strlen($split[1])).$split[1].'-'. str_repeat('0',2 - strlen($split[0])).$split[0];
    	}*/
    	
    	if($date != ''){
    		$split = explode("/",$date);
    	
    		if($split[2]>date('Y')){
    			$searchYear = ($split[2])-543;
    			$split[2] = $searchYear;
    		}
    	
    		$result = $split[2] .'-'. str_repeat('0',2 - strlen($split[1])).$split[1].'-'. str_repeat('0',2 - strlen($split[0])).$split[0];
    	}
    	//print_r($result);exit();
        return $result; 
    }
    public static function MSSqlToDateThai($date, $type = 'D') {
    	 
    	$result = "";
    	if($date != '') {
    		$date = $date->format('Y-m-d H:i:s w');
    		$split = explode(" ",$date);
    		$datePart = $split[0];
    		$timePart = $split[1];
    		$day = $split[2];
    		$timeSplit =  explode(":",$timePart);
     
    		$dateSplit = explode("-",$datePart);
   
    		$dateThai = ltrim($dateSplit[2], '0') .'/'. $dateSplit[1].'/'. ($dateSplit[0] + 543);
//     		$dateThai = ltrim($dateSplit[2], '0') .'/'. $dateSplit[1].'/'. ($dateSplit[0] + 543);

    		$dateThaiFull = 
    		
    		'วัน'.
    		($day == '0' ? 'อาทิตย์' :
    		($day == '1' ? 'จันทร์' :
    		($day == '2' ? 'อังคาร' :
    		($day == '3' ? 'พุธ' :
    		($day == '4' ? 'พฤหัสบดี' :
    		($day == '5' ? 'ศุกร์' :
    		($day == '6' ? 'เสาร์' : ''
    		)))))))
    		.'ที่ '.
      		 ltrim($dateSplit[2], '0')
      		.' '.
      		($dateSplit[1] == '01' ? 'มกราคม' : 
			($dateSplit[1] == '02' ? 'กุมภาพันธ์' :
			($dateSplit[1] == '03' ? 'มีนาคม' : 
			($dateSplit[1] == '04' ? 'เมษายน' : 
			($dateSplit[1] == '05' ? 'พฤษภาคม' : 
			($dateSplit[1] == '06' ? 'มิถุนายน' : 
			($dateSplit[1] == '07' ? 'กรกฎาคม' : 
			($dateSplit[1] == '08' ? 'สิงหาคม' : 
			($dateSplit[1] == '09' ? 'กันยายน' : 
			($dateSplit[1] == '10' ? 'ตุลาคม' : 
			($dateSplit[1] == '11' ? 'พฤศจิกายน' : 
			($dateSplit[1] == '12' ? 'ธันวาคม' : '/'.$dateSplit[1].'/'
			))))))))))))
    		.' '.($dateSplit[0] + 543);
			
			$dateThaiNonDay = 
    		
    		'วันที่ '.
      		 ltrim($dateSplit[2], '0')
      		.' '.
      		($dateSplit[1] == '01' ? 'มกราคม' : 
			($dateSplit[1] == '02' ? 'กุมภาพันธ์' :
			($dateSplit[1] == '03' ? 'มีนาคม' : 
			($dateSplit[1] == '04' ? 'เมษายน' : 
			($dateSplit[1] == '05' ? 'พฤษภาคม' : 
			($dateSplit[1] == '06' ? 'มิถุนายน' : 
			($dateSplit[1] == '07' ? 'กรกฎาคม' : 
			($dateSplit[1] == '08' ? 'สิงหาคม' : 
			($dateSplit[1] == '09' ? 'กันยายน' : 
			($dateSplit[1] == '10' ? 'ตุลาคม' : 
			($dateSplit[1] == '11' ? 'พฤศจิกายน' : 
			($dateSplit[1] == '12' ? 'ธันวาคม' : '/'.$dateSplit[1].'/'
			))))))))))))
    		.' '.($dateSplit[0] + 543);
    		
    		$hour = $timeSplit[0];
    		$minute =  $timeSplit[1];
    		$second =   $timeSplit[2];
    
    		switch ($type) {
    			case "T" :
    				$result = $timePart;
    				break;
    			case "DT" :
    				$result = $dateThai . ' ' . $timePart;
    				break;
    			case "DT_F" :
    				$result = $dateThaiFull;
    				break;
				case "DT_Nd" :
    				$result = $dateThaiNonDay;
    				break;	
    			case "H" :
    				$result = $hour;
    				break;
    			case "M" :
    				$result = $minute;
    				break;
    			case "S" :
    				$result = $second;
    				break;
    			case "D" :
    			default:
    				$result = $dateThai;
    				break;
    		}
    	}
    	return  $result;
    }

    public static function MSSqlToDateEng($date, $type = 'D') {
  //  echo $date; exit;
    	$result = "";
    	if($date != '') {
    		$date = $date->format('Y-m-d H:i:s');
    		$split = explode(" ",$date);
    		$datePart = $split[0];
    		$timePart = $split[1];
    		$timeSplit =  explode(":",$timePart);
    		 
    		$dateSplit = explode("-",$datePart);
    		 
    		$dateThai = $dateSplit[2] .'/'. $dateSplit[1].'/'. ($dateSplit[0]);
    
    		$hour = $timeSplit[0];
    		$minute =  $timeSplit[1];
    		$second =   $timeSplit[2];
    
    		switch ($type) {
    			case "T" :
    				$result = $timePart;
    				break;
    			case "DT" :
    				$result = $dateThai . ' ' . $timePart;
    				break;
    			case "H" :
    				$result = $hour;
    				break;
    			case "M" :
    				$result = $minute;
    				break;
    			case "S" :
    				$result = $second;
    				break;
    			case "D" :
    			default:
    				$result = $dateThai;
    				break;
    		}
    	}
    	return  $result;
    }
    
    /**
     *
     * @param  DateTime      $dateObj that is DateTime Object from Sql Server.
     * @param  int|boolean   $setThaiYear - OPTIONAL flag to make Thai Year.
     * @return string
     * @throws Zend_Exception
     */
    public static function toDateString($dateObj, $format = "d/m/Y", $setThaiYear = 1)
    {
        if (empty($dateObj))
            return "";
        $str = "";
        try {
            if ($setThaiYear) {
                $year = $dateObj->format("Y") + 543;
                $format = str_replace("Y", $year, $format);
            } 
            $str = $dateObj->format($format);
        } catch (Exception $e) {
            $str = "";
        }
        return $str;
    }
    
    /**
     *
     * @param  DateTime     $startDateObj.
     * @param  DateTime     $endDateObj.
     * @return array
     * @throws Zend_Exception
     */
    public static function dateDiff($startDateObj, $endDateObj)
    {
        if (empty($startDateObj))
            return null;
        if (empty($endDateObj))
            return null;

        $years = $endDateObj->format("Y") - $startDateObj->format("Y");

        $endMonth = $endDateObj->format("m");
        $startMonth = $startDateObj->format("m"); 

        // Calculate Mounths
        $months = $endMonth - $startMonth;
        
        // Calculate Days
        // Todo: still problem
        $offsets = array();
        if ($years != 0){ //($years > 0){
            $offsets[] = $years . ((abs($years) == 1) ? ' year' : ' years');
        }
        if ($months != 0){ //($months > 0) {
            $offsets[] = $months . ((abs($months) == 1) ? ' month' : ' months');
        }
        $offsets = count($offsets) > 0 ? '+' . implode(' ', $offsets) : 'now';

        $days = $endDateObj - strtotime($offsets, $startDateObj->getTimestamp);
        $days = date('z', $days);

        if ($years == 0 && $months == 0) {
            $days = $endDateObj->format("d") - $startDateObj->format("d");
        }

        return array($years, $months, $days);
    }
    /**
     *
     * @param  DateTime     $startDateObj.
     * @param  DateTime     $endDateObj.
     * @return array
     * @throws Zend_Exception
     */
    public static function dayDiff($startDateObj, $endDateObj)
    {
        if (empty($startDateObj))
            return 0;
        if (empty($endDateObj))
            return 0;
        
        $days = (strtotime($endDateObj->format("Y-m-d")) - strtotime($startDateObj->format("Y-m-d")))/  ( 60 * 60 * 24 );

        return $days;
    }
    /**
     * 
     * @param string $date
     * @return string
     */
    public static function dateThaiToMySQL($date) {
    	if($date != ''){
    		$split = explode("/",$date);
    		$thaiYear = ($split[2])-543;
    		$split[2] = $thaiYear;
    		$result = $split[2] .'-'. str_repeat('0',2 - strlen($split[1])).$split[1].'-'. str_repeat('0',2 - strlen($split[0])).$split[0];
    	}
    	return  $result;
    }
    
    public static function dateEngToMySQL($date) {
    	if($date != ''){
    		$split = explode("/",$date);
    		$thaiYear = ($split[2]);
    		$split[2] = $thaiYear;
    		$result = $split[2] .'-'. str_repeat('0',2 - strlen($split[1])).$split[1].'-'. str_repeat('0',2 - strlen($split[0])).$split[0];
    	}
    	return  $result;
    }
    /**
     *
     * @param string $date
     * @return string
     */
    public static function dateThaiToOracle($date) {
    	if($date != ''){
    		$split = explode("/",$date);
    		$thaiYear = ($split[2])-543;
    		$split[2] = $thaiYear;
    		$result = $split[2] .'-'. str_repeat('0',2 - strlen($split[1])).$split[1].'-'. str_repeat('0',2 - strlen($split[0])).$split[0];
    	}
    	return  $result;
    }

    /**
     *
     * @param string $date
     * @param string $type กำหนดประเภท D = วันที่เท่านั้น, T = เวลาเท่านั้น, DT วันที่ และเวลา
     * @return string
     */
    public static function MySQLToDateThai($date, $type = 'D') {
    	$result = "";
    	if($date != '') {
    		$split = explode(" ",$date);
    		$datePart = $split[0];
    		$timePart = $split[1];
    
    		$dateSplit = explode("-",$datePart);
    
    		$dateThai = $dateSplit[2] .'/'. $dateSplit[1].'/'. ($dateSplit[0] + 543);
    		
    		$dateSQL_format = $dateSplit[0] .'-'. $dateSplit[1].'-'. ($dateSplit[2]);
    		
    		$dateThaiAbbr =
    		ltrim($dateSplit[2], '0')
    		.' '.
    		($dateSplit[1] == '01' ? 'ม.ค.' :
    		($dateSplit[1] == '02' ? 'ก.พ.' :
    		($dateSplit[1] == '03' ? 'มี.ค.' :
    		($dateSplit[1] == '04' ? 'เม.ย.' :
    		($dateSplit[1] == '05' ? 'พ.ค.' :
    		($dateSplit[1] == '06' ? 'มิ.ย.' :
    		($dateSplit[1] == '07' ? 'ก.ค.' :
    		($dateSplit[1] == '08' ? 'ส.ค.' :
    		($dateSplit[1] == '09' ? 'ก.ย.' :
    		($dateSplit[1] == '10' ? 'ต.ค' :
    		($dateSplit[1] == '11' ? 'พ.ย.' :
    		($dateSplit[1] == '12' ? 'ธ.ค.' : '/'.$dateSplit[1].'/'
    		))))))))))))
    		.' '.($dateSplit[0] + 543);
    		
    		$dateThaiFull =
    		ltrim($dateSplit[2], '0')
    		.' '.
    		($dateSplit[1] == '01' ? 'มกราคม' :
    		($dateSplit[1] == '02' ? 'กุมภาพันธ์' :
    		($dateSplit[1] == '03' ? 'มีนาคม' :
    		($dateSplit[1] == '04' ? 'เมษายน' :
    		($dateSplit[1] == '05' ? 'พฤษภาคม' :
    		($dateSplit[1] == '06' ? 'มิถุนายน' :
    		($dateSplit[1] == '07' ? 'กรกฎาคม' :
    		($dateSplit[1] == '08' ? 'สิงหาคม' :
    		($dateSplit[1] == '09' ? 'กันยายน' :
    		($dateSplit[1] == '10' ? 'ตุลาคม' :
    		($dateSplit[1] == '11' ? 'พฤศจิกายน' :
    		($dateSplit[1] == '12' ? 'ธันวาคม' : '/'.$dateSplit[1].'/'
    		))))))))))))
    		.' '.($dateSplit[0] + 543);
    
    		switch ($type) {
    			case "T" :
    				$result = $timePart;
    				break;
    			case "DT" :
    				$result = $dateThai . ' ' . $timePart;
    				break;
    			case "DT_Abbr" :
    				$result = $dateThaiAbbr;
    				break;
    			case "DT_F" :
    				$result = $dateThaiFull;
    				break;
    			case "SQL" :
    				$result = $dateSQL_format;
    				break;
    			case "D" :
    			default:
    				$result = $dateThai;
    				break;
    		}
    	}
    	return  $result;
    }
	
	/**
     *
     * @param string $date
     * @param string $type กำหนดประเภท D = วันที่เท่านั้น, T = เวลาเท่านั้น, DT วันที่ และเวลา
     * @return string
     */
    public static function MySQLToDateEng($date, $type = 'D') {
    	$result = "";
    	if($date != '') {
    		$split = explode(" ",$date);
    		$datePart = $split[0];
    		$timePart = $split[1];
    
    		$dateSplit = explode("-",$datePart);
    
    		$dateEng = $dateSplit[2] .'/'. $dateSplit[1].'/'. ($dateSplit[0]);
    		
    		$dateSQL_format = $dateSplit[0] .'-'. $dateSplit[1].'-'. ($dateSplit[2]);
    		
    		$dateEngAbbr =
    		ltrim($dateSplit[2], '0')
    		.' '.
    		($dateSplit[1] == '01' ? 'Jan' :
    		($dateSplit[1] == '02' ? 'Feb' :
    		($dateSplit[1] == '03' ? 'Mar' :
    		($dateSplit[1] == '04' ? 'Apr' :
    		($dateSplit[1] == '05' ? 'May' :
    		($dateSplit[1] == '06' ? 'Jun' :
    		($dateSplit[1] == '07' ? 'Jul' :
    		($dateSplit[1] == '08' ? 'Aug' :
    		($dateSplit[1] == '09' ? 'Sep' :
    		($dateSplit[1] == '10' ? 'Oct' :
    		($dateSplit[1] == '11' ? 'Nov' :
    		($dateSplit[1] == '12' ? 'Dec' : '/'.$dateSplit[1].'/'
    		))))))))))))
    		.' '.($dateSplit[0]);
    		
    		
    		$dateEngFull =
    		ltrim($dateSplit[2], '0')
    		.' '.
    		($dateSplit[1] == '01' ? 'January' :
    		($dateSplit[1] == '02' ? 'February' :
    		($dateSplit[1] == '03' ? 'March' :
    		($dateSplit[1] == '04' ? 'April' :
    		($dateSplit[1] == '05' ? 'May' :
    		($dateSplit[1] == '06' ? 'June' :
    		($dateSplit[1] == '07' ? 'July' :
    		($dateSplit[1] == '08' ? 'August' :
    		($dateSplit[1] == '09' ? 'September' :
    		($dateSplit[1] == '10' ? 'October' :
    		($dateSplit[1] == '11' ? 'November' :
    		($dateSplit[1] == '12' ? 'December' : '/'.$dateSplit[1].'/'
    		))))))))))))
    		.' '.($dateSplit[0]);
    
    		switch ($type) {
    			case "T" :
    				$result = $timePart;
    				break;
    			case "DT" :
    				$result = $dateEng . ' ' . $timePart;
    				break;
    			case "DT_Abbr" :
    				$result = $dateEngAbbr;
    				break;
    			case "DT_F" :
    				$result = $dateEngFull;
    				break;
    			case "SQL" :
    				$result = $dateSQL_format;
    				break;
    			case "D" :
    			default:
    				$result = $dateEng;
    				break;
    		}
    	}
    	return  $result;
    }

    /**
     *
     * @param string $date
     * @param string $type กำหนดประเภท D = วันที่เท่านั้น, T = เวลาเท่านั้น, DT วันที่ และเวลา
     * @return string
     */
    public static function OracleToDateThai($date, $type = 'D') {
    	$result = "";
    	if($date != '') {
    		$split = explode(" ",$date);
    		$datePart = $split[0];
    		$timePart = $split[1];
    
    		$dateSplit = explode("-",$datePart);
    
    		$dateThai = $dateSplit[2] .'/'. $dateSplit[1].'/'. ($dateSplit[0] + 543);
    
    		switch ($type) {
    			case "T" :
    				$result = $timePart;
    				break;
    			case "H" :
    				if (empty($timePart)) { 
    					$result = "";
    				} else {    					
	    				$timeSplit = explode(":",$timePart);
	    				$result = $timeSplit[0];    					
    				}
    				break;
    			case "i" :
    				if (empty($timePart)) { 
    					$result = "";
    				} else {    					
	    				$timeSplit = explode(":",$timePart);
	    				$result = $timeSplit[1];    					
    				}
    				break;
    			case "DT" :
    				$result = $dateThai . ' ' . $timePart;
    				break;
    			case "D" :
    			default:
    				$result = $dateThai;
    				break;
    		}
    	}
    	return  $result;
    }
    
    /**
     * 
     * @param string $date
     * @return string
     */ 
    public function dateEngTodateThai($date) {
    	 /*Convert dd/mm/2010 TO dd/mm/2553*/
    	if($date != ''){
    		$split = explode("/",$date);
    		$thaiYear = ($split[2])+543;
    		$split[2] = $thaiYear;
    		$result = $split[0].'/'.$split[1].'/'.$split[2];
    	}else {$result = '';}
    	return  $result;
    }

    public  function getThaiYear($date) {
    	$result = "";
    	if($date != '') {
    		$split = explode("/",$date);
    		$datePart = $split[0];
    		$timePart = $split[1];
    		$result = $split[2];
    	}
    	return  $result;
    }
    
    public  function getThaiMonth($date,$format = 'L') {
    	$result = "";
    	if($date != '') {
    		$split = explode("/",$date);
    		if ($format == "L") {
    			if ($split[1] == "1")
    				$result = "มกราคม";
    			else
    				if ($split[1] == "2")
    				$result = "กุมภาพันธ์";
    			else
    				if ($split[1] == "3")
    				$result = "มีนาคม";
    			else
    				if ($split[1] == "4")
    				$result = "เมษายน";
    			else
    				if ($split[1] == "5")
    				$result = "พฤษภาคม";
    			else
    				if ($split[1] == "6")
    				$result = "มิถุนายน";
    			else
    				if ($split[1] == "7")
    				$result = "กรกฏาคม";
    			else
    				if ($split[1] == "8")
    				$result = "สิงหาคม";
    			else
    				if ($split[1] == "9")
    				$result = "กันยายน";
    			else
    				if ($split[1] == "10")
    				$result = "ตุลาคม";
    			else
    				if ($split[1] == "11")
    				$result = "พฤศจิกายน";
    			else
    				if ($split[1] == "12")
    				$result = "ธันวาคม";
    			else
    				$result = "";
    
    		} else if ($format == "S") {
    
    			if ($split[1] == "1")
    				$result = "ม.ค";
    			else
    				if ($split[1] == "2")
    				$result = "ก.พ";
    			else
    				if ($split[1] == "3")
    				$result = "มี.ค";
    			else
    				if ($split[1] == "4")
    				$result = "เม.ย";
    			else
    				if ($split[1] == "5")
    				$result = "พ.ค";
    			else
    				if ($split[1] == "6")
    				$result = "มิ.ย";
    			else
    				if ($split[1] == "7")
    				$result = "ก.ค";
    			else
    				if ($split[1] == "8")
    				$result = "ส.ค";
    			else
    				if ($split[1] == "9")
    				$result = "ก.ย";
    			else
    				if ($split[1] == "10")
    				$result = "ต.ค";
    			else
    				if ($split[1] == "11")
    				$result = "พ.ย";
    			else
    				if ($split[1] == "12")
    				$result = "ธ.ค";
    			else
    				$result = "";
     
    		}
    	}
    
    	return  $result;
    }
    public  function getDay($date) {
    	$result = "";
    	if($date != '') {
    		$split = explode("/",$date);
    		$result = $split[0];
    	}
    	return  $result;
    }
    
    public static function advancesearchDate($date, $isThaiYear = 1)
    {
    	$result = "";
    	 	if($date != ''){
    	 		$split = explode("/",$date);
    	 		
    	 		if($split[2]>date('Y')){
					$searchYear = ($split[2])-543;
					$split[2] = $searchYear;
    	 		}

    	 		$result = $split[2] .'-'. str_repeat('0',2 - strlen($split[1])).$split[1].'-'. str_repeat('0',2 - strlen($split[0])).$split[0];
    	 	}
    	 	return $result;
    }
    
    
}
