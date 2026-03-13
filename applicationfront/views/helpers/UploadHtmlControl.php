<?php
/*
 *
*/

class Zend_View_Helper_UploadHtmlControl extends Zend_View_Helper_Abstract
{
	private $imageExtension = array("jpg", "gif", "png", "jpeg", "bmp");
	public function UploadHtmlControl($controlName, $folderName, $filePathArray = array(), $options = array())
	{
		// Options
		// multi, isimage, showimage, exts, maxsize, readonly, showlink
		$readonly = $options["readonly"] ? true : false;
		$showlink = $options["showlink"] ? true : ($readonly ? false : true);
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
		foreach ($filePathArray as $filePath) {
			if (!empty($filePath)) {
				
				$filename = $filePath['file_name'];
				$filePathSrc = $filePath['file_name_encode'];
				$fileRealPath = UPLOAD_PATH . DS . $folderName . DS . $filePathSrc;
				$fileSize = $this->getFileSize($fileRealPath);
				$fileSize = empty($fileSize) ? "" : " (" . $fileSize . ")";
				$ext = strtolower(substr(strrchr($filename, '.'), 1));

				$linkControl .= "<div id='div{$controlName}_{$filePathID}'>";
				if ($readonly) {
					$linkControl .= "<div><a href='/file/get/file/{$filePath['hash_name']}' style='color: #2c93c7;'><b title='{$filename}'>- {$filename}</b></a></div>";
					//$linkControl .= "<div><a href='/filemanagement/index/downloadfile?fileName_md={$filePathSrc}&amp;folderName={$folderName}' style='color: #2c93c7;'><b title='{$filename}'>{$filename}</b></a></div>";
				}
				if ($showlink) {
					//$linkControl .= "<div><a href='/message/index/downloadfile?fileName_md={$filePathSrc}&amp;folderName={$folderName}' style='color: #2c93c7;'><b title='{$filename}'>{$filename}</b></a></div>";
				}
				// Show Image
				if (in_array($ext, $this->imageExtension) && $showimage) {
					//$linkControl .= "<br /><img src='{$filePathSrc}' title='{$filename}' alt='{$filename}' style='max-width:150px;' />";
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
		$htmlStr .= "			item: '<div class=\"jFiler-items\"><a  class=\"jFiler-item-title\" style=\"color: #2c93c7;\"><b title=\"{{fi-name}}\">{{fi-name | limitTo: 25}}&nbsp;</b></a><a class=\"icon-jfi-trash jFiler-item-trash-action\"></a><div class=\"jFiler-jProgressBar\" style=\"width: 200px;\"></div></div>',\n";
		$htmlStr .= "			itemAppend: '<div class=\"jFiler-items\"><a  class=\"jFiler-item-title\" style=\"color: #2c93c7;\"  href=\"/file/get/file/{{fi-fid}}\"><b title=\"{{fi-name}}\">{{fi-name | limitTo: 25}}&nbsp;</b></a><a class=\"icon-jfi-trash jFiler-item-trash-action\"></a><input type=\"hidden\" name=\"hdf{$controlName}[]\" value=\"{{fi-fid}}\" /></div>',\n";
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
		$htmlStr .= "			url: \"" . $this->view->url(array('module' => "default", "controller" => "file", "action" => "upload"), 'default', true) . "\",\n";
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
		//$htmlStr .= "				console.log(data);\n";
		$htmlStr .= "				var name = objData['metas'][0]['old_name'];\n";
		$htmlStr .= "				var name_md5 = objData['metas'][0]['name'];\n";
		$htmlStr .= "				var parent = el.find(\".jFiler-jProgressBar\").parent();\n";
		$htmlStr .= "				el.find(\".jFiler-jProgressBar\").fadeOut(\"slow\", function(){\n";
		$htmlStr .= "					$(\"<span class=\\\"jFiler-item-others text-success\\\" style='display: none;'><input type='hidden' name='hdf{$controlName}[]' value='\" + name_md5 + \"' /><i class=\\\"icon-jfi-check-circle\\\"></i> Success</span>\").hide().appendTo(parent).fadeIn(\"slow\");    \n";
		$htmlStr .= "				});\n";
		$htmlStr .= "				el.find(\".jFiler-item-title\").attr('href', '/file/get/file/' + name_md5);\n";
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
	
	/* Upload ไฟล์ข้อมูล */
	public function uploadfileAction(){
	
		// Get Params and Set Value
		$folderName = $this->getRequest()->getParam("folderName");
		$inputName = $this->getRequest()->getParam("inputName");
	
		$uploader = new Issue_Model_Uploader();
		$data = $uploader->upload($_FILES[$inputName],  array(
				'limit' => 10, //Maximum Limit of files. {null, Number}
				'maxSize' => 10, //Maximum Size of files {null, Number(in MB's)}
				'extensions' => null, //Whitelist for file extension. {null, Array(ex: array('jpg', 'png'))}
				'required' => false, //Minimum one file is required for upload {Boolean}
				'uploadDir' => APPLICATION_ROOT . '/public/uploads/' . $folderName . '/', //Upload directory {String}
				'title' => array('name'), //New file name {null, String, Array} *please read documentation in README.md
				'removeFiles' => true, //Enable file exclusion {Boolean(extra for jQuery.filer), String($_POST field name containing json data with file names)}
				'perms' => null, //Uploaded file permisions {null, Number}
				'onCheck' => null, //A callback function name to be called by checking a file for errors (must return an array) | ($file) | Callback
				'onError' => null, //A callback function name to be called if an error occured (must return an array) | ($errors, $file) | Callback
				'onSuccess' => null, //A callback function name to be called if all files were successfully uploaded | ($files, $metas) | Callback
				'onUpload' => null, //A callback function name to be called if all files were successfully uploaded (must return an array) | ($file) | Callback
				'onComplete' => null, //A callback function name to be called when upload is complete | ($file) | Callback
				'onRemove' => 'onFilesRemoveCallback' //A callback function name to be called by removing files (must return an array) | ($removed_files) | Callback
		));
			
		if($data['isComplete']){
			$files = $data['data'];
			echo Zend_Json_Encoder::encode($files);
		}
	
		if($data['hasErrors']){
			$errors = $data['errors'];
			//print_r($errors);
		}
			
		function onFilesRemoveCallback($removed_files){
			foreach($removed_files as $key=>$value){
				$file = APPLICATION_ROOT . '/public/uploads/' . $value;
				echo 'file : ' . $file;exit();
				if(file_exists($file)){
					unlink($file);
				}
			}
			return $removed_files;
		}
	
		// Set View
		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
	
	}
	
	/* Download ไฟล์ข้อมูล */
	public function downloadfileAction(){
	
		// Get Params and Set Value
		$FolderName = $this->getRequest()->getParam("folderName");
		$FileName_Md = $this->getRequest()->getParam("fileName_md");
	
		$file = APPLICATION_ROOT . '/public/uploads/' . $FolderName . '/' . $FileName_Md;
	
		$download = 1;
		if (isset($FileName_Md)) {
			header("Content-Type: application/force-download");
			header("Content-Disposition: attachment; filename='$FileName_Md'");
			@readfile("$file");
		} else {
			echo "<h2>Download ข้อมูลไม่สำเร็จ</h2>";
		}
	
		// Set View
		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
	}
	
	/* ลบข้อมูลไฟล์ที่ทำการ Upload */
	public function deletefileAction(){
	
		$FolderName = $this->getRequest()->getParam("folderName");
		$FileName_Md = $this->getRequest()->getParam("fileName_md");
		//echo 'FolderName : ' . $FolderName . '<br>FileName_Md : ' . $FileName_Md; exit();
	
		$file = APPLICATION_ROOT . '/public/uploads/' . $FolderName . '/' . $FileName_Md;
		if(file_exists($file)){
			unlink($file);
		}
	
		// Set View
		Zend_Layout::resetMvcInstance();
		$this->_helper->viewRenderer->setNoRender();
	}
	
}

?>
