function wpscex_init_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'emptyresults_sc',
		},
		dataType: 'html',
		success: function(response) {
			if (response == 'true') { wpscex_recheck_scan(); console.log(response); }
			else { window.setInterval( wpscex_init_scan(),1000 ); console.log(response); }
		}
	});
}

function wpscex_recheck_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'emptyresults_sc',
		},
		dataType: 'html',
		success: function(response) {
			if (response == 'true') { window.setInterval(wpscex_recheck_scan(), 5000 ); console.log(response); }
			else { wpscex_finish_scan(); console.log(response); }
		}
	});
}

function wpscex_finish_scan() {
	jQuery.ajax({
		url: ajax_object.ajax_url,
		type: "POST",
		data: {
			action: 'finish_empty_scan',
		},
		dataType: 'html',
		success: function(response) {
			window.location.href = encodeURI("?page=wp-spellcheck-seo.php&wpsc-script=noscript");
		}
	});
}

window.setInterval( wpscex_recheck_scan(),1000 );