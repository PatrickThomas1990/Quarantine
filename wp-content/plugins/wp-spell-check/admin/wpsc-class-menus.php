<?php

class wpscx_menu {
    
    function __construct() {
        
    }
    
    function add_menu() {
        if (!isset($_POST['uninstall'])) {
            global $pro_included;
            global $ent_included;

            if ($pro_included) {
                    add_menu_page( 'WP Spell Checker', 'WP Spell Check (Pro)', 'manage_options', 'wp-spellcheck.php', 'wpscx_admin_render', plugin_dir_url( __FILE__ ) . 'images/logo-icon-16x16.png');
            } elseif ($ent_included) {
                    add_menu_page( 'WP Spell Checker', 'WP Spell Check (Pro)', 'manage_options', 'wp-spellcheck.php', 'wpscx_admin_render', plugin_dir_url( __FILE__ ) . 'images/logo-icon-16x16.png');
            } else {
                    add_menu_page( 'WP Spell Checker', 'WP Spell Check', 'manage_options', 'wp-spellcheck.php', 'wpscx_admin_render', plugin_dir_url( __FILE__ ) . 'images/logo-icon-16x16.png');
            }
            add_submenu_page( 'wp-spellcheck.php', 'Spell Check', 'Spell Check', 'manage_options', 'wp-spellcheck.php', 'wpscx_admin_render');
            add_submenu_page( 'wp-spellcheck.php', 'Grammar', 'Grammar', 'manage_options', 'wp-spellcheck-grammar.php', 'wpgcx_render_results');
            add_submenu_page( 'wp-spellcheck.php', 'SEO Empty Fields', 'SEO Empty Fields', 'manage_options', 'wp-spellcheck-seo.php', 'wpscx_admin_empty_render');
            add_submenu_page( 'wp-spellcheck.php', 'Broken Code', 'Broken Code', 'manage_options', 'wp-spellcheck-html.php', 'wphcx_admin_render');
        }
    }

    function add_tools_scan_menu() {
            add_submenu_page( 'tools.php', 'WP Spell Check', 'WP Spell Check', 'manage_options', 'wp-spellcheck.php', 'wpscx_admin_render');
    }


    function add_settings_menu() {
            add_submenu_page( 'options-general.php', 'WP Spell Check', 'WP Spell Check', 'manage_options', 'wpsc-options.php', 'wpscx_render_options');
    }


    function add_options_menu() {
            add_submenu_page( 'wp-spellcheck.php', 'Options', 'Options', 'manage_options', 'wp-spellcheck-options.php', 'wpscx_render_options');
    }


    function add_dictionary_menu() {	
            add_submenu_page( 'wp-spellcheck.php', 'My Dictionary', 'My Dictionary', 'manage_options', 'wp-spellcheck-dictionary.php', 'wpscx_dictionary_render');
    }


    function add_ignore_menu() {	
            add_submenu_page( 'wp-spellcheck.php', 'Ignore List', 'Ignore List', 'manage_options', 'wp-spellcheck-ignore.php', 'wpscx_ignore_render');
    }


    function add_pro_menu() {
            global $ent_included;
            global $wpsc_version;

            if (!$ent_included) {
                    global $submenu;
                    $permalink = 'https://www.wpspellcheck.com/product-tour/?utm_source=baseplugin&utm_campaign=leftsidebar&utm_medium=admin_bar&utm_content=' . $wpsc_version;
                    $submenu['wp-spellcheck.php'][] = array( 'Upgrade to Premium', 'manage_options', $permalink );
            } else {
                    global $submenu;
                    $permalink = 'https://www.wpspellcheck.com/account?utm_source=baseplugin&utm_campaign=acount_login&utm_medium=pro_version&utm_content=' . $wpsc_version;
                    $submenu['wp-spellcheck.php'][] = array( 'Account Login', 'manage_options', $permalink );
            }
    }
    
    function add_tutorial_menu() {
        //add_submenu_page('wp-spellcheck.php','Tutorials','<a id="re-direct" target="_blank" href="https://www.wpspellcheck.com/support">Online Training</a>','manage_options','https://www.wpspellcheck.com/support/');
    }
    
    function add_network_menu() {
            add_submenu_page('settings.php', 'WP Spell Check Database Cleanup and Deactivation', 'WP Spell Check Database Cleanup and Deactivation', 'manage_options', 'wpsc_uninstall_page', 'wpsc_uninstall_page');
    }
    
