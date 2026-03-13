<?php

class Emwork_Jasper_Jasper {

	private $_objDbConnect;
	private $_objJep;
	private $_objPrint;
	private $_objReport;
	private $_objStream;
	private $_pdfFont;

	function __construct($username, $password, $dataSourceServerUrl) {
		$this->initReport($username, $password, $dataSourceServerUrl);
	}

	function initReport($username, $password, $dataSourceServerUrl) {
		$this->_objStream = new Java("java.io.ByteArrayOutputStream");
		$this->_objJep = new Java("net.sf.jasperreports.engine.JRExporterParameter");
		$objClass = new Java("java.lang.Class");
		// Oracle
		$objClass->forName("oracle.jdbc.driver.OracleDriver");
		//// postgresql
		//$objClass->forName("org.postgresql.Driver");
		$objDbm = new Java("java.sql.DriverManager");
		$this->_objDbConnect = $objDbm->getConnection($dataSourceServerUrl, $username, $password);
		return;
	}

	public function compileReport($jrxmlReport) {
		$objJcm = new Java("net.sf.jasperreports.engine.JasperCompileManager");
		$this->_objReport = $objJcm->compileReport($jrxmlReport);
		return;
	}

	public function setPdfFont($path) {
		$this->_pdfFont = $path;
	}

	public function setParams($mapArray) {
		$map = new Java("java.util.HashMap");
		foreach ($mapArray as $key => $value) {
			$map->put($key, $value);
		}
		$objJfm = new Java("net.sf.jasperreports.engine.JasperFillManager");
		$this->_objPrint = $objJfm->fillReport($this->_objReport, $map, $this->_objDbConnect);
		return;
	}

	public function toHtml() {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRHtmlExporter");
		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		$objJhe->setParameter($this->_objJep->OUTPUT_STREAM, $this->_objStream);
		$objJhe->exportReport();
		echo $this->_objStream->toString();
		return;
	}

	public function toHtmlStr() {
		$imagesMap = new Java("java.util.HashMap");
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRHtmlExporter");


		$htmlParams = new Java("net.sf.jasperreports.engine.export.JRHtmlExporterParameter");
		$objJhe->setParameter($htmlParams->IMAGES_MAP, $imagesMap);
		$objJhe->setParameter($htmlParams->IMAGES_URI, "image?image=");
		$objJhe->setParameter($htmlParams->SIZE_UNIT, $htmlParams->SIZE_UNIT_POINT );
		$objJhe->setParameter($htmlParams->IS_USING_IMAGES_TO_ALIGN , false);

		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		$string = new Java("java.lang.StringBuffer");
		$objJhe->setParameter($this->_objJep->OUTPUT_STRING_BUFFER, $string);
		$objJhe->exportReport();
		return $string->toString();
		//return;
	}

	public function toPreview($imageUri = "image?image=") {
		$imagesMap = new Java("java.util.HashMap");
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRHtmlExporter");
		//java_session()->setAttribute("IMAGES_MAP", $imagesMap);

		$htmlParams = new Java("net.sf.jasperreports.engine.export.JRHtmlExporterParameter");
		$objJhe->setParameter($htmlParams->IMAGES_MAP, $imagesMap);
		$objJhe->setParameter($htmlParams->IMAGES_URI, $imageUri);
		//$objJhe->setParameter($htmlParams->ZOOM_RATIO, "2");
		$objJhe->setParameter($htmlParams->SIZE_UNIT, $htmlParams->SIZE_UNIT_POINT );
		//$objJhe->setParameter($htmlParams->CHARACTER_ENCODING , "TIS-620");
		$objJhe->setParameter($htmlParams->IS_USING_IMAGES_TO_ALIGN , false);

		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		$objJhe->setParameter($this->_objJep->PAGE_INDEX , 0);
		$string = new Java("java.lang.StringBuffer");
		$objJhe->setParameter($this->_objJep->OUTPUT_STRING_BUFFER, $string);
		$objJhe->exportReport();
		return $string->toString();
		//return;
	}
	public function toPreviewImage($imageUri = "image?image=") {
		$BufferedImage = new Java("java.awt.image.BufferedImage");
		$pageImage = new Java("java.awt.image.BufferedImage", $this->_objPrint->getPageWidth() + 1, $this->_objPrint->getPageHeight() + 1, $BufferedImage->TYPE_INT_RGB);

		$JRGraphics2DExporterParameter = new Java("net.sf.jasperreports.engine.export.JRGraphics2DExporterParameter");
		$exporter = new Java("net.sf.jasperreports.engine.export.JRGraphics2DExporter");
		$exporter->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		$exporter->setParameter($JRGraphics2DExporterParameter->GRAPHICS_2D, $pageImage->getGraphics());
		$exporter->setParameter($this->_objJep->PAGE_INDEX , 0);
		$exporter->exportReport();
		header("Content-type: image/jpeg, jpg");
		$imageIO = new Java("javax.imageio.ImageIO");
		$reult = $imageIO->write($pageImage, "jpeg", $this->_objStream);

		echo java_cast($this->_objStream->toByteArray(), "S");
	}

