<?php

class wpscx_spellcheck_scanner extends wpscx_scanner { 
    function check_pages($log_errors = false) {
        $end = round(microtime(true),5);
		////$loc = dirname(__FILE__)."/../../../../debug.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Page Content Start Time: " . date("g:i:sA") . "\r\n" );
		//fclose($debug_file);
        
                //$error = new asdrfasdfasdf;

		$start = round(microtime(true),5);
		$start_debug = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(6000); 
		global $wpdb;
		//global $wpsc_haystack;
		global $ignore_list;
		global $wpsc_settings;
		global $base_page_max;
                global $wpscx_title;
		$timer_init = 0; //Initialization
		$timer_ignore = 0; //Ignore Page
		$timer_email = 0; //Ignore Emails if needed
		$timer_website = 0; //Ignore websites if needed
		$timer_upper = 0; //Ignore uppercase words if needed
		$timer_spellcheck = 0; //Spellcheck the word
		$timer_cleanup = 0; //Cleanup words before checking them
		$timer_errors = 0; //Add errors to database
		$timer_final = 0; //Finalization
		
		$start_time = time();
		wpscx_set_global_vars();
		
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table = $wpdb->prefix . 'posts';
		$max_pages = $base_page_max;
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
			$contents = file_get_contents($loc);
	
			$contents = str_replace("\r\n", "\n", $contents);
			$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);

		global $pro_included;
		$total_pages = $max_pages;
		$total_words = 0;
		$page_count = 0;
		$word_count = 0;
		$error_count = 0;
		
			if ($wpsc_settings[136]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
			else { $post_status = " AND post_status='publish'"; }
		
		$page_list = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, post_name, ID FROM $page_table WHERE post_type='page'$post_status"));
		$sql_count++;
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
		}
			$ind_start_time = time();
		
		$max_time = ini_get('max_execution_time'); 

		$ignore_pages = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');
		$sql_count++;
		
		global $ignore_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		
		$timer_init = round(microtime(true),5) - $start;

		for ($x = 0; $x < $page_list->getSize(); $x++) {
			if ($page_list[$x]->ID == 10348) continue;
                        
                        $loc = dirname(__FILE__)."/debug.log";
                        $debug_file = fopen($loc, 'a');
                        $debug_var = fwrite( $debug_file, "Page Name: " . $page_list[$x]->post_title . "\r\n" );
                        fclose($debug_file);
		
			$start_timer = round(microtime(true),5);
			$ignore_flag = 'false';
			foreach($ignore_pages as $ignore_check) {
				if (strtoupper(trim($page_list[$x]->post_title)) == strtoupper(trim($ignore_check->keyword))) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$page_count++;
                        $wpscx_title = $page_list[$x]->post_title;
			
			$timer_ignore += round(microtime(true),5) - $start_timer;
			
			$words_content = $page_list[$x]->post_content;
                        if (strpos($words_content, '[fep_submission_form]')) continue;
                        try { $words_content = do_shortcode($words_content); } catch (Exception $e) {}
			$words_content = wpscx_content_filter($words_content);
			
			$words_content = wpscx_clean_all($words_content, $wpsc_settings);
			
			$words = explode(" ", $words_content);
				
			//$start_debug = round(microtime(true),5);
			
			$timer_page_cleanup += round(microtime(true),5) - $start_timer;

			foreach($words as $word) {
				$start_timer = round(microtime(true),5);
				
				$total_words++;
				$word = trim($word, "'`”“");
				
				if ($word == "") continue;
				if ($wpsc_haystack[strtoupper($word)] != 1) {
					$in_dictionary = false;
					
					if (!$in_dictionary) {
						$start_timer = round(microtime(true),5);
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && !is_numeric($word)) {
							$timer_upper += round(microtime(true),5) - $start_timer;
							if ($page_count <= $total_pages) {
								//$word = addslashes($word);
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $page_list[$x]->post_title;
								$hold[2] = $page_list[$x]->ID;
								$hold[3] = "Page Content";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
							} else {
								$pro_error_count++;
							}
						} else { $timer_upper += round(microtime(true),5) - $start_timer; }
					}
				} else { $timer_spellcheck += round(microtime(true),5) - $start_timer; }
			}
			unset($page_list[$x]);
		}
		
		if ($log_errors) {
			$end = round(microtime(true),5);
			wpscx_print_debug("Page Content EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), $pro_error_count);
			return $pro_error_count;
		}
		
		
		
		
		if (!$end_task) {
			if ($page_count > $max_pages) $counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");
			$word_count = $word_count + intval($counter[0]->option_value);
			
			$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");
			$total_words = $total_words + intval($counter[0]->option_value);
			$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));
			if ($page_count > $total_pages) $page_count = $total_pages;
			$wpdb->update($options_table, array('option_value' => $page_count), array('option_name' => 'page_count'));
			$sql_count += 4;
			
			
			wpscx_sql_insert($error_list, 'Page Content');
			
				if ($is_running != true) wpscx_finalize($start_time);
			}
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'page_sip'));
		$sql_count++;
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Page Content", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
    }
    
    function check_posts($log_errors = false) {
        $start = round(microtime(true),5);
		$start_debug = round(microtime(true),5);
		global $scan_delay;
                global $wpscx_title;
		$sql_count = 0;
		
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(6000); 
		global $wpdb;
		//global $wpsc_haystack;
		global $ignore_list;
		global $wpsc_settings;
		global $base_page_max;
		$timer_init = 0; //Initialization
		$timer_ignore = 0; //Ignore Page
		$timer_email = 0; //Ignore Emails if needed
		$timer_website = 0; //Ignore websites if needed
		$timer_upper = 0; //Ignore uppercase words if needed
		$timer_spellcheck = 0; //Spellcheck the word
		$timer_cleanup = 0; //Cleanup words before checking them
		$timer_errors = 0; //Add errors to database
		$timer_final = 0; //Finalization
		
		$start_time = time();
		wpscx_set_global_vars();
		
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table = $wpdb->prefix . 'posts';
		
		$max_pages = $base_page_max;
		$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		$divi_check = wp_get_theme();

		global $pro_included;
		//$total_pages = $max_pages;
		$total_words = 0;
		$page_count = 0;
		$word_count = 0;
		$pro_word_count = 0;
		$error_count = 0;

		$post_types = get_post_types( array('publicly_queryable' => true) );
			$post_type_list = "(";
			foreach ($post_types as $type) {
				if ($type != 'revision' && $type != 'page' && $type != 'slider' && $type != 'attachment' && $type != 'optionsframework' && $type != 'product' && $type != 'wpsc-product' && $type != 'wpcf7_contact_form' && $type != 'nav_menu_item' && $type != 'gal_display_source' && $type != 'lightbox_library' && $type != 'wpcf7s')
					$post_type_list .= "post_type='$type' OR ";
			}
			$post_type_list = trim($post_type_list, " OR ");
			$post_type_list .= ")";
		
			if ($wpsc_settings[137]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
			else { $post_status = " AND post_status='publish'"; }
		
		$page_list = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, post_name, ID FROM $page_table WHERE $post_type_list$post_status"));
		$sql_count++;
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
		}
			$ind_start_time = time();
		
		$max_time = ini_get('max_execution_time'); 

		$ignore_pages = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');
		$sql_count++;
		
		global $ignore_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		
		$timer_init = round(microtime(true),5) - $start;

		for ($x = 0; $x < $page_list->getSize(); $x++) {
			if ($page_list[$x]->ID == 10348) continue;
			if (strpos($words_content, '[fep_submission_form]')) continue;
		
			$start_timer = round(microtime(true),5);
			$ignore_flag = 'false';
			foreach($ignore_pages as $ignore_check) {
				if (strtoupper(trim($page_list[$x]->post_title)) == strtoupper(trim($ignore_check->keyword))) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$page_count++;
                        $wpscx_title = $page_list[$x]->post_title;
			
			$timer_ignore += round(microtime(true),5) - $start_timer;
			
			$words_content = $page_list[$x]->post_content;
			try { $words_content = do_shortcode($words_content); } catch (Exception $e) {}
			$words_content = wpscx_content_filter($words_content);
			
			$words_content = wpscx_clean_all($words_content, $wpsc_settings);
			$words = explode(" ", $words_content);
			
			$timer_page_cleanup += round(microtime(true),5) - $start_timer;

			foreach($words as $word) {
				$total_words++;
				$word = trim($word, "'`”“");
				
				if ($word == "") continue;
				if ($wpsc_haystack[strtoupper($word)] != 1) {
					$in_dictionary = false;
					if (!$in_dictionary) {
						$start_timer = round(microtime(true),5);
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && !is_numeric($word)) {
							$timer_upper += round(microtime(true),5) - $start_timer;
							if ($page_count <= $total_pages) {
							
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $page_list[$x]->post_title;
							$hold[2] = $page_list[$x]->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
							} else {
								$pro_error_count++;
							}
						} else { $timer_upper += round(microtime(true),5) - $start_timer; }
					}
				} else { $timer_spellcheck += round(microtime(true),5) - $start_timer; }
			}
			unset($page_list[$x]);
		}
		
		if ($log_errors) {
			$end = round(microtime(true),5);
			wpscx_print_debug("Post Content EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), $pro_error_count);
			return $pro_error_count;
		}
		
		
		if (!$end_task) {
			if ($page_count > $max_pages) { $counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='pro_word_count';");$sql_count++; 
			$word_count = $word_count + intval($counter[0]->option_value); }
			
			$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");$sql_count++;
			$total_words = $total_words + intval($counter[0]->option_value);
			$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));$sql_count++;
			if ($page_count > $total_pages) $page_count = $total_pages;
			$wpdb->update($options_table, array('option_value' => $page_count), array('option_name' => 'post_count'));$sql_count++;
			
			$start_timer = round(microtime(true),5);

			wpscx_sql_insert($error_list, 'Post Content');
			
			$timer_errors += round(microtime(true),5) - $start_timer;
			$start_timer = round(microtime(true),5);
			
				if ($is_running != true) wpscx_finalize($start_time);
			}
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'post_sip'));
		$sql_count++;
		
		$timer_final += round(microtime(true),5) - $start_timer;
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Post Content", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
		
		unset($sql); unset($error_list);
    }
    
    function check_author_spelling() {
        $start = round(microtime(true),5);
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		global $ent_included;
		error_reporting(0);
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table = $wpdb->prefix . 'posts';
		$user_table = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';
		$sql_count = 0;
		$total_words = 0;
		$word_count = 0;
		$error_count = 0;
		
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		
		$options_settings = SplFixedArray::fromArray($wpdb->get_results("SELECT option_value FROM $options_table;")); $sql_count++;
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		$posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT a.meta_key, a.user_id, a.meta_value, b.user_login, b.post_author FROM $user_table a LEFT JOIN (SELECT a.post_author, b.user_login FROM $post_table a, $username_table b WHERE a.post_author = b.ID GROUP BY post_author) AS b ON b.post_author = a.user_id WHERE (a.meta_key = 'first_name' OR a.meta_key = 'last_name' OR a.meta_key = 'description' OR a.meta_key = 'wpseo_metadesc' OR a.meta_key='wpseo_title');")); $sql_count++;
		
		for ($x = 0; $x < $posts_list->getSize(); $x++) {
			
			if ($posts_list[$x]->user_login == '') continue;
			$words_list  = $posts_list[$x]->meta_value;
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
			
			foreach($words as $word) {
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word)))$ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') { 
							//$word = addslashes($word);
							$to_add = true;
							if ($posts_list[$x]->meta_key == "first_name") { $post_type = "Author First Name";
							} elseif ($posts_list[$x]->meta_key == "last_name") { $post_type = "Author Last Name";
							} elseif ($posts_list[$x]->meta_key == "description") { $post_type = "Author Biography";
							} elseif ($posts_list[$x]->meta_key == 'wpseo_metadesc') { $post_type = "Author SEO Description";
								if (!$ent_included) $to_add = false;
							} elseif ($posts_list[$x]->meta_key == 'wpseo_title') { $post_type = "Author SEO Title";
								if (!$ent_included) $to_add = false;
							} else { $post_type = $posts_list[$x]->meta_key; }
							
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->user_login;
							$hold[2] = $posts_list[$x]->user_id;
							$hold[3] = $post_type;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
		}
		
		if ($post_count > $total_posts) $post_count = $total_posts;
		
		wpscx_sql_insert($error_list, 'Multi');

		$end = round(microtime(true),5);
		wpscx_print_debug("Author", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
    }
    
    function check_site_name() {
        $start = round(microtime(true),5);
		global $wpdb;
		global $end_included;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table = $wpdb->prefix . 'posts';
		$opt_table = $wpdb->prefix . 'options';
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(600); 
		$sql_count = 0;
		
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);
		if (!$ent_included) $max_pages = 500;
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);

		$total_words = 0;
		$post_count = 0;
		$word_count = 0;
		$error_count = 0;
