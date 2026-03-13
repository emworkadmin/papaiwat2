<?php

class Poll_Model_Item {
	
	/**
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $db = null;
	protected $UserID = null;
	protected $DeptID = null;
	protected $auth = null;
	
	function __construct() {
		try {
			// setup database
			$this->db = Zend_Db_Table::getDefaultAdapter();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance();
			$this->auth = $authobj->getIdentity();
			$this->UserID = isset($this->auth->UserID) ? $this->auth->UserID : '';
		} catch (Exception $e) {
			
		}
	}
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'poll_item_info'), '')
			->joinLeft(array('d' => 'poll_item_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('c' => 'poll_category_info') , "tbl.PollCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'poll_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
			->columns(array(
					"ID" => "tbl.ID",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"Title" => "d.Title",
					"CategoryTitle" => "cd.Title",
			))
			->where("tbl.IsDeleted ='N'")
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}
			if (!empty($advancesearch['isCat'])) {
				// $select = $select->distinct()
				// ->joinInner(array('ca' => "poll_category_item"), "tbl.ID = ca.PollItemID", '')
				// ->where("ca.PollCategoryID = ? ", $advancesearch['isCat'])
				// ->where("ca.IsDeleted = 'N' ")
				// ;
				$select = $select->where("tbl.PollCategoryID = ?", $advancesearch['isCat']);
			}
			
			$records = $this->getListCount($select);
			
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			//->order("$rowSort $sord")
			->limitPage($page, $rows)
			;
			if (in_array($rowSort, array('Title','CategoryTitle'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return $data;
	}
	function getListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}
	
	function getInfo($id) {
		$data = new stdClass();
		try {
			$select= $this->db->select()->distinct()
			->from(array('tbl' => 'poll_item_info'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.IntroImageID = fi.ID ", "")
			->columns(array(
					"ID" => "tbl.ID",
					"PollCategoryID" => "tbl.PollCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"OneUserPerVote" => "tbl.OneUserPerVote",
					"OneIPPerVote" => "tbl.OneIPPerVote",
					"VoteDelay" => "tbl.VoteDelay",
					"ShowIntroText" => "tbl.ShowIntroText",
					"ResultDisplay" => "tbl.ResultDisplay",
					"ResultPieSetting" => "tbl.ResultPieSetting",
					"ResultBarSetting" => "tbl.ResultBarSetting",
					"StartDate" => "tbl.StartDate",
					"EndDate" => "tbl.EndDate",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",
					
					"IntroImageFileName" => "fi.Name",
					"IntroImageHashName" => "fi.HashName",
					"IntroImageFileType" => "fi.FileType",
					"IntroImageFileSize" => "fi.FileSize",
					"IntroImageFileExtension" => "fi.Extension",
			))
			->where("tbl.ID = ? " , $id)
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->StartDate = empty($data->StartDate) ? null : new DateTime($data->StartDate);
				$data->EndDate = empty($data->EndDate) ? null : new DateTime($data->EndDate);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'poll_item_info'), '')
			->joinLeft(array('d' => 'poll_item_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"PollCategoryID" => "tbl.PollCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"OneUserPerVote" => "tbl.OneUserPerVote",
					"OneIPPerVote" => "tbl.OneIPPerVote",
					"VoteDelay" => "tbl.VoteDelay",
					"ShowIntroText" => "tbl.ShowIntroText",
					"ResultDisplay" => "tbl.ResultDisplay",
					"ResultPieSetting" => "tbl.ResultPieSetting",
					"ResultBarSetting" => "tbl.ResultBarSetting",
					"StartDate" => "tbl.StartDate",
					"EndDate" => "tbl.EndDate",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",

					"Lang" => "d.Lang",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText"
			))
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->StartDate = empty($data->StartDate) ? null : new DateTime($data->StartDate);
				$data->EndDate = empty($data->EndDate) ? null : new DateTime($data->EndDate);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getQuestion($PollItemID) {
		$data = array();
		try {
			$select= $this->db->select()
			->from(array('q' => 'poll_question_info'), '')
			->joinLeft(array("qd" => "poll_question_detail"), "q.ID = qd.ID ", "")
			->columns(array(
					"ID" => "q.ID",
					"Lang" => "qd.Lang",
					"Question" => "qd.Question",
					"Description" => "qd.Description"
			))
			->where("q.PollItemID = ? " , $PollItemID)
			->order("q.Ordering")
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getQuestionByLanguage($PollItemID, $lang) {
		$data = array();
		try {
			$select= $this->db->select()
			->from(array('q' => 'poll_question_info'), '')
			->joinLeft(array("qd" => "poll_question_detail"), "q.ID = qd.ID  and qd.Lang = '{$lang}'", "")
			->columns(array(
					"ID" => "q.ID",
					"Lang" => "qd.Lang",
					"Question" => "qd.Question",
					"Description" => "qd.Description"
			))
			->where("q.PollItemID = ? " , $PollItemID)
			->order("q.Ordering")
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getQuestionOption($PollQuestionID) {
		$data = array();
		try {
			$select= $this->db->select()
			->from(array('o' => 'poll_option_info'), '')
			->joinLeft(array("od" => "poll_option_detail"), "o.ID = od.ID ", "")
			->columns(array(
					"ID" => "o.ID",
					"Lang" => "od.Lang",
					"Name" => "od.Name"
			))
			->where("o.PollQuestionID = ? " , $PollQuestionID)
			->order("o.Ordering")
			;
			$stmt = $this->db->query($select);
			$data  = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function save($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = '';
			$modified = date("Y-m-d H:i:s");
			if($defaultLanguageCode == 'th'){
				$param[$defaultLanguageCode]['StartDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['StartDate']);
				$param[$defaultLanguageCode]['StartDate'] .= empty($param[$defaultLanguageCode]['StartDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['StartDateTime']);
				$param[$defaultLanguageCode]['EndDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['EndDate']);
				$param[$defaultLanguageCode]['EndDate'] = empty($param[$defaultLanguageCode]['EndDate']) ? null : $param[$defaultLanguageCode]['EndDate'] . " " . (empty($param[$defaultLanguageCode]['EndDateTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['EndDateTime']));
			}else {
				$param[$defaultLanguageCode]['StartDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['StartDate']);
				$param[$defaultLanguageCode]['StartDate'] .= empty($param[$defaultLanguageCode]['StartDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['StartDateTime']);
				$param[$defaultLanguageCode]['EndDate'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['EndDate']);
				$param[$defaultLanguageCode]['EndDate'] = empty($param[$defaultLanguageCode]['EndDate']) ? null : $param[$defaultLanguageCode]['EndDate'] . " " . (empty($param[$defaultLanguageCode]['EndDateTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['EndDateTime']));
			}
			$form = array();
			$form['PollCategoryID'] = $param[$defaultLanguageCode]['PollCategoryID'];
			$form['IntroImageID'] = empty($params['IntroImageID']) ? null : $params['IntroImageID'];
			$form['OneUserPerVote'] = $param[$defaultLanguageCode]['OneUserPerVote'];
			$form['OneIPPerVote'] = $param[$defaultLanguageCode]['OneIPPerVote'];
			$form['VoteDelay'] = empty($param[$defaultLanguageCode]['VoteDelay']) ? null : $param[$defaultLanguageCode]['VoteDelay'];
			$form['ShowIntroText'] = $param[$defaultLanguageCode]['ShowIntroText'];
			$form['ResultDisplay'] = $param[$defaultLanguageCode]['ResultDisplay'];
			// $form['ResultPieSetting'] = $param[$defaultLanguageCode]['ResultPieSetting'];
			// $form['ResultBarSetting'] = $param[$defaultLanguageCode]['ResultBarSetting'];
			$form['StartDate'] = $param[$defaultLanguageCode]['StartDate'];
			$form['EndDate'] = $param[$defaultLanguageCode]['EndDate'];
			$form['Created'] = $modified;
			$form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$form['IsDeleted'] = 'N';
			$result = $this->db->insert("poll_item_info", $form);
			$id = $this->db->lastInsertId();
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$formDetail['ID'] = $id;
					$formDetail['Lang'] = $lang;
					$formDetail['Title'] = $value['Title'];
					$formDetail['IntroText'] = $value['IntroText'];
					$formDetail['Created'] = $modified;
					$formDetail['IsEnable'] = 'Y';
					$formDetail['IsDeleted'] = 'N';
					$result = $this->db->insert("poll_item_detail", $formDetail);
				}
			}
			
			if($result){
				$data->id = $id;
				$data->result = true;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function update($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = $params['hdfID'];
			$modified = date("Y-m-d H:i:s");
			if($defaultLanguageCode == 'th'){
				$param[$defaultLanguageCode]['StartDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['StartDate']);
				$param[$defaultLanguageCode]['StartDate'] .= empty($param[$defaultLanguageCode]['StartDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['StartDateTime']);
				$param[$defaultLanguageCode]['EndDate'] =  Emwork_Utility_DateTime::dateThaiToMySQL($param[$defaultLanguageCode]['EndDate']);
				$param[$defaultLanguageCode]['EndDate'] = empty($param[$defaultLanguageCode]['EndDate']) ? null : $param[$defaultLanguageCode]['EndDate'] . " " . (empty($param[$defaultLanguageCode]['EndDateTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['EndDateTime']));
			} else {
				$param[$defaultLanguageCode]['StartDate'] = Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['StartDate']);
				$param[$defaultLanguageCode]['StartDate'] .= empty($param[$defaultLanguageCode]['StartDateTime']) ? " 0:00" : " " . str_replace(" : ", ":", $param[$defaultLanguageCode]['StartDateTime']);
				$param[$defaultLanguageCode]['EndDate'] =  Emwork_Utility_DateTime::dateEngToMySQL($param[$defaultLanguageCode]['EndDate']);
				$param[$defaultLanguageCode]['EndDate'] = empty($param[$defaultLanguageCode]['EndDate']) ? null : $param[$defaultLanguageCode]['EndDate'] . " " . (empty($param[$defaultLanguageCode]['EndDateTime']) ? "0:00" : str_replace(" : ", ":", $param[$defaultLanguageCode]['EndDateTime']));
			}
			$form = array();
			$where = array();
			$form['PollCategoryID'] = $param[$defaultLanguageCode]['PollCategoryID'];
			$form['IntroImageID'] = empty($params['IntroImageID']) ? null : $params['IntroImageID'];
			$form['OneUserPerVote'] = $param[$defaultLanguageCode]['OneUserPerVote'];
			$form['OneIPPerVote'] = $param[$defaultLanguageCode]['OneIPPerVote'];
			$form['VoteDelay'] = empty($param[$defaultLanguageCode]['VoteDelay']) ? null : $param[$defaultLanguageCode]['VoteDelay'];
			$form['ShowIntroText'] = $param[$defaultLanguageCode]['ShowIntroText'];
			$form['ResultDisplay'] = $param[$defaultLanguageCode]['ResultDisplay'];
			// $form['ResultPieSetting'] = $param[$defaultLanguageCode]['ResultPieSetting'];
			// $form['ResultBarSetting'] = $param[$defaultLanguageCode]['ResultBarSetting'];
			$form['StartDate'] = $param[$defaultLanguageCode]['StartDate'];
			$form['EndDate'] = $param[$defaultLanguageCode]['EndDate'];
			$form['Modified'] = $modified;
			$form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $param[$defaultLanguageCode]['IsEnable'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("poll_item_info", $form, $where);
			
			// Detail
			if(count($params['params'])>0){
				foreach ($param as $lang => $value) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $value['Title'];
					$formDetail['IntroText'] = $value['IntroText'];
					$formDetail['Modified'] = $modified;
					$whereDetail['ID = ? '] = $id;
					$whereDetail['Lang = ? '] = $lang;
					$affRow = $this->db->update("poll_item_detail", $formDetail, $whereDetail);
					if ($affRow == 0) {
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = $lang;
						$formDetail['Created'] = $modified;
						$formDetail['IsEnable'] = 'Y';
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("poll_item_detail", $formDetail);
					}
				}
			}
						
			if($result){
				$data->id = $id;
				$data->result = true;
			}
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	function delete($params) {
		$data = new stdClass();
		$data->result = false;
		try {

			$modified = date("Y-m-d H:i:s");
			
			$form = array();
			$where = array();
			$form['IsDeleted'] = 'Y';
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("poll_item_info", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
	public function getOption($lang) {
		$select = $this->db->select()
		->from(array('tbl' => 'poll_item_info'), array( 'ID'))
		->joinInner(array('d' => 'poll_item_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Name'=>'Title'))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->order("convert(d.Title USING tis620) ASC")
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Name"];
		}
		return $result;
	}
	
	function getFile($id) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'FileInfo'), '')
			->columns(array(
					"tbl.Name",
					"tbl.HashName",
					"tbl.FileSize",
					"tbl.FileType",
			))
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getFiles($pollItemID) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'poll_item_file'), '')
			->joinLeft(array("fi" => "file_info"), "tbl.FileInfoID = fi.ID ", "")
			->columns(array(
					"FileInfoID" => "tbl.FileInfoID",
					"ItemFileType" => "tbl.FileType",
					
					"FileName" => "fi.Name",
					"FileHashName" => "fi.HashName",
					"FileType" => "fi.FileType",
					"FileSize" => "fi.FileSize",
					"FileExtension" => "fi.Extension"
			))
			->where("tbl.PollItemID = ? ", $pollItemID)
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getIdfile($id) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'PollInfoFile'), array(""))
			->columns(array(
					"tbl.FileInfoID",
					"tbl.FileType"
			))
			->where("tbl.PollInfoID = ? " ,$id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function saveQuestion($params) {
		$data = new stdClass();
		$data->result = false;
		try {

			$pollItemID = $params['hdfID'];
			$modified = date("Y-m-d H:i:s");
			
			if(count($params['row'])>0){
				$i=1;
				foreach($params['row'] as $k1=>$v1){
					unset($Question);
					$Question = json_decode($v1['setting'],true);
					// echo '<h4>Question '.$i.'</h4><pre>'; print_r($Question); echo '</pre>';
					
					// Check has question
					$hasQuestion = $this->hasPollQuestion($k1);

					// Question
					$formQuestion = array();
					$formQuestion['PollItemID'] = $pollItemID;
					$formQuestion['Ordering'] = $i;
					if(!$hasQuestion){
						$formQuestion['ID'] = $k1;
						$formQuestion['Created'] = $modified;
						$formQuestion['CreatedBy'] = $this->UserID;
						$result = $this->db->insert("poll_question_info", $formQuestion);
					}else{
						$formQuestion['Modified'] = $modified;
						$formQuestion['ModifiedBy'] = $this->UserID;
						$whereQuestion['ID = ? '] = $k1;
						$result = $this->db->update("poll_question_info", $formQuestion, $whereQuestion);
					}

					// Question Detail
					if(count($Question)>0){
						foreach ($Question as $lang=>$value) {
							$formQuestionDetail = array();
							$formQuestionDetail['Question'] = $value['Question'];
							$formQuestionDetail['Description'] = $value['Description'];
							if(!$hasQuestion){
								$formQuestionDetail['ID'] = $k1;
								$formQuestionDetail['Lang'] = $lang;
								$formQuestionDetail['Created'] = $modified;
								$formQuestionDetail['CreatedBy'] = $this->UserID;
								$result = $this->db->insert("poll_question_detail", $formQuestionDetail);
							}else{
								$formQuestionDetail['Modified'] = $modified;
								$formQuestionDetail['ModifiedBy'] = $this->UserID;
								$whereQuestionDetail['ID = ? '] = $k1;
								$whereQuestionDetail['Lang = ? '] = $lang;
								$result = $this->db->update("poll_question_detail", $formQuestionDetail, $whereQuestionDetail);
							}
						}
					}

					if(count($v1['cols'])>0){
						$j=1;
						foreach($v1['cols'] as $k2=>$v2){
							unset($Option);
							$Option = json_decode($v2['setting'],true);
							// echo '<h4>Option '.$i.'-'.$j.'</h4><pre>'; print_r($Option); echo '</pre>';

							// Check has question option
							$hasOption = $this->hasPollQuestionOption($k2);

							$formOption = array();
							$formOption['PollQuestionID'] = $k1;
							$formOption['Ordering'] = $j;
							if(!$hasOption){
								$formOption['ID'] = $k2;
								$formOption['Created'] = $modified;
								$formOption['CreatedBy'] = $this->UserID;
								$result = $this->db->insert("poll_option_info", $formOption);
							}else{
								$formOption['Modified'] = $modified;
								$formOption['ModifiedBy'] = $this->UserID;
								$whereOption['ID = ? '] = $k2;
								$result = $this->db->update("poll_option_info", $formOption, $whereOption);
							}

							// Option Detail
							if(count($Option)>0){
								foreach ($Option as $lang=>$value) {
									$formOptionDetail = array();
									$formOptionDetail['Name'] = $value['Name'];
									if(!$hasOption){
										$formOptionDetail['ID'] = $k2;
										$formOptionDetail['Lang'] = $lang;
										$formOptionDetail['Created'] = $modified;
										$formOptionDetail['CreatedBy'] = $this->UserID;
										$result = $this->db->insert("poll_option_detail", $formOptionDetail);
									}else{
										$formOptionDetail['Modified'] = $modified;
										$formOptionDetail['ModifiedBy'] = $this->UserID;
										$whereOptionDetail['ID = ? '] = $k2;
										$whereOptionDetail['Lang = ? '] = $lang;
										$result = $this->db->update("poll_option_detail", $formOptionDetail, $whereOptionDetail);
									}
								}
							}

							$j++;
						}
					}

					$i++;
				}
			}

			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	private function hasPollQuestion($ID){
		$select = $this->db->select()
		->from(array('tbl' => 'poll_question_info'), array("ID"))
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted = 'N' ")
		->where("tbl.ID = ? ", $ID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		$result = false;
		if($data->ID){
			$result = true;
		}
		return $result;
	}

	private function hasPollQuestionOption($ID){
		$select = $this->db->select()
		->from(array('tbl' => 'poll_option_info'), array("ID"))
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted = 'N' ")
		->where("tbl.ID = ? ", $ID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();

		$result = false;
		if($data->ID){
			$result = true;
		}
		return $result;
	}

	function getSubmissionList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->columns(array(
					"ID" => "tbl.ID",
					"ActionDate" => "tbl.ActionDate",
					"RemoteIP" => "tbl.RemoteIP",
					"AnswerType" => "tbl.AnswerType",
			))
			->where("tbl.IsDeleted ='N'")
			;

			if($advancesearch['PollItemID']){
				$select = $select->where("tbl.PollItemID = ?", $advancesearch['PollItemID']);
			}
			
			$records = $this->getSubmissionListCount($select);
			
			if (empty($sidx)) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select()
			->from(array('tbl' => $select), array('*'))
			->columns(array("RECORD" => "abs($records)"))
			->order("$rowSort $sord")
			->limitPage($page, $rows)
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["ActionDate"] = empty($data[$i]["ActionDate"]) ? null : new DateTime($data[$i]["ActionDate"]);
				}
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}
		return $data;
	}
	function getSubmissionListCount($select) {
		$select = $this->db->select()
		->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data->RECORDS;
	}

	function getSubmissionValues($PollSubmissionID,$PollQuestionID,$lang='th'){
		$select = $this->db->select()
		->from(array('tbl' => 'poll_submission_values'), array(""))
		->joinLeft(array('od' => 'poll_option_detail') , "tbl.PollOptionID = od.ID and od.Lang = '{$lang}'" , array(""))
		->columns(array(
			"ID" => "tbl.ID",
			"PollQuestionID" => "tbl.PollQuestionID",
			"PollOptionName" => "od.Name",
		))
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted = 'N' ")
		->where("tbl.PollSubmissionID = ? ", $PollSubmissionID)
		->where("tbl.PollQuestionID = ? ", $PollQuestionID)
		;
		$stmt = $this->db->query($select);
		$data = $stmt->fetchObject();
		return $data;
	}

	// ---------------------------------------------------  Report ------------------------------------------------------- //

	function getReportSubmissionList($PollItemID) {
		$data = new stdClass();
		try {

			$selectAmount  = $this->db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->joinLeft(array('v' => 'poll_submission_values'),"tbl.ID = v.PollSubmissionID", '')
			->columns(array(
					"v.PollQuestionID",
					"Amount" => "count(tbl.PollItemID)"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->group("v.PollQuestionID")
			;

			$select = $this->db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->joinLeft(array('v' => 'poll_submission_values'),"tbl.ID = v.PollSubmissionID", '')
			->joinLeft(array('a' => $selectAmount),"v.PollQuestionID = a.PollQuestionID", '')
			->columns(array(
					"v.PollQuestionID",
					"v.PollOptionID",
					"Amount" => "count(tbl.PollItemID)",
					"Percentage" => "concat(round(( COUNT(tbl.PollItemID)/a.amount * 100 ),2),'%')"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollItemID = ? ", $PollItemID)
			->group("v.PollQuestionID")
			->group("v.PollOptionID")
			->group("a.amount")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getQuestionListByLanguage($PollItemID='-1', $lang='th') {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'poll_question_info'), '')
			->joinLeft(array('d' => 'poll_question_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"Question" => "d.Question",
					"Description" => "d.Description"
			))
			->where("d.Description IS NOT NULL ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollItemID = ? ", $PollItemID)
			->order("tbl.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function getOptionListByLanguage($PollQuestionID='-1', $lang='th') {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'poll_option_info'), '')
			->joinLeft(array('d' => 'poll_option_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"Name" => "d.Name"
			))
			->where("d.Name IS NOT NULL ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollQuestionID = ? ", $PollQuestionID)
			->order("tbl.Ordering")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function listPublish($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			$status = $params['status'];
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {
					$form = array ();
					$where = array ();
					$form['IsEnable'] = empty($status) ? 'N' : $status;
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['ID = ? '] = $id;
					$affRow += $this->db->update("poll_item_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function listCopy($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			$modified = date("Y-m-d H:i:s");
			$defaultLanguage = $params['defaultLanguage'];
			
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {

					// Info
					$selectInfo = $this->db->select ()
					->from(array('tbl'=>'poll_item_info'), array(
						'PollCategoryID',
						'IntroImageID',
						'OneUserPerVote',
						'OneIPPerVote',
						'VoteDelay',
						'ShowIntroText',
						'ResultDisplay',
						'ResultPieSetting',
						'ResultBarSetting',
						'StartDate',
						'EndDate',
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['PollCategoryID'] = empty($dataInfo->PollCategoryID) ? null : $dataInfo->PollCategoryID;
					$form['IntroImageID'] = empty($dataInfo->IntroImageID) ? null : $dataInfo->IntroImageID;
					$form['OneUserPerVote'] = empty($dataInfo->OneUserPerVote) ? null : $dataInfo->OneUserPerVote;
					$form['OneIPPerVote'] = empty($dataInfo->OneIPPerVote) ? null : $dataInfo->OneIPPerVote;
					$form['VoteDelay'] = empty($dataInfo->VoteDelay) ? null : $dataInfo->VoteDelay;
					$form['ShowIntroText'] = empty($dataInfo->ShowIntroText) ? null : $dataInfo->ShowIntroText;
					$form['ResultDisplay'] = empty($dataInfo->ResultDisplay) ? null : $dataInfo->ResultDisplay;
					$form['ResultPieSetting'] = empty($dataInfo->ResultPieSetting) ? null : $dataInfo->ResultPieSetting;
					$form['ResultBarSetting'] = empty($dataInfo->ResultBarSetting) ? null : $dataInfo->ResultBarSetting;
					$form['StartDate'] = empty($dataInfo->StartDate) ? null : $dataInfo->StartDate;
					$form['EndDate'] = empty($dataInfo->EndDate) ? null : $dataInfo->EndDate;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("poll_item_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'poll_item_detail'), array('Lang','Title','IntroText'))
						->where("tbl.ID = ? ", $id)
						;
						$stmtDetail = $this->db->query($selectDetail);
						$dataDetail = $stmtDetail->fetchAll();

						if(count($dataDetail)>0){
							foreach($dataDetail as $d){
								$form = array();
								$form['ID'] = $tmpID;
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Title'] = empty($d['Title']) ? null : $d['Title'];
								if($defaultLanguage == $d['Lang']){
									$form['Title'] = $form['Title'] . ' (Copy)';
								}
								$form['IntroText'] = empty($d['IntroText']) ? null : $d['IntroText'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("poll_item_detail", $form);
							}
						}

						// Question
						$selectQuestion = $this->db->select ()
						->from(array('tbl'=>'poll_question_info'), array('ID','Ordering'))
						->where("tbl.PollItemID = ? ", $id)
						;
						$stmtQuestion = $this->db->query($selectQuestion);
						$dataQuestion = $stmtQuestion->fetchAll();

						if(count($dataQuestion)>0){
							foreach($dataQuestion as $que){

								$tmpPollQuestionID = $this->gencode();

								$form = array();
								$form['PollItemID'] = $tmpID;
								$form['ID'] = $tmpPollQuestionID;
								$form['Ordering'] = empty($que['Ordering']) ? null : $que['Ordering'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("poll_question_info", $form);

								// Question Detail
								$selectQuestionDetail = $this->db->select ()
								->from(array('tbl'=>'poll_question_detail'), array('Lang','Question','Description'))
								->where("tbl.ID = ? ", $que['ID'])
								;
								$stmtQuestionDetail = $this->db->query($selectQuestionDetail);
								$dataQuestionDetail = $stmtQuestionDetail->fetchAll();

								if(count($dataQuestionDetail)>0){
									foreach($dataQuestionDetail as $queDetail){
										$form = array();
										$form['ID'] = $tmpPollQuestionID;;
										$form['Lang'] = empty($queDetail['Lang']) ? null : $queDetail['Lang'];
										$form['Question'] = empty($queDetail['Question']) ? null : $queDetail['Question'];
										$form['Description'] = empty($queDetail['Description']) ? null : $queDetail['Description'];
										$form['Created'] = $modified;
										$form['CreatedBy'] = $this->UserID;
										$this->db->insert("poll_question_detail", $form);
									}
                                }
                                
                                // Option
								$selectOption = $this->db->select ()
								->from(array('tbl'=>'poll_option_info'), array('ID','Ordering'))
								->where("tbl.PollQuestionID = ? ", $que['ID'])
								;
								$stmtOption = $this->db->query($selectOption);
								$dataOption = $stmtOption->fetchAll();

								if(count($dataOption)>0){
									foreach($dataOption as $opt){

										$tmpPollOptionID = $this->gencode();

										$form = array();
										$form['ID'] = $tmpPollOptionID;
										$form['PollQuestionID'] = $tmpPollQuestionID;
										$form['Ordering'] = empty($opt['Ordering']) ? null : $opt['Ordering'];
										$form['Created'] = $modified;
										$form['CreatedBy'] = $this->UserID;
                                        $this->db->insert("poll_option_info", $form);
                                        
                                        // Option Detail
                                        $selectOptionDetail = $this->db->select ()
                                        ->from(array('tbl'=>'poll_option_detail'), array('Lang','Name'))
                                        ->where("tbl.ID = ? ", $opt['ID'])
                                        ;
                                        $stmtOptionDetail = $this->db->query($selectOptionDetail);
                                        $dataOptionDetail = $stmtOptionDetail->fetchAll();

                                        if(count($dataOptionDetail)>0){
                                            foreach($dataOptionDetail as $optDetail){
                                                $form = array();
                                                $form['ID'] = $tmpPollOptionID;
                                                $form['Lang'] = empty($optDetail['Lang']) ? null : $optDetail['Lang'];
                                                $form['Name'] = empty($optDetail['Name']) ? null : $optDetail['Name'];
                                                $form['Created'] = $modified;
                                                $form['CreatedBy'] = $this->UserID;
                                                $this->db->insert("poll_option_detail", $form);
                                            }
                                        }

									}
                                }

							}
						}

					}

				} // End Foreach
			} // End Count
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function listDelete($params) {
		$data = new stdClass();
		$data->result = false;
		try {
			
			$modified = date("Y-m-d H:i:s");
			
			$affRow = 0;
			if (count($params['ids']) > 0) {
				foreach ($params['ids'] as $id) {
					$form = array ();
					$where = array ();
					$form['IsDeleted'] = 'Y';
					$form['Modified'] = $modified;
					$form['ModifiedBy'] = $this->UserID;
					$where['ID = ? '] = $id;
					$affRow += $this->db->update("poll_item_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}

	function gencode(){
		$code  = '';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 8);
		$code .= '-';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 4);
		$code .= '-';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 4);
		$code .= '-';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 4);
		$code .= '-';
		$code .= substr(md5(uniqid(mt_rand(), true)) , 0, 12);
		return $code;
	}

}

?>
