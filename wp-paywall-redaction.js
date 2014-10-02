/* insert paywall into the post */
function insertPaywall(where, field) {
	if (where == 'code') {
		edInsertContent(field, '[wp-paywall]');
	} else {
		return '[wp-paywall]';
	}
}

/* create an icon on the WP editor toolbar */
if (document.getElementById("ed_toolbar")) {
	edButtons[edButtons.length] = new edButton("ed_paywall", "paywall", "", "", "");
	jQuery(document).ready(function($) { 
		var classes = $('#ed_toolbar input[type="button"]').first().attr('class');
		$('#qt_content_ed_paywall').replaceWith(
			'<input type="button" id="qt_content_ed_paywall" accesskey="" class="' + classes + '" onclick="insertPaywall(\'code\', edCanvas);" value="&nbsp;&nbsp;&nbsp;paywall" title="insert paywall"/>');
	});
}

