<?php

class Zend_View_Helper_Widget_Menu1_Menu1 extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Menu1_Menu1($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		$menuGroupID = $this->params["menugroup"];
		$isUseLanguageCode = false;
		if (Zend_Registry::isRegistered("IsUseLanguageCode")) {
			$isUseLanguageCode = Zend_Registry::get("IsUseLanguageCode");
		}
		$prefixUrl = ""; // ($isUseLanguageCode ? '/' . $lang : '');
		
		$MenuID = false;
		if (Zend_Registry::isRegistered("MenuID")) {
			$MenuID = Zend_Registry::get("MenuID");
		}

		if($MenuID) {
			$select = $db->select()
			->from(array('tbl' => 'menu_info'), array('ID', 'Url'))
			->where("tbl.ID = ?", $MenuID)
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			;
			$stmt = $db->query($select);
			$dataMenuID = $stmt->fetchObject();
		}
		if($dataMenuID->ID) {
			$dataMenuID->OriginalUrl = $dataMenuID->Url;
			$dataMenuID->Url = ($isUseLanguageCode ? '/' . $lang : '') . $dataMenuID->Url ;
		}

		// -------------------------------------------- Get Data -------------------------------------------- //

		$select = $db->select()
		->from(array('tbl' => 'menu_info'), array('ID', 'MenuGroupID', 'ParentID', 'Url', 'Type', 'Params'))
		->joinLeft(array('d' => 'menu_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}' ", array("Name"))
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted = 'N' ")
		->where("tbl.MenuGroupID = ? ", $menuGroupID)
		->order("tbl.Ordering asc")
		;
		$stmt = $db->query($select);
		$data = $stmt->fetchAll();
		
		// ------------------------------------------- Prepare Data ------------------------------------------- //

		$dataMenu = array();
		
		// Level 1
		if(count($data)>0){
			foreach ($data as $k => $v){
				if(empty($v['ParentID'])){
					$dataMenu[$v['ID']]['id'] = $v['ID'];
					$dataMenu[$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
					$dataMenu[$v['ID']]['name'] = $v['Name'];
					if (empty($v["Type"]) || ($v["Type"] == "component")) {
						$dataMenu[$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
						$dataMenu[$v['ID']]['target'] = "_self";
					} elseif ($v["Type"] == "url") {
						$dataMenu[$v['ID']]['url'] = $v['Url'];
						$menuSetting = json_decode($v['Params'], true); 
						$dataMenu[$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"]; 
					} else {
						$dataMenu[$v['ID']]['url'] = "#";
						$dataMenu[$v['ID']]['target'] = "_self";
					}
					$dataMenu[$v['ID']]['parentLevel2'] = array();
					unset($data[$k]);
				}
			}
		}
		// Level 2
		if(count($data)>0){
			foreach ($data as $k => $v){
				foreach ($dataMenu as $k1 => $v1){
					if($v['ParentID'] == $k1){
						$dataMenu[$k1]['parentLevel2'][$v['ID']]['id'] = $v['ID'];
						$dataMenu[$k1]['parentLevel2'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
						$dataMenu[$k1]['parentLevel2'][$v['ID']]['name'] = $v['Name'];
						//$dataMenu[$k1]['parentLevel2'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
						
						if (empty($v["Type"]) || ($v["Type"] == "component")) {
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['target'] = "_self";
						} elseif ($v["Type"] == "url") {
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['url'] = $v['Url'];
							$menuSetting = json_decode($v['Params'], true);
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"];
						} else {
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['url'] = "#";
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['target'] = "_self";
						}
						$dataMenu[$k1]['parentLevel2'][$v['ID']]['parentLevel3'] = array();
						unset($data[$k]);
					}
				}
			}
		}
		// Level 3
		if(count($data)>0){
			foreach ($data as $k => $v){
				foreach ($dataMenu as $k1 => $v1){
					foreach ($v1['parentLevel2'] as $k2 => $v2){
						if($v['ParentID'] == $k2){
							$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['id'] = $v['ID'];
							$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
							$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['name'] = $v['Name'];
							//$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
							
							if (empty($v["Type"]) || ($v["Type"] == "component")) {
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['target'] = "_self";
							} elseif ($v["Type"] == "url") {
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['url'] = $v['Url'];
								$menuSetting = json_decode($v['Params'], true);
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"];
							} else {
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['url'] = "#";
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['target'] = "_self";
							}
							
							$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['parentLevel4'] = array();
							unset($data[$k]);
						}
					}
				}
			}
		}
		// Level 4
		if(count($data)>0){
			foreach ($data as $k => $v){
				foreach ($dataMenu as $k1 => $v1){
					foreach ($v1['parentLevel2'] as $k2 => $v2){
						foreach ($v2['parentLevel3'] as $k3 => $v3){
							if($v['ParentID'] == $k3){
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['id'] = $v['ID'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['name'] = $v['Name'];
								//$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
								
								if (empty($v["Type"]) || ($v["Type"] == "component")) {
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['target'] = "_self";
								} elseif ($v["Type"] == "url") {
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['url'] = $v['Url'];
									$menuSetting = json_decode($v['Params'], true);
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"];
								} else {
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['url'] = "#";
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['target'] = "_self";
								}
								
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['parentLevel5'] = array();
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
				foreach ($dataMenu as $k1 => $v1){
					foreach ($v1['parentLevel2'] as $k2 => $v2){
						foreach ($v2['parentLevel3'] as $k3 => $v3){
							foreach ($v3['parentLevel4'] as $k4 => $v4){
								if($v['ParentID'] == $k4){
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['id'] = $v['ID'];
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['name'] = $v['Name'];
									//$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
																		
									if (empty($v["Type"]) || ($v["Type"] == "component")) {
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['target'] = "_self";
									} elseif ($v["Type"] == "url") {
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['url'] = $v['Url'];
										$menuSetting = json_decode($v['Params'], true);
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"];
									} else {
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['url'] = "#";
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['target'] = "_self";
									}
									
									unset($data[$k]);
								}
							}
						}
					}
				}
			}
		}

		// ----------------------------------------------- MENU ----------------------------------------------- //

		echo '<link rel="stylesheet" type="text/css" href="/assets/wmenu1/css/font-awesome.css">';
		echo '<link rel="stylesheet" type="text/css" href="/assets/wmenu1/css/menu.css">';
		echo '<script src="/assets/wmenu1/js/function.js"></script>';

		// TODO : Custom CSS
		$style = '';
		if(!empty($style)){
			echo '<style>' . $style . '<style>';
		}

		echo '		<div class="widget-menu-1">';
		echo '			<a id="menu-toggle" class="button dark" href="#"><em class="icon-reorder"></em></a>';
		echo '			<nav id="navigation">';
		echo '				<ul class="nav navbar-nav" id="main-menu">';

		if(count($dataMenu)>0){
			foreach ($dataMenu as $k1 => $v1){

			if($v1['url'] == $dataMenuID->Url || $v1['url'] == $dataMenuID->OriginalUrl) { 
				echo '<li class="active">';
			} else {
				echo '<li>';
			}

			// Menu Level 1
			if(count($v1['parentLevel2'])>0) {
				echo '<a href="' . $prefixUrl . $v1['url'] . '" target="' . $v1["target"] . '">' . $v1['name'] . ' <span class="caret"></span>' . '</a>';
				echo '<ul class="sub-menu">';
				foreach ($v1['parentLevel2'] as $k2 => $v2) {
					
					// Menu Level 2
					if(count($v2['parentLevel3'])>0) {
						echo '<li>';
						echo '<a href="' . $prefixUrl . $v2['url'] . '" target="' . $v2["target"] . '">' . $v2['name'] . '</a>';
						echo '<ul class="sub-menu">';
						foreach ($v2['parentLevel3'] as $k3 => $v3){
							
							// Menu Level 3
							if(count($v3['parentLevel4'])>0){
								echo '<li>';
								echo '<a href="' . $prefixUrl . $v3['url'] . '" target="' . $v3["target"] . '">' . $v3['name'] . '</a>';
								echo '<ul class="sub-menu">';
								foreach ($v3['parentLevel4'] as $k4 => $v4){
									
									// Menu Level 4
									if(count($v4['parentLevel5'])>0){
										echo '<li>';
										echo '<a href="' . $prefixUrl . $v4['url'] . '" target="' . $v4["target"] . '">' . $v4['name'] . '</a>';
										echo '<ul class="sub-menu">';
										foreach ($v4['parentLevel5'] as $k5 => $v5){
											
											// Menu Level 5
											if($v5['url'] == $dataMenuID->Url || $v5['url'] == $dataMenuID->OriginalUrl) { 
												echo '<li class="active">';
											} else {
												echo '<li>';
											}
											echo '<a href="' . $prefixUrl . $v5['url'] . '" target="' . $v5["target"] . '">' . $v5['name'] . '</a></li>';
											
										}
											
										echo '</ul>';
										echo '</li>';
									}else{
										if($v4['url'] == $dataMenuID->Url || $v4['url'] == $dataMenuID->OriginalUrl) { 
											echo '<li class="active">';
										} else {
											echo '<li>';
										}
										echo '<a href="' . $prefixUrl . $v4['url'] . '" target="' . $v4["target"] . '">' . $v4['name'] . '</a></li>';
									}
									
								}
								echo '</ul>';
								echo '</li>';
							}else{
								if($v3['url'] == $dataMenuID->Url || $v3['url'] == $dataMenuID->OriginalUrl) { 
									echo '<li class="active">';
								} else {
									echo '<li>';
								}
								echo '<a href="' . $prefixUrl . $v3['url'] . '" target="' . $v3["target"] . '">' . $v3['name'] . '</a></li>';
							}
							
						}
						echo '</ul>';
						echo '</li>';
					} else {
						if($v2['url'] == $dataMenuID->Url || $v2['url'] == $dataMenuID->OriginalUrl) { 
							echo '<li class="active">';
						} else {
							echo '<li>';
						}
						echo '<a href="' . $prefixUrl . $v2['url'] . '" target="' . $v2["target"] . '">' . $v2['name'] . '</a></li>';
					}
			
				}
				echo '</ul>';
			}else{
				echo '<a href="' . $prefixUrl . $v1['url'] . '" target="' . $v1["target"] . '">' . $v1['name'] . '</a>';
			}

			echo '</li>';

			}
		}

		echo '				</ul>';
		echo '			</nav>';
		echo '			<div class="clear"></div>';
		echo '		</div>';
		
	}
}