$word_count = 0;
		$max_time = ini_get('max_execution_time'); 
		if ($is_running != true) {
			wpscx_set_global_vars();
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
			$start_time = time();
		}
		$ind_start_time = time();

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');$sql_count++;

		$posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT * FROM $opt_table WHERE option_name='blogname'"));$sql_count++;
		
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
	

		for ($x = 0; $x < $posts_list->getSize(); $x++) {
			if (!isset($posts_list[$x]->post_title)) continue;
			$words_list = $posts_list[$x]->option_value;
			
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$total_words++;				
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_check = str_replace("'", "\'", $ignore_check);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$dict_word = str_replace("'", "\'", $dict_word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && preg_match('/[^a-zA-Z]/', $word) != true) {
							//$word = addslashes($word);							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = 0;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
			$end_task = false;
			if (((time() - $ind_start_time) >= $max_time - 3) && count($page_list) > 0) {
				$end_task = true;
				wp_schedule_single_event(time() - 10 + 1, 'admincheckposts', array(true, $posts_list));
				break;
			}
			if($end_task) break;
		}
		
		
			
			
			
			
		
		if (!$end_task) {

		if ($post_count > $total_posts) $post_count = $total_posts;
		
		
		
		wpscx_sql_insert($error_list, 'Sitename');
			
			if ($is_running != true) wpscx_finalize($start_time);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Sitename", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
    }
    
    function check_site_tagline() {
        $start = round(microtime(true),5);
		global $wpdb;
		global $ent_included;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table = $wpdb->prefix . 'posts';
		$opt_table = $wpdb->prefix . 'options';
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(600); 
		$sql_count = 0;
		
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);
		if (!$ent_included) $max_pages = 500;
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		$total_words = 0;
		$post_count = 0;
		$word_count = 0;
		$error_count = 0;
$word_count = 0;
		$max_time = ini_get('max_execution_time'); 
		if ($is_running != true) {
			wpscx_set_global_vars();
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
			$start_time = time();
		}
		$ind_start_time = time();

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');$sql_count++;

		$posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT * FROM $opt_table WHERE option_name='blogdescription'"));$sql_count++;
		
		//global $ignore_list;
		//global $dict_list;
		//global $wpsc_settings;
		$error_list = new SplFixedArray(1);

		for ($x = 0; $x < $posts_list->getSize(); $x++) {
			$words_list = $posts_list[$x]->option_value;
			
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && preg_match('/[^a-zA-Z]/', $word) != true) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = 0;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
			$end_task = false;
			if (((time() - $ind_start_time) >= $max_time - 3) && count($page_list) > 0) {
				$end_task = true;
				wp_schedule_single_event(time() - 10 + 1, 'admincheckposts', array(true, $posts_list));
				break;
			}
			if($end_task) break;
		}
		
		
			
			
			
			
		
		if (!$end_task) {

		if ($post_count > $total_posts) $post_count = $total_posts;
		
		
		
		wpscx_sql_insert($error_list, 'Site Tagline');
			
			if ($is_running != true) {
				$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_in_progress')); $sql_count++;
			$end_time = time();
			$total_time = time_elapsed($end_time - $start_time + 6);
			$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished')); $sql_count++;
		}
		}
		
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Site Tagline", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
    }
    
    function check_authors() {
        global $scan_delay;
	sleep($scan_delay);
	
	ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(6000); 
	
	global $wpdb;
	global $ent_included;
	global $pro_included;
	$table_name = $wpdb->prefix . 'spellcheck_words';
	$options_table = $wpdb->prefix . 'spellcheck_options';
	$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
	$start_time = time(); 
	
	$post_table = $wpdb->prefix . 'posts';
	$posts_list = $wpdb->get_results("SELECT * FROM $post_table GROUP BY post_author");

	$this->check_author_spelling($wpsc_haystack);
	$this->check_site_tagline(true, $wpsc_haystack);
	$this->check_site_name(true, $wpsc_haystack);
	if ($ent_included) {
		//check_author_seotitle_ent(true);
		//check_author_seodesc_ent(true);
	}
	
	$end_time = time();
	$total_time = time_elapsed($end_time - $start_time + 6);
	$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished')); 
	$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'author_sip'));
    }
    
    function check_cf7() {
        $start = round(microtime(true),5);
		global $scan_delay;
		if (!$is_running) sleep($scan_delay);
		global $wpdb;
		global $ent_included;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		global $pro_included;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(6000); 
		$sql_count = 0;
		
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);
		if (!$ent_included) $max_pages = 500;
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);

		global $pro_included;
		$total_posts = 100;
		if ($pro_included) $total_posts = 1000;
		if ($ent_included) $total_posts = PHP_INT_MAX;
		$total_words = 0;
		$post_count = 0;
		$error_count = 0;
