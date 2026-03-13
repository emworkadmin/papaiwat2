<?php

class Zend_View_Helper_Widget_Menushowall_Menushowall extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Menushowall_Menushowall($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		$menuGroupID = $this->params["menugroup"];
		$menuDeep = $this->params["deep"];
		$htmlClass = $this->params["HtmlClass"];
		$customCSS = $this->params["CustomCSS"];

		$isUseLanguageCode = false;
		if (Zend_Registry::isRegistered("IsUseLanguageCode")) {
			$isUseLanguageCode = Zend_Registry::get("IsUseLanguageCode");
		}
		
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
			$dataMenuID->Url = ($isUseLanguageCode ? '/' . $lang : '') . $dataMenuID->Url ;
		}

		// -------------------------------------------- Get Data -------------------------------------------- //

		$select = $db->select()
		->from(array('tbl' => 'menu_info'), array('ID', 'MenuGroupID', 'ParentID', 'Url', 'Type', 'Params'))
		->joinLeft(array('d' => 'menu_detail'), "tbl.ID = d.ID and d.Lang = '{$lang}'", array("Name"))
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
					}
					$dataMenu[$v['ID']]['parentLevel2'] = array();
					unset($data[$k]);
				}
			}
		}
		// Level 2
		if($menuDeep>=2 || $menuDeep==-1){
			if(count($data)>0){
				foreach ($data as $k => $v){
					foreach ($dataMenu as $k1 => $v1){
						if($v['ParentID'] == $k1){
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['id'] = $v['ID'];
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['name'] = $v['Name'];
							if (empty($v["Type"]) || ($v["Type"] == "component")) {
								$dataMenu[$k1]['parentLevel2'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
								$dataMenu[$k1]['parentLevel2'][$v['ID']]['target'] = "_self";
							} elseif ($v["Type"] == "url") {
								$dataMenu[$k1]['parentLevel2'][$v['ID']]['url'] = $v['Url'];
								$menuSetting = json_decode($v['Params'], true);
								$dataMenu[$k1]['parentLevel2'][$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"];
							} else {
								$dataMenu[$k1]['parentLevel2'][$v['ID']]['url'] = "#";
							}
							$dataMenu[$k1]['parentLevel2'][$v['ID']]['parentLevel3'] = array();
							unset($data[$k]);
						}
					}
				}
			}
		}
		if($menuDeep>=3 || $menuDeep==-1){
			// Level 3
			if(count($data)>0){
				foreach ($data as $k => $v){
					foreach ($dataMenu as $k1 => $v1){
						foreach ($v1['parentLevel2'] as $k2 => $v2){
							if($v['ParentID'] == $k2){
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['id'] = $v['ID'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['menu_group_id'] = $v['MenuGroupID'];
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['name'] = $v['Name'];
								if (empty($v["Type"]) || ($v["Type"] == "component")) {
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['target'] = "_self";
								} elseif ($v["Type"] == "url") {
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['url'] = $v['Url'];
									$menuSetting = json_decode($v['Params'], true);
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"];
								} else {
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['url'] = "#";
								}
								$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$v['ID']]['parentLevel4'] = array();
								unset($data[$k]);
							}
						}
					}
				}
			}
		}
		if($menuDeep>=4 || $menuDeep==-1){
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
									if (empty($v["Type"]) || ($v["Type"] == "component")) {
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['target'] = "_self";
									} elseif ($v["Type"] == "url") {
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['url'] = $v['Url'];
										$menuSetting = json_decode($v['Params'], true);
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"];
									} else {
										$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['url'] = "#";
									}
									$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$v['ID']]['parentLevel5'] = array();
									unset($data[$k]);
								}
							}
						}
					}
				}
			}
		}
		if($menuDeep>=5 || $menuDeep==-1){
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
										if (empty($v["Type"]) || ($v["Type"] == "component")) {
											$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['url'] = ($isUseLanguageCode ? '/' . $lang : '') . $v['Url'];
											$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['target'] = "_self";
										} elseif ($v["Type"] == "url") {
											$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['url'] = $v['Url'];
											$menuSetting = json_decode($v['Params'], true);
											$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['target'] = empty($menuSetting["Target"]) ? "_blank" : $menuSetting["Target"];
										} else {
											$dataMenu[$k1]['parentLevel2'][$k2]['parentLevel3'][$k3]['parentLevel4'][$k4]['parentLevel5'][$v['ID']]['url'] = "#";
										}
										unset($data[$k]);
									}
								}
							}
						}
					}
				}
			}
		}

		// ----------------------------------------------- MENU ----------------------------------------------- //
		$UID = uniqid();

		$css  = '';
		$css .= '#widget-menu-show-all-'.$UID.' { width: 100%; display: inline-block; padding-top: 15px; }';
		$css .= '#widget-menu-show-all-'.$UID.' ul { width: 100%; display: flex; flex-wrap: wrap; }'; 
		$css .= '#widget-menu-show-all-'.$UID.' li:not(.menu-group) { width: 100%; }'; 
		$css .= '#widget-menu-show-all-'.$UID.' a,';
		$css .= '#widget-menu-show-all-'.$UID.' span { color: #FFFFFF; display: block; }';
		$css .= '#widget-menu-show-all-'.$UID.' a:hover { color: #000000; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-1 a,';
		$css .= '#widget-menu-show-all-'.$UID.' .level-1 span { font-weight: bold; display: block; border-bottom: solid 1px #FFF; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-2 a,';
		$css .= '#widget-menu-show-all-'.$UID.' .level-2 span { margin-left: 20px; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-3 a,';
		$css .= '#widget-menu-show-all-'.$UID.' .level-3 span { margin-left: 20px; padding-left: 18px; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-4 a,';
		$css .= '#widget-menu-show-all-'.$UID.' .level-4 span { margin-left: 20px; padding-left: 35px; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-5 a,';
		$css .= '#widget-menu-show-all-'.$UID.' .level-5 span { margin-left: 20px; padding-left: 55px; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-2 em { position: absolute; color: #FFF; font-size: 12px; padding: 10px 10px 10px 0px; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-3 em { position: absolute; color: #FFF; font-size: 12px; padding: 10px 13px 10px 0px; left: 37px; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-4 em { position: absolute; color: #FFF; font-size: 12px; padding: 10px 15px 10px 0px; left: 52px; }';
		$css .= '#widget-menu-show-all-'.$UID.' .level-5 em { position: absolute; color: #FFF; font-size: 6px; padding: 10px 15px 10px 0px; left: 72px; }';
		$css .= '#widget-menu-show-all-'.$UID.' .icon-pointer { cursor: pointer; }';
		$css .= '#widget-menu-show-all-'.$UID.' .hide-child { display: none; }';
		$css .= '#widget-menu-show-all-'.$UID.' .menu-group { margin-bottom: 15px; }';
		echo '<style>'.$css.'</style>';

		$bgColor = '#3A8ADC';
		$bgOpacity = '0.8';
		$rgba = $this->hex2rgba($bgColor, $bgOpacity);

		$cssCanvas  = '';
		$cssCanvas .= '#toggleCanvas{ display: block !important; }';
		$cssCanvas .= '#offCanvas{ width: 100%; height: 100%; background: '.$rgba.'; }';
		$cssCanvas .= 'a.close-offcanvas { background: unset !important; font-size: 26px !important; right: 15px !important; }';
		$cssCanvas .= '.c-offcanvas--right { transform: translate3d(1366px,0,0); }';
		$cssCanvas .= '.c-offcanvas { transition: transform .5s cubic-bezier(.4,0,.6,1); }';
		echo '<style>'.$cssCanvas.'</style>';

		// Custom CSS
		if(!empty($customCSS)){
			echo '<style>'.$customCSS.'</style>';
		}

		echo '<div class="widget-menu-show-all '.$htmlClass.'" id="widget-menu-show-all-'.$UID.'">';
		echo '	<ul class="">';

		if(count($dataMenu)>0){
			foreach ($dataMenu as $k1 => $v1){
				echo '<li class="menu-group col-md-3 col-sm-4 col-xs-12">';
				echo '	<ul class="">';
				echo '		<li class="level-1">';
								if($v1['url']!='#'){
				echo '			<a href="' . $v1['url'] . '" target="' . $v1["target"] . '">' . $v1['name'] . '</a>';
								}else{
				echo '			<span>' . $v1['name'] . '</span>';
								}
				echo 		'</li>';
							// Menu Level 2
							if(count($v1['parentLevel2'])>0){
								echo '		<ul class="" id="level-2-'.$v1['id'].'">';
								foreach ($v1['parentLevel2'] as $k2 => $v2){
									if(count($v2['parentLevel3'])>0){ $cssClass = 'icon-pointer'; }else{ $cssClass = ''; }
									echo '		<li class="level-2">';
									echo '			<em class="fa fa-long-arrow-right ' . $cssClass . '" id="lnk-show-level-3-'.$v2['id'].'"></em>';
									if($v2['url']!='#'){
									echo '			<a href="' . $v2['url'] . '" target="' . $v2["target"] . '">' . $v2['name'] . '</a>';
													}else{
									echo '			<span>' . $v2['name'] . '</span>';
													}
									echo '		</li>';
									echo '		<script>';
									echo '		$("#lnk-show-level-3-'.$v2['id'].'").click(function(){';
									echo '			var isHide = $("#level-3-'.$v2['id'].'").hasClass("hide-child");';
									echo '			if(isHide){';
									echo '				$("#level-3-'.$v2['id'].'").removeClass("hide-child")';	
									echo '			}else{';
									echo '				$("#level-3-'.$v2['id'].'").addClass("hide-child")';		
									echo '			}';
									echo '		});';
									echo '		</script>';
									// Menu Level 3
									if(count($v2['parentLevel3'])>0){
											echo '		<ul class="hide-child" id="level-3-'.$v2['id'].'">';
										foreach ($v2['parentLevel3'] as $k3 => $v3){
											if(count($v3['parentLevel4'])>0){ $cssClass = 'icon-pointer'; }else{ $cssClass = ''; }
											echo '		<li class="level-3">';
											echo '			<em class="fa fa-caret-right ' . $cssClass . '" id="lnk-show-level-4-'.$v3['id'].'"></em>';
											if($v3['url']!='#'){
											echo '			<a href="' . $v3['url'] . '" target="' . $v3["target"] . '">' . $v3['name'] . '</a>';
															}else{
											echo '			<span>' . $v3['name'] . '</span>';
															}
											echo '		</li>';
											echo '		<script>';
											echo '		$("#lnk-show-level-4-'.$v3['id'].'").click(function(){';
											echo '			var isHide = $("#level-4-'.$v3['id'].'").hasClass("hide-child");';
											echo '			if(isHide){';
											echo '				$("#level-4-'.$v3['id'].'").removeClass("hide-child")';	
											echo '			}else{';
											echo '				$("#level-4-'.$v3['id'].'").addClass("hide-child")';		
											echo '			}';
											echo '		});';
											echo '		</script>';
											// Menu Level 4
											if(count($v3['parentLevel4'])>0){
													echo '		<ul class="hide-child" id="level-4-'.$v3['id'].'">';
												foreach ($v3['parentLevel4'] as $k4 => $v4){
													if(count($v4['parentLevel5'])>0){ $cssClass = 'icon-pointer'; }else{ $cssClass = ''; }
													echo '		<li class="level-4">';
													echo '			<em class="fa fa-angle-right ' . $cssClass . '" id="lnk-show-level-5-'.$v4['id'].'"></em>';
													if($v4['url']!='#'){
													echo '			<a href="' . $v4['url'] . '" target="' . $v4["target"] . '">' . $v4['name'] . '</a>';
																	}else{
													echo '			<span>' . $v4['name'] . '</span>';
																	}
													echo '		</li>';
													echo '		<script>';
													echo '		$("#lnk-show-level-5-'.$v4['id'].'").click(function(){';
													echo '			var isHide = $("#level-5-'.$v4['id'].'").hasClass("hide-child");';
													echo '			if(isHide){';
													echo '				$("#level-5-'.$v4['id'].'").removeClass("hide-child")';	
													echo '			}else{';
													echo '				$("#level-5-'.$v4['id'].'").addClass("hide-child")';		
													echo '			}';
													echo '		});';
													echo '		</script>';
													// Menu Level 5
													if(count($v4['parentLevel5'])>0){
														echo '		<ul class="hide-child" id="level-5-'.$v4['id'].'">';
														foreach ($v4['parentLevel5'] as $k5 => $v5){
															echo '		<li class="level-5">';
															echo '			<em class="fa fa-circle"></em>';
															if($v5['url']!='#'){
															echo '			<a href="' . $v5['url'] . '" target="' . $v5["target"] . '">' . $v5['name'] . '</a>';
																			}else{
															echo '			<span>' . $v5['name'] . '</span>';
																			}
															echo '		</li>';
														}
														echo '		</ul>';
													}
												}
												echo '		</ul>';
											}
										}
										echo '		</ul>';
									}
								}
								echo '		</ul>';
							}
				echo 	'</ul>';	
				echo '</li>';
			}
		}
		
		echo 	'</ul>';
		echo '	</div>';
	}

	function hex2rgba($color, $opacity = false) {
 
		$default = 'rgb(0,0,0)';
	 
		//Return default if no color provided
		if(empty($color))
			  return $default; 
	 
		//Sanitize $color if "#" is provided 
			if ($color[0] == '#' ) {
				$color = substr( $color, 1 );
			}
	 
			//Check if color has 6 or 3 characters and get values
			if (strlen($color) == 6) {
					$hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
			} elseif ( strlen( $color ) == 3 ) {
					$hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
			} else {
					return $default;
			}
	 
			//Convert hexadec to rgb
			$rgb =  array_map('hexdec', $hex);
	 
			//Check if opacity is set(rgba or rgb)
			if($opacity){
				if(abs($opacity) > 1)
					$opacity = 1.0;
				$output = 'rgba('.implode(",",$rgb).','.$opacity.')';
			} else {
				$output = 'rgb('.implode(",",$rgb).')';
			}
	 
			//Return rgb(a) color string
			return $output;
	}
}