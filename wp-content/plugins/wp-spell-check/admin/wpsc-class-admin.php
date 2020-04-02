<?php

class wpscx_admin {
    function __construct() {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	require_once( ABSPATH . 'wp-includes/pluggable.php' );
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	require_once dirname( __FILE__ ) . '/../lib/class-tgm-plugin-activation.php';
        
        require_once( 'wpsc-framework.php' );
        require_once('wpsc-class-scanner.php');
        require_once('wpsc-class-menus.php');
        require_once('wpsc-class-utils.php');
        require_once('wpsc-class-banner.php');
        require_once('wpsc-class-ajax.php');
        require_once('wpsc-class-interface.php');
        require_once ('wpsc-class-email.php');
	if ($_GET['page'] == 'wp-spellcheck-options.php') require_once( 'wpsc-options.php' );
	if ($_GET['page'] == 'wp-spellcheck-dictionary.php') require_once( 'wpsc-dictionary.php' );
	if ($_GET['page'] == 'wp-spellcheck-ignore.php') require_once( 'wpsc-ignore.php' );
	if ($_GET['page'] == 'wp-spellcheck.php' || $_GET['page'] == 'wp-spellcheck-seo.php') require_once( 'wpsc-results.php' );
	if ($_GET['page'] == 'wp-spellcheck-seo.php') require_once( 'wpsc-empty-results.php' );
	require_once( 'wpsc-empty.php' );
	if ($_GET['page'] == 'wp-spellcheck-html.php') require_once( 'html-results.php' );
	require_once( 'grammar/grammar_framework.php' );
	if ($_GET['page'] == 'wp-spellcheck-grammar.php') require_once( 'grammar/grammar_results.php' );
	require_once( 'deactive-survey.php' );
        require_once('grammar/wpsc-class-grammar.php');
        require_once('wpsc-class-spellcheck.php');
        require_once('wpsc-class-seo.php');
        
        define("wpscx_version", "7.1.5");
        
        $this->register_admin_hooks();
        
        $interface = new wpscx_wordpress_interface;
    }
   
    function admin_footer() {
        global $current_screen;
        if ( ! empty( $current_screen->id ) && strpos( $current_screen->id, 'wp-spellcheck' ) !== false ) {
            $url  = 'https://wordpress.org/support/plugin/wp-spell-check/reviews/?filter=5';
            $text = sprintf( esc_html__( 'Finding the plugin useful? Please rate %sWP Spell Check%s %s on %sWordPress.org%s. We appreciate your help!', 'google-analytics-for-wordpress' ), '<strong>', '</strong>', '<a href="' .  $url . '" target="_blank" rel="noopener noreferrer">★★★★★</a>', '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">', '</a>' );
        }
        return $text;
    }
    
    function register_sidebar() {
        /*wp_register_script(
                'wpsc-sidebar-js',
                plugins_url( '../js/wpsc-sidebar.js', __FILE__ ),
                array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components' )
        );*/
        //wp_enqueue_script( 'wpsc-sidebar-js' );
        //wp_enqueue_style( 'wpsc-sidebar-css' );
    }

    
    function cron_add_custom() {
        global $wpdb;
        wpscx_set_global_vars();
        global $check_opt;
        $table_name = $wpdb->prefix . 'spellcheck_options';
        $check_db = $check_opt;
        if(sizeof($check_db) != 0) {
                if (!isset($_POST['scan_frequency_interval']) && !isset($_POST['scan_frequency'])) {
                        $scan_frequency = $wpdb->get_results('SELECT option_value FROM ' . $table_name . ' WHERE option_name="scan_frequency";');
                        $scan_frequency_interval = $wpdb->get_results('SELECT option_value FROM ' . $table_name . ' WHERE				option_name="scan_frequency_interval";');
                        $scan_interval = $scan_frequency_interval[0]->option_value;
                        $scan_timer = intval($scan_frequency[0]->option_value);
                } else {
                        $scan_interval = $_POST['scan_frequency_interval'];
                        $scan_timer = intval($_POST['scan_frequency']);
                }

                switch($scan_interval) {
                        case "hourly":
                                $scan_recurrence = $scan_timer * 3600;
                                break;
                        case "daily":
                                $scan_recurrence = $scan_timer * 86400;
                                break;
                        case "weekly":
                                $scan_recurrence = $scan_timer * 604800;
                                break;
                        case "monthly":
                                $scan_recurrence = $scan_timer * 2592000;
                                break;
                        default:
                                $scan_recurrence = 604800;
                }

                //echo "Debug(wpsc) - " . $scan_recurrence . "<br>";

                $schedules['wpsc'] = array(
                        'interval' => $scan_recurrence,
                        'display' => __( 'wpsc' )
                );
        }
        return $schedules;
    }
    
