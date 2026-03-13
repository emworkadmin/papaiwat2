<?php

class Zend_View_Helper_Widget_Calendar_Calendar extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $recursiveIds = array();

	public function Widget_Calendar_Calendar($params)
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
				"ID" => "tbl.ID",
				"Name" => "tbl.Name",
				"DataType" => "tbl.DataType",
				"WidgetType" => "tbl.WidgetType",
				"ContentSettings" => "tbl.ContentSettings",
				"DisplaySettings" => "tbl.DisplaySettings",
		))
		->where("tbl.WidgetType = 'Calendar' ")
		->where("tbl.IsEnable = 'Y' ")
		->where("tbl.IsDeleted ='N' ")
		->where("tbl.ID = ? ", $this->params['item'])
		;
		$stmtWI = $db->query($selectWI);
		$dataWI = $stmtWI->fetchObject();

		// ------------------------------------------------- Setting ------------------------------------------------ //
		$UID = uniqid();

		$ContentSettings = !empty($dataWI->ContentSettings) ? json_decode($dataWI->ContentSettings,true) : null;
		$DisplaySettings = !empty($dataWI->DisplaySettings) ? json_decode($dataWI->DisplaySettings,true) : null;

		// Content Setting ------------

		$Category = empty($ContentSettings['settingCategory']) ? null : $ContentSettings['settingCategory'];
		$UseCategoryParent = empty($ContentSettings['settingUseCategoryParent']) ? 'N' : $ContentSettings['settingUseCategoryParent'];
		$Sidx = empty($ContentSettings['settingSidx']) ? '' : $ContentSettings['settingSidx'];
		$Sord = empty($ContentSettings['settingSord']) ? 'ASC' : $ContentSettings['settingSord'];
		$Limit = empty($ContentSettings['settingLimit']) ? '' : $ContentSettings['settingLimit'];

		switch ($Sidx) {
			case 'name':
			case 'title':
				$Sidx = 'Title';
				break;
			case 'created':
				$Sidx = 'Created';
				break;
			case 'publishdate':
				$Sidx = 'PublishDate';
				break;
			case 'viewamount':
				$Sidx = 'ViewAmount';
				break;
			default:
				$Sidx = 'Title';
				break;
		}
		
		if(!empty($Category)){
			if($UseCategoryParent=='Y'){
				$categoryIds = $this->getRecursiveCalendarCategoryIds($Category);
				$categoryIds[] = $Category;
			}else{
				$categoryIds = array();
				$categoryIds[] = $Category;
			}
		}

		// Display Setting ------------

		$Template = empty($DisplaySettings['settingTemplate']) ? 'C' : $DisplaySettings['settingTemplate'];

		$defaultStart = null; // (date('Y-m-').'01');
		$defaultEnd = null; // (date('Y-m-t'));
		$StartDate = empty($DisplaySettings['settingStartDate']) ? $defaultStart : $DisplaySettings['settingStartDate'];
		$EndDate = empty($DisplaySettings['settingEndDate']) ? $defaultEnd : $DisplaySettings['settingEndDate'];

		$HtmlClass = empty($DisplaySettings['settingHtmlClass']) ? '' : $DisplaySettings['settingHtmlClass'];

		$CustomCSS = empty($DisplaySettings['settingCustomCSS']) ? '' : $DisplaySettings['settingCustomCSS'];

		// ---------------------------------------------- Present Data ---------------------------------------------- //

		echo '<script src="' . $this->view->baseUrl("/assets/calendar/plugins/fullcalendar-3.9.0/lib/moment.min.js") . '"></script>';
		echo '<script src="' . $this->view->baseUrl("/assets/calendar/plugins/fullcalendar-3.9.0/fullcalendar-th.min.js") . '"></script>';
		echo '<script src="' . $this->view->baseUrl("/assets/calendar/plugins/fullcalendar-3.9.0/locale-all.js") . '"></script>';
		echo '<link rel="stylesheet" type="text/css" href="' . $this->view->baseUrl("/assets/calendar/plugins/fullcalendar-3.9.0/fullcalendar.min.css") . '" />';
		echo '<link rel="stylesheet" type="text/css" href="' . $this->view->baseUrl("/assets/calendar/plugins/fullcalendar-3.9.0/fullcalendar.print.css") . '" media="print" />';

		echo '<link rel="stylesheet" type="text/css" href="/assets/wcalendar/css/styles.css">';

		echo '<style>'.$CustomCSS.'</style>';

		echo '<div class="widget-calendar '.$HtmlClass.'">';

		if($Template=="C"){

		echo '	<div class="calendarpage">';
		echo '		<div class="box-calendar">';
		echo '			<div class="inner">';
		echo '				<div id="calendar-'.$UID.'"></div>';
		echo '			</div>';
		echo '		</div>';
		echo '	</div>';
		echo '	<script>';
		echo '	$(document).ready(function() {';
		echo '		$("#calendar-'.$UID.'").fullCalendar({';
		echo '			header: {';
		echo '				left: "prev",';
		echo '				center: "title",';
		echo '				right: "next"';
		echo '			},';
		echo '			height: "auto",';
		echo '			locale: "th",';
		echo '			navLinks: true,';
		echo '			selectable: true,';
		echo '			displayEventTime: false,';
		echo '			navLinkDayClick: function(date, jsEvent) {';
		echo '			  window.location = "'.$this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'index', 'type' => 'date'), null, true).'\/filter\/"+date._i';
		echo '			}';
		echo '		});';
		echo '	});';
		echo '	</script>';

		}

		if($Template=="L"){

		echo '	<div class="calendarpage">';
		echo '		<div class="box-event box-event-'.$UID.'">';
		echo '			';
		echo '		</div>';
		echo '	</div>';
		echo '	<script>';
		echo '	$(document).ready(function() {';
		echo '		var start = "'.$StartDate.'";';
		echo '		var end = "'.$EndDate.'";';
		echo '		var categoryIds = ['.implode(",",$categoryIds).'];';
		echo '		var sidx = "'.$Sidx.'";';
		echo '		var sord = "'.$Sord.'";';
		echo '		var limit = "'.$Limit.'";';
		echo '		$.post("'.$this->view->url(array('module' => 'calendar', 'controller' => 'article', 'action' => 'getcalendar'), null, true).'", {"start": start, "end": end, "categoryIds": categoryIds, "sidx": sidx, "sord": sord, "limit": limit}, function(data){';
		echo '			if(data != null){';
		echo '				$(data).each(function() {';
		echo '					html = "";';
		echo '					html += "<div class=\"eventList eventList-" + $(this).attr("id") + "\">" + "\n";';
		echo '					html += "	<div class=\"divDate\" style=\"background: " + $(this).attr("color") + "\">" + "\n";';
		echo '					html += "		<div class=\"date1\">" + $(this).attr("date-d") + "</div>" + "\n";';
		echo '					html += "		<div class=\"date2\">" + $(this).attr("date-m") + " " + $(this).attr("date-y") + "</div>" + "\n";';
		echo '					html += "	</div>" + "\n";';
		echo '					html += "	<div class=\"divTitle\">" + "\n";';
		echo '					html += "		<div class=\"title1\">" + $(this).attr("title") + "</div>" + "\n";';
		echo '					html += "		<div class=\"title2\">'.Emwork_Translate::text($lang, 'wcalendar', 'place').': " + $(this).attr("place") + "</div>" + "\n";';
		echo '					html += "	</div>" + "\n";';
		echo '					html += "	<script>" + "\n";';
		echo '					html += "	$(document).ready(function() {" + "\n";';
		echo '					html += "		$(\".eventList-" + $(this).attr("id") + "\").click(function() {" + "\n";';
		echo '					html += "			window.location = \"'.$this->view->url(array("module" => "calendar", "controller" => "article", "action" => "detail"), null, true).'/id/" + $(this).attr("id") + "/iid/" + $(this).attr("id") + "\"" + "\n";';
		echo '					html += "		});" + "\n";';
		echo '					html += "	});" + "\n";';
		echo '					html += "	<\/script>" + "\n";';
		echo '					html += "</div>" + "\n";';
		echo '					$(".box-event-'.$UID.'").append(html);';
		echo '				});';
		echo '			}';
		echo '		}, "json");';
		echo '	});';
		echo '	</script>';

		}

		echo '</div>';

	}

	public function getRecursiveCalendarCategoryIds($id = null) {

		$db = Zend_Db_Table::getDefaultAdapter();
		
		$select = $db->select()
		->from ( array ('tbs' => 'calendar_category_info' ), array ('ID') )
		->where ( "tbs.IsDeleted = ?", "N" )
		->where ( "tbs.IsEnable = ?", "Y" )
		->where ( "tbs.ParentID = ?", $id )
		->order ( "tbs.Ordering ASC" )
		;
		$stmt = $db->query ( $select );
		$data = $stmt->fetchAll ();

		foreach ( $data as $value ) {
			if (empty($id) || ($id != $value["ID"])) {
				$this->getRecursiveCalendarCategoryIds($value ["ID"]);
				if(!in_array($value["ID"],$this->recursiveIds)){ 
					$this->recursiveIds[] = $value["ID"]; 
				}
			}
		}
		return $this->recursiveIds;
	}

}