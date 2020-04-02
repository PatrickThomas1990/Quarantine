<?php
if(!defined('ABSPATH')) { exit; }
/* Admin Classes */
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
class sc_table extends WP_List_Table {

	function __construct() {
		global $status, $page;
		
		
		parent::__construct( array(
			'singular' => 'word',
			'plural' => 'words',
			'ajax' => true
		) );
                
                $this->set_pagination_args(
                    array(
                        'total_items'   => $total_items,
                        'per_page'  => $per_page,
                        'total_pages'   => ceil( $total_items / $per_page ),
                        'orderby'   => ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'title',
                        'order'     => ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
                    )
                );
	}
        
        function display() {
            wp_nonce_field( 'ajax-wpsc-list-nonce', '_ajax_wpsc_list_nonce' );

            echo '<input id="order" type="hidden" name="order" value="' . $this->_pagination_args['order'] . '" />';
            echo '<input id="orderby" type="hidden" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';

            parent::display();
        }
        
        function ajax_response() {
            check_ajax_referer( 'ajax-wpsc-list-nonce', '_ajax_wpsc_list_nonce' );
 
            $this->prepare_items();

            extract( $this->_args );
            extract( $this->_pagination_args, EXTR_SKIP );

            ob_start();
            if ( ! empty( $_REQUEST['no_placeholder'] ) )
                $this->display_rows();
            else
                $this->display_rows_or_placeholder();
            $rows = ob_get_clean();

            ob_start();
            $this->print_column_headers();
            $headers = ob_get_clean();

            ob_start();
            $this->pagination('top');
            $pagination_top = ob_get_clean();

            ob_start();
            $this->pagination('bottom');
            $pagination_bottom = ob_get_clean();

            $response = array( 'rows' => $rows );
            $response['pagination']['top'] = $pagination_top;
            $response['pagination']['bottom'] = $pagination_bottom;
            $response['column_headers'] = $headers;

            if ( isset( $total_items ) )
                $response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

            if ( isset( $total_pages ) ) {
                $response['total_pages'] = $total_pages;
                $response['total_pages_i18n'] = number_format_i18n( $total_pages );
            }

            die( json_encode( $response ) );
        }
	
