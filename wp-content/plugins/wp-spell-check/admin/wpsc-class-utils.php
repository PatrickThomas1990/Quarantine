<?php

class wpscx_dashboard {
    
     function __construct() {}
   
    function add_dashboard_widget() {
            if (current_user_can('manage_options')) {
                    wp_add_dashboard_widget(
                            'wp_spellcheck_widget',			
                            'WP Spell Check',			
                            array( $this, 'create_dashboard_widget'	)
                    );
            }
    }
	

    function create_dashboard_widget() {
            global $wpdb;


            $table_name = $wpdb->prefix . "spellcheck_words";

            $options_table = $wpdb->prefix . "spellcheck_options";
            $empty_table = $wpdb->prefix . "spellcheck_empty";

            $check_db = $wpdb->get_results("SHOW TABLES LIKE '$options_table'");

            if (sizeof($check_db) >= 1) {
            $empty_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $empty_table WHERE ignore_word!=1" );
            $word_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $table_name WHERE ignore_word!=1" );

            $literacy_factor = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='literary_factor';");
            $literacy_factor = $literacy_factor[0]->option_value;
            $empty_factor = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='empty_factor';");
            $empty_factor = $empty_factor[0]->option_value;
            echo "<p><span style='color: rgb(0, 115, 0); font-weight: bold;'>Website Literacy Factor: </span><span style='color: red; font-weight: bold;'>" . $literacy_factor . "%</span><br />";
            echo "<span style='color: rgb(0, 115, 0); font-weight: bold;'>Website Empty Fields Factor: </span><span style='color: red; font-weight: bold;'>" . $empty_factor . "%</span><br />";
            echo "The last spell check scan found $word_count spelling errors<br />";
            echo "The last empty fields scan found $empty_count empty fields<br />";
            echo "<a href='/wp-admin/admin.php?page=wp-spellcheck.php'>Click here</a> To view and fix errors</p>";
            }
    }
}

class wpscx_opendyslexic {
    
     function __construct() {}
   