$word_count = 0;
		$word_count = 0;
		if ($is_running != true) {
			wpscx_set_global_vars();
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
			$start_time = time();
		}

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');$sql_count++;
		
		if ($wpsc_settings[136]->option_value == 'true') { $post_status = array('publish', 'draft'); }
		else { $post_status = array('publish'); }

		$posts_list = SplFixedArray::fromArray(get_posts(array('posts_per_page' => $total_posts, 'post_type' => 'wpcf7_contact_form', 'post_status' => $post_status)));$sql_count++;
		
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
                
		for ($x = 0; $x < $posts_list->getSize(); $x++) {
			$ignore_flag = 'false';
			foreach($ignore_posts as $ignore_check) {
				if (strtoupper(trim($posts_list[$x]->post_title)) == strtoupper(trim($ignore_check->keyword))) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$post_count++;
			$words_list = $posts_list[$x]->post_content;
                        $words_list = explode(PHP_EOL . '1' . PHP_EOL, $words_list);
                        $words_list = $words_list[0];
                        $words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && preg_match('/[^a-zA-Z]/', $word) != true) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
                                                        $hold[3] = "Contact Form 7 Form";
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							
							$error_count++;
						}
					}
				}	
			}
                        
                        //Email Notification
                        $words_list = $posts_list[$x]->post_content;
                        $words_list = explode(PHP_EOL . '1' . PHP_EOL, $words_list);
                        $words_list = $words_list[1];
                        
                        $words_list = preg_replace('/(.*\n){1}/m', "", $words_list,3);
                        $words_list = wpscx_clean_all($words_list, $wpsc_settings);
                        $words = explode(' ', $words_list);
                        
                        foreach($words as $word) {
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && preg_match('/[^a-zA-Z]/', $word) != true) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
                                                        $hold[3] = "Contact Form 7 Email Notification";
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							
							$error_count++;
						}
					}
				}	
			}
                        
                        //Email Auto Response
                        $words_list = $posts_list[$x]->post_content;
                        $words_list = explode(PHP_EOL . '1' . PHP_EOL, $words_list);
                        $words_list = $words_list[2];
                        
                        
                        $words_list = preg_replace('/(.*\n){1}/m', "", $words_list,2);
                        $words_list = wpscx_clean_all($words_list, $wpsc_settings);
                        $words = explode(' ', $words_list);

                        
                        foreach($words as $word) {
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && preg_match('/[^a-zA-Z]/', $word) != true) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
                                                        $hold[3] = "Contact Form 7 Auto Response";
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							
							$error_count++;
						}
					}
				}	
			}
		}
		
		
		$counter = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name ='total_word_count';");$sql_count++;
		$total_words = $total_words + intval($counter[0]->option_value);
		$wpdb->update($options_table, array('option_value' => $total_words), array('option_name' => 'total_word_count'));$sql_count++;
		
		$word_count = $word_count + intval($counter[0]->option_value);
		
		wpscx_sql_insert($error_list, 'Multi');
			
			if ($is_running != true) {
				$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_in_progress')); $sql_count++;
			$end_time = time();
			$total_time = time_elapsed($end_time - $start_time + 6);
			$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished')); $sql_count++;
		}
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'cf7_sip'));$sql_count++;
		
		
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Contact Form 7", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
    }
    
    function check_author_seotitle_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
			global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table = $wpdb->prefix . 'posts';
		$user_table = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';
		set_time_limit(600); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		$sql_count = 0;
		
		
		
		$max_pages = PHP_INT_MAX;
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		if ($wpsc_haystack == null) {
			$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
			$contents = file_get_contents($loc);
	
			$contents = str_replace("\r\n", "\n", $contents);
			$dict_file = explode("\n", $contents);

			foreach ($dict_file as $value) {
				$wpsc_haystack[strtoupper($value)] = 1;
			}
			unset($contents); unset($dict_file);
			
			foreach ($dict_list as $value) {
				$wpsc_haystack[strtoupper($value->word)] = 1;
			}
		}
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count = 0;
		$word_count = 0;
		$max_time = ini_get('max_execution_time'); 
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = array();
		error_reporting(0);
		
		$ind_start_time = time();

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');$sql_count++;
		

		$posts_list = $wpdb->get_results("SELECT a.meta_key, a.meta_value, b.user_login, b.post_author FROM $user_table a LEFT JOIN (SELECT a.post_author, b.user_login FROM $post_table a, $username_table b WHERE a.post_author = b.ID GROUP BY post_author) AS b ON b.post_author = a.user_id WHERE a.meta_key='wpseo_title';"); $sql_count++;

		foreach ($posts_list as $post) {
			array_shift($posts_list);

			$words_list = $post->meta_value;
			
			$words_list = wpscx_clean_text($words_list);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							if ($post_count <= $total_posts) {
							//$word = addslashes($word);
							$error_count++; array_push($error_list, array('word' => $word, 'page_name' => $post->post_title, 'page_id' =>$post->ID, 'page_type' => 'Author SEO Title'));
							} else {
								
							}
						}
					}
				}	
			}
			$end_task = false;
			if (((time() - $ind_start_time) >= $max_time - 3) && count($page_list) > 0) {
				$end_task = true;
				wp_schedule_single_event(time() + 1, 'admincheckposts', array(true, $posts_list));
				break;
			}
			if($end_task) break;
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Author SEO Title EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
		
		return $error_count;
}

function check_author_seodesc_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
			global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$post_table = $wpdb->prefix . 'posts';
		$user_table = $wpdb->prefix . 'usermeta';
		$username_table = $wpdb->prefix . 'users';
		set_time_limit(600); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		$sql_count = 0;
		
		
		
		$max_pages = PHP_INT_MAX;
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		if ($wpsc_haystack == null) {
			$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
			$contents = file_get_contents($loc);
	
			$contents = str_replace("\r\n", "\n", $contents);
			$dict_file = explode("\n", $contents);

			foreach ($dict_file as $value) {
				$wpsc_haystack[strtoupper($value)] = 1;
			}
			unset($contents); unset($dict_file);
			
			foreach ($dict_list as $value) {
				$wpsc_haystack[strtoupper($value->word)] = 1;
			}
		}

