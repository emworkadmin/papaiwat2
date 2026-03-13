<?php
/*
 *
*/

class Vrv_View_Helper_UploadVRControl extends Zend_View_Helper_Abstract
{
	private $imageExtension = array("jpg", "gif", "png", "jpeg", "bmp");
	public function UploadVRControl($controlName, $folderName, $filePathArray = array(), $options = array())
	{
		$urlHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('Url');
		
		// Options
		// multi, isimage, showimage, exts, maxsize, readonly, showlink
		$readonly = $options["readonly"] ? true : false;
		$showlink = $options["showlink"] ? true : false;
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
					// $linkControl .= "<div><a href='" . $urlHelper->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get'), null, true) . "/file/{$filePathSrc}' style='color: #2c93c7;'><b title='{$filename}'>- {$filename}</b></a></div>";
				}
				if ($showlink) {
					$linkControl .= "<div><a href='" . $urlHelper->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get'), null, true) . "/file/{$filePathSrc}' style='color: #2c93c7;'><b title='{$filename}'>- {$filename}</b></a></div>";
				}
				if ($showimage) {
					
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
		$htmlStr .= "							<a  class=\"jFiler-item-title\" style=\"color: #2c93c7;\"  href=\"" . $urlHelper->url(array('module' => 'default', 'controller' => 'file', 'action' => 'get'), null, true) . "/file/{{fi-fid}}\" download><b title=\"{{fi-name}}\">{{fi-name | limitTo: 25}}</b></a>\\\n";
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
		$htmlStr .= "			url: \"" . $urlHelper->url(array('module' => 'default', 'controller' => 'file', 'action' => 'upload', 'uploadtype' => 'vr'), null, true) . "\",\n";
		$htmlStr .= "			type: 'POST',\n";
		$htmlStr .= "			enctype: 'multipart/form-data',\n";
		$htmlStr .= "			beforeSend: function(){},\n";
		$htmlStr .= "			success: function(data, el){\n";
		$htmlStr .= "				if(data.files){\n";
		$htmlStr .= "					var objData = data;\n";
		$htmlStr .= "					var name = objData['metas'][0]['old_name'];\n";
		$htmlStr .= "					var name_md5 = objData['metas'][0]['name'];\n";
		$htmlStr .= "					var fullpath = objData['metas'][0]['fullpath'];\n";
		$htmlStr .= "					var parent = el.find(\".jFiler-jProgressBar\").parent();\n";
		$htmlStr .= "					el.find(\".jFiler-jProgressBar\").fadeOut(\"slow\", function(){\n";
		$htmlStr .= "						$(\"<span class=\\\"jFiler-item-others text-success\\\" style='display: none;'><input type='hidden' name='hdf{$controlName}[]' value='\" + name_md5 + \"' /><input type='hidden' name='hdf{$controlName}Path[]' value='\" + fullpath + \"' /><i class=\\\"icon-jfi-check-circle\\\"></i> Success</span>\").hide().appendTo(parent).fadeIn(\"slow\");    \n";
		$htmlStr .= "					});\n";
		$htmlStr .= "					el.find(\".jFiler-item-title\").attr('href', fullpath);\n";
		$htmlStr .= "				}else{\n";
		$htmlStr .= "					var parent = el.find(\".jFiler-jProgressBar\").parent();\n";
		$htmlStr .= "					el.find(\".jFiler-jProgressBar\").fadeOut(\"slow\", function(){\n";
		$htmlStr .= "						$(\"<span class=\\\"jFiler-item-others text-error\\\"><i class=\\\"icon-jfi-minus-circle\\\"></i> Error</span>\").hide().appendTo(parent).fadeIn(\"slow\");        \n";
		$htmlStr .= "					});\n";
		$htmlStr .= "					if(data.message){\n";
		$htmlStr .= "						alert(data.message);\n";
		$htmlStr .= "					}\n";
		$htmlStr .= "				}\n";
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