    function register_required_plugins() {
        global $wp_version;
                
        if (version_compare($wp_version, '5.0.0') >= 0 ) {
            $plugins = array(
                    array(
                            'name'      => 'Classic Editor',
                            'slug'      => 'classic-editor',
                            'required'  => false,
                    ),
                    array(
                            'name'      => 'WP Mail SMTP',
                            'slug'      => 'wp-mail-smtp',
                            'required'  => false,
                    )
            );

        } else {
            $plugins = array(
                    array(
                            'name'      => 'WP Mail SMTP',
                            'slug'      => 'wp-mail-smtp',
                            'required'  => false,
                    )
            );
        }

        $config = array(
        'id'           => 'wpspellcheck',                 // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '',                      // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins', // Menu slug.
        'parent_slug'  => 'plugins.php',            // Parent menu slug.
        'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
        'has_notices'  => true,                    // Show admin notices or not.
        'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => false,                   // Automatically activate plugins after installation or not.
        'message'      => 'WP Spell Check editor highlights requires Classic Editor in order to work',                      // Message to output right before the plugins table.


        'strings'      => array(
                'page_title'                      => __( 'Install Required Plugins', 'wpspellcheck' ),
                'menu_title'                      => __( 'Install Plugins', 'wpspellcheck' ),
                'installing'                      => __( 'Installing Plugin: %s', 'wpspellcheck' ),
                'updating'                        => __( 'Updating Plugin: %s', 'wpspellcheck' ),
                'oops'                            => __( 'Something went wrong with the plugin API.', 'wpspellcheck' ),
                'notice_can_install_required'     => _n_noop(
                        'WP Spell Check requires the following plugin: %1$s.',
                        'WP Spell Check requires the following plugins: %1$s.',
                        'wpspellcheck'
                ),
                'notice_can_install_recommended'  => _n_noop(
                        'WP Spell Check recommends the following plugin: %1$s.',
                        'WP Spell Check recommends the following plugins: %1$s.',
                        'wpspellcheck'
                ),
                'notice_can_activate_required'    => _n_noop(
                        'WP Spell Check requires activating the following plugin: %1$s.',
                        'WP Spell Check requires activating the following plugins: %1$s.',
                        'wpspellcheck'
                ),
                'notice_can_activate_recommended' => _n_noop(
                        'WP Spell Check recommends activating the following plugin: %1$s.',
                        'WP Spell Check recommends activating the following plugin: %1$s.',
                        'wpspellcheck'
                ),
                'install_link'                    => _n_noop(
                        'Begin installing plugin',
                        'Begin installing plugins',
                        'wpspellcheck'
                ),
                'update_link' 					  => _n_noop(
                        'Begin updating plugin',
                        'Begin updating plugins',
                        'wpspellcheck'
                ),
                'activate_link'                   => _n_noop(
                        'Begin activating plugin',
                        'Begin activating plugins',
                        'wpspellcheck'
                ),
                'return'                          => __( 'Return to Required Plugins Installer', 'wpspellcheck' ),
                'plugin_activated'                => __( 'Plugin activated successfully.', 'wpspellcheck' ),
                'activated_successfully'          => __( 'The following plugin was activated successfully:', 'wpspellcheck' ),
                'plugin_already_active'           => __( 'No action taken. Plugin %1$s was already active.', 'wpspellcheck' ),
                'plugin_needs_higher_version'     => __( 'Plugin not activated. A higher version of %s is needed for WP Spell Check. Please update the plugin.', 'wpspellcheck' ),
                'complete'                        => __( 'All plugins installed and activated successfully. %1$s', 'wpspellcheck' ),
                'dismiss'                         => __( 'Dismiss this notice', 'wpspellcheck' ),
                'notice_cannot_install_activate'  => __( 'There are one or more required or recommended plugins to install, update or activate.', 'wpspellcheck' ),
                'contact_admin'                   => __( 'Please contact the administrator of this site for help.', 'wpspellcheck' ),

                'nag_type'                        => 'notice-warning', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
        ),

);

        tgmpa( $plugins, $config );
    }
    
    function plugin_add_premium_link() {
        global $wpsc_version;
        unset($links['edit']); 
        $settings_link = '<a href="https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=upgradePlugins_Page&utm_medium=plugin_page&utm_content='  . $wpsc_version . '" target="_blank">' . __( 'Premium Features' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }
    
    function plugin_add_settings_link() {
        $settings_link = '<a href="admin.php?page=wp-spellcheck-options.php">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }
    
    function register_admin_hooks() {
        $plugin = plugin_basename( __FILE__ );
        //$database = new wpscx_database;
        $cur_page = $_GET['page'];
        //add_action( 'plugins_loaded', array($database,'wpsc_update_db_check_main' ));
        
        add_filter( 'admin_footer_text', array($this, 'admin_footer'), 1, 2 );
        add_filter( 'cron_schedules', array($this, 'cron_add_custom') );
        add_action( 'tgmpa_register', array($this,'register_required_plugins') );
        add_filter( "plugin_action_links_$plugin", array($this,'plugin_add_settings_link') );
        add_filter( "plugin_action_links_$plugin", array($this,'plugin_add_premium_link') );
        //add_action( 'init', array($this,'register_sidebar') );
    }
}