	public function toPrinter() {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRPrintServiceExporter");
		$printExportParam = new Java("net.sf.jasperreports.engine.export.JRPrintServiceExporterParameter");
		$prn = $objJhe->checkAvailablePrinters();
		if ($prn) {
			$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
			$objJhe->setParameter($printExportParam->DISPLAY_PRINT_DIALOG, true);
			$objJhe->exportReport();
		}
		return;
	}

	public function toPrinterNew() {
		/*
		 *
		JasperPrint print = JasperFillManager.fillReport( this.class.getResource("/classpath/yourReport.jasper").getPath(), new HashMap(), new yourReportDataSource());
		PrinterJob job = PrinterJob.getPrinterJob();
		///* Create an array of PrintServices
		PrintService[] services = PrintServiceLookup.lookupPrintServices(null, null);
		int selectedService = 0;
		///* Scan found services to see if anyone suits our needs
		for(int i = 0; i < services.length;i++){
		if(services[i].getName().toUpperCase().contains("Your printer's name")){
		///*If the service is named as what we are querying we select it
		selectedService = i;
		}
		}
		job.setPrintService(services[selectedService]);

		PrintRequestAttributeSet printRequestAttributeSet = new HashPrintRequestAttributeSet();
		MediaSizeName mediaSizeName = MediaSize.findMedia(4,4,MediaPrintableArea.INCH);
		printRequestAttributeSet.add(mediaSizeName);
		printRequestAttributeSet.add(new Copies(1));

		JRPrintServiceExporter exporter;
		exporter = new JRPrintServiceExporter();
		exporter.setParameter(JRExporterParameter.JASPER_PRINT, print);
		///* We set the selected service and pass it as a paramenter
		exporter.setParameter(JRPrintServiceExporterParameter.PRINT_SERVICE, services[selectedService]);
		exporter.setParameter(JRPrintServiceExporterParameter.PRINT_SERVICE_ATTRIBUTE_SET, services[selectedService].getAttributes());
		exporter.setParameter(JRPrintServiceExporterParameter.PRINT_REQUEST_ATTRIBUTE_SET, printRequestAttributeSet);
		exporter.setParameter(JRPrintServiceExporterParameter.DISPLAY_PAGE_DIALOG, Boolean.FALSE);
		exporter.setParameter(JRPrintServiceExporterParameter.DISPLAY_PRINT_DIALOG, Boolean.TRUE);
		exporter.exportReport();
		*/
		$PrinterJob = new Java("java.awt.print.PrinterJob");
		$job = $PrinterJob->getPrinterJob();

		$PrintServiceLookup = new Java("javax.print.PrintServiceLookup");
		$services = $PrintServiceLookup->lookupPrintServices(null, null);
		$selectedService = 0;
		for ($i = 0; $i < count($services); $i++) {
			if ($services[$i]->getName()->toUpperCase()->contains("Your printer's name")) {
				///*If the service is named as what we are querying we select it
				$selectedService = $i;
			}
		}
		$job->setPrintService($services[$selectedService]);

		$objJhe = new Java("net.sf.jasperreports.engine.export.JRPrintServiceExporter");
		$printExportParam = new Java("net.sf.jasperreports.engine.export.JRPrintServiceExporterParameter");
		$prn = $objJhe->checkAvailablePrinters();
		if ($prn) {
			$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
			// New
			$objJhe->setParameter($printExportParam->PRINT_SERVICE, $services[$selectedService]);
			$objJhe->setParameter($printExportParam->PRINT_SERVICE_ATTRIBUTE_SET, $services[$selectedService]->getAttributes());
			//$objJhe->setParameter($printExportParam->PRINT_REQUEST_ATTRIBUTE_SET, printRequestAttributeSet);
			$objJhe->setParameter($printExportParam->DISPLAY_PAGE_DIALOG, false);
			$objJhe->setParameter($printExportParam->DISPLAY_PRINT_DIALOG, true);
			$objJhe->exportReport();
		}
		return;
	}

	public function toPDFTest($fileName="") {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRPdfExporter");
		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		if ($fileName == "") {
			$objJhe->setParameter($this->_objJep->OUTPUT_STREAM, $this->_objStream);
			$objJhe->exportReport();
			header("Content-type: application/pdf");
			echo java_cast($this->_objStream->toByteArray(), "S");
		} else {
			$objJhe->setParameter($this->_objJep->OUTPUT_FILE_NAME, $fileName);
			$objJhe->exportReport();
		}

		return;
	}

