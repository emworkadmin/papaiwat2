<?php

class FileController extends Zend_Controller_Action
{
	protected $imageTypeOption = array('image/png', 'image/gif', 'image/jpg', 'image/jpeg');
	protected $thumbnailSize = 500;
	
	public function init()
	{ 
		
	}
	
	public function uploadAction()
	{
		// Init Return value
		$result = array();
		$result['files'] = null;
		$result['metas'] = array();
// 		$result['result'] = false;
// 		$result['id'] = null;
// 		$result['fid'] = null;
// 		$result['filesize'] = null;
// 		$result['name'] = null;
// 		$result['fname'] = null;
// 		$result['message'] = null;
		try {
			$params = $this->getRequest()->getParams();			

			$uploadRootPath = APPLICATION_ROOT . DS . 'files';
			$storePath = '/' . date('Y') . '/' . date('m') . '/' . date('d');
			
			$pathToUploads = $uploadRootPath . $storePath;
			$pathToUploads = str_replace('/', DS, $pathToUploads);
			$pathToUploads = str_replace("\\", DS, $pathToUploads);
			
			if ($this->createDirectory($pathToUploads)) {
				$adapter = new Zend_File_Transfer_Adapter_Http();
				
				$adapter->addValidator('Count', false, array('min' => 1, 'max' => 1))
				->addValidator('Size', false, array('max' => '100mB'))
				->setDestination($pathToUploads);
				
				// ตรวจสอบว่าถูกต้องหรือไม่
				if ($adapter->isValid()) {
					
					foreach ($adapter->getFileInfo() as $key => $info) {
						$baseName = $info['name'];
						$fileSize = $info['size'];
						$fileType = Emwork_Utility_File::returnMIMEType($baseName);
						$fileName = pathinfo($baseName, PATHINFO_FILENAME);
						$ext = pathinfo($baseName,  PATHINFO_EXTENSION);
						
						$hashFileName = date('Ymd') . md5($fileName) . date('His');
						$hashBaseName = $hashFileName . '.' . $ext;
						
						$adapter->addFilter('Rename',array('target' => $pathToUploads . DS . $hashBaseName));
						if($adapter->receive()){
							// Save to model
							$modelFile = new System_Model_Fileinfo();
							$data = $modelFile->save($baseName, $hashBaseName, $storePath, $fileSize, $ext, $fileType);
							
							if ($data->result) {
// 								$result['result'] = true;
// 								$result['id'] = $data->id;
// 								$result['fid'] = md5($data->id);
// 								$result['filesize'] = $fileSize;
// 								$result['name'] = $baseName;
// 								$result['fname'] = $hashBaseName;
								$result['files'] = array($hashBaseName);
								$result['metas'][] = array(
										'date' => date('Y-m-d H:i:s'),
										'extension' => $ext,
										'file' => $hashFileName,
										'name' => $hashBaseName,
										'old_name' => $fileName,
										'size' => $fileSize,
										'size2' => $fileSize,
										'replaced' => false,
										//'size2' => 'xxxx',
										'type' => explode('/', $fileType),
										'fid' => $hashBaseName
								);
							} else {
								$result['message'] = "Can't Save Data!";
							}
						} else {
							$result['message'] = "Can't Save file!";
						}
					}
				} else {
					$result['message'] = "File isn't valid";
				}
			} else {
				$result['message'] = "Create Folder Fail";
			}
		} catch (Exception $e) {
			$result['message'] = $e->getMessage();
		}
		$this->_helper->json($result);
	}
	
	public function getAction()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		ob_clean();
		
		// Get Params and Set Value
		$hashName = $this->getRequest()->getParam("file");
		$type = $this->getRequest()->getParam("type", '');
		$size = $this->getRequest()->getParam("size", $this->thumbnailSize);
		
