function ChangeLanguage(lang) {
    jQuery.cookie('lang', lang, { expires: 1, path: '/' });
    location.reload();
}
function LoadPage(selector, loadUrl){
    $(selector)
    .empty()
    .append("<img src='/images/box-loader.gif' alt='Loading' title='Loading' />")
    .load(loadUrl)
    .css("display", "");
}
function OpenDialogForm(dialogID, urlPage, actionButton, titleStr, height , width) {
    if (titleStr == null)
        titleStr = " ";
    if (width == null)
        width = 700;
    if (height == null)
        height = 300;

    var $dialog = $("<div id='"+dialogID+"'></div>");
    //$dialog.html("");
    var $divWarning = $("<div></div>");
    $divWarning.addClass("ui-state-error");
    $divWarning.addClass("ui-corner-all");
    $divWarning.css("padding", "5px 10px");
    $divWarning.html("<span style=\"float: left; margin-right: 0.3em;\" class=\"ui-icon ui-icon-alert\"></span><span class=\"textalert\"></span>");
    $textError = $divWarning.children(".textalert");
    $dialog.append($divWarning);
    var $divContent = $("<div></div>");
    $divContent.append("<img src=\"/images/box-loader.gif\" alt=\"Loading...\" title=\"Loading...\"/>");
    $divContent.load(urlPage);
    $dialog.append($divContent);

    $dialog.dialog({
        title : titleStr,
        bgiframe: true,
        autoOpen: false,
        height: height,
        width: width,
        modal: true,
        overlay: {
            backgroundColor: '#000',
            opacity: 0.9
        },
        buttons: actionButton,
        close: function() {
            $("#" + dialogID).dialog( 'destroy' );
            $("#" + dialogID).remove();
        },
        open: function() {
            $divWarning.hide();
        }
    });

    $dialog.dialog('open');
    return false;
}
function getData(url, control, firstLabel, textname, valuename, selectValue) {
    var controlObj = jQuery(control);
    controlObj.empty();
    var html = '';
    jQuery.get(url, {}, function(data){
        if (firstLabel != '') {
            html = "<option value=''>"+firstLabel+"</option>";
        }
        if (data != null) {
            jQuery.each(data, function(i, item){
                selectstr = "";
                if (selectValue == item[valuename])
                    selectstr = "selected='selected'";
                html += "<option value='"+item[valuename]+"' "+selectstr+">"+item[textname]+"</option>";
            });
        }
        controlObj.html(html);
        if (selectValue == "")
            controlObj.change();
    }, "json");
}

function toDateFormat(thaiDate) {
	if (thaiDate == null || thaiDate == undefined || thaiDate == '') {
		return '';
	}
	var d = thaiDate.split("/");
	if (d.length != 3) {
		return '';
	} else {
		var year = d[2] - 543;
		var month = d[1];
		var day = d[0];
		
		return year + '-' + month + '-' + day;
	}
}

function toDate(thaiDate, hour, minute) {
	var date = null;
	var year = '';
	var month = '';
	var day = '';
	hour = hour != null && hour != '' ? hour : 0;
	minute = minute != null && minute != '' ? minute : 0;
	
	if (thaiDate == null || thaiDate == undefined || thaiDate == '') {
		;
	} else {
		var d = thaiDate.split("/");
		if (d.length == 3) {
			year = d[2] - 543;
			month = d[1];
			day = d[0];
		}
	}
	
	if (year != '' && month != '' && day != '' && hour != '' && minute != '') {
		date = new Date(year, month - 1, day, hour, minute, 0, 0);
	} 
	
	return date;
}

function setFontSize (f){
	$("body").removeClass('fontSmall fontLarge');
	var c = ""
	if(f == "small"){
		$("body").addClass("fontSmall");
		c = "fontSmall";
	}else if(f == "normal"){
		
	}else if(f == "large"){
		$("body").addClass("fontLarge");
		c = "fontLarge";
	}else{
		$.removeCookie("font_size");
		return false;
	}
	$.cookie("font_size", c, {path: '/'});
	return true;
}
function setTheme (t){
	$("body").removeClass("themeWhite themeYellow");
	var c = ""
	if(t == "white"){
		$("body").addClass("themeWhite");
		c = "themeWhite";
	}else if(t == "normal"){
		
	}else if(t == "yellow"){
		$("body").addClass("themeYellow");
		c = "themeYellow";
	}else{
		$.removeCookie("theme");
		return false;
	}
	$.cookie("theme", c, {path: '/'});
	return true;
}


