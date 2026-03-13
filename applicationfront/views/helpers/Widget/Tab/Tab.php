<?php

class Zend_View_Helper_Widget_Tab_Tab extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Tab_Tab($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');

		// --------------------------------------------- Get Widget Item -------------------------------------------- //

		$selectWI = $db->select()
		->from(array('tbl' => 'widget_item'), '')
		->columns(array(
				'ID' => 'tbl.ID',
				'Name' => 'tbl.Name',
				'DataType' => 'tbl.DataType',
				'WidgetType' => 'tbl.WidgetType',
				'ContentSettings' => 'tbl.ContentSettings',
				'DisplaySettings' => 'tbl.DisplaySettings',
		))
		->where('tbl.WidgetType = "tab" ')
		->where('tbl.IsEnable = "Y" ')
		->where('tbl.IsDeleted = "N" ')
		->where('tbl.ID = ? ', $this->params['item'])
		;
		$stmtWI = $db->query($selectWI);
		$dataWI = $stmtWI->fetchObject();

		// ------------------------------------------------- Setting ------------------------------------------------ //

		$ContentSettings = !empty($dataWI->ContentSettings) ? json_decode($dataWI->ContentSettings,true) : null;
		$DisplaySettings = !empty($dataWI->DisplaySettings) ? json_decode($dataWI->DisplaySettings,true) : null;

		$settingAnimation = empty($DisplaySettings['settingAnimation']) ? '' : $DisplaySettings['settingAnimation'];
		$settingShowImage = $DisplaySettings['settingShowImage'] == 'Y' ? true : false;
		$settingImageAlign = empty($DisplaySettings['settingImageAlign']) ? 'left' : $DisplaySettings['settingImageAlign'];
		$settingShowTitle = $DisplaySettings['settingShowTitle'] == 'Y' ? true : false;
		$settingTitleAlign = empty($DisplaySettings['settingTitleAlign']) ? 'left' : $DisplaySettings['settingTitleAlign'];
		$settingShowIntroText = $DisplaySettings['settingShowIntroText'] == 'Y' ? true : false;
		$settingIntroTextAlign = empty($DisplaySettings['settingIntroTextAlign']) ? 'left' : $DisplaySettings['settingIntroTextAlign'];
		$settingShowLink = $DisplaySettings['settingShowLink'] == 'Y' ? true : false;
		$settingLinkTitle = empty($DisplaySettings['settingLinkTitle']) ? 'Read More' : $DisplaySettings['settingLinkTitle'];
		$settingLinkAlign = empty($DisplaySettings['settingLinkAlign']) ? 'left' : $DisplaySettings['settingLinkAlign'];
		$settingLinkTarget = empty($DisplaySettings['settingLinkTarget']) ? '_blank' : $DisplaySettings['settingLinkTarget'];
		$settingHtmlClass = empty($DisplaySettings['settingHtmlClass']) ? '' : $DisplaySettings['settingHtmlClass'];
		$settingCustomCSS = empty($DisplaySettings['settingCustomCSS']) ? '' : $DisplaySettings['settingCustomCSS'];
		
		echo "<link rel='stylesheet' type='text/css' href='/assets/wtab/css/responsive-bootstrap-tabs.css' />";
		echo "<link rel='stylesheet' type='text/css' href='/assets/wtab/css/animate.css' />";
		echo "<link rel='stylesheet' type='text/css' href='/assets/wtab/css/style.css' />";

		if($settingTitleAlign){
			echo "<style>.wg-tab-title { text-align: ".$settingTitleAlign."; }</style>";
		}
		if($settingIntroTextAlign){
			echo "<style>.wg-tab-content { text-align: ".$settingIntroTextAlign."; }</style>";
		}
		if($settingLinkAlign){
			echo "<style>.wg-tab-link { text-align: ".$settingLinkAlign."; }</style>";
		}
		if($settingCustomCSS){
			echo "<style>".$settingCustomCSS."</style>";
		}
				
		echo "<div class='responsive-bootstrap-tabs " . $settingHtmlClass . "' role='tabpanel' data-example-id='togglable-tabs'>";
		echo "	<ul class='nav nav-tabs nav-tabs-responsive' role='tablist'>";

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randstring = '';
		for ($j = 0; $j < 10; $j++) {
			$randstring .= $characters[rand(0, strlen($characters))];
		}


		$keyList = array();
		if(count($ContentSettings)>0){
			foreach($ContentSettings as $k=>$v){
				$key = $randstring;
				$key .= '-'.$k;
				$keyList[] = $key;

		echo "		<li role='presentation' class='next'>";
		echo "			<a href='#" . $key . "' id='" . $key . "-tab' role='tab' data-toggle='tab' aria-controls='" . $key . "' >";
		echo "				<span class='text'>" . $v['Title'] . "</span>";
		echo "			</a>";
		echo "		</li>";

			}
		}

		echo "	</ul>";
		echo "	<div class='tab-content'>";

		if(count($ContentSettings)>0){
			foreach($ContentSettings as $k=>$v){
				$key = $randstring;
				$key .= '-'.$k;
				$imageUrl = $v['ImageUrl'];

		echo "		<div role='tabpanel' class='tab-pane fade' id='" . $key . "' aria-labelledby='" . $key . "-tab'>";

				if($settingImageAlign=='left'){

		echo "			<div class='template1 row'>";

						if($settingShowImage && !$settingShowTitle && !$settingShowIntroText && !$settingShowLink){
		echo "				<div class='col-md-12'>";
		echo "					<img alt=\"image\" class='wg-tab-image' src='" . $imageUrl . "'>";
		echo "				</div>";
						}

						if($settingShowImage && ($settingShowTitle || $settingShowIntroText || $settingShowLink)){
		echo "					<div class='col-md-6'>";
		echo "						<img alt=\"image\" class='wg-collapse-image' src='" . $imageUrl . "'>";
		echo "					</div>";
						}

						if($settingShowImage && ($settingShowTitle || $settingShowIntroText || $settingShowLink)){
		echo "				<div class='col-md-6'>";

							if($settingShowTitle){
		echo "					<div class='wg-tab-title'>";
		echo "						" . $v['Title'] . "";
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='text'){
		echo "					<div class='wg-tab-content'>";
		echo "						" . nl2br($v['IntroText']) . "";
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widget'){
		echo "					<div class='wg-tab-content'>";
									if(!empty($v['Widget'])){
										$widgetDataObj = Page_Model_Widget::getDetailByLaguage($v['Widget'], $lang);
										$widgetPrefix = "Widget";
										$widgetType = ucfirst($widgetDataObj->Widget);
										$widgetParams = json_decode($widgetDataObj->Params, true);
										$widgetClass = "{$widgetPrefix}_{$widgetType}_{$widgetType}";
										$widgetObj = $this->view->$widgetClass($widgetParams);
										if ($widgetObj != null && method_exists($widgetObj, "display")) {
											$widgetObj->display();
										}
									}
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widgettool'){
		echo "					<div class='wg-tab-content'>";
									if(!empty($v['WidgetTool'])){

										$selectWT = $db->select()
										->from(array('tbl' => 'widget_item'), array('ID', 'WidgetType'))
										->where('tbl.IsEnable = "Y" ')
										->where('tbl.IsDeleted = "N" ')
										->where('tbl.ID = ? ', $v['WidgetTool'])
										;
										$stmtWT = $db->query($selectWT);
										$dataWT = $stmtWT->fetchObject();

										if($dataWT->ID){
											switch($dataWT->WidgetType){
												case 'grid':
													$widgetType = 'Grid';
													break;
												case 'slideshow':
													$widgetType = 'Slideshow';
													break;
												case 'slideset':
													$widgetType = 'Slideset';
													break;
												case 'tab':
													$widgetType = 'Tab';
													break;
												case 'collapse':
													$widgetType = 'Collapse';
													break;
											}
											$widgetParams = array('item'=>$dataWT->ID);

											$widgetPrefix = "Widget";
											$widgetClass = "{$widgetPrefix}_{$widgetType}_{$widgetType}";
											$widgetObj = $this->view->$widgetClass($widgetParams);
											if ($widgetObj != null && method_exists($widgetObj, "display")) {
												$widgetObj->display();
											}
										}
									}
									
		echo "					</div>";
							}

							if($settingShowLink){
		echo "					<div class='wg-tab-link'>";
		echo "						<a class='btn btn-primary' href='" . $v['Url'] . "' target='" . $settingLinkTarget . "'>" . $settingLinkTitle . "</a>";
		echo "					</div>";
							}

		echo "				</div>";
						}

						if(!$settingShowImage && ($settingShowTitle || $settingShowIntroText || $settingShowLink)){
		echo "				<div class='col-md-12'>";

							if($settingShowTitle){
		echo "					<div class='wg-tab-title'>";
		echo "						" . $v['Title'] . "";
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='text'){
		echo "					<div class='wg-tab-content'>";
		echo "						" . nl2br($v['IntroText']) . "";
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widget'){
		echo "					<div class='wg-tab-content'>";
									if(!empty($v['Widget'])){
										$widgetDataObj = Page_Model_Widget::getDetailByLaguage($v['Widget'], $lang);
										$widgetPrefix = "Widget";
										$widgetType = ucfirst($widgetDataObj->Widget);
										$widgetParams = json_decode($widgetDataObj->Params, true);
										$widgetClass = "{$widgetPrefix}_{$widgetType}_{$widgetType}";
										$widgetObj = $this->view->$widgetClass($widgetParams);
										if ($widgetObj != null && method_exists($widgetObj, "display")) {
											$widgetObj->display();
										}
									}
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widgettool'){
		echo "					<div class='wg-tab-content'>";
									if(!empty($v['WidgetTool'])){

										$selectWT = $db->select()
										->from(array('tbl' => 'widget_item'), array('ID', 'WidgetType'))
										->where('tbl.IsEnable = "Y" ')
										->where('tbl.IsDeleted = "N" ')
										->where('tbl.ID = ? ', $v['WidgetTool'])
										;
										$stmtWT = $db->query($selectWT);
										$dataWT = $stmtWT->fetchObject();

										if($dataWT->ID){
											switch($dataWT->WidgetType){
												case 'grid':
													$widgetType = 'Grid';
													break;
												case 'slideshow':
													$widgetType = 'Slideshow';
													break;
												case 'slideset':
													$widgetType = 'Slideset';
													break;
												case 'tab':
													$widgetType = 'Tab';
													break;
												case 'collapse':
													$widgetType = 'Collapse';
													break;
											}
											$widgetParams = array('item'=>$dataWT->ID);

											$widgetPrefix = "Widget";
											$widgetClass = "{$widgetPrefix}_{$widgetType}_{$widgetType}";
											$widgetObj = $this->view->$widgetClass($widgetParams);
											if ($widgetObj != null && method_exists($widgetObj, "display")) {
												$widgetObj->display();
											}
										}
									}
									
		echo "					</div>";
							}

							if($settingShowLink){
		echo "					<div class='wg-tab-link'>";
		echo "						<a class='btn btn-primary' href='" . $v['Url'] . "' target='" . $settingLinkTarget . "'>" . $settingLinkTitle . "</a>";
		echo "					</div>";
							}

		echo "				</div>";
						}

		echo "			</div>";
					}

					if($settingImageAlign=='top'){
		echo "			<div class='template2 row'>";
		echo "				<div class='col-md-12'>";

							if($settingShowImage){
		echo "					<img alt=\"image\" class='wg-tab-image' src='" . $imageUrl . "'>";
							}

							if($settingShowTitle){
		echo "					<div class='wg-tab-title'>";
		echo "						" . $v['Title'] . "";
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='text'){
		echo "					<div class='wg-tab-content'>";
		echo "						" . nl2br($v['IntroText']) . "";
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widget'){
		echo "					<div class='wg-tab-content'>";
									if(!empty($v['Widget'])){
										$widgetDataObj = Page_Model_Widget::getDetailByLaguage($v['Widget'], $lang);
										$widgetPrefix = "Widget";
										$widgetType = ucfirst($widgetDataObj->Widget);
										$widgetParams = json_decode($widgetDataObj->Params, true);
										$widgetClass = "{$widgetPrefix}_{$widgetType}_{$widgetType}";
										$widgetObj = $this->view->$widgetClass($widgetParams);
										if ($widgetObj != null && method_exists($widgetObj, "display")) {
											$widgetObj->display();
										}
									}
		echo "					</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widgettool'){
		echo "					<div class='wg-tab-content'>";
									if(!empty($v['WidgetTool'])){

										$selectWT = $db->select()
										->from(array('tbl' => 'widget_item'), array('ID', 'WidgetType'))
										->where('tbl.IsEnable = "Y" ')
										->where('tbl.IsDeleted = "N" ')
										->where('tbl.ID = ? ', $v['WidgetTool'])
										;
										$stmtWT = $db->query($selectWT);
										$dataWT = $stmtWT->fetchObject();

										if($dataWT->ID){
											switch($dataWT->WidgetType){
												case 'grid':
													$widgetType = 'Grid';
													break;
												case 'slideshow':
													$widgetType = 'Slideshow';
													break;
												case 'slideset':
													$widgetType = 'Slideset';
													break;
												case 'tab':
													$widgetType = 'Tab';
													break;
												case 'collapse':
													$widgetType = 'Collapse';
													break;
											}
											$widgetParams = array('item'=>$dataWT->ID);

											$widgetPrefix = "Widget";
											$widgetClass = "{$widgetPrefix}_{$widgetType}_{$widgetType}";
											$widgetObj = $this->view->$widgetClass($widgetParams);
											if ($widgetObj != null && method_exists($widgetObj, "display")) {
												$widgetObj->display();
											}
										}
									}
									
		echo "					</div>";
							}
							
							if($settingShowLink){
		echo "					<div class='wg-tab-link'>";
		echo "						<a class='btn btn-primary' href='" . $v['Url'] . "' target='" . $settingLinkTarget . "'>" . $settingLinkTitle . "</a>";
		echo "					</div>";
							}
							
		echo "				</div>";
		echo "			</div>";
					}

		echo "		</div>";

				}
			}

		echo "	</div>";
		echo "</div>";

		echo "<script>";
		echo "	'use strict';";

			if($settingAnimation){
		echo "	function tabAnimated(tab){";
		echo "		$(tab).addClass('animated " . $settingAnimation . "');";
		echo "	}";
			}

		echo "	$(document).on('show.bs.tab', '.nav-tabs-responsive [data-toggle=\"tab\"]', function(e) {";

		echo "		var \$target = $(e.target);";
		echo "		var \$tabs = \$target.closest('.nav-tabs-responsive');";
		echo "		var \$current = \$target.closest('li');";
		echo "		var \$parent = \$current.closest('li.dropdown');";
		echo "			\$current = \$parent.length > 0 ? \$parent : \$current;";
		echo "		var \$next = \$current.next();";
		echo "		var \$prev = \$current.prev();";

		echo "		var updateDropdownMenu = function(\$el, position){";
		echo "			\$el";
		echo "			.find('.dropdown-menu')";
		echo "			.removeClass('pull-xs-left pull-xs-center pull-xs-right')";
		echo "			.addClass( 'pull-xs-' + position );";
		echo "		};";

		echo "		\$tabs.find('>li').removeClass('next prev');";
		echo "		\$prev.addClass('prev');";
		echo "		\$next.addClass('next');";
				
		echo "		updateDropdownMenu( \$prev, 'left' );";
		echo "		updateDropdownMenu( \$current, 'center' );";
		echo "		updateDropdownMenu( \$next, 'right' );";

				if($settingAnimation){
		echo "		tabAnimated($(this).attr('href'));";
				}

		echo "	});";

			if(count($keyList)>0){
		echo "	$('#" . $keyList[0].'-tab' . "').click();";
			}

		echo "</script>";
	
	}
	
}