$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count = 0;
		$word_count = 0;
		$max_time = ini_get('max_execution_time'); 
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = array();
		error_reporting(0);
		$ind_start_time = time();

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";'); $sql_count++;
		

		$posts_list = $wpdb->get_results("SELECT a.meta_key, a.meta_value, b.user_login, b.post_author FROM $user_table a LEFT JOIN (SELECT a.post_author, b.user_login FROM $post_table a, $username_table b WHERE a.post_author = b.ID GROUP BY post_author) AS b ON b.post_author = a.user_id WHERE a.meta_key = 'wpseo_metadesc';"); $sql_count++;

		foreach ($posts_list as $post) {
			array_shift($posts_list);

			$words_list = $post->meta_value;
			
			$words_list = wpscx_clean_text($words_list);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							if ($post_count <= $total_posts) {
							//$word = addslashes($word);
							$error_count++; array_push($error_list, array('word' => $word, 'page_name' => $post->post_title, 'page_id' =>$post->ID, 'page_type' => 'Author SEO Description'));
							} else {
								
							}
						}
					}
				}	
			}
		}	
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Author SEO Desc EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
		
		return $error_count;
	}
	
        function check_widgets_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
            $start = round(microtime(true),5);
            $sql_count = 0;
            $total_words = 0;
            $error_count = 0;

            //Set memory/timeout
            set_time_limit(6000);
            ini_set('memory_limit','512M');

            //Set global variables
            global $wpdb;
            global $ignore_list;
            global $wpsc_settings;
            global $ent_included;
            wpscx_set_global_vars();

            //Set database tablenames
            $table_name = $wpdb->prefix . 'spellcheck_words';
            $options_table = $wpdb->prefix . 'spellcheck_options';
            $ignore_table = $wpdb->prefix . 'spellcheck_ignore';
            $dict_table = $wpdb->prefix . 'spellcheck_dictionary';

            //Set up dictionary haystack
            $loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
            $contents = file_get_contents($loc);
            $contents = str_replace("\r\n", "\n", $contents);
            $dict_file = explode("\n", $contents);
            $wpsc_haystack = wpscx_dictionary_init($dict_file);

            $error_list = new SplFixedArray(1);

            $widget_instances = get_option('widget_text');
            foreach($widget_instances as $widget) {
                    $text = $widget['text'];

                    $text = do_shortcode($text);
                    $text = wpscx_clean_all($text, $wpsc_settings, false);
                    $words = explode(" ", $text);

                    foreach($words as $word) {
                            $total_words++;
                            $word = trim($word, "'`”“");

                            if ($wpsc_haystack[strtoupper($word)] != 1 && $word != '') {
                                    //Add the error to a new fixed holding array
                                    $hold = new SplFixedArray(4);
                                    $hold[0] = $word;
                                    $hold[1] = $widget['title'];
                                    $hold[2] = 0;
                                    $hold[3] = "Widget Content";

                                    $error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
                                    $error_list[$error_count] = $hold;
                                    $error_count++;
                            }
                    }
            }

            $end = round(microtime(true),5);
            wpscx_print_debug("Widgets EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
            
            return $error_list->getSize();
        }

	function check_menus_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'posts';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		
		global $wpsc_settings;
		wpscx_set_global_vars();
		
		$max_pages = intval($wpsc_settings[138]->option_value);
		$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $words_table WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
			$sql_count++;
			$start_time = time();
		
		}
		global $ignore_list;
		global $dict_list;
		
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		

		$menus = SplFixedArray::fromArray($wpdb->get_results('SELECT post_title, ID FROM ' . $table_name . ' WHERE post_type ="nav_menu_item" LIMIT ' . $max_pages . ';'));
		$sql_count++;
		
		for ($x = 0; $x < $menus->getSize(); $x++) {
			$word_list = html_entity_decode(strip_tags($menus[$x]->post_title), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				//$ignore_check = str_replace("'", "\'", $word);
				//$ignore_word = $wpdb->get_results("SELECT word FROM $words_table WHERE word='" . $ignore_check . "' AND ignore_word = true");
				if ($wpsc_haystack[strtoupper($word)] != 1) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						$table_name = $wpdb->prefix . 'spellcheck_words';						
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $menus[$x]->post_title;
							$hold[2] = $menus[$x]->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
			unset($menus[$x]);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Menus EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
		
		return $error_list->getSize();
	}
	

	function check_page_title_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$end = round(microtime(true),5);
		////$loc = dirname(__FILE__)."/../../../../debug.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Page Content Start Time: " . date("g:i:sA") . "\r\n" );
		//fclose($debug_file);

		$start = round(microtime(true),5);
		$start_debug = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(6000); 
		global $wpdb;
		//global $wpsc_haystack;
		global $ignore_list;
		global $wpsc_settings;
		$timer_init = 0; //Initialization
		$timer_ignore = 0; //Ignore Page
		$timer_email = 0; //Ignore Emails if needed
		$timer_website = 0; //Ignore websites if needed
		$timer_upper = 0; //Ignore uppercase words if needed
		$timer_spellcheck = 0; //Spellcheck the word
		$timer_cleanup = 0; //Cleanup words before checking them
		$timer_errors = 0; //Add errors to database
		$timer_final = 0; //Finalization
		
		$start_time = time();
		wpscx_set_global_vars();
		
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table = $wpdb->prefix . 'posts';
		
		//$language_setting = $wpdb->get_results('SELECT option_value from ' . $options_table . ' WHERE option_name="language_setting";');
		
		//$max_pages = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'");
		$max_pages = intval($wpsc_settings[138]->option_value);
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
			$contents = file_get_contents($loc);
	
			$contents = str_replace("\r\n", "\n", $contents);
			$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);

		global $pro_included;
		$total_pages = $max_pages;
		$total_words = 0;
		$page_count = 0;
		$word_count = 0;
		$error_count = 0;
		
			if ($wpsc_settings[136]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
			else { $post_status = " AND post_status='publish'"; }
		
		$page_list = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, post_name, ID FROM $page_table WHERE post_type='page'$post_status"));
		$sql_count++;
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
		}
			$ind_start_time = time();
		
		$max_time = ini_get('max_execution_time'); 

		$ignore_pages = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');
		$sql_count++;
		
		global $ignore_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		
		$timer_init = round(microtime(true),5) - $start;

		for ($x = 0; $x < $page_list->getSize(); $x++) {
			$ignore_flag = 'false';
			foreach($ignore_pages as $ignore_check) {
				if (strtoupper(trim($page_list[$x]->post_title)) == strtoupper(trim($ignore_check->keyword))) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$page_count++;
		
			//Page Title
			$word_list = html_entity_decode(strip_tags($page_list[$x]->post_title), ENT_QUOTES, 'utf-8');
				
				$word_list = wpscx_clean_all($word_list, $wpsc_settings);

				$words = explode(' ', $word_list);

				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = trim($word, "'`”“");
					if ($word == "") continue;
					$ignore_check = str_replace("'", "\'", $word);
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}

						if (!$in_dictionary) {
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $page_list[$x]->post_title;
								$hold[2] = $page_list[$x]->ID;
								$hold[3] = "Page Title";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
							}
						}
					}
				}
			
			//Page Slug
			$desc_title = $page_list[$x]->post_title;
				$desc_id = $page_list[$x]->ID;
				$desc = $page_list[$x]->post_name;
				
				$desc = wpscx_clean_slug($desc);
				
				$words = explode(' ', $desc);

				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = str_replace(' ', '', $word);
					$word = str_replace('=', '', $word);
					$word = str_replace(',', '', $word);
					$word = trim($word, "?!.,'()`”:“@$#-%\=/");
					$word = trim($word, '"');
					$word = trim($word);
					$word = preg_replace("/[0-9]/", "", $word);
					$word = preg_replace("/[^a-zA-z'’`éèùâêîôûçëïü]/i", "", $word);
					if ($word == "") continue;
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}

						if (!$in_dictionary) {
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
									
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $desc_title;
								$hold[2] = $desc_id;
								$hold[3] = "Page Slug";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
							}
						}
					}
				}
				
			unset($page_list[$x]);
		}
		
		//Widgets
		$widget_instances = get_option('widget_text');
		foreach($widget_instances as $widget) {
			$text = $widget['text'];
			
			$text = do_shortcode($text);
			$text = wpscx_clean_all($text, $wpsc_settings, false);
			$words = explode(" ", $text);
			
			foreach($words as $word) {
				$total_words++;
				$word = trim($word, "'`”“");
				
				if ($wpsc_haystack[strtoupper($word)] != 1 && $word != '') {
					//Add the error to a new fixed holding array
					$hold = new SplFixedArray(4);
					$hold[0] = $word;
					$hold[1] = $widget['title'];
					$hold[2] = 0;
					$hold[3] = "Widget Content";
					
					$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
					$error_list[$error_count] = $hold;
					$error_count++;
				}
			}
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Page EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));

		return $error_list->getSize();
	}
        
        function check_custom_fields_free($post_id, $wpsc_haystack) {
        $post_meta = get_post_meta($post_id);
        global $wpsc_settings;
        $error_list = new SplFixedArray(1);
        $error_count = 0;
        
        foreach ($post_meta as $key => $value) {
            $meta = $value[0];
            if (substr($key, 0, 1) == '_' || $key == 'pageSlogan') continue;
            if (substr_count($meta, ':') > 5) continue;
            
                $words_content = wpscx_clean_all($meta, $wpsc_settings, false);
				
		$words = explode(" ", $words_content);

                foreach($words as $word) {
                                    
                        $start_timer = round(microtime(true),5);

                        $word = trim($word, "'`”“");

                        if ($word == "") continue;
                        if ($wpsc_haystack[strtoupper($word)] != 1) {
                            $start_timer = round(microtime(true),5);
                            if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && !is_numeric($word)) {
                                    $hold = new SplFixedArray(4);
                                    $hold[0] = $word;
                                    $hold[1] = '';
                                    $hold[2] = '';
                                    $hold[3] = $type . " Custom Field";

                                    $error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
                                    $error_list[$error_count] = $hold;
                                    $error_count++;
                            }
                        }
                }
        }
        return $error_list;
        }
	

	function check_post_title_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$end = round(microtime(true),5);
		////$loc = dirname(__FILE__)."/../../../../debug.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Start Time: " . date("g:i:sA") . "\r\n" );
		//fclose($debug_file);
		
		
		$start = round(microtime(true),5);
		$start_debug = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(6000); 
		global $wpdb;
		//global $wpsc_haystack;
		global $ignore_list;
		global $wpsc_settings;
		$timer_init = 0; //Initialization
		$timer_ignore = 0; //Ignore Page
		$timer_email = 0; //Ignore Emails if needed
		$timer_website = 0; //Ignore websites if needed
		$timer_upper = 0; //Ignore uppercase words if needed
		$timer_spellcheck = 0; //Spellcheck the word
		$timer_cleanup = 0; //Cleanup words before checking them
		$timer_errors = 0; //Add errors to database
		$timer_final = 0; //Finalization
		
		$start_time = time();
		wpscx_set_global_vars();
		
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$page_table = $wpdb->prefix . 'posts';
		
		//$language_setting = $wpdb->get_results('SELECT option_value from ' . $options_table . ' WHERE option_name="language_setting";');
		
		//$max_pages = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name = 'pro_max_pages'");
		$max_pages = intval($wpsc_settings[138]->option_value);
		$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		$divi_check = wp_get_theme();

		global $pro_included;
		$total_pages = $max_pages;
		$total_words = 0;
		$page_count = 0;
		$word_count = 0;
		$error_count = 0;

		$post_types = get_post_types( array('publicly_queryable' => true) );
			$post_type_list = "AND (";
			foreach ($post_types as $type) {
				if ($type != 'revision' && $type != 'page' && $type != 'slider' && $type != 'attachment' && $type != 'optionsframework' && $type != 'product' && $type != 'wpsc-product' && $type != 'wpcf7_contact_form' && $type != 'nav_menu_item' && $type != 'gal_display_source' && $type != 'lightbox_library' && $type != 'wpcf7s')
					$post_type_list .= "post_type='$type' OR ";
			}
			$post_type_list = trim($post_type_list, " OR ");
			$post_type_list .= ")";
		
			if ($wpsc_settings[137]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
			else { $post_status = " AND post_status='publish'"; }
		
		$page_list = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, post_name, ID FROM $page_table WHERE post_type='post'$post_status"));
		$sql_count++;
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
		}
			$ind_start_time = time();
		
		$max_time = ini_get('max_execution_time'); 

		$ignore_pages = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');
		$sql_count++;
		
		global $ignore_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		
		$timer_init = round(microtime(true),5) - $start;

		for ($x = 0; $x < $page_list->getSize(); $x++) {
                    
                        //Custom Fields
                        /*$custom = $this->check_custom_fields($page_list[$x]->ID,$wpsc_haystack,"Page",$page_list[$x]->post_title);
                        for ($y = 0; $y < $custom->getSize(); $y++) {
                            $error_list->setSize($error_list->getSize() + 1);
                            $error_list[$error_count] = $custom[$y];      
                            $error_count++;
                        }*/
		
			//Post Title
			if ($wpsc_settings[13]->option_value == 'true') {
				$word_list = html_entity_decode(strip_tags($page_list[$x]->post_title), ENT_QUOTES, 'utf-8');
				$word_list = wpscx_clean_all($word_list, $wpsc_settings);
				$words = explode(' ', $word_list);

				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = trim($word, "'`”“");
					if ($word == "") continue;
					$ignore_check = str_replace("'", "\'", $word);
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}

						if (!$in_dictionary) {
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $page_list[$x]->post_title;
								$hold[2] = $page_list[$x]->ID;
								$hold[3] = "Post Title";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
							}
						}
					}
				}
			}
			
			//Post Slug
			$desc_title =  $page_list[$x]->post_title;
				$desc_id =  $page_list[$x]->ID;
				$desc = $page_list[$x]->post_name;
				$desc = wpscx_clean_slug($desc);
				$words = explode(' ', $desc);

				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = str_replace(' ', '', $word);
					$word = str_replace('=', '', $word);
					$word = str_replace(',', '', $word);
					$word = trim($word, "?!.,'()`”:“@$#-%\=/");
					$word = trim($word, '"');
					$word = trim($word);
					$word = preg_replace("/[0-9]/", "", $word);
					$word = preg_replace("/[^a-zA-z'’`éèùâêîôûçëïü]/i", "", $word);
					if ($word == "") continue;
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}

						if (!$in_dictionary) {
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
									
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $desc_title;
								$hold[2] = $desc_id;
								$hold[3] = "Post Slug";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
							}
						}
					}
				}
			unset($page_list[$x]);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Post(End) EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
		
		return $error_list->getSize();
	}
	

