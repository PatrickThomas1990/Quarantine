<?php
if(!defined('ABSPATH')) { exit; }
/*
	Works in the background: yes
	Pro version scans the entire website: yes
	Sends email reminders: yes
	Finds place holder text: yes
	Custom Dictionary for unusual words: yes
	Scans Password Protected membership Sites: yes
	Unlimited scans on my website: Yes


	Scans Categories: Yes WP Spell Check Pro
	Scans SEO Titles: Yes WP Spell Check Pro
	Scans SEO Descriptions: Yes WP Spell Check Pro
	Scans WordPress Menus: Yes WP Spell Check Pro
	Scans Page Titles: Yes WP Spell Check Pro
	Scans Post Titles: Yes WP Spell Check Pro
	Scans Page slugs: Yes WP Spell Check Pro
	Scans Post Slugs: Yes WP Spell Check Pro
	Scans Post categories: Yes WP Spell Check Pro

	Privacy URI: https://www.wpspellcheck.com/privacy-policy/
	Pro Add-on / Home Page: https://www.wpspellcheck.com/
	Pro Add-on / Prices: https://www.wpspellcheck.com/pricing/
*/

	function wpscx_check_page_title_empty($rng_seed, $is_running = false, $log_debug = true) {	
		$scanner = new wpscx_seo_scanner;
                $scanner->check_page_title_empty();
	}
	add_action('admincheckpagetitlesemptybase', 'wpscx_check_page_title_empty');
	
	function wpscx_check_post_title_empty($rng_seed, $is_running = false, $log_debug = true) {	
		$scanner = new wpscx_seo_scanner;
                $scanner->check_post_title_empty();
	}
	add_action('admincheckposttitlesemptybase', 'wpscx_check_post_title_empty');
	
	function wpscx_check_author_empty($rng_seed) {
		$scanner = new wpscx_seo_scanner;
                $scanner->check_author_empty();
	}
	add_action ('admincheckauthorsempty', 'wpscx_check_author_empty');
	
	function wpscx_clear_results_empty() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'pro_word_count')); //$ Clear out the pro errors count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'empty_checked')); //$ Clear out the total empty field count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'page_count')); //$ Clear out the page count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'post_count')); //$ Clear out the post count
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'media_count')); //$Clear out the media count

		$wpdb->delete($table_name, array('ignore_word' => false));
	}
	
	function wpscx_clear_empty_scan() {
		global $wpdb;
		global $wpsc_settings;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'entire_empty_scan'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_author_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_page_title_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_post_title_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_menu_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_page_seo_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_post_seo_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_media_seo_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_media_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_ecommerce_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_tag_desc_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'empty_cat_desc_sip'));
	}
	
	function wpscx_set_empty_scan_in_progress($rng_seed = 0) {
		global $wpdb;
		global $pro_included;
		global $ent_included;
		global $wpsc_settings;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'entire_empty_scan'));
		
		$settings = $wpsc_settings;

		
		if ($settings[47]->option_value =='true') {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_author_sip'));
		if ($settings[49]->option_value =='true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_page_title_sip'));
		if ($settings[50]->option_value =='true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_post_title_sip'));
			
		if ($ent_included || $pro_included) {
		if ($settings[48]->option_value =='true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_menu_sip'));
		if ($settings[53]->option_value =='true') {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_page_seo_sip'));
		}
		if ($settings[54]->option_value =='true') {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_post_seo_sip'));
		}
		if ($settings[55]->option_value =='true') {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_media_seo_sip'));
		}
		if ($settings[56]->option_value =='true') {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_media_sip'));
		}
		if ($settings[57]->option_value =='true') {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_ecommerce_sip'));
		}
		if ($settings[51]->option_value =='true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_tag_desc_sip'));
		if ($settings[52]->option_value =='true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_cat_desc_sip'));
		}
		}
	}
	
	function wpscx_clear_events_empty() {
		$time = wp_next_scheduled('admincheckmenusempty_ent');
		wp_unschedule_event($time, 'admincheckmenusempty_ent');
		
		$time = wp_next_scheduled('admincheckpagetitlesempty_ent');
		wp_unschedule_event($time, 'admincheckpagetitlesempty_ent');
		
		$time = wp_next_scheduled('admincheckposttitlesempty_ent');
		wp_unschedule_event($time, 'admincheckposttitlesempty_ent');
		
		$time = wp_next_scheduled('admincheckpostseoempty_ent');
		wp_unschedule_event($time, 'admincheckpostseoempty_ent');
		
		$time = wp_next_scheduled('admincheckmediaseoempty_ent');
		wp_unschedule_event($time, 'admincheckmediaseoempty_ent');
		
		$time = wp_next_scheduled('admincheckmediaempty');
		wp_unschedule_event($time, 'admincheckmediaempty');
		
		$time = wp_next_scheduled('admincheckecommerceempty_ent');
		wp_unschedule_event($time, 'admincheckecommerceempty_ent');
		
		$time = wp_next_scheduled('admincheckposttagsdescempty_ent');
		wp_unschedule_event($time, 'admincheckposttagsdescempty_ent');
		
		$time = wp_next_scheduled('admincheckcategoriesdescempty_ent');
		wp_unschedule_event($time, 'admincheckcategoriesdescempty_ent');
		
		$time = wp_next_scheduled('admincheckauthorsempty');
		wp_unschedule_event($time, 'admincheckauthorsempty');
		
		$time = wp_next_scheduled('admincheckmenusempty');
		wp_unschedule_event($time, 'admincheckmenusempty');
		
		$time = wp_next_scheduled('admincheckpagetitlesempty');
		wp_unschedule_event($time, 'admincheckpagetitlesempty');
		
		$time = wp_next_scheduled('admincheckposttitlesempty');
		wp_unschedule_event($time, 'admincheckposttitlesempty');
		
		$time = wp_next_scheduled('admincheckpostseoempty');
		wp_unschedule_event($time, 'admincheckpostseoempty');
		
		$time = wp_next_scheduled('admincheckmediaseoempty');
		wp_unschedule_event($time, 'admincheckmediaseoempty');
		
		$time = wp_next_scheduled('admincheckmediaempty_pro');
		wp_unschedule_event($time, 'admincheckmediaempty_pro');
		
		$time = wp_next_scheduled('admincheckecommerceempty');
		wp_unschedule_event($time, 'admincheckecommerceempty');
		
		$time = wp_next_scheduled('admincheckposttagsdescempty');
		wp_unschedule_event($time, 'admincheckposttagsdescempty');
		
		$time = wp_next_scheduled('admincheckcategoriesdescempty');
		wp_unschedule_event($time, 'admincheckcategoriesdescempty');
		
		$time = wp_next_scheduled('admincheckpagetitlesemptybase');
		wp_unschedule_event($time, 'admincheckpagetitlesemptybase');
		
		$time = wp_next_scheduled('admincheckposttitlesemptybase');
		wp_unschedule_event($time, 'admincheckposttitlesemptybase');
	}
	
	
	function wpscx_scan_site_empty($rng_seed = 0) {
		$start = round(microtime(true),5);
		$sql_count = 0;
		global $wpdb;
		global $pro_included;
		global $ent_included;
		
		//wpsc_clear_events_empty(); //Clear out the event scheduler of any previous empty field events
		
		$table_name = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		set_time_limit(600); //$ Set PHP timeout limit
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'empty_scan_in_progress')); $sql_count++;
		$start_time = time(); 
		$wpdb->update($options_table, array('option_value' => $start_time), array('option_name' => 'scan_start_time'));  $sql_count++;

		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table); //4 = Pages, 5 = Posts, 6 = Theme, 7 = Menus
		
		if ($ent_included) {
		if ($settings[48]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckmenusempty_ent', array ($rng_seed, true ));
		if ($settings[49]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckpagetitlesempty_ent', array ($rng_seed, true ));
		if ($settings[50]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckposttitlesempty_ent', array ($rng_seed, true ));
		if ($settings[53]->option_value =='true') {
			wp_schedule_single_event(time(), 'admincheckpageseoempty_ent', array ($rng_seed, true ));
		}
		if ($settings[54]->option_value =='true') {
			wp_schedule_single_event(time(), 'admincheckpostseoempty_ent', array ($rng_seed, true ));
		}
		if ($settings[55]->option_value =='true') {
			wp_schedule_single_event(time(), 'admincheckmediaseoempty_ent', array ($rng_seed, true ));
		}
		if ($settings[56]->option_value =='true') {
			wp_schedule_single_event(time(), 'admincheckmediaempty_ent', array ($rng_seed, true ));
		}
		if ($settings[57]->option_value =='true') {
			wp_schedule_single_event(time(), 'admincheckecommerceempty_ent', array ($rng_seed, true ));
		}
		if ($settings[51]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckposttagsdescempty_ent', array ($rng_seed, true ));
		if ($settings[52]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckcategoriesdescempty_ent', array ($rng_seed, true ));
		if ($settings[47]->option_value =='true') {
			wp_schedule_single_event(time(), 'admincheckauthorsempty', array ($rng_seed , false));
		}
		} else {
			if ($settings[47]->option_value =='true') {
				wp_schedule_single_event(time(), 'admincheckauthorsempty', array ($rng_seed, true ));
			}
			if ($settings[49]->option_value =='true')
				wp_schedule_single_event(time(), 'admincheckpagetitlesemptybase', array ($rng_seed, true ));
			if ($settings[50]->option_value =='true')
				wp_schedule_single_event(time(), 'admincheckposttitlesemptybase', array ($rng_seed, true ));
		}
		
		if (!$ent_included) wpscx_check_empty_wpsc();
		
		$end = round(microtime(true),5);
		//////$loc = dirname(__FILE__)."/../../../../debug.log";
		////////$debug_file = fopen($loc, 'a');
		////////$debug_var = fwrite( $debug_file, "Initialization Time: " . round($end - $start,5) . ".     SQL: " . $sql_count . "     Memory: " . round(memory_get_usage() / 1000,5) . " \r\n" );
		////////fclose($debug_file);
	}
	add_action ('adminscansiteempty', 'wpscx_scan_site_empty');
	
	function wpscx_check_empty_wpsc() {
            $scanner = new wpscx_seo_scanner;
            $scanner->check_empty_wpsc();
	}
	add_action ('admincheckemptywpsc', 'wpscx_check_empty_wpsc');
?>