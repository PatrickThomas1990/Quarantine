<?php

class wpscx_grammar_scanner extends wpscx_scanner {
        function check_grammar($to_check) {
            global $wpdb;
            global $wpgc_options;
            $score = 0;

            $loc = dirname(__FILE__) . "/errors.pws";
            $file = fopen($loc, 'r');
            $contents = fread($file,filesize($loc));
            fclose($file);

            $contents = str_replace("\r\n", "\n", $contents);
            $error_list = explode("\n", $contents);

            foreach($error_list as $error) {
		$score += preg_match_all("/\b" . $error . "\b/i", $to_check);
            }

            return $score;
        }
        
        function check_spacing($content) {
	$count = 0;
	
	preg_match_all("/(\.|\?|\!|\,|\:|\;)([a-z]|[A-Z])/", $content, $matches);
	$count += sizeof((array)$matches);
	preg_match_all("/[A-Z].[A-Z]/",$content,$matches);
	
	return $count;
}

        
        function check_pages() {
            $start = round(microtime(true),5);
            $sql_count = 0;
            wpscx_set_global_vars();
            $page_list = null;
            global $wpgc_scan_delay;
            global $wpsc_settings;
            global $wpgc_settings;
            global $wpdb;
            global $pro_included;
            global $ent_included;
            global $base_page_max;
            $options_table = $wpdb->prefix . "spellcheck_grammar_options";
            $wpsc_options = $wpdb->prefix . "spellcheck_options";
            $page_table = $wpdb->prefix . 'posts';
            $page_count = 0;
            $error_count = 0;
            $pro_error_count = 0; 
            set_time_limit(6000); 

            $loc = dirname(__FILE__) . "/errors.pws";
            $file = fopen($loc, 'r');
            $contents = fread($file,filesize($loc));
            fclose($file);

            $contents = str_replace("\r\n", "\n", $contents);
            $error_list = explode("\n", $contents);

            $max_pages = $wpsc_settings[138]->option_value;
            //$max_pages = 5000;
            if (!$ent_included) $max_pages = $base_page_max;
            if (!$is_running) sleep($wpgc_scan_delay);

            $results_table = $wpdb->prefix . "spellcheck_grammar";

            if ($wpsc_settings[136]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
                    else { $post_status = " AND post_status='publish'"; }

            $total_pages = $max_pages;
            if ($total_pages == 0) $total_pages = PHP_INT_MAX;
            $page_list = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, ID FROM $page_table WHERE post_type='page'$post_status")); $sql_count++;

            for ($x = 0;$x < $page_list->getSize();$x++) {
                    $words_content = $page_list[$x]->post_content;
                    
                    if (strpos($words_content, '[fep_submission_form]')) continue;
                    $words_content = do_shortcode($words_content);
                    $words_content = wpscx_content_filter($words_content);
                    $words_content = wpgcx_clean_all($words_content, $wpsc_settings);

                    $score = $this->check_grammar($words_content);
                    if ($page_count < $total_pages) {
                            if ($page_list[$x]->ID != null) wpgcx_sql_insert(array("page_id" => $page_list[$x]->ID, "grammar" => $score));
                            $error_count += $score;
                    } else {
                            $pro_error_count += $score;
                    }
                    if ($page_count < $total_pages) $page_count++;
                    unset($page_list[$x]);
            }

            if ($total_pages > $max_pages) {
                    $count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_error_count';"); $sql_count++;
                    $pro_error_count += intval($count[0]->option_value);
                    $wpdb->update($options_table, array('option_value' => $pro_error_count), array('option_name' => 'pro_error_count')); $sql_count++;
            }

            $wpdb->update($options_table, array("option_value" => $page_count), array("option_name" => "pages_scanned")); $sql_count++;
            $result = $wpdb->get_results("SELECT * FROM $options_table WHERE option_name='last_scan_errors'"); $sql_count++;
            $error_results = $result[0]->option_value;
            $wpdb->update($options_table, array("option_value" => $error_count + $error_results), array("option_name" => "last_scan_errors")); $sql_count++;

            sleep(2);
            $end_time = time();
            $total_time = time_elapsed($end_time - $start_time);
            //$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_time'));
            $wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_running')); $sql_count++;
            $wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'page_running')); $sql_count++;

