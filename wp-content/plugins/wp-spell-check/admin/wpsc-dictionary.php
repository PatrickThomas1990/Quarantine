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
function wpscx_display_dictionary_quickedit( $column_name, $post_type ) {
		static $printNonce = TRUE;
		if ( $printNonce ) {
			$printNonce = FALSE;
			wp_nonce_field( plugin_basename( __FILE__ ), 'book_edit_nonce' );
		}

		?>

		<fieldset class="inline-edit-col-right inline-edit-book">
			<div class="inline-edit-col column-<?php echo $column_name; ?>">
				<label class="inline-edit-group">
				<span class="title">Word</span><input name="dictionary_word" />
				</label>
			</div>
		</fieldset>
	<?php
	}
	add_action( 'quick_edit_custom_box', 'wpscx_display_dictionary_quickedit', 10, 2 );

class sc_dictionary_table extends WP_List_Table {

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
			'Edit'					=> sprintf('<a href="#" class="wpsc-dictionary-edit-button" id="wpsc-word-' . $item['word'] . '">Edit</a>'),
			'Delete'      			=> sprintf('<a href="?page=wp-spellcheck-dictionary.php&delete=' . $item['id'] . '">Delete</a>'),
		);
		
		
		return sprintf('%1$s <span style="color:silver"></span>%3$s',
            $item['word'],
            $item['ID'],
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
		
		
		
		$table_name = $wpdb->prefix . 'spellcheck_dictionary';
		if (isset($_GET['s'])) {
			$results = $wpdb->get_results('SELECT id, word FROM ' . $table_name . ' WHERE word LIKE "%' . $_GET['s'] . '%"', OBJECT); 
		} else { 
			$results = $wpdb->get_results('SELECT id, word FROM ' . $table_name, OBJECT); 
		}
		$data = array();
		foreach($results as $word) {
			array_push($data, array('id' => $word->id, 'word' => stripslashes($word->word), 'page_name' => $word->page_name));
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
}

class wpscx_dictionary {

    function delete_word($id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'spellcheck_dictionary';

            $wpdb->delete($table_name, array('id' => $id));
            return "Word Deleted";
    }

    function update_word($old_word, $new_word) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'spellcheck_dictionary';

            $wpdb->update($table_name, array('word' => $new_word), array('word' => $old_word));
            return "Word has been updated";
    }

    function save_dictionary_edit( $word_id, $word ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'spellcheck_dictionary';

            $wpdb->update( $table_name, array ('word' => $word, 'id' => $word_id));
            return 'Word Updated';
    }
}