    function profile_dyslexic( $user ) {
		?>
			<table class="form-table">
					<tr>
							<th><label><?php _e('Opendyslexic font', 'opendyslexic');?></label></th>
							<td><p><?php _e('You can use the OpenDyslexic font on the website or on both the website and the admin. The OpenDyslexic font is designed to help people with dyslexia with their reading. ', 'opendyslexic');?></p></td>
					</tr>
			<tr>
			<td></td>
			<td>
	 <select name="wpsc_usedyslexic" id="wpsc_usedyslexic" >
							<option value="no" <?php selected( 'no', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e('Do Not use the OpenDyslexic Font', 'opendyslexic');?></option>
							<option value="yes_adminonly" <?php selected( 'yes_adminonly', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e('Use only on the admin area (back-end)', 'opendyslexic');?></option>
							<option value="yes_websiteonly" <?php selected( 'yes_websiteonly', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e('Use only on the website (front-end)', 'opendyslexic');?></option>
							<option value="yes_everywhere" <?php selected( 'yes_everywhere', get_user_meta( $user_id, 'wpsc_usedyslexic', true ) ); ?>><?php _e('Use both on the website and Admin area', 'opendyslexic');?></option>
						</select>
		</td>
			</tr>
			</table>
		<?php
	}
	
	function update_dyslexic($user_id) {
		if ( current_user_can('edit_user',$user_id) )
			update_usermeta($user_id, 'wpsc_usedyslexic', $_POST['wpsc_usedyslexic']);
	}
	
	
	
	function dyslexic_css() {
		$user_ID = get_current_user_id(); 
		$use_opendyslexic = get_user_meta($user_ID, 'wpsc_usedyslexic', true );
		?>
			<style> @font-face { font-family: open-dyslexic; src: url('<?= plugin_dir_url( __FILE__ );?>OpenDyslexic-Regular.ttf'); } </style>
		<?php
		if ($use_opendyslexic=="yes_everywhere" || $use_opendyslexic=="yes_websiteonly") {
			?>
			<style type="text/css">
			* { font-family: open-dyslexic !important }
			</style>
			<?php
		}
	}
        
        function dyslexic_css_admin() {
		$user_ID = get_current_user_id(); 
		$use_opendyslexic = get_user_meta($user_ID, 'wpsc_usedyslexic', true);
		?>
			<style> @font-face { font-family: open-dyslexic; src: url('<?= plugin_dir_url( __FILE__ );?>OpenDyslexic-Regular.ttf'); } </style>
		<?php
		if ($use_opendyslexic=="yes_everywhere" || $use_opendyslexic=="yes_adminonly") {
			?>
			<style type="text/css">
			* { font-family: open-dyslexic !important }
			</style>
			<?php
		}
	}
}

class wpscx_results_utils {
    function ignore_word($ids) {
	global $wpdb;
	global $ent_included;
	$word_list = array();
	$table_name = $wpdb->prefix . 'spellcheck_words';
	$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
	$show_error_ignore = false;
	$show_error_dict = false;
	$word_list[0] = '';
	$added = '';
	$dict_msg = '';
	$ignore_msg = '';
	foreach ($ids as $id) {
		$words = $wpdb->get_results('SELECT word FROM ' . $table_name . ' WHERE id='. $id . ';');
		$word = $words[0]->word;
		$ignore_word = str_replace("'","\'",$word);
		$ignore_word = str_replace("'","\'",$ignore_word);
		$check_word = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE word="' . $ignore_word . '" AND ignore_word = true');
		$check_dict = $wpdb->get_results('SELECT * FROM ' . $dict_table . ' WHERE word="' . $word . '"');
		if (sizeof((array)$check_word) <= 0 && sizeof((array)$check_dict) <= 0) {
			$wpdb->update($table_name, array('ignore_word' => true), array('id' => $id));
			$wpdb->query("DELETE FROM $table_name WHERE id != $id AND word='" . addslashes($word) . "'");
			$added .= stripslashes($word) . ", ";
			
		} else {
			if (sizeof((array)$check_dict) <= 0) {
				$ignore_msg .= stripslashes($word) . ", ";
				$show_error_ignore = true;
			} else {
				$dict_msg .= stripslashes($word) . ", ";
				$show_error_dict = true;
			}
		}
		if ($ent_included) wpscx_print_changelog_dict("ignore list", $word);
	}
	if ($show_error_ignore) {
		$ignore_msg =trim($dict_msg, ", ");
		$word_list[1] = "The following words were already found in the ignore list: " . $ignore_msg;
	}
	if ($show_error_dict) {
		$dict_msg =trim($dict_msg, ", ");
		$word_list[2] = "The following words were already found in the dictionary: " . $dict_msg;
	}
	$added =trim($added, ", ");
	if (strpos($added, ", ") !== false) {
		$word_list[0] = "The following words have been added to ignore list: " . $added;
	} else {
		$word_list[0] = "The following word has been added to ignore list: " . $added;
	}
	return $word_list;
}

function ignore_word_empty($ids) {
	global $wpdb;
	global $ent_included;
	$word_list = array();
	$table_name = $wpdb->prefix . 'spellcheck_empty';
	$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
	$show_error_ignore = false;
	$show_error_dict = false;
	$word_list[0] = '';
	foreach ($ids as $id) {
		$words = $wpdb->get_results('SELECT word FROM ' . $table_name . ' WHERE id='. $id . ';');
		$word = $words[0]->word;
		$ignore_word = str_replace("'","\'",$word);
		$ignore_word = str_replace("'","\'",$ignore_word);
		
		$check_dict = $wpdb->get_results('SELECT * FROM ' . $dict_table . ' WHERE word="' . $word . '"');
		if (sizeof((array)$check_dict) <= 0) {
			$wpdb->update($table_name, array('ignore_word' => true), array('id' => $id));
			
			$word_list[0] .= stripslashes($word) . ", ";
			
		} else {
			if (sizeof((array)$check_dict) <= 0) {
				$word_list[1] .= stripslashes($word) . ", ";
				$show_error_ignore = true;
			} else {
				$word_list[2] .= stripslashes($word) . ", ";
				$show_error_dict = true;
			}
		}
		if ($ent_included) wpscx_print_changelog_dict("ignore list", $word);
	}
	if ($show_error_ignore) {
		$word_list[1] =trim($word_list[1], ", ");
		$word_list[1] = "The following words were already found in the ignore list: " . $word_list[1];
	}
	if ($show_error_dict) {
		$word_list[2] =trim($word_list[2], ", ");
		$word_list[2] = "The following words were already found in the dictionary: " . $word_list[2];
	}
	$word_list[0] =trim($word_list[0], ", ");
	if (strpos($word_list[0], ", ") !== false) {
		$word_list[0] = "The following words have been added to ignore list: " . $word_list[0];
	} else {
		$word_list[0] = "The following word has been added to ignore list: " . $word_list[0];
	}
	return $word_list;
}

function add_to_dictionary($ids) {
	global $wpdb;
	global $ent_included;
	$table_name = $wpdb->prefix . 'spellcheck_words';
	$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
	$word_list = array();
	$show_error_ignore = false;
	$show_error_dict = false;
	foreach ($ids as $id) {
		$words = $wpdb->get_results('SELECT word FROM ' . $table_name . ' WHERE id='. $id . ';');
		$word = $words[0]->word;
		$word = str_replace('%28', '(', $word);
		$ignore_word = str_replace("'","\'",$word);
		$ignore_word = str_replace("'","\'",$ignore_word);
		$check = $wpdb->get_results('SELECT * FROM ' . $dictionary_table . ' WHERE word="' . $word . '"'); 
		$ignore_check = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE word="' . $ignore_word . '" AND ignore_word = true');

		if (sizeof((array)$check) < 1 && sizeof((array)$ignore_check) < 1) {
			$wpdb->insert($dictionary_table, array('word' => stripslashes($word))); 

			$wpdb->delete($table_name, array('word' => $word)); 
			$word_list[0] = $word_list[0] . stripslashes($word) . ", ";
			
		} else {
			if (sizeof((array)$check_dict) <= 0) {
				$word_list[1] .= stripslashes($word) . ", ";
				$show_error_ignore = true;
			} else {
				$word_list[2] .= stripslashes($word) . ", ";
				$show_error_dict = true;
			}
		}
		
		if ($ent_included) wpscx_print_changelog_dict("dictionary", $word);
	}
	if ($show_error_ignore) {
		$word_list[1] = trim($word_list[1], ", ");
		$word_list[1] = "The following words were already found in the ignore list: " . $word_list[1];
	}
	if ($show_error_dict) {
		$word_list[2] = trim($word_list[2], ", ");
		$word_list[2] = "The following words were already found in the dictionary: " . $word_list[2];
	}
	$word_list[0] = trim($word_list[0], ", ");
	if (strpos($word_list[0], ", ") !== false) {
		$word_list[0] = "The following words have been added to dictionary: " . $word_list[0];
	} else {
		$word_list[0] = "The following word has been added to dictionary: " . $word_list[0];
	}
	return $word_list;
}

/*
 *
 * When editing words, individual words get updated first then ones checked off to apply to entire site
 * If duplicates are detected in either list, the one which appears first in the results list takes priority
 * If duplicates are between each list, individual updates take priority over entire site changes
 *
*/
function update_word_admin($old_words, $new_words, $page_names, $page_types, $old_word_ids, $mass_edit) {
        //print_r($new_words);
	global $wpdb;
	global $ent_included;
	$table_name = $wpdb->prefix . 'posts';
	$words_table = $wpdb->prefix . 'spellcheck_words';
	$terms_table = $wpdb->prefix . 'terms';
	$meta_table = $wpdb->prefix . 'postmeta';
	$taxonomy_table = $wpdb->prefix . 'term_taxonomy';
	$user_table = $wpdb->prefix . 'usermeta';
	$dict_table = $wpdb->prefix . "spellcheck_dictionary";
	$word_list = '';
	
	$mass_edit_list = array();
	$mass_edit_list_new = array();
	$mass_edit_words = array();
	$ignore_list = array();
	
	$my_dictionary = $wpdb->get_results("SELECT * FROM $dict_table;");
		
	foreach($my_dictionary as $dict_word) {
			array_push($ignore_list,$dict_word->word);
	}
	
	$my_ignore = $wpdb->get_results("SELECT * FROM $words_table WHERE ignore_word = 1;");
		
	foreach($my_ignore as $dict_word) {
			array_push($ignore_list,$dict_word->word);
	}

for ($x= 0; $x < sizeof((array)$old_words); $x++) {
	$old_words[$x] = str_replace('%28', '(', $old_words[$x]);
	$new_words[$x] = str_replace('%28', '(', $new_words[$x]);
	$old_words[$x] = str_replace('%27', "'", $old_words[$x]);
	$new_words[$x] = str_replace('%27', "'", $new_words[$x]);
        $old_words[$x] = str_replace('%amp;', '&', $old_words[$x]);
	$new_words[$x] = str_replace('%amp;', '&', $new_words[$x]);
        $old_words[$x] = str_replace('%pls;', '+', $old_words[$x]);
        $new_words[$x] = str_replace('%pls;', '+', $new_words[$x]);
        $old_words[$x] = str_replace('%hash;', '#', $old_words[$x]);
        $new_words[$x] = str_replace('%hash;', '#', $new_words[$x]);
	$old_words[$x] = stripslashes(stripslashes($old_words[$x]));
	$new_words[$x] = stripslashes(stripslashes($new_words[$x]));
        
        $old_words[$x] = trim($old_words[$x]);
	
	if (in_array($old_words[$x], $ignore_list)) continue;

	$edit_flag = false;
	if (is_array($mass_edit)) {
	foreach($mass_edit as $edit_id) {
		if ($edit_id == $old_word_ids[$x] && !in_array($old_words[$x], $mass_edit_words)) {
			array_push($mass_edit_list, array('old_word' => $old_words[$x], 'new_word' => $new_words[$x]));
			array_push($mass_edit_words, $old_words[$x]);
			$edit_flag = true;
		} 
	}
	}
	if ($edit_flag) continue;
	$word_id = $old_word_ids[$x];
        $new_words[$x] = str_replace('$', '\$', $new_words[$x]);

	if ($page_types[$x] == 'Post Content' || $page_types[$x] == 'Page Content' || $page_types[$x] == 'Media Description' || $page_types[$x] == 'WooCommerce Product' || $page_types[$x] == 'WP eCommerce Product' ) {
		
		$page_result = $wpdb->get_results('SELECT post_content, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$updated_content = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], html_entity_decode($page_result[0]->post_content));
		$old_name = $page_result[0]->post_title;

		$wpdb->update($table_name, array('post_content' => $updated_content), array('ID' => $page_names[$x]));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Page Custom Field' || $page_types[$x] == 'Post Custom Field') {
                $meta_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE meta_id="' . $page_names[$x] . '"');
                $updated_meta = str_replace($old_words[$x], $new_words[$x], $meta_result[0]->meta_value);
                
                $wpdb->update($meta_table, array('meta_value' => $updated_meta), array('meta_id' => $page_names[$x]));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
        } elseif ($page_types[$x] == 'Contact Form 7 Form' || $page_types[$x] == 'Contact Form 7 Email Notification' || $page_types[$x] == 'Contact Form 7 Auto Response') {
		
		$page_result = $wpdb->get_results('SELECT post_content, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		

		
		$updated_content = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], html_entity_decode($page_result[0]->post_content));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($table_name, array('post_content' => $updated_content), array('ID' => $page_names[$x]));
                if ($page_types[$x] == 'Contact Form 7 Form') {
                    $meta_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="' . $page_names[$x] . '" AND meta_key="_form"');
                    $updated_meta = str_replace($old_words[$x], $new_words[$x], $meta_result[0]->meta_value);
                    //$wpdb->update($meta_table, array('meta_value' => $updated_meta), array('post_id' => $page_names[$x], 'meta_key' => '_form'));   
                    update_post_meta($page_names[$x], '_form', $updated_meta);
                }
                elseif ($page_types[$x] == 'Contact Form 7 Email Notification') {
                    $meta_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="' . $page_names[$x] . '" AND meta_key="_mail"');
                    $updated_meta = str_replace($old_words[$x], $new_words[$x], maybe_unserialize($meta_result[0]->meta_value));
                    //$wpdb->update($meta_table, array('meta_value' => $updated_meta), array('post_id' => $page_names[$x], 'meta_key' => '_mail'));
                    update_post_meta($page_names[$x], '_mail', $updated_meta);
                } elseif ($page_types[$x] == 'Contact Form 7 Auto Response') {
                    $meta_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id="' . $page_names[$x] . '" AND meta_key="_mail_2"');
                    $updated_meta = str_replace($old_words[$x], $new_words[$x], maybe_unserialize($meta_result[0]->meta_value));
                    //$wpdb->update($meta_table, array('meta_value' => $updated_meta), array('post_id' => $page_names[$x], 'meta_key' => '_mail_2'));
                    $updated_meta = preg_replace_callback('!s:\d+:"(.*?)";!s', function($m) { return "s:" . strlen($m[1]) . ':"'.$m[1].'";'; }, $updated_meta);
                    update_post_meta($page_names[$x], '_mail_2', $updated_meta);
                }
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'WooCommerce Product Short Description' || $page_types[$x] == 'WP eCommerce Product Excerpt') {
		
		$page_result = $wpdb->get_results('SELECT post_content, post_title, post_excerpt FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');

		
		$updated_content = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], html_entity_decode($page_result[0]->post_excerpt));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($table_name, array('post_excerpt' => $updated_content), array('ID' => $page_names[$x]));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Menu Item' || $page_types[$x] == 'Post Title' || $page_types[$x] == 'Page Title' || $page_types[$x] == 'Slider Title' || $page_types[$x] == 'Media Title' || $page_types[$x] == 'WP eCommerce Product Name' || $page_types[$x] == 'WooCommerce Product Title') {
		
		$menu_result = $wpdb->get_results('SELECT post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($menu_result[0]->post_title));

		$old_name = $menu_result[0]->post_title;
		$wpdb->update($table_name, array('post_title' => $updated_content), array('ID' => $page_names[$x]));
		$wpdb->update($words_table, array('page_name' => $updated_content), array('page_name' => $old_name)); //Update the title of the page/post/menu in the spellcheck database
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	/*} elseif ($page_types[$x] == 'twitter') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='twitter'");
		$updated_content = str_replace($old_words[$x], $new_words[$x], $author_result[0]->meta_value);
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'twitter'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id"); 
	} elseif ($page_types[$x] == 'facebook') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='facebook'");
		$updated_content = str_replace($old_words[$x], $new_words[$x], $author_result[0]->meta_value);
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'facebook'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id"); */
	} elseif ($page_types[$x] == 'Author Nickname') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='nickname'");
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($author_result[0]->meta_value));
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_result[0]->post_author, 'meta_key' => 'nickname'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Author First Name') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='first_name'");
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($author_result[0]->meta_value));
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'first_name'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Author Last Name') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='last_name'");
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($author_result[0]->meta_value));
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'last_name'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Author Biography') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='description'");
		
		$updated_content = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], html_entity_decode($author_result[0]->meta_value));
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'description'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Author SEO Title') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='wpseo_title'");
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($author_result[0]->meta_value));
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'wpseo_title'));
		$wpdb->delete($words_table, array('word' => $old_words[$x], 'id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author SEO Description') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='wpseo_metadesc'");
		
		$updated_content = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], html_entity_decode($author_result[0]->meta_value));
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'wpseo_metadesc'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Site Name') {
		$opt_table = $wpdb->prefix . "options";
	
		$site_result = $wpdb->get_results("SELECT * FROM $opt_table WHERE option_name='blogname'");
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($site_result[0]->option_value));
	
		$wpdb->update($opt_table, array('option_value' => $updated_content), array('option_name' => 'blogname'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Site Tagline') {
		$opt_table = $wpdb->prefix . "options";
	
		$site_result = $wpdb->get_results("SELECT * FROM $opt_table WHERE option_name='blogdescription'");
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($site_result[0]->option_value));
	
		$wpdb->update($opt_table, array('option_value' => $updated_content), array('option_name' => 'blogdescription'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Slider Caption') {
		
		$menu_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$caption = get_post_meta($menu_result[0]->ID, 'my_slider_caption', true);
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($caption));

		update_post_meta($menu_result[0]->ID, 'my_slider_caption', $updated_content);
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Huge IT Slider Caption') {
		
		$it_table = $wpdb->prefix . 'huge_itslider_images';
		$menu_result = $wpdb->get_results('SELECT name, description FROM ' . $it_table . ' WHERE id="' . $page_names[$x] . '"');
		
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($menu_result[0]->description));
		
		$wpdb->update($it_table, array('description' => $updated_content), array('id' => $page_names[$x]));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Huge IT Slider Title') {
		
		$it_table = $wpdb->prefix . 'huge_itslider_images';
		$menu_result = $wpdb->get_results('SELECT name FROM ' . $it_table . ' WHERE id="' . $page_names[$x] . '"');
		
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($menu_result[0]->name));	

		$wpdb->update($it_table, array('name' => $updated_content), array('id' => $page_names[$x]));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Smart Slider Caption') {
		
		$slider_table = $wpdb->prefix . 'wp_nextend_smartslider_slides';
		$menu_result = $wpdb->get_results('SELECT description FROM ' . $slider_table . ' WHERE id="' . $page_names[$x] . '"');
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($menu_result[0]->description));

		$wpdb->update($slider_table, array('description' => $updated_content), array('id' => $page_names[$x]));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Smart Slider Title') {
		
		$slider_table = $wpdb->prefix . 'wp_nextend_smartslider_slides';
		$menu_result = $wpdb->get_results('SELECT title FROM ' . $slider_table . ' WHERE id="' . $page_names[$x] . '"');
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($menu_result[0]->title));

		$wpdb->update($slider_table, array('title' => $updated_content), array('id' => $page_names[$x]));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Media Alternate Text') {
		
		$menu_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$caption = get_post_meta($menu_result[0]->ID, '_wp_attachment_image_alt', true);
		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($caption));

		update_post_meta($menu_result[0]->ID, '_wp_attachment_image_alt', $updated_content);
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Media Caption') {
		
		$page_result = $wpdb->get_results('SELECT post_excerpt, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');

		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($page_result[0]->post_excerpt));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($table_name, array('post_excerpt' => $updated_content), array('ID' => $page_names[$x]));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Tag Title' || $page_types[$x] == 'Category Title' || $page_types[$x] == 'WooCommerce Category Title' || $page_types[$x] == 'WooCommerce Tag Title') {
		
		$tag_result = $wpdb->get_results('SELECT name FROM ' . $terms_table . ' WHERE term_id=' . $page_names[$x]);

		$updated_content = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], html_entity_decode($tag_result[0]->name));

		$wpdb->update($terms_table, array('name' => $updated_content), array('name' => $tag_result[0]->name));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Tag Description' || $page_types[$x] == 'WooCommerce Tag Description') {
		
		$tag_result = $wpdb->get_results('SELECT description FROM ' . $taxonomy_table . ' WHERE term_id=' . $page_names[$x]);

		$updated_content = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], html_entity_decode($tag_result[0]->description));
                
                echo $updated_content . "<br>";

		$wpdb->update($taxonomy_table, array('description' => $updated_content), array('description' => $tag_result[0]->description));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Category Description' || $page_types[$x] == 'WooCommerce Category Description') {
		
		$tag_result = $wpdb->get_results('SELECT description FROM ' . $taxonomy_table . ' WHERE term_id=' . $page_names[$x]);

		$updated_content = preg_replace(wpscx_regex_pattern($old_words[$x]), $new_words[$x], html_entity_decode($tag_result[0]->description));

		$wpdb->update($taxonomy_table, array('description' => $updated_content), array('description' => $tag_result[0]->description));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Post Custom Field') {
		
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_value LIKE "%' . $old_words[$x] . '%"');

		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($desc_result[0]->meta_value));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Yoast SEO Description') {
		
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_yoast_wpseo_metadesc"');

		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($desc_result[0]->meta_value));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_yoast_wpseo_metadesc'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'All in One SEO Description') {
		
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_aioseop_description"');

		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($desc_result[0]->meta_value));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_aioseop_description'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Ultimate SEO Description') {
		
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_su_description"');

		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($desc_result[0]->meta_value));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_su_description'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Yoast SEO Title') {
		
		$page_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_yoast_wpseo_title"');

		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($desc_result[0]->meta_value));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_yoast_wpseo_title'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'All in One SEO Title') {
		$page_result = $wpdb->get_results('SELECT ID FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_aioseop_title"');

		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($desc_result[0]->meta_value));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_aioseop_title'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Ultimate SEO Title') {
		$page_result = $wpdb->get_results('SELECT ID FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$desc_result = $wpdb->get_results('SELECT meta_value FROM ' . $meta_table . ' WHERE post_id=' . $page_result[0]->ID . ' AND meta_key="_su_title"');

		$updated_content = str_replace($old_words[$x], $new_words[$x], html_entity_decode($desc_result[0]->meta_value));

		$old_name = $page_result[0]->post_title;
		$wpdb->update($meta_table, array('meta_value' => $updated_content), array('post_id' => $page_result[0]->ID, 'meta_key' => '_su_title'));
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	} elseif ($page_types[$x] == 'Widget Content') {
		$widget_instances = get_option('widget_text');
		
		foreach (array_keys($widget_instances) as $index) {
			if ($widget_instances[$index]['title'] == $page_names[$x]) {
				$widget_instances[$index]['text'] = str_replace($old_words[$x], $new_words[$x], html_entity_decode($widget_instances[$index]['text']));
			}
		}
		
		update_option('widget_text',$widget_instances);
		$wpdb->query("DELETE FROM $words_table WHERE id=$word_id");
	}
	

	
	$page_url = get_permalink( $page_names[$x] );
	$page_title = get_the_title( $page_names[$x] );
        $new_words[$x] = str_replace('\$', '$', $new_words[$x]);
	$word_list .= stripslashes($old_words[$x]) . " to " . $new_words[$x] . ", ";
	
	$url = wpscx_construct_url($page_types[$x], $page_names[$x]);
	if ($ent_included) wpscx_print_changelog($old_words[$x], $new_words[$x], $page_types[$x], $url);
	
	}
	
	$return_message = "";
	if ($ent_included) {
		$url = plugins_url()."/wp-spell-check-pro/admin/changes.php";
		$view_link = "<a target='_blank' href='$url'>Click here</a> to view the changelog";
	} else {

		$view_link = "<span style='color: grey;'>Click here to view the changelog</a><span class='wpsc-mouseover-button-change' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?</span><span class='wpsc-mouseover-text-change'>To view the changelog, you must <a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Upgrade to Pro</a></span>";
	}
	if (sizeof((array)$mass_edit_list) > 0 && $ent_included) {
		$return_message = wpsc_mass_edit($mass_edit_list);
		$return_message .= "<br />";
	}
	
	$word_list =trim($word_list, ", ");
	//echo "Word List: |" . $word_list . "|";
	if (strpos($word_list, ", ") !== false) {
		return $return_message . "The following words have been updated: " . $word_list . "<br>" . $view_link;
	} else {
		if ($word_list != '') {
			return $return_message . "The following word has been updated: " . $word_list . "<br>" . $view_link;
		} else {
			return $return_message . $view_link;
		}
	}
}

