<?php
add_filter('AdftiorestAPI_load_active_languages', 'AdftiorestAPI_load_active_languages_callback', 10, 1);
add_filter('AdftiorestAPI_load_wpml_settings', 'AdftiorestAPI_load_wpml_settings_callback', 10, 1);
add_filter('AdforestAPI_wpml_show_all_posts', 'AdforestAPI_wpml_show_all_posts_callback', 0, 1);
add_action('AdforestAPI_duplicate_posts_lang', 'AdforestAPI_duplicate_posts_lang_callback', 10, 1);

if (!function_exists('AdforestAPI_duplicate_posts_lang_callback')) {

    function AdforestAPI_duplicate_posts_lang_callback($org_post_id = 0) {
        global $sitepress,$adforestAPI;
        
        $sb_duplicate_post = isset($adforestAPI['sb_duplicate_post_app']) ? $adforestAPI['sb_duplicate_post_app'] : false;
        
        if (function_exists('icl_object_id') && $org_post_id != 0 && $sb_duplicate_post) {
            $language_details_original = $sitepress->get_element_language_details($org_post_id, 'post_ad_post');
            if (!class_exists('TranslationManagement')) {
                include(ABSPATH . 'wp-content/plugins/sitepress-multilingual-cms/inc/translation-management/translation-management.class.php');
            }
            foreach ($sitepress->get_active_languages() as $lang => $details) {
                if ($lang != $language_details_original->language_code) {
                    $iclTranslationManagement = new TranslationManagement();
                    $iclTranslationManagement->make_duplicate($org_post_id, $lang);
                }
            }
        }
    }

}

if (!function_exists('AdforestAPI_wpml_show_all_posts_callback')) {

    function AdforestAPI_wpml_show_all_posts_callback($query_args = array()) {
        global $sitepress,$adforestAPI;

        $sb_show_posts = isset($adforestAPI['sb_show_posts_app']) ? $adforestAPI['sb_show_posts_app'] : false;

        if (function_exists('icl_object_id') && $query_args != '' && $sb_show_posts) {
            do_action('adforest_wpml_terms_filters');
            $query_args['suppress_filters'] = true;
        }
        return $query_args;
    }

}




if (!function_exists('AdftiorestAPI_load_wpml_settings_callback')) {

    function AdftiorestAPI_load_wpml_settings_callback($data_return = array()) {
        global $adforestAPI;

        $defaultLogo = ADFOREST_API_PLUGIN_URL . "images/logo.png";
        $wpml_logo = (isset($adforestAPI['app_wpml_logo'])) ? $adforestAPI['app_wpml_logo']['url'] : $defaultLogo;
        
        $json_arr_lang = array();
        $get_active_languages = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc');
        if (isset($get_active_languages) && is_array($get_active_languages)) {
            foreach ($get_active_languages as $language) {
                if (isset($adforestAPI['sb_load_languages']) && !empty($adforestAPI['sb_load_languages']) && is_array($adforestAPI['sb_load_languages'])) {
                    foreach ($adforestAPI['sb_load_languages'] as $lang_code) {

                        if ($lang_code == $language['language_code']) {
                            $json_arr_lang[] = array(
                                'code' => $language['code'],
                                'flag_url' => $language['country_flag_url'],
                                'native_name' => $language['native_name'],
                                'translated_name' => $language['translated_name'],
                                'locale' => $language['default_locale'],
                            );
                        }
                    }
                }
            }
        }

        $data_return['is_wpml_active'] = (isset($adforestAPI['sb_api_wpml_anable']) && $adforestAPI['sb_api_wpml_anable']) ? true : false;
        $data_return['wpml_logo'] = $wpml_logo;
        $data_return['wpml_header_title_1'] = isset($adforestAPI['wpml_header_title1']) && !empty($adforestAPI['wpml_header_title1']) ? $adforestAPI['wpml_header_title1'] : 'Pick your';
        $data_return['wpml_header_title_2'] = isset($adforestAPI['wpml_header_title2']) && !empty($adforestAPI['wpml_header_title2']) ? $adforestAPI['wpml_header_title2'] : 'Language';
        $data_return['wpml_menu_text'] = isset($adforestAPI['wpml_menu_text']) && !empty($adforestAPI['wpml_menu_text']) ? $adforestAPI['wpml_menu_text'] : 'Languages';
        $data_return['site_languages'] = $json_arr_lang;

        return $data_return;
    }

}


if (!function_exists('AdftiorestAPI_load_active_languages_callback')) {

    function AdftiorestAPI_load_active_languages_callback($languages_return = array()) {

        if (function_exists('icl_object_id')) {
            $get_active_languages = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc');
            if (isset($get_active_languages) && is_array($get_active_languages)) {
                foreach ($get_active_languages as $language) {
                    $languages_return[$language['language_code']] = $language['translated_name'];
                }
            }
        }
        return $languages_return;
    }

}
?>