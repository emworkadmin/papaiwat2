<?php
require_once("setPDF.php");
// เน€เธเธดเนเธกเธซเธเนเธฒเนเธ PDF 
$pdf->AddPage();

// เธเธณเธซเธเธ” HTML code เธซเธฃเธทเธญเธฃเธฑเธเธเนเธฒเธเธฒเธเธ•เธฑเธงเนเธเธฃเธ—เธตเนเธชเนเธเธกเธฒ
//	เธเธฃเธ“เธตเธเธณเธซเธเธ”เนเธ”เธขเธ•เธฃเธ
//	เธ•เธฑเธงเธญเธขเนเธฒเธ เธเธฃเธ“เธตเธฃเธฑเธเธเธฒเธเธ•เธฑเธงเนเธเธฃ
// $htmlcontent =$_POST['HTMLcode'];
$htmlcontent='<p>เธ—เธ”เธชเธญเธ</p>';
$htmlcontent=stripslashes($htmlcontent);
$htmlcontent=AdjustHTML($htmlcontent);

// เธชเธฃเนเธฒเธเน€เธเธทเนเธญเธซเธฒเธเธฒเธ  HTML code
$pdf->writeHTML($htmlcontent, true, 0, true, 0);

// เน€เธฅเธทเนเธญเธ pointer เนเธเธซเธเนเธฒเธชเธธเธ”เธ—เนเธฒเธข
$pdf->lastPage();

// เธเธดเธ”เนเธฅเธฐเธชเธฃเนเธฒเธเน€เธญเธเธชเธฒเธฃ PDF
$pdf->Output('test.pdf', 'I');
?>