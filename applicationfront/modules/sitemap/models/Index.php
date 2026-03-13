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
			$this->db = Zend_Db_Table::getDefaultAdapter();
			
			// do the authentication
			$authobj = Zend_Auth::getInstance();
			$this->auth = $authobj->getIdentity();
			$this->UserID = isset($this->auth->UserID) ? $this->auth->UserID : '';
		} catch (Exception $e) {
			
		}
	}
	
	function getDataSitemapInfo($id, $lang = 'th')
	{
		$data = array();
		try {
			$select = $this->db->select()
			->from(array("tbl" => "sitemap_info"), "")
			->joinLeft(array("d" => "sitemap_detail"), "tbl.ID = d.ID", "")
			->columns(array(
				"tbl.ID",
				"tbl.ShowDetail",
				"tbl.Col",
				"d.Title",
				"d.Detail",
			))
			->where("tbl.ID = ?", $id)
			->where("d.Lang = ?", $lang)
			->where("tbl.IsEnable = 'Y'")
			->where("tbl.IsDeleted = 'N'")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchObject();
			
			unset($select);
			$select = $this->db->select()
			->from(array("tbl" => "sitemap_info_menugroups"), "")
			->joinLeft(array("mgif" => "menugroup_info"), "tbl.MenuGroupID = mgif.ID", "")
			->joinLeft(array("mif" => "menu_info"), "tbl.MenuGroupID = mif.MenuGroupID", "")
			->joinLeft(array('md' => 'menu_detail'), "mif.ID = md.ID and md.Lang = '{$lang}' ", "")
			->columns(array(
				"mif.ID",
				"tbl.MenuGroupID",
				"mif.ParentID",
				"mif.Url",
				"md.Name"
			))
			->where("tbl.SitemapInfoID = ?", $id)

			->where("mgif.isEnable = 'Y' ")
			
			->where("mif.IsEnable = 'Y' ")
			->where("mif.IsDeleted = 'N' ")
			->order("tbl.MenuGroupID ASC")
			->order("mif.Ordering ASC")
			;

			$stmt = $this->db->query($select);
			$dataMenuGroup = $stmt->fetchAll();

   			// ------------------------------------------- Prepare Data ------------------------------------------- //			
			$dataMenu = array();
			$tmp_array = array();
			
			// Level 1
			if(count($dataMenuGroup)>0){
				foreach ($dataMenuGroup as $k => $v) {
					if(empty($v['ParentID'])) {
						$dataMenu[$v['ID']]['id'] = $v['ID'];
						$dataMenu[$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
						$dataMenu[$v['ID']]['name'] = $v['Name'];
						$dataMenu[$v['ID']]['url'] = $v['Url'];
						$dataMenu[$v['ID']]['parentLevel2'] = array();
						unset($dataMenuGroup[$k]);
					}
				}
			}

			// Level 2
			if(count($dataMenuGroup)>0) {
				foreach ($dataMenuGroup as $k => $v){
					foreach ($dataMenu as $k1 => $v1){
						if($v['ParentID'] == $k1) {
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['id'] = $v['ID'];
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['name'] = $v['Name'];
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['url'] = $v['Url'];
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['parentLevel3'] = array();
							unset($dataMenuGroup[$k]);
						}
					}
				}
			}

			// Level 3
			if(count($dataMenuGroup)>0) {
				foreach ($dataMenuGroup as $k => $v) {
					foreach ($dataMenu as $k1 => $v1) {
						foreach ($v1['parentLevel2'] as $k2 => $v2){
							if($v['ParentID'] == $k2){
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['id'] = $v['ID'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['name'] = $v['Name'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['url'] = $v['Url'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['parentLevel4'] = array();
								unset($dataMenuGroup[$k]);
							}
						}
					}
				}
			}
			
			// Level 4
			if(count($dataMenuGroup)>0){
				foreach ($dataMenuGroup as $k => $v) {
					foreach ($dataMenu as $k1 => $v1) {
						foreach ($v1['parentLevel2'] as $k2 => $v2){
							foreach ($v2['parentLevel3'] as $k3 => $v3){
								if($v['ParentID'] == $k3){
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['id'] = $v['ID'];
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['name'] = $v['Name'];
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['url'] = $v['Url'];
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['parentLevel5'] = array();
									unset($dataMenuGroup[$k]);
								}
							}
						}
					}
				}
			}

			// Level 5
			if(count($dataMenuGroup)>0) {
				foreach ($dataMenuGroup as $k => $v) {
					foreach ($dataMenu as $k1 => $v1) {
						foreach ($v1['parentLevel2'] as $k2 => $v2){
							foreach ($v2['parentLevel3'] as $k3 => $v3){
								foreach ($v3['parentLevel4'] as $k4 => $v4){
									if($v['ParentID'] == $k4){
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['id'] = $v['ID'];
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['name'] = $v['Name'];
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['url'] = $v['Url'];
										unset($dataMenuGroup[$k]);
									}
								}
							}
						}
					}
				}
			}
			
			$data->parentLevel1 = $dataMenu;


		} catch (Exception $e) {

		}
		return $data;
	}

}

?>