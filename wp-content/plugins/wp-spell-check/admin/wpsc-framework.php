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
	/* WP Spell Check classes */
		
	/* Main WP Spell Check Functions */
	
	
	/*function check_word($word, $dict_list) {
		ini_set('memory_limit','256M'); //Sets the PHP memory limit
		if (strlen($word) <= 2) { return true; }
		if (preg_replace('/[^A-Za-z0-9]/', '', $word) == '') { return true; }
		global $wpdb;
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$words_table = $wpdb->prefix . 'spellcheck_words';
		
		if (is_numeric($word)) { return true; }
		if (preg_match("/^[0-9]{3}-[0-9]{4}-[0-9]{4}$/", $word)) { return true; }
		
		$ignore_word = $wpdb->get_results("SELECT word FROM $words_table WHERE word='" . addslashes($word) . "' AND ignore_word!=0");
		if (sizeof((array)$ignore_word) >= 1) return true;

		return false;
	}*/
	
	function wpscx_print_debug($scan, $time, $sql, $memory, $error) {
		//global $wpsc_settings;
		//$loc = dirname(__FILE__)."/../../../../debug.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "$scan     Time: $time.     SQL: $sql     Memory: $memory KB.     Errors: $error     Number of Options Loaded: " . sizeof((array)$wpsc_settings) . "\r\n" );
		//fclose($debug_file);
	}
	
	function wpscx_print_debug_end($scan_type, $total_time) {
		//$loc = dirname(__FILE__)."/../../../../debug.log";
		//$debug_file = fopen($loc, 'a');
		//$debug_var = fwrite( $debug_file, "-------------------------$scan_type | " . date( 'd-M-Y H:i:s', current_time( 'timestamp', 0 ) ) . "------------------------------\r\n\r\n\r\n" );
		//fclose($debug_file);
	}
	
	function wpscx_construct_url($type, $id) {
		$blog = get_site_url();
		
		$url = $blog . '/wp-admin/post.php?post=' . $id . '&action=edit';
		
		if ($type == 'Menu Item') {
			$url = $blog . '/wp-admin/nav-menus.php?action=edit&menu='.$id;
		} elseif ($type == 'Contact Form 7') {
			$url = $blog . '"admin.php?page=wpcf7&post='.$id.'&action=edit';
		} elseif ($type == 'Post Title' || $type == 'Page Title' || $type == 'Yoast SEO Description' || $type == 'All in One SEO Description' || $type == 'Ultimate SEO Description' || $type == 'SEO Description' || $type == 'Yoast SEO Title' || $type == 'All in One SEO Title' || $type == 'Ultimate SEO Title' || $type == 'SEO Title' || $type == 'Post Slug' || $type == 'Page Slug') {
			$url = $blog . '/wp-admin/post.php?post=' . $id . '&action=edit';
		} elseif ($type == 'Slider Title' || $type == 'Slider Caption' || $type == 'Smart Slider Title' || $type == 'Smart Slider Caption') {
			$url = $blog . '/wp-admin/post.php?post=' . $id . '&action=edit';
		} elseif ($type == 'Huge IT Slider Title' || $type == 'Huge IT Slider Caption') {
			$url = $blog . '/wp-admin/admin.php?page=sliders_huge_it_slider&task=edit_cat&id=' . $id;
		} elseif ($type == 'Media Title' || $type == 'Media Description' || $type == 'Media Caption' || $type == 'Media Alternate Text') {
			$url = $blog . '/wp-admin/post.php?post=' . $id . '&action=edit';
		} elseif ($type == 'Tag Title' || $type == 'Tag Description' || $type == 'Tag Slug') {
			$url = $blog . '/wp-admin/term.php?taxonomy=post_tag&tag_ID=' . $id . '&post_type=post';
		} elseif ($type == 'Post Category' || $type == 'Category Description' || $type == 'Category Slug') {
			$url = $blog . '/wp-admin/term.php?taxonomy=category&tag_ID=' . $id . '&post_type=post';
		} elseif($type == 'Author Nickname' || $type == 'Author First Name' || $type == 'Author Last Name' || $type == 'Author Biography' || $type == 'Author SEO Title' || $type == 'Author SEO Description' || $type == 'twitter' || $type == 'facebook') {
			$url = $blog . '/wp-admin/user-edit.php?user_id=' . $id;
		} elseif($type == "Site Name" || $type == "Site Tagline") {
			$url = $blog . '/wp-admin/options-general.php';
		} elseif (($item['page_type'] == "WP eCommerce Product Excerpt" || $item['page_type'] == "WP eCommerce Product Name" || $item['page_type'] == "WooCommerce Product Excerpt" || $item['page_type'] == "WooCommerce Product Name" || $item['page_type'] == "Page Title" || $item['page_type'] == "Post Title" || $item['page_type'] == 'Yoast SEO Page Description' || $item['page_type'] == 'All in One SEO Page Description' || $item['page_type'] == 'Ultimate SEO Page Description' || $item['page_type'] == 'SEO Page Description' || $item['page_type'] == 'Yoast SEO Page Title' || $item['page_type'] == 'All in One SEO Page Title' || $item['page_type'] == 'Ultimate SEO Page Title' || $item['page_type'] == 'SEO Page Title' || $item['page_type'] == 'Yoast SEO Post Description' || $item['page_type'] == 'All in One SEO Post Description' || $item['page_type'] == 'Ultimate SEO Post Description' || $item['page_type'] == 'SEO Post Description' || $item['page_type'] == 'Yoast SEO Post Title' || $item['page_type'] == 'All in One SEO Post Title' || $item['page_type'] == 'Ultimate SEO Post Title' || $item['page_type'] == 'SEO Post Title' || $item['page_type'] == 'Yoast SEO Media Description' || $item['page_type'] == 'All in One SEO Media Description' || $item['page_type'] == 'Ultimate SEO Media Description' || $item['page_type'] == 'SEO Media Description' || $item['page_type'] == 'Yoast SEO Media Title' || $item['page_type'] == 'All in One SEO Media Title' || $item['page_type'] == 'Ultimate SEO Media Title' || $item['page_type'] == 'SEO Media Title') && $item['word'] == "Empty Field") {
				$url = $blog . '/wp-admin/post.php?post=' . $id . '&action=edit';
		}
		
		return $url;
	}
	
	function wpscx_finalize($start_time) {
		global $wpdb;
		$options_table = $wpdb->prefix . "spellcheck_options";
	
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$end_time = time();
		$total_time = time_elapsed($end_time - $start_time + 6);
		$wpdb->update($options_table, array('option_value' => $total_time), array('option_name' => 'last_scan_finished')); $sql_count++;
	}
	
	function wpscx_sql_insert($error_list, $page_type, $table_name = '') {
		global $wpdb;
		if ($table_name == '') $table_name = $wpdb->prefix . 'spellcheck_words';
		if ($page_type == 'Empty Field') $table_name = $wpdb->prefix . 'spellcheck_empty';
	
		if ($page_type == "Multi") {
			$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
			if($error_list->getSize() > 0) {			
				for ($x = 0; $x < $error_list->getSize(); $x++) {
					if ($error_list[$x][0] != '') $sql .= "('" . esc_sql($error_list[$x][0]) . "', '" . esc_sql($error_list[$x][1]) . "', '" . $error_list[$x][3] . "', " . $error_list[$x][2] . "), ";
					if ($x % 100 == 0) {
						$sql = trim($sql, ", ");
						if ($sql != "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES") $wpdb->query($sql);
						$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
					}
				}
				$sql = trim($sql, ", ");
				if ($sql != "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES") $wpdb->query($sql);
			
			}
		} elseif ($page_type == "Empty Field") {
			if(sizeof((array)$error_list) > 0) {
			$sql = '';
			
			foreach ($error_list as $error) {
				if ($sql == '' && $error['word'] != '') $sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
				if ($error['word'] != '') $sql .= "('" . esc_sql($error['word']) . "', '" . esc_sql($error['page_name']) . "', '" . $error['page_type'] . "', " . $error['page_id'] . "), ";
			}
			$sql = trim($sql, ", ");
			if ($sql != '') $wpdb->query($sql);
		}
		} else {
			$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
			if($error_list->getSize() > 1) {
				for ($x = 0; $x < ($error_list->getSize() - 1); $x++) {
					if ($error_list[$x][0] != '') $sql .= "('" . esc_sql($error_list[$x][0]) . "', '" . esc_sql($error_list[$x][1]) . "', '" . $page_type . "', " . $error_list[$x][2] . "), ";
					if ($x % 100000 == 0) {
						$sql = trim($sql, ", ");
						if ($sql != "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES") $wpdb->query($sql);
						$sql = "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES ";
					}
				}
				$sql = trim($sql, ", ");
				if ($sql != "INSERT INTO $table_name (word, page_name, page_type, page_id) VALUES") $wpdb->query($sql);
			}
		}
	}
	
	function wpgcx_sql_insert($error_list) {
		global $wpdb;
		$results_table = $wpdb->prefix . "spellcheck_grammar";
		
		$wpdb->insert($results_table, $error_list);
	}
	
	function wpscx_clean_text($content, $debug = false) {	
                //$content = utf8_encode($content);
                $content = preg_replace("/\s/u", " ", $content);
		$content = str_replace("’","'", $content);
		$content = str_replace("`","'", $content);
                $content = str_replace('“'," ", $content);
		$content = str_replace("'''","'", $content);
		//$content = str_replace("'s"," ", $content);
                $content = str_replace("("," ", $content);
                $content = str_replace(")"," ", $content);
                $content = str_replace("-"," ", $content);
                $content = str_replace('"'," ", $content);
                $content = str_replace('/'," ", $content);
                $content = str_replace('‘'," ", $content);
                $content = str_replace('–'," ", $content);
                $content = str_replace('—'," ", $content);
                $content = str_replace('•'," ", $content);
                $content = str_replace('′'," ", $content);
                $content = str_replace(''," ", $content);
                $content = str_replace('‐'," ", $content);
                $content = str_replace('‑'," ", $content);
                $content = str_replace('…'," ", $content);
                $content = trim($content, "'");
		$content = preg_replace("/(((?<=\s|^))[0-9|\$][0-9.,]+((?=\s|$)|(c|k|b|s|m|st|th|nd|rd|mb|kg|gb|tb|yb|sec|hr|min|am|pm|a.m.|p.m.)(\s|$|,|<|\.)))/ui", "", $content);
                //$content = preg_replace("/[0-9]/u", "", $content);
		//Spanish characters: áÁéÉíÍñÑóÓúÚüÜ¿¡«»
		//French Characters: ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿ
                $content = preg_replace("/([^0-9'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€a-zA-Z]|'s)+(\s|$|\"|')/ius", " ", $content);
		$content = preg_replace("/(\s|^)(\S+[^ 0-9a-zA-Z'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€!@#$%^&*()\-=_+,.\/;'[\]\\<>?:\"{}|]+\S+)(\s|$)/u", " ", $content);
                
                
		$content = str_replace("§"," ", $content);
		$content = str_replace("¢"," ", $content);
		$content = str_replace("¨"," ", $content);
		$content = str_replace('\\',' ', $content);
                $content = preg_replace("/\r?\n|\r/u", " ", $content);
		
		return $content;
	}
        
        function wpscx_clean_slug($slug) {
            return str_replace('-', ' ', $slug);
        }
	
	function wpscx_ignore_caps($wpsc_settings, $word) {
		return (strtoupper($word) != $word || $wpsc_settings[3]->option_value == 'false');
	}
	
	function wpscx_dictionary_init($dict_file) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		$wpsc_haystack = null;
		
		$dict_list = $wpdb->get_results("SELECT * FROM $dict_table;");
		$ignore_list = $wpdb->get_results("SELECT * FROM $table_name WHERE ignore_word=true;");

		foreach ($dict_file as $value) {
			$wpsc_haystack[strtoupper(stripslashes($value))] = 1;
		}
		
		foreach ($dict_list as $value) {
			$wpsc_haystack[strtoupper(stripslashes($value->word))] = 1;
		}
		
		foreach ($ignore_list as $value) {
			$wpsc_haystack[strtoupper(stripslashes($value->word))] = 1;
		}
		
		return $wpsc_haystack;
	}
	
	function wpscx_content_filter($content) {
		$divi_check = wp_get_theme();
		if ($divi_check->name == "Divi" || $divi_check->parent_name == "Divi" || $divi_check->parent_name == "Bridge" || $divi_check->name == "Bridge") {
			global $wp_query;
			$wp_query->is_singular = true;

			$content =  apply_filters( 'the_content', $content );
		
			return $content;
		} else {
			return $content;
		}
	}
	
	function wpscx_divi_check($content) {
		$divi_check = wp_get_theme();
		if ($divi_check->name == "Divi" || $divi_check->parent_name == "Divi" || $divi_check->parent_name == "Bridge" || $divi_check->name == "Bridge") {
			global $wp_query;
			$wp_query->is_singular = true;

			$content =  apply_filters( 'the_content', $content );
			
			$return_content;
		} else {
			return $content;
		}
	}
	
	function wpscx_script_cleanup($content) {
		$content = preg_replace("@<style[^>]*?>.*?</style>@siu",' ',$content);
		$content = preg_replace("@<script[^>]*?>.*?</script>@siu",' ',$content);
		$content = preg_replace("/(\<.*?\>)/",' ',$content);
		$content = preg_replace("/<iframe.+<\/iframe>/", " ", $content);
		
		return $content;
	}
	
	function wpscx_clean_shortcode($content) {
		return preg_replace('/(\[.*?\])/', ' ', $content);
	}
	
	function wpscx_html_cleanup($content) {
		return html_entity_decode(strip_tags($content), ENT_QUOTES, 'utf-8');
	}
	
	function wpscx_email_cleanup($content) {
		return preg_replace('/\S+\@\S+\.\S+/', ' ', $content);
	}
	
	function wpscx_website_cleanup($content) {
		$content = preg_replace('/((http|https|ftp)\S+)/', '', $content);
		$content = preg_replace('/www\.\S+/', '', $content);
		$content = preg_replace('/(\S+\.(COM|NET|ORG|GOV|INFO|XYZ|US|TOP|LOAN|BIZ|WANG|WIN|CLUB|ONLINE|VIP|MOBI|BID|SITE|MEN|TECH|PRO|SPACE|SHOP|WEBSITE|ASIA|KIWI|XIN|LINK|PARTY|TRADE|LIFE|STORE|NAME|CLOUD|STREAM|CAT|LIVE|TEL|XXX|ACCOUNTANT|DATE|DOWNLOAD|BLOG|WORK|RACING|REVIEW|TODAY|CLICK|ROCKS|NYC|WORLD|EMAIL|SOLUTIONS|NEWS|TOKYO|DESIGN|GURU|LONDON|LTD|ONE|PUB|REALTY|COMPANY|BERLIN|WEBCAM|HOST|PHOTOGRAPHY|PRESS|SCIENCE|FAITH|JOBS|REALTOR|REN|CITY|OVH|RED|AGENCY|SERVICES|MEDIA|GROUP|CENTER|STUDIO|GLOBAL|NINJA|TECHNOLOGY|TIPS|BAYERN|EXPERT|SALE|AMSTERDAM|DIGITAL|ACADEMY|NETWORK|HAMBURG|gdn|DE|CN|UK|NL|EU|RU|TK|AR|BR|IT|PL|FR|AU|CH|CA|ES|JP|KR|DK|BE|SE|AT|CZ|IN|HU|NO|TW|NZ|MX|PT|CL|FI|HK|TR|TRAVEL|AERO|COOP|MUSEUM)[^a-zA-Z])/i', ' ', $content);
		
		return $content;
	}
	
	function wpscx_clean_all($content, $wpsc_settings, $debug = false) {	
		$content = wpscx_script_cleanup($content);
		$content = wpscx_clean_shortcode($content);
		$content = wpscx_html_cleanup($content);
		
		if ($wpsc_settings[23]->option_value == 'true') {
			$content = wpscx_email_cleanup($content);
		}
		
		if ($wpsc_settings[24]->option_value == 'true') {
			$content = wpscx_website_cleanup($content);
		}
		
		$content = wpscx_clean_text($content, $debug);
		
		return $content;
	}
	
	function wpgcx_clean_all($content, $wpsc_settings) {
		$content = wpscx_script_cleanup($content);
		$content = wpscx_clean_shortcode($content);
		$content = wpscx_html_cleanup($content);
		
		if ($wpsc_settings[23]->option_value == 'true') {
			$content = wpscx_email_cleanup($content);
		}
		
		if ($wpsc_settings[24]->option_value == 'true') {
			$content = wpscx_website_cleanup($content);
		}
		
		return $content;
	}
	
	function wpbcx_clean_all($content, $wpsc_settings) {
		$content = wpscx_script_cleanup($content);
		
		if ($wpsc_settings[23]->option_value == 'true') {
			$content = wpscx_email_cleanup($content);
		}
		
		if ($wpsc_settings[24]->option_value == 'true') {
			$content = wpscx_website_cleanup($content);
		}
		
		return $content;
	}
	
	function wpscx_check_broken_code($rng_seed = 0, $is_running = false, $log_errors = true, $log_debug = true) {
            $scanner = new wpscx_broken_code_scanner_pro;
            
            $scanner->wpscx_scan_all();
	}
	add_action ('admincheckcode', 'wpscx_check_broken_code', 10, 2);
	
	function wpscx_check_broken_html($rng_seed = 0, $is_running = false, $log_errors = true, $log_debug = true) {
            $scanner = new wpscx_broken_code_scanner_pro;
            
            $scanner->wpscx_scan_html();
	}
	add_action ('admincheckhtml', 'wpscx_check_broken_html', 10, 2);
	
	function wpscx_check_broken_shortcode($rng_seed = 0, $is_running = false, $log_errors = true, $log_debug = true) {
            $scanner = new wpscx_broken_code_scanner_pro;
            
            $scanner->wpscx_scan_shortcode();
	}
	add_action ('admincheckshortcode', 'wpscx_check_broken_shortcode', 10, 2);
	
	function wpscx_check_pages($rng_seed = 0, $is_running = false, $wpsc_haystack = null, $log_errors = false, $log_debug = true) {
		$scanner = new wpscx_spellcheck_scanner;
                
                $scanner->check_pages();
	}
	add_action ('admincheckpages', 'wpscx_check_pages', 10, 2);

	function wpscx_check_posts($rng_seed = 0, $is_running = false, $wpsc_haystack = null, $log_errors = false, $log_debug = true) {
		$scanner = new wpscx_spellcheck_scanner;
                
                $scanner->check_posts();
	}
	add_action ('admincheckposts', 'wpscx_check_posts',10,2);
	
        function wpscx_check_author_spelling($wpsc_haystack = null, $log_debug = true) {
                $scanner = new wpscx_spellcheck_scanner;

                $scanner->check_author_spelling();
        }

        function wpscx_check_site_name($is_running = false, $wpsc_haystack = null, $log_debug = true) {
                $scanner = new wpscx_spellcheck_scanner;

                $scanner->check_site_name();
        }

        function wpscx_check_site_tagline($is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$scanner = new wpscx_spellcheck_scanner;
                
                $scanner->check_site_tagline();	
        }

        function wpscx_check_authors($rng_seed = 0, $wpsc_haystack = null, $log_debug = true) {
                $scanner = new wpscx_spellcheck_scanner;

                $scanner->check_authors();
        }
        add_action ('admincheckauthors', 'wpscx_check_authors');

	function wpscx_check_cf7($rng_seed = 0, $is_running = false, $wpsc_haystack = null, $log_debug = true) {
		$scanner = new wpscx_spellcheck_scanner;
                
                $scanner->check_cf7();
	}
	add_action ('admincheckcf7', 'wpscx_check_cf7');
	
	function wphcx_clear_results($clear_type = '') {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_html';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'html_page_count')); 
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'html_post_count')); 
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'html_media_count')); 

		$wpdb->delete($table_name, array('ignore_word' => false));
		$wpdb->get_results("ALTER TABLE $table_name AUTO_INCREMENT = 1");
	}
        
        function wphcx_clear_scan() {
            global $wpdb;
            $options_table = $wpdb->prefix . 'spellcheck_options';
            
            $wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'html_scan_running'));
        }

	function wpscx_clear_results($clear_type = '') {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'total_word_count')); 
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'page_count')); 
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'post_count')); 
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'media_count')); 

		$wpdb->delete($table_name, array('ignore_word' => false));
		$wpdb->get_results("ALTER TABLE $table_name AUTO_INCREMENT = 1");
		if ($clear_type == 'full') {
			$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'pro_word_count')); 
		}
	}
	
	function wpscx_clear_empty_results($clear_type = '') {
		global $wpdb;
		$table_name = $wpdb->prefix . 'spellcheck_empty';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'empty_page_count')); 
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'empty_post_count')); 
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'empty_media_count')); 
		$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'pro_empty_count')); 

		$wpdb->delete($table_name, array('ignore_word' => false));
		$wpdb->get_results("ALTER TABLE $table_name AUTO_INCREMENT = 1");
		
		if ($clear_type == 'full') {
			$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'empty_factor')); 
			$wpdb->update($options_table, array('option_value' => '0'), array('option_name' => 'pro_empty_count')); 
		} 
	}
	
	function wpscx_set_scan_in_progress($rng_seed = 0) {
		global $wpdb;
		global $pro_included;
		global $ent_included;
		global $wpsc_settings;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		
		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);
		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'entire_scan'));
		
		if ($settings[4]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_sip'));
		if ($settings[5]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_sip'));
		if ($settings[37]->option_value == 'true' && is_plugin_active('contact-form-7/wp-contact-form-7.php'))
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'cf7_sip'));
		if ($settings[44]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'author_sip'));
			
		if ($ent_included || $pro_included) {
		if ($settings[7]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'menu_sip'));
		if ($settings[14]->option_value == 'true' || $settings[38]->option_value == 'true' || $settings[39]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'tag_title_sip'));
		if ($settings[15]->option_value == 'true' || $settings[40]->option_value == 'true' || $settings[41]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'cat_title_sip'));
		if ($settings[16]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'seo_desc_sip'));
		if ($settings[17]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'seo_title_sip'));
		if ($settings[30]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'slider_sip'));
		if ($settings[31]->option_value == 'true')
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'media_sip'));
		if ($settings[36]->option_value == 'true' && (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active('wp-e-commerce/wp-shopping-cart.php')))
			$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'ecommerce_sip'));
		}
	}
	
	function wpscx_clear_scan() {
		global $wpdb;
		global $wpsc_settings;
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$settings = $wpsc_settings;
		
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'entire_scan'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'page_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'post_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'cf7_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'author_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'menu_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'tag_title_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'cat_title_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'seo_desc_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'seo_title_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'slider_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'media_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'tag_desc_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'tag_slug_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'cat_desc_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'cat_slug_sip'));
		$wpdb->update($options_table, array('option_value' => 'false'), array('option_name' => 'ecommerce_sip'));
	}

	function wpscx_scan_site_event($rng_seed = 0, $log_debug = true) {
		$start = round(microtime(true),5);
		ini_set('memory_limit','512M'); //Sets the PHP memory limit
		set_time_limit(600);
		global $wpdb;
		global $pro_included;
		global $ent_included;
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$page_list = null;
		$post_list = null;
		$sql_count = 0;
		
		if ($rng_seed = 10) wpscx_clear_results();
		
		$wpsc_haystack = null;
		
		$start_time = time(); 
		$wpdb->update($options_table, array('option_value' => $start_time), array('option_name' => 'scan_start_time')); $sql_count++;

		$settings = $wpdb->get_results('SELECT option_value FROM ' . $options_table);$sql_count++;
		
		wpscx_set_global_vars();
		
		if (!$ent_included) {
                    $scanner = new wpscx_spellcheck_scanner;
                    $scanner->check_errors();
                }
		
		if ($ent_included) {
		if ($settings[4]->option_value == 'true' || $settings[12]->option_value == 'true' || $settings[18]->option_value == 'true')
			wp_schedule_single_event(time(), 'admincheckpages_ent', array ($rng_seed, true, null, $wpsc_haystack, $log_debug));
		if ($settings[5]->option_value =='true' || $settings[13]->option_value == 'true' || $settings[19]->option_value == 'true')
			wp_schedule_single_event(time(), 'admincheckposts_ent', array ($rng_seed, true, null, $wpsc_haystack, $log_debug));
		if ($settings[36]->option_value =='true' && (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active('wp-e-commerce/wp-shopping-cart.php')))
			wp_schedule_single_event(time(), 'admincheckecommerce_ent', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[7]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckmenus_ent', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[14]->option_value =='true' || $settings[38]->option_value =='true' || $settings[39]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckposttags_ent', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[15]->option_value =='true' || $settings[41]->option_value =='true' || $settings[40]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckcategories_ent', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[16]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckseodesc_ent', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[17]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckseotitles_ent', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[30]->option_value =='true')
			wp_schedule_single_event(time(), 'adminchecksliders_ent', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[31]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckmedia_ent', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[37]->option_value =='true' && (is_plugin_active('contact-form-7/wp-contact-form-7.php')))
			wp_schedule_single_event(time(), 'admincheckcf7', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[44]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckauthors', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[147]->option_value =='true')
			wp_schedule_single_event(time(), 'wpsccheckwidgets', array ($rng_seed, true, $log_debug));
		} else {
		if ($settings[4]->option_value == 'true')
			wp_schedule_single_event(time(), 'admincheckpages', array ($rng_seed, true, $wpsc_haystack, false, $log_debug ));
		if ($settings[5]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckposts', array ($rng_seed, true , $wpsc_haystack, false, $log_debug));
		if ($settings[44]->option_value =='true')
			wp_schedule_single_event(time(), 'admincheckauthors', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		if ($settings[37]->option_value =='true' && (is_plugin_active('contact-form-7/wp-contact-form-7.php')))
			wp_schedule_single_event(time(), 'admincheckcf7', array ($rng_seed, true, $wpsc_haystack, $log_debug));
		}
		
		$end = round(microtime(true),5);
		if ($log_debug) wpscx_print_debug("Initialization", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), "N/A");
	}
	add_action ('adminscansite', 'wpscx_scan_site_event');

	function time_elapsed($secs){
		if ($secs > 300000000) $secs = 0;
		$secs += 3;
	    $bit = array(
	        ' year'        => $secs / 31556926 % 12,
	        ' week'        => $secs / 604800 % 52,
	        ' day'        => $secs / 86400 % 7,
	        ' hour'        => $secs / 3600 % 24,
	        ' minute'    => $secs / 60 % 60,
	        ' second'    => $secs % 60
	        );
        
	    foreach($bit as $k => $v){
	        if($v > 1)$ret[] = $v . $k . 's';
	        if($v == 1)$ret[] = $v . $k;
	        }
	    array_splice($ret, count($ret)-1, 0, ' ');
	    $ret[] = '';
    
	    return join(' ', $ret);
        }

	
	function wpscx_show_feature_window() {
		/*echo "<div class='request-feature-container'>";
		echo "<div class='request-feature-popup' style='display: none;'>";
		echo "<a href='' class='close-popup'>X</a>";
		echo "<img src='" . plugin_dir_url( __FILE__ ) . "images/logo.png' alt='WP Spell Check' /><br />";
		echo "<h3>We love hearing from you</h3>";
		echo "<p>Please report your problem to make the WP Spell Check plugin better</p>";
		echo "<a href='https://www.wpspellcheck.com/report-a-problem' target='_blank'><button>Report a Problem</button></a>";
		echo "<p>Please note: Support requests will not be handled through this form</p>";
		echo "</div>";
		echo "<div class='request-feature'><a href='' class='request-feature-link'>Report a Problem</a></div>";
		echo"</div>";*/
	}
	
	function wpscx_check_broken_code_free($rng_seed = 0, $is_running = false, $log_debug = true) {
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
			$words_content = do_shortcode($words_content);
			$words_content = wpscx_content_filter($words_content);
			$words_content = wpbc_clean_all($words_content, $wpsc_settings);
			
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
		if ($log_debug) wpscx_print_debug("Broken Code EPS", round($end - $start,5), $sql_count, round(memory_get_usage() / 1000,5), sizeof((array)$error_list));
		
		return $error_list->getSize();
	}
	
	function wphcx_check_scan_progress() {
		global $wpdb;
		global $wpsc_settings;
		
		$scan_in_progress = false;

		if ($wpsc_settings[141]->option_value == "true") $scan_in_progress = true;
		
		return $scan_in_progress;
	}
	
	function wpscx_check_scan_progress() {
		global $wpdb;
		global $wpsc_settings;
		
		$scan_in_progress = false;
		
		for($x = 66; $x <= 86; $x++) {
			if ($wpsc_settings[$x]->option_value == "true") $scan_in_progress = true;
		}
		
		return $scan_in_progress;
	}
	
	function wpscx_check_empty_scan_progress() {
		global $wpdb;
		global $wpsc_settings;
		
		$scan_in_progress = false;
		
		for($x = 87; $x <= 98; $x++) {
			if ($wpsc_settings[$x]->option_value == "true") $scan_in_progress = true;

		}
		
		return $scan_in_progress;
	}
        
        function wpscx_regex_pattern($to_replace) {
            $to_replace = preg_quote($to_replace);
            $to_replace = str_replace("'","['|’|‘]",$to_replace);
            $to_replace = str_replace('"','["|“|”]',$to_replace);
            //$regex = "/(?![^<]*>)(?<=\s|^|-|>|\"|“|\[)" . $to_replace . "(?=[^0-9'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€a-zA-Z])/m";
            $regex = "/(?![^<]*>)(?<=\s|^|-|>|\"|“|\[|\(|'|’|”|‘|“|’s|'s|‘s)" . $to_replace . "(?=\s|$|-|<|\.|\"|”\s|\]|,|\*|\)|!|'|\?|>|’|”|‘|“)/um";
            //echo $regex . "<br>";
            return $regex;
        }
?>
