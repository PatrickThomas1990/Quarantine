<?php

class wpscx_banner {
    
     function __construct() {}
   
               function check_inactive_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		$show_notice = false;
		global $wpdb;
		$table_name = $wpdb->prefix . "spellcheck_options";
		
		//$option = $wpdb->get_results("SELECT option_value FROM $table_name WHERE option_name = 'last_scan_date'");
		//$time = $option[0]->option_value;
		$last_active = (time()+(60)); 
		
		
		//$time = strtotime($notice_date);
		$first_notice = (time()+(60*60*24*5)); 
		$second_notice = (time()+(60*60*24*20)); 
		$third_notice = (time()+(60*60*24*30)); 
		$last_notices = (time() + (60*60*24*30)); 

		/*if ($times_dismissed == '0') {
			if ($first_notice > $time) {
				$show_notice = true;
			}
		} elseif ($times_dismissed == '1') {
			if ($second_notice > $time) {
				$show_notice = true;
			}
		} elseif ($times_dismissed == '2') {
			if ($third_notice > $time) {
				$show_notice = true;
			}
		} elseif ($last_notices > $time) {
			$show_notice = true;
		}
		
		if ((current_user_can('manage_options')) && $show_notice) {
			
		}*/
		
	}
	
	
	
	
	function show_review_notice() {
		global $current_user;
		global $wpsc_upgrade_show;
		$user_id = $current_user->ID;
		if (!isset($_GET['page'])) $_GET['page'] = '';
		$page = $_GET['page'];

		if ($page != '') $page = '&page=' . $page;		
			$loc = "https://www.wpspellcheck.com/api/survey.php";
			$output = file_get_contents($loc);
			$output = preg_replace("/\?wpsc_ignore_review_notice=1&page=WPSC-PAGE-LINK/",html_entity_decode( esc_url( add_query_arg( array( 'wpsc_ignore_review_notice' => '1' ) ) ), ENT_QUOTES, 'utf-8'),$output);
			if (preg_match("/hide-message/m", $output) || $wpsc_upgrade_show) { } else {
				echo $output;
			}
	}
	
	function ignore_review_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset($_GET['wpsc_ignore_review_notice']) && $_GET['wpsc_ignore_review_notice'] == '1') {
			add_user_meta($user_id, 'wpsc_ignore_review_notice', 'true', true);
			update_user_meta($user_id, 'wpsc_ignore_review_notice', 'true');

			
			$notice_date = time();
			add_user_meta($user_id, 'wpsc_review_date', $notice_date, true);
			update_user_meta($user_id, 'wpsc_review_date', $notice_date);
			
			
			$times_dismissed = get_user_meta($user_id, 'wpsc_times_dismissed_review', true);
			if ($times_dismissed == '0') $times_dismissed = '1';
			if ($times_dismissed == '1') $times_dismissed = '2';
			if ($times_dismissed == '2') $times_dismissed = '3';
			if ($times_dismissed == '3') $times_dismissed = '4';
			update_user_meta($user_id, 'wpsc_times_dismissed_review', $times_dismissed);
		} elseif ( isset($_GET['wpsc_ignore_review_notice']) && $_GET['wpsc_ignore_review_notice'] == '2') {
			add_user_meta($user_id, 'wpsc_ignore_review_notice', 'hide', true);
			update_user_meta($user_id, 'wpsc_ignore_review_notice', 'hide');

			
			$notice_date = time();
			add_user_meta($user_id, 'wpsc_review_date', $notice_date, true);
			update_user_meta($user_id, 'wpsc_review_date', $notice_date);
			
			
			$times_dismissed = get_user_meta($user_id, 'wpsc_times_dismissed_review', true);
			if ($times_dismissed == '0') $times_dismissed = '1';
			if ($times_dismissed == '1') $times_dismissed = '2';
			if ($times_dismissed == '2') $times_dismissed = '3';
			if ($times_dismissed == '3') $times_dismissed = '4';
			update_user_meta($user_id, 'wpsc_times_dismissed_review', $times_dismissed);
		}
	}
	
	function get_notice_timing($user_id) {
            $notice_timing = get_user_meta($user_id, 'wpsc_notice_timing', true);
            $notice_timing_date = get_user_meta($user_id, 'wpsc_notice_timing_date', true);
            
            if ($notice_timing_date == '') {
                $notice_timing_date = time();
                add_user_meta($user_id, 'wpsc_notice_timing_date', $notice_timing_date, true);
            }
            if ($notice_timing == '') {
                $loc = "http://www.wpspellcheck.com/api/notice-timing.php";
                $input = file_get_contents($loc);
                
                add_user_meta($user_id, 'wpsc_notice_timing', $input, true);
            }

            $time = (time() - (60 * 60 * 7));
            if ($time <= $notice_timing_date) {
                $loc = "http://www.wpspellcheck.com/api/notice-timing.php";
                $input = file_get_contents($loc);
                
                update_user_meta($user_id, 'wpsc_notice_timing', $input, true);
                return $input;
            } else {
                return $notice_timing;
            }
        }
	
	function check_review_notice() {
                if (!ini_get('allow_url_fopen')) return;
            
		global $current_user;
		$user_id = $current_user->ID;
		
		
		$notice_date = get_user_meta($user_id, 'wpsc_review_date', true);
		$ignore_review = get_user_meta($user_id, 'wpsc_ignore_review_notice', true);
		$times_dismissed = get_user_meta($user_id, 'wpsc_times_dismissed_review', true);
                
		$show_notice = false;
		
		
		if ($notice_date == '') {
			$notice_date = time();
			add_user_meta($user_id, 'wpsc_review_date', $notice_date, true);
		}
		
				
		if ($times_dismissed == '') {
			add_user_meta($user_id, 'wpsc_times_dismissed_review', '0', true);
		}
                
                $input = $this->get_notice_timing($user_id);
		
		$timing = explode(";",$input);
		$timing_numbers = str_replace("Survey: ","",$timing[0]);
		$timing_list = explode(",",$timing_numbers);
		
		$time = $notice_date;
		$first_notice = (time()-(60*60*24*intval($timing_list[0]))); 
		$second_notice = (time()-(60*60*24*intval($timing_list[1]))); 
		$third_notice = (time()-(60*60*24*intval($timing_list[2]))); 
		$last_notices = (time()-(60*60*24*intval($timing_list[3])));
		
		if ($times_dismissed == '0') {
			if ($first_notice > $time) {
				$show_notice = true;
			}
		} elseif ($times_dismissed == '1') {
			if ($second_notice > $time) {
				$show_notice = true;
			}
		} elseif ($times_dismissed == '2') {
			if ($third_notice > $time) {
				$show_notice = true;
			}
		} elseif ($last_notices > $time) {
			$show_notice = true;
		}
                //$show_notice = true;
                if ((current_user_can('manage_options')) && $show_notice && $ignore_review != 'hide' && $page_action != 'edit') {
                        $this->show_review_notice();
                }
		
	}
	
	
	function ignore_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset($_GET['wpsc_pro_ignore_notice']) && $_GET['wpsc_pro_ignore_notice'] == '1') {
			add_user_meta($user_id, 'wpsc_pro_ignore_notice', 'true', true);
			update_user_meta($user_id, 'wpsc_pro_ignore_notice', 'true');

			
			$notice_date = time();
			update_user_meta($user_id, 'wpsc_pro_notice_date', $notice_date);

			
			$times_dismissed = get_user_meta($user_id, 'wpsc_pro_times_dismissed', true);
			if ($times_dismissed == '0') $times_dismissed = '1';
			if ($times_dismissed == '1') $times_dismissed = '2';
			if ($times_dismissed == '2') $times_dismissed = '3';
			if ($times_dismissed == '3') $times_dismissed = '4';
			update_user_meta($user_id, 'wpsc_pro_times_dismissed', $times_dismissed);
		}
	}

	
                

        
	function show_install_notice() { 
		$page = $_GET['page'];
                if ($_GET['install'] == 'hide') return;
                global $wpsc_version;
                wpscx_set_global_vars();
                ?>
		<div class="wpsc-install-notice">
                    <img src="/wp-content/plugins/wp-spell-check/images/logo.png" alt="WP Spell Check">
                    <img src="/wp-content/plugins/wp-spell-check/images/install-character.png" alt="WP Spell Check" style="position: absolute; left: 5px; bottom: 10px; width: 125px;">
                    <div style="text-align: center; font-weight: bold; font-size: 28px; margin: 5px 0 25px 0;">Thank you for activating WP Spell Check</div>
                    <div style="position: absolute; top: 40%; width: 45%; left: 30%; text-align: left;">
                        <ul style="list-style: disc;">
                            <li style="font-size: 18px;"><a class="wpsc-install-link-delay" href="/wp-admin/admin.php?page=wp-spellcheck.php&action=check&submit=Entire+Site">Spell Check my website</a></li>
                            <li style="list-style-type: none; text-align: center; padding-right: 25%;">Or</li>
                            <li style="font-size: 18px;"><a class="wpsc-install-link" href="https://www.wpspellcheck.com/plugin-support/an-overview-of-the-plugin/?utm_source=baseplugin&utm_campaign=toturial_rightside&utm_medium=spell_check&utm_content=<?php echo $wpsc_version; ?>" target="_blank">Watch a brief Video tutorial</a></li>
                        </ul>
                    </div>
                    <div style="text-align: center; position: absolute; bottom: 17px; width: 100%;"><a href="#" style="text-decoration: none; font-size: 16px;" class="wpsc-install-notice-dismiss">Dismiss this message</a></div>
                </div>
			<script type="text/javascript">
				jQuery(document).ready( function($) {
                                        //$( "#wp-admin-bar-WP_Spell_Check").prepend('<div class="wpsc-install-notice"><div><span style="color: #013c68;">Thank you for activating WP Spell Check.</span><span style="color: green;">Click Up Here!</span><a class="wpsc-install-notice-dismiss" href="<?php echo esc_url( add_query_arg( array( 'wpsc_ignore_install_notice' => '1' ) ) ) ?>">Dismiss<span style="display: inline-block!important; font-size: 10px!important; position: relative; top: -7px; left: 2px;">X</span></a></div><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/install-notice.png' ?>" /></div>');
					
					$('.wpsc-install-notice-dismiss').click(function(e) {
						e.preventDefault();
						
						jQuery.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
							},
							dataType: 'html'
						});
						
						$('.wpsc-install-notice').hide();
					});
                                        $('.wpsc-install-link').click(function(e) {
						jQuery.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
							},
							dataType: 'html'
						});
						
						$('.wpsc-install-notice').hide();
					});
                                        $('.wpsc-install-link-delay').click(function(e) {
                                                e.preventDefault();
                                        
						jQuery.ajax({
							url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
							type: "POST",
							data: {
								action: 'wpsc_dismiss',
							},
							dataType: 'html'
						});
						
						$('.wpsc-install-notice').hide();
                                                window.location.href = "/wp-admin/admin.php?page=wp-spellcheck.php&action=check&submit=Entire+Site&install=hide";
					});
				});
			</script>
		<?php
	}
	
	function ignore_install_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		$dismissed = get_user_meta($user_id, 'wpsc_ignore_install_notice', true);
		if ($dismissed == '') {
			add_user_meta($user_id, 'wpsc_ignore_install_notice', 'true', true);
		} else {
			update_user_meta($user_id, 'wpsc_ignore_install_notice', 'true');
		}
	}
        
        function check_install_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		$dismissed = get_user_meta($user_id, 'wpsc_ignore_install_notice', true);
		
		// && !is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php')
                $cur_page = $_GET['page'];
		if ((current_user_can('manage_options')) && $dismissed != 'true' && ($cur_page == 'wp-spellcheck.php' || $cur_page == 'wp-spellcheck-grammar.php' || $cur_page == 'wp-spellcheck-seo.php' || $cur_page == 'wp-spellcheck-html.php' || $cur_page == 'wp-spellcheck-options.php' || $cur_page == 'wp-spellcheck-dictionary.php' || $cur_page == 'wp-spellcheck-ignore.php')) {
				$this->show_install_notice();
		}
		
		//update_user_meta($user_id, 'wpsc_ignore_install_notice', 'false');
	}
	

	function check_version() {
		$plugin_data = get_plugin_data( __FILE__, false);
		$current_version = $plugin_data['Version'];
		global $current_user;
		$user_id = $current_user->ID;
		$reshow_notice = time();
		
		$last_check = get_user_meta($user_id, 'wpsc_last_check', true);
		$check_version = get_user_meta($user_id, 'wpsc_version', true);
		$is_outdated = get_user_meta($user_id, 'wpsc_outdated', true);
		
		if ($last_check == '') {
			$last_check = time();
			add_user_meta($user_id, 'wpsc_last_check', $last_check, true);
			add_user_meta($user_id, 'wpsc_version', $current_version, true);
			add_user_meta($user_id, 'wpsc_outdated', 'false', true);
			$check_version = $current_version;
			$is_outdated = 'false';
		}
		
		$notice_date = get_user_meta($user_id, 'wpsc_update_notice_date', true);
			
			
			$time = intval($notice_date) + (60);
		
		$recheck_time = (time()-(60*60*24*2)); 
		if ($recheck_time > $last_check) {
			$url = 'https://www.wpspellcheck.com/api/check-version.php';
			
			
			$params = array('current_version' => $current_version);
			
			$args = array(
				'body' => $params,
				'timeout' => '5',
				'redirection' => '5',
				'httpversion' => '1.0',
				'blocking' => true,
				'headers' => array(),
				'cookies' => array()
			);
			
			$response = wp_remote_post($url, $args);
			
			global $current_user;
			$user_id = $current_user->ID;
			$notice_date = get_user_meta($user_id, 'wpsc_update_notice_date', true);
			
			
			$time = intval($notice_date) + (60);
			$reshow_notice = time();
			
			update_user_meta($user_id, 'wpsc_last_check', time());
			
			if ( !is_wp_error( $response ) ) {
				if ($response['response']['code'] == 403) {
					update_user_meta($user_id, 'wpsc_outdated','true');
					update_user_meta($user_id, 'wpsc_version', $current_version);
					if (($time <= $reshow_notice) || $time == '')
						$this->show_upgrade_notice();
				} else {
					
				}
			}
		} else {
			if ((($time <= $reshow_notice) || $time == '') && $current_version == $check_version && $is_outdated == 'true')
				$this->show_upgrade_notice();
		}
	}
	

	function show_upgrade_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		$page = $_GET['page'];
		if ($page != '') $page = '&page=' . $page;
		$upgrade_url = '/wp-admin/update-core.php';
		echo '<div class="update-nag" style="display: block;">There is an update available for <span style="font-weight: bold">WP Spell Check</span>. <a href="' . $upgrade_url . '" style="font-weight: bold;">Click here</a> to update to the latest version.</div>';
	}
	
	function check_version_pro() {
		global $pro_included;
		global $ent_included;
		global $pro_loc;
		global $ent_loc;
		
		if (is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php')) {
			$plugin_data = get_plugin_data( $ent_loc, false);
			$current_version = $plugin_data['Version'];
			global $current_user;
			$user_id = $current_user->ID;
			
			$last_check = get_user_meta($user_id, 'wpsc_ent_last_check', true);
			$check_version = get_user_meta($user_id, 'wpsc_ent_version', true);
			$is_outdated = get_user_meta($user_id, 'wpsc_ent_outdated', true);
			
			if ($last_check == '') {
				$last_check = time();
				add_user_meta($user_id, 'wpsc_ent_last_check', $last_check, true);
				add_user_meta($user_id, 'wpsc_ent_version', $current_version, true);
				add_user_meta($user_id, 'wpsc_ent_outdated', 'false', true);
				$check_version = $current_version;
				$is_outdated = 'false';
			}
			
			$recheck_time = (time()-(60*60*24*0));
			
			if ($recheck_time > $last_check) {
				$url = 'https://www.wpspellcheck.com/api/check-pro-version.php';
				
				
				$params = array('current_version' => $current_version);
				
				$args = array(
					'body' => $params,
					'timeout' => '5',
					'redirection' => '5',
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'cookies' => array()
				);
				
				$response = wp_remote_post($url, $args);
				
				global $current_user;
				$user_id = $current_user->ID;
				$notice_date = get_user_meta($user_id, 'wpsc_ent_update_notice_date', true);
				
				
				$time = intval($notice_date) + (60);
				$reshow_notice = time();
				
				update_user_meta($user_id, 'wpsc_ent_last_check', time());
				
				if ( !is_wp_error( $response ) ) {
					if ($response['response']['code'] == 403) {
						update_user_meta($user_id, 'wpsc_ent_outdated','true');
						update_user_meta($user_id, 'wpsc_ent_version', $current_version);
						if (($time <= $reshow_notice) || $time == '')
							$this->show_upgrade_notice_pro("Enterprise");
					} else {
						
					}
				}
			}
		}
	}
	

	function show_upgrade_notice_pro($plugin_string) {
		global $current_user;
		$user_id = $current_user->ID;
		$page = $_GET['page'];
		if ($page != '') $page = '&page=' . $page;
		$upgrade_url = '/wp-admin/update-core.php';
		echo '<div class="update-nag" style="display: block;"><img src="/wp-content/plugins/wp-spell-check/images/logo-square.png" style="margin: 0px 10px 0px 0;display: inline-block;width: 40px;vertical-align: middle;"><div style="display: inline-block; vertical-align: middle; width: 90%;">There is an update available for <span style="font-weight: bold">WP Spell Check Pro</span>. <a href="/wp-admin/plugins.php" style="font-weight: bold;" >Click here</a> to go to the Plugins page to update.</div></div>';
	}
	
	function ignore_upgrade_notice() {
		global $current_user;
		$user_id = $current_user->ID;
		if ( isset($_GET['wpsc_ignore_upgrade_notice']) && $_GET['wpsc_ignore_upgrade_notice'] == '1') {
			delete_user_meta($user_id, 'wpsc_update_notice_date');
			add_user_meta($user_id, 'wpsc_update_notice_date', time(), true);
		}
	}
        
        function show_upgrade_message() {
		global $wpsc_upgrade_show;
		$wpsc_upgrade_show = true;
		if (!isset($_GET['page'])) $_GET['page'] = '';
		$page = $_GET['page'];
		$loc = "http://www.wpspellcheck.com/api/upgrade-to-pro.php";
		$output = file_get_contents($loc);
		$output = preg_replace("/\?wpsc_ignore_notice=1&page=WPSC-PAGE-LINK/",esc_url( add_query_arg( array( 'wpsc_pro_ignore_notice' => '1' ) ) ),$output);
		echo $output;
	} 

	function check_upgrade_message() {
                if (!ini_get('allow_url_fopen')) return;
            
		global $current_user;
		global $pro_included;
		global $ent_included;
		global $wpsc_upgrade_show;
		$wpsc_upgrade_show = false;
		
		$user_id = $current_user->ID;
		$notice_date = get_user_meta($user_id, 'wpsc_pro_notice_date', true);
		$times_dismissed = get_user_meta($user_id, 'wpsc_pro_dismissed', true);
		$show_notice = false;

		
		if ($notice_date == '') {
			$notice_date = time();
			add_user_meta($user_id, 'wpsc_pro_notice_date', $notice_date, true);
		}

		
		if ($times_dismissed == '') {
			add_user_meta($user_id, 'wpsc_pro_dismissed', '0', true);
		}
		
		$input = $this->get_notice_timing($user_id);
		
		
		$timing = explode(";",$input);
		$timing_numbers = str_replace("Upgrade: ","",$timing[1]);
		$timing_list = explode(",",$timing_numbers);
		
		$time = $notice_date;
		$first_notice = (time()-(60*60*24*intval($timing_list[0]))); 
		$second_notice = (time()-(60*60*24*intval($timing_list[1]))); 
		$third_notice = (time()-(60*60*24*intval($timing_list[2]))); 
		$last_notices = (time()-(60*60*24*intval($timing_list[3])));
		
		if ($times_dismissed == '0') {
			if ($first_notice > $time) {
				$show_notice = true;
			}
		} elseif ($times_dismissed == '1') {
			if ($second_notice > $time) {
				$show_notice = true;
			}
		} elseif ($times_dismissed == '2') {
			if ($third_notice > $time) {
				$show_notice = true;
			}
		} elseif ($last_notices > $time) {
			$show_notice = true;
		}

		if ((current_user_can('manage_options')) && !is_plugin_active('wp-spell-check-pro/wpspellcheckpro.php') && !is_plugin_active('wp-spell-check-enterprise/wpspellcheckenterprise.php') && $show_notice && !$pro_included && !$ent_included) {
			$this->show_upgrade_message();
		}
	}
}

