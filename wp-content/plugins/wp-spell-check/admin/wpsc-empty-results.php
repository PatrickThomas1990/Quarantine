<?php
function wpscx_admin_empty_render() {
    
        ?>
            <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css">
            <link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>../css/admin-styles.css">
            <link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>../css/wpsc-sidebar.css">
        <?php
        
	$start = time();
	ini_set('memory_limit','8192M'); 
	set_time_limit(600); 
	global $wpdb;
	global $ent_included;
	global $base_page_max;
        global $wpsc_version;
	$table_name = $wpdb->prefix . "spellcheck_words";
	$empty_table = $wpdb->prefix . "spellcheck_empty";
	$options_table = $wpdb->prefix . "spellcheck_options";
	$post_table = $wpdb->prefix . "posts";
	$total_smartslider = 0;
	$total_huge_it = 0;
        $utils = new wpscx_results_utils;
        
        wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script('admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js');
	wp_enqueue_script('feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js');
        wp_enqueue_script('jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../js/jquery.contextMenu.js');
	wp_enqueue_script('jquery.ui.position', plugin_dir_url( __FILE__ ) . '../js/jquery.ui.position.js');
	
	if (!isset($_GET['action'])) $_GET['action'] = '';
	if (!isset($_GET['submit'])) $_GET['submit'] = '';
	if (!isset($_GET['submit-empty'])) $_GET['submit-empty'] = '';
	if (!isset($_GET['wpsc-scan-tab'])) $_GET['wpsc-scan-tab'] = '';
	
	$max_pages = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'");
	$max_pages = intval($max_pages[0]->option_value);
	
	if (!$ent_included && !$pro_included) $max_pages = $base_page_max;
	
	$message = '';
	
	if ($_GET['submit'] == "Stop Scans") {
		$message = "All current spell check scans have been stopped.";
		wpscx_clear_scan();
	}
	if ($_GET['submit-empty'] == "Stop Scans") {
		$message = "All current empty field scans have been stopped.";
		wpscx_clear_empty_scan();
	}

	
	$settings = $wpdb->get_results('SELECT option_name, option_value FROM ' . $options_table);
	$check_pages = $settings[4]->option_value;
	$check_posts = $settings[5]->option_value;
	$check_menus = $settings[7]->option_value;
	$page_titles = $settings[12]->option_value;
	$post_titles = $settings[13]->option_value;
	$tags = $settings[14]->option_value;
	$categories = $settings[15]->option_value;
	$seo_desc = $settings[16]->option_value;
	$seo_titles = $settings[17]->option_value;
	$page_slugs = $settings[18]->option_value;
	$post_slugs = $settings[19]->option_value;
	$check_sliders = $settings[30]->option_value;
	$check_media = $settings[31]->option_value;
	$check_ecommerce = $settings[36]->option_value;
	$check_cf7 = $settings[37]->option_value;
	$check_tag_desc = $settings[38]->option_value;
	$check_tag_slug = $settings[39]->option_value;
	$check_cat_desc = $settings[40]->option_value;
	$check_cat_slug = $settings[41]->option_value;
	$check_custom = $settings[42]->option_value;
	$check_authors = $settings[44]->option_value;
	$check_authors_empty = $settings[46]->option_value;
	$check_authors_empty = $settings[47]->option_value;
	$check_menu_empty = $settings[48]->option_value;
	$check_page_titles_empty = $settings[49]->option_value;
	$check_post_titles_empty = $settings[50]->option_value;
	$check_tag_desc_empty = $settings[51]->option_value;
	$check_cat_desc_empty = $settings[52]->option_value;
	$check_page_seo_empty = $settings[53]->option_value;
	$check_post_seo_empty = $settings[54]->option_value;
	$check_media_seo_empty = $settings[55]->option_value;
	$check_media_empty = $settings[56]->option_value;
	$check_ecommerce_empty = $settings[57]->option_value;
	
	$postmeta_table = $wpdb->prefix . "postmeta";
	$post_table = $wpdb->prefix . "posts";
	$it_table = $wpdb->prefix . "huge_itslider_images";
	$smartslider_table = $wpdb->prefix . "nextend_smartslider_slides";
	
	$total_pages = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'");
	$total_posts = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'");
	$total_media = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'attachment'");
	
	
	
	if (isset($_GET['action'])) {
		if ($_GET['action'] == 'check') {
			
			
			
			$total_products = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type='product' AND (post_status='draft' OR post_status='publish')");
			$total_cf7 = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type='wpcf7_contact_form' AND (post_status='draft' OR post_status='publish')");
			$total_menu = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type='nav_menu_item' AND (post_status='draft' OR post_status='publish')");
			$total_authors = sizeof((array)$wpdb->get_results("SELECT * FROM $post_table GROUP BY post_author")); $sql_count++;
			$total_tags = sizeof(get_tags()); $sql_count++;
			$total_tag_desc = $total_tags;
			$total_tag_slug = $total_tags;
			$total_cat = sizeof(get_categories()); $sql_count++;
			$total_cat_desc = $total_cat;
			$total_cat_slug = $total_cat;
			$total_seo_title = sizeof((array)$wpdb->get_results("SELECT * FROM $postmeta_table WHERE meta_key='_yoast_wpseo_title' OR meta_key='_aioseop_title' OR meta_key='_su_title'")); $sql_count++;
			$total_seo_desc = sizeof((array)$wpdb->get_results("SELECT * FROM $postmeta_table WHERE meta_key='_yoast_wpseo_metadesc' OR meta_key='_aioseop_description' OR meta_key='_su_description'")); $sql_count++;
			
			
			
			
			
			
			
			$total_generic_slider = sizeof((array)get_pages(array('number' => PHP_INT_MAX, 'hierarchical' => 0, 'post_type' => 'slider', 'post_status' => array('publish', 'draft')))); $sql_count++;
			$total_sliders = $total_huge_it + $total_smartslider + $total_generic_slider;

			if (!$ent_included) {
				if ($total_pages > 1000) $total_pages = 1000;
				if ($total_posts > 1000) $total_posts = 1000;
				if ($total_media > 1000) $total_posts = 1000;
				if ($total_seo_title > 1000) $total_seo_title = 1000;
				if ($total_seo_desc > 1000) $total_seo_desc = 1000;
			}
			
			$total_other = $total_menu + $total_authors + $total_tags + $total_tag_desc + $total_tag_slug + $total_cat + $total_cat_desc + $total_cat_slug + $total_seo_title + $total_seo_desc;
			
			$total_page_slugs = $total_pages; 
			$total_post_slugs = $total_posts; 
			$total_page_title = $total_pages; 
			$total_post_title = $total_posts; 
			
			$estimated_time = intval((($total_pages + $total_posts) / 3.5) + 3);
	}
	}

	if (!$ent_included) {
		if ($total_pages > 1000) $total_pages = 1000;
		if ($total_posts > 1000) $total_posts = 1000;
		if ($total_media > 1000) $total_posts = 1000;
		if ($total_seo_title > 1000) $total_seo_title = 1000;
		if ($total_seo_desc > 1000) $total_seo_desc = 1000;
	}
	
	$total_other = $total_menu + $total_authors + $total_tags + $total_tag_desc + $total_tag_slug + $total_cat + $total_cat_desc + $total_cat_slug + $total_seo_title + $total_seo_desc;
	
	$total_page_slugs = $total_pages; 
	$total_post_slugs = $total_posts; 
	$total_page_title = $total_pages; 
	$total_post_title = $total_posts; 
	
	$estimated_time = intval((($total_pages + $total_posts) / 3.5) + 3);
	$scan_message = '';
	
	$scan = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='scan_in_progress';");
	$empty_scan = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='empty_scan_in_progress';");
	
	$check_scan = wpscx_check_scan_progress();
	if ($check_scan && $_GET['wpsc-script'] != 'noscript') {
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		sleep(1);
	}
	$check_empty = wpscx_check_empty_scan_progress();
	if ($check_empty && $_GET['wpsc-script'] != 'noscript') {
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		sleep(1);
	}
	
	
	
	
	$estimated_time = time_elapsed($estimated_time);
	
	$end = time();
	//echo "debug - Initialization Finished: " . ($end - $start) . " Seconds<br />";
	
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Menus') {
		$estimated_time = 5 + intval($total_menu / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Menus</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_menu_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Menus'), array('option_name' => 'last_empty_type'));
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckmenusempty_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckmenusempty', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Page Titles') {
		$estimated_time = 5 + intval($total_page_title / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Page Titles</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_page_title_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Page Titles'), array('option_name' => 'last_empty_type'));
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpagetitlesempty_ent', array ($rng_seed ));
		} elseif ($pro_included) {
		wp_schedule_single_event(time(), 'admincheckpagetitlesempty', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckpagetitlesemptybase', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Post Titles') {
		$estimated_time = 5 + intval($total_post_title / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Post Titles</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_post_title_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Post Titles'), array('option_name' => 'last_empty_type'));
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttitlesempty_ent', array ($rng_seed ));
		} elseif ($pro_included) {
		wp_schedule_single_event(time(), 'admincheckposttitlesempty', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckposttitlesemptybase', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Tag Descriptions') {
		$estimated_time = 5 + intval($total_tag_desc / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Tag Descriptions</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_tag_desc_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Tag Descriptions'), array('option_name' => 'last_empty_type'));
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttagsdescempty_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckposttagsdescempty', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Category Descriptions') {
		$estimated_time = 5 + intval($total_cat_desc / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Category Descriptions</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_cat_desc_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Category Descriptions'), array('option_name' => 'last_empty_type'));
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckcategoriesdescempty_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckcategoriesdescempty', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Media Files') {
		$estimated_time = 5 + intval($total_media / 3.5);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Media Files</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_media_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Media Files'), array('option_name' => 'last_empty_type'));
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckmediaempty_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckmediaempty_pro', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'WooCommerce and WP-eCommerce Products') {
		$estimated_time = 5 + intval($total_products / 3.5);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">eCommerce Products</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_ecommerce_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'eCommerce Products'), array('option_name' => 'last_empty_type'));
		
		sleep(1);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty') );
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckecommerceempty_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckecommerceempty', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Authors') {
		$estimated_time = 5 + intval($total_authors / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Authors</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_author_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Authors'), array('option_name' => 'last_empty_type'));
		wp_schedule_single_event(time(), 'admincheckauthorsempty', array ($rng_seed ));
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Page SEO') {
		$estimated_time = 5 + intval($total_seo_desc / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Page SEO</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_page_seo_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Page SEO'), array('option_name' => 'last_empty_type'));
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpageseoempty_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckpageseoempty', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Post SEO') {
		$estimated_time = 5 + intval($total_seo_title / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Post SEO</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_post_seo_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Post SEO'), array('option_name' => 'last_empty_type'));
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpostseoempty_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckpostseoempty', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Media Files SEO') {
		$estimated_time = 5 + intval($total_seo_title / 100);
		$estimated_time = time_elapsed($estimated_time);
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Media Files SEO</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_empty_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_media_seo_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Media Files SEO'), array('option_name' => 'last_empty_type'));
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckmediaseoempty_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckmediaseoempty', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Entire Site') {
		$estimated_time = intval((($total_pages + $total_posts) / 3.5) + 3);
		$estimated_time = time_elapsed($estimated_time);
	$empty_scan_message = '';
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for the <span style="color: rgb(115, 1, 154); font-weight: bold;">Entire Site</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		
		wpscx_clear_results_empty("full");
		$rng_seed = rand(0,999999999);
		wpscx_set_empty_scan_in_progress($rng_seed);
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
		$wpdb->update($options_table, array('option_value' => 'Entire Site'), array('option_name' => 'last_empty_type'));
		wp_enqueue_script( 'emptyresults-ajax', plugin_dir_url( __FILE__ ) . '/empty-ajax.js', array('jquery') );
		wp_localize_script( 'emptyresults-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'wpsc_scan_tab' => 'empty' ) );
		wp_schedule_single_event(time(), 'adminscansiteempty', array($rng_seed));
	}
	
	if ($_GET['action'] == 'check' && $_GET['submit-empty'] == 'Clear Results') {
		$message = 'All empty field results have been cleared';
		wpscx_clear_empty_results("full");
	}
	
	if (isset($_GET['word_update'])) {
		$message = $utils->update_empty_admin($_GET['word_update'], $_GET['edit_page_name'], $_GET['edit_page_type'], $_GET['edit_old_word_id']);
	}
	
	if (isset($_GET['ignore_word'])) {
	if ($_GET['ignore_word'] != '' && $_GET['wpsc-scan-tab'] != 'empty') {
		$ignore_message = ignore_word($_GET['ignore_word']); 
	} elseif ($_GET['ignore_word'] != '' && $_GET['wpsc-scan-tab'] == 'empty') {
		$ignore_message = $utils->ignore_word_empty($_GET['ignore_word']); 
	}
	}
	
	$end = time();
	//echo "debug - Checking For Scan Buttons Pressed Finished: " . ($end - $start) . " Seconds<br />";
	
	
	
		
	$word_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" );
	$empty_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $empty_table WHERE ignore_word='false'" );
	
	$empty_table = new sc_table();
	$empty_table->prepare_empty_items();
	
	$path = plugin_dir_path( __FILE__ ) . '../premium-functions.php';
	global $pro_included;
	
	
	
	$end = time();
	//echo "debug - Results Tables Prepared: " . ($end - $start) . " Seconds<br />";
	
	

	
	$pro_words = 0;
	$empty_words = 0;
	if (!$pro_included && !$ent_included) {
		$pro_word_count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='pro_word_count';");
		$pro_words = $pro_word_count[0]->option_value;
		$empty_word_count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='pro_empty_count';");
		$empty_words = $empty_word_count[0]->option_value;
	}
	$total_word_count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='total_word_count';");
	$literacy_factor = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='literary_factor';");
	$literacy_factor = $literacy_factor[0]->option_value;
	
	$empty_factor = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='empty_factor';");
	$empty_factor = $empty_factor[0]->option_value;
	
	$empty_results = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='empty_checked';");
	$empty_field_count = $empty_results[0]->option_value;
	
	$cron_tasks = _get_cron_array();
	$scan_progress = false;
	$scan_site = 0;
	
	foreach ($cron_tasks as $task) {
		if (key($task) == 'adminscansite') {
			$scan_site++;
		} elseif (substr(key($task), 0, strlen('admincheck')) === 'admincheck') {
			$scan_progress = true;
		}
	}
	if ($scan_site >= 2) $scan_progress = true;
	
	
	
	
	
	
	
	$scanning = $scan;
	$scan_progress = wpscx_check_scan_progress();
	if ($scan_progress && $scan_message == '' && $_GET['wpsc-script'] != 'noscript') {
		$last_type = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='last_scan_type'");
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' . $last_type[0]->option_value . '</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
	} elseif ($scanning[0]->option_value == "error" && $scan_message == '' && !$scan_progress) {
		$scan_message = "<span style='color:red;'>No scan currently running. The previous scan was unable to finish scanning</style>";
	} elseif ($scan_message == '') {
		$scan_message = "No scan currently running";
	}
	
	$empty_scan_progress = wpscx_check_empty_scan_progress();
	if ($empty_scan_progress && $empty_scan_message == '' && $_GET['wpsc-script'] != 'noscript') {
		$last_type = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='last_empty_type'");
		$empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' . $last_type[0]->option_value . '</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
	} elseif ($empty_scan_message == '') {
		$empty_scan_message = "No scan currently running";
	}
	
	$time_of_scan = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='last_scan_finished';");
	$time_of_empty = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='empty_start_time';");
	if ($time_of_scan[0]->option_value == "0") {
		$time_of_scan = "0 Minutes";
	} else {
		$time_of_scan = $time_of_scan[0]->option_value;
		if ($time_of_scan == '') $time_of_scan = "0 Seconds";
	}
	
	if ($time_of_empty[0]->option_value == "0") {
		$time_of_empty = "0 Minutes";
	} else {
		$time_of_empty = $time_of_empty[0]->option_value;
		if ($time_of_empty == '') $time_of_empty = "0 Seconds";
	}
	
	$options_table = $wpdb->prefix . "spellcheck_options";
	
	$scan_type = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='last_scan_type'");
	$empty_type = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='last_empty_type'");

	
	
	$post_status = array("publish", "draft");
	
	
	

	$post_count = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type='post' AND (post_status='draft' OR post_status='publish')");
	$page_count = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type='page' AND (post_status='draft' OR post_status='publish')");
	$media_count = $total_media;

	
	
	
	$page_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='page_count';");
	$post_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='post_count';");
	$media_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='media_count';");
	
	$empty_page_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='empty_page_count';");
	$empty_post_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='empty_post_count';");
	$empty_media_scan = $wpdb->Get_results("SELECT option_value FROM $options_table WHERE option_name='empty_media_count';");
	$options_list = $wpdb->Get_results("SELECT option_value FROM $options_table;");
	
	$empty_post_scan_count = $empty_post_scan[0]->option_value;
	if ($empty_post_scan_count > $post_count) $empty_post_scan_count = $post_count;
	
	$total_words = $options_list[22]->option_value;
	
	wp_enqueue_script('results-nav', plugin_dir_url( __FILE__ ) . 'results-nav.js');
	
	
	
	//$empty_factor = ();
	
	$end = time();
	//echo "debug - Finalization Code Finished(about to render HTML): " . ($end - $start) . " Seconds<br />";
	
	?>
		<?php wpscx_show_feature_window(); ?>
		<?php //wpscx_check_install_notice(); ?>
		
	<style>input[type=submit]{border-radius:32px!important; box-shadow: none!important; text-shadow: none!important; border: none!important;}.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } td.word { font-size: 15px; } p.submit { display: inline-block; margin-left: 8px; } h3.sc-message { width: 49%; display: inline-block; padding-left: 8px; font-weight: normal; } .wpsc-mouseover-text-page,.wpsc-mouseover-text-post,.wpsc-mouseover-text-refresh { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; z-index: -100; } .wpsc-row .row-actions, .wpsc-row .row-actions *{ visibility: visible!important; left: 0!important; } #current-page-selector { width: 12%; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; margin-botton: 15px; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -1px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } #wpsc-empty-fields-tab .button-primary { background: #73019a; border-color: #51006E; text-shadow: 1px 1px #51006d; box-shadow: 0 1px 0 #51006d; } #wpsc-empty-fields-tab .button-primary:hover { background: #9100c3 } #wpsc-empty-fields-tab .button-primary:active { background: #51006d; }.wpsc-scan-buttons input#submit:active { margin-top: -7px; } #wpsc-empty-fields-tab span.wpsc-bulk { display: none; } span.wpsc-bulk { color: black; }
	
	</style>
	<script>
		jQuery(document).ready(function() {
			var should_submit = false;
			var shown_box = false;
                        <?php if ($check_scan) { ?>
                            var scan_in_progress = true;
                        <?php } else { ?>
                            var scan_in_progress = false;
                        <?php } ?>
			
			jQuery(".wpsc-edit-update-button").click( function(event) {
				if (!should_submit) event.preventDefault();
				jQuery('.wpsc-mass-edit-chk').each(function() {
					if (jQuery(this).is(":checked") && shown_box == false) {
						shown_box = true;
						jQuery( "#wpsc-mass-edit-confirm" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							"Yes": function() {
							  jQuery( this ).dialog( "close" );
							  should_submit = true;
							  jQuery("#wpsc-edit-update-button-hidden").click();
							},
							Cancel: function() {
							  jQuery( this ).dialog( "close" );
							}
						  }
						});
				}
				});
				if (shown_box == false) {
					should_submit = true;
					jQuery("#wpsc-edit-update-button-hidden").click();
				}
			  } );
                          
                          jQuery('.wpscScan').click(function(event) {
                            event.preventDefault();
                            if (scan_in_progress) return;
                            scan_in_progress = true;
                            ajax_object = '<?php echo admin_url( 'admin-ajax.php' ) ?>';
                            var scanType = jQuery(this).attr('value');
                            console.log(scanType);
                                    
                            jQuery.ajax({
                                    url: ajax_object,
                                    type: "POST",
                                    data: {
                                            type: scanType,
                                            action: 'wpscx_start_scan_empty',
                                    },
                                    dataType: 'html',
                                    success: function(response) {
                                        jQuery('#wpscScanMessage').html(response); //update the scan message to display the scan started message
                                        window.setInterval(wpscex_recheck_scan_temp(), 1000 );
                                        jQuery('tr.wpsc-row').animate({opacity: 0}, 500, function() { jQuery('tr.wpsc-row').hide(); })
                                    }
                            });
                        });
		});
                
                function wpscex_recheck_scan_temp() {
                        jQuery.ajax({
                                url: ajax_object.ajax_url,
                                type: "POST",
                                data: {
                                        action: 'emptyresults_sc',
                                },
                                dataType: 'html',
                                success: function(response) {
                                        if (response == 'true') { window.setInterval(wpscex_recheck_scan_temp(), 5000 ); console.log(response); }
                                        else { wpscex_finish_scan_temp(); console.log(response); }
                                }
                        });
                }

                function wpscex_finish_scan_temp() {
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
	</script>
<div id="wpsc-mass-edit-confirm" title="Are you sure?" style="display: none;">
  <p>This will update all areas of your website that you have selected WP Spell Check to scan. Are you sure you wish to proceed with the changes?</p>
</div>
	<div class="wrap wpsc-table">
		<h2><a href="admin.php?page=wp-spellcheck-seo.php"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - SEO Empty Field Results</span></h2>
			<div class="wpsc-scan-nav-bar">
				<a href="<?php echo admin_url(); ?>admin.php?page=wp-spellcheck.php" id="wpsc-scan-results" name="wpsc-scan-results">Spelling Errors</a>
				<a href="<?php echo admin_url(); ?>admin.php?page=wp-spellcheck-grammar.php" id="wpsc-grammar" name="wpsc-grammar">Grammar</a>
				<a href="#empty-fields" id="wpsc-empty-fields" class="selected" name="wpsc-empty-fields">SEO Empty Fields</a>
				<a href="<?php echo admin_url(); ?>admin.php?page=wp-spellcheck-html.php" id="wpsc-grammar" name="wpsc-grammar">Broken Code</a>
			</div>
			<div id="wpsc-empty-fields-tab">
			<form action="<?php echo admin_url('admin.php'); ?>" method='GET'>
				<div class="wpsc-scan-buttons" style="background: white; padding-left: 8px; padding-top: 5px;">
				<h3 style="margin-bottom: 0px;">This function finds all the fields that have been left empty so you can add content to improve your SEO</h3>
				<h3 style="display: inline-block;">Scan:</h3>
				<p class="submit"><input style="background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold;" type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Entire Site" <?php if ($checked_pages == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php if ($pro_included || $ent_included) { ?>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Page SEO" <?php if ($check_page_seo_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Post SEO" <?php if ($check_post_seo_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Media Files SEO" <?php if ($check_media_seo_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Media Files" <?php if ($check_media_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php } ?>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Authors" <?php if ($check_authors_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php if ($pro_included || $ent_included) { ?>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Menus" <?php if ($check_menu_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php } ?>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Page Titles" <?php if ($check_page_titles_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Post Titles" <?php if ($check_post_titles_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php if ($pro_included || $ent_included) { ?>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Tag Descriptions" <?php if ($check_tag_desc_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="Category Descriptions" <?php if ($check_cat_desc_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php if (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active('wp-e-commerce/wp-shopping-cart.php')) { ?><p class="submit"><input type="submit" name="submit-empty" id="submit" class="button button-primary wpscScan" value="WooCommerce and WP-eCommerce Products" <?php if ($check_ecommerce_empty == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p><?php } ?>
				<?php } ?>
				<p class="submit" style="margin-left: -11px;"><span style="position: relative; left: 15px;"> - </span><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/clear-results.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 27px;" /><input type="submit" name="submit-empty" id="submit" style="padding-left: 30px; background-color: red;" class="button button-primary" value="Clear Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/see-results.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 26px;" /><input type="submit" name="submit" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="See Scan Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/stop-scans.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 25px;" /><input type="submit" name="submit-empty" id="submit" class="button button-primary" style="padding-left: 30px; background-color: red;" value="Stop Scans"></p>
                                <p class="submit" style="margin-left: -11px;"><a href="/wp-admin/admin.php?page=wp-spellcheck-options.php"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/options.png'; ?>" title="Options" style="width: 30px; position: relative; top: 11px; left: 20px; padding: 0px; border-radius: 25px;" /></a></p>
				</div>
				<div style="background: white; padding: 5px; font-size: 12px;">
				<input type="hidden" name="page" value="wp-spellcheck-seo.php">
				<input type="hidden" name="action" value="check">
				<?php echo "<h3 class='sc-message'style='color: rgb(115, 1, 154); font-size: 1.4em;'>Website Empty Fields Factor: " . $empty_factor . "%"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Last scan took $time_of_empty</h3>"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);' id='wpscScanMessage'>$empty_scan_message</h3><br />"; ?>
				<?php if (!$ent_included) {
					if ($empty_words > 0) {
						echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><strong>Pro Version: </strong>" . $empty_words . " SEO Empty Fields were found on other parts of your website. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradeSEO&utm_medium=seo_scan&utm_content=$wpsc_version' target='_blank'>Click here</a> to upgrade and boost your SEO.</h3>";
					} else {
						//echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Upgrade</a> to scan all parts of your website.</h3>";
					}
				} ?>
				</div>
			</form>
			<?php include("sidebar.php"); ?>
			<?php if(($message != '' || $ignore_message[0] != '' || $dict_message[0] != '') && $_GET['wpsc-scan-tab'] == 'empty') { ?>
				<div style="text-align: center; background-color: white; padding: 5px; margin: 15px 0;">
					<?php if($message != '') echo "<div class='wpsc-message' style='width: 74%; font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $message . "</div>"; ?>
					<?php if($ignore_message[0] != '') echo "<div class='wpsc-message' style='width: 74%; font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $ignore_message[0] . "</div>"; ?>
					<?php if($dict_message[0] != '') echo "<div class='wpsc-message' style='width: 74%; font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $dict_message[0] . "</div>"; ?>
				</div>
				<?php } ?>
			<form id="words-list" method="get" style="width: 75%; float: left; margin-top: 10px;">
				<p class="search-box" style="position: relative; margin-top: 8px;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<input type="hidden" name="wpsc-scan-tab" value="empty" />
				<input name="wpsc-edit-update-button" class="wpsc-edit-update-button empty-tab" type="submit" value="Save all Changes" class="button button-primary" style="width: 16%; padding-top: 5px; padding-bottom: 5px; margin-left: 32.5%; display: block; background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: 7px;"/>
				<?php $empty_table->display(); ?>
				<?php $end_empty = time(); ?>
				<p class="search-box" style="margin-top: 0.7em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Page Names">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
				<input name="wpsc-edit-update-buttom" class="wpsc-edit-update-button empty-tab" type="submit" value="Save all Changes" class="button button-primary" style="width: 16%; padding-top: 5px; padding-bottom: 5px; margin-left: 31.5%; display: block;  background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: -31px;"/>
			</form>
			
			<div style="padding: 15px; background: white;  clear: both; width: 72%; font-family: helvetica;">
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>SEO problems found on <span style='color: rgb(115, 1, 154); font-weight: bold;'>".$empty_type[0]->option_value."</span>: {$empty_count}</h3>"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Pages scanned: " . $empty_page_scan[0]->option_value . "/" . $page_count;
					if ($pro_included && $page_count >= 1000) { echo "<span class='wpsc-mouseover-button-page' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-page'>Our pro version scans up to 1000 pages.<br /><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Click here</a> to upgrade to enterprise</span></span>";
					} elseif (!$pro_included && !$ent_included && sizeof((array)$page_count) >= 500) { echo "<span class='wpsc-mouseover-button-page' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-page'>Our free version scans up to 500 pages.<br /><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Click here</a> to upgrade to pro</span></span>"; }
					echo "</h3>"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Posts scanned: " . $empty_post_scan_count . "/" . $post_count;
				if ($pro_included && $post_count >= 1000) { echo "<span class='wpsc-mouseover-button-post' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-post'>Our pro version scans up to 1000 posts.<br /><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Click here</a> to upgrade to enterprise</span></span>";
				} elseif (!$pro_included && !$ent_included && $post_count >= 500) { echo "<span class='wpsc-mouseover-button-post' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-post'>Our free version scans up to 500 posts.<br /><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Click here</a> to upgrade to pro</span></span>"; }
				echo "</h3>"; ?>
				<?php if ($pro_included || $ent_included) { echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Media files scanned: " . $empty_media_scan[0]->option_value . "/" . $media_count . "</h3>"; } ?>
			</div>
		</div>
		</div>
<!-- Quick Edit Clone Field -->
		<table style="display: none;">
			<tbody>
				<tr id="wpsc-editor-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-edit-content">
							<h4 style="display: inline-block;">Edit %Word%</h4>
							<input type="text" size="60" name="word_update[]" style="margin-left: 3em;" value class="wpsc-edit-field edit-field">
							<br><span class="wpsc-bulk"><input name="wpsc-mass-edit[]" class="wpsc-mass-edit-chk" type="checkbox" value="" />Apply this change to the entire website</span>
							<input type="hidden" name="edit_page_name[]" value>
							<input type="hidden" name="edit_page_type[]" value>
							<input type="hidden" name="edit_old_word[]" value>
							<input type="hidden" name="edit_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
							<!--<input type="checkbox" name="global-edit" value="global-edit"> Apply changes to entire website-->
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<!-- Suggested Spellings Clone Field -->
		<table style="display: none;">
			<tbody>
				<tr id="wpsc-suggestion-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-suggestion-content">
							<label><span>Suggested Spellings</span>
							<select class="wpsc-suggested-spelling-list" name="suggested_word[]">
								<option id="wpsc-suggested-spelling-1" value></option>
								<option id="wpsc-suggested-spelling-2" value></option>
								<option id="wpsc-suggested-spelling-3" value></option>
								<option id="wpsc-suggested-spelling-4" value></option>
							</select><br>
							<input name="wpsc-mass-edit[]" class="wpsc-mass-edit-chk" type="checkbox" value="" />Apply this change to the entire website
							<input type="hidden" name="suggest_page_name[]" value>
							<input type="hidden" name="suggest_page_type[]" value>
							<input type="hidden" name="suggest_old_word[]" value>
							<input type="hidden" name="suggest_old_word_id[]" value>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-suggest-button" value="Cancel">
							<!--<input type="checkbox" name="global-suggest" value="global-suggest"> Apply changes to entire website-->
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	<?php 
	//echo "debug - After Displaying Spellcheck Table: " . ($end_display - $start) . " Seconds<br />";
	//echo "debug - After Displaying Empty Field Table: " . ($end_empty - $start) . " Seconds<br />";
	}
	
	
	
	
?>