	function column_default($item, $column_name) {
		return print_r($item,true);
	}
	
	
	function column_word($item) {
		set_time_limit(600); 
		global $wpdb;
		global $dict_list;
		global $wpsc_settings;
		global $ent_included;
		$table_name = $wpdb->prefix . 'spellcheck_options';
		$dict_table = $wpdb->prefix . "spellcheck_dictionary";
		$language_setting = $wpsc_settings[11];
		$dict_words = $dict_list;
		
		if ($ent_included) {
			$loc = dirname(__FILE__) . "/../../wp-spell-check-pro/admin/dict/" . $language_setting->option_value . ".pws";
		} else {
			$loc = dirname(__FILE__) . "/dict/" . $language_setting->option_value . ".pws";
		}
		
		$file = fopen($loc, 'r');
		$contents = fread($file,filesize($loc));
		fclose($file);
		
		$word_list = array();
		foreach ($dict_words as $dict_word) {
			array_push($word_list,$dict_word->word);
		}
		
		$my_dictionary = $wpdb->get_results("SELECT * FROM $dict_table;");
		
		foreach($my_dictionary as $dict_word) {
			array_push($word_list,$dict_word->word);
		}
	
		$contents = str_replace("\r\n", "\n", $contents);
		$main_list = explode("\n", $contents);

		$word_list = array_merge($word_list,$main_list);
	
		$suggestions = array();
		$suggestions_holding = array();
		
		$start = round(microtime(true),5);
		$first_word = stripslashes($item['word']);
		foreach ($word_list as $words) {
			if (strlen($words) >= strlen($first_word) - 2 && strlen($words) <= strlen($first_word) + 2) {
				similar_text(strtoupper($first_word),strtoupper($words),$percentage);
				if ($percentage > 85.00) {
					if ($first_word[0] == strtoupper($first_word[0])) { array_push($suggestions_holding,array(ucfirst($words),$percentage));
					} else { array_push($suggestions_holding,array(lcfirst($words), $percentage)); }
				}
			}
		}

		
		for ($x = 0; $x < sizeof((array)$suggestions_holding); $x++ ) {
			$temp = '';
			$temp_per = 0;
			$temp_index = 0;
				for ($y = 0; $y < sizeof((array)$suggestions_holding); $y++ ) {
					if ($suggestions_holding[$y][1] > $temp_per) {
						$temp = $suggestions_holding[$y][0];
						$temp_per = $suggestions_holding[$y][1];
						$temp_index = $y;
					}
				}
			//if ($item['word'] == 'Havent') print_r($x);
			if ($temp != '') {
				array_push($suggestions, $temp);
				$suggestions_holding[$temp_index][1] = 0;
			}
			if (sizeof((array)$suggestions) >= 4) break;
		}
		/*if (sizeof((array)$suggestions) < 4) {
			foreach ($word_list as $words) {
				
				$first_word = stripslashes($item['word']);
				if (gettype($words) == 'string') similar_text(strtoupper($first_word),strtoupper($words),$percentage);
				if ($percentage > 60.00)
					array_push($suggestions,$words);
					
				if (sizeof((array)$suggestions) >= 4) break;
			}
		}*/

		$sorting = '';
		if ($_GET['orderby'] != '') $sorting .= '&orderby=' . $_GET['orderby'];
		if ($_GET['order'] != '') $sorting .= '&order=' . $_GET['order'];
		if ($_GET['paged'] != '') $sorting .= '&paged=' . $_GET['paged'];

		
		if ($item['word'] == "Empty Field") {
			if ($item['page_type'] == 'Page Slug' || $item['page_type'] == 'Post Slug' || $item['page_type'] == 'Tag Slug' || $item['page_type'] == 'Category Slug') {
				$actions = array (
					'Ignore'      			=> sprintf('<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore'),
				);
			} else {
				$actions = array (
					'Edit'					=> sprintf('<a href="#" class="wpsc-edit-button" page_type="' . $item['page_type'] . '" id="wpsc-word-' . $item['word'] . '">Edit</a>'),
					'Ignore'      			=> sprintf('<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore')
				);
			}
		} else {
			if ($item['page_type'] == 'Page Slug' || $item['page_type'] == 'Post Slug' || $item['page_type'] == 'Tag Slug' || $item['page_type'] == 'Category Slug') {
				$actions = array (
					'Ignore'      			=> sprintf('<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore'),
					'Add to Dictionary'		=> sprintf('<input type="checkbox" class="wpsc-add-checkbox" name="add-word[]" value="' . $item['id'] . '" />Add to Dictionary')
				);
			} else {
				$actions = array (
					'Ignore'      			=> sprintf('<input type="checkbox" class="wpsc-ignore-checkbox" name="ignore-word[]" value="' . $item['id'] . '" />Ignore'),
					'Add to Dictionary'		=> sprintf('<input type="checkbox" class="wpsc-add-checkbox" name="add-word[]" value="' . $item['id'] . '" />Add to Dictionary'),
                                        'Suggested Spelling'	=> sprintf('<br><a href="#" class="wpsc-suggest-button" suggestions="' . $suggestions[0] . '-' . $suggestions[1] . '-' . $suggestions[2] . '-' . $suggestions[3] . '">Suggested Spelling</a>'),
					'Edit'					=> sprintf('<a href="#" class="wpsc-edit-button" page_type="' . $item['page_type'] . '" id="wpsc-word-' . $item['word'] . '">Edit</a>')
				);
			}
		}
		
		
		return sprintf('%1$s<span style="background-color:#0096ff; float: left; margin: 3px 5px 0 -30px; display: block; width: 12px; height: 12px; border-radius: 16px; opacity: 1.0;"></span>%3$s',
            stripslashes(stripslashes($item['word'])),
            $item['ID'],
            $this->row_actions($actions)
        );
	}
	
	
	function column_page_name($item) {
		$start = round(microtime(true),5);
		$sql_count = 0;
		
		global $wpdb;
		$link = urldecode ( get_permalink( $item['page_id'] ) );
		$handle = curl_init($url);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

		$response = curl_exec($handle);

		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		if($httpCode == 404) {
			$output = '';
		} elseif ($item['page_type'] == 'Menu Item') {
			$output = '<a href="/wp-admin/nav-menus.php?action=edit&menu='.$item['page_id'].'" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		} elseif ($item['page_type'] == 'Contact Form 7') {
			$output = '<a href="admin.php?page=wpcf7&post='.$item['page_id'].'&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		} elseif ($item['page_type'] == 'Post Title' || $item['page_type'] == 'Page Title' || $item['page_type'] == 'Yoast SEO Description' || $item['page_type'] == 'All in One SEO Description' || $item['page_type'] == 'Ultimate SEO Description' || $item['page_type'] == 'SEO Description' || $item['page_type'] == 'Yoast SEO Title' || $item['page_type'] == 'All in One SEO Title' || $item['page_type'] == 'Ultimate SEO Title' || $item['page_type'] == 'SEO Title' || $item['page_type'] == 'Post Slug' || $item['page_type'] == 'Page Slug') {
			$output = '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		} elseif ($item['page_type'] == 'Slider Title' || $item['page_type'] == 'Slider Caption' || $item['page_type'] == 'Smart Slider Title' || $item['page_type'] == 'Smart Slider Caption') {
			$output = '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		} elseif ($item['page_type'] == 'Huge IT Slider Title' || $item['page_type'] == 'Huge IT Slider Caption') {
			$output = '<a href="/wp-admin/admin.php?page=sliders_huge_it_slider&task=edit_cat&id=' . $item['page_id'] . '" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		} elseif ($item['page_type'] == 'Media Title' || $item['page_type'] == 'Media Description' || $item['page_type'] == 'Media Caption' || $item['page_type'] == 'Media Alternate Text') {
			$output = '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		} elseif ($item['page_type'] == 'Tag Title' || $item['page_type'] == 'Tag Description' || $item['page_type'] == 'Tag Slug') {
			$output = '<a href="/wp-admin/term.php?taxonomy=post_tag&tag_ID=' . $item['page_id'] . '&post_type=post" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
                } elseif ($item['page_type'] == "WooCommerce Tag Description" || $item['page_type'] == "WooCommerce Tag Title") {
                        $output = '<a href="/wp-admin/term.php?taxonomy=product_tag&tag_ID=' . $item['page_id'] . '&post_type=product" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
                } elseif ($item['page_type'] == 'WooCommerce Category Description' || $item['page_type'] == 'WooCommerce Category Title') {
                        $output = '<a href="/wp-admin/term.php?taxonomy=product_cat&tag_ID=' . $item['page_id'] . '&post_type=product" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
                } elseif ($item['page_type'] == 'Post Category' || $item['page_type'] == 'Category Description' || $item['page_type'] == 'Category Slug') {
			$output = '<a href="/wp-admin/term.php?taxonomy=category&tag_ID=' . $item['page_id'] . '&post_type=post" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		} elseif($item['page_type'] == 'Author Nickname' || $item['page_type'] == 'Author First Name' || $item['page_type'] == 'Author Last Name' || $item['page_type'] == 'Author Biography' || $item['page_type'] == 'Author SEO Title' || $item['page_type'] == 'Author SEO Description' || $item['page_type'] == 'twitter' || $item['page_type'] == 'facebook' || $item['page_type'] == 'Author facebook' || $item['page_type'] == 'Author twitter' || $item['page_type'] == 'Author googleplus') {
			$output = '<a href="/wp-admin/user-edit.php?user_id=' . $item['page_id'] . ' " id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		} elseif($item['page_type'] == "Site Name" || $item['page_type'] == "Site Tagline") {
			$output = '<a href="/wp-admin/options-general.php" target="_blank">View</a>';
		} elseif($item['page_type'] == "Widget Content") {
			$output = '<a href="/wp-admin/widgets.php" id="wpsc-page-name" page="' . $item['page_name'] . '" target="_blank">View</a>';
		} elseif($item['page_type'] == "Post Custom Field" || $item['page_type'] == "Page Custom Field") {
                        $postmeta = $wpdb->prefix . "postmeta";
                        $result = $wpdb->get_results("SELECT * FROM $postmeta WHERE meta_id = " . $item['page_id']);
                        $output = '<a href="/wp-admin/post.php?post=' . $result[0]->post_id . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
                } else {
			$output = '<a href="' . $link . '" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		}
		if (($item['page_type'] == "WP eCommerce Product Excerpt" || $item['page_type'] == "WP eCommerce Product Name" || $item['page_type'] == "WooCommerce Product Excerpt" || $item['page_type'] == "WooCommerce Product Title" || $item['page_type'] == "WooCommerce Product Short Description" || $item['page_type'] == "WooCommerce Category Title" || $item['page_type'] == "WooCommerce Category Description" || $item['page_type'] == "WooCommerce Tag Title" || $item['page_type'] == "WooCommerce Tag Description" || $item['page_type'] == "WooCommerce Product Name" || $item['page_type'] == "Page Title" || $item['page_type'] == "Post Title" || $item['page_type'] == 'Yoast SEO Page Description' || $item['page_type'] == 'All in One SEO Page Description' || $item['page_type'] == 'Ultimate SEO Page Description' || $item['page_type'] == 'SEO Page Description' || $item['page_type'] == 'Yoast SEO Page Title' || $item['page_type'] == 'All in One SEO Page Title' || $item['page_type'] == 'Ultimate SEO Page Title' || $item['page_type'] == 'SEO Page Title' || $item['page_type'] == 'Yoast SEO Post Description' || $item['page_type'] == 'All in One SEO Post Description' || $item['page_type'] == 'Ultimate SEO Post Description' || $item['page_type'] == 'SEO Post Description' || $item['page_type'] == 'Yoast SEO Post Title' || $item['page_type'] == 'All in One SEO Post Title' || $item['page_type'] == 'Ultimate SEO Post Title' || $item['page_type'] == 'SEO Post Title' || $item['page_type'] == 'Yoast SEO Media Description' || $item['page_type'] == 'All in One SEO Media Description' || $item['page_type'] == 'Ultimate SEO Media Description' || $item['page_type'] == 'SEO Media Description' || $item['page_type'] == 'Yoast SEO Media Title' || $item['page_type'] == 'All in One SEO Media Title' || $item['page_type'] == 'Ultimate SEO Media Title' || $item['page_type'] == 'SEO Media Title') && $item['word'] == "Empty Field") {
			$output = '<a href="/wp-admin/post.php?post=' . $item['page_id'] . '&action=edit" id="wpsc-page-name" page="' . $item['page_id'] . '" target="_blank">View</a>';
		}

		curl_close($handle);
		$actions = array (
			'View'      			=> sprintf($output),
		);
		
		/*$end = round(microtime(true),5);
		$loc = dirname(__FILE__)."/../../../../results.log";
		$debug_file = fopen($loc, 'a');
		$debug_var = fwrite( $debug_file, "Page Name Column     Time: " . round($end - $start,5) . ".      SQL: " . $sql_count . ".     Memory: " . round(memory_get_usage() / 1000,5) . " KB\r\n" );
		fclose($debug_file);*/
		
		
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['page_name'],
            $item['ID'],
            $this->row_actions($actions)
        );
	}

	
	function column_page_type($item) {
		
		$actions = array ();
		
		
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['page_type'],
            $item['ID'],
            $this->row_actions($actions)
        );
	}
	
	function column_count($item) {
		
		$actions = array ();
		
		
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['count'],
            $item['ID'],
            $this->row_actions($actions)
        );
	}

	
	function get_columns() {
		global $ent_included;
                $page = $_GET['page'];
                if ($page == 'wp-spellcheck-seo.php') { 
                    $columns = array(
                        'cb' => '<input type="checkbox" />',
                        'word' => 'SEO Empty Field',
                        'page_name' => 'Page',
                        'page_type' => 'Page Type'
                    );
                } else {
                    if ($ent_included) {
                            $columns = array(
                                    'cb' => '<input type="checkbox" />',
                                    'word' => 'Misspelled Words',
                                    'page_name' => 'Page',
                                    'page_type' => 'Page Type',
                                    'count' => 'Count'
                            );
                    } else {
                            $columns = array(
                                    'cb' => '<input type="checkbox" />',
                                    'word' => 'Misspelled Words',
                                    'page_name' => 'Page',
                                    'page_type' => 'Page Type'
                            );
                    }
                }
		return $columns;
	}
	
	
	function get_sortable_columns() {
		$sortable_columns = array(
			'word' => array('word',false),
			'page_name' => array('page_name',false),
			'page_type' => array('page_type',false)
		);
		return $sortable_columns;
	}

	
	function single_row( $item ) {
		static $row_class = 'wpsc-row';
		$row_class = ( $row_class == '' ? ' class="alternate"' : '' );

		echo '<tr class="wpsc-row" id="wpsc-row-' . $item['id'] . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}
	
	
	function prepare_items() {
		$start = round(microtime(true),5);
		error_reporting(0);
		global $wpdb;
		global $ent_included;
		
		$per_page = 20;
		
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		
		
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
		if ($_GET['s'] != '') {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND word LIKE "%' . $_GET['s'] . '%"', OBJECT); 
		} elseif ($_GET['s-top'] != '') {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND word LIKE "%' . $_GET['s-top'] . '%"', OBJECT); 
		} else {
			if ($ent_included) {
				$results = $wpdb->get_results('SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM ' . $table_name . ' AS c JOIN (SELECT CAST(word as BINARY) as word_cs, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word_cs) as c2 ON (c2.word_cs = c.word) WHERE ignore_word is false ORDER BY c2.cnt DESC;', OBJECT);
			} else {
				$results = $wpdb->get_results('SELECT c.id, c.word, c.page_type, c.page_name, c.page_id, c2.cnt FROM ' . $table_name . ' AS c JOIN (SELECT word, COUNT(*) as cnt FROM ' . $table_name . ' GROUP BY word) as c2 ON (c2.word = c.word) WHERE ignore_word is false ORDER BY c.id DESC;', OBJECT);
			}
		}
		
		$end = round(microtime(true),5);
		//echo "Get data: " . ($end - $start) . "<br>";
		$start = round(microtime(true),5);
		
		$counter = $wpdb->get_results('SELECT word, count(*) AS instances FROM ' . $table_name . ' GROUP BY word');
		
		$data = array();
		foreach($results as $word) {
				array_push($data, array('id' => $word->id, 'word' => $word->word, 'page_name' => $word->page_name, 'page_type' => $word->page_type, 'page_url' => $word->page_url, 'page_id' => $word->page_id, 'count' => $word->cnt));
		}
		
		$end = round(microtime(true),5);
		//echo "Get count data: " . ($end - $start) . "<br>";
		$start = round(microtime(true),5);
		
		function usort_reorder($a, $b) {
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'word'; 
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; 
			
			$result = strcmp($a[$orderby], $b[$orderby]); 
			return ($order==='asc') ? $result : -$result;
		}
		function usort_reorder_default($a, $b) {
			/*$orderby = 'count';
			$order = 'desc'; 
			
			$result = strcmp($a[$orderby], $b[$orderby]); 
			return ($order==='asc') ? $result : -$result;*/
			return intval($b['count']) - intval($a['count']);
		}

		if (!empty($_REQUEST['orderby']) && $_REQUEST['orderby'] != 'undefined') {
			usort($data, 'usort_reorder');
		} else {
			if ($ent_included) usort($data, 'usort_reorder_default');
		}
		
		$end = round(microtime(true),5);
		//echo "Handle table sorting: " . ($end - $start) . "<br>";
		$start = round(microtime(true),5);
		
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $data;
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );
		
		$end = round(microtime(true),5);
		//echo "Finalize data: " . ($end - $start) . "<br>";
	}

	function prepare_empty_items() {
		error_reporting(0);
		global $wpdb;
		
		$per_page = 20;
		
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		
		$table_name = $wpdb->prefix . 'spellcheck_empty';
		$dictionary_table = $wpdb->prefix . 'spellcheck_dictionary';
		if ($_GET['s'] != '') {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND page_name LIKE "%' . $_GET['s'] . '%"', OBJECT); 
		} elseif($_GET['s-top'] != '') {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false AND page_name LIKE "%' . $_GET['s-top'] . '%"', OBJECT); 
		} else {
			$results = $wpdb->get_results('SELECT id, word, page_name, page_type, page_id FROM ' . $table_name . ' WHERE ignore_word is false', OBJECT);
		}
		$data = array();
		foreach($results as $word) {
			if ($word->word != '') {
				array_push($data, array('id' => $word->id, 'word' => $word->word, 'page_name' => $word->page_name, 'page_type' => $word->page_type, 'page_url' => $word->page_url, 'page_id' => $word->page_id));
			}
		}
		
		function usort_empty_reorder($a, $b) {
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'word'; 
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; 
			
			$result = strcmp($a[$orderby], $b[$orderby]); 
			return ($order==='asc') ? $result : -$result;
		}
		usort($data, 'usort_empty_reorder');
		
		
		$current_page = $this->get_pagenum();
		$total_items = count($data);
		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		$this->items = $data;
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $per_page,
			'total_pages' => ceil($total_items/$per_page)
		) );		
	}
}

