<?php

class wpscx_scanner {
    private $settings_list;
    private $ignore_list;
    private $to_scan;
    private $haystack;
    
    function __construct() {
        global $wpdb;
        $settings_table = $wpdb->prefix . 'spellcheck_options';
        $dict_table = $wpdb->prefix . 'spellcheck_dictionary';
        $ignore_table = $wpdb->prefix . 'spellcheck_words';
        
        $this->settings_list = SplFixedArray::fromArray($wpdb->get_results("SELECT * FROM $settings_table"));
        $this->ignore_list = $wpdb->get_results("SELECT word FROM $ignore_table WHERE ignore_word = true");
        $dict_list = $wpdb->get_results("SELECT * FROM $dict_table");
        
        $loc = dirname(__FILE__) . "/dict/" . $this->settings_list[11]->option_value . ".pws";
        $contents = file_get_contents($loc);

        $contents = str_replace("\r\n", "\n", $contents);
        $dict_file = explode("\n", $contents);
        
        foreach($dict_file as $value) {
            $this->haystack[strtoupper(stripslashes($value))] = 1;
        }
        
        foreach ($dict_list as $value) {
            $this->haystack[strtoupper(stripslashes($value->word))] = 1;
        }

        foreach ($this->ignore_list as $value) {
            $this->haystack[strtoupper(stripslashes($value->word))] = 1;
        }
    }
    
    function sql_insert($error_list, $page_type, $table_name = '') {
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
    
    function clean_text($content) {
        $content = preg_replace("/\s/u", " ", $content);
        $content = str_replace("’","'", $content);
        $content = str_replace("`","'", $content);
        $content = str_replace('“'," ", $content);
        $content = str_replace("'''","'", $content);
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
        //Spanish characters: áÁéÉíÍñÑóÓúÚüÜ¿¡«»
        //French Characters: ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿ
        $content = preg_replace("/([^0-9'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€a-zA-Z]|'s)+(\s|$|\"|')/ius", " ", $content);
        $content = preg_replace("/(\s|^)(\S+[^ 0-9a-zA-Z'’`ÀàÂâÆæÈèÉéÊêËëÎîÏïÔôŒœÙùÛûÜüŸÿüáÁéÉíÍñÑóÓúÚüÜ¿¡«»€!@#$%^&*()\-=_+,.\/;'[\]\\<>?:\"{}|]+\S+)(\s|$)/u", " ", $content);


        $content = str_replace("§"," ", $content);
        $content = str_replace("¢"," ", $content);
        $content = str_replace("¨"," ", $content);
        $content = str_replace('\\',' ', $content);
        $content = preg_replace("/\r?\n|\r/u", " ", $content);	
    }
    
    function ignore_caps($word) {
        return (strtoupper($word) != $word || $this->settings_list[3]->option_value == 'false');
    }
    
    function content_filter($content) {
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
    
    function clean_script($content) {
        $content = preg_replace("@<style[^>]*?>.*?</style>@siu",' ',$content);
        $content = preg_replace("@<script[^>]*?>.*?</script>@siu",' ',$content);
        $content = preg_replace("/(\<.*?\>)/",' ',$content);
        $content = preg_replace("/<iframe.+<\/iframe>/", " ", $content);

        return $content;
    }
    
    function clean_shortcode($content) {
        return preg_replace('/(\[.*?\])/', ' ', $content);
    }
    
    function clean_html($content) {
        return html_entity_decode(strip_tags($content), ENT_QUOTES, 'utf-8');
    }
    
    function clean_email($content) {
        return preg_replace('/\S+\@\S+\.\S+/', ' ', $content);
    }
    
    function clean_website($content) {
        $content = preg_replace('/((http|https|ftp)\S+)/', '', $content);
        $content = preg_replace('/www\.\S+/', '', $content);
        $content = preg_replace('/(\S+\.(COM|NET|ORG|GOV|INFO|XYZ|US|TOP|LOAN|BIZ|WANG|WIN|CLUB|ONLINE|VIP|MOBI|BID|SITE|MEN|TECH|PRO|SPACE|SHOP|WEBSITE|ASIA|KIWI|XIN|LINK|PARTY|TRADE|LIFE|STORE|NAME|CLOUD|STREAM|CAT|LIVE|TEL|XXX|ACCOUNTANT|DATE|DOWNLOAD|BLOG|WORK|RACING|REVIEW|TODAY|CLICK|ROCKS|NYC|WORLD|EMAIL|SOLUTIONS|NEWS|TOKYO|DESIGN|GURU|LONDON|LTD|ONE|PUB|REALTY|COMPANY|BERLIN|WEBCAM|HOST|PHOTOGRAPHY|PRESS|SCIENCE|FAITH|JOBS|REALTOR|REN|CITY|OVH|RED|AGENCY|SERVICES|MEDIA|GROUP|CENTER|STUDIO|GLOBAL|NINJA|TECHNOLOGY|TIPS|BAYERN|EXPERT|SALE|AMSTERDAM|DIGITAL|ACADEMY|NETWORK|HAMBURG|gdn|DE|CN|UK|NL|EU|RU|TK|AR|BR|IT|PL|FR|AU|CH|CA|ES|JP|KR|DK|BE|SE|AT|CZ|IN|HU|NO|TW|NZ|MX|PT|CL|FI|HK|TR|TRAVEL|AERO|COOP|MUSEUM)[^a-zA-Z])/i', ' ', $content);

        return $content;
    }
    
    function clean_all($content) {
        if (strpos($content, '[fep_submission_form]')) return "";
        try { // Try to clean up all of the content to prepare it for scanning
            $content = $this->clean_script($content);
            $content = $this->clean_shortcode($content);
            $content = $this->clean_html($content);

            if ($this->settings_list[23]->option_value == 'true') {
                    $content = $this->clean_email($content);
            }

            if ($this->settings_list[24]->option_value == 'true') {
                    $content = $this->clean_website($content);
            }

            $content = $this->clean_text($content, $debug);

            return $content;
        } catch(Exception $e) {
            return ""; //If an error occurred while cleaning the content, send back blank content to skip to next entry
        }
    }
}