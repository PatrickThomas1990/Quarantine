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

        class wpscx_options {
    
            function export_data($options_output, $grammar_output, $dict_output, $ignore_output) {
                    $loc = dirname(__FILE__) . "/wpsc-data.ini";
                    $output = fopen($loc, 'w');

                    fwrite($output, "[wpsc_settings]\r\n");

                    foreach($options_output as $option) {
                            fwrite($output, $option->option_name . "=" . $option->option_value . "\r\n");
                    }
                    unset( $options_output );

                    fwrite($output, "\r\n[wpsc_grammar]\r\n");

                    foreach($grammar_output as $option) {
                            fwrite($output, $option->option_name . "=" . $option->option_value . "\r\n");
                    }
                    unset( $grammar_output );

                    fwrite($output, "\r\n[wpsc_dictionary]\r\n");

                    if ($dict_output != null) {
                            foreach($dict_output as $dict) {
                                    fwrite($output, $dict->word . "\r\n");
                            }
                            unset( $dict_output );
                    }

                    fwrite($output, "\r\n[wpsc_ignore]\r\n");

                    if ($ignore_output != null) {
                            foreach($ignore_output as $ignore) {
                                    fwrite($output, $ignore->word . "\r\n");
                            }
                            unset( $ignore_output );
                    }

                    fclose($output);

                    ob_clean();

                    header( 'Content-Type: application/octet-stream; charset=utf-8' );
                    header( 'Content-Transfer-Encoding: Binary' );
                    header( 'Content-Disposition: attachment; filename=wpsc-data.ini' );
                    header( 'Content-Length: ' . filesize( dirname(__FILE__) .  "/wpsc-data.ini" ) );

                    readfile( dirname(__FILE__) .  "/wpsc-data.ini" );

                    exit();
            }
            
            /* Clear out the database for uninstallation */
            function prepare_uninstall() {
                    global $wpdb;


                    $sql = "DROP TABLE " . $wpdb->prefix . "spellcheck_dictionary;";
                    $wpdb->query($sql);
                    $sql = "DROP TABLE " . $wpdb->prefix . "spellcheck_ignore;";
                    $wpdb->query($sql);
                    $sql = "DROP TABLE " . $wpdb->prefix . "spellcheck_options;";
                    $wpdb->query($sql);
                    $sql = "DROP TABLE " . $wpdb->prefix . "spellcheck_words;";
                    $wpdb->query($sql);
                    $sql = "DROP TABLE " . $wpdb->prefix . "spellcheck_empty;";
                    $wpdb->query($sql);
                    $sql = "DROP TABLE " . $wpdb->prefix . "spellcheck_html;";
                    $wpdb->query($sql);
                    $sql = "DROP TABLE " . $wpdb->prefix . "spellcheck_grammar;";
                    $wpdb->query($sql);
                    $sql = "DROP TABLE " . $wpdb->prefix . "spellcheck_grammar_options;";
                    $wpdb->query($sql);


                    global $current_user;
                    $user_id = $current_user->ID;
                    delete_user_meta($user_id, 'wpsc_pro_notice_date');
                    delete_user_meta($user_id, 'wpsc_pro_dismissed');
                    delete_user_meta($user_id, 'wpsc_ignore_review_notice');
                    delete_user_meta($user_id, 'wpsc_review_date');
                    delete_user_meta($user_id, 'wpsc_times_dismissed_review');
                    delete_user_meta($user_id, 'wpsc_pro_ignore_notice');
                    delete_user_meta($user_id, 'wpsc_pro_notice_date');
                    delete_user_meta($user_id, 'wpsc_ignore_install_notice');
                    delete_user_meta($user_id, 'wpsc_last_check');
                    delete_user_meta($user_id, 'wpsc_version');
                    delete_user_meta($user_id, 'wpsc_outdated');
                    delete_user_meta($user_id, 'wpsc_pro_last_check');
                    delete_user_meta($user_id, 'wpsc_pro_version');
                    delete_user_meta($user_id, 'wpsc_pro_outdated');
                    delete_user_meta($user_id, 'wpsc_ent_last_check');
                    delete_user_meta($user_id, 'wpsc_ent_version');
                    delete_user_meta($user_id, 'wpsc_ent_outdated');
                    delete_user_meta($user_id, 'wpsc_update_notice_date');
                    delete_user_meta($user_id, 'wpsc_usedyslexic');
                    update_option('wpsc_data_acti','');

            }
	
        }

	function wpscx_render_options() {  
	global $wpdb;
	global $pro_included;
	global $ent_included;
	global $key_valid;
  global $wpsc_version;
        $options = new wpscx_options;
        
        //$error = new asdfasdfasdf;
    
  wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script('admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js');
	wp_enqueue_script('feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js');
  wp_enqueue_script('jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../js/jquery.contextMenu.js');
	wp_enqueue_script('jquery.ui.position', plugin_dir_url( __FILE__ ) . '../js/jquery.ui.position.js');
  ?>
      <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css">
      <link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>../css/admin-styles.css">
      <link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>../css/wpsc-sidebar.css">
  <?php
        
	$table_name = $wpdb->prefix . 'spellcheck_options';
	$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
	$grammar_table = $wpdb->prefix . 'spellcheck_grammar_options';
	$dict_table = $wpdb->prefix . "spellcheck_dictionary";
	$words_table = $wpdb->prefix . "spellcheck_words";
	ini_set('memory_limit','512M'); //Sets the PHP memory limit
	$message = '';
	if (isset($_POST['uninstall'])) {
	if ($_POST['uninstall'] == 'Clean up Database and Deactivate Plugin') {
		$options->prepare_uninstall();
		deactivate_plugins( 'wp-spell-check/wpspellcheck.php' );
		if ($pro_included) deactivate_plugins( 'wp-spell-check-pro/wpspellcheckpro.php' );
		if ($ent_included) deactivate_plugins( 'wp-spell-check-pro/wpspellcheckpro.php' );
		wp_die( 'WP Spell Check has been deactivated. If you wish to use the plugin again you may activate it on the WordPress plugin page' );
	}
	}
        
        $next_scan = wp_next_scheduled('admincheckcode', array(10,1));
        wp_unschedule_event($next_scan, 'admincheckcode', array(10,1));
        
        
	if (isset($_POST['import'])) {
	if ( $_POST['import'] == "Import Plugin Data" ) {
		$extension = end( explode( '.', $_FILES['import_file']['name'] ) );
		if ( $extension != 'ini' ) wp_die ( __( "Please Upload a valid .ini file" ) ); //Check to make sure the imported file is a .ini file
		
		$import_file = $_FILES['import_file']['tmp_name'];
		if ( empty($import_file) ) wp_die ( __( "Please upload a file with content. Last file has no content" ) ); //Check to make sure that the imported file isn't empty
		
		$input = file_get_contents( $import_file ); //Get the contents of the uploaded file
		
		$content = explode("\r\n", $input);
		
		$to_add = "[none]";
		$dict_dupe = "<h3 style='color: rgb(200, 0, 0);'> The following words were not added to My Dictionary because they were a duplicate:";
		$dict_dupe_ig = "<h3 style='color: rgb(200, 0, 0);'> The following words were not added to My Dictionary because they were found in the Ignore List:";
		$ignore_dupe = "<h3 style='color: rgb(200, 0, 0);'> The following words were not added to Ignore List because they were a duplicate:";
		$ignore_dupe_dict = "<h3 style='color: rgb(200, 0, 0);'> The following words were not added to Ignore List because they were found in My Dictionary:";
		
		$dict_display = false;
		$dict_display_ig = false;
		$ignore_display = false;
		$ignore_display_dict = false;
		
		foreach($content as $item) {
			if ($item == '') continue;
			if ($item == '[wpsc_settings]') $to_add = '[wpsc_settings]';
			if ($item == '[wpsc_grammar]') $to_add = '[wpsc_grammar]';
			if ($item == '[wpsc_dictionary]') $to_add = '[wpsc_dictionary]';
			if ($item == '[wpsc_ignore]') $to_add = '[wpsc_ignore]';
			//Check for the headers of each section and set flag accordingly
			
			if ($to_add == '[wpsc_settings]' && $item != '[wpsc_settings]' ) {
				$settings = explode("=", $item);
				if (sizeof((array)$settings) == 2) $wpdb->update($table_name, array('option_value' => $settings[1]), array('option_name' => $settings[0])); //Update the main settings table
			} elseif ($to_add == '[wpsc_grammar]' && $item != '[wpsc_grammar]' ) {
				$settings = explode("=", $item);
				if (sizeof((array)$settings) == 2) $wpdb->update($grammar_table, array('option_value' => $settings[1]), array('option_name' => $settings[0])); //Update the grammar settings table
			} elseif ($to_add == '[wpsc_dictionary]' && $item != '[wpsc_dictionary]') {
				$check_dict = $wpdb->get_results('SELECT * FROM ' . $dict_table . ' WHERE word="' . $item . '"');
				$check_ignore = $wpdb->get_results('SELECT * FROM ' . $words_table . ' WHERE word="' . $item . '" AND ignore_word = true');
			
				if (sizeof((array)$check_dict) > 0) {
					$dict_display = true;
					$dict_dupe .= " " . $item . ",";
				} elseif (sizeof((array)$check_ignore) > 0) {
					$dict_display_ig = true;
					$dict_dupe_ig .= " " . $item . ",";
				} else {
					$wpdb->insert($dict_table, array('word' => $item)); //Update the dictionary table
				}
			} elseif ($to_add == '[wpsc_ignore]' && $item != '[wpsc_ignore]') {
				$check_dict = $wpdb->get_results('SELECT * FROM ' . $dict_table . ' WHERE word="' . $item . '"');
				$check_ignore = $wpdb->get_results('SELECT * FROM ' . $words_table . ' WHERE word="' . $item . '" AND ignore_word = true');
			
				if (sizeof((array)$check_dict) > 0) {
					$ignore_display_dict = true;
					$ignore_dupe_dict .= " " . $item . ",";
				} elseif (sizeof((array)$check_ignore) > 0) {
					$ignore_display = true;
					$ignore_dupe .= " " . $item . ",";
				} else {
					$wpdb->insert($words_table, array('word' => $item, 'page_name' => 'WPSC_Ignore', 'ignore_word' => true, 'page_type' => 'wpsc_ignore')); //Update the ignore table
				}
			}
		}
		}
		
		$dict_dupe = trim($dict_dupe, ",");
		$dict_dupe_ig = trim($dict_dupe_ig, ",");
		$ignore_dupe = trim($ignore_dupe, ",");
		$ignore_dupe_dict = trim($ignore_dupe_dict, ",");
		
		$dict_dupe .= "</h3>";
		$dict_dupe_ig .= "</h3>";
		$ignore_dupe .= "</h3>";
		$ignore_dupe_dict .= "</h3>";
		
		if ($dict_display) echo "True";
		
		$message = "<h3 style='color: rgb(0, 115, 0);'>Plugin data has been successfully imported</h3>";
		
		if ($dict_display == true) $message .= $dict_dupe;
		if ($dict_display_ig == true) $message .= $dict_dupe_ig;
		if ($ignore_display == true) $message .= $ignore_dupe;
		if ($ignore_display_dict == true) $message .= $ignore_dupe_dict;
	}
	
	//set defaults for anything not already set
	
	if (!isset($_POST['email'])) $_POST['email'] = '';
	if (!isset($_POST['ignore-caps'])) $_POST['ignore-caps'] = '';
	if (!isset($_POST['check-pages'])) $_POST['check-pages'] = '';
	if (!isset($_POST['check-posts'])) $_POST['check-posts'] = '';
	if (!isset($_POST['check-authors'])) $_POST['check-authors'] = '';
	if (!isset($_POST['check-sliders'])) $_POST['check-sliders'] = '';
	if (!isset($_POST['check-media'])) $_POST['check-media'] = '';
	if (!isset($_POST['check-menu'])) $_POST['check-menus'] = '';
	if (!isset($_POST['page-titles'])) $_POST['page-titles'] = '';
	if (!isset($_POST['post-titles'])) $_POST['post-titles'] = '';
	if (!isset($_POST['tags'])) $_POST['tags'] = '';
	if (!isset($_POST['check-tag-desc'])) $_POST['check-tag-desc'] = '';
	if (!isset($_POST['check-tag-slug'])) $_POST['check-tag-slug'] = '';
	if (!isset($_POST['categories'])) $_POST['categories'] = '';
	if (!isset($_POST['check-cat-desc'])) $_POST['check-cat-desc'] = '';
	if (!isset($_POST['check-cat-slug'])) $_POST['check-cat-slug'] = '';
	if (!isset($_POST['seo-titles'])) $_POST['seo-titles'] = '';
	if (!isset($_POST['seo-desc'])) $_POST['seo-desc'] = '';
	if (!isset($_POST['page-slugs'])) $_POST['page-slugs'] = '';
	if (!isset($_POST['post-slugs'])) $_POST['post-slugs'] = '';
	if (!isset($_POST['check-ecommerce'])) $_POST['check-ecommerce'] = '';
	if (!isset($_POST['check-custom'])) $_POST['check-custom'] = '';
	if (!isset($_POST['ignore-emails'])) $_POST['ignore-emails'] = '';
	if (!isset($_POST['ignore-websites'])) $_POST['ignore-websites'] = '';
	if (!isset($_POST['highlight-words'])) $_POST['highlight-words'] = '';
	if (!isset($_POST['check-cf7'])) $_POST['check-cf7'] = '';
	if (!isset($_POST['check-post-drafts'])) $_POST['check-post-drafts'] = '';
	if (!isset($_POST['check-page-drafts'])) $_POST['check-page-drafts'] = '';
	if (!isset($_POST['check-authors-empty'])) $_POST['check-authors-empty'] = '';
	if (!isset($_POST['check-page-titles-empty'])) $_POST['check-page-titles-empty'] = '';
	if (!isset($_POST['check-post-titles-empty'])) $_POST['check-post-titles-empty'] = '';
	if (!isset($_POST['check-menu-empty'])) $_POST['check-menu-empty'] = '';
	if (!isset($_POST['check-tag-desc-empty'])) $_POST['check-tag-desc-empty'] = '';
	if (!isset($_POST['check-cat-desc-empty'])) $_POST['check-cat-desc-empty'] = '';
	if (!isset($_POST['check-page-seo-empty'])) $_POST['check-page-seo-empty'] = '';
	if (!isset($_POST['check-post-seo-empty'])) $_POST['check-post-seo-empty'] = '';
	if (!isset($_POST['check-media-seo-empty'])) $_POST['check-media-seo-empty'] = '';
	if (!isset($_POST['check-ecommerce-empty'])) $_POST['check-ecommerce-empty'] = '';
	if (!isset($_POST['check-media-empty'])) $_POST['check-media-empty'] = '';
	if (!isset($_POST['check-pages-grammar'])) $_POST['check-pages-grammar'] = '';
	if (!isset($_POST['check-posts-grammar'])) $_POST['check-posts-grammar'] = '';
	if (!isset($_POST['check-widgets'])) $_POST['check-widgets'] = '';
	if (!isset($_POST['wpsc-scan-tab'])) $_POST['wpsc-scan-tab'] = '';
	if (!isset($_POST['uninstall'])) $_POST['uninstall'] = '';
	
	if (isset($_POST['submit'])) {
	if ($_POST['submit'] == 'Update' || $_POST['submit'] == 'Send Test') {
		check_admin_referer('wpsc_update_options');
		$message = "<h3 style='color: rgb(0, 115, 0);'>Options Updated</h3>";
		if ($_POST['email'] == 'email') {
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'email'));
		} else { 
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'email'));
		}
		$wpdb->update($table_name, array('option_value' => $_POST['email_address']), array('option_name' => 'email_address'));
		if ($_POST['ignore-caps'] == 'ignore-caps')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'ignore_caps'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'ignore_caps'));
		if ($_POST['check-pages'] == 'check-pages')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_pages'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_pages'));
		if ($_POST['check-posts'] == 'check-posts')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_posts'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_posts'));
		if ($_POST['check-authors'] == 'check-authors')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_authors'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_authors'));
		if ($ent_included) {
			if ($_POST['check-sliders'] == 'check-sliders')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_sliders'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_sliders'));
			if ($_POST['check-media'] == 'check-media')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_media'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_media'));;
			if ($_POST['check-menu'] == 'check-menu')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_menus'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_menus'));
			if ($_POST['page-titles'] == 'page-titles')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'page_titles'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'page_titles'));
			if ($_POST['post-titles'] == 'post-titles')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'post_titles'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'post_titles'));
			if ($_POST['tags'] == 'tags')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'tags'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'tags'));
			if ($_POST['check-tag-desc'] == 'check-tag-desc')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_tag_desc'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_tag_desc'));
			if ($_POST['check-tag-slug'] == 'check-tag-slug')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_tag_slug'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_tag_slug'));
			if ($_POST['categories'] == 'categories')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'categories'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'categories'));
			if ($_POST['check-cat-desc'] == 'check-cat-desc')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_cat_desc'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_cat_desc'));
			if ($_POST['check-cat-slug'] == 'check-cat-slug')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_cat_slug'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_cat_slug'));
			if ($_POST['seo-titles'] == 'seo-titles')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'seo_titles'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'seo_titles'));
			if ($_POST['seo-desc'] == 'seo-desc')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'seo_desc'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'seo_desc'));
			if ($_POST['page-slugs'] == 'page-slugs')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'page_slugs'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'page_slugs'));
			if ($_POST['post-slugs'] == 'post-slugs')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'post_slugs'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'post_slugs'));
			if ($_POST['check-ecommerce'] == 'check-ecommerce')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_ecommerce'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_ecommerce'));
			if ($_POST['check-widgets'] == 'check-widgets')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_widgets'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_widgets'));
		}
		if ($_POST['check-custom'] == 'check-custom')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_custom'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_custom'));
		if ($_POST['ignore-emails'] == 'ignore-emails')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'ignore_emails'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'ignore_emails'));
		if ($_POST['ignore-websites'] == 'ignore-websites')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'ignore_websites'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'ignore_websites'));
		if ($_POST['highlight-words'] == 'highlight-words')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'highlight_word'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'highlight_word'));
		if ($_POST['check-cf7'] == 'check-cf7')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_cf7'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_cf7'));
			
		if ($_POST['check-post-drafts'] == 'check-post-drafts')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'scan_post_drafts'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'scan_post_drafts'));
		if ($_POST['check-page-drafts'] == 'check-page-drafts')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'scan_page_drafts'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'scan_page_drafts'));
			
		
		
		if ($_POST['check-authors-empty'] == 'check-authors')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_authors_empty'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_authors_empty'));
		if ($_POST['check-page-titles-empty'] == 'check-page-titles')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_page_titles_empty'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_page_titles_empty'));
		if ($_POST['check-post-titles-empty'] == 'check-post-titles')
			$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_post_titles_empty'));
		else
			$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_post_titles_empty'));
		if ($ent_included) {
			if ($_POST['check-menu-empty'] == 'check-menu')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_menu_empty'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_menu_empty'));
			if ($_POST['check-tag-desc-empty'] == 'check-tag-desc')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_tag_desc_empty'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_tag_desc_empty'));
			if ($_POST['check-cat-desc-empty'] == 'check-cat-desc')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_cat_desc_empty'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_cat_desc_empty'));
			if ($_POST['check-page-seo-empty'] == 'check-page-seo')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_page_seo_empty'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_page_seo_empty'));
			if ($_POST['check-post-seo-empty'] == 'check-post-seo')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_post_seo_empty'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_post_seo_empty'));
			if ($_POST['check-media-seo-empty'] == 'check-media-seo')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_media_seo_empty'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_media_seo_empty'));
			if ($_POST['check-ecommerce-empty'] == 'check-ecommerce')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_ecommerce_empty'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_ecommerce_empty'));
			if ($_POST['check-media-empty'] == 'check-media')
				$wpdb->update($table_name, array('option_value' => 'true'), array('option_name' => 'check_media_empty'));
			else
				$wpdb->update($table_name, array('option_value' => 'false'), array('option_name' => 'check_media_empty'));
		}
			
		if ($_POST['check-pages-grammar'] == 'check-pages')
			$wpdb->update($grammar_table, array('option_value' => 'true'), array('option_name' => 'check_pages'));
		else
			$wpdb->update($grammar_table, array('option_value' => 'false'), array('option_name' => 'check_pages'));
		if ($_POST['check-posts-grammar'] == 'check-posts')
			$wpdb->update($grammar_table, array('option_value' => 'true'), array('option_name' => 'check_posts'));
		else
			$wpdb->update($grammar_table, array('option_value' => 'false'), array('option_name' => 'check_posts'));

		if (is_numeric($_POST['scan_frequency'])) {
			$wpdb->update($table_name, array('option_value' => $_POST['scan_frequency']), array('option_name' => 'scan_frequency'));

			
			$next_scan = wp_next_scheduled('adminscansite', array(10));
			wp_unschedule_event($next_scan, 'adminscansite', array(10));

			switch($_POST['scan_frequency_interval']) {
				case 'hourly':
					$scan_timer = intval($_POST['scan_frequency']) * 3600;
					break;
				case 'daily':
					$scan_timer = intval($_POST['scan_frequency']) * 86400;
					break;
				case 'weekly':
					$scan_timer = intval($_POST['scan_frequency']) * 604800;
					break;
				case 'monthly':
					$scan_timer = intval($_POST['scan_frequency']) * 2592000;
					break;
				default:
					$scan_timer = 604800;
			}
			
			//echo "Debug(Options) - " . $scan_timer . "<br>";

			wp_schedule_event(time() + $scan_timer, 'wpsc', 'adminscansite', array(10));
		} else {
			$message = "Please enter a valid number for scan frequency";
		}
		$wpdb->update($table_name, array('option_value' => $_POST['scan_frequency_interval']), array('option_name' => 'scan_frequency_interval'));
		$wpdb->update($table_name, array('option_value' => $_POST['language_setting']), array('option_name' => 'language_setting'));
		$wpdb->update($table_name, array('option_value' => $_POST['api_key']), array('option_name' => 'api_key'));
		
		//echo "Debug: " . $debug;
		
		$pages = explode(PHP_EOL, $_POST['pages-ignore']);

		$wpdb->query('TRUNCATE TABLE ' . $ignore_table); 

		foreach($pages as $page) {
			if ($page != '')
				$wpdb->insert($ignore_table, array('keyword' => $page, 'type' => 'page')); 
		}
		
                if (is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php')) {
                    $pro_data = get_plugin_data(dirname(__FILE__) . '/../../wp-spell-check-pro/wpspellcheckpro.php');
                    $pro_ver = $pro_data['Version'];
                    if ($_POST['uninstall'] != 'Clean up Database and Deactivate Plugin' && is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php') && version_compare($pro_ver, $wpsc_version) == 0) wpsc_do_ent_api_request(); //Refresh the API Key validation after updating it unless deactivating plugin
                }
		global $key_valid;
		global $pro_included;
		global $ent_included;
		
		$user_id = get_current_user_id();
		update_usermeta($user_id, 'wpsc_usedyslexic', $_POST['wpsc_usedyslexic']);
		//-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif
		if ($_POST['wpsc_usedyslexic'] == 'no' || $_POST['wpsc_usedyslexic'] == "yes_websiteonly") { ?>
		<style>
			* { font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif!important; }
		</style>
		<?php } else { ?>
		<style>
			* { font-family: open-dyslexic !important }
		</style>
		<?php }
	}
	}
	
	$settings = $wpdb->get_results('SELECT option_name, option_value FROM ' . $table_name);
	$grammar_settings = $wpdb->get_results('SELECT option_name, option_value FROM ' . $grammar_table);
	
	$grammar_pages = $grammar_settings[0]->option_value;
	$grammar_posts = $grammar_settings[1]->option_value;
	
	$email = $settings[0]->option_value;
	$email_address = $settings[1]->option_value;
	$ignore_caps = $settings[3]->option_value;
	$check_pages = $settings[4]->option_value;
	$check_posts = $settings[5]->option_value;
	$check_menus = $settings[7]->option_value;
	$scan_frequency = $settings[8]->option_value;
	$scan_frequency_interval = $settings[9]->option_value;
	$email_frequency_interval = $settings[10]->option_value;
	$language_setting = $settings[11]->option_value;
	$page_titles = $settings[12]->option_value;
	$post_titles = $settings[13]->option_value;
	$tags = $settings[14]->option_value;
	$categories = $settings[15]->option_value;
	$seo_desc = $settings[16]->option_value;
	$seo_titles = $settings[17]->option_value;
	$page_slugs = $settings[18]->option_value;
	$post_slugs = $settings[19]->option_value;
	$api_key = $settings[20]->option_value;
	$ignore_emails = $settings[23]->option_value;
	$ignore_websites = $settings[24]->option_value;
	$check_sliders = $settings[30]->option_value;
	$check_media = $settings[31]->option_value;
	$highlight_words = $settings[33]->option_value;
	$check_ecommerce = $settings[36]->option_value;
	$check_cf7 = $settings[37]->option_value;
	$check_tag_desc = $settings[38]->option_value;
	$check_tag_slug = $settings[39]->option_value;
	$check_cat_desc = $settings[40]->option_value;
	$check_cat_slug = $settings[41]->option_value;
	$check_custom = $settings[42]->option_value;
	$check_authors = $settings[44]->option_value;
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
	$check_page_drafts = $settings[136]->option_value;
	$check_post_drafts = $settings[137]->option_value;
	$check_widgets = $settings[147]->option_value;
	
	$page_data = $wpdb->get_results("SELECT keyword FROM " . $ignore_table . " WHERE type='page';");
	$page_list = '';
	foreach ($page_data as $page) {
		$page_list .= $page->keyword . PHP_EOL;
	}
	if (isset($_POST['test-email'])) {
	if ($_POST['test-email'] == 'Send Test') {
		$wpdb->update($table_name, array('option_value' => $_POST['email_address']), array('option_name' => 'email_address'));
                $emailer = new wpscx_email;
		$message = $emailer->send_test_email();
		$email_address = $_POST['email_address'];
	}
	}
		
	wp_enqueue_script('options-nav', plugin_dir_url( __FILE__ ) . 'options-nav.js');
	?>
		<style> p.submit { display: inline-block; margin-left: 10px; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; margin-botton: 15px; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 1px -1px 1px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; height: 16px; border-radius: 5px 5px 0 0; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } .wpsc-mouseover-text-pro-feature { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; z-index: -100; } .wpsc-mouseover-text-freq { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; z-index: -100; } </style>
		<?php wpscx_show_feature_window(); ?>
		<?php //wpscx_check_install_notice(); ?>
		<div class="wrap">
			<h2><a href="admin.php?page=wp-spellcheck.php"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - Options</span></h2>
			<?php if(!$key_valid && $api_key != '') echo "<div class='error' style='color: red; font-weight: bold; font-size: 14px'>API Key not valid</div>"; ?>
			<?php if ($key_valid) echo "<div class='updated' style='color: rgb(0, 115, 0); font-weight: bold; font-size: 14px'>API Key is valid</div>"; ?>
			<?php if($message != '') echo "<span class='wpsc-message'>" . $message . "</span>"; ?>
			<div class="wpsc-scan-nav-bar" style="width: 75%;">
				<a href="#general-options" id="wpsc-general-options" <?php if ($_POST['wpsc-scan-tab'] != 'scan' && $_POST['wpsc-scan-tab'] != 'empty' && $_POST['wpsc-scan-tab'] != 'grammar' && $_POST['wpsc-scan-tab'] != 'accessibility') echo 'class="selected"';?> name="wpsc-general-options">General Settings</a>
				<a href="#scan-options" id="wpsc-scan-options" <?php if ($_POST['wpsc-scan-tab'] == 'scan') echo 'class="selected"';?> name="wpsc-scan-options">Spell Check Options</a>
				<a href="#grammar-options" id="wpsc-grammar-options" <?php if ($_POST['wpsc-scan-tab'] == 'grammar') echo 'class="selected"';?> name="wpsc-grammar-options">Grammar Options</a>
				<a href="#empty-options" id="wpsc-empty-options" <?php if ($_POST['wpsc-scan-tab'] == 'empty') echo 'class="selected"';?> name="wpsc-empty-options">SEO Empty Field Options<span style="font-size: 8px;"></span></a>
				<a href="#accessibility-options" id="wpsc-accessibility-options" <?php if ($_POST['wpsc-scan-tab'] == 'accessibility') echo 'class="selected"';?> name="wpsc-accessibility-options">Accessibility Options<span style="font-size: 8px;"></span></a>
			</div>
			<form action="admin.php?page=wp-spellcheck-options.php" method="post" name="options" style="margin-top: -7px;" enctype="multipart/form-data">
                        <?php wp_nonce_field('wpsc_update_options'); ?>
			<input type="hidden" name="wpsc-scan-tab" class="wpsc-nav-tab" value="<?php echo $_POST['wpsc-scan-tab']; ?>">
			<div id="wpsc-general-options-tab" <?php if ($_POST['wpsc-scan-tab'] == 'scan' || $_POST['wpsc-scan-tab'] == 'empty' || $_POST['wpsc-scan-tab'] == 'grammar' || $_POST['wpsc-scan-tab'] == 'accessibility') echo 'class="hidden"';?>>
			<table class="form-table wpsc-general-options-table" style="width: 75%; float: left; background: white;" cellpadding="10"><tbody>
				<tr><td scope="row" align="left" style="padding-top: 30px;"><label style="display: inline-block; margin-bottom: 6px;">API Key (For <a target="_blank" href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=spellcheck_options&utm_content=<?php echo $wpsc_version; ?>">Pro</a> Version)</label><br /><input type="text" name="api_key" value="<?php echo $api_key; ?>"></td>
				<td style="padding-top: 30px;"><label style="display: inline-block; margin-bottom: 6px;">Language</label><br /><select style="display: inline-block; width: 140px; height: 27px; margin-top: 1px;" name="language_setting">
<option value="en_CA" <?php if ($language_setting == 'en_CA') echo "selected='selected'"; ?>>English (Canada)</option>
<option value="en_US" <?php if ($language_setting == 'en_US') echo "selected='selected'"; ?>>English (US)</option>
<option value="en_UK" <?php if ($language_setting == 'en_UK') echo "selected='selected'"; ?>>English (UK)</option>
</select></td></tr>
                                <tr><td colspan="2"><hr style="width: 50%;"><br><p style="text-align: center; margin-top: -20px; font-weight: bold;">Setup email notifications to get notified about errors on your website, especially broken HTML and broken shortcode notifications.</p></td></tr>
				<tr><td scope="row" align="left"><span style="display: inline-block; margin-bottom: 6px;"><input type="checkbox" name="email" value="email" <?php if ($email == 'true') echo 'checked'; ?>>Send Email Reports</span><br /><input type="text" name="email_address" value="<?php echo $email_address; ?>"><input type="hidden" name="page" value="wp-spellcheck-options.php">
				<input type="hidden" name="action" value="check">
				<input type="submit" name="test-email" id="test-email" class="button button-primary" value="Send Test"></td><td scope="row" align="left"><label style="display: inline-block; margin-bottom: 6px; margin-top: -2px;">Scan Frequency</label><br />Every <input size="5" name="scan_frequency" style="border: 1px solid #ddd" value="<?php echo $scan_frequency; ?>"><select style="height: 27px; margin-top: 0px; display: inline-block;" name="scan_frequency_interval">
<option value="hourly" <?php if ($scan_frequency_interval == 'hourly') echo "selected='selected'"; ?>>Hour(s)</option>
<option value="daily" <?php if ($scan_frequency_interval == 'daily') echo "selected='selected'"; ?>>Day(s)</option>
<!--<option value="weekly" <?php if ($scan_frequency_interval == 'weekly') echo "selected='selected'"; ?>>Week(s)</option>
<option value="monthly" <?php if ($scan_frequency_interval == 'monthly') echo "selected='selected'"; ?>>Month(s)</option>-->
</select><span class="wpsc-mouseover-button-freq" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-freq">For scans twice a day, set to 12 hours.<br>For scans once per week, set to 7 days.<br>For scans once per month, set to 30 days.</span></td></tr>
				<?php if (is_plugin_active('wp-mail-smtp/wp_mail_smtp.php') != true) { ?>
					<tr><td colspan="2">To ensure emails are delivered, try using <a target="_blank" href="https://en-ca.wordpress.org/plugins/wp-mail-smtp/">WP Mail SMTP</a></td></tr>
				<?php } ?>
				<?php if ($email_address == '' && $email == 'true') { ?>
					<tr><td colspan="2">An email address must be entered in order to receive email alerts</td></tr>
				<?php } ?>
				<tr><td scope="row" align="left"><label>Pages/Posts to ignore (Please enter Page/Post titles and place one on each line)</label></td><td colspan="2"><textarea name="pages-ignore" rows="4" cols="50"><?php echo $page_list; ?></textarea></td></tr>
                                <tr><td colspan="2"><hr style="width: 50%;"><p style="text-align: center; margin-top: -5px; font-weight: bold;">Choose if you want to scan the pages/posts that are saved as drafts</p></td></tr>
				<tr><td scope="row" align="left"><input class="ignore-check-all" type="checkbox" name="check-page-drafts" value="check-page-drafts" <?php if ($check_page_drafts == 'true') echo 'checked'; ?>>Scan Page Drafts</td>
				<td scope="row" align="left"><input class="ignore-check-all" type="checkbox" name="check-post-drafts" value="check-post-drafts" <?php if ($check_post_drafts == 'true') echo 'checked'; ?>>Scan Post Drafts</td></tr>
				
				<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
                                <tr><td colspan="2"><hr style="width: 50%;"></td></tr>
				<tr><td><h2>Import/Export Plugin Data</h2></td></tr>
				<tr>
					<td scope="row" align="left"><input type="checkbox" name="export-dict" value="true" /> Export My Dictionary<br><br><input type="checkbox" name="export-ignore" value="true" /> Export Ignore List</td>
					<td scope="row" align="left"><input type="file" name="import_file" id="import-file"></td>
				</tr>
				<tr>
					<td scope="row" align="left"><input type="submit" name="export" value="Export Plugin Data" /></td>
					<td><input type="submit" name="import" value="Import Plugin Data" /></td>
				</tr>
                                <tr><td colspan="2"><hr style="width: 50%;"></td></tr>
				<tr><td colspan="3" scope="row" align="left"><input type="submit" name="uninstall" value="Clean up Database and Deactivate Plugin" /><span style="margin-left: 10px;">This will deactivate WP Spell Check on all sites on the network and clean up the database of any changes made by WP Spell Check. If you wish to use WP Spell Check again after, you may activate it on the WordPress plugins page</span></td></tr>
			</tbody>
			</table>
			</div>
			<div id="wpsc-scan-options-tab" <?php if ($_POST['wpsc-scan-tab'] != 'scan') echo 'class="hidden"';?>>
				<table class="form-table" style="width: 75%; float: left; background: white;" cellpadding="10"><tbody>
                                        <tr><td colspan="3"><h3>Select which parts of your website you'd like to check for spelling errors and click on the Update button</h3></td></tr>
				<?php if ($pro_included || $ent_included) { ?>
				<tr><td scope="row" align="left"><input type="checkbox" id="check-all" name="check-all" value="check-all">Select All</td></tr>
				<tr><td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-pages" value="check-pages" <?php if ($check_pages == 'true') echo 'checked'; ?>>Pages</td>
				<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="page-titles" value="page-titles" <?php if ($page_titles == 'true') echo 'checked'; ?>>Page Titles</td>
				<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="page-slugs" value="page-slugs" <?php if ($page_slugs == 'true') echo 'checked'; ?>>Page Slugs</td>
				</tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-posts" value="check-posts" <?php if ($check_posts == 'true') echo 'checked'; ?>>Posts</td>
				<td scope="row" align="left"><input type="checkbox" name="post-titles" value="post-titles" <?php if ($post_titles == 'true') echo 'checked'; ?>>Post Titles</td>
				<td scope="row" align="left"><input type="checkbox" name="post-slugs" value="post-slugs" <?php if ($post_slugs == 'true') echo 'checked'; ?>>Post Slugs</td>
				</tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="tags" value="tags" <?php if ($tags == 'true') echo 'checked'; ?>>Tags</td>
				<td scope="row" align="left"><input type="checkbox" name="check-tag-desc" value="check-tag-desc" <?php if ($check_tag_desc == 'true') echo 'checked'; ?>>Tag Descriptions</td>
				<td scope="row" align="left"><input type="checkbox" name="check-tag-slug" value="check-tag-slug" <?php if ($check_tag_slug == 'true') echo 'checked'; ?>>Tag Slugs</td>
				</tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="categories" value="categories" <?php if ($categories == 'true') echo 'checked'; ?>>Categories</td>
				<td scope="row" align="left"><input type="checkbox" name="check-cat-desc" value="check-cat-desc" <?php if ($check_cat_desc == 'true') echo 'checked'; ?>>Category Descriptions</td>
				<td scope="row" align="left"><input type="checkbox" name="check-cat-slug" value="check-cat-slug" <?php if ($check_cat_slug == 'true') echo 'checked'; ?>>Category Slugs</td>
				</tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-media" value="check-media" <?php if ($check_media == 'true') echo 'checked'; ?>>Media Files</td>
				<td scope="row" align="left"><input type="checkbox" name="seo-desc" value="seo-desc" <?php if ($seo_desc == 'true') echo 'checked'; ?>>SEO Descriptions</td>
				<td scope="row" align="left"><input type="checkbox" name="seo-titles" value="seo-titles" <?php if ($seo_titles == 'true') echo 'checked'; ?>>SEO Titles</td>
				</tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-menu" value="check-menu" <?php if ($check_menus == 'true') echo 'checked'; ?>>Wordpress Menus</td>
				<td scope="row" align="left"><input type="checkbox" name="check-sliders" value="check-sliders" <?php if ($check_sliders == 'true') echo 'checked'; ?>>Sliders</td>				
				<td scope="row" align="left"><input type="checkbox" name="check-ecommerce" value="check-ecommerce" <?php if ($check_ecommerce == 'true') echo 'checked'; ?>>WooCommerce and WP-eCommerce Products</td></tr>
				<tr>
				<td scope="row" align="left"><input type="checkbox" name="check-cf7" value="check-cf7" <?php if ($check_cf7 == 'true') echo 'checked'; ?>>Contact Form 7</td>
				<td scope="row" align="left"><input type="checkbox" name="check-authors" value="check-authors" <?php if ($check_authors == 'true') echo 'checked'; ?>>Authors</td>
				<td scope="row" align="left"><input type="checkbox" name="check-widgets" value="check-widgets" <?php if ($check_widgets == 'true') echo 'checked'; ?>>Widgets</td></tr>
				<tr><td><div style="margin-top: 25px;"></div></td></tr>
				<tr><td scope="row" align="left" style="vertical-align: top;"><input class="ignore-check-all" type="checkbox" name="ignore-caps" value="ignore-caps" <?php if ($ignore_caps == 'true') echo 'checked'; ?>>Ignore fully capitalized words</td>
				<td scope="row" align="left" style="vertical-align: top;"><input class="ignore-check-all" type="checkbox" name="ignore-emails" value="ignore-emails" <?php if ($ignore_emails == 'true') echo 'checked'; ?>>Ignore Email Addresses</td></tr>
				<tr><td scope="row" align="left" style="vertical-align: top;"><input class="ignore-check-all" type="checkbox" name="ignore-websites" value="ignore-websites" <?php if ($ignore_websites == 'true') echo 'checked'; ?>>Ignore Website URLs</td>
				<td scope="row" align="left" style="vertical-align: top;"><input <?php if (!$pro_included && !$ent_included) echo "disabled" ?> class="ignore-check-all" type="checkbox" name="highlight-words" value="highlight-words" <?php if ($highlight_words == 'true' && ($pro_included || $ent_included)) echo 'checked'; ?>>Highlight Misspelled Words (For logged in admin only)<?php if (!$pro_included && !$ent_included) echo "<span class='wpsc-mouseover-pro-feature' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-pro-feature'>This is a pro version feature</span></span>"; ?></td></tr>
                                <tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
				<?php } else { ?>
				<tr><td scope="row" align="left"><input type="checkbox" id="check-all" name="check-all" value="check-all">Select All</td></tr>
				<tr><td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-pages" value="check-pages" <?php if ($check_pages == 'true') echo 'checked'; ?>>Pages</td>
				<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-posts" value="check-posts" <?php if ($check_posts == 'true') echo 'checked'; ?>>Posts</td>
				<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-cf7" value="check-cf7" <?php if ($check_cf7 == 'true') echo 'checked'; ?>>Contact Form 7</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-authors" value="check-authors" <?php if ($check_authors == 'true') echo 'checked'; ?>>Authors</td></tr>
				<tr><td><div style="margin-top: 25px;"></div></td></tr>
				<tr><td scope="row" align="left"><input class="ignore-check-all" type="checkbox" name="ignore-caps" value="ignore-caps" <?php if ($ignore_caps == 'true') echo 'checked'; ?>>Ignore fully capitalized words</td>
				<td scope="row" align="left"><input class="ignore-check-all" type="checkbox" name="ignore-emails" value="ignore-emails" <?php if ($ignore_emails == 'true') echo 'checked'; ?>>Ignore Email Addresses</td></tr>
				<tr><td scope="row" align="left"><input class="ignore-check-all" type="checkbox" name="ignore-websites" value="ignore-websites" <?php if ($ignore_websites == 'true') echo 'checked'; ?>>Ignore Website URLs</td>
				<td scope="row" align="left"><input <?php if (!$pro_included && !$ent_included) echo "disabled" ?> class="ignore-check-all" type="checkbox" name="highlight-words" value="highlight-words" <?php if ($highlight_words == 'true' && ($pro_included || $ent_included)) echo 'checked'; ?>>Highlight Misspelled Words (For logged in admin only)<?php if (!$pro_included && !$ent_included) echo "<span class='wpsc-mouseover-pro-feature' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-pro-feature'>This is a pro version feature</span></span>"; ?></td></tr>
                                <tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
                                <tr><td colspan="3"><hr style="width: 50%;"></td></tr>
					<tr style="background: white;"><td colspan="3"><h3 style="color: red;"><a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=spellcheck_options&utm_content=<?php echo $wpsc_version; ?>" target="_blank">Upgrade to Pro</a> to scan the following</h3></td></tr>
					<tr style="background: white;"><td>WordPress Menus</td><td>Page Titles</td><td>Post Titles</td></tr>
					<tr style="background: white;"><td>Tags</td><td>Tag Descriptions</td><td>Tag Slugs</td></tr>
					<tr style="background: white;"><td>Category Slugs</td><td>Categories</td><td>Category Descriptions</td></tr>
					<tr style="background: white;"><td>SEO Descriptions</td><td>SEO Titles</td><td>Page Slugs</td></tr>
					<tr style="background: white;"><td>Post Slugs</td><td>Sliders</td><td>Media Files</td></tr>
					<tr style="background: white;"><td>WooCommerce and WP-eCommerce Products</td></tr>
				<?php } ?>
				<?php if ($pro_included || $ent_included) { ?><tr><td colspan="3" scope="row" align="left"><span style="font-size: 14px; font-weight: bold; color: red;">Warning: When updating <span style="color: black; text-decoration: underline;">page/post slugs</span>, some links contained within the theme may not be updated. Consult your webmaster before updating page/post slugs.<br /><a href="https://www.wpspellcheck.com/about/faqs#update-slugs" target="_blank">Click here to learn more</a></span><br /><br /><span style="font-size: 14px; font-weight: bold; color: red;">When updating <span style="color: black; text-decoration: underline;">Media filenames</span> this may cause images to stop working on your website. This does not apply to descriptions, alternate text, or captions.</span></td></tr> <?php } ?>
			</tbody></table>
		</div>
		<div id="wpsc-empty-options-tab" <?php if ($_POST['wpsc-scan-tab'] != 'empty') echo 'class="hidden"';?>>
				<table class="form-table" style="width: 75%; float: left; background: white;" cellpadding="10"><tbody>
                                    <tr><td colspan="3"><h3>Select which parts of your website you'd like to audit for SEO Empty Fields to improve your SEO. <a href="https://www.wpspellcheck.com/plugin-support/how-to-improve-wordpress-seo/" target="_blank">See Video</a></h3></td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" id="check-all-empty" name="check-all-empty" value="check-all-empty">Select All</td></tr>
				<?php if ($pro_included || $ent_included) { ?>
				<tr><td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-authors-empty" value="check-authors" <?php if ($check_authors_empty == 'true') echo 'checked'; ?>>Authors</td>
				<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-menu-empty" value="check-menu" <?php if ($check_menu_empty == 'true') echo 'checked'; ?>>Wordpress Menus</td>
				<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-page-titles-empty" value="check-page-titles" <?php if ($check_page_titles_empty == 'true') echo 'checked'; ?>>Page Titles</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-post-titles-empty" value="check-post-titles" <?php if ($check_post_titles_empty == 'true') echo 'checked'; ?>>Post Titles</td>
				<td scope="row" align="left"><input type="checkbox" name="check-tag-desc-empty" value="check-tag-desc" <?php if ($check_tag_desc_empty == 'true') echo 'checked'; ?>>Tag Descriptions</td>
				<td scope="row" align="left"><input type="checkbox" name="check-cat-desc-empty" value="check-cat-desc" <?php if ($check_cat_desc_empty == 'true') echo 'checked'; ?>>Category Descriptions</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-page-seo-empty" value="check-page-seo" <?php if ($check_page_seo_empty == 'true') echo 'checked'; ?>>Page SEO</td>
				<td scope="row" align="left"><input type="checkbox" name="check-post-seo-empty" value="check-post-seo" <?php if ($check_post_seo_empty == 'true') echo 'checked'; ?>>Post SEO</td>
				<td scope="row" align="left"><input type="checkbox" name="check-media-seo-empty" value="check-media-seo" <?php if ($check_media_seo_empty == 'true') echo 'checked'; ?>>Media Files SEO</td></tr>
				<tr><td scope="row" align="left"><input type="checkbox" name="check-media-empty" value="check-media" <?php if ($check_media_empty == 'true') echo 'checked'; ?>>Media Files</td>
				<td scope="row" align="left"><input type="checkbox" name="check-ecommerce-empty" value="check-ecommerce" <?php if ($check_ecommerce_empty == 'true') echo 'checked'; ?>>WooCommerce and WP-eCommerce Products</td>
				</tr>
                                <tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
				<?php } else { ?>
				<tr><td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-authors-empty" value="check-authors" <?php if ($check_authors_empty == 'true') echo 'checked'; ?>>Authors</td>
					<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-page-titles-empty" value="check-page-titles" <?php if ($check_page_titles_empty == 'true') echo 'checked'; ?>>Page Titles</td>
					<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-post-titles-empty" value="check-post-titles" <?php if ($check_post_titles_empty == 'true') echo 'checked'; ?>>Post Titles</td></tr>
                                <tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
                                <tr><td colspan="3"><hr style="width: 50%;"></td></tr>
					<tr style="background: white;"><td colspan="3"><h3 style="color: red;"><a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=seo_options&utm_content=<?php echo $wpsc_version; ?>" target="_blank">Upgrade to Pro</a> to scan the following</h3></td></tr>
					<tr style="background: white;"><td>WordPress Menus</td><td>Tag Descriptions</td><td>Category Descriptions</td></tr>
					<tr style="background: white;"><td>Page SEO</td><td>Post SEO</td><td>Media Files SEO</td></tr>
					<tr style="background: white;"><td>Media Files</td><td colspan="2">WooCommerce and WP-eCommerce Products</td></tr>
				<?php } ?>
			</tbody></table>
		</div>
		<div id="wpgc-grammar-options-tab" <?php if ($_POST['wpsc-scan-tab'] != 'grammar') echo 'class="hidden"';?>>
			<table class="form-table" style="width: 75%; float: left; background: white;" cellpadding="10"><tbody>
                                <tr><td colspan="3"><h3>Select whether you want to which parts of your website you'd like to scan for grammar errors. Right  now our plugin checks for the following rules: Complex Expression, Hidden Verb, Passive Voice, Possessive Ending, Redundant Expression, Contractions</h3></td></tr>
				<tr><td><div style="margin-top: 5px;"></div></td></tr>
				<tr>
					<td scope="row" align="left" style="width: 33%;"><input type="checkbox" name="check-pages-grammar" value="check-pages" <?php if ($grammar_pages == 'true') echo 'checked'; ?>>Pages</td>
					<td scope="row" align="left"><input type="checkbox" name="check-posts-grammar" value="check-posts" <?php if ($grammar_posts == 'true') echo 'checked'; ?>>Posts</td>
				</tr>
                                <?php if (!$pro_included && !$ent_included) { ?><tr style="background: white;"><td colspan="3"><h3 style="color: red;"><a href="https://www.wpspellcheck.com/pricing/?utm_source=baseplugin&utm_campaign=upgradeoptions&utm_medium=spellcheck_options&utm_content=<?php echo $wpsc_version; ?>" target="_blank">Upgrade to Pro</a> to scan your entire site</h3></td></tr><?php } ?>
				<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
			</table>
		</div>
		<div id="wpgc-accessibility-options-tab" <?php if ($_POST['wpsc-scan-tab'] != 'accessibility') echo 'class="hidden"';?>>
			<table class="form-table" style="width: 75%; float: left; background: white;" cellpadding="10"><tbody>
				<tr><td><div style="margin-top: 5px;"></div></td></tr>
				<tr>
					<td>Use OpenDyslexic Font<br />You can use the OpenDyslexic font on the website or on both the website and the admin. The OpenDyslexic font is designed to help people with dyslexia with their reading.</td>
					<td>
						<select name="wpsc_usedyslexic" id="wpsc_usedyslexic" >
							<?php
								$user_id = get_current_user_id();
							?>
							<option value="no" <?php selected( 'no', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e('Do Not use the OpenDyslexic Font', 'opendyslexic');?></option>
							<option value="yes_adminonly" <?php selected( 'yes_adminonly', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e('Use only on the admin area (back-end)', 'opendyslexic');?></option>
							<option value="yes_websiteonly" <?php selected( 'yes_websiteonly', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e('Use only on the website (front-end)', 'opendyslexic');?></option>
							<option value="yes_everywhere" <?php selected( 'yes_everywhere', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e('Use both on the website and Admin area', 'opendyslexic');?></option>
						</select>
					</td>
				</tr>
				<tr colspan="2"><td><input type="submit" name="submit" value="Update" class="button button-primary" /></td></tr>
			</table>
		</div>
		</form>
<?php include("sidebar.php"); ?>
</div>
	<?php
	if (isset($_POST['export'])) {
	if ($_POST['export'] == 'Export Plugin Data') { 
		$options_output = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE option_name NOT LIKE "%API%" AND option_name NOT LIKE "%count%" AND option_name NOT LIKE "%scan%" AND option_name NOT LIKE "%checked%" AND option_name NOT LIKE "%type%" AND option_name NOT LIKE "%sip%" AND option_name NOT LIKE "%factor%" AND option_name NOT LIKE "%pro_max%" AND option_name NOT LIKE "%html_%" AND option_name NOT LIKE "%time%";');
		$grammar_output = $wpdb->get_results('SELECT * FROM ' . $grammar_table . ' WHERE option_name NOT LIKE "%API%" AND option_name NOT LIKE "%count%" AND option_name NOT LIKE "%scan%" AND option_name NOT LIKE "%checked%" AND option_name NOT LIKE "%type%" AND option_name NOT LIKE "%sip%" AND option_name NOT LIKE "%factor%" AND option_name NOT LIKE "%pro_max%" AND option_name NOT LIKE "%html_%" AND option_name NOT LIKE "%time%";');
		if ($_POST['export-dict'] == 'true') $dict_output = $wpdb->get_results("SELECT * FROM $dict_table");
		if ($_POST['export-ignore'] == 'true') $ignore_output = $wpdb->get_results("SELECT * FROM $words_table WHERE ignore_word=true");
		
		$options->export_data($options_output, $grammar_output, $dict_output, $ignore_output);
	}
	}
	}
?>