/* Admin Functions */

function wpscx_admin_render() {    
        global $wpsc_version;
        global $wp_version;
        $wpsc_api = 'https://www.wpspellcheck.com/api/error-report.php';
	$log_debug = true; //Enables debugging log
        $utils = new wpscx_results_utils;
         
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

	$start = round(microtime(true),5);
	ini_set('memory_limit','8192M'); 
	set_time_limit(600); 
	global $wpdb;
	global $ent_included;
	global $pro_included;
	global $base_page_max;
	$table_name = $wpdb->prefix . "spellcheck_words";
	$empty_table = $wpdb->prefix . "spellcheck_empty";
	$options_table = $wpdb->prefix . "spellcheck_options";
        $error_table = $wpdb->prefix . 'spellcheck_errors';
	$post_table = $wpdb->prefix . "posts";
	$estimated_time = 6;
	
	$sql_count = 0;
	$total_smartslider = 0;
	$total_huge_it = 0;
        
        
	$message = '';
        $showPopup = false;
        
        if ($_GET['wpsc_dismiss_error'] == '1') {
            $wpdb->update($options_table, array('option_value' => "None"), array('option_name' => 'last_php_error'));
            $wpdb->query("TRUNCATE TABLE $error_table");
            $showPopup = true;
        }
        if ($_GET['wpsc_dismiss_error'] == '2') {
            $wpdb->update($options_table, array('option_value' => "None"), array('option_name' => 'last_php_error'));
            $wpdb->query("TRUNCATE TABLE $error_table");
        }
	
	$settings = $wpdb->get_results('SELECT option_name, option_value FROM ' . $options_table); $sql_count++;
        $errors = $wpdb->get_results('SELECT error_name FROM ' . $error_table); $sql_count++;
        
        $error_text = "";
        foreach ($errors as $error) {
            $error_text .= $error->error_name . ", ";
        }
        $error_text = trim($error_text, ", ");
        $error_text = trim($error_text, '"');

	$max_pages = intval($settings[138]->option_value);
	
	if (!$ent_included) $max_pages = $base_page_max;
	
	
	if (isset($_GET['submit'])) {
	if ($_GET['submit'] == "Stop Scans") {
		$message = "All current spell check scans have been stopped.";
		wpscx_clear_scan();
	}
	}
	if (isset($_GET['submit-empty'])) {
	if ($_GET['submit-empty'] == "Stop Scans") {
		$message = "All current empty field scans have been stopped.";
		wpscx_clear_empty_scan();
	}
	}

	if ($settings[4]->option_value || $settings[12]->option_value || $settings[18]->option_value) {
		$check_pages = 'true';
	} else {
		$check_pages = 'false';
	}
	if ($settings[5]->option_value || $settings[13]->option_value || $settings[19]->option_value) {
		$check_posts = "true";
	} else {
		$check_posts = "false";
	}
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
	$check_widgets = $settings[147]->option_value;
        $php_error = $settings[149]->option_value;
	
	$postmeta_table = $wpdb->prefix . "postmeta";
	$post_table = $wpdb->prefix . "posts";
	$it_table = $wpdb->prefix . "huge_itslider_images";
	$smartslider_table = $wpdb->prefix . "nextend_smartslider_slides";
	
	
	
	$total_pages = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'page'"); $sql_count++;
	$total_posts = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'post'"); $sql_count++;
	$total_media = $wpdb->get_var("SELECT COUNT(*) FROM $post_table WHERE post_type = 'attachment'"); $sql_count++;
	
	$post_count = $total_pages;
	$page_count = $total_posts;
	$media_count = $total_media;
	
	$end = round(microtime(true),5);
	//echo "Set up Variables: " . ($end - $start) . "<br>";
	$start = round(microtime(true),5);
	
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
			
			
			
			
			
			
			
			$total_generic_slider = get_pages(array('number' => PHP_INT_MAX, 'hierarchical' => 0, 'post_type' => 'slider', 'post_status' => array('publish', 'draft'))); $sql_count++;
			$total_sliders = $total_huge_it + $total_smartslider + sizeof((array)$total_generic_slider);
			
			$total_other = $total_menu + $total_authors + $total_tags + $total_tag_desc + $total_tag_slug + $total_cat + $total_cat_desc + $total_cat_slug + $total_seo_title + $total_seo_desc;
			
			$total_page_slugs = $total_pages; 
			$total_post_slugs = $total_posts; 
			$total_page_title = $total_pages; 
			$total_post_title = $total_posts; 
			
			$estimated_time = intval((($total_pages + $total_posts) / 3.5) + 3);
	}
	}
	$scan_message = '';
	
	$check_scan = wpscx_check_scan_progress();
	
	if ($check_scan) {
	if ($check_scan && $_GET['wpsc-script'] != 'noscript') {
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		sleep(1);
	}
	}
	
	
	
	
	$estimated_time = time_elapsed($estimated_time);

	if (isset($_GET['action'])) {
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Pages') {
		$estimated_time = 5 + intval($total_pages / 3.5);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Page Content</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Page Content'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpages_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckpages', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Posts') {
		$estimated_time = 5 + intval($total_posts / 3.5);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Post Content</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Post Content'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposts_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckposts', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Authors') {
		$estimated_time = 5 + intval($total_authors / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Authors</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'author_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Authors'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		wp_schedule_single_event(time(), 'admincheckauthors', array ($rng_seed));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Menus') {
		$estimated_time = 5 + intval($total_menu / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Menus</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'menu_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Menus'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckmenus_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckmenus', array ($rng_seed ));
		}
	}
	/*if ($_GET['action'] == 'check' && $_GET['submit'] == 'Page Titles') {
		$estimated_time = 5 + intval($total_page_title / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Page Titles</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_title_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Page Titles'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpagetitles_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckpagetitles', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Post Titles') {
		$estimated_time = 5 + intval($total_post_title / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Post Titles</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_title_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Post Titles'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttitles_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckposttitles', array ($rng_seed ));
		}
	}*/
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Tags') {
		$estimated_time = 5 + intval($total_tags / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Tags</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'tag_title_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Tag Titles'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttags_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckposttags', array ($rng_seed ));
		}
	}
	/*if ($_GET['action'] == 'check' && $_GET['submit'] == 'Tag Descriptions') {
		$estimated_time = 5 + intval($total_tag_desc / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Tag Descriptions</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'tag_desc_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Tag Descriptions'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttagsdesc_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckposttagsdesc', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Tag Slugs') {
		$estimated_time = 5 + intval($total_tag_slug / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Tag Slugs</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'tag_slug_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Tag Slugs'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckposttagsslugs_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckposttagsslugs', array ($rng_seed ));
		}
	}*/
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Categories') {
		$estimated_time = 5 + intval($total_cat / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Categories</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'cat_title_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Category Titles'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckcategories_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckcategories', array ($rng_seed ));
		}
	}
	/*if ($_GET['action'] == 'check' && $_GET['submit'] == 'Category Descriptions') {
		$estimated_time = 5 + intval($total_cat_desc / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Category Descriptions</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'cat_desc_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Category Descriptions'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckcategoriesdesc_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckcategoriesdesc', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Category Slugs') {
		$estimated_time = 5 + intval($total_cat_slug / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Category Slugs</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'cat_slug_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Category Slugs'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckcategoriesslugs_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckcategoriesslugs', array ($rng_seed ));
		}
	}*/
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'SEO Descriptions') {
		$estimated_time = 5 + intval($total_seo_desc / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">SEO Descriptions</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'seo_desc_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'SEO Descriptions'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckseodesc_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckseodesc', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'SEO Titles') {
		$estimated_time = 5 + intval($total_seo_title / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">SEO Titles</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'seo_title_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'SEO Titles'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckseotitles_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckseotitles', array ($rng_seed ));
		}
	}
	/*if ($_GET['action'] == 'check' && $_GET['submit'] == 'Page Slugs') {
		$estimated_time = 5 + intval($total_page_slugs / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Page Slugs</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'page_slug_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Page Slugs'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpageslugs_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckpageslugs', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Post Slugs') {
		$estimated_time = 5 + intval($total_post_slugs / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Post Slugs</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'post_slug_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Post Slugs'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckpostslugs_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckpostslugs', array ($rng_seed ));
		}
	}*/
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Sliders') {
		$estimated_time = 5 + intval($total_sliders / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Sliders</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'slider_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Sliders'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'adminchecksliders_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'adminchecksliders_pro', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Media Files') {
		$estimated_time = 5 + intval($total_media / 3.5);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Media Files</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'media_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Media Files'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckmedia_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckmedia_pro', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'WooCommerce and WP-eCommerce Products') {
		$estimated_time = 5 + intval($total_products / 3.5);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">eCommerce Products</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'ecommerce_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'eCommerce Products'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'admincheckecommerce_ent', array ($rng_seed ));
		} else {
		wp_schedule_single_event(time(), 'admincheckecommerce', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Widgets') {
		$estimated_time = 5 + intval($total_pages / 3.5);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Widgets</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'ecommerce_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Widgets'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		if ($ent_included) { 
		wp_schedule_single_event(time(), 'wpsccheckwidgets', array ($rng_seed ));
		}
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Contact Form 7') {
		$estimated_time = 5 + intval($total_cf7 / 100);
		$estimated_time = time_elapsed($estimated_time);
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for <span style="color: rgb(0, 150, 255); font-weight: bold;">Contact Form 7</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results();
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'scan_in_progress')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'true'), array('option_name' => 'cf7_sip')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Contact Form 7'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		wp_schedule_single_event(time(), 'admincheckcf7', array ($rng_seed, false));
	}
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Entire Site') {
		$estimated_time = intval((($total_pages + $total_posts + $total_media) / 3.5) + (intval(($total_seo_title + $total_seo_desc + $total_cat + $total_tags) / 100)) + 3);
		$estimated_time = time_elapsed($estimated_time);
		
		$scan_message = '';
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> Scan has been started for the <span style="color: rgb(0, 150, 255); font-weight: bold;">Entire Site</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
		wpscx_clear_results("full");
		$rng_seed = rand(0,999999999);
		wp_enqueue_script( 'results-ajax', plugin_dir_url( __FILE__ ) . '/ajax.js', array('jquery') );
		wp_localize_script( 'results-ajax', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
		
		$wpdb->update($options_table, array('option_value' => time()), array('option_name' => 'last_scan_date')); $sql_count++;
		$wpdb->update($options_table, array('option_value' => 'Entire Site'), array('option_name' => 'last_scan_type')); $sql_count++;
		sleep(1);
		
		wpscx_set_scan_in_progress($rng_seed);
		wp_schedule_single_event(time(), 'adminscansite', array($rng_seed, $log_debug));
	}
	
	
	
	if ($_GET['action'] == 'check' && $_GET['submit'] == 'Clear Results') {
		$message = 'All spell check results have been cleared';
		wpscx_clear_results("full");
	}
	
	$end = round(microtime(true),5);
	//echo "Check button presses: " . ($end - $start) . "<br>";
	$start = round(microtime(true),5);
	
	}
	if (isset($_GET['ignore_word'])) {
	if ($_GET['ignore_word'] != '' && $_GET['wpsc-scan-tab'] != 'empty') {
		$ignore_message = $utils->ignore_word($_GET['ignore_word']); 
	} elseif ($_GET['ignore_word'] != '' && $_GET['wpsc-scan-tab'] == 'empty') {
		$ignore_message = $utils->ignore_word_empty($_GET['ignore_word']); 
	}
	}
	
	if (isset($_GET['add_word'])) {
	if ($_GET['add_word'] != '')
		$dict_message = $utils->add_to_dictionary($_GET['add_word']); 
	}
	
	if (isset($_GET['old_words'])) {
	if ($_GET['old_words'] != '' && $_GET['new_words'] != '' && $_GET['page_types'] != '' && $_GET['old_word_ids'] != '')  {
                //print_r($_GET['nre_words']);
		$message = $utils->update_word_admin($_GET['old_words'], $_GET['new_words'], $_GET['page_names'], $_GET['page_types'], $_GET['old_word_ids'], $_GET['mass_edit']);
	} elseif ($_GET['new_words'] != '' && $_GET['page_types'] != '' && $_GET['old_word_ids'] != '') {
		$message = $utils->update_empty_admin($_GET['new_words'], $_GET['page_names'], $_GET['page_types'], $_GET['old_word_ids']);
	}
	}
	
		
	$word_count = $wpdb->get_var ( "SELECT COUNT(*) FROM $table_name WHERE ignore_word='false'" ); $sql_count++;
	
	$end = round(microtime(true),5);
	//echo "Check for Ignore/Dictionary/Edit/Suggested Changes: " . ($end - $start) . "<br>";
	$start = round(microtime(true),5);
	
	$pro_words = 0;
	$empty_words = 0;
	if (!$pro_included && !$ent_included) {
		$pro_words = $settings[21]->option_value;
	}
	$total_word_count = $settings[22]->option_value;
	$literacy_factor = $settings[64]->option_value;
	
	
	if ($check_scan && $scan_message == '') {
		$last_type = $settings[45]->option_value;
		$scan_message = '<img src="'. plugin_dir_url( __FILE__ ) . 'images/loading.gif" alt="Scan in Progress" /> A scan is currently in progress for <span class="sc-message" style="color: rgb(0, 150, 255); font-weight: bold;">' . $last_type[0]->option_value . '</span>. Estimated time for completion is '.$estimated_time.' . <a href="/wp-admin/admin.php?page=wp-spellcheck.php">Click here</a> to see scan results. <span class="wpsc-mouseover-button-refresh" style="border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;">?</span><span class="wpsc-mouseover-text-refresh">The page will automatically refresh when the scan is finished. You do not need to remain on this page for the scan to run.<br /><br />Time estimate may vary based on server strength.</span>';
	} elseif ($scan_message == '') {
		$scan_message = "No scan currently running";
	}

	
	$time_of_scan = $wpdb->get_results("SELECT option_value FROM $options_table WHERE option_name='last_scan_finished';"); $sql_count++;
	if ($time_of_scan[0]->option_value == "0") {
		$time_of_scan = "0 Minutes";
	} else {
		$time_of_scan = $time_of_scan[0]->option_value;
		if ($time_of_scan == '') $time_of_scan = "0 Seconds";
	}
	
	$scan_type = $settings[45]->option_value;
	
	
	
	$post_status = array("publish", "draft");

	
	
	
	$page_scan = $settings[28]->option_value;
	$post_scan = $settings[29]->option_value;
	$media_scan = $settings[32]->option_value;
	
	$post_scan_count = $post_scan;
	if ($post_scan_count > $post_count) $post_scan_count = $post_count;
	
	$total_words = $settings[22]->option_value;
	
	wp_enqueue_script('results-nav', plugin_dir_url( __FILE__ ) . 'results-nav.js');
	
	$list_table = new sc_table();
	$list_table->prepare_items();	
        
        $date = new DateTime;
        $date->modify('+1 day');
        $expireDate = $date->format('l, F d');
	
	//#wpwrap{background:white!important;}
	?>
		<?php wpscx_show_feature_window(); ?>
		<?php //wpscx_check_install_notice(); ?>
		
	<style>span.Suggested{color: black;}.wpsc-suggested-spelling-list{vertical-align: initial!important;}.wpsc-edit-content h4,.wpsc-suggestion-content h4{color: red; font-weight: bold!important; }.wpsc-suggestion-content h4{display:inline-block;}input[type=submit]{border-radius:32px!important; box-shadow: none!important; text-shadow: none!important; border: none!important;}.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } td.word { font-size: 15px; } p.submit { display: inline-block; margin-left: 8px; } h3.sc-message { width: 49%; display: inline-block; font-weight: normal; padding-left: 8px; } .wpsc-mouseover-text-page,.wpsc-mouseover-text-post,.wpsc-mouseover-text-refresh, .wpsc-mouseover-text-change { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; z-index: -100; } .wpsc-row .row-actions, .wpsc-row .row-actions *{ visibility: visible!important; left: 0!important; } #current-page-selector { width: 12%; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; margin-botton: 15px; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -1px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } #wpsc-empty-fields-tab .button-primary { background: #73019a; border-color: #51006E; text-shadow: 1px 1px #51006d; box-shadow: 0 1px 0 #51006d; } #wpsc-empty-fields-tab .button-primary:hover { background: #9100c3 } #wpsc-empty-fields-tab .button-primary:active { background: #51006d; }.wpsc-scan-buttons input#submit:active { margin-top: -7px; } #wpsc-empty-fields-tab span.wpsc-bulk { display: none; } span.wpsc-bulk { color: black; } th#count { width: 80px; }.wpsc-mouseover-text-pro-feature-2, .wpsc-mouseover-text-pro-feature-3 { color: black!important; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; z-index: -100; }
	#wpsc-error-report  { display: block; width: 330px; height: 165px; position: fixed; left: calc(50% - 150px); top: calc(50% - 150px); border: 2px solid black; border-radius: 15px; background: white; z-index: 999999; padding: 15px; }
        #wpsc-error-confirm { display: block; width: 330px; height: 200px; position: fixed; left: calc(50% - 150px); top: calc(50% - 150px); border: 2px solid black; border-radius: 15px; background: white; z-index: 999999; padding: 15px; }
        #wpsc-error-report button, #wpsc-error-confirm .wpsc-error-confirm-contact { text-decoration: none; background: #008200; color: white; padding: 5px 20px; border-radius: 15px; position: absolute; bottom: 10px; }
        #wpsc-error-report a, #wpsc-error-confirm .wpsc-error-confirm-dismiss { text-decoration: none; color: grey; padding: 5px 20px; position: absolute; bottom: 10px; }
	</style>
	<script>
		jQuery(document).ready(function() {
			var should_submit = false;
			var shown_box = false;
			var allow_next = false;
			var pending = false;
			var admin_url = "<?php echo admin_url(); ?>";
                        var wpsc_popup = jQuery('#wpsc-error-report');
                        var wpsc_form = wpsc_popup.find('form');
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
			  
			  jQuery(".next-page, .prev-page, .last-page, .first-page").click(function (event) {
				if (!allow_next) event.preventDefault();
					pending = false;
					button = jQuery(this).attr('href');
					
					jQuery('.wpsc-ignore-checkbox, .wpsc-add-checkbox').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					jQuery('.wpsc-mass-edit-chk').each(function() {
						if (jQuery(this).attr('value') != '') pending = true;
					});
					
					
					if (pending) {
						jQuery( "#wpsc-mass-edit-block" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							Cancel: function() {
							  jQuery( this ).dialog( "close" );
							},
							"Move Forward Anyway": function() {
							  jQuery( this ).dialog( "close" );
							  allow_next = true;
							  window.location.replace(button);
							}
						  }
						});
					} else {
						allow_next = true;
						window.location.replace(button);
					}
			  });
			  
                        //    jQuery(".wpsc-scan-buttons input").click(function (event) {
			//	if (!allow_next) event.preventDefault();
			//		pending = false;
			//		value = jQuery(this).attr('value');
			//		button = admin_url + 'admin.php?page=wp-spellcheck.php&action=check&submit=' + value;
			//		
			//		jQuery('.wpsc-ignore-checkbox, .wpsc-add-checkbox').each(function() {
			//			if (jQuery(this).is(":checked")) pending = true;
			//		});
			//		
			//		jQuery('.wpsc-mass-edit-chk').each(function() {
			//			if (jQuery(this).attr('value') != '') pending = true;
			//		});
			//		
			//		
			//		if (pending) {
			//			jQuery( "#wpsc-mass-edit-block" ).dialog({
			//			  resizable: false,
			//			  height: "auto",
			//			  width: 400,
			//			  modal: true,
			//			  buttons: {
			//				cancel: function() {
			//				  jQuery( this ).dialog( "close" );
			//				},
			//				"Move Forward Anyway": function() {
			//				  jQuery( this ).dialog( "close" );
			//				  allow_next = true;
			//				  window.location.replace(button);
			//				}
			//			  }
			//			});
			//		} else {
			//			allow_next = true;
			//			window.location.replace(button);
			//		}
			//  });
                          
                          wpsc_form.submit(function(event) { 
                                event.preventDefault();
                                
                                var form_data = {
                                        error: <?php echo json_encode(utf8_encode($error_text)); ?>,
                                        site: '<?php echo esc_url( home_url() ); ?>',
                                        wordpress_ver: '<?php echo $wp_version; ?>',
                                        php_ver: '<?php echo phpversion(); ?>',
                                        theme_name: '<?php echo wp_get_theme()->name; ?>',
                                        parent_name: '<?php echo wp_get_theme()->parent()->name; ?>',
                                        plugin_ver: '<?php echo $wpsc_version; ?>'
                                };

                                var submit = jQuery.post('https://www.wpspellcheck.com/api/error-report.php', form_data);
                                submit.always(function() {
                                        location.href = '<?php echo html_entity_decode( esc_url( add_query_arg( array( 'wpsc_dismiss_error' => '1' ) ) ) ); ?>';
                                });
                        });
                        
                        jQuery("#wpsc-error-confirm a").click(function(event) {
                            jQuery("#wpsc-error-confirm").css('display','none');
                        });
                        
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
                                            action: 'wpscx_start_scan',
                                    },
                                    dataType: 'html',
                                    success: function(response) {
                                        jQuery('#wpscScanMessage').html(response); //update the scan message to display the scan started message
                                        window.setInterval(wpscx_recheck_scan_temp(), 1000 );
                                        jQuery('tr.wpsc-row').animate({opacity: 0}, 500, function() { jQuery('tr.wpsc-row').hide(); })
                                    }
                            });
                        });
		});
                
                function wpscx_recheck_scan_temp() {
                        jQuery.ajax({
                                url: ajax_object,
                                type: "POST",
                                data: {
                                        action: 'results_sc',
                                },
                                dataType: 'html',
                                success: function(response) {
                                        if (response == 'true') { window.setInterval(wpscx_recheck_scan_temp(), 1000 ); console.log(response); }
                                        else { wpscx_finish_scan_temp(); console.log(response); }
                                }
                        });
                }
                
                function wpscx_finish_scan_temp() {
                        jQuery.ajax({
                                url: ajax_object,
                                type: "POST",
                                data: {
                                        action: 'finish_scan',
                                },
                                dataType: 'html',
                                success: function(response) {
                                        scan_in_progress = false;
                                        window.location.href = encodeURI("?page=wp-spellcheck.php&wpsc-script=noscript&wpsc-scan-tab=" + ajax_object.wpsc_scan_tab);
                                }
                        });
                }
	</script>
	<?php
	$end = round(microtime(true),5);
	//echo "Set up CSS, JavaScript, and any display messages: " . ($end - $start) . "<br>";
	$start = round(microtime(true),5);
        //echo addslashes($php_error);
	?>
