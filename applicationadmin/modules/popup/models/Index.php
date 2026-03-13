<?php

Class Popup_Model_Index {

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
            echo $e->getMessage();
        }
    }

    function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th')
    {
        $data = array();
        try {
            
            $select = $this->db->select()
            ->from(array("tbl" => "popup_info") , "")
            ->joinLeft(array('d' => 'popup_detail') , "tbl.ID = d.ID and d.Lang = '{$lang}'" , '')
            ->columns(array(
                "ID" => "tbl.ID",
                "Title" => "d.Title",
                "Status" => "tbl.IsEnable"
            ))
            ->where("tbl.isDeleted = 'N'")
            ;

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where("d.Title like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%"));
            }
            
            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.IsEnable = ?", $advancesearch['status']);
            }

            // find total
            $records = $this->getListCount($select);

            // set ordering and total            
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

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $data;
    }

	public function getOption($lang) {

		$select = $this->db->select()
		->from(array('tbl' => 'popup_info'), array( 'ID'))
		->joinInner(array('d' => 'popup_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array('Title'=>'Title'))
		->where("tbl.IsDeleted = ?", "N")
		->where("tbl.IsEnable = ?", "Y")
		->order("convert(d.Title USING tis620) ASC")
		;
		// echo $
		$stmt = $this->db->query($select);
		$data = $stmt->fetchAll();
		$result = array();
		foreach ($data as $value) {
			$result[$value["ID"]] = $value["Title"];
		}
		return $result;
	}
	

    function getListCount($select) {
        $select = $this->db->select()
        ->from(array('T' => $select), array("RECORDS" => "count(T.ID)"))
        ;
        $stmt = $this->db->query($select);
        $data = $stmt->fetchObject();
        return $data->RECORDS;
    }

    function getDataByLanguage($id,$lang)
	{
		$data = new stdClass();
		try {
			$select = $this->db->select()
			->from(array('tbl' => 'popup_info'), "")
			->joinInner(array('d' => 'popup_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", "")
			->columns(array(
				"Lang"=>"d.Lang",
				"Title"=>"d.Title",
				"Detail"=>"d.Detail",
				'Width'=>'tbl.Width',
				'Height'=>'tbl.Height',
				'BackgroundColor'=>'tbl.BackgroundColor',
				'Padding'=>'tbl.Padding',
				"CustomCss"=>"tbl.CustomCss",
				"IsEnable"=>"tbl.IsEnable"
			))
			->where("tbl.ID = ?", $id)
			->where("tbl.IsDeleted = 'N'")
			;
			
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
		} catch (Exception $e) {
			
		}
		return $data;
    }
    
    public function getData($id,$lang='th')
    {
        $data = new stdClass();

        try {
			$select = $this->db->select()
			->from(array("tbl" => "popup_info") , "")
			->joinInner(array('d' => 'popup_detail'), "tbl.ID = d.ID  and d.Lang='{$lang}'", array(
				'ID'=>'tbl.ID',
				'CustomCss'=>'tbl.CustomCss',
				'Width'=>'tbl.Width',
				'Height'=>'tbl.Height',
				'BackgroundColor'=>'tbl.BackgroundColor',
				'Padding'=>'tbl.Padding',
				'IsEnable'=>'tbl.IsEnable',
				'Title'=>'d.Title',
			))
			->where("tbl.ID = ?", $id)
			;
				
            $stmt = $this->db->query($select);
            $data = $stmt->fetchObject();

        } catch (Exception $e) {
            echo $e->getMessage();
        }
        return $data;
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
			
			$formInfo = array();
			$formInfo['CustomCss'] = $param[$defaultLanguageCode]['CustomCss'];
			$formInfo['Height'] = $param[$defaultLanguageCode]['Height'];
			$formInfo['Width'] = $param[$defaultLanguageCode]['Width'];
			$formInfo['BackgroundColor'] = $param[$defaultLanguageCode]['BackgroundColor'];
			$formInfo['Padding'] = $param[$defaultLanguageCode]['Padding'];
			$formInfo['Created'] = $modified;
			$formInfo['CreatedBy'] = empty($this->UserID) ? null : $this->UserID;
			$formInfo['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
			$formInfo['IsDeleted'] = 'N';
			$result = $this->db->insert("popup_info", $formInfo);	
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
						$this->db->insert("popup_detail", $formDetail);
						$i ++;
					}
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

    public function update($params) {
		$data = new stdClass();
		try {
			$defaultLanguageCode = $params['DefaultLanguageCode'];
			if (empty($defaultLanguageCode)) {
				throw new Exception("ไม่มีการกำหนดภาษาเริ่มต้น");
			}
			$param = $params['params'];
			$id = $params['hdfID'];
			$modified = date('Y-m-d H:i:s');
			
			$formInfo = array();
			$formInfo['CustomCss'] = $param[$defaultLanguageCode]['CustomCss'];
			$formInfo['Height'] = empty($param[$defaultLanguageCode]['Height']) ? null : $param[$defaultLanguageCode]['Height'];
			$formInfo['Width'] = empty($param[$defaultLanguageCode]['Width']) ? null : $param[$defaultLanguageCode]['Width'];
			$formInfo['BackgroundColor'] = $param[$defaultLanguageCode]['BackgroundColor'];
			$formInfo['Padding'] = $param[$defaultLanguageCode]['Padding'];
			$formInfo['Modified'] = $modified;
			$formInfo['ModifiedBy'] = empty($this->UserID) ? null : $this->UserID;
            $formInfo['IsEnable'] = $param[$defaultLanguageCode]['IsEnable'];
			$whereInfo['ID = ?'] = $id;
			$result = $this->db->update("popup_info", $formInfo, $whereInfo);	

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
					$this->db->update("popup_detail", $formDetail, $whereDetail);
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

    public function delete($params) {
	    $result = new stdClass();
	    $result->result = false;
	    $result->message = '';
	    
	    try {
	        $id = $params['itemid'];
	        
	        // Delete
	        $form= array();
	        $where = array();
	        $form['IsDeleted'] = 'Y';
	        $form['Modified'] = date('Y-m-d H:i:s');
	        $form['ModifiedBy'] = $this->UserID;
	        $where['ID = ?'] = $id;
	        $result->result = $this->db->update("popup_info", $form, $where);
	        
	    } catch (Exception $e) {
	        $result->result = false;
	        $result->message = $e->getMessage();

	    }
	    return $result;
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
					$affRow += $this->db->update("popup_info", $form, $where);
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
					->from(array('tbl'=>'popup_info'), array(
						'CustomCss',
						'Width',
						'Height',
						'BackgroundColor',
						'Padding'
					  ))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['CustomCss'] = empty($dataInfo->CustomCss) ? null : ($dataInfo->CustomCss);
					$form['Width'] = empty($dataInfo->Width) ? null : $dataInfo->Width;
					$form['Height'] = empty($dataInfo->Height) ? null : $dataInfo->Height;
					$form['BackgroundColor'] = empty($dataInfo->BackgroundColor) ? null : $dataInfo->BackgroundColor;
					$form['Padding'] = empty($dataInfo->Padding) ? null : $dataInfo->Padding;
					$form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("popup_info", $form);
					$tmpID = $this->db->lastInsertId();

					if($data->result && $tmpID){

						// Detail
						$selectDetail = $this->db->select ()
						->from(array('tbl'=>'popup_detail'), array('Lang','Title','Detail'))
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
								$this->db->insert("popup_detail", $form);
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
					$affRow += $this->db->update("popup_info", $form, $where);
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