function check_post_tags_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);
		$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
			$sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		
		$tags_list = SplFixedArray::fromArray(get_tags()); $sql_count++;
		
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Options Array: " . print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);


		for($x = 0; $x < $tags_list->getSize(); $x++) {
			$words = array();
			
			if ($wpsc_settings[14]->option_value =='true') {
				$words = wpscx_clean_text(strip_tags(html_entity_decode($tags_list[$x]->name)));
				$words = wpscx_clean_all($words, $wpsc_settings);
			
				$words = explode(' ',$words);
				
				//Tag Titles
				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = trim($word, '"');
					$word = trim($word);
					if ($word == "") continue;
					
					
					$ignore_check = str_replace("'", "\'", $word);
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}
						

						if (!$in_dictionary) {
						
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $tags_list[$x]->post_title;
								$hold[2] = $tags_list[$x]->term_id;
								$hold[3] = "Tag Title";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
							}
						}
					}
				}
			}

			if ($wpsc_settings[38]->option_value =='true') {
				//Tag Descriptions
				$words = wpscx_clean_text(strip_tags(html_entity_decode($tags_list[$x]->description)));
				$words = wpscx_clean_all($words, $wpsc_settings);
				$words = explode(' ', $words);
				
				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = trim($word, "?!.,'()`”:“@$#-%\=/");
					$word = trim($word, '"');
					$word = trim($word);
					if ($word == "") continue;
					
					$ignore_check = str_replace("'", "\'", $word);
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}
						

						if (!$in_dictionary) {
						
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $tags_list[$x]->post_title;
								$hold[2] = $tags_list[$x]->term_id;
								$hold[3] = "Tag Description";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
							}
						}
					}
				}		
			}
			
			if ($wpsc_settings[39]->option_value =='true') {
				//Tag Slugs
				$words = wpscx_clean_slug($tags_list[$x]->slug);
				
				$words = explode(' ', $words);
				
				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = str_replace(' ', '', $word);
					$word = str_replace('=', '', $word);
					$word = str_replace(',', '', $word);
					$word = trim($word, "?!.,'()`”:“@$#-%\=/");
					$word = trim($word, '"');
					$word = trim($word);
					$word = preg_replace("/[0-9]/", "", $word);
					$word = preg_replace("/[^a-zA-z'’`éèùâêîôûçëïü]/i", "", $word);
					if ($word == "") continue;
					
					$ignore_check = str_replace("'", "\'", $word);
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}
						

						if (!$in_dictionary) {
						
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $tags_list[$x]->post_title;
								$hold[2] = $tags_list[$x]->term_id;
								$hold[3] = "Tag Slug";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
							}
						}
					}
				}
			}
			
			unset($tags_list[$x]);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Tag EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));

		return $error_list->getSize();
	}

	function check_post_categories_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);
		$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		

		
		$cats_list = SplFixedArray::fromArray(get_categories()); $sql_count++;

		for ($x = 0; $x < $cats_list->getSize(); $x++) {
			$words = array();
			
			if ($wpsc_settings[15]->option_value =='true') {
				//Cat Titles
				$words = strip_tags(html_entity_decode($cats_list[$x]->name));
				$words = wpscx_clean_all($words, $wpsc_settings);
				$words = explode(' ', $words);
			
				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = trim($word, "'`”“");
					if ($word == "") continue;
					
					$ignore_check = str_replace("'", "\'", $word);
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}

						if (!$in_dictionary) {
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $cats_list[$x]->post_title;
								$hold[2] = $cats_list[$x]->term_id;
								$hold[3] = "Category Title";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
						}
						}
					}	
				}
			}
			
			if ($wpsc_settings[40]->option_value =='true') {
				//Cat Descriptions
				$words = array();
				$words = $cats_list[$x]->description;
				
				$words = wpscx_clean_all($words, $wpsc_settings);
				
				$words = explode(' ', $words);
			
				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = trim($word, "'`”“");
					if ($word == "") continue;
					$ignore_check = str_replace("'", "\'", $word);
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}

						if (!$in_dictionary) {
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $cats_list[$x]->post_title;
								$hold[2] = $cats_list[$x]->term_id;
								$hold[3] = "Category Description";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
						}
						}
					}	
				}
			}
			
			if ($wpsc_settings[41]->option_value =='true') {
				//Cat Slugs
				$words = wpscx_clean_slug($cats_list[$x]->slug);
				
				$words = explode(' ',$words);
			
				foreach($words as $word) {
					$word_count++;
					$total_words++;
					$word = str_replace(' ', '', $word);
					$word = str_replace('=', '', $word);
					$word = str_replace(',', '', $word);
					$word = trim($word, "?!.,'()`”:“@$#-%\=/");
					$word = trim($word, '"');
					$word = trim($word);
					$word = preg_replace("/[0-9]/", "", $word);
					$word = preg_replace("/[^a-zA-z'’`éèùâêîôûçëïü]/i", "", $word);
					if ($word == "") continue;
					$ignore_check = str_replace("'", "\'", $word);
					$ignore_word = false;
					if(isset($ignore_list)) { 
						foreach($ignore_list as $ignore) {
						if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
					}
						}
					if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
						$dict_word = str_replace("'", "\'", $word);
						$in_dictionary = false;
						if(isset($dict_list)) { 
						foreach($dict_list as $dict) {
							if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
						}
						}

						if (!$in_dictionary) {
							
							if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
								//Add the error to a new fixed holding array
								$hold = new SplFixedArray(4);
								$hold[0] = $word;
								$hold[1] = $cats_list[$x]->post_title;
								$hold[2] = $cats_list[$x]->term_id;
								$hold[3] = "Category Slug";
								
								$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
								$error_list[$error_count] = $hold;
								$error_count++;
						}
						}
					}	
				}
			}
			
			unset($cats_list[$x]);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Category EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));

		return $error_list->getSize();
	}

	function check_yoast_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);
		$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $words_table WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);

		if ($wpsc_settings[136]->option_value == 'true') { $page_status = true; }
		else { $page_status = false; }
		
		if ($wpsc_settings[137]->option_value == 'true') { $post_status = true; }
		else { $post_status = false; }
		
		$ain_active = is_plugin_active("all-in-one-seo-pack/all_in_one_seo_pack.php");
		$su_active = is_plugin_active("seo-ultimate/seo-ultimate.php");
		$yoast_active = is_plugin_active("wordpress-seo/wp-seo.php");

		$results = SplFixedArray::fromArray($wpdb->get_results('SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE meta_key="_yoast_wpseo_metadesc" OR meta_key="_aioseop_description" OR meta_key="_su_description" LIMIT ' . $max_pages)); $sql_count++;
		
		
		for($x = 0;$x < $results->getSize();$x++) {
			$desc = $results[$x];
			$post_store = $desc;
			$page_results = $wpdb->get_results('SELECT * FROM ' . $posts_table . ' WHERE ID=' . $desc->post_id);
			
			if ($page_results[0]->post_title == '') continue;
			if ($page_results[0]->post_status == 'draft' && $page_results[0]->post_type == 'page' && !$page_status) continue;
			if ($page_results[0]->post_status == 'draft' && $page_results[0]->post_type != 'page' && !$post_status) continue;
			
			$desc_type = $desc->meta_key;
			$desc = html_entity_decode(strip_tags($desc->meta_value), ENT_QUOTES, 'utf-8');
			$desc = wpscx_clean_all($desc, $wpsc_settings);
			$words = explode(' ', $desc);

			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && ($yoast_active || $ain_active || $su_active)) {
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $page_results[0]->post_title;
							$hold[2] = $page_results[0]->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							if ($desc_type == '_yoast_wpseo_metadesc' && $yoast_active) {
								$hold[3] =  'Yoast SEO Description';
							} elseif ($desc_type == '_aioseop_description' && $ain_active) {
								$hold[3] = 'All in One SEO Description'; 
							} elseif ($desc_type == '_su_description' && $su_active) {
								$hold[3] = 'Ultimate SEO Description'; 
							}
							$error_count++;
						}
					}
				}
			}
			unset($results[$x]);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Seo Desc EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));

		return $error_list->getSize();
	}
	

	function check_seo_titles_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'postmeta';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $words_table WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);

		if ($wpsc_settings[136]->option_value == 'true') { $page_status = true; }
		else { $page_status = false; }
		
		if ($wpsc_settings[137]->option_value == 'true') { $post_status = true; }
		else { $post_status = false; }
		
		$ain_active = is_plugin_active("all-in-one-seo-pack/all_in_one_seo_pack.php");
		$su_active = is_plugin_active("seo-ultimate/seo-ultimate.php");
		$yoast_active = is_plugin_active("wordpress-seo/wp-seo.php");

		$results = SplFixedArray::fromArray($wpdb->get_results('SELECT post_id, meta_value, meta_key FROM ' . $table_name . ' WHERE meta_key="_yoast_wpseo_title" OR meta_key="_aioseop_title" OR meta_key="_su_title" LIMIT ' . $max_pages)); $sql_count++;
		
		
		for($x = 0;$x < $results->getSize();$x++) {
			$desc = $results[$x];
			$post_store = $desc;
			$page_results = $wpdb->get_results('SELECT ID, post_title, post_status FROM ' . $posts_table . ' WHERE ID=' . $desc->post_id);
			
			if ($page_results[0]->post_title == '') continue;
			if ($page_results[0]->post_status == 'draft' && $page_results[0]->post_type == 'page' && !$page_status) continue;
			if ($page_results[0]->post_status == 'draft' && $page_results[0]->post_type != 'page' && !$post_status) continue;
			
			$desc_type = $desc->meta_key;
			$desc = $desc->meta_value;
			
			$desc = wpscx_clean_all($desc, $wpsc_settings);
			
			$words = explode(' ', $desc);

			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && ($yoast_active || $ain_active || $su_active)) {
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $page_results[0]->post_title;
							$hold[2] = $page_results[0]->ID;
							
							if ($desc_type == '_yoast_wpseo_title' && $yoast_active) {
								$hold[3] = 'Yoast SEO Title';
							} elseif ($desc_type == '_aioseop_title' && $ain_active) {
								$hold[3] = 'All in One SEO Title'; 
							} elseif ($desc_type == '_su_title' && $su_active) {
								$hold[3] = 'Ultimate SEO Title'; 
							} else {
								break;
							}
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}
			}
			unset($results[$x]);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("SEO Title EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));

		return $error_list->getSize();

	}
	

