<?php

class Sitemap_Model_Index
{

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
    
	 function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
		$data = array();
		try {
			
			$select = $this->db->select()
			->from(array('tbl' => 'sitemap_info'), '')
			->joinLeft(array('d' => 'sitemap_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}'" , '')
			->columns(array(
					"ID" => "tbl.ID",
					"Title" => "d.Title",
					"IsEnable" => "tbl.IsEnable"
			))
			->where("tbl.IsDeleted ='N'")
			;
			
			if (!empty($advancesearch['textsearch'])) {
				$val = $advancesearch['textsearch'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
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

	function getMenugroupInfo()
	{
		$data = array();
		try {

			$select = $this->db->select()
			->from(array("tbl" => "menugroup_info"), "*")
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {

		}
		return $data;
	}

	function getData($id)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "sitemap_info"), "")
			->columns(array(
				"tbl.ID",
				"tbl.ShowDetail",
				"tbl.Col",
				"tbl.IsEnable"
			))
			->where("tbl.ID = ?", $id)
			->where("tbl.IsDeleted = 'N'")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
		} catch (Exception $e) {

		}
		return $data;
	}

	function getDataByLanguage($id)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'sitemap_detail'), "")
			->columns(array(
					"tbl.Lang",
					"tbl.Title",
					"tbl.Detail"
			))
			->where("tbl.ID = ?", $id)
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
	}

	function getDataGroupMenu($id)
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "menugroup_info"), "")
			->joinLeft(array("d" => "sitemap_info_menugroups"), "tbl.ID = d.MenuGroupID and d.SitemapInfoID = '{$id}'", "")
			->columns(array(
				"tbl.ID",
				"tbl.Name",
				"d.SitemapInfoID"
			))
			->where("tbl.IsDeleted = 'N'")
			->order("IFNULL(d.Ordering, 999)")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

		} catch (Exception $e) {
			
		}
		return $data;
	}

	public function getOption($lang = 'th')
	{
		$result = array();

		try {
			$select = $this->db->select()
			->from(array("tbl" => "sitemap_info"), "")
			->joinLeft(array("d" => "sitemap_detail"), "tbl.ID = d.ID and d.Lang = '{$lang}'", "")
			->columns(array(
				"tbl.ID",
				"d.Title"
			))
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();

			foreach ( $data as $value ) {
				$result[$value["ID"]] = $value["Title"];
			}
		} catch (Exception $e) {

		}
		return $result;
	}

	function save($params) {
		$data = new stdClass();
		try {

			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = '';
			$modified = date('Y-m-d H:i:s');

			$dataGroupMenu = $param[$defaultLanguageCode]['dataGroupMenu'];

			$formInfo = array();
			$formInfo['ShowDetail'] = empty($param[$defaultLanguageCode]['ShowDetail']) ? 'N' : $param[$defaultLanguageCode]['ShowDetail'];
			$formInfo['Col'] = $param[$defaultLanguageCode]['Col'];
			$formInfo['Created'] = $modified;
			$formInfo['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
			$formInfo['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
			$formInfo['IsDeleted'] = 'N';
			$result = $this->db->insert("sitemap_info", $formInfo);	
			$id = $this->db->lastInsertId();

			$i = 0;
			if(count($param)> 0) {
				foreach ($param as $p) {
					if ($i < count($param)) {
						$formDetail = array();
						$formDetail['ID'] = $id;
						$formDetail['Lang'] = array_keys($param)[$i];
						$formDetail['Title'] = $p['Title'];
						$formDetail['Detail'] = $p['Detail'];
						$formDetail['Created'] = $modified;
						$formDetail['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
						$formDetail['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
						$formDetail['IsDeleted'] = 'N';
						$this->db->insert("sitemap_detail", $formDetail);
						$i ++;
					}
				}
			}

			$i_groupMenu = 1;
			if(count($dataGroupMenu)>0) {
				foreach($dataGroupMenu as $p) {
					$formGroup = array();
					$formGroup['SitemapInfoID'] = $id;
					$formGroup['MenuGroupID'] = $p;
					$formGroup['IsUsed'] = 'Y';
					$formGroup['Ordering'] = $i_groupMenu;
					$formGroup['Created'] = $modified;
					$formGroup['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
					$formGroup['IsEnable'] = 'Y';
					$formGroup['IsDeleted'] = 'N';
					$this->db->insert("sitemap_info_menugroups", $formGroup);
					$i_groupMenu++;
				}
			}

			if($result) {
				$data->result = true;
				$data->ID = $id;
			}

		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}


	function update($params) {
		$data = new stdClass();
		try {

			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = $params['hdfID'];
			$modified = date('Y-m-d H:i:s');

			$dataGroupMenu = $param[$defaultLanguageCode]['dataGroupMenu'];

			$formInfo = array();
			$whereInfo = array();
			$formInfo['ShowDetail'] = empty($param[$defaultLanguageCode]['ShowDetail']) ? 'N' : $param[$defaultLanguageCode]['ShowDetail'];
			$formInfo['Col'] = $param[$defaultLanguageCode]['Col'];
			$formInfo['Modified'] = $modified;
			$formInfo['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
			$formInfo['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
			$whereInfo['ID = ?'] = $id;
			$result = $this->db->update("sitemap_info", $formInfo, $whereInfo);	

			if(count($param)> 0) {
				foreach ($param as $k => $v) {
					$formDetail = array();
					$whereDetail = array();
					$formDetail['Title'] = $v['Title'];
					$formDetail['Detail'] = $v['Detail'];
					$formDetail['Modified'] = $modified;
					$formDetail['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
					$formDetail['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
					$whereDetail['Lang =?'] = $k;
					$whereDetail['ID = ?'] = $id;
					$this->db->update("sitemap_detail", $formDetail, $whereDetail);
				}
			}

			$whereGroup['SitemapInfoID = ?'] = $id;
			$this->db->delete("sitemap_info_menugroups",$whereGroup);
			$i_groupMenu = 1;
			if(count($dataGroupMenu)>0) {
				foreach($dataGroupMenu as $v) {
					$formGroup = array();
					$formGroup['SitemapInfoID'] = $id;
					$formGroup['MenuGroupID'] = $v;
					$formGroup['IsUsed'] = 'Y';
					$formGroup['Ordering'] = $i_groupMenu;
					$formGroup['Created'] = $modified;
					$formGroup['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
					$formGroup['IsEnable'] = 'Y';
					$formGroup['IsDeleted'] = 'N';
					$this->db->insert("sitemap_info_menugroups", $formGroup);
					$i_groupMenu++;
				}
			}

			if($result) {
				$data->result = true;
				$data->ID = $id;
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
			$result = $this->db->update("sitemap_info", $form, $where);
			
			$data->result = $result;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
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
					$affRow += $this->db->update("sitemap_info", $form, $where);
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
					->from(array('tbl'=>'sitemap_info'), array(
						'ShowDetail',
						'Col',
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['ShowDetail'] = empty($dataInfo->ShowDetail) ? null : ($dataInfo->ShowDetail);
					$form['Col'] = empty($dataInfo->Col) ? null : $dataInfo->Col;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("sitemap_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'sitemap_detail'), array('Lang','Title','Detail'))
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
								$form['Detail'] = empty($d['Detail']) ? null : $d['Detail'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("sitemap_detail", $form);
							}
						}

						// Sitemap Menugroups
						$selectMenugroups = $this->db->select ()
						->from(array('tbl'=>'sitemap_info_menugroups'), array('MenuGroupID','IsUsed','Ordering'))
						->where("tbl.SitemapInfoID = ? ", $id)
						;
						$stmtMenugroups = $this->db->query($selectMenugroups);
						$dataMenugroups = $stmtMenugroups->fetchAll();

						if(count($dataMenugroups)>0){
							foreach($dataMenugroups as $d){
								$form = array();
								$form['SitemapInfoID'] = $tmpID;
								$form['MenuGroupID'] = empty($d['MenuGroupID']) ? null : $d['MenuGroupID'];
								$form['IsUsed'] = empty($d['IsUsed']) ? null : $d['IsUsed'];
								$form['Ordering'] = empty($d['Ordering']) ? null : $d['Ordering'];
								$form['Created'] = $modified;
								$form['CreatedBy'] = $this->UserID;
								$this->db->insert("sitemap_info_menugroups", $form);
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
					$affRow += $this->db->update("sitemap_info", $form, $where);
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