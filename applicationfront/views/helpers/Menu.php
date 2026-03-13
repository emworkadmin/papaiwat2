<?php
/*
 *
*/

class Zend_View_Helper_Menu extends Zend_View_Helper_Abstract
{
	protected $db = null;
	
	public function Menu($lang)
	{
		$this->db = Zend_Db_Table::getDefaultAdapter();
		
		$data = new stdClass();
		try {
		    
		    // Get data template
		    $selectTemplate = $this->db->select()->distinct()
		    ->from(array('tbl' => 'template'), array('id', 'menu_group_id'))
		    ;
		    $stmtTemplate = $this->db->query($selectTemplate);
		    $dataTemplate = $stmtTemplate->fetchObject();
		    
		    // Get data menu
			$select = $this->db->select()->distinct()
			->from(array('tbl' => 'menu'), array('id', 'menu_group_id', 'parent_id', 'url'))
			->joinLeft(array('d' => 'menu_detail'), "tbl.id = d.id", array("name"))
			->where("tbl.is_enable = 'Y' ")
			->where("tbl.is_deleted = 'N' ")
			->where("tbl.menu_group_id = ? ", $dataTemplate->menu_group_id)
			->where("d.lang = ? ", $lang)
			->order("tbl.ordering asc")
			;
			$stmt = $this->db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
		
		}
		
		// Prepare data
		$result = array();
		
		// Level 1
		if(count($data)>0){
		    foreach ($data as $k => $v){
		        if(empty($v['parent_id'])){
		            $result[$v['id']]['id'] = $v['id'];
		            $result[$v['id']]['menu_group_id'] = $v['menu_group_id'];
		            $result[$v['id']]['name'] = $v['name'];
		            $result[$v['id']]['url'] = $v['url'];
		            $result[$v['id']]['parentLevel2'] = array();
		            unset($data[$k]);
		        }
		    }
		}
		// Level 2
		if(count($data)>0){
		    foreach ($data as $k => $v){
		        foreach ($result as $k1 => $v1){
		            if($v['parent_id'] == $k1){
		                $result[$k1]['parentLevel2'][$v['id']]['id'] = $v['id'];
		                $result[$k1]['parentLevel2'][$v['id']]['menu_group_id'] = $v['menu_group_id'];
		                $result[$k1]['parentLevel2'][$v['id']]['name'] = $v['name'];
		                $result[$k1]['parentLevel2'][$v['id']]['url'] = $v['url'];
		                $result[$k1]['parentLevel2'][$v['id']]['parentLevel3'] = array();
		                unset($data[$k]);
		            }
		        }
		    }
		}
		// Level 3
		if(count($data)>0){
		    foreach ($data as $k => $v){
		        foreach ($result as $k1 => $v1){
		            foreach ($v1['parentLevel2'] as $k2 => $v2){
    		            if($v['parent_id'] == $k2){
    		                $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['id']]['id'] = $v['id'];
    		                $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['id']]['menu_group_id'] = $v['menu_group_id'];
    		                $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['id']]['name'] = $v['name'];
    		                $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['id']]['url'] = $v['url'];
    		                $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['id']]['parentLevel4'] = array();
    		                unset($data[$k]);
    		            }
		            }
		        }
		    }
		}
		// Level 4
		if(count($data)>0){
		    foreach ($data as $k => $v){
		        foreach ($result as $k1 => $v1){
		            foreach ($v1['parentLevel2'] as $k2 => $v2){
		                foreach ($v2['parentLevel3'] as $k3 => $v3){
    		                if($v['parent_id'] == $k3){
    		                    $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['id']]['id'] = $v['id'];
    		                    $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['id']]['menu_group_id'] = $v['menu_group_id'];
    		                    $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['id']]['name'] = $v['name'];
    		                    $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['id']]['url'] = $v['url'];
    		                    $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['id']]['parentLevel5'] = array();
    		                    unset($data[$k]);
    		                }
		                }
		            }
		        }
		    }
		}
		// Level 5
		if(count($data)>0){
		    foreach ($data as $k => $v){
		        foreach ($result as $k1 => $v1){
		            foreach ($v1['parentLevel2'] as $k2 => $v2){
		                foreach ($v2['parentLevel3'] as $k3 => $v3){
		                    foreach ($v3['parentLevel4'] as $k4 => $v4){
    		                    if($v['parent_id'] == $k4){
    		                        $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['id']]['id'] = $v['id'];
    		                        $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['id']]['menu_group_id'] = $v['menu_group_id'];
    		                        $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['id']]['name'] = $v['name'];
    		                        $result[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['id']]['url'] = $v['url'];
    		                        unset($data[$k]);
    		                    }
		                    }
		                }
		            }
		        }
		    }
		}

		return $result;
	}

}

?>
