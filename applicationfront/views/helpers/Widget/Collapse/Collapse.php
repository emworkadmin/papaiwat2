<?php

class Zend_View_Helper_Widget_Collapse_Collapse extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Collapse_Collapse($params)
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
		->where('tbl.WidgetType = "collapse" ')
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
		$settingAllowMultipleOpened = $DisplaySettings['settingAllowMultipleOpened'] == 'Y' ? true : false;
		$settingShowFirstItem = $DisplaySettings['settingShowFirstItem'] == 'Y' ? true : false;
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
		
		echo "<link rel='stylesheet' type='text/css' href='/assets/wcollapse/css/animate.css' />";
		echo "<link rel='stylesheet' type='text/css' href='/assets/wcollapse/css/style.css' />";

		if($settingTitleAlign){
			echo "<style>.wg-collapse-title { text-align: ".$settingTitleAlign."; }</style>";
		}
		if($settingIntroTextAlign){
			echo "<style>.wg-collapse-content { text-align: ".$settingIntroTextAlign."; }</style>";
		}
		if($settingLinkAlign){
			echo "<style>.wg-collapse-link { text-align: ".$settingLinkAlign."; }</style>";
		}
		if($settingCustomCSS){
			echo "<style>".$settingCustomCSS."</style>";
		}

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$randstring = '';
		for ($j = 0; $j < 10; $j++) {
			$randstring .= $characters[rand(0, strlen($characters))];
		}

		echo "<div class='panel-group' id='".$randstring."'>";

			if(count($ContentSettings)>0){
				$index=1;
				foreach($ContentSettings as $k=>$v){
					$key = $randstring;
					$key .= '-'.$k;
					$imageUrl = $v['ImageUrl'];

		echo "	<div class='panel panel-default'>";
		echo "		<div class='panel-heading'>";

                    $settingAllowMultipleOpenedStr = '';
					if(!$settingAllowMultipleOpened){
						$settingAllowMultipleOpenedStr = "data-parent='#".$randstring."'"; 
					}
		echo "			<h4 class='panel-title wg-collapse-header-title' data-toggle='collapse' ".$settingAllowMultipleOpenedStr." href='#".$randstring.'-'.$k."'>";
		echo "				".$v['Title']."";
		echo "			</h4>";
		echo "		</div>";

                    $settingShowFirstItemStr = '';
					if($settingShowFirstItem && $index=='1'){
						$settingShowFirstItemStr = 'in'; 
					}
		echo "		<div id='".$randstring.'-'.$k."' class='panel-collapse collapse ".$settingShowFirstItemStr."'>";
		echo "			<div class='panel-body'>";


				if($settingImageAlign=='left'){

        echo "				<div class='template1 row'>";
        
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
		echo "					<div class='col-md-6'>";

							if($settingShowTitle){
		echo "						<div class='wg-collapse-title'>";
		echo "							" . $v['Title'] . "";
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='text'){
		echo "						<div class='wg-collapse-content'>";
		echo "							" . nl2br($v['IntroText']) . "";
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widget'){
		echo "						<div class='wg-collapse-content'>";
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
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widgettool'){
		echo "						<div class='wg-collapse-content'>";
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
									
		echo "						</div>";
							}

							if($settingShowLink){
		echo "						<div class='wg-collapse-link'>";
		echo "							<a class='btn btn-primary' href='" . $v['Url'] . "' target='" . $settingLinkTarget . "'>" . $settingLinkTitle . "</a>";
		echo "						</div>";
							}

		echo "					</div>";
						}

						if(!$settingShowImage && ($settingShowTitle || $settingShowIntroText || $settingShowLink)){
		echo "					<div class='col-md-12'>";

							if($settingShowTitle){
		echo "						<div class='wg-collapse-title'>";
		echo "							" . $v['Title'] . "";
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='text'){
		echo "						<div class='wg-collapse-content'>";
		echo "							" . nl2br($v['IntroText']) . "";
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widget'){
		echo "						<div class='wg-collapse-content'>";
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
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widgettool'){
		echo "						<div class='wg-collapse-content'>";
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
									
		echo "						</div>";
							}

							if($settingShowLink){
		echo "						<div class='wg-collapse-link'>";
		echo "							<a class='btn btn-primary' href='" . $v['Url'] . "' target='" . $settingLinkTarget . "'>" . $settingLinkTitle . "</a>";
		echo "						</div>";
							}

		echo "					</div>";
						}

		echo "				</div>";
					}

					if($settingImageAlign=='top'){
		echo "				<div class='template2 row'>";
		echo "					<div class='col-md-12'>";

							if($settingShowImage){
		echo "						<img alt=\"image\" class='wg-collapse-image' src='" . $imageUrl . "'>";
							}

							if($settingShowTitle){
		echo "						<div class='wg-collapse-title'>";
		echo "							" . $v['Title'] . "";
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='text'){
		echo "						<div class='wg-collapse-content'>";
		echo "							" . nl2br($v['IntroText']) . "";
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widget'){
		echo "						<div class='wg-collapse-content'>";
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
		echo "						</div>";
							}

							if($settingShowIntroText && $v['DataType']=='widgettool'){
		echo "						<div class='wg-collapse-content'>";
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
									
		echo "						</div>";
							}
							
							if($settingShowLink){
		echo "						<div class='wg-collapse-link'>";
		echo "							<a class='btn btn-primary' href='" . $v['Url'] . "' target='" . $settingLinkTarget . "'>" . $settingLinkTitle . "</a>";
		echo "						</div>";
							}
							
		echo "					</div>";
		echo "				</div>";
					}

        echo "		</div>";
        echo "	</div>";
        echo "</div>";
        
				$index++;
				}
            }
            
		echo "</div>";

		if($settingAnimation){
		echo "<script>";
		echo "	$(function() {";
		echo "		$('.collapse').on('show.bs.collapse', function(e) {";
		echo "			$(e.target).children('.panel-body').children('.row').addClass('animated ".$settingAnimation."');";
		echo "		})";
		echo "	});";
		echo "</script>";
		}
	
	}
	
}