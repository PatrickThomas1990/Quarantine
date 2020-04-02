/*
 * Settings of the sticky menu
 */

jQuery(document).ready(function(jQuery){
    if (jQuery(window).width() > 768) {
       var wpAdminBarTop = jQuery('#wpadminbar, .top-header');
        if ( wpAdminBarTop.length ) {
        	wpAdminBarTop =jQuery('#wpadminbar').height() + jQuery('.top-header').height();
        	jQuery("#nav-sticker").sticky({topSpacing:wpAdminBarTop});
        } else {
        	jQuery("#nav-sticker").sticky({topSpacing:0});
        }
    }
});