function update_empty_admin($new_words, $page_names, $page_types, $old_word_ids) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'posts';
	$words_table = $wpdb->prefix . 'spellcheck_empty';
	$terms_table = $wpdb->prefix . 'terms';
	$meta_table = $wpdb->prefix . 'postmeta';
	$taxonomy_table = $wpdb->prefix . 'term_taxonomy';
	$user_table = $wpdb->prefix . 'usermeta';
	$word_list = '';
	$seo_error = false;

for ($x= 0; $x < sizeof((array)$new_words); $x++) {
	$new_words[$x] = str_replace('%28', '(', $new_words[$x]);
	$new_words[$x] = str_replace('%27', "'", $new_words[$x]);
	$new_words[$x] = stripslashes($new_words[$x]);
	
	if ($page_types[$x] == 'Media Description') {
		
		$page_result = $wpdb->get_results('SELECT post_content FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');

		$updated_content = $new_words[$x];

		$wpdb->update($table_name, array('post_content' => $updated_content), array('ID' => $page_names[$x]));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'WooCommerce Product Excerpt' || $page_types[$x] == 'WP eCommerce Product Excerpt') {
		
		$page_result = $wpdb->get_results('SELECT post_content, post_title, post_excerpt FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');

		$updated_content = $new_words[$x];

		$old_name = $page_result[0]->post_title;
		$wpdb->update($table_name, array('post_excerpt' => $updated_content), array('ID' => $page_names[$x]));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Menu Item' || $page_types[$x] == 'Post Title' || $page_types[$x] == 'Page Title' || $page_types[$x] == 'Slider Title' || $page_types[$x] == 'WP eCommerce Product Name' || $page_types[$x] == 'WooCommerce Product Name') {
		
		$menu_result = $wpdb->get_results('SELECT post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$updated_content = $new_words[$x];

		$wpdb->update($table_name, array('post_title' => $updated_content), array('ID' => $page_names[$x]));
		$wpdb->update($words_table, array('page_name' => $updated_content), array('id' => $old_word_ids[$x])); //Update the title of the page/post/menu in the spellcheck database
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author Nickname') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='nickname'");
		$updated_content = $new_words[$x];
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'nickname'));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author First Name') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='first_name'");
		$updated_content = $new_words[$x];
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'first_name'));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author Last Name') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='last_name'");
		$updated_content = $new_words[$x];
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'last_name'));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author Biographical Information') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='description'");
		$updated_content = $new_words[$x];
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'description'));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author twitter') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='twitter'");
		$updated_content = $new_words[$x];
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'twitter'));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author googleplus') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='googleplus'");
		$updated_content = $new_words[$x];
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'googleplus'));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author facebook') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='facebook'");
		$updated_content = $new_words[$x];
	
		$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'facebook'));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	}  elseif ($page_types[$x] == 'Author SEO Title') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='wpseo_title'");
		$updated_content = $new_words[$x];
		
		if (sizeof((array)$author_result) <= 0) {
			$wpdb->insert($user_table, array('meta_value' => $updated_content, 'meta_key' => 'wpseo_title', 'user_id' => $page_names[$x]));
		} else {
			$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_result[0]->post_author, 'meta_key' => 'wpseo_title'));
		}
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Author SEO Description') {
		$author_result = $wpdb->get_results("SELECT * FROM $user_table WHERE user_id=" . $page_names[$x] . " AND meta_key='wpseo_metadesc'");
		$updated_content = $new_words[$x];
	
		if (sizeof((array)$author_result) <= 0) {
			$wpdb->insert($user_table, array('meta_value' => $updated_content, 'meta_key' => 'wpseo_metadesc', 'user_id' => $page_result[0]->post_author));
		} else {
			$wpdb->update($user_table, array('meta_value' => $updated_content), array('user_id' => $page_names[$x], 'meta_key' => 'wpseo_metadesc'));
		}
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Media Alternate Text') {
		
		$menu_result = $wpdb->get_results('SELECT ID, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');
		$caption = get_post_meta($menu_result[0]->ID, '_wp_attachment_image_alt', true);
		$updated_content = $new_words[$x];

		update_post_meta($menu_result[0]->ID, '_wp_attachment_image_alt', $updated_content);
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Media Caption') {
		
		$page_result = $wpdb->get_results('SELECT post_excerpt, post_title FROM ' . $table_name . ' WHERE ID="' . $page_names[$x] . '"');

		$updated_content = $new_words[$x];

		$wpdb->update($table_name, array('post_excerpt' => $updated_content), array('ID' => $page_names[$x]));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Tag Description') {
		
		$tag_result = $wpdb->get_results('SELECT description FROM ' . $taxonomy_table . ' WHERE term_id=' . $page_names[$x]);

		$updated_content = $new_words[$x];

		$wpdb->update($taxonomy_table, array('description' => $updated_content), array('term_id' => $page_names[$x]));
		$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
	} elseif ($page_types[$x] == 'Category Description') {
		
		$tag_result = $wpdb->get_results('SELECT description FROM ' . $taxonomy_table . ' WHERE term_id=' . $page_names[$x]);

		$updated_content = $new_words[$x];

		$wpdb->update($taxonomy_table, array('description' => $updated_content), array('term_id' => $page_names[$x]));
		$wpdb->delete($words_table, array('word' => $old_words[$x])); 
	} elseif ($page_types[$x] == 'SEO Page Title' || $page_types[$x] == 'SEO Post Title' || $page_types[$x] == 'SEO Media Title') {
		if (is_plugin_active('wordpress-seo/wp-seo.php')) {
			
			$wpdb->insert($meta_table, array('post_id' => $page_names[$x], 'meta_key' => "_yoast_wpseo_title", 
			'meta_value' => $new_words[$x]));
			
			$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
		} elseif (is_plugin_active('seo-ultimate/seo-ultimate.php')) {
			$wpdb->insert($meta_table, array('post_id' => $page_names[$x], 'meta_key' => "_su_title", 
			'meta_value' => $new_words[$x]));
			
			$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
		} elseif (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
			$wpdb->insert($meta_table, array('post_id' => $page_names[$x], 'meta_key' => "_aioseop_title", 
			'meta_value' => $new_words[$x]));
			
			$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
		} else {
			$seo_error = true;
		}
	} elseif ($page_types[$x] == 'SEO Page Description' || $page_types[$x] == 'SEO Post Description' || $page_types[$x] == 'SEO Media Description') {
		if (is_plugin_active('wordpress-seo/wp-seo.php')) {
			
			$wpdb->insert($meta_table, array('post_id' => $page_names[$x], 'meta_key' => "_yoast_wpseo_metadesc", 
			'meta_value' => $new_words[$x]));
			
			$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
		} elseif (is_plugin_active('seo-ultimate/seo-ultimate.php')) {
			$wpdb->insert($meta_table, array('post_id' => $page_names[$x], 'meta_key' => "_su_description", 
			'meta_value' => $new_words[$x]));
			
			$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
		} elseif (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
			$wpdb->insert($meta_table, array('post_id' => $page_names[$x], 'meta_key' => "_aioseop_description", 
			'meta_value' => $new_words[$x]));
			
			$wpdb->delete($words_table, array('id' => $old_word_ids[$x])); 
		} else {
			$seo_error = true;
		}
	}
	

	
	$page_url = get_permalink( $page_names[$x] );
	$page_title = get_the_title( $page_names[$x] );
	$current_time = date( 'l F d, g:i a' );
	//$loc = dirname(__FILE__) . "/spellcheck.debug";
	//$debug_file = fopen($loc, 'a');
	//$debug_var = fwrite( $debug_file, " Empty Field | New Word: " . $new_words[$x] . " | Type: " . $page_types[$x] . " | Page Name: " . $page_title . " | Page URL: " . $page_url . " | Timestamp: " . $current_time . "\r\n\r\n" );
	//fclose($debug_file);
	}
	
	$message = "";
	if ($seo_error) $message = "<div style='color: #FF0000'>SEO fields could not be updated because no active SEO plugin could be detected</div>";
	return "Empty Fields have been updated" . $message;
}
}

    function wpscx_preview_highlights($content) {
        
        if (!isset($_GET['preview']) || $_GET['preview'] != 'true') return $content;
        
        $content = str_replace("background: #FFC0C0", "background: None", $content);
        $content = str_replace("background: #a3c5ff;", "background: None", $content);
        $content = str_replace("background: #59c033;", "background: None", $content);
	return $content;
    }
        
    if (isset($_GET['preview']) && $_GET['preview'] == 'true') {
        add_filter ("the_content", "wpscx_preview_highlights");
    }