<div id="wpsc-mass-edit-block" title="Are you sure?" style="display: none;">
  <p>You have changes pending on the current page. Please go back and click save all changes.</p>
</div>
<div id="wpsc-mass-edit-confirm" title="Are you sure?" style="display: none;">
  <p>Have you backed up your database? This will update all areas of your website that you have selected WP Spell Check to scan. Are you sure you wish to proceed with the changes?</p>
</div>
        <div id="wpsc-error-report" <?php if ($php_error != "Show Message") echo "style='display: none;'"; ?>>
            <form class="wpsc_error_form">
            <h3 style='text-align: center;'>Ooops!</h3>
            <p style='text-align: center;'>An error has occurred while the plugin was running. Would you like to send an error report?</p>
            <button type="submit" class="wpsc-error-report-send" href="<?php echo html_entity_decode( esc_url( add_query_arg( array( 'wpsc_dismiss_error' => '1' ) ) ) ); ?>" style="left: 10px;">Send Report</button>
            <a class="wpsc-error-report-dismiss" href="<?php echo html_entity_decode( esc_url( add_query_arg( array( 'wpsc_dismiss_error' => '2' ) ) ) ); ?>" style="right: 10px;">Dismiss</a>
            </form>
        </div>
        <div id="wpsc-error-confirm" <?php if (!$showPopup) echo "style='display: none;'"; ?>>
            <h3 style='text-align: center; color: #008200;'>Thank you for sending the report</h3>
            <h4 style='text-align: center;'>If you would like to work with us to fix this issue and <u>get free access to our pro version</u>, click the link below to get in touch with us(Make sure to mention this message when you contact us).</h4>
            <h4 style='text-align: center;'>This offer expires tomorrow, <?php echo $expireDate; ?> at 5 PM</h4>
            <a class="wpsc-error-confirm-contact" target="_blank" href="https://www.wpspellcheck.com/contact-support/" style="left: 10px;">Get in Touch</a>
            <a class="wpsc-error-confirm-dismiss" href="#" style="right: 10px;">Dismiss</a>
        </div>
		<div class="wrap wpsc-table">
			<h2><a href="admin.php?page=wp-spellcheck.php"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - Scan Results</span></h2>
			<div class="wpsc-scan-nav-bar">
				<a href="#scan-results" id="wpsc-scan-results" class="selected" name="wpsc-scan-results">Spelling Errors</a>
				<a href="<?php echo admin_url(); ?>admin.php?page=wp-spellcheck-grammar.php" id="wpsc-grammar" name="wpsc-grammar">Grammar</a>
				<a href="<?php echo admin_url(); ?>admin.php?page=wp-spellcheck-seo.php" id="wpsc-empty-fields" name="wpsc-empty-fields">SEO Empty Fields</a>
				<a href="<?php echo admin_url(); ?>admin.php?page=wp-spellcheck-html.php" id="wpsc-grammar" name="wpsc-grammar">Broken Code</a>
			</div>
			<div id="wpsc-scan-results-tab" style="margin-top: -17px;" <?php if ($_GET['wpsc-scan-tab'] == 'empty') echo 'class="hidden"';?>>
			<form action="<?php echo admin_url('admin.php'); ?>" method='GET'>
				<div class="wpsc-scan-buttons" style="background: white; padding-left: 8px;">
                                    <h3 style="margin-bottom: 0px; padding-top: 10px;">Click on the buttons below to spell check various parts of your website.</h3>
				<h3 style="display: inline-block;">Scan:</h3>
				<p class="submit"><input style="background-color: #ffb01f; border-color: #ffb01f; box-shadow: 0px 1px 0px #ffb01f; text-shadow: 1px 1px 1px #ffb01f; font-weight: bold;" type="submit" name="submit" id="submit wpscEntireSite" class="button button-primary wpscScan" value="Entire Site" <?php if ($checked_pages == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Pages" <?php if ($check_pages == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Posts" <?php if ($check_posts == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php if ($pro_included || $ent_included) { ?>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="SEO Titles" <?php if ($seo_titles == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="SEO Descriptions" <?php if ($seo_desc == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Media Files" <?php if ($check_media == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php } ?>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Authors" <?php if ($check_authors == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
                                <?php if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) { ?><p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Contact Form 7" <?php if ($check_cf7 == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p><?php } ?>
				<?php if ($pro_included || $ent_included) { ?>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Menus" <?php if ($check_menus == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<!--<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Page Titles" <?php if ($page_titles == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Post Titles" <?php if ($post_titles == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>-->
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Tags" <?php if ($tags == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Categories" <?php if ($categories == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>	
				<!--<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Page Slugs" <?php if ($page_slugs == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Post Slugs" <?php if ($post_slugs == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>-->
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Sliders" <?php if ($check_sliders == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php if (is_plugin_active('woocommerce/woocommerce.php') || is_plugin_active('wp-e-commerce/wp-shopping-cart.php')) { ?><p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="WooCommerce and WP-eCommerce Products" <?php if ($check_ecommerce == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p><?php } ?>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary wpscScan" value="Widgets" <?php if ($check_widgets == 'false') echo "style='background: darkgrey!important; color: white!important; border-color: grey!important;' disabled" ?>></p>
				<?php } ?>
				<p class="submit" style="margin-left: -11px;"><span style="position: relative; left: 15px;"> - </span><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/clear-results.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 27px;" /><input type="submit" name="submit" id="submit" style="padding-left: 30px; background-color: red;" class="button button-primary" value="Clear Results"></p>
                                <p class="submit" style="margin-left: -11px;"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/see-results.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 26px;" /><input type="submit" name="submit" id="submit" style="padding-left: 30px; background-color: red;" class="button button-primary" value="See Scan Results"></p>
				<p class="submit" style="margin-left: -11px;"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/stop-scans.png'; ?>" style="width: 20px; position: relative; top: 5px; left: 25px;" /><input type="submit" name="submit" id="submit" style="padding-left: 30px; background-color: red;" class="button button-primary" value="Stop Scans"></p>
                                <p class="submit" style="margin-left: -11px;"><a href="/wp-admin/admin.php?page=wp-spellcheck-options.php"><img src="<?php echo plugin_dir_url( __FILE__ ) . '../images/options.png'; ?>" title="Options" style="width: 30px; position: relative; top: 11px; left: 20px; padding: 0px; border-radius: 25px;" /></a></p>
				<?php if (($scan_type[0]->option_value == "Entire Site" || $scan_type[0]->option_value == "Page Content" || $scan_type[0]->option_value == "Post Content") && $scan_message == 'No scan currently running' && $ent_included) { ?>
				<?php } ?>
				<!--<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" style="background-color: red;" value="Create Pages"></p>-->
				</div>
				<div style="background: white; padding: 5px; font-size: 12px;">
				<input type="hidden" name="page" value="wp-spellcheck.php">
				<input type="hidden" name="action" value="check">
				<?php echo "<h3 class='sc-message'style='color: rgb(0, 150, 255); font-size: 1.4em;'>Website Literacy Factor: " . $literacy_factor . "%"; ?>
                                <?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>".$settings[45]->option_value."</span>: {$word_count}</h3>"; ?>
                                <?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Posts scanned: " . $settings[29]->option_value . "/" . $page_count; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Pages scanned: " . $settings[28]->option_value . "/" . $post_count; ?>
                                <?php if ($pro_included || $ent_included) { echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Media files scanned: " . $settings[32]->option_value . "/" . $media_count . "</h3>"; } ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Last scan took $time_of_scan</h3>"; ?>
				<?php echo "<h3 class='sc-message' id='wpscScanMessage' style='color: rgb(0, 115, 0);'>$scan_message</h3><br />"; ?>
				<?php if (!$ent_included) {
					if ($word_count > 0 && $pro_words > 0) {
						echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><strong>Pro Version: </strong>" . $pro_words . " Spelling Errors on other parts of your website are hurting your professional image. <a href='https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradespellch&utm_medium=spellcheck_scan&utm_content=$wpsc_version' target='_blank'>Click here</a> to upgrade to find and fix all the errors.</h3>";
					} else {
						//echo "<h3 class='sc-message' style='color: rgb(225, 0, 0);'><a href='https://www.wpspellcheck.com/product-tour/' target='_blank'>Upgrade</a> to scan all parts of your website.</h3>";
					}
				} ?>
				</div>
			</form>
			<?php include("sidebar.php"); ?>
			<?php if(($message != '' || $ignore_message[0] != '' || $dict_message[0] != '' || $mass_edit_message != '') && $_GET['wpsc-scan-tab'] != 'empty') { ?>
				<div style="text-align: center; background-color: white; padding: 5px; margin: 15px 0; width: 74%;">
                                        <?php if($ignore_message[0] != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $ignore_message[0] . "</div>"; ?>
					<?php if($dict_message[0] != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $dict_message[0] . "</div>"; ?>
					<?php if($message != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $message . "</div>"; ?>
					<?php if($mass_edit_message != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $mass_edit_message . "</div>"; ?>
				</div>
				<?php } ?>
			<form id="words-list" method="get" style="width: 75%; float: left; margin-top: 10px;">
				<input name="wpsc-edit-update-button-hidden" id="wpsc-edit-update-button-hidden" type="submit" value="Save all Changes" class="button button-primary" style="display:none;"/>
				<p class="search-box" style="position: relative; margin-top: 8px;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value="" placeholder="Search for Misspelled Words">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<input name="wpsc-edit-update-button" class="wpsc-edit-update-button" type="submit" value="Save all Changes" class="button button-primary" style="width: 16%; padding-top: 5px; padding-bottom: 5px; margin-left: 32.5%; display: block; background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: 7px;"/>
				<?php 
	
	
	 ?>
				<?php 
				$list_table->display(); 
				?>
				
				<?php 
	
				$end_display = time();
	
	 ?>
				<p class="search-box" style="margin-top: 0.7em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Misspelled Words">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
				<input name="wpsc-edit-update-buttom" class="wpsc-edit-update-button" type="submit" value="Save all Changes" class="button button-primary" style="width: 16%; padding-top: 5px; padding-bottom: 5px; margin-left: 31.5%; display: block;  background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: -31px;"/>
			</form>
			
			<div style="padding: 15px; background: white; clear: both; width: 72%; font-family: helvetica;">
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Errors found on <span style='color: rgb(0, 150, 255); font-weight: bold;'>".$settings[45]->option_value."</span>: {$word_count}</h3>"; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Posts scanned: " . $settings[29]->option_value . "/" . $page_count; ?>
				<?php echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Pages scanned: " . $settings[28]->option_value . "/" . $post_count; ?>
				<?php if ($pro_included || $ent_included) { echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'>Media files scanned: " . $settings[32]->option_value . "/" . $media_count . "</h3>"; } ?>
				<?php 
					if ($ent_included) {
						$url = plugins_url()."/wp-spell-check-pro/admin/changes.php"; 
						echo "<h3 class='sc-message' style='color: rgb(0, 115, 0);'><a target='_blank' href='$url'>Click here</a> to view the changelog</h3>"; 
					} else {
						echo "<h3 class='sc-message' style='color: rgb(70, 70, 70);'>Click here to view the changelog<span class='wpsc-mouseover-button-change' style='border-radius: 29px; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?</span><span class='wpsc-mouseover-text-change'>To view the changelog, you must Upgrade to Pro</span></h3>";
					}
					
				?>
			</div>
		</div>
		<!-- Empty Fields  Tab -->
		
		</div>
		<!-- Quick Edit Clone Field -->
		<table style="display: none;">
			<tbody>
				<tr id="wpsc-editor-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-edit-content">
                                                    <h4 style="display: inline-block;">Change <u>%Word%</u> to</h4>
							<input type="text" size="60" name="word_update[]" style="margin-left: 0.5em;" value class="wpsc-edit-field edit-field">
                                                        <br><span class="wpsc-bulk" <?php if (!$ent_included) { echo "style='color: grey;'"; } ?>><input name="wpsc-mass-edit[]" class="wpsc-mass-edit-chk" type="checkbox" value="" <?php if (!$ent_included) { echo "disabled"; } ?> />Apply this change to the entire website<?php if (!$ent_included) echo "<span class='wpsc-mouseover-pro-feature-3' style='border-radius: 29px; color: #008200!important; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-pro-feature-3' style='color: black!important;'>This is a pro version feature</span></span>"; ?></span>
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
                                                    <label><h4>Change <u>%Word%</u> to</h4>
							<select class="wpsc-suggested-spelling-list" name="suggested_word[]">
								<option id="wpsc-suggested-spelling-1" value></option>
								<option id="wpsc-suggested-spelling-2" value></option>
								<option id="wpsc-suggested-spelling-3" value></option>
								<option id="wpsc-suggested-spelling-4" value></option>
							</select><br>
                                                        <div <?php if (!$ent_included) { echo "style='color: grey;'"; } ?>><input name="wpsc-mass-edit[]" class="wpsc-mass-edit-chk" type="checkbox" value="" <?php if (!$ent_included) { echo "disabled"; } ?> />Apply this change to the entire website<?php if (!$ent_included) echo "<span class='wpsc-mouseover-pro-feature-2' style='border-radius: 29px; color: #008200!important; border: 1px solid green; display: inline-block; margin-left: 10px; padding: 4px 10px; cursor: help;'>?<span class='wpsc-mouseover-text-pro-feature-2' style='color: black!important;'>This is a pro version feature</span></span>"; ?></div>
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
	$end = round(microtime(true),5);
	//echo "HTML Rendered(End of function): " . ($end - $start) . "<br>";
	$start = round(microtime(true),5);
	}
	
	
	function wpscx_admin_render_single($wpsc_data, $pageID) {
	$list_table = new sc_table();
	$list_table->prepare_items_single($wpsc_data, $pageID);	
	?>
	<style>.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } td.word { font-size: 15px; } p.submit { display: inline-block; margin-left: 8px; } h3.sc-message { width: 49%; display: inline-block; font-weight: normal; padding-left: 8px; } .wpsc-mouseover-text-page,.wpsc-mouseover-text-post,.wpsc-mouseover-text-refresh, .wpsc-mouseover-text-change { color: black; font-size: 12px; width: 225px; display: inline-block; position: absolute; margin: -13px 0 0 -270px; padding: 3px; border: 1px solid black; border-radius: 10px; opacity: 0; background: white; z-index: -100; } .wpsc-row .row-actions, .wpsc-row .row-actions *{ visibility: visible!important; left: 0!important; } #current-page-selector { width: 12%; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; margin-botton: 15px; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -1px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } #wpsc-empty-fields-tab .button-primary { background: #73019a; border-color: #51006E; text-shadow: 1px 1px #51006d; box-shadow: 0 1px 0 #51006d; } #wpsc-empty-fields-tab .button-primary:hover { background: #9100c3 } #wpsc-empty-fields-tab .button-primary:active { background: #51006d; }.wpsc-scan-buttons input#submit:active { margin-top: -7px; } #wpsc-empty-fields-tab span.wpsc-bulk { display: none; } span.wpsc-bulk { color: black; } th#count { width: 80px; }
	
	</style>
	<script>
		jQuery(document).ready(function() {
			var should_submit = false;
			var shown_box = false;
			var allow_next = false;
			var pending = false;
			var admin_url = "<?php echo admin_url(); ?>";
			
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
							
								var old_words = '';
								jQuery('[name="edit_old_word[]"], [name="suggest_old_word[]"]').each(function() {
									if (jQuery(this).attr('value') != '') {
										old_words += "old_words[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
									}
								});
								
								var page_types = '';
								jQuery('[name="edit_page_type[]"], [name="suggest_page_type[]"]').each(function() {
									if (jQuery(this).attr('value') != '') {
										page_types += "page_types[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
									}
								});

								var new_words = '';
								jQuery('[name="word_update[]"], [name="suggested_word[]"]').each(function() {
									if (jQuery(this).attr('value') != '') {
										new_words += "new_words[]=" + jQuery(this).attr('value').replace('(','%28') + "&";
									}
								});

								var ignore_words = "";
								var add_words = "";
								var mass_edit = "";
								jQuery('[name="ignore-word[]"]').each(function() {
									if (jQuery(this).attr('checked')) {
										ignore_words += "ignore_word[]=" + jQuery(this).attr('value') + "&";
									}
								});
								jQuery('[name="add-word[]"]').each(function() {
									if (jQuery(this).attr('checked')) {
										add_words += "add_word[]=" + jQuery(this).attr('value') + "&";
									}
								});
								jQuery('.wpsc-mass-edit-chk').each(function() {
									if (jQuery(this).is(':checked')) mass_edit += "mass_edit[]=" + jQuery(this).attr('value') + "&";
								});
								
								var ajaxUrl = "wpsc-update.php?" + old_word_ids + old_words + page_names + page_types + new_words + ignore_words + add_words + mass_edit;
								
								jQuery.ajax({
									url: ajaxUrl,
									type: "GET",
									success: function(response) { 
										window.top.location.reload();
									}
								});
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
			  
			  jQuery(".next-page, .prev-page, .last-page, .first-page").click(function (event) {
				if (!allow_next) event.preventDefault();
					pending = false;
					button = jQuery(this).attr('href');
					
					jQuery('.wpsc-ignore-checkbox, .wpsc-add-checkbox').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					jQuery('.wpsc-mass-edit-chk').each(function() {
						if (jQuery(this).attr('value') != '') pending = true;
					});
					
					
					if (pending) {
						jQuery( "#wpsc-mass-edit-block" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							Cancel: function() {
							  jQuery( this ).dialog( "close" );
							},
							"Move Forward Anyway": function() {
							  jQuery( this ).dialog( "close" );
							  allow_next = true;
							  window.location.replace(button);
							}
						  }
						});
					} else {
						allow_next = true;
						window.location.replace(button);
					}
			  });
			  
			  jQuery(".wpsc-scan-buttons input").click(function (event) {
				if (!allow_next) event.preventDefault();
					pending = false;
					value = jQuery(this).attr('value');
					button = admin_url + 'admin.php?page=wp-spellcheck.php&action=check&submit=' + value;
					
					jQuery('.wpsc-ignore-checkbox, .wpsc-add-checkbox').each(function() {
						if (jQuery(this).is(":checked")) pending = true;
					});
					
					jQuery('.wpsc-mass-edit-chk').each(function() {
						if (jQuery(this).attr('value') != '') pending = true;
					});
					
					
					if (pending) {
						jQuery( "#wpsc-mass-edit-block" ).dialog({
						  resizable: false,
						  height: "auto",
						  width: 400,
						  modal: true,
						  buttons: {
							cancel: function() {
							  jQuery( this ).dialog( "close" );
							},
							"Move Forward Anyway": function() {
							  jQuery( this ).dialog( "close" );
							  allow_next = true;
							  window.location.replace(button);
							}
						  }
						});
					} else {
						allow_next = true;
						window.location.replace(button);
					}
			  });
		});
	</script>
	<?php
	$end = round(microtime(true),5);
	//echo "Set up CSS, JavaScript, and any display messages: " . ($end - $start) . "<br>";
	$start = round(microtime(true),5);
	?>
<div id="wpsc-mass-edit-block" title="Are you sure?" style="display: none;">
  <p>You have changes pending on the current page. Please go back and click save all changes.</p>
</div>
<div id="wpsc-mass-edit-confirm" title="Are you sure?" style="display: none;">
  <p>Have you backed up your database? This will update all areas of your website that you have selected WP Spell Check to scan. Are you sure you wish to proceed with the changes?</p>
</div>
		<div class="wrap wpsc-table">
			<?php if(($message != '' || $ignore_message[0] != '' || $dict_message[0] != '' || $mass_edit_message != '') && $_GET['wpsc-scan-tab'] != 'empty') { ?>
				<div style="text-align: center; background-color: white; padding: 5px; margin: 15px 0; width: 74%;">
					<?php if($message != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $message . "</div>"; ?>
					<?php if($mass_edit_message != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $mass_edit_message . "</div>"; ?>
					<?php if($ignore_message[0] != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $ignore_message[0] . "</div>"; ?>
					<?php if($dict_message[0] != '') echo "<div class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold;'>" . $dict_message[0] . "</div>"; ?>
				</div>
				<?php } ?>
			<form id="words-list" method="get" style="width: 100%; margin-top: 10px;">
				<input name="wpsc-edit-update-button-hidden" id="wpsc-edit-update-button-hidden" type="submit" value="Save all Changes" class="button button-primary" style="display:none;"/>
				<p class="search-box" style="position: relative; margin-top: 8px;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input-top" name="s-top" value="" placeholder="Search for Misspelled Words">
					<input type="submit" id="search-submit-top" class="button" value="search">
				</p>
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<input name="wpsc-edit-update-button" class="wpsc-edit-update-button" type="submit" value="Save all Changes" class="button button-primary" style="width: 15%; margin-left: 32.5%; display: block; background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: 7px;"/>
				<?php 
	
	
	 ?>
				<?php 
				$list_table->display(); 
				?>
				
				<?php 
	
				$end_display = time();
	
	 ?>
				<p class="search-box" style="margin-top: 0.7em;">
					<label class="screen-reader-text" for="search_id-search-input">search:</label>
					<input type="search" id="search_id-search-input" name="s" value="" placeholder="Search for Misspelled Words">
					<input type="submit" id="search-submit" class="button" value="search">
				</p>
				<input name="wpsc-edit-update-buttom" class="wpsc-edit-update-button" type="submit" value="Save all Changes" class="button button-primary" style="width: 15%; margin-left: 31.5%; display: block;  background: #008200; border-color: #005200; color: white; font-weight: bold; position: absolute; margin-top: -31px;"/>
			</form>
		</div>
		<!-- Empty Fields  Tab -->
		
		</div>
		<!-- Quick Edit Clone Field -->
		<table style="display: none;">
			<tbody>
				<tr id="wpsc-editor-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-edit-content">
							<h4 style="display: inline-block;">Change %Word% to</h4>
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
							<label><h4>Change <u>%Word%</u> to</h4>
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
	}
	
?>