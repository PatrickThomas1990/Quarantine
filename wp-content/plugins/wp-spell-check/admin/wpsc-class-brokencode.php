<?php

class wpscx_broken_code_scanner extends wpscx_scanner {
    function clean_all($content) {
        $content = wpscx_script_cleanup($content);
        if ($wpsc_settings[23]->option_value == 'true') {
                $content = wpscx_email_cleanup($content);
        }

        if ($wpsc_settings[24]->option_value == 'true') {
                $content = wpscx_website_cleanup($content);
        }

        return $content;
    }
    
    function wpscx_scan_all_eps() {
        $start = round(microtime(true),5);
		$sql_count = 0;
		$page_list = null;
		global $scan_delay;
		global $ent_included;
		global $wpsc_settings;
		if (sizeof((array)$wpsc_settings) < 1) wpscx_set_global_vars();
		//if (!$is_running) sleep($scan_delay);
		
		ini_set('memory_limit','1024M'); //Sets the PHP memory limit
		set_time_limit(600); 
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'spellcheck_html';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table = $wpdb->prefix . 'posts';
		
		$max_pages = intval($wpsc_settings[138]->option_value);

		$total_words = 0;
		$page_count = 0;
		$post_count = 0;
		$word_count = 0;
		$error_count = 0;
		
		wpscx_set_global_vars();
		
		if ($wpsc_settings[136]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
		else { $post_status = " AND post_status='publish'"; }
		
		$page_list = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content FROM $page_table WHERE (post_type='page' OR post_type='post')$post_status")); $sql_count++;
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		}
			$ind_start_time = time();
		
		$max_time = ini_get('max_execution_time'); 
		
		$divi_check = wp_get_theme();
		
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);

		for ($x = 0;$x < $page_list->getSize();$x++) {
			if ($page_list[$x]->post_type == "page" ) { $page_count++; } else { $post_count++; }
			
			//if ($page_list[$x]->ID == 2624) print_r("<code>" . $words_content . "</code>");
			
			$words_content = $page_list[$x]->post_content;
                        if (strpos($words_content, '[fep_submission_form]')) continue;
			$words_content = do_shortcode($words_content);
			$words_content = wpscx_content_filter($words_content);
			$words_content = wpbcx_clean_all($words_content, $wpsc_settings);
			
			//if ($page_list[$x]->post_title == 'Resources') print_r($words_content);

			if (sizeof((array)$html_errors) != 0) {
				//print_r("<br>" . $page_list[$x]->post_title . " | " . print_r($html_errors));
				foreach($html_errors as $html_error) {
					if ($html_error[0] != '') {
						$hold = new SplFixedArray(1);
						$hold[0] = $html_error[0];
					
						$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
						$error_list[$error_count] = $hold;
						
						$error_count++;
					}
				}
			}
			
			preg_match_all('/\[.*?\]/', $words_content, $shortcode_errors);

			if (sizeof((array)$shortcode_errors) != 0) {
				//print_r("<br>" . $page_list[$x]->post_title . " | " . print_r($shortcode_errors));
				foreach($shortcode_errors as $shortcode_error) {		
					if ($shortcode_error[0] != '' && strpos($shortcode_error[0], 'vc') === false) {
						$hold = new SplFixedArray(1);
						$hold[0] = $shortcode_error[0];
						
						$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
						$error_list[$error_count] = $hold;
						$error_count++;
					}
				}
			}
			unset($page_list[$x]);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Broken Code EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
		
		return $error_list->getSize();
    }
}