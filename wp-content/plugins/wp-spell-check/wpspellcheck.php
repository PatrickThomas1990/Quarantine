<?php
if(!defined('ABSPATH')) { exit; }
	/*
	Plugin Name: WP Spell Check
	Description: The Fastest Proofreading plugin that allows you to find & fix Spelling errors, Grammar errors, Broken HTML & Shortcodes and, SEO Opportunities to Create a professional image and take your site to the next level
	Version: 8.1
	Author: WP Spell Check
	Requires at least: 4.1.1
	Tested up to: 5.3.2
	Stable tag: 8.1
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
	Copyright: © 2019 WP Spell Check
	Contributors: wpspellcheck
	Donate Link: www.wpspellcheck.com
	Donate Link: www.wpspellcheck.com
	Tags: spelling, SEO, Spell Check, WordPress spell check, Spell Checker, WordPress spell checker, spelling errors, spelling mistakes, spelling report, fix spelling, WP Spell Check
	
	Author URI: https://www.wpspellcheck.com
	
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
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        require_once('admin/wpsc-class-database.php');
        register_activation_hook( __FILE__, array ('wpscx_database','wpsc_install_spellcheck_main' ));
        $database = new wpscx_database;
        add_action( 'plugins_loaded', array($database,'wpsc_update_db_check_main' ));
        
        add_action('plugins_loaded', 'wpscx_load_plugin');

        function wpscx_load_plugin() {
            if (!(current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' ) || current_user_can( 'contributor' ))) {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                require_once( ABSPATH . 'wp-includes/pluggable.php' );
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

                require_once( 'admin/wpsc-framework.php' );
                require_once('admin/wpsc-class-scanner.php');
                require_once ('admin/wpsc-class-email.php');
                require_once( 'admin/wpsc-empty.php' );
                require_once( 'admin/grammar/grammar_framework.php' );
                require_once('admin/grammar/wpsc-class-grammar.php');
                require_once('admin/wpsc-class-spellcheck.php');
                require_once('admin/wpsc-class-seo.php');
                
                if (is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php')) {
                    $pro_data = get_plugin_data(dirname(__FILE__) . '-pro/wpspellcheckpro.php');
                    $pro_ver = $pro_data['Version'];
                    if (version_compare($pro_ver, '8.1') == 0) {
                                include dirname(__FILE__) . '-pro/pro-loader.php';
                    }
                }
                return;
            }

            require_once('admin/wpsc-class-admin.php');
            $wpscx = new wpscx_admin;

            if (is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php')) {
                $pro_data = get_plugin_data(dirname(__FILE__) . '-pro/wpspellcheckpro.php');
                $pro_ver = $pro_data['Version'];
                if (version_compare($pro_ver, '8.1') == 0) {
                            include dirname(__FILE__) . '-pro/pro-loader.php';
                }
            }
            if (current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'author' ) || current_user_can( 'contributor' )) wp_enqueue_style( 'global-admin-styles', plugin_dir_url( __FILE__ ) . 'css/global-admin-styles.css' );
            if (get_option('wpsc_data_acti') == '' && current_user_can( 'administrator' ) && $_POST['uninstall'] != 'Clean up Database and Deactivate Plugin' && $_GET['action'] != 'activate' && $_GET['plugin_status'] == 'all') {
                add_action('admin_head', array('wpscx_banner','show_install_notice'));
               update_option('wpsc_data_acti',array());
            }
            
            if ( is_admin() ) {
		new wpscx_deactivation();
            }
        }
        
        function wpscx_error_reporting() {
            $error = error_get_last();
            
            if (strpos($error['message'], "/wp-spell-check/") !== false) {
                global $wpdb;
                global $ent_included;
                global $wpscx_title;
                $rng_seed = rand(0,999999999);
                $options_table = $wpdb->prefix . "spellcheck_options";
                $grammar_table = $wpdb->prefix . 'spellcheck_grammar_options';
                $ignore_table = $wpdb->prefix . 'spellcheck_ignore';
                $error_table = $wpdb->prefix . 'spellcheck_errors';
                $message = $error['message'];
                
                $wpdb->update($options_table, array('option_value' => 'PHP Crash'), array('option_name' => 'last_php_error'));
                $wpdb->insert($error_table, array('error_name' => $message)); 
                $settings = $wpdb->get_results('SELECT option_name, option_value FROM ' . $options_table);
                $last_scan = $settings[45]->option_value;
                
                $wpdb->insert($ignore_table, array('keyword' => $wpscx_title, 'type' => 'page')); 
                
                $error_count = $wpdb->get_results("SELECT * FROM $error_table");
                
                if (sizeof($error_count) <= 2) {
                    if ($last_scan == "Page Content") {
                        if ($ent_included) { 
                        wp_schedule_single_event(time(), 'admincheckpages_ent', array ($rng_seed ));
                        } else {
                        wp_schedule_single_event(time(), 'admincheckpages', array ($rng_seed ));
                        }
                    } elseif ($last_scan == "Post Content") {
                        if ($ent_included) { 
                        wp_schedule_single_event(time(), 'admincheckposts_ent', array ($rng_seed ));
                        } else {
                        wp_schedule_single_event(time(), 'admincheckposts', array ($rng_seed ));
                        }
                    } elseif ($last_scan == "Authors") {
                        wp_schedule_single_event(time(), 'admincheckauthors', array ($rng_seed));
                    } elseif ($last_scan == "Menus") {
                        if ($ent_included) { 
                        wp_schedule_single_event(time(), 'admincheckmenus_ent', array ($rng_seed ));
                        } else {
                        wp_schedule_single_event(time(), 'admincheckmenus', array ($rng_seed ));
                        }
                    } elseif ($last_scan == "Tag Titles") {
                        wp_schedule_single_event(time(), 'admincheckposttags_ent', array ($rng_seed ));
                    } elseif ($last_scan == "Category Titles") {
                        wp_schedule_single_event(time(), 'admincheckcategories_ent', array ($rng_seed ));
                    } elseif ($last_scan == "SEO Descriptions") {
                        wp_schedule_single_event(time(), 'admincheckseodesc_ent', array ($rng_seed ));
                    } elseif ($last_scan == "SEO Titles") {
                        wp_schedule_single_event(time(), 'admincheckseotitles_ent', array ($rng_seed ));
                    } elseif ($last_scan == "Sliders") {
                        wp_schedule_single_event(time(), 'adminchecksliders_ent', array ($rng_seed ));
                    } elseif ($last_scan == "Media Files") {
                        if ($ent_included) { 
                        wp_schedule_single_event(time(), 'admincheckmedia_ent', array ($rng_seed ));
                        } else {
                        wp_schedule_single_event(time(), 'admincheckmedia_pro', array ($rng_seed ));
                        }
                    } elseif ($last_scan == "eCommerce Products") {
                        wp_schedule_single_event(time(), 'admincheckecommerce_ent', array ($rng_seed ));
                    } elseif ($last_scan == "Widgets") {
                        wp_schedule_single_event(time(), 'wpsccheckwidgets', array ($rng_seed ));
                    } elseif ($last_scan == "Contact Form 7") {
                        wp_schedule_single_event(time(), 'admincheckcf7', array ($rng_seed, false));
                    } elseif ($last_scan == "Entire Site") {
                        $debug = wp_schedule_single_event(time(), 'adminscansite', array($rng_seed, $log_debug));
                    }
                } else {
                    wpscx_clear_scan();
                    wpscx_clear_empty_scan();
                    wphcx_clear_scan();
                    wpgcx_clear_scan();
                }
            }
        }
        register_shutdown_function('wpscx_error_reporting');
	
	function wpscx_set_global_vars() {
		global $wpdb;
		global $ignore_list;
		global $dict_list;
		global $wpsc_settings;
		global $wpgc_settings;
		global $check_opt;
		global $wpsc_haystack;
		global $base_page_max;
		global $ent_included;
                global $wpsc_version;
                
                $wpsc_version = "8.1";
		
		$ignore_list = array();
		$dict_list = array();
		$wpgc_settings = array();
		
		$test_var = "Test successful";
		
		$words_table = $wpdb->prefix . 'spellcheck_words';
		$options_table = $wpdb->prefix . 'spellcheck_options';
		$grammar_options_table = $wpdb->prefix . 'spellcheck_grammar_options';
		$ignore_table = $wpdb->prefix . 'spellcheck_ignore';
		$dict_table = $wpdb->prefix . 'spellcheck_dictionary';
		
		$check_opt = $wpdb->get_results("SHOW TABLES LIKE '$options_table'");
		$check_word = $wpdb->get_results("SHOW TABLES LIKE '$words_table'");
		$check_ig = $wpdb->get_results("SHOW TABLES LIKE '$ignore_table'");
		$check_dict = $wpdb->get_results("SHOW TABLES LIKE '$dict_table'");
		$check_grm = $wpdb->get_results("SHOW TABLES LIKE '$grammar_options_table'");
		
		if (!isset($wpsc_settings) && sizeof($check_opt) != 0) {
			$wpsc_settings_temp = $wpdb->get_results("SELECT * FROM $options_table");
                        if (isset($wpsc_settings_temp) && sizeof($wpsc_settings_temp) > 0) {
                            $wpsc_settings = new SplFixedArray(sizeof($wpsc_settings_temp) + 1);
                            for ($x = 0; $x < sizeof($wpsc_settings_temp); $x++) {
                                    $wpsc_settings[$x] = $wpsc_settings_temp[$x];
                            }
                            unset($wpsc_settings_temp);
                        }
		}
		
		if (sizeof((array)$wpsc_settings) < 1) {
		
		if (sizeof($check_opt) != 0 && sizeof($check_word) != 0 && sizeof($check_ig) != 0 && sizeof($check_dict) != 0) {
			$ignore_list = $wpdb->get_results("SELECT word FROM $words_table WHERE ignore_word = true");
			$dict_list = $wpdb->get_results("SELECT word FROM $dict_table");
			$wpgc_settings = $wpdb->get_results("SELECT * FROM $grammar_options_table");
		}
		}
		
		if ($ent_included) {
			if (isset($wpsc_settings[138])) $base_page_max = $wpsc_settings[138]->option_value;
		} else {
			$base_page_max = 25;
		}
	}
	
	global $scdb_version;
	global $scan_delay;
	$scan_delay = 0;
	$scdb_version = '1.0';
	wpscx_set_global_vars();
	
	/* Initialization Code */
         /*Create Network Page*/
        function wpscx_uninstall_page() {
                if ($_POST['uninstall'] == 'Uninstall') {
                        check_admin_referer('wpsc_network_uninstall');
                        global $wpdb;

                        if (function_exists('is_multisite') && is_multisite()) {
                                if ($networkwide) {
                                        $old_blog = $wpdb->blogid;


                                        $blogids = $wpdb->get_col("SELECT blog_ID FROM $wpdb->blogs");
                                        foreach ($blogids as $blog_id) {
                                                switch_to_blog($blog_id);
                                                prepare_uninstall();
                                        }
                                        switch_to_blog($old_blog);
                                }
                        }
                        prepare_uninstall();
                        deactivate_plugins( 'wp-spell-check/wpspellcheck.php' );
                        if ($pro_included) deactivate_plugins( 'wp-spell-check-pro/wpspellcheckpro.php' );
                        if ($ent_included) deactivate_plugins( 'wp-spell-check-enterprise/wpspellcheckenterprise.php' );
                        wp_die( 'WP Spell Check has been deactivated. If you wish to use the plugin again you may activate it on the WordPress plugin page' );
                }

                ?>
                <h2><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/logo.png'; ?>" alt="WP Spell Check" /> <span style="position: relative; top: -15px;">Network Uninstall</span></h2>
                <p>This will deactivate WP Spell Check on all sites on the network and clean up the database of any changes made by WP Spell Check. If you wish to use WP Spell Check again after, you may activate it on the WordPress plugins page</p>
                <form action="settings.php?page=wpsc_uninstall_page" method="post" name="uninstall">
                        <?php wp_nonce_field('wpsc_network_uninstall'); ?>
                        <input type="submit" name="uninstall" value="Clean up Database and Deactivate Plugin" />
                </form>
                <?php
        }
?>