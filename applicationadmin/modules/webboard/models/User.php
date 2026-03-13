<?php

class Webboard_Model_User {
    
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
    
    function getList($sidx, $sord, $rows, $page, $textstr, $filter, $advancesearch, $lang = 'th') {
        $data = array();

        try {
            $select = $this->db->select()
            ->from(array("tbl" => "webboard_user") , "")
            ->columns(array(
                "tbl.ID",
                "tbl.Username",
                "tbl.TitleName",
                "tbl.FirstName",
                "tbl.LastName",
                "tbl.Email",
                "tbl.Created",
                "tbl.LastLoginDate",

                "tbl.IsEnable",
            ))
            ->where("tbl.IsDeleted = 'N'")
            ;
            
            // set criteria

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where(
                    "(tbl.Username like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%") . ") OR " . 
                    "( tbl.TitleName like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%" ) . ") OR " . 
                    "( tbl.FirstName like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%" ) . ") OR " . 
                    "( tbl.Email like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%" ) . ") OR " . 
                    "( tbl.LastName like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%") .  ")"
            );
            }   
            
            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.IsEnable = ?", $advancesearch['status']);
            }
            
            if (!empty($advancesearch['sendfromRegister'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfromRegister']);
                $select = $select->where("tbl.Created >= ?", $value);
            }

            if (!empty($advancesearch['sendtoRegister'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendtoRegister']);
                $select = $select->where("tbl.Created <= ?", $value . " 23:59:59");
            }

            if (!empty($advancesearch['sendfromLogin'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfromLogin']);
                $select = $select->where("tbl.LastLoginDate >= ?", $value);
            }

            if (!empty($advancesearch['sendtoLogin'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendtoLogin']);
                $select = $select->where("tbl.LastLoginDate <= ?", $value . " 23:59:59");
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

            if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
                    $data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
                    $data[$i]["LastLoginDate"] = empty($data[$i]["LastLoginDate"]) ? null : new DateTime($data[$i]["LastLoginDate"]);
                    $data[$i]["FullName"] = $data[$i]["TitleName"]." ".$data[$i]["FirstName"]." ".$data[$i]["LastName"];
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

    function getDataByUsername($username) {
        $data = new stdClass();
        try {

            $select = $this->db->select()
            ->from(array("tbl" => "webboard_user"), "tbl.ID")
	        ->where("tbl.Username = ?", $username)
            ->where("tbl.IsDeleted = 'Y' ")
	        ;
	        $stmt = $this->db->query($select);
            $data = $stmt->fetchObject();

	    } catch (Exception $e) {
	        
	    }
	    return $data;
    }

    function getDataByEmail($Email) {

        $data = new stdClass();
        try {

            $select = $this->db->select()
            ->from(array("tbl" => "webboard_user"), "tbl.ID")
            ->where("tbl.Email = ?", $Email)
            ->where("tbl.IsDeleted = 'N' ")
            ;

	        $stmt = $this->db->query($select);
            $data = $stmt->fetchObject();

	    } catch (Exception $e) {
	        
	    }
	    return $data;
    }
    
    function getData($ID) {
	    $data = new stdClass();
	    try {
	        $select = $this->db->select()
            ->from(array("tbl" => "webboard_user"), "*")
	        ->where("tbl.ID = ?", $ID)
	        ;
	        $stmt = $this->db->query($select);
            $data = $stmt->fetchObject();
            
            if (!empty($data->ID)) {
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
            }

	    } catch (Exception $e) {
	        
	    }
	    return $data;
    }
    

    public function getOption($lang) {
        $select = $this->db->select()
        ->from(array('tbl' => 'webboard_category_info'), '')
        ->joinLeft(array('d' => 'webboard_category_detail'),"tbl.ID = d.ID", '')
        ->columns(array(
            "ID" => "tbl.ID",
            "Lang" => "d.Lang",
            "Title" => "d.Title",
        ))
        ->where("tbl.IsDeleted = ?", "N")
        ->where("tbl.IsEnable = ?", "Y")
        ->where("d.Lang = ?", $lang)
        ->order("convert(d.Title USING TIS620)  ASC")
        ;
        
        $stmt = $this->db->query($select);
        $data = $stmt->fetchAll();
        $result = array();
        
        foreach ($data as $value) {
            $result[$value["ID"]] = $value["Title"];
        }
        return $result;
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
			$id = $params[''];
            $modified = date("Y-m-d H:i:s");

			$form = array();
            $form['TitleName'] = $params['TitleName'];
            $form['FirstName'] = $params['FirstName'];
            $form['LastName'] = $params['LastName'];
            $form['Username'] = $params['Username'];

            $form['Password'] = md5($params['Password']);
            $form['Email'] = $params['Email'];
            $form['IdentityCardID'] = $params['CardID'];
            $form['Gender'] = $params['Sex'];
            $form['Telephone'] = $params['Telephone'];
            $form['Address'] = $params['Address'];
			$form['Created'] = $modified;
            $form['CreatedBy']= $this->UserID;
			$form['IsEnable']= $params['IsStatus'];
			$result = $this->db->insert("webboard_user", $form, $where);
            $id = $this->db->lastInsertId();
										
			
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

			$form = array();
            $where = array();
            $form['TitleName'] = $params['TitleName'];
            $form['FirstName'] = $params['FirstName'];
            $form['LastName'] = $params['LastName'];

            $form['Password'] = md5($params['Passwordone']);
            $form['Email'] = $params['Email'];
            $form['IdentityCardID'] = $params['CardID'];
            $form['Gender'] = $params['Sex'];
            $form['Telephone'] = $params['Telephone'];
            $form['Address'] = $params['Address'];
			$form['Modified'] = $modified;
            $form['ModifiedBy']= $this->UserID;
			$form['IsEnable']= $params['IsStatus'];
			$where['ID = ? '] = $id;
			$result = $this->db->update("webboard_user", $form, $where);
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
			$result = $this->db->update("webboard_user", $form, $where);
			
			$data->result = $result > 0;
			
		} catch (Exception $e) {
			$data->result = false;
			$data->message = $e->getMessage();
		}
		return $data;
    }

    function getListExcel($advancesearch, $lang = 'th') {
        $data = array();

        try {
            $select = $this->db->select()
            ->from(array("tbl" => "webboard_user") , "")
            ->columns(array(
                "tbl.ID",
                "tbl.Username",
                "tbl.TitleName",
                "tbl.FirstName",
                "tbl.Password",
                "tbl.Telephone",
                "tbl.Address",
                "tbl.IdentityCardID",
                "tbl.Gender",
                "tbl.LastName",
                "tbl.Email",
                "tbl.Created",
                "tbl.LastLoginDate",

                "tbl.IsEnable",
            ))
            ->where("tbl.IsDeleted = 'N'")
            ;
            
            // set criteria

            if (!empty($advancesearch['textsearch'])) {
                $select = $select->where(
                    "(tbl.Username like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%") . ") OR " . 
                    "( tbl.TitleName like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%" ) . ") OR " . 
                    "( tbl.FirstName like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%" ) . ") OR " . 
                    "( tbl.Email like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%" ) . ") OR " . 
                    "( tbl.LastName like " . $this->db->quote("%" . $advancesearch['textsearch'] . "%") .  ")"
            );
            }  
            
            if (!empty($advancesearch['status'])) {
                $select = $select->where("tbl.IsEnable = ?", $advancesearch['status']);
            }
            
            if (!empty($advancesearch['sendfromRegister'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfromRegister']);
                $select = $select->where("tbl.Created >= ?", $value);
            }

            if (!empty($advancesearch['sendtoRegister'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendtoRegister']);
                $select = $select->where("tbl.Created <= ?", $value . " 23:59:59");
            }

            if (!empty($advancesearch['sendfromLogin'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendfromLogin']);
                $select = $select->where("tbl.LastLoginDate >= ?", $value);
            }

            if (!empty($advancesearch['sendtoLogin'])) {
                $value = Emwork_Utility_DateTime::dateThaiToMySQL($advancesearch['sendtoLogin']);
                $select = $select->where("tbl.LastLoginDate <= ?", $value . " 23:59:59");
            }
            //echo $select;
            $stmt = $this->db->query($select);
            $data = $stmt->fetchAll();

            if (count($data) > 0) {
				for ($i = 0; $i < count($data); $i ++) {
                    $data[$i]["Created"] = empty($data[$i]["Created"]) ? null : new DateTime($data[$i]["Created"]);
                    $data[$i]["LastLoginDate"] = empty($data[$i]["LastLoginDate"]) ? null : new DateTime($data[$i]["LastLoginDate"]);
                    $data[$i]["FullName"] = $data[$i]["TitleName"]." ".$data[$i]["FirstName"]." ".$data[$i]["LastName"];
				}
            }

        } catch (Exception $e) {
            echo $e->getMessage();
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
					$affRow += $this->db->update("webboard_user", $form, $where);
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
					->from(array('tbl'=>'webboard_user'), array(
						'Username',
						'LastAttemptDate',
                        'Password',
                        'TitleName',
                        'FirstName',
                        'LastName',
                        'Email',
                        'Telephone',
                        'Address',
                        'LastLoginDate',
                        'IdentityCardID',
                        'Gender',
                        'ActivateCode',
                        'VerifyCode',
						'AttemptAmount'
						))
					->where("tbl.ID = ? ", $id)
					;
					$stmtInfo = $this->db->query($selectInfo);
					$dataInfo = $stmtInfo->fetchObject();

					$form = array();
					$form['Username'] = empty($dataInfo->Username) ? null : $dataInfo->Username;
					$form['Password'] = empty($dataInfo->Password) ? null : $dataInfo->Password;
					$form['TitleName'] = empty($dataInfo->TitleName) ? null : $dataInfo->TitleName;
					$form['FirstName'] = empty($dataInfo->FirstName) ? null : $dataInfo->FirstName;
                    $form['LastName'] = empty($dataInfo->LastName) ? null : $dataInfo->LastName;
                    $form['Email'] = empty($dataInfo->Email) ? null : $dataInfo->Email;
                    $form['Telephone'] = empty($dataInfo->Telephone) ? null : $dataInfo->Telephone;
                    $form['Address'] = empty($dataInfo->Address) ? null : $dataInfo->Address;
                    $form['LastLoginDate'] = null;
                    $form['IdentityCardID'] = empty($dataInfo->IdentityCardID) ? null : $dataInfo->IdentityCardID;
                    $form['Gender'] = empty($dataInfo->Gender) ? null : $dataInfo->Gender;
                    $form['AttemptAmount'] = null;
                    $form['LastAttemptDate'] = null;
                    $form['ActivateCode'] = null;
                    $form['VerifyCode'] = null;
                    $form['Created'] = $modified;
					$form['CreatedBy'] = $this->UserID;
					$form['IsEnable'] = 'N';
					$data->result = $this->db->insert("webboard_user", $form);

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
					$affRow += $this->db->update("webboard_user", $form, $where);
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
