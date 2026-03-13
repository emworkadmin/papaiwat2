<?php
class Zend_View_Helper_Widget_Poll_Poll extends Zend_View_Helper_Abstract
{
	protected $params = array();
	protected $colorCode = array('#E63935', '#D81A62', '#3F4EA2', '#863B96', '#2899D5', '#0D8A7C', '#7CB342', '#FCD836', '#F68C1E', '#F05323');
	protected $lang = null;

	public function Widget_Poll_Poll($params)
	{
		$this->params = $params;

		if (Zend_Registry::isRegistered('lang')) {
			$this->lang = Zend_Registry::get('lang');
		} else {
			$defaultLanguage = System_Model_Setting::getDefaultLanguage();
			$this->lang = $defaultLanguage["Code"];
		}

		return $this;
    }

    public function display() {

		$db = Zend_Db_Table::getDefaultAdapter();
        $lang = Zend_Registry::get('lang');
        $UID = uniqid();

		// --------------------------------------------- Get Widget Item -------------------------------------------- //

		// Get Param
		$item = $this->params['item'];
		
		// Get Data
		$model = new Poll_Model_Item();
		$data = $this->getDataByLanguage($item, $lang);

		// Get Question List
		$dataQuestion = $this->getQuestionListByLanguage($data->ID, $lang);
		if(count($dataQuestion)>0){
			foreach($dataQuestion as $k=>$v){
				$dataOption = $this->getOptionListByLanguage($v['ID'], $lang);
				$dataQuestion[$k]['OptionList'] = $dataOption;
			}
		}

		$request = Zend_Controller_Front::getInstance()->getRequest();
		$request->getServer('REMOTE_ADDR');

		$RemoteIP = null;
		if($data->OneIPPerVote=='Y'){
			$RemoteIP = $request->getServer('REMOTE_ADDR');
		}

		// Get Last Submission
		$dataLastSubmission = $this->getLastSubmission($data->ID, $RemoteIP, $data->OneUserPerVote=='Y');

		// Setting
		$ShowIntroText = $data->ShowIntroText == 'Y';
		$ResultDisplay = $data->ResultDisplay;

		// Send Data to view
		$id = $data->ID;
		$pageTitle = empty($data->Title) ? '' : $data->Title;
		$canVote = $data->CanVote == 1;
		$startDate = $data->StartDate;
        
        $isReport = false;
		if($dataLastSubmission->ID && $data->OneUserPerVote=='Y'){
			$isReport = true;
		}
		if($dataLastSubmission->ID && $data->OneIPPerVote=='Y'){
			$isReport = true;
		}
		if(!empty($dataLastSubmission->ActionDiffHour) && !empty($data->VoteDelay)){
			if($dataLastSubmission->ActionDiffHour < $data->VoteDelay){
				$isReport = true;
			}
		}
		// Case หมดเวลา Vote
		if($data->EndDiff <= 0){
			$isReport = true;
		}

		// Get Data Report
		if($isReport){
			$dataSubmission = $this->getSubmissionList($data->ID);
			$dataQuestion = $this->getQuestionListByLanguage($data->ID, $lang);
			if(count($dataQuestion)>0){
				foreach($dataQuestion as $k=>$v){
					$dataOption = $this->getOptionListByLanguage($v['ID'], $lang);
					if(count($dataOption)>0){
						$optionAmount = count($dataOption);
						$index=0;
						foreach($dataOption as $k2=>$v2){
							if(count($dataSubmission)>0){
								foreach($dataSubmission as $k3=>$v3){
									if($v['ID']==$v3['PollQuestionID'] && $v2['ID']==$v3['PollOptionID']){
										$dataOption[$k2]['Amount'] = $v3['Amount'];
										$dataOption[$k2]['Percentage'] = $v3['Percentage'];
										if($index<=9){
											$dataOption[$k2]['Color'] = $this->colorCode[$index];
											$index++;	
										}else{
											$dataOption[$k2]['Color'] = '#'.$this->random_color();
										}
									}
								}
							}
						}
					}
					$dataQuestion[$k]['OptionList'] = $dataOption;
				}
			}
		}

        // ---------------------------------------------- Present Data ---------------------------------------------- //
		if(!$isReport){

            if($canVote){

                echo '<style>' . "\n";
                echo '	#widget-poll-'.$UID.' .poll-question { position: relative; padding: 10px 0px 10px 50px; font-weight: bold; background: #a6def5; border-radius: 5px; }' . "\n";
                echo '	#widget-poll-'.$UID.' .poll-question .question-index { position: absolute; left: 15px; }' . "\n";
                echo '	#widget-poll-'.$UID.' .poll-question .question-title { }' . "\n";
                echo '	#widget-poll-'.$UID.' .poll-question-desc { padding: 10px 0px 10px 50px; font-size: 18px; background: #f2f2f2; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; }' . "\n";
                echo '	#widget-poll-'.$UID.' .poll-option { padding: 10px 0px 10px 50px; }' . "\n";
                echo '	#widget-poll-'.$UID.' .error.help-block { padding: 10px 0px 10px 50px; }' . "\n";
                echo '	#widget-poll-'.$UID.' .button-panel-zone { margin-bottom: 30px; }' . "\n";
                echo '</style>' . "\n";
                echo '<style>'.$this->params['CustomCSS'].'</style>' . "\n";

                echo '<div class="info infopage ' . $this->params['HtmlClass'] . '" id="widget-poll-'.$UID.'">' . "\n";

                echo '	<div class="item-title title-panel">' . "\n";
                echo '		<span class="title">' . "\n";
                echo '			' . $pageTitle . "\n";
                echo '		</span>' . "\n";
                echo '		<hr />' . "\n";
                echo '	</div>' . "\n";

                echo '	<div class="result-list-panel">' . "\n";
                echo '		<div class="row">' . "\n";
                echo '			<form class="form-horizontal" id="formQuestion-'.$UID.'" name="formQuestion-'.$UID.'">' . "\n";
                echo '			<input type="hidden" name="hdfID" value="' . $id . '">' . "\n";
                        
                            if(count($dataQuestion)>0){
                                $index = 1;
                                foreach($dataQuestion as $k1=>$v1){

                echo '			<div class="col-md-12" data-id="' . $v1['ID'] . '">' . "\n";
                            
                echo '				<div class="poll-question col-md-12">' . "\n";
                echo '					<span class="question-index">' . $index . '. ' . '</span>' . "\n";
                echo '					<span class="question-title">' . $v1['Question'] . '</span>' . "\n";
                echo '				</div>' . "\n";

                                    if($ShowIntroText && $v1['Description']){
                echo '				<div class="poll-question-desc col-md-12">' . "\n";
                echo '					' . nl2br($v1['Description']) . "\n";
                echo '				</div>' . "\n";
                                    }

                echo '				<div class="divControl">' . "\n";
                                    if(count($v1['OptionList'])>0){
                                        foreach($v1['OptionList'] as $k2=>$v2){
                echo '						<div class="poll-option col-md-12">' . "\n";
                echo '							<input type="radio" name="Answers[' . $v1['ID'] . ']" id="Answers-' . $v1['ID'] . '-' . $v2['ID'] . '" value="' . $v2['ID'] . '" class="required"> &nbsp;&nbsp; ' . $v2['Name'] . "\n";
                echo '						</div>' . "\n";
                                        }
                                    }
                echo '				</div>' . "\n";

                echo '			</div>' . "\n";

                                $index++;
                                }
                            }

                echo '			</form>' . "\n";
                echo '		</div>' . "\n";
                echo '	</div>' . "\n";

                echo '	<div class="form-group text-center">' . "\n";
                echo '		<div class="col-md-12 button-panel-zone">' . "\n";
                echo '			<button type="button" class="btn btn-warning" id="btnSend-'.$UID.'"><em class="fa fa-paper-plane"></em> ' . Emwork_Translate::text($this->lang, 'wpoll', 'send_poll') . '</button>' . "\n";
                echo '			<button type="button" class="btn btn-primary" id="btnReport-'.$UID.'"><em class="fa fa-pie-chart"></em> ' . Emwork_Translate::text($this->lang, 'wpoll', 'view_score') . '</button>' . "\n";
                echo '		</div>' . "\n";
                echo '	</div>' . "\n";
                    
                echo '</div>' . "\n";

                echo '<script src="' . $this->view->baseUrl('plugins/jQueryValidation/jquery.validate.min.js') . '"></script>' . "\n";
                if($lang=='th'){
                echo '<script src="' . $this->view->baseUrl('plugins/jQueryValidation/localization/messages_th.min.js') . '"></script>' . "\n";
                }

                echo '<script>' . "\n";
                echo 'jQuery(function(){' . "\n";
                echo '	$("#formQuestion-'.$UID.'").validate({' . "\n";
                echo '		errorElement: "em",' . "\n";
                echo '		errorPlacement: function(error, element) {' . "\n";
                echo '			error.addClass( "help-block" );' . "\n";
                echo '			error.appendTo( element.parents("div.divControl"));' . "\n";
                echo '		},' . "\n";
                echo '		ignore: []' . "\n";
                echo '	});' . "\n";

                echo '	$("#formQuestion-'.$UID.' input[type=radio]").iCheck({checkboxClass: "icheckbox_minimal-blue",radioClass: "iradio_minimal-blue"});' . "\n";

                echo '	$("#btnSend-'.$UID.'").click(function(){' . "\n";
                echo '		buttonZoneDisabled_'.$UID.'();' . "\n";
                echo '		var valid = $("#formQuestion-'.$UID.'").valid();' . "\n";
                echo '		if (valid) {' . "\n";
                echo '			$.post("' . $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'save'), null, true) . '", $("#formQuestion-'.$UID.'").serializeArray(), function(data){' . "\n";
                echo '				if (data.result) {' . "\n";
                echo '					DialogComplete();' . "\n";
                echo '					setTimeout(function(){' . "\n";
                echo '						window.location = "' . $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'finish', 'id' => $id), null, true) . '";' . "\n";
                echo '					}, 1000);' . "\n";
                echo '				} else {' . "\n";
                echo '					DialogWarning(data.message,"'.Emwork_Translate::text($this->lang, 'general', 'dialog_warning_title').'","'.Emwork_Translate::text($this->lang, 'general', 'dialog_warning_close').'");' . "\n";
                echo '				}' . "\n";
                echo '				buttonZoneActive_'.$UID.'();' . "\n";
                echo '			}, "json");' . "\n";
                echo '		} else {' . "\n";
                echo '			buttonZoneActive_'.$UID.'();' . "\n";
                echo '		}' . "\n";
                echo '	});' . "\n";

                echo '	$("#btnReport-'.$UID.'").click(function(){' . "\n";
                echo '		window.location = "' . $this->view->url(array('module' => 'poll', 'controller' => 'item', 'action' => 'report', 'id' => $id), null, true) . '";' . "\n";
                echo '	});' . "\n";

                echo '	function buttonZoneDisabled_'.$UID.'() {' . "\n";
                echo '		$(".button-panel-zone button").attr("disabled", "disabled");' . "\n";
                echo '		$(".button-panel-zone").append(\'<em class="fa fa-refresh fa-spin"></em>\');' . "\n";
                echo '	}' . "\n";
                echo '	function buttonZoneActive_'.$UID.'() {' . "\n";
                echo '		$(".button-panel-zone button").removeAttr("disabled");' . "\n";
                echo '		$(".button-panel-zone > em").remove();' . "\n";
                echo '	}' . "\n";
                echo '});' . "\n";
                echo '</script>' . "\n";

            }else{

                echo '<style>' . "\n";
                echo ' #widget-poll-'.$UID.' .warning-poll { padding: 0px; text-align: center; }' . "\n";
                echo ' #widget-poll-'.$UID.' .warning-poll h1 { font-family: "ThaiSans Neue"; font-weight: bold; }' . "\n";
                echo ' #widget-poll-'.$UID.' .warning-poll h3 { font-family: "ThaiSans Neue"; font-weight: bold; }' . "\n";
                echo ' #widget-poll-'.$UID.' .button-panel-zone { margin-bottom: 30px; }' . "\n";
                echo '</style>' . "\n";
                echo '<style>'.$this->params['CustomCSS'].'</style>' . "\n";

                echo '<div class="info infopage ' . $this->params['HtmlClass'] . '" id="widget-poll-'.$UID.'">' . "\n";
                echo '    <div class="item-title title-panel">' . "\n";
                echo '        <span class="title">' . "\n";
                echo '            ' . $pageTitle . '' . "\n";
                echo '        </span>' . "\n";
                echo '        <hr />' . "\n";
                echo '    </div>' . "\n";
                echo '    <div class="warning-poll">' . "\n";
                echo '        <h1><u>'.Emwork_Translate::text($this->lang, 'wpoll', 'warning_title').'</u> '.Emwork_Translate::text($this->lang, 'wpoll', 'warning_subtitle').'</h1>' . "\n";
                echo '        <h3>' . "\n";

				if($lang == 'th') {
					echo '            '.Emwork_Translate::text($lang,'wpoll','warning_detail_1').' ' . Emwork_Utility_DateTime::MSSqlToDateThai($startDate, 'D') . ' ' . "\n";
					echo '            '.Emwork_Translate::text($lang,'wpoll','warning_time').' ' . Emwork_Utility_DateTime::MSSqlToDateThai($startDate, 'H') . ' : ' . Emwork_Utility_DateTime::MSSqlToDateThai($startDate, 'M') . ' '.Emwork_Translate::text($lang,'wpoll','warning_time_end') . "\n";
				} else {
					echo '            '.Emwork_Translate::text($lang,'wpoll','warning_detail_1').' ' . Emwork_Utility_DateTime::MSSqlToDateEng($startDate, 'D') . ' ' . "\n";
					echo '            '.Emwork_Translate::text($lang,'wpoll','warning_time').' ' . Emwork_Utility_DateTime::MSSqlToDateEng($startDate, 'H') . ' : ' . Emwork_Utility_DateTime::MSSqlToDateEng($startDate, 'M') . ' '.Emwork_Translate::text($lang,'wpoll','warning_time_end') . "\n";
				}

                echo '        </h3>' . "\n";
                echo '        <h3>'.Emwork_Translate::text($this->lang, 'wpoll', 'warning_thankyou').'</h3>' . "\n";
                echo '    </div>' . "\n";
                echo '</div>' . "\n";

            }

		}else{

			echo '<link rel="stylesheet" type="text/css" href="/assets/poll/css/style.css">' . "\n";
			echo '<style>' . "\n";
            echo '  #widget-poll-'.$UID.' .poll-option { padding: 5px 0px; }' . "\n";
            echo '  #widget-poll-'.$UID.' .divPollPieChart { margin-top: 15px; padding: 0; }' . "\n";
			echo '</style>' . "\n";
			echo '<style>'.$this->params['CustomCSS'].'</style>' . "\n";

			if($ResultDisplay=='bar'){

				echo '<div class="info infopage ' . $this->params['HtmlClass'] . '" id="widget-poll-'.$UID.'">' . "\n";
				echo '	 <div class="item-title title-panel">' . "\n";
				echo '		<span class="title">' . "\n";
				echo '			' . $pageTitle . "\n";
				echo '		</span>' . "\n";
				echo '		<hr />' . "\n";
				echo '	</div>' . "\n";
				
				echo '	<div class="result-list-panel">' . "\n";
				echo '		<div class="row">' . "\n";
				echo '			<form class="form-horizontal" id="formQuestion-'.$UID.'" name="formQuestion-'.$UID.'">' . "\n";
				echo '			<input type="hidden" name="hdfID" value="' . $id . '">' . "\n";
						
							if(count($dataQuestion)>0){
								$index = 1;
								foreach($dataQuestion as $k1=>$v1){
				
				echo '			<div class="col-md-12" data-id="' . $v1['ID'] . '">' . "\n";
							
				echo '				<div class="poll-question col-md-12">' . "\n";
				echo '					<span class="question-index">' . $index . ". " . '</span>' . "\n";
				echo '					<span class="question-title">' . $v1['Question'] . '</span>' . "\n";
				echo '				</div>' . "\n";
				
									if($ShowIntroText && $v1['Description']){
				echo '				<div class="poll-question-desc col-md-12">' . "\n";
				echo '					' . nl2br($v1['Description']) . "\n";
				echo '				</div>' . "\n";
									}
				
				echo '				<div class="divControl">' . "\n";

									if(count($v1['OptionList'])>0){
										foreach($v1['OptionList'] as $k2=>$v2){

				echo '						<div class="poll-option col-md-12">' . "\n";
				echo '							<div class="col-md-6">' . "\n";
				echo '								<em class="fa fa-circle"></em> &nbsp;&nbsp; ' . $v2['Name'] . "\n";
				echo '							</div>' . "\n";
				echo '							<div class="col-md-6">' . "\n";
				echo '								<div class="rateBarTitle">' . "\n";
				echo '									<div class="meter blue bar">' . "\n";
				echo '										<span style="width : ' . (empty($v2['Percentage']) ? '0%' : $v2['Percentage']) . ';" title="' . (empty($v2['Percentage']) ? '0%' : $v2['Percentage']) . '"></span>' . "\n";
				echo '									</div>' . "\n";
				echo '									<div class="rate">' . "\n";
				echo '										<span>' . (empty($v2['Percentage']) ? '0%' : $v2['Percentage']) . '</span>' . "\n";
				echo '									</div>' . "\n";
				echo '								</div>' . "\n";
				echo '							</div>' . "\n";
				echo '						</div>' . "\n";

										}
									}

				echo '				</div>' . "\n";
				
				echo '			</div>' . "\n";
				
								$index++;
								}
							}
				
				echo '			</form>' . "\n";
				
				echo '		</div>' . "\n";
				echo '	</div>' . "\n";
				echo '</div>' . "\n";

			}else{

				echo '<script src="' . $this->view->baseUrl('assets/poll/js/chart.bundle.min.js') . '"></script>' . "\n";

				echo '<div class="info infopage ' . $this->params['HtmlClass'] . '" id="widget-poll-'.$UID.'">' . "\n";
				echo '	<div class="item-title title-panel">' . "\n";
				echo '		<span class="title">' . "\n";
				echo '			' . $pageTitle . '' . "\n";
				echo '		</span>' . "\n";
				echo '		<hr />' . "\n";
				echo '	</div>' . "\n";

				echo '	<div class="result-list-panel">' . "\n";
				echo '		<div class="row">' . "\n";
				echo '			<form class="form-horizontal" id="formQuestion-'.$UID.'" name="formQuestion-'.$UID.'">' . "\n";
				echo '			<input type="hidden" name="hdfID" value="' . $id . '">' . "\n";
						
							if(count($dataQuestion)>0){
								$index = 1;
								foreach($dataQuestion as $k1=>$v1){

				echo '			<div class="col-md-12" data-id="' . $v1['ID'] . '">' . "\n";
				echo '				<div class="poll-question col-md-12">' . "\n";
				echo '					<span class="question-index">' . $index . ". " . '</span>' . "\n";
				echo '					<span class="question-title">' . $v1['Question'] . '</span>' . "\n";
				echo '				</div>' . "\n";

								if($ShowIntroText && $v1['Description']){
				echo '				<div class="poll-question-desc col-md-12">' . "\n";
				echo '					' . nl2br($v1['Description']) . '' . "\n";
				echo '				</div>' . "\n";
								}

				echo '				<div class="col-md-6">' . "\n";

									if(count($v1['OptionList'])>0){
										foreach($v1['OptionList'] as $k2=>$v2){

				echo '						<div class="poll-option col-md-12">' . "\n";
				echo '							<div class="row">' . "\n";
				echo '								<div class="col-md-12">' . "\n";
				echo '									<em class="fa fa-circle"></em> &nbsp;&nbsp; ' . $v2['Name'] . '' . "\n";
				echo '								</div>' . "\n";
				echo '							</div>' . "\n";
				echo '						</div>' . "\n";

										}
									}

				echo '				</div>' . "\n";

				echo '				<div class="col-md-6 divPollPieChart">' . "\n";
				echo '					<canvas id="charts-' . $v1['ID'] . '"></canvas>' . "\n";
				echo '					<script>' . "\n";
				echo '						jQuery(function(){' . "\n";
				echo '							var data_' . str_replace("-","_",$v1['ID']) . ' = {' . "\n";
				echo '								labels: [' . "\n";

													if(count($v1['OptionList'])>0){
														$i = 1;
														$l = count($v1['OptionList']);
														foreach($v1['OptionList'] as $k2=>$v2){
															echo '"'.$v2['Name'].'"';
															if($i != $l){
																echo ',';
															}
															$i++;
														} 
													} 

				echo '								],' . "\n";
				echo '								datasets: [{' . "\n";
				echo '									data: [' . "\n";

														if(count($v1['OptionList'])>0){
															$i = 1;
															$l = count($v1['OptionList']);
															foreach($v1['OptionList'] as $k2=>$v2){
																echo empty($v2['Amount']) ? 0 : $v2['Amount'];
																if($i != $l){
																	echo ',';
																}
																$i++;
															} 
														} 

				echo '									],' . "\n";
				echo '									backgroundColor : [' . "\n";

														if(count($v1['OptionList'])>0){
															$i = 1;
															$l = count($v1['OptionList']);
															foreach($v1['OptionList'] as $k2=>$v2){
																echo '"'.$v2['Color'].'"';
																if($i != $l){
																	echo ',';
																}
																$i++;
															} 
														} 

				echo '									]' . "\n";
				echo '								}]' . "\n";
				echo '							};' . "\n";
				echo '							function chart_' . str_replace("-","_",$v1['ID']) . '() {' . "\n";
				echo '								var ctx = $("#charts-' . $v1['ID'] . '");' . "\n";
				echo '								var options = {' . "\n";
                echo '										responsive: true,' . "\n";
                echo '										legend: { display: false }' . "\n";
				echo '									};' . "\n";
				echo '								var chart = new Chart(ctx, {type : "pie", data: data_' . str_replace("-","_",$v1['ID']) . ', options: options});' . "\n";
				echo '							}' . "\n";
				echo '							chart_' . str_replace("-","_",$v1['ID']) . '();' . "\n";
				echo '						});' . "\n";
				echo '					</script>' . "\n";
				echo '				</div>' . "\n";

				echo '			</div>' . "\n";

								$index++;
								}
							}

				echo '			</form>' . "\n";
				echo '		</div>' . "\n";
				echo '	</div>' . "\n";
				echo '</div>' . "\n";
				
			}

		}
		
	}
	
	private function getDataByLanguage($id, $lang='th') {
		$db = Zend_Db_Table::getDefaultAdapter();
		$data = new stdClass();
		try {
			$select = $db->select()
			->from(array('tbl' => 'poll_item_info'), '')
			->joinLeft(array('d' => 'poll_item_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"PollCategoryID" => "tbl.PollCategoryID",
					"IntroImageID" => "tbl.IntroImageID",
					"OneUserPerVote" => "tbl.OneUserPerVote",
					"OneIPPerVote" => "tbl.OneIPPerVote",
					"VoteDelay" => "tbl.VoteDelay",
					"ShowIntroText" => "tbl.ShowIntroText",
					"ResultDisplay" => "tbl.ResultDisplay",
					"ResultPieSetting" => "tbl.ResultPieSetting",
					"ResultBarSetting" => "tbl.ResultBarSetting",
					"StartDate" => "tbl.StartDate",
					"EndDate" => "tbl.EndDate",
					"Created" => "tbl.Created",
					"CreatedBy" => "tbl.CreatedBy",
					"Modified" => "tbl.Modified",
					"ModifiedBy" => "tbl.ModifiedBy",
					"IsEnable" => "tbl.IsEnable",
					"IsDeleted" => "tbl.IsDeleted",

					"Lang" => "d.Lang",
					"Title" => "d.Title",
					"IntroText" => "d.IntroText",

					"CanVote"=>"(case when NOW() BETWEEN tbl.StartDate AND tbl.EndDate then 1 else 0 end)",
					"EndDiff"=>"(TIMESTAMPDIFF(MINUTE, NOW(), tbl.EndDate))"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.ID = ? " ,$id)
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
			
			if (!empty($data->ID)) {
				$data->StartDate = empty($data->StartDate) ? null : new DateTime($data->StartDate);
				$data->EndDate = empty($data->EndDate) ? null : new DateTime($data->EndDate);
				$data->Created = empty($data->Created) ? null : new DateTime($data->Created);
				$data->Modified = empty($data->Modified) ? null : new DateTime($data->Modified);
			}
		} catch (Exception $e) {
			
		}
		return $data;
	}

	private function getQuestionListByLanguage($PollItemID='-1', $lang='th') {
		$db = Zend_Db_Table::getDefaultAdapter();
		$data = new stdClass();
		try {
			$select = $db->select()
			->from(array('tbl' => 'poll_question_info'), '')
			->joinLeft(array('d' => 'poll_question_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"Question" => "d.Question",
					"Description" => "d.Description"
			))
			->where("d.Description IS NOT NULL ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollItemID = ? ", $PollItemID)
			->order("tbl.Ordering")
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}
	
	private function getOptionListByLanguage($PollQuestionID='-1', $lang='th') {
		$db = Zend_Db_Table::getDefaultAdapter();
		$data = new stdClass();
		try {
			$select = $db->select()
			->from(array('tbl' => 'poll_option_info'), '')
			->joinLeft(array('d' => 'poll_option_detail'),"tbl.ID = d.ID and d.Lang = '{$lang}' ", '')
			->columns(array(
					"ID" => "tbl.ID",
					"Name" => "d.Name"
			))
			->where("d.Name IS NOT NULL ")
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollQuestionID = ? ", $PollQuestionID)
			->order("tbl.Ordering")
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}

	private function getLastSubmission($PollItemID, $RemoteIP=null, $OneUserPerVote=false) {

		$authobj = Zend_Auth::getInstance();
		$auth = $authobj->getIdentity();
		$UserID = isset($auth->UserID) ? $auth->UserID : '';

		$db = Zend_Db_Table::getDefaultAdapter();

		$data = new stdClass();
		try {
			$select = $db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->columns(array(
				"tbl.ID",
				"tbl.PollItemID",
				"tbl.UserID",
				"tbl.ActionDate",
				"tbl.RemoteIP",
				"ActionDiffHour" => "HOUR(TIMEDIFF(tbl.ActionDate,NOW()))"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollItemID = ? ", $PollItemID)
			->order("tbl.Created DESC")
			;
			if($RemoteIP && !$OneUserPerVote){
				$select = $select->where('tbl.RemoteIP = ? ', $RemoteIP);
			}
			if($OneUserPerVote && !$RemoteIP){
				$select = $select->where('tbl.UserID = ? ', (empty($UserID) ? '-1' : $UserID));
			}
			if($OneUserPerVote && $RemoteIP){
				$select = $select->where('tbl.UserID = ' . (empty($UserID) ? '-1' : $UserID) . ' OR ' . 'tbl.RemoteIP = "' . $RemoteIP . '"');
			}
			$stmt = $db->query($select);
			$data = $stmt->fetchObject();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}

	private function getSubmissionList($PollItemID) {
		$db = Zend_Db_Table::getDefaultAdapter();
		$data = new stdClass();
		try {

			$selectAmount  = $db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->joinLeft(array('v' => 'poll_submission_values'),"tbl.ID = v.PollSubmissionID", '')
			->columns(array(
					"v.PollQuestionID",
					"Amount" => "count(tbl.PollItemID)"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->group("v.PollQuestionID")
			;

			$select = $db->select()
			->from(array('tbl' => 'poll_submission_info'), '')
			->joinLeft(array('v' => 'poll_submission_values'),"tbl.ID = v.PollSubmissionID", '')
			->joinLeft(array('a' => $selectAmount),"v.PollQuestionID = a.PollQuestionID", '')
			->columns(array(
					"v.PollQuestionID",
					"v.PollOptionID",
					"Amount" => "count(tbl.PollItemID)",
					"Percentage" => "concat(round(( COUNT(tbl.PollItemID)/a.amount * 100 ),2),'%')"
			))
			->where("tbl.IsEnable = 'Y' ")
			->where("tbl.IsDeleted = 'N' ")
			->where("tbl.PollItemID = ? ", $PollItemID)
			->group("v.PollQuestionID")
			->group("v.PollOptionID")
			->group("a.amount")
			;
			$stmt = $db->query($select);
			$data = $stmt->fetchAll();
			
		} catch (Exception $e) {
			
		}
		return $data;
	}

	private function random_color_part() {
		return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
	}
	
	private function random_color() {
		return $this->random_color_part() . $this->random_color_part() . $this->random_color_part();
	}

}
?>