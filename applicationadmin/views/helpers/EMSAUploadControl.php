<?php
/*
 *
*/

class Zend_View_Helper_EMSAUploadControl extends Zend_View_Helper_Abstract
{
	private $imageExtension = array("jpg", "gif", "png", "jpeg", "bmp");
	public function EMSAUploadControl($controlName, $folderName, $filePathArray = array(), $options = array())
	{
		$urlHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Url');
		
		// Init
		$emsaConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/emsa.ini', 'api');
		$emsaUploadPath = $emsaConfig->fileuploadpath;
		$emsaGetPath = $emsaConfig->filegetpath;
		
		// Options
		// multi, isimage, showimage, exts, maxsize, readonly, showlink
		$readonly = $options["readonly"] ? true : false;
		$showlink = $options["showlink"] ? true : false;
		//$showlink = $options["showlink"] ? true : ($readonly ? false : true);
		$maxSize = empty($options["maxsize"])  ? 10 : $options["maxsize"];
		$multi = $options["multi"] ? true : false;
		$multiStr = $multi ? 'true' : 'false';
		$isimage = $options["isimage"] ? true : false;
		$showimage = $options["showimage"] ? ($isimage ? true : false) : false;
		$fileExts = empty($options["exts"]) ? ($isimage ? $this->imageExtension : null) : $options["exts"];
		
		$fileExtsScript = "";
		if (count($fileExts) > 0) {
			$fileExtsScript = "            'fileTypeExts': '";
			$ext = $extHint = "";
			foreach ($fileExts as $value) {
				if (substr($value, 0, 1) != ".") {
					$value = "." . $value;
				}
				$ext .= (empty($ext) ? "" : ";") . "*" . $value;
				$extHint .= (empty($extHint) ? "" : ",") . " " . $value;
			}
			$fileExtsScript .= $ext . "', " . "\n";
			$fileExtsHint = $extHint;
		}
		if (empty($ext)) {
			$ext = "*.*";
		}
		$ext = ": ({$ext})";

		if (empty($controlName))
			$controlName = "fileupload";

		$imageScript = "";
		if ($showimage) {
			$imageScript = "                    divHtml += '<br /><img src=\"/file/{$folderName}?filename=' + filepath + '\" title=\"' + filename + '\" alt=\"' + filename + '\" style=\"max-width:150px;\" />';";
		}

		$fileUploadControl = $this->view->formFile("fup$controlName", null);

		if (!is_array($filePathArray)) {
			$filePathArray = array($filePathArray);
		}
		
		if($showimage){
			$linkControl .= "<style>";
			$linkControl .= ".uploadmainBox {";
			$linkControl .= "	width: 150px; height:100px; background-color: #e4e4e4; border-radius: 10px; position: relative; display: block; float: left; margin-right: 10px; margin-bottom: 10px; box-shadow: 1px 1px 1px #999;";
			$linkControl .= "}";
			$linkControl .= ".uploadmainBox-Title {";
			$linkControl .= "	color: #2C93D1; font-weight: bold;";
			$linkControl .= "}";
			$linkControl .= ".uploadmainBox:hover {";
			$linkControl .= "	background-color: #d3d3d3;";
			$linkControl .= "}";
			$linkControl .= ".previewBox {";
			$linkControl .= "	width: 30px; height:30px; background-color: #7e7e7e; border-radius: 10px; position: absolute; bottom: 5px; right: 40px; text-align: center;";
			$linkControl .= "}";
			$linkControl .= ".previewBox:hover {";
			$linkControl .= "	background-color: #545454;";
			$linkControl .= "}";
			$linkControl .= ".downloadBox {";
			$linkControl .= "	width: 30px; height:30px; background-color: #7e7e7e; border-radius: 10px; position: absolute; bottom: 5px; right: 5px; text-align: center;";
			$linkControl .= "}";
			$linkControl .= ".downloadBox:hover {";
			$linkControl .= "	background-color: #545454;";
			$linkControl .= "}";
			$linkControl .= ".overflowText{";
			$linkControl .= "	white-space: nowrap;";
			$linkControl .= "	text-overflow: ellipsis;";
			$linkControl .= "	overflow: hidden";
			$linkControl .= "}";
			$linkControl .= "</style>";
		}

		foreach ($filePathArray as $key => $filePath) {
			if (!empty($filePath)) {
				
				$uid = uniqid();
				
				$filename = $filePath['file_name'];
				$filePathSrc = $filePath['hash_name'];
				$fileSize = $this->getFileSize($filePathSrc);
				$fileSize = empty($fileSize) ? "" : " (" . $fileSize . ")";
				$ext = strtolower(substr(strrchr($filename, '.'), 1));

				$linkControl .= "<div id='div{$controlName}_{$filePathID}'>";
				if ($readonly) {
					//$linkControl .= "<div><a href='" . $emsaGetPath . "/file/{$filePathSrc}' style='color: #2c93c7;'><b title='{$filename}'>- {$filename}</b></a></div>";
				}
				if ($showlink) {
					$linkControl .= "<div><a href='" . $emsaGetPath . "/file/{$filePathSrc}/type/download' style='color: #2c93c7;'><b title='{$filename}'>- {$filename}</b></a></div>";
				}
				if ($showimage) {
					// Please import lightbox.js & lightbox.css for this option.
					
					$linkControl .= "<style>";
					$linkControl .= ".Box-".$uid." {";
					if(in_array($ext, $this->imageExtension)){
						$linkControl .= "	background: url('" . $emsaGetPath . "/file/{$filePathSrc}'); ";
					}else if(in_array($ext, array('doc','docx'))){
						$linkControl .= "	background: url('/admin/assets/emsa/images/upload/word-icon.png'); ";
					}else if(in_array($ext, array('xls','xlsx'))){
						$linkControl .= "	background: url('/admin/assets/emsa/images/upload/excel-icon.png'); ";
					}else if(in_array($ext, array('pdf'))){
						$linkControl .= "	background: url('/admin/assets/emsa/images/upload/pdf-icon.png'); ";
					}else if(in_array($ext, array('xmind'))){
						$linkControl .= "	background: url('/admin/assets/emsa/images/upload/xmind-icon.png'); ";
					}else if(in_array($ext, array('mp4'))){
						$linkControl .= "	background: url('/admin/assets/emsa/images/upload/video-icon.png'); ";
					}else{
						$linkControl .= "	background: url('/admin/assets/emsa/images/upload/file-icon.png'); ";
					}
					$linkControl .= "	overflow: hidden; ";
					if(in_array($ext, $this->imageExtension)){
						$linkControl .= "	background-size: cover; ";
					}else{
						$linkControl .= "	background-size: contain; padding: 15px; background-origin: content-box;";
					}
					$linkControl .= "	background-repeat: no-repeat; ";
					$linkControl .= "	background-position: center center;";
					$linkControl .= "}";
					$linkControl .= "</style>";
					
					$linkControl .= "<div class='uploadmainBox Box-".$uid."'>";
					//$linkControl .= "<div class='overflowText' style='padding: 5px;'>";
					//$linkControl .= "<em class='fa fa-file uploadmainBox-Title'></em>&nbsp;";
					//$linkControl .= "<span class='uploadmainBox-Title' title='{$filename}'>{$filename}</span>";
					//$linkControl .= "<br><span class='uploadmainBox-Title'>{$fileSize}</span>";
					//$linkControl .= "</div>";
					
					if(in_array($ext, $this->imageExtension)){
						$linkControl .= "<a class='previewBox' href='" . $emsaGetPath . "/file/{$filePathSrc}' title='Preview : {$filename}' alt='Preview : {$filename}' data-lightbox='imageList-{$controlName}'>";
						$linkControl .= "<em class='fa fa-file-image-o' style='color: #FFF; margin-top: 8px;'></em>";
						$linkControl .= "</a>";
					}
					
					$linkControl .= "<a class='downloadBox' href='" . $emsaGetPath . "/file/{$filePathSrc}/type/download' title='Download : {$filename}' alt='Download : {$filename}'>";
					$linkControl .= "<em class='fa fa-download' style='color: #FFF; margin-top: 8px;'></em>";
					$linkControl .= "</a>";
					$linkControl .= "</div>";
				}
				$linkControl .= "</div>";
			}
		}

		if (empty($linkControl) && $readonly) {
			$linkControl = "-";
		}
		$divControl = "<div id='div$controlName'>{$linkControl}</div>";

		$maxSizeMb = number_format($maxSize / (1024 * 1024), 2 );
		$divHint = "<div class='hint'>ขนาดไฟล์ไม่เกิน {$maxSizeMb} MB</div>";
		if (!empty($fileExtsHint)) {
			$divHint .= "<div class='hint'>เฉพาะไฟล์นามสกุล: {$fileExtsHint}</div>";
		}

		$htmlStr  = "<input type=\"file\" name=\"{$controlName}[]\" id=\"{$controlName}\" multiple=\"multiple\" />\n\n";
		$htmlStr .= "<script type=\"text/javascript\">\n";
		$htmlStr .= "	$('#{$controlName}').filer({\n";
		$htmlStr .= "		showThumbs: true,\n";

		// Multi File
		if($multiStr == 'false'){			
			$htmlStr .= "		limit: 1,\n";		
		}
		
		// Max Filesize
		if(!empty($maxSize)){			
			$htmlStr .= "		maxSize: " . $maxSize . ",\n";			
		}
		
		// Specific extension
		if(!empty($options["exts"]) ){			
			if(count($fileExts)>0){
				$loop = count($fileExts);
				$index = 1;
				$data = '';
				foreach ($fileExts as $ext){
					$data =  $data . "'" . $ext . "'" . ($loop!=$index ? ',' : '');
					$index++;
				}
			}
			$htmlStr .= "extensions: [" . $data . "],\n";			
		}
		
		
		$htmlStr .= "		templates: {\n";
		$htmlStr .= "			box: '<div class=\"jFiler-items-list jFiler-items-grid\"></div>',\n";
		$htmlStr .= "			item: '<div class=\"jFiler-items\">\\\n";
		$htmlStr .= "							<a  class=\"jFiler-item-title\" style=\"color: #2c93c7;\" download><b title=\"{{fi-name}}\">{{fi-name | limitTo: 25}}</b></a>\\\n";
		$htmlStr .= "							<a class=\"icon-jfi-trash jFiler-item-trash-action\"></a>\\\n";
		$htmlStr .= "							<div class=\"jFiler-jProgressBar\" style=\"width: 200px;\"></div>\\\n";
		$htmlStr .= "					</div>',\n";
		$htmlStr .= "			itemAppend: '<div class=\"jFiler-items\">\\\n";
		$htmlStr .= "							<a  class=\"jFiler-item-title\" style=\"color: #2c93c7;\"  href=\"" . $emsaGetPath . "/file/{{fi-fid}}/type/download\"><b title=\"{{fi-name}}\">{{fi-name | limitTo: 25}}</b></a>\\\n";
		$htmlStr .= "							<a class=\"icon-jfi-trash jFiler-item-trash-action\"></a>\\\n";
		$htmlStr .= "							<input type=\"hidden\" name=\"hdf{$controlName}[]\" value=\"{{fi-fid}}\" />\\\n";
		$htmlStr .= "					</div>',\n";
		$htmlStr .= "					\n";
		$htmlStr .= "			progressBar: '<div class=\"bar\"></div>',\n";
		$htmlStr .= "			itemAppendToEnd: false,\n";
		$htmlStr .= "			removeConfirmation: true,\n";
		$htmlStr .= "			_selectors: {\n";
		$htmlStr .= "				list: '.jFiler-items-list',\n";
		$htmlStr .= "				item: '.jFiler-items',\n";
		$htmlStr .= "				progressBar: '.bar',\n";
		$htmlStr .= "				remove: '.jFiler-item-trash-action'\n";
		$htmlStr .= "			}\n";
		$htmlStr .= "		},\n";
		$htmlStr .= "		uploadFile: {\n";
		
		$htmlStr .= "			url: \"" . $emsaUploadPath . "\",\n";
		//$htmlStr .= "			url: \"" . $urlHelper->url(array('module' => 'default', 'controller' => 'file', 'action' => 'upload'), null, true) . "\",\n";
		//$htmlStr .= "			data: null,\n";
		//$htmlStr .= "			data: {\n";
		//$htmlStr .= "				folderName: '{$folderName}',\n";
		//$htmlStr .= "				inputName: '{$controlName}'\n";
		//$htmlStr .= "			},\n";
		$htmlStr .= "			type: 'POST',\n";
		$htmlStr .= "			enctype: 'multipart/form-data',\n";
		$htmlStr .= "			beforeSend: function(){},\n";
		$htmlStr .= "			success: function(data, el){\n";
		//$htmlStr .= "				var objData = $.parseJSON(data);\n";
		$htmlStr .= "				var objData = data;\n";
		$htmlStr .= "				console.log(data);\n";
		$htmlStr .= "				var name = objData['metas'][0]['old_name'];\n";
		$htmlStr .= "				var name_md5 = objData['metas'][0]['name'];\n";
		$htmlStr .= "				var parent = el.find(\".jFiler-jProgressBar\").parent();\n";
		$htmlStr .= "				el.find(\".jFiler-jProgressBar\").fadeOut(\"slow\", function(){\n";
		$htmlStr .= "					$(\"<span class=\\\"jFiler-item-others text-success\\\" style='display: none;'><input type='hidden' name='hdf{$controlName}[]' value='\" + name_md5 + \"' /><i class=\\\"icon-jfi-check-circle\\\"></i> Success</span>\").hide().appendTo(parent).fadeIn(\"slow\");    \n";
		$htmlStr .= "				});\n";
		$htmlStr .= "				el.find(\".jFiler-item-title\").attr('href', '" . $emsaGetPath . "/file/' + name_md5 + '/type/download');\n";
		$htmlStr .= "			},\n";
		$htmlStr .= "			error: function(el){\n";
		$htmlStr .= "				var parent = el.find(\".jFiler-jProgressBar\").parent();\n";
		$htmlStr .= "				el.find(\".jFiler-jProgressBar\").fadeOut(\"slow\", function(){\n";
		$htmlStr .= "					$(\"<span class=\\\"jFiler-item-others text-error\\\"><i class=\\\"icon-jfi-minus-circle\\\"></i> Error</span>\").hide().appendTo(parent).fadeIn(\"slow\");        \n";
		$htmlStr .= "				});\n";
		$htmlStr .= "			},\n";
		$htmlStr .= "			statusCode: null,\n";
		$htmlStr .= "			onProgress: null,\n";
		$htmlStr .= "			onComplete: null\n";
		$htmlStr .= "		},\n";
		$htmlStr .= "		onRemove: function(itemEl, file, id, listEl, boxEl, newInputEl, inputEl){\n";
		$htmlStr .= "			var folderName = '{$folderName}';\n";
		$htmlStr .= "			var filename = file.name;\n";
		$htmlStr .= "			var fileName_md = $(\"input[name='hdf{$controlName}[]']\").val();\n";
		$htmlStr .= "			//$.post(\"/file/remove\", {fid: fileName_md , folderName: folderName});\n";
		$htmlStr .= "		},\n";
		$htmlStr .= "		addMore: true,\n";
		$htmlStr .= "		files: [\n";
		
		foreach ($filePathArray as $filePath) {
			if (!empty($filePath)) {
				
			$htmlStr .= "					{\n";
			$htmlStr .= "						name: '{$filePath['file_name']}',\n";
			$htmlStr .= "						type: '{$filePath['type']}',\n";
			$htmlStr .= "						fid: '{$filePath['hash_name']}',\n";
			$htmlStr .= "					},		\n";
		
			}
		}
		
		$htmlStr .= "				]\n";
		$htmlStr .= "	});\n";
		$htmlStr .= "</script>";

		if ($readonly)
			return $divControl;

		//return $fileUploadControl . "\n" . $script . "\n" . $divControl . $divHint;
		return $htmlStr . "\n" . $divControl;
		
	}

	private function getFileSize($filePath) {
		$str = '';
		if (file_exists($filePath)) {
			$size =  filesize($filePath);
			if ($size < 1024) {
				$str = number_format($size, 2, '.', ',') . " bytes";
			} else {
				$size = $size / 1024;
				if ($size < 1024) {
					$str = number_format($size, 2, '.', ',') . " kB";
				} else {
					$size = $size / 1024;
					if ($size < 1024) {
						$str = number_format($size, 2, '.', ',') . " mB";
					} else {
						$size = $size / 1024;
						$str = number_format($size, 2, '.', ',') . " gB";
					}
				}
			}
		}
		return $str;
	}
}

?>
