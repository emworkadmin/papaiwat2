<?php

class Zend_View_Helper_Widget_Storysearch_Storysearch extends Zend_View_Helper_Abstract
{
	protected $params = array();
	public function Widget_Storysearch_Storysearch ($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function display() {
		$db = Zend_Db_Table::getDefaultAdapter();
		$lang = Zend_Registry::get('lang');
		
		$searchByText = System_Model_Setting::getValue("SearchByText", "Story");
		$searchCategory1 = System_Model_Setting::getValue("SearchCategory1", "Story");
		$searchCategory2 = System_Model_Setting::getValue("SearchCategory2", "Story");
		$searchCategory3 = System_Model_Setting::getValue("SearchCategory3", "Story");
		
		$catAmount = 0;
		$catAmount += empty($searchCategory1) ? 0 : 1;
		$catAmount += empty($searchCategory2) ? 0 : 1;
		$catAmount += empty($searchCategory3) ? 0 : 1;
		
		$colText = 0;
		$colCat = 0;
		$colBtn = 12;
		
		if ($catAmount == 0) { // บังคับแสดง TextSearch
			$colText = 10;
			$colBtn = 2;
		} else {
			if ($searchByText == 'Y') {
				if ($catAmount == 3) {
					$colText = 4;
					$colCat = 2;
					$colBtn = 2;
				} elseif ($catAmount == 2) {
					$colText = 4;
					$colCat = 3;
					$colBtn = 2;					
				} else {
					$colText = 7;
					$colCat = 3;
					$colBtn = 2;					
				}
			} else {
				if ($catAmount == 3) {
					$colCat = 3;
					$colBtn = 3;
				} elseif ($catAmount == 2) {
					$colCat = 5;
					$colBtn = 2;
				} else {
					$colCat = 9;
					$colBtn = 3;
				}
			}
		}
		
		$divID = "story-search-panel-" . uniqid();

		$html = '';
		$html .= '<div id="' . $divID . '" class="story-search-panel">' . "\n";
		$html .= '	<form class="form-horizontal" method="get" action="' . $this->view->url(array("module" => "story", "controller" => "search", "action" => "index"), null, true) . '">' . "\n";
		$html .= '		<div class="form-group">' . "\n";
		
		if ($colText > 0) {
			$html .= '			<div class="col-md-' . $colText . ' search-text">' . "\n";
			$html .= '				' . $this->view->formText("q", null, array("class" => "form-control", "placeholder" => Emwork_Translate::text($lang, "story", "search_text_placeholder"))) . '' . "\n";
			$html .= '			</div>' . "\n";
		}
		
		if ($colCat > 0 && !empty($searchCategory1)) {
			$catOption = $this->getOption($searchCategory1, $lang);
			$html .= '			<div class="col-md-' . $colCat . ' search-select">' . "\n";
			$html .= '				' . $this->view->formSelect("cat1", null, array("class" => "form-control"), Emwork_Utility::addEmptyOption($catOption)) .'' . "\n";
			$html .= '			</div>' . "\n";
		}
		
		if ($colCat > 0 && !empty($searchCategory2)) {
			$catOption = $this->getOption($searchCategory2, $lang);
			$html .= '			<div class="col-md-' . $colCat . ' search-select">' . "\n";
			$html .= '				' . $this->view->formSelect("cat2", null, array("class" => "form-control"), Emwork_Utility::addEmptyOption($catOption)) .'' . "\n";
			$html .= '			</div>' . "\n";
		}
		
		if ($colCat > 0 && !empty($searchCategory3)) {
			$catOption = $this->getOption($searchCategory3, $lang);
			$html .= '			<div class="col-md-' . $colCat . ' search-select">' . "\n";
			$html .= '				' . $this->view->formSelect("cat3", null, array("class" => "form-control"), Emwork_Utility::addEmptyOption($catOption)) .'' . "\n";
			$html .= '			</div>' . "\n";
		}
		
		$html .= '			<div class="col-md-' . $colBtn . ' search-button">' . "\n";
		$html .= '				<button type="submit" class="btn btn-block" id="btnSearch"><em class="fa fa-search"></em> ' . Emwork_Translate::text($lang, "general", "button_search") .'</button>' . "\n";
		$html .= '			</div>' . "\n";
		$html .= '		</div>' . "\n";
		$html .= '	</form>' . "\n";
		$html .= '</div>' . "\n";
				
		$script = '';
		$script .= '<link rel="stylesheet" href="'. $this->view->baseUrl('assets/story/css/story.css') .'">' . "\n";
		$script .= '<link rel="stylesheet" href="'. $this->view->baseUrl('plugins/select2/select2.min.css') .'">' . "\n";
		$script .= '<script src="' . $this->view->baseUrl('plugins/select2/select2.min.js') . '"></script>' . "\n";
		$script .= '<script>' . "\n";
		$script .= '	jQuery(function(){' . "\n";
		$script .= '		$("#' . $divID . '.story-search-panel select").select2();' . "\n";
		$script .= '	});' . "\n";
		$script .= '</script>' . "\n";
		
		echo $html;
		echo $script;
	}
	
	private function getOption($parantID, $lang) {
		
		$result = array ();
		if (empty($parantID)) {
			return $result;
		}
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$select = $db->select()
		->from ( array ('tbs' => 'story_category_info' ), array ('ID') )
		->joinInner ( array ('d' => 'story_category_detail'), "tbs.ID = d.ID and d.Lang = '{$lang}' ", array ('Title') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ?",  $parantID )
		->order ( "tbs.Ordering ASC" )
		->order ( "convert(d.Title USING TIS620)  ASC" );
		$stmt = $db->query ( $select );
		$data = $stmt->fetchAll ();
		
		foreach ( $data as $value ) {
			$result [$value ["ID"]] = $value ["Title"];
		}
		return $result;
	}
}