/**
 * Loading
 * @param titleStr
 * @param type
 * @returns {Boolean}
 */
function DialogLoading(titleStr, type) {
    if (type == 'open') { 
        if ((titleStr == null) || (titleStr == "")) 
            titleStr = "โปรดรอ...";
        $dialogLoading = $("<div id='dialogLoading'></div>");
        $dialogLoading.append("<div style='text-align: center;'><img src='' alt='Loading' /></div>");
        $dialogLoading.dialog({
            title: titleStr,
            bgiframe: true,
            autoOpen: false,
            height: 150,
            width: 150,
            modal: true,
            overlay: {
                backgroundColor: '#000',
                opacity: 0.9
            },
            close: function() {
                $("#dialogLoading").dialog('destroy');
                $("#dialogLoading").remove();
                delete $dialogLoading;
            }
        });
        $dialogLoading.dialog('open');
    } else {
    	if ($("#dialogLoading").dialog( "isOpen" ) === true) {
    		$( "#dialogLoading" ).dialog( "close" );
    	}
    } 
    return false;
}

/**
 * 
 * @param message
 * @param titleStr
 * @param closeStr
 * @returns {Boolean}
 */
function DialogWarning(message, titleStr, closeStr) {
	console.log(message, titleStr, closeStr);
    if ((titleStr == null) || (titleStr == "")) {
    	titleStr = "แจ้งเตือน";
    }
    if ((message == null) || (message == "")) {
    	message = "แจ้งเตือน";
    }
    if ((closeStr == null) || (closeStr == "")) {
    	closeStr = "ปิด";
    }
    
	var text = '<div style="font-weight: bold;">' + titleStr + '</div>' + "<span>" + message + "</span>";
    noty({
    	text: text, 
    	modal: true, 
    	type: 'warning', 
    	layout: 'center',
        buttons: [{
        	addClass: 'btn btn-danger', 
        	text: closeStr, 
        	onClick: function($noty){
        		$noty.close();
        	}
        }]
    });    
    return false;
}

/**
 * Complete
 * @param message
 * @returns {Boolean}
 */
function DialogComplete(message) {
    message = ((message == null) || (message == "")) ? "บันทึกสำเร็จ" : message;
    noty({text: message, modal: false, type: 'success', layout: 'topCenter', timeout: 1000});

    return false;
}

/**
 * Confirm
 * @param message
 * @param titleStr
 * @param confirmFunction_Y
 * @param confirmFunction_N
 * @param confirmStr
 * @param cancelStr
 * @returns {Boolean}
 */
function DialogConfirm(message, titleStr, confirmFunction_Y,confirmFunction_N, confirmStr, cancelStr) {
    if ((titleStr == null) || (titleStr == "")) {
    	titleStr = "คุณแน่ใจหรือไม่?";
    }
    if ((message == null) || (message == "")) {
    	message = "คุณแน่ใจหรือไม่?"; 
    }
    if ((confirmStr == null) || (confirmStr == "")) {
    	confirmStr = "ยืนยัน"; 
    }
    if ((cancelStr == null) || (cancelStr == "")) {
    	cancelStr = "ยกเลิก"; 
    }
    
	var text = ' <div style="font-weight: bold;">' + titleStr + '</div>' + "<span>" + message + "</span>";
    noty({text: text, modal: true, type: 'confirmation', layout: 'center', 
        buttons: [
                  {addClass: 'btn btn-primary', text: confirmStr, onClick: function($noty) {
                      	if (confirmFunction_Y != null && confirmFunction_Y != undefined && confirmFunction_Y != '' ) { 
                      		confirmFunction_Y();	
                      	}
                        $noty.close();
                      }},
                  {addClass: 'btn btn-danger', text: cancelStr, onClick: function($noty){ 
                    	  if (confirmFunction_N != null && confirmFunction_N != undefined && confirmFunction_N != '' ) { 
                    		  confirmFunction_N();	
                          }
                	  		$noty.close();
                      }}
                  ]
    });
    
    return false;
}