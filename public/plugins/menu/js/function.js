$(document).ready(function() {

	/* MAIN MENU */
	$("#d2ms-main-menu ul.sub-menu > li:has(ul.sub-menu) > a").addClass("parent");

	$("#d2ms-menu-toggle").click(function() {
		$("#main-menu").slideToggle(300);
		return false;
	});

	$(window).resize(function() {
		if ($(window).width() > 700) {
			$("#d2ms-main-menu").removeAttr("style");
		}
	});

});