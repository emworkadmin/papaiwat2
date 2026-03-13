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
class Emwork_Utility_File
{
	public static function  isIe(){
		if (isset($_SERVER['HTTP_USER_AGENT']) &&
				(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false))
			return true;
		else
			return false;
	}
	
	public static function isWinXp(){
		$iswinxp = false;
		$matches = NULL;
		if(preg_match('#(Windows|win32)\s+NT\s+([0-9.]+)\s*;#i', $USER_AGENT, $matches)) {
			$ntversion = @$matches[2];
			$r = version_compare($ntversion , "6.0");
			if($ntversion && ($r<0)) $iswinxp = true;
		}
		return $iswinxp;
	}
	
	public static function returnMIMEType($filename)
	{
		preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $fileSuffix);
	
		switch(strtolower($fileSuffix[1]))
		{
			case "js" :
				return "application/x-javascript";
	
			case "json" :
				return "application/json";
	
			case "jpg" :
			case "jpeg" :
			case "jpe" :
				return "image/jpg";
	
			case "png" :
			case "gif" :
			case "bmp" :
			case "tiff" :
				return "image/".strtolower($fileSuffix[1]);
	
			case "css" :
				return "text/css";
	
			case "xml" :
				return "application/xml";
	
			case "doc" :
			case "docx" :
				return "application/msword";
	
			case "xls" :
			case "xlt" :
			case "xlm" :
			case "xld" :
			case "xla" :
			case "xlc" :
			case "xlw" :
			case "xll" :
				return "application/vnd.ms-excel";
	
			case "ppt" :
			case "pps" :
				return "application/vnd.ms-powerpoint";
	
			case "rtf" :
				return "application/rtf";
	
			case "pdf" :
				return "application/pdf";
	
			case "html" :
			case "htm" :
			case "php" :
				return "text/html";
	
			case "txt" :
				return "text/plain";
	
			case "mpeg" :
			case "mpg" :
			case "mpe" :
				return "video/mpeg";
	
			case "mp3" :
				return "audio/mpeg3";
	
			case "mp4" :
				return "video/mp4";
	
			case "wav" :
				return "audio/wav";
	
			case "aiff" :
			case "aif" :
				return "audio/aiff";
	
			case "avi" :
				return "video/msvideo";
	
			case "wmv" :
				return "video/x-ms-wmv";
	
			case "mov" :
				return "video/quicktime";
	
			case "zip" :
				return "application/zip";
	
			case "tar" :
				return "application/x-tar";
	
			case "swf" :
				return "application/x-shockwave-flash";
	
			default :
				if(function_exists("mime_content_type"))
				{
					$fileSuffix = mime_content_type($filename);
				}
	
				return "unknown/" . trim($fileSuffix[0], ".");
		}
	}
}