function check_post_slugs_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$post_table = $wpdb->prefix . 'posts';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $words_table WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		

		
			$post_types = get_post_types( array('publicly_queryable' => true) );
			$post_type_list = "AND (";
			foreach ($post_types as $type) {
				if ($type != 'revision' && $type != 'page' && $type != 'slider' && $type != 'attachment' && $type != 'optionsframework' && $type != 'product' && $type != 'wpsc-product' && $type != 'wpcf7_contact_form' && $type != 'nav_menu_item' && $type != 'gal_display_source' && $type != 'lightbox_library' && $type != 'wpcf7s')
					$post_type_list .= "post_type='$type' OR ";
			}
			$post_type_list = trim($post_type_list, " OR ");
			$post_type_list .= ")";
		
			if ($wpsc_settings[137]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
			else { $post_status = " AND post_status='publish'"; }

			//$posts_list = get_posts(array('posts_per_page' => $max_pages, 'post_type' => $post_type_list, 'post_status' => $post_status));
			$results = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, post_name, ID FROM $post_table WHERE post_type = 'post'" . $post_status . $post_type_list));
		$sql_count++;

		for ($x = 0;$x < $results->getSize();$x++) {
			$desc = $results[$x];
			$post_count++;
			$desc_title = $desc->post_title;
			$desc_id = $desc->ID;
			$desc = html_entity_decode(strip_tags($desc->post_name), ENT_QUOTES, 'utf-8');
			$desc = wpscx_clean_text($desc);
			$words = explode('-', $desc);

			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				$word = preg_replace("/[^a-zA-z'’`éèùâêîôûçëïü]/i", "", $word);
				if ($word == "") continue;
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $desc_title;
							$hold[2] = $desc_id;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}
			}
			unset($results[$x]);
		}
		
		$end = round(microtime(true),5);
		//$loc = dirname(__FILE__)."/../../../../debug.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Slug     Time: " . round($end - $start,5) . "      SQL: " . $sql_count . "     Memory: " . round(memory_get_usage() / 1000,5) . " KB" );
		//fclose($debug_file);
		
		return $error_list->getSize();
	}
	
	
	function check_page_slugs_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$post_table = $wpdb->prefix . 'posts';
		$table_name = $wpdb->prefix . 'spellcheck_words';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$posts_table = $wpdb->prefix . 'posts';
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		
		
			if ($wpsc_settings[137]->option_value == 'true') { $post_status = " AND (post_status='publish' OR post_status='draft')"; }
			else { $post_status = " AND post_status='publish'"; }

			//$posts_list = get_posts(array('posts_per_page' => $max_pages, 'post_type' => $post_type_list, 'post_status' => $post_status));
			$results = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, post_name, ID FROM $post_table WHERE post_type = 'page'" . $post_status));
		$sql_count++;

		for ($x = 0;$x < $results->getSize();$x++) {
			$desc = $results[$x];
			$post_count++;
			$desc_title = $desc->post_title;
			$desc_id = $desc->ID;
			$desc = html_entity_decode(strip_tags($desc->post_name), ENT_QUOTES, 'utf-8');
			$desc = wpscx_clean_text($desc);
			$words = explode('-', $desc);

			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = str_replace(' ', '', $word);
				$word = str_replace('=', '', $word);
				$word = str_replace(',', '', $word);
				$word = trim($word, "?!.,'()`”:“@$#-%\=/");
				$word = trim($word, '"');
				$word = trim($word);
				$word = preg_replace("/[0-9]/", "", $word);
				$word = preg_replace("/[^a-zA-z'’`éèùâêîôûçëïü]/i", "", $word);
				if ($word == "") continue;
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
								
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $desc_title;
							$hold[2] = $desc_id;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}
			}
			unset($results[$x]);
		}
		
		$end = round(microtime(true),5);
		//$loc = dirname(__FILE__)."/../../../../debug.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Page Slugs     Time: " . round($end - $start,5) . "      SQL: " . $sql_count . "     Memory: " . round(memory_get_usage() / 1000,5) . " KB" );
		//fclose($debug_file);

		return $error_list->getSize();
	}
	

	function check_slider_titles_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$posts_list = SplFixedArray::fromArray(get_posts(array('posts_per_page' => $max_pages, 'post_type' => 'slider', 'post_status' => array('publish', 'draft')))); $sql_count++;

		for($x = 0;$x < $posts_list->getSize();$x++) {
			$word_list = html_entity_decode(strip_tags($posts_list[$x]->post_title), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}
			}
		}
		$end = round(microtime(true),5);
		wpscx_print_debug("Slider Title EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));

		return $error_list->getSize();	
	}

	function check_slider_captions_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(6000); 
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$posts_list = SplFixedArray::fromArray(get_posts(array('posts_per_page' => $max_pages, 'post_type' => 'slider', 'post_status' => array('publish', 'draft')))); $sql_count++;

		for($x = 0;$x < $posts_list->getSize();$x++) {
			$word_list = get_post_meta ($posts_list[$x]->ID, 'my_slider_caption', true );
			$word_list = html_entity_decode(strip_tags($word_list), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Slider Caption EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
		

		return $error_list->getSize();
	}

/* Slider Plugins */

function check_it_slider_titles_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$slider_table = $wpdb->prefix . 'hugeit_slider_slide';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$table_name = $wpdb->prefix . 'spellcheck_words';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT slider_id, title FROM $slider_table")); $sql_count++;

		for ($x = 0;$x < $posts_list->getSize();$x++) {
			$word_list = html_entity_decode(strip_tags($posts_list[$x]->title), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				//$ignore_check = str_replace("'", "\'", $word);
				//$ignore_word = $wpdb->get_results("SELECT word FROM $words_table WHERE word='" . $ignore_check . "' AND ignore_word = true");
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->title;
							$hold[2] = $posts_list[$x]->slider_id;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
		}
		

		return $error_list->getSize();	
	}

	function check_it_slider_captions_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$slider_table = $wpdb->prefix . 'hugeit_slider_slide';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$table_name = $wpdb->prefix . 'spellcheck_words';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(6000); 
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT slider_id, description, title FROM $slider_table"));

		for ($x = 0;$x < $posts_list->getSize();$x++) {
			$word_list = html_entity_decode(strip_tags($posts_list[$x]->description), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				//$ignore_check = str_replace("'", "\'", $word);
				//$ignore_word = $wpdb->get_results("SELECT word FROM $words_table WHERE word='" . $ignore_check . "' AND ignore_word = true");
				if ($wpsc_haystack[strtoupper($word)] != 1) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->title;
							$hold[2] = $posts_list[$x]->slider_id;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
		}
		

		return $error_list->getSize();	
	}

/* Smart Slider 2 */

function check_smart_slider_titles_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'wp_nextend_smartslider_slides';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT slider, title FROM $table_name"));

		for ($x = 0;$x < $posts_list->getSize();$x++) {
			$word_list = html_entity_decode(strip_tags($posts_list[$x]->title), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = $wpdb->get_results("SELECT word FROM $words_table WHERE word='" . $ignore_check . "' AND ignore_word = true");
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
		}
		

		return $error_list->getSize();	
	}

	function check_smart_slider_captions_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'wp_nextend_smartslider_slides';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$words_table = $wpdb->prefix . 'spellcheck_words';
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT slider, description, title FROM $table_name"));

		for ($x = 0;$x < $posts_list->getSize();$x++) {
			$word_list = html_entity_decode(strip_tags($posts_list[$x]->description), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = $wpdb->get_results("SELECT word FROM $words_table WHERE word='" . $ignore_check . "' AND ignore_word = true");
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
		}
		
		
		return $error_list->getSize();	
	}


function check_media_titles_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$sql_count = 0;
		
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$post_table = $wpdb->prefix . 'posts';
		$word_count = 0;
		$total_words = 0;
		$media_count = 0;
		$error_count = 0;
		set_time_limit(6000);
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		
		$posts_list = SplFixedArray::fromArray($wpdb->get_results("SELECT post_content, post_title, post_excerpt, ID from $post_table WHERE post_type='attachment'")); $sql_count++;
		
		for ($x = 0;$x < $posts_list->getSize();$x++) {
			$media_count++;
			
			//******CHECK MEDIA TITLES******
			$word_list = html_entity_decode(strip_tags($posts_list[$x]->post_title), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
						
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
							$hold[3] = 'Media Title';
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
			
			//******CHECK MEDIA DESCRIPTION******
			$word_list = html_entity_decode(strip_tags($posts_list[$x]->post_content), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
							$hold[3] = 'Media Description';
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
			
			//******CHECK MEDIA CAPTION******
			$word_list = html_entity_decode(strip_tags($posts_list[$x]->post_excerpt), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
							$hold[3] = 'Media Caption';
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
			
			//******CHECK MEDIA ALT TEXT******
			$word_list = html_entity_decode(strip_tags(get_post_meta ($posts_list[$x]->ID, '_wp_attachment_image_alt', true )), ENT_QUOTES, 'utf-8');
			$word_list = wpscx_clean_all($word_list, $wpsc_settings);
			$words = explode(' ', $word_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $posts_list[$x]->post_title;
							$hold[2] = $posts_list[$x]->ID;
							$hold[3] = 'Media Alternate Text';
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
						}
					}
				}	
			}
			unset($posts_list[$x]);
		}
		
		$end = round(microtime(true),5);
		wpscx_print_debug("Media EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));

		return $error_list->getSize();	
	}	
	
	function check_woocommerce_free($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$start = round(microtime(true),5);
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		
		$total_posts = $max_pages;

$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count = 0;
		$word_count = 0;
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";'); $sql_count++;
		

		$posts_list = get_posts(array('posts_per_page' => $max_pages, 'post_type' => 'product', 'post_status' => array('publish', 'draft'))); $sql_count++;

		foreach ($posts_list as $post) {
			array_shift($posts_list);
			$ignore_flag = 'false';
			foreach($ignore_posts as $ignore_check) {
				if (strtoupper(trim($post->post_title)) == strtoupper(trim($ignore_check->keyword))) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$post_count++;
                        $words_list = $post->post_content;
                        
                        //Product Description
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							if ($post_count <= $total_posts) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $post->post_title;
							$hold[2] = $post->ID;
                                                        $hold[3] = "WooCommerce Product";
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
							} else {
								
							}
						}
					}
				}	
			}
                        
                        //Product Excerpt
                        $words_list = $post->post_excerpt;
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							if ($post_count <= $total_posts) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $post->post_title;
							$hold[2] = $post->ID;
                                                        $hold[3] = "WooCommerce Short Description";
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
							} else {
								
							}
						}
					}
				}	
			}
                        
                        //Product Title
                        $words_list = $post->post_title;
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							if ($post_count <= $total_posts) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(4);
							$hold[0] = $word;
							$hold[1] = $post->post_title;
							$hold[2] = $post->ID;
                                                        $hold[3] = "WooCommerce Title";
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
							} else {
								
							}
						}
					}
				}	
			}
		}
                
                
                //Check Categories
                $args = array('taxonomy', 'product_cat');
                $cats_list = SplFixedArray::fromArray(get_categories());

                for ($x = 0; $x < $cats_list->getSize(); $x++) {
                        $words = array();
                        $words = $cats_list[$x]->name;

                        $words = wpscx_clean_all($words, $wpsc_settings);

                        $words = explode(' ', $words);

                        foreach($words as $word) {
                                $word_count++;
                                $total_words++;
                                $word = trim($word, "'`”“");
                                if ($word == "") continue;

                                $ignore_check = str_replace("'", "\'", $word);
                                $ignore_word = false;
                                if(isset($ignore_list)) { 
                                        foreach($ignore_list as $ignore) {
                                        if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
                                }
                                        }
                                if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
                                        $dict_word = str_replace("'", "\'", $word);
                                        $in_dictionary = false;
                                        if(isset($dict_list)) { 
                                        foreach($dict_list as $dict) {
                                                if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
                                        }
                                        }

                                        if (!$in_dictionary) {

                                                if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {

                                                        //Add the error to a new fixed holding array
                                                        $hold = new SplFixedArray(4);
                                                        $hold[0] = $word;
                                                        $hold[1] = $cats_list[$x]->post_title;
                                                        $hold[2] = $cats_list[$x]->term_id;
                                                        $hold[3] = "WooCommerce Category Title";

                                                        $error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
                                                        $error_list[$error_count] = $hold;
                                                        $error_count++;
                                        }
                                        }
                                }	
                        }

                        $words = $cats_list[$x]->description;

                        $words = wpscx_clean_all($words, $wpsc_settings);

                        $words = explode(' ', $words);

                        foreach($words as $word) {
                                $word_count++;
                                $total_words++;
                                $word = trim($word, "'`”“");
                                if ($word == "") continue;

                                $ignore_check = str_replace("'", "\'", $word);
                                $ignore_word = false;
                                if(isset($ignore_list)) { 
                                        foreach($ignore_list as $ignore) {
                                        if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
                                }
                                        }
                                if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
                                        $dict_word = str_replace("'", "\'", $word);
                                        $in_dictionary = false;
                                        if(isset($dict_list)) { 
                                        foreach($dict_list as $dict) {
                                                if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
                                        }
                                        }

                                        if (!$in_dictionary) {

                                                if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {

                                                        //Add the error to a new fixed holding array
                                                        $hold = new SplFixedArray(4);
                                                        $hold[0] = $word;
                                                        $hold[1] = $cats_list[$x]->post_title;
                                                        $hold[2] = $cats_list[$x]->term_id;
                                                        $hold[3] = "WooCommerce Category Description";

                                                        $error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
                                                        $error_list[$error_count] = $hold;
                                                        $error_count++;
                                        }
                                        }
                                }	
                        }
                }

                
                //Check Tags
                $title_table = $wpdb->prefix . "terms";
                $desc_table = $wpdb->prefix . "taxonomy_terms";
                $tags_list = SplFixedArray::fromArray($wpdb->get_result('SELECT a.term_id, a.description, b.name FROM ' . $desc_table . ' a, ' . $title_table . ' b WHERE a.taxonomy="product_tag" AND a.term_id = b.term_id;'));

                for ($x = 0; $x < $tags_list->getSize(); $x++) {
                        $words = array();
                        $words = $tags_list[$x]->name;

                        $words = wpscx_clean_all($words, $wpsc_settings);

                        $words = explode(' ', $words);

                        foreach($words as $word) {
                                $word_count++;
                                $total_words++;
                                $word = trim($word, "'`”“");
                                if ($word == "") continue;

                                $ignore_check = str_replace("'", "\'", $word);
                                $ignore_word = false;
                                if(isset($ignore_list)) { 
                                        foreach($ignore_list as $ignore) {
                                        if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
                                }
                                        }
                                if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
                                        $dict_word = str_replace("'", "\'", $word);
                                        $in_dictionary = false;
                                        if(isset($dict_list)) { 
                                        foreach($dict_list as $dict) {
                                                if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
                                        }
                                        }

                                        if (!$in_dictionary) {

                                                if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {

                                                        //Add the error to a new fixed holding array
                                                        $hold = new SplFixedArray(4);
                                                        $hold[0] = $word;
                                                        $hold[1] = $tags_list[$x]->name;
                                                        $hold[2] = $tags_list[$x]->term_id;
                                                        $hold[3] = "WooCommerce Tag Title";

                                                        $error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
                                                        $error_list[$error_count] = $hold;
                                                        $error_count++;
                                        }
                                        }
                                }	
                        }

                        $words = $tags_list[$x]->description;

                        $words = wpscx_clean_all($words, $wpsc_settings);

                        $words = explode(' ', $words);

                        foreach($words as $word) {
                                $word_count++;
                                $total_words++;
                                $word = trim($word, "'`”“");
                                if ($word == "") continue;

                                $ignore_check = str_replace("'", "\'", $word);
                                $ignore_word = false;
                                if(isset($ignore_list)) { 
                                        foreach($ignore_list as $ignore) {
                                        if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
                                }
                                        }
                                if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
                                        $dict_word = str_replace("'", "\'", $word);
                                        $in_dictionary = false;
                                        if(isset($dict_list)) { 
                                        foreach($dict_list as $dict) {
                                                if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
                                        }
                                        }

                                        if (!$in_dictionary) {

                                                if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {

                                                        //Add the error to a new fixed holding array
                                                        $hold = new SplFixedArray(4);
                                                        $hold[0] = $word;
                                                        $hold[1] = $tags_list[$x]->name;
                                                        $hold[2] = $tags_list[$x]->term_id;
                                                        $hold[3] = "WooCommerce Tag Description";

                                                        $error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
                                                        $error_list[$error_count] = $hold;
                                                        $error_count++;
                                        }
                                        }
                                }	
                        }
                }
		
		$end = round(microtime(true),5);
		wpscx_print_debug("WooCommerce EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));

		return $error_count;
	}

	function check_woocommerce_coupon_free($is_running = false, $wpsc_haystack = null) {
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);

		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