            $end = round(microtime(true),5);
            wpscx_print_debug("Grammar Page Content", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), $error_count);
        }
        
        function check_posts() {
            $start = round(microtime(true),5);
            $sql_count = 0;
            wpscx_set_global_vars();
            $post_list = null;
            global $wpgc_scan_delay;
            global $wpsc_settings;
            global $wpgc_settings;
            global $wpdb;
            global $ent_included;
            global $base_page_max;
            $wpsc_options = $wpdb->prefix . "spellcheck_options";
            $options_table = $wpdb->prefix . "spellcheck_grammar_options";
            $post_table = $wpdb->prefix . "posts";
            $post_count = 0;
            $error_count = 0;
            $pro_error_count = 0;
            $start_time = time();
            set_time_limit(6000); 

            if (!$is_running) sleep($wpgc_scan_delay);

            $results_table = $wpdb->prefix . "spellcheck_grammar";

            $max_pages = $wpsc_settings[138]->option_value;
            //$max_pages = 5000;
            if (!$ent_included) $max_pages = $base_page_max;

            //Get a list of all the custom post types
            $post_types = get_post_types();
                            $post_type_list = "AND (";
                            foreach ($post_types as $type) {
                                    if ($type != 'revision' && $type != 'page' && $type != 'slider' && $type != 'attachment' && $type != 'optionsframework' && $type != 'product' && $type != 'wpsc-product' && $type != 'wpcf7_contact_form' && $type != 'nav_menu_item' && $type != 'gal_display_source' && $type != 'lightbox_library' && $type != 'wpcf7s')
                                            $post_type_list .= "post_type='$type' OR ";
                            }
                            $post_type_list = trim($post_type_list, " OR ");
                            $post_type_list .= ")";

            if ($wpsc_settings[137]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
            else { $post_status = " AND post_status='publish'"; }

            $total_pages = $max_pages;
            if ($total_pages == 0) $total_pages = PHP_INT_MAX;
            $posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, ID FROM $post_table WHERE post_type = 'post'" . $post_status . $post_type_list)); $sql_count++;

            for ($x = 0;$x < $posts_list->getSize();$x++) {
                    $words_content = $posts_list[$x]->post_content;

                    if (strpos($words_content, '[fep_submission_form]')) continue;
                    $words_content = do_shortcode($words_content);
                    $words_content = wpscx_content_filter($words_content);
                    $words_content = wpgcx_clean_all($words_content, $wpsc_settings);

                    $score = $this->check_grammar($words_content);

                    if ($post_count < $total_pages) {
                            if ($posts_list[$x]->ID != null) wpgcx_sql_insert(array("page_id" => $posts_list[$x]->ID, "grammar" => $score));
                            $error_count += $score;
                    } else {
                            $pro_error_count += $score;
                    }
                    if ($post_count < $total_pages) $post_count++;
                    unset($posts_list[$x]);
            }

            if ($total_pages > $max_pages) {
                    $count = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_error_count';"); $sql_count++;
                    $pro_error_count += intval($count[0]->option_value);
                    $wpdb->update($options_table, array('option_value' => $pro_error_count), array('option_name' => 'pro_error_count')); $sql_count++;
            }

            $wpdb->update($options_table, array("option_value" => $post_count), array("option_name" => "posts_scanned")); $sql_count++;
            $result = $wpdb->get_results("SELECT * FROM $options_table WHERE option_name='last_scan_errors'"); $sql_count++;
            $error_results = $result[0]->option_value;
            $wpdb->update($options_table, array("option_value" => $error_count + $error_results), array("option_name" => "last_scan_errors")); $sql_count++;

            sleep(2);
            $end_time = time();
            $total_time = time_elapsed($end_time - $start_time);
            //$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_time'));
            $wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_running')); $sql_count++;
            $wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'post_running')); $sql_count++;

            $end = round(microtime(true),5);
            wpscx_print_debug("Grammar Post Content", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), $error_count);
        }
        
        function scan_site() {
            wpgcx_set_global_vars();
            global $wpdb;
            global $wpgc_options;
            $options_table = $wpdb->prefix . "spellcheck_grammar_options";
            wpgcx_clear_results(); //Clear out results table in preparation for a new scan
            
            $start = time();
            $wpdb->update($options_table, array("option_value" => $start), array("option_name" => "scan_start_time")); $sql_count++;
            
            $this->check_pages();
            $this->check_posts();
        }
        
        function scan_individual() {
            wpscx_set_global_vars();
            global $wpgc_options;
            global $wpdb;
            $results_table = $wpdb->prefix . "spellcheck_grammar";

            $post = get_post($page_id); //Get the post/page

            $words_content = $post->post_content; //Get the content from the postpage

            //Clean up the content
            $words_content = do_shortcode($words_content);
            $words_content = wpscx_content_filter($words_content);
            $words_content = wpgcx_clean_all($words_content, $wpsc_settings);

            $score = $this->check_grammar($words_content); //Get the grammar scores

            wpgcx_sql_insert(array("page_id" => $post->ID, "grammar" => $score)); //Insert into database for the on page editor
        }
}
