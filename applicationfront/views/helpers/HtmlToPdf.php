<?php
/*
 *
*/

class Zend_View_Helper_HtmlToPdf extends Zend_View_Helper_Abstract
{
	public function HtmlToPdf($url, $output)
	{
		$result = false;		

		$out = array();
		$ret = -1;		

		// Config
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/pdf.ini', 'setting');
		
		$option = ' --footer-line --footer-center "http://jobpwdthai.org/" --footer-right "[page]/[toPage]" --footer-font-size 8 --minimum-font-size 8 --encoding "UTF-8" -L 10mm -R 10mm --viewport-size "1000x768" ';
		$binaryPath = $config->binarypath;
		
		$command = '"' . $binaryPath . '" ' . $option . ' "' . $url . '" "' . $output . '"';
		
		exec($command, $out, $ret);
		
		$result = $ret === 0;

		return $result;
	}
}

?>