$total_posts = $max_pages;
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count = 0;
		$word_count = 0;
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress'));  $sql_count++;
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";'); $sql_count++;
		

		$posts_list = get_posts(array('posts_per_page' => $max_pages, 'post_type' => 'shop_coupon', 'post_status' => array('publish', 'draft'))); $sql_count++;

		foreach ($posts_list as $post) {
			array_shift($posts_list);
			$ignore_flag = 'false';
			foreach($ignore_posts as $ignore_check) {
				if (strtoupper(trim($post->post_title)) == strtoupper(trim($ignore_check->keyword))) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$post_count++;
			$words_list = $post->post_excerpt;
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							if ($post_count <= $total_posts) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $post->post_title;
							$hold[2] = $post->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
							} else {
								
							}
						}
					}
				}	
			}
		}
		
		return $error_count;

	}

	function check_woocommerce_excerpt_free($is_running = false, $wpsc_haystack = null) {
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		

$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count = 0;
		$word_count = 0;
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');
		

		$posts_list = get_posts(array('posts_per_page' => $max_pages, 'post_type' => 'product', 'post_status' => array('publish', 'draft')));

		foreach ($posts_list as $post) {
			array_shift($posts_list);
			$ignore_flag = 'false';
			foreach($ignore_posts as $ignore_check) {
				if (strtoupper(trim($post->post_title)) == strtoupper(trim($ignore_check->keyword))) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$post_count++;
			$words_list = $post->post_excerpt;
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);
		
			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							if ($post_count <= $total_posts) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $post->post_title;
							$hold[2] = $post->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
							} else {
								
							}
						}
					}
				}	
			}
		}
		

		return $error_count;
	}


	function check_wpecommerce_free($is_running = false, $wpsc_haystack = null) {
		global $scan_delay;
		$sql_count = 0;
		
		global $wpdb;
		global $wpsc_haystack;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		set_time_limit(6000); 
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		
		
		$max_pages = intval($wpsc_settings[138]->option_value);	
$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");
		$loc = dirname(__FILE__)."/../../../../debug-var.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "Post Content Ignore List: " . sizeof((array)$ignore_list) . "          Dictionary List: " . sizeof((array)$dict_list) . "          Options: " . sizeof((array)$wpsc_settings) . "          Grammar Options: " . sizeof((array)$wpgc_settings) . "\r\n" );
		//$debug_var = fwrite( $debug_file, print_r($wpsc_settings, true) . "\r\n" );
		//fclose($debug_file);
		
		wpscx_set_global_vars();
		global $wpsc_settings;
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);
		
		$wpsc_haystack = wpscx_dictionary_init($dict_file);
		
		
