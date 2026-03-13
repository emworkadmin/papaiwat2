<?php
/*
 *
*/

class Zend_View_Helper_QrcodeCurrentUrl extends Zend_View_Helper_Abstract
{
	
	public function QrcodeCurrentUrl($currentUrl, $logoPath, $siteName)
	{
		$imagePath = '';
		$logoPath = empty($logoPath) ? '' : $logoPath;
		$siteName = empty($siteName) ? '' : $siteName;
				
		if (version_compare(PHP_VERSION, "7.0", "<=")) {
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'chillerlan' . DS . 'QRCode' . DS . 'QRCode.php';
		} else {
			require_once APPLICATION_ROOT . DS . 'library' . DS . 'vendor' . DS . 'autoload.php';
		}
		
		$currentUrlHashCode = md5($currentUrl . ":" . $logoPath . ":" . $siteName);
		$qrCodePath = APPLICATION_ROOT . DS . 'public' . DS . 'cache' . DS . 'qrcode' . DS;
		if (!file_exists($qrCodePath . $currentUrlHashCode . '.jpg')) {	
			$qrCode = new chillerlan\QRCode\QRCode();
			//$qrCodeData = $qrCode->render($currentUrl . $currentUrl . $currentUrl);
			$qrCodeData = $qrCode->render($currentUrl);
			$qrCodeData = str_replace("data:image/png;base64,", "", $qrCodeData);
			$tmpQrcodePath = APPLICATION_ROOT . DS . 'tmp' . DS . 'qrcode' . DS . $currentUrlHashCode . ".png";
			file_put_contents($tmpQrcodePath, base64_decode($qrCodeData)); // Save to tmp 
			
			// QrCode Image
			$imQrOriginal = imagecreatefrompng($tmpQrcodePath);
			$imQrOriginalWidth = imagesx($imQrOriginal);
			$imQrOriginalHeight = imagesy($imQrOriginal);
			$imQr = imagecreatetruecolor(205, 205);
			imagecopyresampled($imQr, $imQrOriginal, 0, 0, 0, 0, 205, 205, $imQrOriginalWidth, $imQrOriginalHeight);
			
			// New Image
			$im = imagecreate(205, 253);
			$white = imagecolorallocate($im, 255, 255, 255);
			$black = imagecolorallocate($im, 0, 0, 0);
			
			// เอา QrCode มาวาง
			imagecopymerge($im, $imQr, 0, 0, 0, 0, 205, 205, 100);
			
			// TODO: Logo
			$isPng = true;
			$resizeLogoHeight = 30;
			$imLogo = imagecreatefrompng(APPLICATION_ROOT . DS . 'public' . DS . 'images' . DS . 'logos' . DS . 'logo.png');
			$background = imagecolorallocate($imLogo , 0, 0, 0);
			imagecolortransparent($imLogo, $background);
			imagesavealpha($imLogo, false);
			
			$siteLogoFileID = System_Model_Setting::getValue("Logo", "Site");
			if (!empty($siteLogoFileID)) {
				$fileModel = new System_Model_Fileinfo();
				$fileData = $fileModel->item($siteLogoFileID);
				if (!empty($fileData->ID)) {
					if(file_exists(APPLICATION_ROOT . DS . 'files' . $fileData->Path . DS . $fileData->HashName)) {
						if (strtolower($fileData->Extension) == 'png') {
							$imLogo = imagecreatefrompng(APPLICATION_ROOT . DS . 'files' . $fileData->Path . DS . $fileData->HashName);
						} elseif (strtolower($fileData->Extension) == 'jpg' || strtolower($fileData->Extension) == 'gif') {
							$imLogo = imagecreatefromjpeg(APPLICATION_ROOT . DS . 'files' . $fileData->Path . DS . $fileData->HashName);
							$isPng = false;
						} elseif (strtolower($fileData->Extension) == 'gif') {
							$imLogo = imagecreatefromgif(APPLICATION_ROOT . DS . 'files' . $fileData->Path . DS . $fileData->HashName);
							$isPng = false;
						} else {
							$isPng = false;
						}
					}
				}
			}
			
			$imLogoWidth = imagesx($imLogo);
			$imLogoHeight = imagesy($imLogo);
			$imLogoResizeWidth = $resizeLogoHeight;
			
			$imLogoResize = imagecreatetruecolor($resizeLogoHeight, $resizeLogoHeight);
			imagefill($imLogoResize, 0, 0, imagecolorallocate($imLogoResize, 255, 255, 255));
			
			$this->scale_image($imLogo, $imLogoResize, "fit");
			
			
			/*
			$imLogoResizeHeight = $resizeLogoHeight;
			//$imLogoResizeWidth = round($imLogoWidth * $imLogoResizeHeight / $imLogoHeight);
			$imLogoResizeWidth = $imLogoResizeHeight;
			$imLogoResize = imagecreatetruecolor ($imLogoResizeHeight, $imLogoResizeWidth);
			//imagecopyresampled($imLogoResize, $imLogo, 0, 0, 0, 0, $imLogoResizeWidth, $imLogoResizeHeight, $imLogoWidth, $imLogoHeight);
			imagecopyresampled($imLogoResize, $imLogo, 0, 0, $offsetX, $offsetY, $imLogoResizeWidth, $imLogoResizeHeight, $square, $square);
			if ($isPng) { 
				$background = imagecolorallocate($imLogoResize , 0, 0, 0);
				imagecolortransparent($imLogoResize, $background);
				imagesavealpha($imLogoResize, false);
			}*/
			
			// เอารูป Logo มาวาง
			imagecopymerge($im, $imLogoResize, 18, 205, 0, 0, 30, 30, 100);	
			
			// TODO: Text site name
			$fontSize = 16;
			$fontPath = APPLICATION_ROOT . DS . 'public' . DS . 'dist' . DS . 'fonts' . DS . 'THSarabunNew.ttf';
			$imTitleHeight = 30;
			$imTitleWidth = 169 - 18 - $imLogoResizeWidth;
			imagettftext($im, $fontSize, 0, 18 + 5 + $imLogoResizeWidth, 205 + 20, $black, $fontPath, $siteName);
			
			// Export to image
			imagejpeg($im, $qrCodePath . $currentUrlHashCode . '.jpg');
			
			// Clear All
			imagedestroy($im);
			imagedestroy($$imQr);
			imagedestroy($imLogo);
			
			unlink($tmpQrcodePath);			
		}
		
		$imagePath = $this->view->baseUrl("cache/qrcode/" . $currentUrlHashCode . '.jpg');
		
// 		// Test		
// 		$imQr = imagecreatefrompng($qrCodePath . $currentUrlHashCode . '.png');
// 		$im = ImageCreate (205, 253);
// 		$white = ImageColorAllocate ($im, 255, 255, 255);
// 		$black = ImageColorAllocate($im, 0, 0, 0);
		
// 		// เอา QrCode มาวาง
// 		imagecopymerge($im, $imQr, 0, 0, 0, 0, 205, 205, 100);
		
// 		// TODO: Logo
// 		$resizeLogoHeight = 30;
// 		$imLogo = imagecreatefrompng(APPLICATION_ROOT . DS . 'public' . DS . 'images' . DS . 'logos' . DS . 'logo.png');		
// 		$imLogoWidth = imagesx($imLogo);
// 		$imLogoHeight = imagesy($imLogo);
// 		$imLogoResizeHeight = $resizeLogoHeight;
// 		$imLogoResizeWidth = round($imLogoWidth * $imLogoResizeHeight / $imLogoHeight);
// 		$imLogoResize = ImageCreate ($imLogoResizeHeight, $imLogoResizeWidth);
// 		imagecopyresampled($imLogoResize, $imLogo, 0, 0, 0, 0, $imLogoResizeWidth, $imLogoResizeHeight, $imLogoWidth, $imLogoHeight);
// 		imagecopymerge($im, $imLogoResize, 18, 205, 0, 0, 30, 30, 100);
		
// 		// TODO: Text site name
// 		$fontSize = 16;
// 		$fontPath = APPLICATION_ROOT . DS . 'public' . DS . 'dist' . DS . 'fonts' . DS . 'THSarabunNew.ttf';
// 		$imTitleHeight = 30;
// 		$imTitleWidth = 169 - 18 - $imLogoResizeWidth;
// 		imagettftext($im, $fontSize, 0, 18 + 5 + $imLogoResizeWidth, 205 + 20, $black, $fontPath, $siteName);
		
// 		// Export to image
// 		imagejpeg($im, APPLICATION_ROOT . DS . 'tmp' . DS . 'qrcode' . DS . "image.jpg");
		
// 		// Clear All
// 		imagedestroy($im);
// 		imagedestroy($$imQr);
// 		imagedestroy($imLogo);
		
		
		return $imagePath;
	}
	
	function scale_image($src_image, $dst_image, $op = 'fit') {
		$src_width = imagesx($src_image);
		$src_height = imagesy($src_image);
		
		$dst_width = imagesx($dst_image);
		$dst_height = imagesy($dst_image);
		
		// Try to match destination image by width
		$new_width = $dst_width;
		$new_height = round($new_width*($src_height/$src_width));
		$new_x = 0;
		$new_y = round(($dst_height-$new_height)/2);
		
		// FILL and FIT mode are mutually exclusive
		if ($op =='fill')
			$next = $new_height < $dst_height; else $next = $new_height > $dst_height;
			
			// If match by width failed and destination image does not fit, try by height
			if ($next) {
				$new_height = $dst_height;
				$new_width = round($new_height*($src_width/$src_height));
				$new_x = round(($dst_width - $new_width)/2);
				$new_y = 0;
			}
			
			// Copy image on right place
			imagecopyresampled($dst_image, $src_image , $new_x, $new_y, 0, 0, $new_width, $new_height, $src_width, $src_height);
	}
	
}

?>
