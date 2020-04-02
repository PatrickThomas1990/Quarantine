( function( wp ) {
    //var registerPlugin = wp.plugins.registerPlugin;
    //var PluginSidebar = wp.editPost.PluginSidebar;
    //var el = wp.element.createElement;
	//var Text = wp.components.TextControl;
	//var Btn = wp.components.Button;
	//var url = window.location.href;

    /* registerPlugin( 'wpsc-sidebar', {
        render: function() {
            return el( PluginSidebar,
                {
                    name: 'wpsc-sidebar',
                    icon: 'admin-post',
                    title: 'WP Spell Check',
                },
                el( 'div',
                    { className: 'wpsc-sidebar-content' },
                    el( Text, {
						type: 'button',
                        value: 'Spell Check this page',
						onClick: function( content ) { window.location.href = url+'&wpsc-scan-page=1#wpscmetabox'; },
                    } ),
					el( Text, {
						type: 'button',
                        value: 'View Spelling Errors',
						onClick: function( content ) { wpsc_create_popup(); },
                    } ),
                )
            );
        },
    } ); */
} )( window.wp );

/*function wpsc_create_popup() {
	var postID = findGetParameter("post");
	var filePath = window.location.protocol + "//" + window.location.hostname + "/wp-content/plugins/wp-spell-check/admin/wpsc-editor.php?id=" + postID;
	
	jQuery('.wpsc-modal-box').css('display','block');
	jQuery('.wpsc-modal-box').html('<a href="#" class="wpsc-close-modal">X</a><iframe src="' + filePath + '"></iframe>');
	jQuery('.wpsc-close-modal').on('click',function() {
		jQuery('.wpsc-modal-box').css('display','none');
	});
}*/

/*function findGetParameter(parameterName) {
    var result = null,
        tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
          tmp = item.split("=");
          if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        });
    return result;
}*/