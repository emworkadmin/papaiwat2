<?php
class Faq_Model_Subcategory {
	
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
			$this->db = Zend_Db_Table::getDefaultAdapter ();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance ();
			$this->auth = $authobj->getIdentity ();
			$this->UserID = isset ( $this->auth->UserID ) ? $this->auth->UserID : '';
		} catch ( Exception $e ) {
			
		}
	}

	public function getOption($lang, $id=null) {
		
		$select = $this->db->select()
		->from ( array ('tbs' => 'faq_subcategory_info' ), array ('ID') )
		->joinInner ( array ('d' => 'faq_subcategory_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Name') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Name USING TIS620)  ASC" );

		if(!empty($id)){
			$select = $select->where ( "tbs.FaqCategoryID = ?", $id );
		}

		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchAll ();
		$result = array ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Name"];
		}
		return $result;
	}
	
	function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array ();
		try {
			
			// set query
			$select = $this->db->select()->distinct()
			->from ( array ('tbs' => 'faq_subcategory_info'), '')
			->joinLeft ( array ("d" => "faq_subcategory_detail" ), "tbs.ID = d.ID and d.Lang = '{$lang}' ", '')
			->joinLeft ( array ("c" => "faq_category_info" ), "tbs.FaqCategoryID = c.ID", '')
			->joinLeft ( array ("cd" => "faq_category_detail" ), "c.ID = cd.ID and cd.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbs.ID",
					"Name" => "d.Name",
					"CategoryName" => "cd.Name",
					"Ordering" => "tbs.Ordering",
					"Created" => "tbs.Created",
					"CreatedBy" => "tbs.CreatedBy",
					"Modified" => "tbs.Modified",
					"ModifiedBy" => "tbs.ModifiedBy",
					"IsEnable" => "tbs.IsEnable",
					"IsDeleted" => "tbs.IsDeleted",
			))
			->where ( "tbs.IsDeleted = 'N' " )
			;
			
			// set criteria
			if (! empty ( $advancesearch ['searchText'] )) {
				$val = $advancesearch ['searchText'];
				$select = $select->where ( "d.Name like " . $this->db->quote ( "%" . $val . "%" ) );
			}
			if (! empty ( $advancesearch ['isEnable'] )) {
				$select = $select->where ( "tbs.IsEnable = ? ", $advancesearch ['isEnable'] );
			}
			if (! empty ( $advancesearch ['category'] )) {
				$select = $select->where ( "tbs.FaqCategoryID = ? ", $advancesearch ['category'] );
			}
			
			// find total
			$records = $this->getListCount ( $select );
			
			// set ordering and total
			if (empty ( $sidx )) {
				$sidx = "ID";
			}
			$rowSort = $sidx;
			
			$select = $this->db->select ()
			->from ( array ('tbs' => $select ), array ('*'))
			->columns ( array ("RECORD" => "abs($records)" ) )
			->limitPage ( $page, $rows )
			;
			if (in_array($rowSort, array('Name'))) {
				$select = $select->order("convert($rowSort USING tis620) $sord");
			} else {
				$select = $select->order("$rowSort $sord");
			}

			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
			
			if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
					$data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
					$data[$i]["Modified"] = empty($data[$i]["Modified"]) ? null : new DateTime($data[$i]["Modified"]);
				}
			}
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
		return $data;
	}
	function getListCount($select) {
		$select = $this->db->select ()
		->from ( array ('T' => $select), array ("RECORDS" => "count(T.ID)") )
		;
		$stmt = $this->db->query ( $select );
		$data = $stmt->fetchObject ();
		return $data->RECORDS;
	}

	function getInfo($id) {
		$data = new stdClass ();
		try {
			
			$select = $this->db->select ()
			->from(array ('tbs' => 'faq_subcategory_info'), array (
					"ID",
					"FaqCategoryID",
					"Ordering" ,
					"IsDeleted",
					"IsEnable" 
			))
			->where ( "tbs.IsDeleted = 'N' " )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchObject ();
		} catch ( Exception $e ) {
			
		}
		return $data;
	}

	function getDetails($id) {
		$data = array();
		try {
			$select = $this->db->select()
			->from(array ('tbs' => 'faq_subcategory_detail'), array (
					"ID",
					"Name",
					"IsEnable",
					"Lang"
			))
			->where ( "tbs.IsDeleted = 'N'" )
			->where ( "tbs.ID = ? ", $id )
			;
			$stmt = $this->db->query ( $select );
			$data = $stmt->fetchAll ();
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	public function getDataByLanguage($id, $lang) {
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('m' => 'faq_subcategory_info'), "")
			->joinLeft(array("md" => "faq_subcategory_detail"), "m.ID = md.ID and md.Lang = '{$lang}' ", "")
			->joinLeft ( array ("c" => "faq_category_info" ), "m.FaqCategoryID = c.ID", '')
			->joinLeft ( array ("cd" => "faq_category_detail" ), "c.ID = cd.ID and cd.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "m.ID",
					"FaqCategoryID" => "m.FaqCategoryID",
					"Lang" => 'md.Lang',
					"Name" => "md.Name",
					"CategoryName" => "cd.Name",
					"Ordering" => "m.Ordering",
					"IsDeleted" => "m.IsDeleted",
					"IsEnable" => "m.IsEnable",
			))
			->where("m.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	function save($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			
			$id = '';
			$param = $params['params'];			
			$modified = date("Y-m-d H:i:s");
			
			$form = array ();
			$form ['FaqCategoryID'] = empty ( $param[$defaultLanguageCode]['FaqCategoryID'] ) ? 0 : $param[$defaultLanguageCode]['FaqCategoryID'];
			$form ['Ordering'] = empty ( $param[$defaultLanguageCode]['Ordering'] ) ? 0 : $param[$defaultLanguageCode]['Ordering'];
			$form ['Created'] = $modified;
			$form ['CreatedBy'] = $this->UserID;
			$form ['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form ['IsDeleted'] = 'N';
			$result = $this->db->insert ( "faq_subcategory_info", $form);
			$id = $this->db->lastInsertId ();
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
				// Detail
				if (count($param) > 0) {
					foreach ($param as $lang => $value) {
						$formDetail = array ();
						$formDetail ['ID'] = $id;
						$formDetail ['Lang'] = $lang;
						$formDetail ['Name'] = empty ( $value ['Name'] ) ? null : $value ['Name'];
						$formDetail ['Created'] = $modified;
						$formDetail ['CreatedBy'] = $this->UserID;
						$this->db->insert ( "faq_subcategory_detail", $formDetail );
					}
				}
			}
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	function update($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			
			$id = $params ['hdfID'];
			$param = $params['params'];
			$modified = date("Y-m-d H:i:s");
			
			$form = array ();
			$form ['FaqCategoryID'] = empty ( $param[$defaultLanguageCode]['FaqCategoryID'] ) ? 0 : $param[$defaultLanguageCode]['FaqCategoryID'];
			$form ['Ordering'] = empty ( $param[$defaultLanguageCode]['Ordering'] ) ? 0 : $param[$defaultLanguageCode]['Ordering'];
			$form ['IsEnable'] = empty ( $param[$defaultLanguageCode]['IsEnable'] ) ? 'N' : $param[$defaultLanguageCode]['IsEnable'];
			$form ['Modified'] = $modified;
			$form ['ModifiedBy'] = $this->UserID;
			$result = $where ["ID = ?"] = $id;
			$this->db->update ( "faq_subcategory_info", $form, $where );
			
			$data->result = $result > 0;
			$data->id = $id;
			
			if ($result) {
				$data->result = true;
				$data->id = $id;
				
				// Detail
				if (count($param) > 0) {
					foreach ($param as $lang => $value) {
						$formDetail = array ();
						$whereDetail = array ();
						$formDetail ['Name'] = empty ( $value ['Name'] ) ? null : $value ['Name'];
						$formDetail ['Modified'] = $modified;
						$formDetail ['ModifiedBy'] = $this->UserID;
						$whereDetail ["ID = ?"] = $id;
						$whereDetail ["Lang = ?"] = $lang;
						$affectedRows = $this->db->update ( "faq_subcategory_detail", $formDetail, $whereDetail );
						if ($affectedRows == 0) {
							$formDetail["ID"] = $id;
							$formDetail["Lang"] = $lang;
							$formDetail['Created'] = $modified;
							$formDetail["IsEnable"] = 'Y';
							$formDetail["IsDeleted"] = 'N';
							$this->db->insert("faq_subcategory_detail", $formDetail);
						}
					}
				}
			}
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
		}
		return $data;
	}
	
	function delete($params) {
		$data = new stdClass ();
		$data->result = false;
		try {
			
			$id = $params ['id'];
			
			// User
			$form = array ();
			$where = array ();
			$form ['IsDeleted'] = 'Y';
			$form ['Modified'] = date ( 'Y-m-d H:i:s' );
			$form ['ModifiedBy'] = $this->UserID;
			$where ['ID = ? '] = $id;
			$result = $this->db->update ( "faq_subcategory_info", $form, $where );
			
			$data->result = count ( $result ) > 0;
		} catch ( Exception $e ) {
			$data->result = false;
			$data->message = $e->getMessage ();
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
					$affRow += $this->db->update("faq_subcategory_info", $form, $where);
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


					$selectInfo = $this->db->select ()
					->from(array('tbl'=>'faq_subcategory_info'), array('FaqCategoryID','Ordering'))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['FaqCategoryID'] = empty($dataInfo->FaqCategoryID) ? null : $dataInfo->FaqCategoryID;
					$form['Ordering'] = empty($dataInfo->Ordering) ? null : $dataInfo->Ordering;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("faq_subcategory_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'faq_subcategory_detail'), array('Lang','Name'))
						->where("tbl.ID = ? ", $id)
						;
						$stmtDetail = $this->db->query($selectDetail);
						$dataDetail = $stmtDetail->fetchAll();

						if(count($dataDetail)>0){
							foreach($dataDetail as $d){
								$form = array();
								$form['ID'] = $tmpID;
								$form['Lang'] = empty($d['Lang']) ? null : $d['Lang'];
								$form['Name'] = empty($d['Name']) ? null : $d['Name'];
								if($defaultLanguage == $d['Lang']){
									$form['Name'] = $form['Name'] . ' (Copy)';
								}
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("faq_subcategory_detail", $form);
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
					$form ['IsDeleted'] = 'Y';
					$form ['Modified'] = $modified;
					$form ['ModifiedBy'] = $this->UserID;
					$where ['ID = ? '] = $id;
					$affRow += $this->db->update("faq_subcategory_info", $form, $where);
				}
			}
			
			$data->result = $affRow > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
}

?>
