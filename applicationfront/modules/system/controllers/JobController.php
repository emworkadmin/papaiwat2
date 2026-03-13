<?php
class System_JobController extends Zend_Controller_Action {
		
	function init() {
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$this->getResponse()->clearAllHeaders();
		$this->getResponse()->setHeader('Content-Type', 'application/json; charset=utf-8');
	}
	
	function indexAction() {
		
	}
	
	/**
	 * Action: Get Complain
	 */
	function getcomplainAction() {
		
		set_time_limit ( 600 );		

		$request = $this->getRequest();
		$_accessKey = $request->getParam('key', '');
		$_accessSecret = $request->getParam('secret', '');
		
		// Access Config
		$accessConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/job.ini', 'access');
		$accessKey = $accessConfig->key;
		$accessSecret = $accessConfig->secret;
			
		if ($_accessKey != $accessKey || $_accessSecret != $accessSecret) {
			exit;
		}
		
		$response = array();
		
		// Model
		$model = new Complain_Model_Complain();
		$modelCasecategory = new Lookup_Model_Casecategory();
		$modelCasestatus = new Lookup_Model_Casestatus();
		
		// Webservice Config
		$config = new Zend_Config_Ini(APPLICATION_PATH . DS . 'configs' . DS . 'external.ini', 'one2one');
		$webservice = new One2one_Webservice($config->get('wsdlUrl'));
		$webservice->setLocation($config->get('locationUrl'));
		
		$modifiedDate = (new DateTime())->format('Y/m/d');
		//$modifiedDate = "2016/10/19";
		
		// Webservice 
		require_once 'One2one/Request/GetCasesList.php';
		$getCasesList = new GetCasesList();
		$getCasesList->username = $config->get('username');
		$getCasesList->password = $config->get('password');
		$getCasesList->idcard = '';
		$getCasesList->phonenumber = '';
		$getCasesList->createdate = '';
		$getCasesList->modifieddate = $modifiedDate;
		
		// Webservice GetCasesList
		$resultWebserviceCasesList = $webservice->callGetCasesList($getCasesList);
		$CasesList = $resultWebserviceCasesList->GetCasesListResult->GetCasesList->CaseListInfo;
		
		if (is_object($CasesList)) {
			$CasesListTemp = $CasesList;
			$CasesList = array();
			$CasesList[] = $CasesListTemp;
		}
		
		// Record 
		$response['total'] = count($CasesList);
		
		if(count($CasesList)>0){
			require_once 'One2one/Request/GetCaseDetail.php';
			$getCaseDetail = new GetCaseDetail();
			$getCaseDetail->username = $config->get('username');
			$getCaseDetail->password = $config->get('password');
		
			require_once 'One2one/Request/GetCasesLogList.php';
			$getCasesLogList = new GetCasesLogList();
			$getCasesLogList->username = $config->get('username');
			$getCasesLogList->password = $config->get('password');
		
			foreach ($CasesList as $d){
					
				// Check Update
				$caseCode = $d->casecode;
				$dateTmp = new DateTime($d->modifydate);
				$modified = $dateTmp->format('Y-m-d H:i:s');
				$response[$caseCode] = array();
					
				$chkComplain = $model->checkComplain($caseCode, $modified);
				$response[$caseCode]['check'] = $chkComplain;
				
				if ($chkComplain) {
					continue;
				}
					
				$getCaseDetail->casecode = $d->casecode;
				$getCaseDetail->issuecode = empty($d->issuecode) ? "" : $d->issuecode;
					
				// Webservice GetCaseDetail
				unset($CaseDetail);
				$resultWebserviceCaseDetail = $webservice->callGetCaseDetail($getCaseDetail);
				$CaseDetail = $resultWebserviceCaseDetail->GetCaseDetailResult->GetCaseDetailList->CaseDetailInfo;
					
				// Prepare Date Format
				$dateTmp = new DateTime($CaseDetail->modifydate);
				$modified = $dateTmp->format('Y-m-d H:i:s');
				$dateTmp = new DateTime($CaseDetail->createon);
				$created = $dateTmp->format('Y-m-d H:i:s');
				$CaseDetail->user_id = null;
				$CaseDetail->modified = $modified;
				$CaseDetail->created = $created;
		
				if(empty($chkComplain)){
		
					// Save or Update Complain
					$result = $model->updateByWebservice($CaseDetail);
					$response[$caseCode]['isUpdate'] = $result->isUpdate;
		
					if (!$result->isUpdate) {
						$attachment = $CaseDetail->attachment;
						if (!empty($attachment)) {
							$contentFile = base64_decode($attachment);
							$finfo = new finfo(FILEINFO_MIME_TYPE);
							$fileType = $finfo->buffer($contentFile);
							$ext = "";
							switch ($fileType) {
								case "application/pdf" : $ext = "pdf"; break;
								case "image/jpeg" : $ext = "jpg"; break;
								case "image/gif" : $ext = "gif"; break;
								case "image/png" : $ext = "png"; break;
							}
		
							// Save File
							$uploadRootPath = APPLICATION_ROOT . DS . 'files';
							$storePath = '/' . date('Y') . '/' . date('m') . '/' . date('d');
								
							$pathToUploads = $uploadRootPath . $storePath;
							$pathToUploads = str_replace('/', DS, $pathToUploads);
							$pathToUploads = str_replace("\\", DS, $pathToUploads);
		
							$modelFile = new System_Model_Picture();
		
							if ($this->createDirectory($pathToUploads)) {
									
								$baseName = uniqid() . (empty($ext) ? "" : "." . $ext);
									
								$fileName = pathinfo($baseName, PATHINFO_FILENAME);
									
								$hashFileName = date('Ymd') . md5($fileName) . date('His');
								$hashBaseName = $hashFileName . (empty($ext) ? "" : "." . $ext);
									
								$fileSize = file_put_contents($pathToUploads . DS . $hashBaseName, $contentFile);
									
								if ($fileSize !== false) {
									$fileResult = $modelFile->save($baseName, $hashBaseName, $storePath, $fileSize, $ext, $fileType);
									if ($fileResult->result) {
											
										// Insert file to Complain
										$model->saveComplainFile($result->id, $fileResult->id, null);
									}
								}
							}
		
						}
					}
		
					// Webservice GetCasesLogList
					$getCasesLogList->caseid = $CaseDetail->caseid;
					$resultWebserviceCasesLogList = $webservice->callGetCasesLogList($getCasesLogList);
					$CasesLogList = $resultWebserviceCasesLogList->GetCasesLogListResult->GetCasesLogList->CaseLogInfo;
					$chkArray = count($CasesLogList);
					$response[$caseCode]['logTotal'] = $chkArray;
					
					// Prepare Data CasesLogList
					if($chkArray == 1){
						$CasesLogListTmp = array();
						$item = new stdClass();
						$item->clsid = $CasesLogList->clsid;
						$item->casenote = $CasesLogList->casenote;
						$item->firstname = $CasesLogList->firstname;
						$item->lastname = $CasesLogList->lastname;
						$item->createon = $CasesLogList->createon;
							
						$CasesLogListTmp[] = $item;
						$CasesLogList = $CasesLogListTmp;
					}
					foreach ($CasesLogList as $key => $value){
						$CasesLogList[$key]->complain_id = $result->id;
						$CasesLogList[$key]->user_id = $_user_id;
					}
		
					// Save or Update Complain Log
					$resultLog = $model->updateLog($CasesLogList);
				}
			}
		}
		
		
		echo Zend_Json_Encoder::encode($response);
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