$word_count = 0;
		$error_count = 0;
		$total_words = 0;
		$post_count = 0;
		$word_count = 0;
		if ($is_running != true) {
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); 
			$start_time = time();
		wpscx_set_global_vars();
		}
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		$error_list = new SplFixedArray(1);
		error_reporting(0);
		

		$ignore_posts = $wpdb->get_results('SELECT keyword FROM ' . $ignore_table . ' WHERE type="page";');
		

		$posts_list = get_posts(array('posts_per_page' => $max_pages, 'post_type' => 'wpsc-product', 'post_status' => array('publish', 'draft')));

		foreach ($posts_list as $post) {
			array_shift($posts_list);
			$ignore_flag = 'false';
			foreach($ignore_posts as $ignore_check) {
				if (strtoupper(trim($post->post_title)) == strtoupper(trim($ignore_check->keyword))) {
					$ignore_flag = 'true';
				}
			}
			if ($ignore_flag == 'true') { continue; }
			$post_count++;
			$words_list = $post->post_content;
			$words_list = wpscx_clean_all($words_list, $wpsc_settings);
			$words = explode(' ', $words_list);

			foreach($words as $word) {
				$word_count++;
				$total_words++;
				$word = trim($word, "'`”“");
				if ($word == "") continue;
				$ignore_check = str_replace("'", "\'", $word);
				$ignore_word = false;
				if(isset($ignore_list)) { 
					foreach($ignore_list as $ignore) {
					if (strtoupper($word) == stripslashes(strtoupper($ignore->word))) $ignore_word = true;
				}
					}
				if ($wpsc_haystack[strtoupper($word)] != 1 && !$ignore_word && !is_numeric($word)) {	
					$dict_word = str_replace("'", "\'", $word);
					$in_dictionary = false;
					if(isset($dict_list)) { 
					foreach($dict_list as $dict) {
						if (strtoupper($word) == strtoupper($dict->word)) $in_dictionary = true;
					}
					}

					if (!$in_dictionary) {
						
						if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '') {
							if ($post_count <= $total_posts) {
							//$word = addslashes($word);
							
							//Add the error to a new fixed holding array
							$hold = new SplFixedArray(3);
							$hold[0] = $word;
							$hold[1] = $post->post_title;
							$hold[2] = $post->ID;
							
							$error_list->setSize($error_list->getSize() + 1); //Increase the size of the main error array by 1
							$error_list[$error_count] = $hold;
							$error_count++;
							} else {
								
							}
						}
					}
				}	
			}
		}
		
		
		return $error_count;
	}
        
        function check_errors($log_debug = false) {
		global $wpdb;
		global $ent_included;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		set_time_limit(600); 
		
		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);
		
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'pro_word_count')); 
		
		$language_setting = $wpdb->get_results('SELECT option_value from ' . $options_table . ' WHERE option_name="language_setting";');
		$loc = dirname(__FILE__) . "/dict/" . $language_setting[0]->option_value . ".pws";
		$contents = file_get_contents($loc);

		$contents = str_replace("\r\n", "\n", $contents);
		$dict_list = explode("\n", $contents);

		foreach ($dict_list as $value) {
			$wpsc_haystack[strtoupper($value)] = 1;
		}
		
		$error_count = 0;
		$last_count = 0;
		
		$error_count += $this->check_posts(true);
		$last_count = $error_count;
		
		$error_count += $this->check_pages(true);
		$last_count = $error_count;
                
                $error_count += $this->check_widgets_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_menus_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_page_title_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_post_title_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_post_tags_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_post_categories_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_yoast_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_seo_titles_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_slider_titles_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_slider_captions_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_media_titles_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$error_count += $this->check_author_seodesc_free(true, $wpsc_haystack);
		$last_count = $error_count;
		
		$wpdb->update($options_table, array('option_value' => $error_count), array('option_name' => 'pro_word_count'));
		$wpdb->update($options_table, array('option_value' => "false"), array('option_name' => 'free_sip'));
	}
        
        function scan_single( $post_id ) {
		//Initialization
		ini_set('memory_limit', '512M'); //Sets the PHP memory limit
		global $wpdb;
		wpscx_set_global_vars();
		global $wpsc_settings;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$error_list = array();
		
		//Set up Dictionary haystack based on language settings
		$language_setting = $wpdb->get_results('SELECT option_value from ' . $options_table . ' WHERE option_name="language_setting";');
		$wpsc_settings = $wpsc_settings;
		
		$loc = dirname(__FILE__) . "/dict/" . $wpsc_settings[11]->option_value . ".pws";
		$contents = file_get_contents($loc);
		$contents = str_replace("\r\n", "\n", $contents);
		$dict_file = explode("\n", $contents);

		$wpsc_haystack = wpscx_dictionary_init($dict_file);

		$page = get_page( $post_id ); //Get the page/post
		
		$page_content = $page->post_content; //Get the content from the page/post
		
		//Cleanup the content for scanning
		$page_content = do_shortcode($page_content);
		$page_content = wpscx_content_filter($page_content);
		$page_content = wpscx_clean_all($page_content, $wpsc_settings);
		$words = explode(" ", $page_content);
		
		foreach($words as $word) {
			$word = trim($word, "'`”“");
			
			//Check the word against the dictionary haystack
			if ($wpsc_haystack[strtoupper($word)] != 1) {
				if (wpscx_ignore_caps($wpsc_settings, $word) && $word != '' && !is_numeric($word)) {
					array_push($error_list, array('word' => $word, 'page_type' => 'Page Content'));
				}
			}
		}
		
		return $error_list; //Return the error list to the on page editor for highlighting
	}
}