		try {
			
			// Check from $hashName First
			$_hashYear = substr($hashName, 0, 4);
			$_hashMonth = substr($hashName, 4, 2);
			$_hashDay = substr($hashName, 6, 2);
			$_hashPath = APPLICATION_ROOT . DS . 'files' . (DS . $_hashYear) . (DS . $_hashMonth) . (DS . $_hashDay) . DS . $hashName;
			$_hashFullPath = $_hashPath . ($type == 'thumbnail' ? "." . ((empty($size) ? $this->thumbnailSize : $size) . '.thumbnail') : '');
			if (file_exists($_hashFullPath)) {
				
				if ($type == 'download') {
					header("Content-Type: application/force-download");
					
					$fileModel = new System_Model_Fileinfo();
					$fileData = $fileModel->itemByHashName($hashName);
					
					header('Content-Disposition: filename="' . $fileData->Name . '"');
				} else {
					$_hashContentType = Emwork_Utility_File::returnMIMEType($_hashPath);
					if (!empty($_hashContentType)) {
						header('Content-Type: ' . $_hashContentType);
					}
					header('Content-Disposition: inline; filename="' . $hashName . '"');
				}
				//header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Cache-Control: max-age=900');
				//header('Pragma: public');
				
				@readfile($_hashFullPath);
				return;
			}
			
			$fileModel = new System_Model_Fileinfo();
			$fileData = $fileModel->itemByHashName($hashName);
			
			if (!empty($fileData->ID)) {

				$filePath = APPLICATION_ROOT . DS . 'files' . $fileData->Path . DS . $fileData->HashName;
				if (file_exists($filePath)) {
					if (!empty($fileData->FileType)) {
						header('Content-Type: ' . $fileData->FileType);
					}
					if ($type == 'download') {
						header("Content-Type: application/force-download");

						if (!empty($fileData->FileSize)) {
							header('Content-Length: ' . $fileData->FileSize);
						}
					} elseif ($type == 'thumbnail') {
						if (!empty($fileData->FileType) && in_array($fileData->FileType, $this->imageTypeOption)) {
							$fileType = strtolower($fileData->FileType);
							if (file_exists($filePath . '.' . $size . '.thumbnail')) {
								$filePath = $filePath . '.' . $size . '.thumbnail';
							} else {
								list($width, $height) = getimagesize($filePath);
								if ($width <= $size && $height <= $size) {
									if (copy($filePath, $filePath . '.' . $size . '.thumbnail')) {
										$filePath = $filePath . '.' . $size . '.thumbnail';
									}
								} else {
									$radio = $width / $height;
									$newwidth = $size;
									$newheight = $size;
									if ($radio > 1) {
										$newheight = $newwidth * ($height / $width);
									} else {
										$newwidth = $newheight * ($width / $height);
									}
									$thumb = imagecreatetruecolor($newwidth, $newheight);
									imagealphablending($thumb, false);
									imagesavealpha($thumb, true);
									$source = imagecreatefromstring(file_get_contents($filePath));
									if (imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height) ) {
										$filePath = $filePath . '.' . $size . '.thumbnail';
										if ($fileType == 'image/png') {
											imagepng($thumb, $filePath, 7);
										} elseif ($fileType == 'image/gif') {
											imagegif($thumb, $filePath);
										} elseif ($fileType == 'image/jpg' || $fileType == 'image/jpeg') {
											imagejpeg($thumb, $filePath);
										}
									}
									imagedestroy($thumb);
									imagedestroy($source);
								}
							}
						}
					}
					header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					header('Pragma: public');
					if($type == 'inline'){
						header('Content-Disposition: inline; filename="' . $fileData->Name . '"');
					}else{
						header('Content-Disposition: attachment; filename="' . $fileData->Name . '"');
					}
					@readfile($filePath);
				} else {
					if (!empty($fileData->FileType) && in_array($fileData->FileType, $this->imageTypeOption)) {
						header('Content-Type: image/png');
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
						header('Pragma: public');
						header('Content-Disposition: attachment; filename="default.png"');
						
						$filePath = APPLICATION_ROOT . DS . 'public' . DS . 'images' . DS . 'logos' . DS . 'logo.png';
						@readfile($filePath);
					} else {
						// TODO: 
					}
				}
			} else {
				// TODO:
			}
		} catch (Exception $e) {
			// TODO:
		}
		return;
	}
	

	private function createDirectory($pathToUploads)
	{
		$result = false;
		
		if (file_exists($pathToUploads)) {
			$result = true;
		} else {
			$result = mkdir($pathToUploads, 0777, true);
		}
		
		return $result;
	}

}