function wpscx_dictionary_render() {
	global $wpdb;
	global $ent_included;
	global $pro_included;
        global $wpsc_version;
        $dictionary = new wpscx_dictionary;
        
        ?>
            <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css">
            <link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>../css/admin-styles.css">
            <link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ ); ?>../css/wpsc-sidebar.css">
        <?php
        
	$table_name = $wpdb->prefix . "spellcheck_words";
	$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
	$added_message = '';
	$ignore_error_message = '';
	$dict_error_message = '';
	$word_list = '';
	$delete = '';
	$updated_word = '';
	$old_word = '';	
        
        wp_enqueue_script( 'jquery-ui-dialog' );
	wp_enqueue_script('admin-js', plugin_dir_url( __FILE__ ) . '../js/feature-request.js');
	wp_enqueue_script('feature-request', plugin_dir_url( __FILE__ ) . '../js/admin-js.js');
        wp_enqueue_script('jquery.contextMenu', plugin_dir_url( __FILE__ ) . '../js/jquery.contextMenu.js');
	wp_enqueue_script('jquery.ui.position', plugin_dir_url( __FILE__ ) . '../js/jquery.ui.position.js');
	
	if (isset($_POST['submit'])) { if ($_POST['submit'] == "Add to Dictionary") {
                check_admin_referer('wpsc_add_dictionary_word');
		$words = explode(PHP_EOL, $_POST['words-add']);
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
					$wpdb->insert($dict_table, array('word' => stripslashes($word)));
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
	} }
	
	$message = '';
	if (isset($_GET['delete'])) { $delete = $_GET['delete']; }
	if (isset($_GET['new_word'])) { $updated_word = $_GET['new_word']; }
	if (isset($_GET['old_word'])) { $old_word = $_GET['old_word']; }
	if ($delete != '')
		$message = $dictionary->delete_word($delete); 
	if ($updated_word != '' && $old_word != '')
		$message = $dictionary->update_word($old_word, $updated_word);
		
	$list_table = new sc_dictionary_table();
	$list_table->prepare_items();

	?>
		<?php wpscx_show_feature_window(); ?>
		<?php //wpscx_check_install_notice(); ?>
		<style>.search-box input[type=submit] { color: white; background-color: #00A0D2; border-color: #0073AA; } #cb-select-all-1,#cb-select-all-2 { display: none; } </style>
		<div class="wrap wpsc-table">
			<h2><a href="admin.php?page=wp-spellcheck.php"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /></a> <span style="position: relative; top: -8px;"> - User Dictionary</span></h2>
			<?php if($message != '' || $added_message != '' || $ignore_error_message != '' || $dict_error_message != '') echo '<div style="background-color: white; padding: 5px;">'; ?>
			<?php if($message != '') echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold; line-height: 1.5em;'>" . $message . "</span>"; ?>
			<?php if($added_message != '' && strpos($added_message, ', ') !== false) { echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>" . $added_message . " have been added to the dictionary</span>"; }
			elseif ($added_message != '') { echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(0, 115, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>" . $added_message . " has been added to the dictionary</span>"; }?>
			<?php if($ignore_error_message != '') echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(200, 0, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>The following words were already found in the ignore list: " . $ignore_error_message . "</span>"; ?>
			<?php if($dict_error_message != '') echo "<span class='wpsc-message' style='font-size: 1.3em; color: rgb(200, 0, 0); font-weight: bold; float: left; width: 100%; line-height: 1.5em;'>The following words were already found in the dictionary: " . $dict_error_message . "</span>"; ?>
			<div style="clear: both;"></div>
			<?php if($message != '' || $added_message != '' || $ignore_error_message != '' || $dict_error_message != '') echo '</div>'; ?>
			<p style="font-size: 18px; font-weight: bold;">Words in the dictionary list will not be flagged as incorrectly spelled words during a scan of your website and can appear as suggested spellings.</p>
			<form action="admin.php?page=wp-spellcheck-dictionary.php" name="add-to-dictionary" id="add-to-dictionary" method="POST">
                                <?php wp_nonce_field('wpsc_add_dictionary_word'); ?>
				<label>Words to Add to Dictionary(Place one on each line)</label><br /><textarea name="words-add" rows="4" cols="50"><?php echo $word_list; ?></textarea><br />
				<input type="submit" name="submit" value="Add to Dictionary" />
			</form>
			<?php include("sidebar.php"); ?>
				<form method-"POST" style="position:absolute; right: 26%; margin-top: 7px;">
					<input type="hidden" name="page" value="wp-spellcheck-dictionary.php" />
					<?php $list_table->search_box('Search My Dictionary', 'search_id'); ?>
				</form>
			<form id="words-list" method="get" style="width: 75%; float: left;">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $list_table->display() ?>
			</form>
<form method-"post"="" style="float: right; margin-top: -30px; position: relative; z-index: 999999; clear: left; margin-right: 26%;">
				<input type="hidden" name="page" value="wp-spellcheck-dictionary.php">
				<p class="search-box">
	<label class="screen-reader-text" for="search_id-search-input">search:</label>
	<input type="search" id="search_id-search-input" name="s" value="">
	<input type="submit" id="search-submit" class="button" value="Search My Dictionary"></p>
			</form>
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