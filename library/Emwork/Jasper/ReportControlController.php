<?php

require_once("http://localhost:8080/JavaBridge/java/Java.inc");

/**
 * Description of ReportControlController
 *
 * @author Krerkkist 21/2/2011
*/
class ReportControlController extends Zend_Controller_Action {

	//public $actionName = 'unit';
	//protected $logmodel = null;
	//private $local;

	private $params;
	private $jreport;
	private $auth;
	private $local;
	private $reportfilename;
	private $ReportName;
	private $ReportShortName;
	private $ProgramName;
	private $DataSourceName;
	private $PrintBy;
	private $reportPath;
	private $filename;

	private $_reportTitleName = 'ชื่อรายงาน';
	private $_reportTitleShortName = 'ชื่อย่อรายงาน';
	private $_reportFileName = 'reportname';
	private $_params;
	private $_isInside = true;

	public function init() {
		$auth = Zend_Auth::getInstance();
		$this->auth = $auth->getIdentity();
		$this->local = new local();

		$config = new Zend_Config_Ini('../application/configs/application.ini', 'oracle');
		$this->params = array('username' => $config->db->params->username,
				'password' => $config->db->params->password,
				'dbname' => $config->db->params->dbname,
				'jdbc' => $config->db->params->jdbc,
				'reportpath' => $config->db->params->reportpath,
				'reportprogram' => $config->db->params->reportprogram,
				'reportsource' => $config->db->params->reportsource);

		$this->filename = 'รายงาน';
	}

	protected function setReportTitleName($str){
		$this->_reportTitleName = $str;
	}
	protected function setReportTitleShortName($str){
		$this->_reportTitleShortName = $str;
	}
	protected function setReportFileName($str){
		$this->_reportFileName = $str;
	}
	protected function setReportExportFileName($str){
		$this->filename = $str;
	}
	protected function setParam($arrParams){
		$this->_params = $arrParams;
	}
	protected function setIsInside($isInside){
		$this->_isInside = $isInside;
	}


	private function settingReport($isCsv = false)
	{
		$this->jreport = new emjasper($this->params['username'], $this->params['password'], $this->params['jdbc']);
		if (!$isCsv)
			$this->reportfilename = $this->_reportFileName . ".jrxml";
		else
			$this->reportfilename = $this->_reportFileName . "_csv.jrxml";

		$this->ReportName = $this->local->dataout($this->_reportTitleName);
		$this->ReportShortName = $this->local->dataout($this->_reportTitleShortName);
		$this->ProgramName = $this->params['reportprogram'];
		$this->DataSourceName = $this->params['reportsource'];
		$this->reportPath = $this->params['reportpath'];
		if ($this->_isInside)
			$this->PrintBy = $this->local->dataout($this->auth->Displayname);
		else
			$this->PrintBy = $this->local->dataout($this->auth->Displayname);

		$this->jreport->compileReport($this->reportPath . $this->reportfilename);
		$param = array('ReportName' => $this->ReportName,
				'ReportShortName' => $this->ReportShortName,
				'ProgramName' => $this->ProgramName,
				'DataSourceName' => $this->DataSourceName,
				'PrintBy' => $this->PrintBy,
				'ReportPath' => $this->reportPath,
				'Query' => '11'
		);

		foreach ($this->_params as $key => $value) {
			$param[$key] = trim(iconv("TIS-620","UTF-8",$value));
		}

		$this->jreport->setParams($param);
	}

	protected function to_preview() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->settingReport();

		$str = $this->jreport->toPreview("/report/image?image=");
		//$str = str_replace('UTF-8', 'TIS-620', $str);
		//$str = iconv("UTF-8","TIS-620",($str));
		echo $str;
	}
	protected function to_pdf() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->settingReport();
		$this->jreport->setPdfFont($this->params['reportpath']."fonts\\\\");
		header("Content-Disposition: attachment; filename=$this->filename.pdf");
		$this->jreport->toPDF();
	}
	protected function to_word() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->settingReport();
		header("Content-Disposition: attachment; filename=$this->filename.doc");
		$this->jreport->toWord();
	}
	protected function to_rtf() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->settingReport();
		header("Content-Disposition: attachment; filename=$this->filename.rtf");
		$this->jreport->toRtf();
	}
	protected function to_excel() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->settingReport();
		header("Content-Disposition: attachment; filename=$this->filename.xls");
		$this->jreport->toExcel();
	}
	protected function to_csv() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->settingReport(true);
		$str = $this->jreport->toCsvStr();
		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=$this->filename.csv");
		echo iconv("UTF-8","TIS-620",$str);
		//echo $str;
	}
	protected function to_html() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->settingReport();

		echo "<meta content=\"text/html; charset=tis-620\" http-equiv=\"Content-Type\">";
		$str = $this->jreport->toHtmlStr();
		$result = iconv("UTF-8","TIS-620",urldecode($str));
		echo $result;
	}
	private function to_previewimage() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->settingReport();
		//header("Content-Disposition: attachment; filename=$this->filename.pdf");
		$this->jreport->toPreviewImage();
	}
	public function image() {

		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();

		$im = @imagecreatefrompng($this->params['reportpath'] . "images\\PPSLogo2cm.png");
		/* See if it failed */
		if(!$im)
		{
			/* Create a blank image */
			$im  = imagecreatetruecolor(150, 30);
			$bgc = imagecolorallocate($im, 255, 255, 255);
			$tc  = imagecolorallocate($im, 0, 0, 0);
			imagefilledrectangle($im, 0, 0, 150, 30, $bgc);
			/* Output an error message */
			imagestring($im, 1, 5, 5, 'Error loading ' . $imgname, $tc);
		}
		//header('Content-Type: image/png');
		imagepng($im);
		imagedestroy($im);
	}
}

