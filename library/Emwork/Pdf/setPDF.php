<?php


// เธเนเธฒเน€เธฃเธดเนเธกเธ•เนเธเธ•เนเธฒเธเน เธชเธฒเธกเธฒเธฃเธ–เน€เธเนเธฒเนเธเธเธณเธซเธเธ”เนเธ”เนเธ—เธตเนเนเธเธฅเน tcpdf_config.php เนเธเนเธเธฅเน€เธ”เธญเธฃเน config
// เธชเธฃเนเธฒเธ PDF document เนเธซเธกเน
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); 

// เธเธณเธซเธเธ”เธฃเธฒเธขเธฅเธฐเน€เธญเธตเธขเธ”เธเธญเธเน€เธญเธเธชเธฒเธฃ pdf เนเธชเธ”เธเน€เธกเธทเนเธญเธเธฅเธดเธเธเธงเธฒเธ—เธตเนเนเธเธฅเน PDF เนเธฅเนเธงเน€เธฅเธทเธญเธ Document Property
$pdf->SetCreator(PDF_CREATOR); // เน€เธเธฃเธทเนเธญเธเธกเธทเธญเธชเธฃเนเธฒเธ PDF  เธเนเธฒเน€เธฃเธดเนเธก PDF_CREATOR = TCPDF
$pdf->SetAuthor('Ninenik'); // เธเธทเนเธญเธเธนเนเธชเธฃเนเธฒเธเนเธเธฅเน PDF
$pdf->SetTitle('USING HTML 2 PDF FROM TCPDF');//  เธเธณเธซเธเธ” Title
$pdf->SetSubject('HTML 2 PDF'); // เธเธณเธซเธเธ” Subject
$pdf->SetKeywords('TCPDF, PDF, USING HTML 2 PDF'); // เธเธณเธซเธเธ” Keyword

//   เธเธณเธซเธเธ”เธเนเธฒเน€เธฃเธดเนเธกเธ•เนเธเธชเธณเธซเธฃเธฑเธ Header
//	PDF_HEADER_LOGO  เนเธฅเนเธเนเธฃเธนเธเธ เธฒเธเธชเนเธงเธ Header
//	PDF_HEADER_LOGO_WIDTH เธเธงเธฒเธกเธเธงเนเธฒเธเธเธญเธเนเธฅเนเธเน เน€เธเนเธ เธกเธดเธฅเน€เธกเธ•เธฃ (mm)
//	PDF_HEADER_TITLE เธซเธฑเธงเน€เธฃเธทเนเธญเธเธเธญเธ Header
//	PDF_HEADER_STRING เธเนเธญเธเธงเธฒเธกเธ—เธตเนเธ•เนเธญเธเธเธฒเธฃเนเธชเธ”เธเนเธเธชเนเธงเธ header เธเธถเนเธเธเธฃเธฃเธ—เธฑเธ”เนเธซเธกเนเนเธเน \n
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// เธเธณเธซเธเธ”เธเนเธฒเน€เธฃเธดเนเธกเธ•เนเธ Font เธชเธณเธซเธฃเธฑเธเธเนเธญเธเธงเนเธฒเธ
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

//เธ•เธฑเนเธเธเนเธฒเธซเธเนเธฒเธเธฃเธฐเธ”เธฒเธฉ
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

//เธเธณเธซเธเธ”เธเธฒเธฃเนเธเนเธเธซเธเนเธฒเธญเธฑเธ•เนเธเธกเธฑเธ•เธด
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// เธเธณเธซเธเธ”เธญเธฑเธ”เธฃเธฒเธชเนเธงเธเธเธญเธเธฃเธนเธเธ เธฒเธ
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO); 

// เธเธณเธซเธเธ”เธเธฅเธธเนเธกเธ เธฒเธฉเธฒ
$pdf->setLanguageArray($l); 

// เธเธณเธซเธเธ” Font เธเธฃเธ“เธตเนเธเนเธ เธฒเธฉเธฒเนเธ—เธขเนเธเน freeserif
$pdf->SetFont('freeserif', '', 10);
?>