	public function toPDF() {

		$fontMap = new Java("java.util.HashMap");

		$font = new Java("net.sf.jasperreports.engine.export.PdfFont", $this->_pdfFont . "micross.ttf" , "Identity-H", true);

		$keyBold = new Java("net.sf.jasperreports.engine.export.FontKey", "Microsoft Sans Serif", true, false);
		$fontMap->put($keyBold, $font);

		$keyNormal = new Java("net.sf.jasperreports.engine.export.FontKey", "Microsoft Sans Serif", false, false);
		$fontMap->put($keyNormal, $font);



		$objJepPdf = new Java("net.sf.jasperreports.engine.export.JRPdfExporterParameter");

		$objJhe = new Java("net.sf.jasperreports.engine.export.JRPdfExporter");
		$objJhe->setParameter($objJepPdf->CHARACTER_ENCODING, "UTF-8");
		$objJhe->setParameter($objJepPdf->FONT_MAP, $fontMap);
		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);

		if ($fileName == "") {
			$objJhe->setParameter($this->_objJep->OUTPUT_STREAM, $this->_objStream);
			$objJhe->exportReport();
			header("Content-type: application/pdf");
			echo java_cast($this->_objStream->toByteArray(), "S");
		} else {
			$objJhe->setParameter($this->_objJep->OUTPUT_FILE_NAME, $fileName);
			$objJhe->exportReport();
		}

		return;
	}

	public function toExcel($fileName="") {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRXlsExporter");
		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		//$excelParam = new Java("net.sf.jasperreports.engine.export.JRXlsExporterParameter");
		//$objJhe->setParameter($this->_objJep->IGNORE_PAGE_MARGINS, true);
		//$objJhe->setParameter($excelParam->IS_ONE_PAGE_PER_SHEET, true);
		if ($fileName == "") {
			$objJhe->setParameter($this->_objJep->OUTPUT_STREAM, $this->_objStream);
			$objJhe->exportReport();
			header("Content-type: application/vnd.ms-excel");
			echo java_cast($this->_objStream->toByteArray(), "S");
		} else {
			$objJhe->setParameter($this->_objJep->OUTPUT_FILE_NAME, $fileName);
			$objJhe->exportReport();
		}

		return;
	}

	public function toCsv() {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRCsvExporter");
		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		if ($fileName == "") {
			$objJhe->setParameter($this->_objJep->OUTPUT_STREAM, $this->_objStream);
			$objJhe->exportReport();
			header("Content-type: text/csv");
			echo java_cast($this->_objStream->toByteArray(), "S");
		} else {
			$objJhe->setParameter($this->_objJep->OUTPUT_FILE_NAME, $fileName);
			$objJhe->exportReport();
		}

		return;
	}

	public function toCsvStr() {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRCsvExporter");
		$csvParam = new Java("net.sf.jasperreports.engine.export.JRCsvExporterParameter");
		$objJhe->setParameter($csvParam->FIELD_DELIMITER, ",");
		$objJhe->setParameter($csvParam->RECORD_DELIMITER, "\n");
		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		$string = new Java("java.lang.StringBuffer");
		$objJhe->setParameter($this->_objJep->OUTPUT_STRING_BUFFER, $string);
		$objJhe->exportReport();
		return $string->toString();
	}

	public function toRtf() {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRRtfExporter");
		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		if ($fileName == "") {
			$objJhe->setParameter($this->_objJep->OUTPUT_STREAM, $this->_objStream);
			$objJhe->exportReport();
			header("Content-type: application/rtf");
			echo java_cast($this->_objStream->toByteArray(), "S");
		} else {
			$objJhe->setParameter($this->_objJep->OUTPUT_FILE_NAME, $fileName);
			$objJhe->exportReport();
		}

		return;
	}

	public function toWord() {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRRtfExporter");
		$objJhe->setParameter($this->_objJep->JASPER_PRINT, $this->_objPrint);
		if ($fileName == "") {
			$objJhe->setParameter($this->_objJep->OUTPUT_STREAM, $this->_objStream);
			$objJhe->exportReport();
			header("Content-type: application/vnd.ms-word");
			echo java_cast($this->_objStream->toByteArray(), "S");
		} else {
			$objJhe->setParameter($this->_objJep->OUTPUT_FILE_NAME, $fileName);
			$objJhe->exportReport();
		}

		return;
	}

	public function hasPrinter() {
		$objJhe = new Java("net.sf.jasperreports.engine.export.JRPrintServiceExporter");
		$prn = $objJhe->checkAvailablePrinters();
		return $prn;
	}

}

?>