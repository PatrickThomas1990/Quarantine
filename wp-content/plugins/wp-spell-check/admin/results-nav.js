jQuery(document).ready(function() {
	
	var link_url = jQuery("#wpsc-empty-fields-tab .next-page").attr("href");
	link_url += "&wpsc-scan-tab=empty";
	jQuery("#wpsc-empty-fields-tab .next-page").attr("href", link_url);
	
	var link_url = jQuery("#wpsc-empty-fields-tab .last-page").attr("href");
	link_url += "&wpsc-scan-tab=empty";
	jQuery("#wpsc-empty-fields-tab .last-page").attr("href", link_url);
	
	var link_url = jQuery("#wpsc-empty-fields-tab .prev-page").attr("href");
	link_url += "&wpsc-scan-tab=empty";
	jQuery("#wpsc-empty-fields-tab .prev-page").attr("href", link_url);
	
	var link_url = jQuery("#wpsc-empty-fields-tab .first-page").attr("href");
	link_url += "&wpsc-scan-tab=empty";
	jQuery("#wpsc-empty-fields-tab .first-page").attr("href", link_url);
});