    function menu_script() {
        ?>
        <script type="text/javascript">
            jQuery(document).ready( function($) {
                $( "ul#adminmenu a[href$='https://www.wpspellcheck.com/product-tour/']" ).attr( 'target', '_blank' );
                            $( "ul#adminmenu a[href$='https://www.wpspellcheck.com/product-tour/']" ).css( 'color', '#EC8E1F' );
                            $( "ul#adminmenu a[href$='https://www.wpspellcheck.com/product-tour/']" ).css( 'font-weight', 'bold' );
                            $( "ul#adminmenu a[href$='https://www.wpspellcheck.com/account/']" ).attr( 'target', '_blank' );
                            $( "li#wp-admin-bar-WP_Spell_Check a[href$='https://www.wpspellcheck.com/support']" ).attr( 'target', '_blank' );
            });
        </script>
        <?php
    }
    
    function add_toolbar_menu( $wp_admin_bar ) {
            global $wpsc_version;

            $site_url = get_option( 'siteurl' );
            $args = array(
                    'id'    => 'WP_Spell_Check',
                    'title' => 'WP Spell Check',
                    'href'  => $site_url . '/wp-admin/admin.php?page=wp-spellcheck.php',
                    'meta'  => array( 'class' => 'wpsc-toolbar-page' ),
                    'parent' => false
            );
            $wp_admin_bar->add_node( $args );

            $args = array(
                    'id'    => 'WP_Spell_Check_Scanner',
                    'title' => 'Spell Check my Website',
                    'href'  => $site_url . '/wp-admin/admin.php?page=wp-spellcheck.php&action=check&submit=Entire+Site',
                    'meta'  => array( 'class' => 'wpsc-toolbar-page' ),
                    'parent' => 'WP_Spell_Check'
            );
            $wp_admin_bar->add_node( $args );

            $args = array(
                    'id'    => 'WP_Spell_Check_Options',
                    'title' => 'Grammar Check my Website',
                    'href'  => $site_url . '/wp-admin/admin.php?page=wp-spellcheck-grammar.php&action=check&submit=Entire+Site',
                    'meta'  => array( 'class' => 'wpsc-toolbar-page' ),
                    'parent' => 'WP_Spell_Check'
            );
            $wp_admin_bar->add_node( $args );

            $args = array(
                    'id'    => 'WP_Spell_Check_Empty_Scanner',
                    'title' => 'Check for SEO Empty Fields',
                    'href'  => $site_url . '/wp-admin/admin.php?page=wp-spellcheck-seo.php&action=check&submit-empty=Entire+Site',
                    'meta'  => array( 'class' => 'wpsc-toolbar-page' ),
                    'parent' => 'WP_Spell_Check'
            );
            $wp_admin_bar->add_node( $args );

            $args = array(
                    'id'    => 'WP_Spell_Check_Code_Scanner',
                    'title' => 'Check for Broken Code',
                    'href'  => $site_url . '/wp-admin/admin.php?page=wp-spellcheck-html.php&action=check&submit=Entire+Site',
                    'meta'  => array( 'class' => 'wpsc-toolbar-page' ),
                    'parent' => 'WP_Spell_Check'
            );
            $wp_admin_bar->add_node( $args );

            $args = array(
                    'id'    => 'WP_Spell_Check_Dictinary',
                    'title' => 'My Dictionary',
                    'href'  => $site_url . '/wp-admin/admin.php?page=wp-spellcheck-dictionary.php',
                    'meta'  => array( 'class' => 'wpsc-toolbar-page' ),
                    'parent' => 'WP_Spell_Check'
            );
            $wp_admin_bar->add_node( $args );

            $args = array(
                    'id'    => 'WP_Spell_Check_Ignore',
                    'title' => 'My Ignore List',
                    'href'  => $site_url . '/wp-admin/admin.php?page=wp-spellcheck-ignore.php',
                    'meta'  => array( 'class' => 'wpsc-toolbar-page' ),
                    'parent' => 'WP_Spell_Check'
            );
            $wp_admin_bar->add_node( $args );

            $args = array(
                    'id'    => 'WP_Spell_Check_Tutorials',
                    'title' => 'Online Training',
                    'href'  => 'https://www.wpspellcheck.com/support?utm_source=baseplugin&utm_campaign=toturial_topbar&utm_medium=admin_bar&utm_content=' . $wpsc_version,
                    'meta'  => array( 'class' => 'wpsc-toolbar-page' ),
                    'parent' => 'WP_Spell_Check'
            );
            $wp_admin_bar->add_node( $args );

    }
}
