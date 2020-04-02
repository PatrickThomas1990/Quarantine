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

class sc_ignore_table extends WP_List_Table {

	function __construct() {
		global $status, $page;
		
		
		parent::__construct( array(
			'singular' => 'word',
			'plural' => 'words',
			'ajax' => true
		) );
	}
	
	function column_default($item, $column_name) {
		return print_r($item,true);
	}
	
	
	function column_word($item) {
		
		$actions = array (
			'Unignore'      			=> sprintf('<a href="?page=wp-spellcheck-ignore.php&delete=' . $item['id'] . '&word=' . $item['word'] . '">Unignore</a>'),
		);
		
		
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            stripslashes($item['word']),
            $item['id'],
            $this->row_actions($actions)
        );
	}
	
	
	function column_cb($item) {
		return sprintf('');
	}
	
	
	function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'word' => 'Word',
		);
		return $columns;
	}
	
	
	function get_sortable_columns() {
		$sortable_columns = array(
			'word' => array('word',false),
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
		global $wpdb;
		
		$per_page = 20;
		
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		
		
		$table_name = $wpdb->prefix . 'spellcheck_words';
		$results = $wpdb->get_results('SELECT id, word FROM ' . $table_name . ' WHERE ignore_word=true;', OBJECT); 
		if (isset($_GET['s'])) {
			if ($_GET['s'] != '') {
				$search_term = str_replace("'","'",$_GET['s']);
				
				$results = $wpdb->get_results('SELECT id, word FROM ' . $table_name . ' WHERE ignore_word=true AND word LIKE "%' . $search_term . '%";', OBJECT); 
			}
		}
		$data = array();
		foreach($results as $word) {
			array_push($data, array('id' => $word->id, 'word' => stripslashes($word->word)));
		}
		
		function usort_reorder($a, $b) {
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'word'; 
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; 
			
			$result = strcmp($a[$orderby], $b[$orderby]); 
			return ($order==='asc') ? $result : -$result;
		}
		usort($data, 'usort_reorder');
		
		
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
	
	function prepare_empty_items() {
		global $wpdb;
		
		$per_page = 20;
		
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		
		
		$table_name = $wpdb->prefix . 'spellcheck_empty';
		$results = $wpdb->get_results('SELECT id, word, page_name, page_type FROM ' . $table_name . ' WHERE ignore_word=true;', OBJECT); 
		if (isset($_GET['s'])) {
			$results = $wpdb->get_results('SELECT id, word FROM ' . $table_name . ' WHERE word LIKE "%' . addslashes($_GET['s']) . '%"', OBJECT); 
		} else { 
			$results = $wpdb->get_results('SELECT id, word FROM ' . $table_name, OBJECT); 
		}
		$data = array();
		foreach($results as $word) {
			array_push($data, array('id' => $word->id, 'word' => $word->page_name . " - " . $word->page_type, 'page_name' => $word->page_name));
		}
		
		function usort_reorder_empty($a, $b) {
			$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'word'; 
			$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; 
			
			$result = strcmp($a[$orderby], $b[$orderby]); 
			return ($order==='asc') ? $result : -$result;
		}
		usort($data, 'usort_reorder_empty');
		
		
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
class wpscx_ignore {
    
    function __construct() {}
    
    function unignore_word($id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'spellcheck_words';

            $wpdb->delete($table_name, array('id' => $id));
            return "Word has been removed from the ignore list";
    }

    function unignore_word_empty($id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'spellcheck_empty';

            $wpdb->delete($table_name, array('id' => $id));
            return "Word has been removed from the ignore list";
    }
}

function wpscx_ignore_render() {
	global $wpdb;
	global $pro_included;
	global $ent_included;
        global $wpsc_version;
        $ignore = new wpscx_ignore;
        
        ?>
            <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css">
            <link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>../css/admin-styles.css">
            <link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>../css/wpsc-sidebar.css">
        <?php
        
	$word_list = null;
	$table_name = $wpdb->prefix . "spellcheck_words";
	$empty_table = $wpdb->prefix . "spellcheck_empty";
	$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
	$added_message = '';
	$message = '';
	$added_message = '';
	$ignore_error_message = '';
	$message = '';
	$ignore_error_message = '';
	$dict_error_message = '';
	$delete = '';
        
        wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script('admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js');
	wp_enqueue_script('feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js');
        wp_enqueue_script('jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../js/jquery.contextMenu.js');
	wp_enqueue_script('jquery.ui.position', plugin_dir_url( __FILE__ ) . '../js/jquery.ui.position.js');
        
	if (!isset($_GET['wpsc-ignore-tab'])) $_GET['wpsc-ignore-tab'] = '';
	if (isset($_GET['delete'])) { $delete = $_GET['delete']; }
	if ($delete != '' && strpos($_GET['word'], ' - ') !== false) {
		$message = $ignore->unignore_word_empty($delete); 
	} elseif ($delete != '') {
		$message = $ignore->unignore_word($delete); 
	}
	if (isset($_POST['submit'])) {
		if ($_POST['submit'] == "Add to Ignore List") {
                        check_admin_referer('wpsc_add_ignore_word');
			$words = explode(PHP_EOL, $_POST['words-ignore']);
			$message = '';
			$show_error_ignore = false;
			$show_error_dict = false;
			$show_success = false;
			foreach ($words as $word) {
				$word = trim($word);
				$dupe_check = str_replace("'","\'",$word);
				$dupe_check = str_replace("'","\'",$dupe_check);
				if (strlen($word) > 1) {
					$check_word = $wpdb->get_results('SELECT * FROM ' . $table_name . ' WHERE word="' . $dupe_check . '" AND ignore_word = true');
					$check_dict = $wpdb->get_results('SELECT * FROM ' . $dict_table . ' WHERE word="' . str_replace("\'","'",$word) . '"');
					if (sizeof((array)$check_word) <= 0 && sizeof((array)$check_dict) <= 0) {
						$wpdb->insert($table_name, array('word' => $word, 'page_name' => 'WPSC_Ignore', 'ignore_word' => true, 'page_type' => 'wpsc_ignore'));
						$added_message .= stripslashes($word) . ', ';
					} else {
						if (sizeof((array)$check_dict) <= 0) {
							$show_error_ignore = true;
							$ignore_error_message .= stripslashes($word) . ', ';
						} else {
							$show_error_dict = true;
							$dict_error_message .= stripslashes($word) . ', ';
						}
					}
				}
			}
			$added_message = trim($added_message, ", ");
			$ignore_error_message = trim($ignore_error_message , ", ");
			$dict_error_message = trim($dict_error_message, ", ");
		}
	}
		
	$list_table = new sc_ignore_table();
	$list_table->prepare_items();
	
	$empty_table = new sc_ignore_table();
	$empty_table->prepare_empty_items();
	

	?>
		<?php wpscx_show_feature_window(); ?>
		<?php //wpscx_check_install_notice(); ?>
		<?php wp_enqueue_script('ignore-nav', plugin_dir_url( __FILE__ ) . 'ignore-nav.js'); ?>
		<style>.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; }  #cb-select-all-1,#cb-select-all-2 { display: none; } .hidden { display: none; } .wpsc-scan-nav-bar { border-bottom: 1px solid #BBB; margin-botton: 15px; } .wpsc-scan-nav-bar a { text-decoration: none; margin: 5px 5px -2px 5px; padding: 8px; border: 1px solid #BBB; display: inline-block; font-weight: bold; color: black; font-size: 14px; } .wpsc-scan-nav-bar a.selected { border-bottom: 1px solid white; background: white; } </style>
		<div class="wrap wpsc-table">
			<h2><a href="admin.php?page=wp-spellcheck.php"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - Ignore List</span></h2>
			<?php if($message != '' || $added_message != '' || $ignore_error_message != '' || $dict_error_message != '') echo '<div style="background-color: white; padding: 5px;">'; ?>
			<?php if($message != '') echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>" . $message . " have been added to the ignore list</span>"; ?>
			<?php if($added_message != '' && strpos($added_message, ', ') !== false) { echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>" . $added_message . " have been added to the ignore list</span>"; }
			elseif ($added_message != '') { echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>" . $added_message . " has been added to the ignore list</span>"; }?>
			<?php if($ignore_error_message != '') echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(200, 0, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>The following words were already found in the ignore list: " . $ignore_error_message . "</span>"; ?>
			<?php if($dict_error_message != '') echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(200, 0, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>The following words were already found in the dictionary: " . $dict_error_message . "</span>"; ?>
			<div style="clear: both;"></div>
			<?php if($message != '' || $added_message != '' || $ignore_error_message != '' || $dict_error_message != '') echo '</div>'; ?>
			<div class="wpsc-scan-nav-bar">
				<a href="#spellcheck-words" id="wpsc-spellcheck-words" <?php if ($_GET['wpsc-ignore-tab'] != 'empty') echo 'class="selected"';?> name="wpsc-general-options">Spellcheck Words</a>
				<a href="#empty-fields" id="wpsc-empty-fields" <?php if ($_GET['wpsc-ignore-tab'] == 'empty') echo 'class="selected"';?> name="wpsc-scan-options">Empty Fields</a>
			</div>
			<div id="wpsc-words-tab" <?php if ($_GET['wpsc-ignore-tab'] == 'empty') echo 'class="hidden"';?>>
			<p style="font-size: 18px; font-weight: bold;">Here you can add words to the ignore list. Words here will not be flagged as incorrectly spelled words during a scan of your website.</p>
			<form action="admin.php?page=wp-spellcheck-ignore.php" name="add-to-ignore" id="add-to-ignore" method="POST">
                                <?php wp_nonce_field('wpsc_add_ignore_word'); ?>
				<label>Words to ignore(Place one on each line)</label><br /><textarea name="words-ignore" rows="4" cols="50"><?php echo $word_list; ?></textarea><br />
				<input type="submit" name="submit" value="Add to Ignore List" />
			</form>
			<?php include("sidebar.php"); ?>
				<form method-"POST" style="position:absolute; right: 26%; margin-top: 7px;">
					<input type="hidden" name="page" value="wp-spellcheck-ignore.php" />
					<?php $list_table->search_box('Search My Ignore List', 'search_id'); ?>
				</form>
			<form id="words-list" method="get" style="width: 75%; float: left;">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $list_table->display() ?>
			</form>
<form method-"post"="" style="float: right; margin-top: -30px; position: relative; z-index: 999999; clear: left; margin-right: 26%;">
				<input type="hidden" name="page" value="wp-spellcheck-ignore.php">
				<p class="search-box">
	<label class="screen-reader-text" for="search_id-search-input">search:</label>
	<input type="search" id="search_id-search-input" name="s" value="">
	<input type="submit" id="search-submit" class="button" value="Search My Ignore List"></p>
			</form>
		</div>
		<div id="wpsc-empty-tab" <?php if ($_GET['wpsc-ignore-tab'] != 'empty') echo 'class="hidden"';?>>
		<?php include("sidebar.php"); ?>
			<form id="words-list" method="get" style="width: 75%; float: left;">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $empty_table->display() ?>
		</div>
		</div>
		<!-- Quick Edit Clone Field -->
		<table style="display: none;">
			<tbody>
				<tr id="wpsc-editor-row" class="wpsc-editor">
					<td colspan="4">
						<div class="wpsc-edit-content">
							<h4>Edit Word</h4>
							<label><span>Word</span><input type="text" name="word_update" style="margin-left: 3em;" value class="wpsc-edit-field"></label>
						</div>
						<div class="wpsc-buttons">
							<input type="button" class="button-secondary cancel alignleft wpsc-cancel-button" value="Cancel">
							<input type="button" class="button-primary save alignleft wpsc-update-button" style="margin-left: 3em" value="Update">
							<div style="clear: both;"></div>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
	<?php 
	}
?>