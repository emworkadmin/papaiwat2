<?php

class Gallery_Model_Trash {
	
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
			->from(array('tbl' => 'gallery_article_info'), '')
			->joinLeft(array('d' => 'gallery_article_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}' " , '')
			->joinLeft(array('c' => 'gallery_category_info') , "tbl.GalleryCategoryID = c.ID" , '')
			->joinLeft(array('cd' => 'gallery_category_detail') , "c.ID = cd.ID and cd.Lang = '{$lang}' " , '')
			->joinLeft(array('u' => 'sys_user'), 'tbl.CreatedBy = u.ID', '')
			->joinLeft(array('ut' => 'sys_user'), 'tbl.TrashBy = ut.ID', '')
			->columns(array(
					"ID" => "tbl.ID",
					"ViewAmount" => "tbl.ViewAmount",
					"Created" => "tbl.Created",
					"Modified" => "tbl.Modified",
					"IsEnable" => "tbl.IsEnable",
					"TrashDate" => "tbl.TrashDate",
					"Title" => "d.Title",
					"Ordering" => "tbl.Ordering",
					"CategoryTitle" => "cd.Title",
					"AuthorName" => "concat(u.FirstName, ' ', u.LastName)",
					"TrashByName" => "concat(u.FirstName, ' ', u.LastName)"
			))
			->where("tbl.IsDeleted ='N'")
			->where("tbl.IsTrash ='Y'")
			;
			
			if (!empty($advancesearch['searchText'])) {
				$val = $advancesearch['searchText'];
				$select = $select->where("d.Title like " . $this->db->quote("%" . $val . "%" ));
			}
			if (!empty($advancesearch['trashdate'])) {
			    $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['trashdate']);
			    $select = $select->where("tbl.TrashDate >= ?", $value);
			}
			if (!empty($advancesearch['trashdateTo'])) {
			    $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['trashdateTo']);
			    $select = $select->where("tbl.TrashDate <= ?", $value . " 23:59:59");
			}
			if (!empty($advancesearch['isEnable'])) {
				$select = $select->where("tbl.IsEnable = ? ", $advancesearch['isEnable']);
			}
			if (!empty($advancesearch['isCat'])) {
				$select = $select->distinct()
				->joinInner(array('ca' => "gallery_category_article"), "tbl.ID = ca.GalleryArticleID", '')
				->where("ca.GalleryCategoryID = ? ", $advancesearch['isCat'])
				->where("ca.IsDeleted = 'N' ")
				;
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
					$data[$i]["TrashDate"] = empty($data[$i]["TrashDate"]) ? null : new DateTime($data[$i]["TrashDate"]);
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
	
	function untrash($params) {
		$data = new stdClass();
		$data->result = false;
		try {

			$modified = date("Y-m-d H:i:s");
			
			$form = array();
			$where = array();
			$form['IsTrash'] = 'N';
			$form['TrashDate'] = null;
			$form['TrashBy'] = null;
			$form['Modified'] = $modified;
			$form['ModifiedBy'] = $this->UserID;
			$where['ID = ? '] = $params['id'];
			$result = $this->db->update("gallery_article_info", $form, $where);
			
			$data->result = $result > 0;
			
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
			$result = $this->db->update("gallery_article_info", $form, $where);
			
			//Tag
			$formTag = array();
			$whereTag = array();
			$formTag['IsDeleted'] = 'Y';
			$whereTagDelete['GalleryArticleID = ? '] = $params['id'];
			$result = $this->db->update("gallery_article_tags",$formTag,$whereTag);

			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
	}
	
}

?>
