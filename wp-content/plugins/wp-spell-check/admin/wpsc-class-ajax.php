<?php

class wpscx_ajax {
    
     function __construct() {}
   
    
    function wphcx_scan_function() {
		require_once( 'wpsc-framework.php' );
		
		global $wpdb;
		global $wpsc_settings;
		
		$scan_in_progress = false;
		
		if ($wpsc_settings[141]->option_value == 'true') $scan_in_progress = true;
		
		if (!$scan_in_progress) {
			echo "false";
		} else {
			echo "true";
		}
		die();
	}
	
	function wpscx_finish_html_scan() {
		sleep(3);
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		
		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);

		
		$time = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='html_scan_start_time'");
		$time = $time[0]->option_value;
		$end_time = time();
		$total_time = time_elapsed($end_time - $time);
		
		$loc = dirname(__FILE__) . "/debug.log";
		////$debug_file = fopen($loc, 'a');
		////$debug_var = fwrite( $debug_file, "Start Time: $time | End Time: $end_time | Total Time: $total_time \r\n" );
		////fclose($debug_file);
		
		//$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'html_last_scan_time'));
	}
	
	function wpscx_finish_scan() {
		$start = round(microtime(true),5);
		$sql_count = 0;
		sleep(3);
		global $wpdb;
		global $ent_included;
                global $wpsc_version;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
                $error_table = $wpdb->prefix . "spellcheck_errors";
		
		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);
                $error_count = $wpdb->get_results("SELECT * FROM $error_table");
                if (sizeof($error_count) >= 1) $wpdb->update($options_table, array('option_value' => 'Show Message'), array('option_name' => 'last_php_error')); $sql_count++;
		if ($settings[45]->option_value != "Entire Site") return false;

			if ($settings[0]->option_value == 'true') {
                            $emailer = new wpscx_email;
                            $emailer->email_admin();
                        }
		
			$total_word = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count'");$sql_count++;
			$total_words = $total_word[0]->option_value;
		
			$word_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" );$sql_count++;
		
			$literacy_factor = 0;
			if ($total_words > 0) { $literacy_factor = (($total_words - $word_count) / $total_words) * 100;
			} else { $literacy_factor = 100; }
			$literacy_factor = number_format(floor((float)$literacy_factor * 100) / 100, 2, '.', '');
			
			$time = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='scan_start_time'");$sql_count++;
			$time = $time[0]->option_value;
			$end_time = time();
			$total_time = time_elapsed($end_time - $time);
			
		
			$wpdb->update($options_table, array('option_value' => $literacy_factor), array('option_name' => 'literary_factor')); $sql_count++;
			$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'entire_scan'));$sql_count++;
			$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished'));$sql_count++;
			
			if ($ent_included) {
				$end = round(microtime(true),5);
				$total_time = round($end - $start, 5);
				wpscx_print_debug_end("$wpsc_version Spell Check Pro",$total_time);
			} else {
				$end = round(microtime(true),5);
				$total_time = round($end - $start, 5);
				wpscx_print_debug_end("$wpsc_version Spell Check Base",$total_time);
			}
	}
	
	function wpscx_finish_empty_scan() {
		$start = round(microtime(true),5);
		sleep(3);
		$sql_count = 0;
		global $wpdb;
		global $ent_included;
                global $wpsc_version;
		$table_name = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		
		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table); $sql_count++;
		if ($settings[63]->option_value != "Entire Site") return false;

		
		if ($settings[100]->option_value == 'true') {
			//if ($settings[0]->option_value == 'true')
			//email_admin();
			
			$total_fields =  $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='empty_checked'"); $sql_count++;
			$total_fields = $total_fields[0]->option_value;
			$empty_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" ); $sql_count++;
		
			$empty_factor = 0;
			if ($total_fields > 0) { $empty_factor = (($total_fields - $empty_count) / $total_fields) * 100;
			} else { $empty_factor = 100; }
			if ($empty_factor < 0) $empty_factor = 0;
			$empty_factor = number_format((float)$empty_factor, 2, '.', '');
		
			$wpdb->update($options_table, array('option_value' => $empty_factor), array('option_name' => 'empty_factor')); $sql_count++;
			$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'entire_empty_scan'));$sql_count++;
			
			$time = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='scan_start_time'"); $sql_count++;
			$time = $time[0]->option_value;
			
			$end_time = time();
			$total_time = time_elapsed($end_time - $time);
			$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'empty_start_time')); $sql_count++;
			
			if ($ent_included) {
				$end = round(microtime(true),5);
				$total_time = round($end - $start, 5);
				wpscx_print_debug_end("$wpsc_version SEO Check Pro",$total_time);
			} else {
				$end = round(microtime(true),5);
				$total_time = round($end - $start, 5);
				wpscx_print_debug_end("$wpsc_version SEO Check Base",$total_time);
			}
		}
	}

	function wpscx_scan_function() {
		require_once( 'wpsc-framework.php' );
		
		global $wpdb;
		global $wpsc_settings;
		
		$scan_in_progress = false;
		
		if ($wpsc_settings[66]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[67]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[68]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[69]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[70]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[71]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[72]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[73]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[74]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[75]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[76]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[77]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[78]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[79]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[80]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[81]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[82]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[83]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[84]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[85]->option_value == 'true') $scan_in_progress = true;
		
		if (!$scan_in_progress) {
			echo "false";
		} else {
			echo "true";
		}
		die();
	}
	
	function wpscx_empty_scan_function() {
		require_once( 'wpsc-framework.php' );
	
		global $wpdb;
		global $wpsc_settings;
		
		$scan_in_progress = false;
		
		if ($wpsc_settings[87]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[88]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[89]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[90]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[91]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[92]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[93]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[94]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[95]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[96]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[97]->option_value == 'true') $scan_in_progress = true;
		if ($wpsc_settings[98]->option_value == 'true') $scan_in_progress = true;
		
		
		if (!$scan_in_progress) {
			echo "false";
		} else {
			echo "true";
		}
		die();
	}
        
        function wpscx_start_scan_empty() {
            require_once( 'wpsc-framework.php' );
            
            global $wpdb;
            $options_table = $wpdb->prefix . "spellcheck_options";
            global $ent_included;
		
            $settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);
            
            $type = $_POST['type'];
            
            if ($type == 'Menus') {
                $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Menus</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                wpscx_clear_empty_results();
                $rng_seed = rand(0,999999999);
                
                $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_menu_sip'));
                $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                $wpdb->update($options_table, array('option_value' => 'Menus'), array('option_name' => 'last_empty_type'));
                
                sleep(3);
                if ($ent_included) { 
                wp_schedule_single_event(time(), 'admincheckmenusempty_ent', array ($rng_seed ));
                } else {
                wp_schedule_single_event(time(), 'admincheckmenusempty', array ($rng_seed ));
                }
            } elseif ($type == 'Page Titles') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Page Titles</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);

                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_page_title_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Page Titles'), array('option_name' => 'last_empty_type'));
                    
                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckpagetitlesempty_ent', array ($rng_seed ));
                    } elseif ($pro_included) {
                    wp_schedule_single_event(time(), 'admincheckpagetitlesempty', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckpagetitlesemptybase', array ($rng_seed ));
                    }
            } elseif ($type == 'Post Titles') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Post Titles</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);

                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_post_title_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Post Titles'), array('option_name' => 'last_empty_type'));
                    
                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckposttitlesempty_ent', array ($rng_seed ));
                    } elseif ($pro_included) {
                    wp_schedule_single_event(time(), 'admincheckposttitlesempty', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckposttitlesemptybase', array ($rng_seed ));
                    }
            } elseif ($type == 'Tag Descriptions') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Tag Descriptions</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);

                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_tag_desc_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Tag Descriptions'), array('option_name' => 'last_empty_type'));
                    
                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckposttagsdescempty_ent', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckposttagsdescempty', array ($rng_seed ));
                    }
            } elseif ($type == 'Category Descriptions') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Category Descriptions</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);

                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_cat_desc_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Category Descriptions'), array('option_name' => 'last_empty_type'));

                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckcategoriesdescempty_ent', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckcategoriesdescempty', array ($rng_seed ));
                    }
            } elseif ($type == 'Media Files') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Media Files</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);

                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_media_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Media Files'), array('option_name' => 'last_empty_type'));

                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckmediaempty_ent', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckmediaempty_pro', array ($rng_seed ));
                    }
            } else if ($type == 'WooCommerce and WP-eCommerce Products') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">eCommerce Products</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);

                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_ecommerce_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'eCommerce Products'), array('option_name' => 'last_empty_type'));

                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckecommerceempty_ent', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckecommerceempty', array ($rng_seed ));
                    }
            } elseif ($type == 'Authors') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Authors</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_author_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Authors'), array('option_name' => 'last_empty_type'));
                    
                    sleep(3);
                    wp_schedule_single_event(time(), 'admincheckauthorsempty', array ($rng_seed ));
            } else if ($type == 'Page SEO') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Page SEO</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);

                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_page_seo_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Page SEO'), array('option_name' => 'last_empty_type'));

                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckpageseoempty_ent', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckpageseoempty', array ($rng_seed ));
                    }
            } elseif ($type == 'Post SEO') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Post SEO</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_post_seo_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Post SEO'), array('option_name' => 'last_empty_type'));

                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckpostseoempty_ent', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckpostseoempty', array ($rng_seed ));
                    }
            } else if ($type == 'Media Files SEO') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(115, 1, 154); font-weight: bold;">Media Files SEO</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
                    wpscx_clear_empty_results();
                    $rng_seed = rand(0,999999999);

                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_media_seo_sip'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Media Files SEO'), array('option_name' => 'last_empty_type'));

                    sleep(3);
                    if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckmediaseoempty_ent', array ($rng_seed ));
                    } else {
                    wp_schedule_single_event(time(), 'admincheckmediaseoempty', array ($rng_seed ));
                    }
            } elseif ($type == 'Entire Site') {
                    $empty_scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for the <span style="color: rgb(115, 1, 154); font-weight: bold;">Entire Site</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';

                    wpscx_clear_results_empty("full");
                    $rng_seed = rand(0,999999999);
                    wpscx_set_empty_scan_in_progress($rng_seed);
                    $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress'));
                    $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date'));
                    $wpdb->update($options_table, array('option_value' => 'Entire Site'), array('option_name' => 'last_empty_type'));
                    
                    sleep(3);
                    wp_schedule_single_event(time(), 'adminscansiteempty', array($rng_seed));
            }
        
        echo $empty_scan_message;
        die();
        }
        
        function wpscx_start_scan_grammar() {
            require_once( 'wpsc-framework.php' );
            
            global $wpdb;
            $options_table = $wpdb->prefix . "spellcheck_grammar_options";
            global $ent_included;
		
            $settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);
            
            $type = $_POST['type'];
            
            if ($type == 'Posts') {
		wpgcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
		
		$wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_running'));
		$wpdb->update($options_table, array('option_value' => 'Posts'), array('option_name' => 'last_scan_type'));
		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Posts</span>. The page will automatically refresh when the scan has finished.';
		
                sleep(3);
		wp_schedule_single_event(time(), 'wpgcx_check_posts', array ($rng_seed, true));
            } elseif ($type == 'Pages') {
		wpgcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);

		$wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_running'));
		$wpdb->update($options_table, array('option_value' => 'Pages'), array('option_name' => 'last_scan_type'));
		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Pages</span>. The page will automatically refresh when the scan has finished.';
		
                sleep(3);
		wp_schedule_single_event(time(), 'wpgcx_check_pages', array ($rng_seed, true));
            } elseif ($type == 'Entire Site') {	
                wpgcx_clear_results(); //Clear out results table in preparation for a new scan
                $rng_seed = rand(0,999999999);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. The page will automatically refresh when the scan has finished.';
		
                $wpdb->update($options_table, array('option_value' => 0), array('option_name' => 'pro_error_count'));
		$wpdb->update($options_table, array("option_value" => '0'), array("option_name" => "last_scan_errors"));
		$wpdb->update($options_table, array('option_value' => 'Entire Site'), array('option_name' => 'last_scan_type'));		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_running'));
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_running'));
		
                sleep(3);
		wp_schedule_single_event(time(), 'wpgcx_scan_site', array ($rng_seed, true));
            }
            
            echo $scan_message;
            die();
        }
        
        function wpscx_start_scan_bc() {
            require_once( 'wpsc-framework.php' );
            
            global $wpdb;
            $options_table = $wpdb->prefix . "spellcheck_options";
            global $ent_included;
		
            $settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);
            
            $type = $_POST['type'];
            
            if ($type == 'Entire Site') {
		wphcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);
                
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'html_scan_running'));
		$wpdb->update($options_table, array("option_value" => time()), array("option_name" => "html_scan_start_time"));
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. The page will automatically refresh when the scan has finished.';
		
                sleep(3);
		wp_schedule_single_event(time(), 'admincheckcode', array ($rng_seed, true));
            } elseif ($type == 'Broken HTML') {
		wphcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);

		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'html_scan_running'));
		$wpdb->update($options_table, array("option_value" => time()), array("option_name" => "html_scan_start_time"));
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Broken HTML</span>. The page will automatically refresh when the scan has finished.';
		
                sleep(3);
		wp_schedule_single_event(time(), 'admincheckhtml', array ($rng_seed, true));
            } elseif ($type == 'Broken Shortcodes') {
		wphcx_clear_results(); //Clear out results table in preparation for a new scan
		$rng_seed = rand(0,999999999);

		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'html_scan_running'));
		$wpdb->update($options_table, array("option_value" => time()), array("option_name" => "html_scan_start_time"));
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Broken Shortcodes</span>. The page will automatically refresh when the scan has finished.';
		
                sleep(3);
		wp_schedule_single_event(time(), 'admincheckshortcode', array ($rng_seed, true));
            }
            
            echo $scan_message;
            die();
        }
        
        function wpscx_start_scan() {
            require_once( 'wpsc-framework.php' );
            
            global $wpdb;
            $options_table = $wpdb->prefix . "spellcheck_options";
            global $ent_included;
            $options_table = $wpdb->prefix . 'spellcheck_options';
		
            $settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);
            
            $type = $_POST['type'];
            
            //echo "|$type|";
            
            if ($type == "Pages") {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Page Content'), array('option_name' => 'last_scan_type')); $sql_count++;
                sleep(3);
		if ($ent_included) { 
                    wp_schedule_single_event(time(), 'admincheckpages_ent', array ($rng_seed ));
		} else {
                    wp_schedule_single_event(time(), 'admincheckpages', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Page Content</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Posts') {
                wpscx_clear_results();
                $rng_seed = rand(0,999999999);

                $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_sip')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => 'Post Content'), array('option_name' => 'last_scan_type')); $sql_count++;
                sleep(3);
                if ($ent_included) { 
                wp_schedule_single_event(time(), 'admincheckposts_ent', array ($rng_seed ));
                } else {
                wp_schedule_single_event(time(), 'admincheckposts', array ($rng_seed ));
                }
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Post Content</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Authors') {
                wpscx_clear_results();
                $rng_seed = rand(0,999999999);
                
                $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'author_sip')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => 'Authors'), array('option_name' => 'last_scan_type')); $sql_count++;
                sleep(3);
                wp_schedule_single_event(time(), 'admincheckauthors', array ($rng_seed));
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Authors</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Menus') {
                wpscx_clear_results();
                $rng_seed = rand(0,999999999);
                
                $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'menu_sip')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
                $wpdb->update($options_table, array('option_value' => 'Menus'), array('option_name' => 'last_scan_type')); $sql_count++;
                sleep(3);
                if ($ent_included) { 
                wp_schedule_single_event(time(), 'admincheckmenus_ent', array ($rng_seed ));
                } else {
                wp_schedule_single_event(time(), 'admincheckmenus', array ($rng_seed ));
                }
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Menus</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Tags') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'tag_title_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Tag Titles'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttags_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckposttags', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Tags</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Categories') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'cat_title_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Category Titles'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckcategories_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckcategories', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Categories</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'SEO Descriptions') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'seo_desc_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'SEO Descriptions'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckseodesc_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckseodesc', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">SEO Descriptions</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'SEO Titles') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		
		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'seo_title_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'SEO Titles'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckseotitles_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckseotitles', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">SEO Titles</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Sliders') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);

		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'slider_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Sliders'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'adminchecksliders_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'adminchecksliders_pro', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Sliders</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Media Files') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);

		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'media_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Media Files'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckmedia_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckmedia_pro', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Media Files</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'WooCommerce and WP-eCommerce Products') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);

		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'ecommerce_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'eCommerce Products'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckecommerce_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckecommerce', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">eCommerce Products</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Widgets') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
                
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'ecommerce_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Widgets'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'wpsccheckwidgets', array ($rng_seed ));
		}
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Widgets</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Contact Form 7') {
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'cf7_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Contact Form 7'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
		
		wp_schedule_single_event(time(), 'admincheckcf7', array ($rng_seed, false));
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Contact Form 7</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            } elseif ($type == 'Entire Site') {
		wpscx_clear_results("full");
		$rng_seed = rand(0,999999999);
		
                wpscx_set_scan_in_progress($rng_seed);
                if ($settings[4]->option_value == 'true') $wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_sip'));
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Entire Site'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(3);
                
		wp_schedule_single_event(time(), 'adminscansite', array($rng_seed, $log_debug));
                
                echo '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for the <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
            }
        
        die();
        }
        
        function _ajax_fetch_wpsc_list_callback() {
 
            $wp_list_table = new sc_table();
            $wp_list_table->ajax_response();
        }
}