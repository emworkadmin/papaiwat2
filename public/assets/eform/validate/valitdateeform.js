(function($) {

    $.fn.alphabetonly = function(options) {
        var opts = $.extend( {}, $.fn.alphabetonly.defaults, options );
        msg = opts.message;
        color = opts.color;
        size = opts.size;
        var letters = /^[กขฃคฅฆงจฉชซฌญฎฏฐฑฒณดตถทธนบปผฝพฟภมยรฤลฦวศษสหฬอฮฯะัาำิีึืฺุู฿เแโใไๅๆ็่้๊๋์ํ๎๏A-Za-z ]+$/;
        $.each($(this), function(i,e) {
            var id = $(e).attr("id");
            $(e).parent().append("<span class='pattern-validate-txt' id='"+id+"' style='display:none;color:"+color+";font-size:"+size+"'>" + msg + "</span>");
            $(this).keyup(function() {
                if($(this).val().length>0) {
                    if( letters.test($(this).val()) ) {
                        $("span#"+id).css({"display":"none"});
                    } else {
                        $("span#"+id).css({"display":"block"});
                    }
                } else {
                    $("span#"+id).css({"display":"none"});
                }
            });
        });
    }
    $.fn.alphabetonly.defaults = {
        message: "โปรดระบุเฉพาะตัวอักษรที่ถูกต้อง !",
        color: "red",
        size: '16px'
    };

    $.fn.emailonly = function(options) {
        var opts = $.extend( {}, $.fn.emailonly.defaults, options );
        msg = opts.message;
        color = opts.color;
        size = opts.size;
        var letters = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        $.each($(this), function(i,e) {
            var id = $(e).attr("id");
            $(e).parent().append("<span class='pattern-validate-txt' id='"+id+"' style='display:none;color:"+color+";font-size:"+size+"'>" + msg + "</span>");
            $(this).keyup(function() {
                if($(this).val().length>0) {
                    if( letters.test($(this).val()) ) {
                        $("span#"+id).css({"display":"none"});
                    } else {
                        $("span#"+id).css({"display":"block"});
                    }
                } else {
                    $("span#"+id).css({"display":"none"});
                }
            });
        });
    }
    $.fn.emailonly.defaults = {
        message: "โปรดระบุที่อยู่อีเมล์ที่ถูกต้อง !",
        color: "red",
        size: '16px'
    };

    $.fn.numbericonly = function(options) {
        var opts = $.extend( {}, $.fn.numbericonly.defaults, options );
        msg = opts.message;
        color = opts.color;
        size = opts.size;
        var letters = /^[1234567890๑๒๓๔ู฿๕๖๗๘๙๐ ]+$/;
        $.each($(this), function(i,e) {
            var id = $(e).attr("id");
            $(e).parent().append("<span class='pattern-validate-txt' id='"+id+"' style='display:none;color:"+color+";font-size:"+size+"'>" + msg + "</span>");
            $(this).keyup(function() {
                if($(this).val().length>0) {
                    if( letters.test($(this).val()) ) {
                        $("span#"+id).css({"display":"none"});
                    } else {
                        $("span#"+id).css({"display":"block"});
                    }
                } else {
                    $("span#"+id).css({"display":"none"});
                }
            });
        });
    }
    $.fn.numbericonly.defaults = {
        message: "โปรดระบุจำนวนเต็มที่ถูกต้อง !",
        color: "red",
        size: '16px'
    };

    $.fn.httponly = function(options) {
        var opts = $.extend( {}, $.fn.httponly.defaults, options );
        msg = opts.message;
        color = opts.color;
        size = opts.size;
        var letters = /((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)/;
        $.each($(this), function(i,e) {
            var id = $(e).attr("id");
            $(e).parent().append("<span class='pattern-validate-txt' id='"+id+"' style='display:none;color:"+color+";font-size:"+size+"'>" + msg + "</span>");
            $(this).keyup(function() {
                if($(this).val().length>0) {
                    if( letters.test($(this).val()) ) {
                        $("span#"+id).css({"display":"none"});
                    } else {
                        $("span#"+id).css({"display":"block"});
                    }
                } else {
                    $("span#"+id).css({"display":"none"});
                }
            });
        });
    }
    $.fn.httponly.defaults = {
        message: "โปรดระบุที่อยู่ url ที่ถูกต้อง !",
        color: "red",
        size: '16px'
    };

    $.fn.validate = function() {
        var valid = true;
        $.each($(this).find(".validate-txt"), function(i, e) {
            if( $(e).css('display') == 'block') {
                valid = false;
            }
        });
        $.each($(this).find(".pattern-validate-txt"), function(i, e) {
            if( $(e).css('display') == 'block') {
                valid = false;
            }
        });                                            
        return valid;
    }

    

})(jQuery);