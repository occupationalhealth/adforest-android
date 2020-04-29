<?php

/* ----
  Settings Starts Here
  ---- */
add_action('rest_api_init', 'adforestAPI_settings_api_hooks_get', 0);

function adforestAPI_settings_api_hooks_get() {

    register_rest_route('adforest/v1', '/settings/', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'adforestAPI_settings_me_get',
            /* 'permission_callback' => function () { return adforestAPI_basic_auth();  }, */
            )
    );
}

if (!function_exists('adforestAPI_settings_me_get')) {

    function adforestAPI_settings_me_get() {
        global $adforestAPI;
        $app_is_open = (isset($adforestAPI['app_is_open']) && $adforestAPI['app_is_open'] == true) ? true : false;
        $data['is_app_open'] = $app_is_open;
        $data['heading'] = __("Register With Us!", "adforest-rest-api");
        $data['internet_dialog']['title'] = __("Error", "adforest-rest-api");
        $data['internet_dialog']['text'] = __("Internet not found", "adforest-rest-api");
        $data['internet_dialog']['ok_btn'] = __("Ok", "adforest-rest-api");
        $data['internet_dialog']['cancel_btn'] = __("Cancel", "adforest-rest-api");

        $data['alert_dialog']['message'] = __("Are you sure you want to do this?", "adforest-rest-api");
        $data['alert_dialog']['title'] = __("Alert!", "adforest-rest-api");

        $data['search']['text'] = __("Search Here", "adforest-rest-api");
        $data['search']['input'] = 'ad_title'; /* Static name For field name */
        $data['cat_input'] = 'ad_cats1'; /* Static name For categories */
        $data['message'] = __("Please wait!", "adforest-rest-api");

        /* Options Coming From Theme Options */

        $gmap_lang = (isset($adforestAPI['gmap_lang']) && $adforestAPI['gmap_lang'] == true) ? $adforestAPI['gmap_lang'] : 'en';
        $data['gmap_lang'] = $gmap_lang;

        $is_rtl = (isset($adforestAPI['app_settings_rtl']) && $adforestAPI['app_settings_rtl'] == true) ? true : false;
        $data['is_rtl'] = $is_rtl;

        $app_color = (isset($adforestAPI['app_settings_color']) ) ? $adforestAPI['app_settings_color'] : '#f58936';
        /* Added Custom Color Option 12-Sep-2018 */
        if (isset($adforestAPI['app_settings_color_custom_btn']) && $adforestAPI['app_settings_color_custom_btn'] == true) {
            if (isset($adforestAPI['app_settings_color_custom']) && $adforestAPI['app_settings_color_custom'] != "") {
                $app_color = $adforestAPI['app_settings_color_custom'];
            }
        }

        $data['main_color'] = $app_color;

        $sb_location_type = (isset($adforestAPI['sb_location_type']) ) ? $adforestAPI['sb_location_type'] : 'cities';
        $data['location_type'] = $sb_location_type;

        $data['registerBtn_show']['google'] = (isset($adforestAPI['app_settings_google_btn']) && $adforestAPI['app_settings_google_btn'] == true ) ? true : false;
        $data['registerBtn_show']['facebook'] = (isset($adforestAPI['app_settings_fb_btn']) && $adforestAPI['app_settings_fb_btn'] == true ) ? true : false;

        $data['dialog']['confirmation'] = array(
            "title" => __("Confirmation", "adforest-rest-api"),
            "text" => __("Are you sure you want to do this.", "adforest-rest-api"),
            "btn_no" => __("Cancel", "adforest-rest-api"),
            "btn_ok" => __("Confirm", "adforest-rest-api"),
        );

        $data['notLogin_msg'] = __("Please login to perform this action.", "adforest-rest-api");

        $enable_featured_slider_scroll = (isset($adforestAPI['sb_enable_featured_slider_scroll']) && $adforestAPI['sb_enable_featured_slider_scroll'] == true ) ? true : false;

        $data['featured_scroll_enabled'] = $enable_featured_slider_scroll;
        if ($enable_featured_slider_scroll) {
            $data['featured_scroll']['duration'] = (isset($adforestAPI['sb_enable_featured_slider_duration']) && $adforestAPI['sb_enable_featured_slider_duration'] == true ) ? $adforestAPI['sb_enable_featured_slider_duration'] : 40;

            $data['featured_scroll']['loop'] = (isset($adforestAPI['sb_enable_featured_slider_loop']) && $adforestAPI['sb_enable_featured_slider_loop'] == true ) ? $adforestAPI['sb_enable_featured_slider_loop'] : 2000;
        }

        $data['location_popup']['slider_number'] = 250;
        $data['location_popup']['slider_step'] = 5;
        $data['location_popup']['location'] = __("Current Location:", "adforest-rest-api");

        $search_radius_type = isset($adforestAPI['search_radius_type']) ? $adforestAPI['search_radius_type'] : 'km';
        $data['location_popup']['text'] = __("Select distance in (KM)", "adforest-rest-api");
        if ($search_radius_type == 'mile') {
            $data['location_popup']['text'] = __("Select distance in (Miles)", "adforest-rest-api");
        }

        $data['location_popup']['btn_submit'] = __("Submit", "adforest-rest-api");
        $data['location_popup']['btn_clear'] = __("Clear", "adforest-rest-api");

        /* App GPS Section Starts */
        $allow_near_by = (isset($adforestAPI['allow_near_by']) && $adforestAPI['allow_near_by'] ) ? true : false;
        $data['show_nearby'] = $allow_near_by;
        $data['gps_popup']['title'] = __("GPS Settings", "adforest-rest-api");
        $data['gps_popup']['text'] = __("GPS is not enabled. Do you want to go to settings menu?", "adforest-rest-api");
        $data['gps_popup']['btn_confirm'] = __("Settings", "adforest-rest-api");
        $data['gps_popup']['btn_cancel'] = __("Cancel", "adforest-rest-api");
        /* App GPS Section Ends */

        /* App Rating Section Starts */
        $allow_app_rating = (isset($adforestAPI['allow_app_rating']) && $adforestAPI['allow_app_rating'] ) ? true : false;

        $allow_app_rating_title = (isset($adforestAPI['allow_app_rating_title']) && $adforestAPI['allow_app_rating_title'] != "" ) ? $adforestAPI['allow_app_rating_title'] : __("App Store Rating", "adforest-rest-api");

        if (ADFOREST_API_REQUEST_FROM == 'ios') {
            $allow_app_rating_url = (isset($adforestAPI['allow_app_rating_url_ios']) && $adforestAPI['allow_app_rating_url_ios'] != "" ) ? $adforestAPI['allow_app_rating_url_ios'] : '';
        } else {
            $allow_app_rating_url = (isset($adforestAPI['allow_app_rating_url']) && $adforestAPI['allow_app_rating_url'] != "" ) ? $adforestAPI['allow_app_rating_url'] : '';
        }

        $data['app_rating']['is_show'] = $allow_app_rating;
        $data['app_rating']['title'] = $allow_app_rating_title;

        $data['app_rating']['btn_confirm'] = __("Maybe Later", "adforest-rest-api");
        $data['app_rating']['btn_cancel'] = __("Never", "adforest-rest-api");
        $data['app_rating']['url'] = $allow_app_rating_url;
        /* App Rating Section Ends */

        /* App Share Section Starts */
        $allow_app_share = (isset($adforestAPI['allow_app_share']) && $adforestAPI['allow_app_share'] ) ? true : false;
        $allow_app_share_title = (isset($adforestAPI['allow_app_share_title']) && $adforestAPI['allow_app_share_title'] != "" ) ? $adforestAPI['allow_app_share_title'] : __("Share this", "adforest-rest-api");
        $allow_app_share_text = (isset($adforestAPI['allow_app_share_text']) && $adforestAPI['allow_app_share_text'] != "" ) ? $adforestAPI['allow_app_share_text'] : '';
        $allow_app_share_url = (isset($adforestAPI['allow_app_share_url']) && $adforestAPI['allow_app_share_url'] != "" ) ? $adforestAPI['allow_app_share_url'] : '';

        $data['app_share']['is_show'] = $allow_app_share;
        $data['app_share']['title'] = $allow_app_share_title;
        $data['app_share']['text'] = $allow_app_share_text;
        $data['app_share']['url'] = $allow_app_share_url;
        /* App Share Section Ends */

        $sb_user_guest_dp = ADFOREST_API_PLUGIN_URL . "images/user.jpg";
        if (adforestAPI_getReduxValue('sb_user_guest_dp', 'url', true)) {
            $sb_user_guest_dp = adforestAPI_getReduxValue('sb_user_guest_dp', 'url', false);
        }

        $data['guest_image'] = $sb_user_guest_dp;
        $data['guest_name'] = __("Guest", "adforest-rest-api");

        $has_value = false;
        $array_sortable = array();
        if (isset($adforestAPI['home-screen-sortable']) && $adforestAPI['home-screen-sortable'] > 0) {

            $array_sortable = $adforestAPI['home-screen-sortable'];
            foreach ($array_sortable as $key => $val) {
                if (isset($val) && $val != "") {
                    $has_value = true;
                }
            }
        }
        $data['ads_position_sorter'] = $has_value;

        $data['menu'] = adforestAPI_appMenu_settings();

        $data['messages_screen']['main_title'] = __("Messages", "adforest-rest-api");
        $data['messages_screen']['sent'] = __("Sent Offers", "adforest-rest-api");
        $data['messages_screen']['receive'] = __("Offers on Ads", "adforest-rest-api");


        $data['gmap_has_countries'] = false;
        if (isset($adforestAPI['sb_location_allowed']) && $adforestAPI['sb_location_allowed'] == false && isset($adforestAPI['sb_list_allowed_country'])) {
            $data['gmap_has_countries'] = true;
            $lists = $adforestAPI['sb_list_allowed_country'];
            /* $countries = array(); foreach( $lists as $list ) { $countries[] = $list; } */
            $data['gmap_countries'] = $lists;
        }
        $data['app_show_languages'] = false;
        $languages = array();
        /* $languages[] = array("key" => "en", "value" => "English", "is_rtl" => false);
          $languages[] = array("key" => "ar", "value" => "Arabic", "is_rtl" => true);
          $languages[] = array("key" => "ro_RO", "value" => "RO Lang", "is_rtl" => false); */
        if (count($languages) > 0) {
            $data['app_text_title'] = __("Select or Search Language", "adforest-rest-api");
            $data['app_text_close'] = __("Close", "adforest-rest-api");
            $data['app_show_languages'] = true;
            $data['app_languages'] = $languages;
        }

        if (ADFOREST_API_REQUEST_FROM == 'ios') {
            
        } else {
            $data['upload']['progress_txt'] = array("title" => __("Uploading", "adforest-rest-api"),
                "title_success" => __("Uploaded", "adforest-rest-api"),
                "title_fail" => __("Failed", "adforest-rest-api"),
                "msg_success" => __("File uploaded", "adforest-rest-api"),
                "msg_fail" => __("File upload failed", "adforest-rest-api"),
                "btn_ok" => __("Ok", "adforest-rest-api"));
            $data['upload']['generic_txts'] = array(
                "confirm" => __("Are you sure?", "adforest-rest-api"),
                "btn_cancel" => __("Cancel", "adforest-rest-api"),
                "btn_confirm" => __("Confirm", "adforest-rest-api"),
                "success" => __("Success", "adforest-rest-api")
            );

            $data['permissions'] = array(
                "title" => __("Need Permissions", "adforest-rest-api"),
                "desc" => __("This app needs permission to use this feature. You can grant them in app settings.", "adforest-rest-api"),
                "btn_goto" => __("GoTo Settings", "adforest-rest-api"),
                "btn_cancel" => __("Cancel", "adforest-rest-api")
            );


            $data['suggestionDialog']['confirmation'] = array(
                "title" => __("Confirmation", "adforest-rest-api"),
                "text" => __("Are you sure you want to delete this.", "adforest-rest-api"),
                "btn_no" => __("No", "adforest-rest-api"),
                "btn_ok" => __("Yes", "adforest-rest-api")
            );
        }

        $data['app_page_data'] = adforestAPI_app_shop_pages_func();

        $bytes = @explode("-", $adforestAPI['sb_upload_size']);

        $data['ad_post']['img_size'] = ( isset($adforestAPI['sb_upload_size']) && $adforestAPI['sb_upload_size'] ) ? $bytes[0] : '5242880';
        $data['ad_post']['img_message'] = __("Max image size should be", "adforest-rest-api") . ' ' . $bytes[0];

        $dim = ( isset($adforestAPI['sb_standard_images_size']) && $adforestAPI['sb_standard_images_size'] ) ? true : false;

        $data['ad_post']['dim_is_show'] = $dim;
        if ($dim) {
            $data['ad_post']['dim_width'] = '760';
            $data['ad_post']['dim_height'] = '410';
            $data['ad_post']['dim_height_message'] = __("Image should be at least 760X410 in dimension.", "adforest-rest-api");
        }

        /* Shop Menus */
        global $woocommerce;
        $cart_url = $checkout_url = $shop_page_url = "";
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

            $cart_url = wc_get_cart_url();
            $checkout_url = wc_get_checkout_url();
            $shop_page_url = get_permalink(wc_get_page_id('shop'));
        }
        $shop_page_url = ( $shop_page_url) ? $shop_page_url : "";
        $checkout_url = ( $checkout_url) ? $checkout_url : "";
        $cart_url = ( $cart_url) ? $cart_url : "";

        $data['app_page_test_url'] = $shop_page_url;
        $data['shop_menu'][] = array("title" => __("Shop", "adforest-rest-api"), "url" => $shop_page_url);
        $data['shop_menu'][] = array("title" => __("Cart", "adforest-rest-api"), "url" => $cart_url);
        $data['shop_menu'][] = array("title" => __("Checkout", "adforest-rest-api"), "url" => $checkout_url);
        /* $data['shop_menu'][] 		= array("title" => __("Terms and conditions", "adforest-rest-api"), "url" => $checkout_url); */

        $data['calander_text']['ok_btn'] = __("Ok", "adforest-rest-api");
        $data['calander_text']['cancel_btn'] = __("Cancel", "adforest-rest-api");
        $data['calander_text']['date_time'] = __("Date Time", "adforest-rest-api");

        $data['search_text'] = __("Search", "adforest-rest-api");

        $data['allow_block'] = (isset($adforestAPI['sb_user_allow_block']) && $adforestAPI['sb_user_allow_block']) ? true : false;


        $data['app_top_location'] = (isset($adforestAPI['app_top_location']) && $adforestAPI['app_top_location']) ? true : false;
        $data['app_top_location_list'] = array();
        if (isset($adforestAPI['app_top_location']) && $adforestAPI['app_top_location']) {

            $site_locations_arr = isset($adforestAPI["app_top_location_list"]) && !empty($adforestAPI["app_top_location_list"]) ? $adforestAPI["app_top_location_list"] : '';

            if (!empty($site_locations_arr) && is_array($site_locations_arr) && count($site_locations_arr) > 0) {

                foreach ($site_locations_arr as $country_id) {
                    $get_loc_data = get_term_by('id', $country_id, 'ad_country');
                    $data['app_top_location_list'][] = array(
                        'location_id'=>$country_id,
                        'location_name'=>$get_loc_data->name,
                    );
                }
            }
        }



        $data = apply_filters('AdftiorestAPI_load_wpml_settings', $data);


        return $response = array('success' => true, 'data' => $data, 'message' => '');
    }

}

if (!function_exists('adforestAPI_app_shop_pages_func')) {

    function adforestAPI_app_shop_pages_func() {
        $pages = array();
        global $woocommerce;

        $cart_url = $shop_page_url = $payment_page = $checkout_url = "";

        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $cart_url = wc_get_cart_url();
            $shop_page_url = get_permalink(wc_get_page_id('shop'));
            $payment_page = get_permalink(wc_get_page_id('pay'));
            $checkout_url = wc_get_checkout_url();
        }

        $pages['shop'] = $shop_page_url;

        $myaccount_page_url = '';
        $myaccount_page_id = get_option('woocommerce_myaccount_page_id');
        if ($myaccount_page_id) {
            $myaccount_page_url = get_permalink($myaccount_page_id);
        }
        $pages['myaccount'] = $shop_page_url;
        $pages['cart_url'] = $cart_url;
        $pages['checkout_url'] = $checkout_url;

        if (get_option('woocommerce_force_ssl_checkout') == 'yes')
            $payment_page = str_replace('http:', 'https:', $payment_page);
        $pages['payment_page'] = $payment_page;

        return $pages;
    }

}

if (!function_exists('adforestAPI_is_app_open')) {

    function adforestAPI_is_app_open() {
        global $adforestAPI;

        $app_is_open = (isset($adforestAPI['app_is_open']) && $adforestAPI['app_is_open'] == true) ? true : false;
        $data['is_app_open'] = $app_is_open;
    }

}
/* ----
  Settings Starts Here
  ---- */
add_action('rest_api_init', 'adforestAPI_app_extra_api_hooks_get', 0);

function adforestAPI_app_extra_api_hooks_get() {

    register_rest_route('adforest/v1', '/app_extra/', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'adforestAPI_app_extra_api_func',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );
    /* POST Feedback */
    register_rest_route('adforest/v1', '/app_extra/feedback/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_app_extra_api_feedback_func',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );
}

if (!function_exists('adforestAPI_app_extra_api_func')) {

    function adforestAPI_app_extra_api_func() {
        global $adforestAPI;

        /* if( ADFOREST_API_REQUEST_FROM == 'ios') { } else { } */
        $allow_app_rating = (isset($adforestAPI['allow_app_rating']) && $adforestAPI['allow_app_rating'] ) ? true : false;
        $allow_app_rating_title = (isset($adforestAPI['allow_app_rating_title']) && $adforestAPI['allow_app_rating_title'] != "" ) ? $adforestAPI['allow_app_rating_title'] : __("App Store Rating", "adforest-rest-api");

        if (ADFOREST_API_REQUEST_FROM == 'ios') {
            $allow_app_rating_url = (isset($adforestAPI['allow_app_rating_url_ios']) && $adforestAPI['allow_app_rating_url_ios'] != "" ) ? $adforestAPI['allow_app_rating_url_ios'] : '';
        } else {
            $allow_app_rating_url = (isset($adforestAPI['allow_app_rating_url']) && $adforestAPI['allow_app_rating_url'] != "" ) ? $adforestAPI['allow_app_rating_url'] : '';
        }

        $data['app_rating']['is_show'] = $allow_app_rating;
        $data['app_rating']['title'] = $allow_app_rating_title;

        $data['app_rating']['btn_confirm'] = __("Maybe Later", "adforest-rest-api");
        $data['app_rating']['btn_cancel'] = __("Never", "adforest-rest-api");
        $data['app_rating']['url'] = $allow_app_rating_url;
        /* App Rating Section Ends */

        /* App Share Section Starts */
        $allow_app_share = (isset($adforestAPI['allow_app_share']) && $adforestAPI['allow_app_share'] ) ? true : false;
        $allow_app_share_title = (isset($adforestAPI['allow_app_share_title']) && $adforestAPI['allow_app_share_title'] != "" ) ? $adforestAPI['allow_app_share_title'] : __("Share this", "adforest-rest-api");
        $allow_app_share_text = (isset($adforestAPI['allow_app_share_text']) && $adforestAPI['allow_app_share_text'] != "" ) ? $adforestAPI['allow_app_share_text'] : '';
        $allow_app_share_url = (isset($adforestAPI['allow_app_share_url']) && $adforestAPI['allow_app_share_url'] != "") ? $adforestAPI['allow_app_share_url'] : '';

        $data['app_share']['is_show'] = $allow_app_share;
        $data['app_share']['title'] = $allow_app_share_title;
        $data['app_share']['text'] = $allow_app_share_text;
        $data['app_share']['url'] = $allow_app_share_url;

        /* About App */
        $data_about = array();
        $app_about_show = (isset($adforestAPI['app_about_show']) && $adforestAPI['app_about_show'] ) ? true : false;
        $data['about']['is_show'] = $app_about_show;
        if ($app_about_show) {
            $data['about']['title'] = (isset($adforestAPI['app_about_title']) && $adforestAPI['app_about_title'] != "" ) ? $adforestAPI['app_about_title'] : __("About App", "adforest-rest-api");
            $data['about']['desc'] = (isset($adforestAPI['app_about_desc']) && $adforestAPI['app_about_desc'] != "" ) ? $adforestAPI['app_about_desc'] : "";
        }
        /* App Version */
        $app_about_show = (isset($adforestAPI['app_version_show']) && $adforestAPI['app_version_show'] ) ? true : false;
        $data['app_version']['is_show'] = $app_about_show;
        if ($app_about_show) {
            $data['app_version']['title'] = (isset($adforestAPI['app_version_title']) && $adforestAPI['app_version_title'] != "" ) ? $adforestAPI['app_version_title'] : __("About Version", "adforest-rest-api");
        }

        /* App Faq's */
        $app_faqs_show = (isset($adforestAPI['app_faqs_show']) && $adforestAPI['app_faqs_show'] ) ? true : false;
        $data['faqs']['is_show'] = $app_faqs_show;
        if ($app_faqs_show) {
            $data['faqs']['title'] = (isset($adforestAPI['app_faqs_title']) && $adforestAPI['app_faqs_title'] != "" ) ? $adforestAPI['app_faqs_title'] : __("Faq's", "adforest-rest-api");
            $data['faqs']['url'] = (isset($adforestAPI['app_faqs_url']) && $adforestAPI['app_faqs_url'] != "" ) ? $adforestAPI['app_faqs_url'] : "";
        }

        /* App Faq's */
        $app_privacy_policy_show = (isset($adforestAPI['app_privacy_policy_show']) && $adforestAPI['app_privacy_policy_show'] ) ? true : false;
        $data['privacy_policy']['is_show'] = $app_privacy_policy_show;
        if ($app_privacy_policy_show) {
            $data['privacy_policy']['title'] = (isset($adforestAPI['app_privacy_policy_title']) && $adforestAPI['app_privacy_policy_title'] != "" ) ? $adforestAPI['app_privacy_policy_title'] : __("Privacy Policy", "adforest-rest-api");
            $data['privacy_policy']['url'] = (isset($adforestAPI['app_privacy_policy_url']) && $adforestAPI['app_privacy_policy_url'] != "" ) ? $adforestAPI['app_privacy_policy_url'] : "";
        }

        /* Terms & Contdition */
        $app_tandc_show = (isset($adforestAPI['app_tandc_show']) && $adforestAPI['app_tandc_show'] ) ? true : false;
        $data['tandc']['is_show'] = $app_tandc_show;
        if ($app_tandc_show) {
            $data['tandc']['title'] = (isset($adforestAPI['app_tandc_title']) && $adforestAPI['app_tandc_title'] != "" ) ? $adforestAPI['app_tandc_title'] : __("Terms and Contdition", "adforest-rest-api");
            $data['tandc']['url'] = (isset($adforestAPI['app_tandc_url']) && $adforestAPI['app_tandc_url'] != "" ) ? $adforestAPI['app_tandc_url'] : "";
        }
        /* Feedback */
        $app_tandc_show = (isset($adforestAPI['app_feedback_show']) && $adforestAPI['app_feedback_show'] ) ? true : false;
        $data['feedback']['is_show'] = $app_tandc_show;
        if ($app_tandc_show) {
            $data['feedback']['title'] = (isset($adforestAPI['app_feedback_title']) && $adforestAPI['app_feedback_title'] != "" ) ? $adforestAPI['app_feedback_title'] : __("Feedback", "adforest-rest-api");

            $data['feedback']['subline'] = (isset($adforestAPI['app_feedback_subline']) && $adforestAPI['app_feedback_subline'] != "" ) ? $adforestAPI['app_feedback_subline'] : __("Got any queries? We are here to help you!", "adforest-rest-api");

            $data['feedback']['form']['title'] = __("Enter Your Subject", "adforest-rest-api");
            $data['feedback']['form']['email'] = __("Enter Your Email", "adforest-rest-api");
            $data['feedback']['form']['message'] = __("Enter Your Feedback", "adforest-rest-api");

            $data['feedback']['form']['btn_submit'] = __("Submit", "adforest-rest-api");
            $data['feedback']['form']['btn_cancel'] = __("Cancel", "adforest-rest-api");
        }

        $data['sections']['About'] = __("About", "adforest-rest-api");
        $data['sections']['general'] = __("General", "adforest-rest-api");
        $data['page_title'] = __('App Settings', 'adforest-rest-api');

        return $response = array('success' => true, 'data' => $data, 'message' => '');
    }

}

if (!function_exists('adforestAPI_app_extra_api_feedback_func')) {

    function adforestAPI_app_extra_api_feedback_func($request) {
        global $adforestAPI;

        $json_data = $request->get_json_params();
        $subject = (isset($json_data['subject'])) ? trim($json_data['subject']) : '';
        $email = (isset($json_data['email'])) ? trim($json_data['email']) : '';
        $message = (isset($json_data['message'])) ? trim($json_data['message']) : '';

        $admin_email = (isset($adforestAPI['app_feedback_admin_email']) && $adforestAPI['app_feedback_admin_email'] != "" ) ? $adforestAPI['app_feedback_admin_email'] : "";
        if ($admin_email == "") {
            return $response = array('success' => false, 'data' => '', 'message' => __("Admin email not setup.", "adforest-rest-api"));
        }
        if ($subject == "") {
            return $response = array('success' => false, 'data' => '', 'message' => __("Please enter your subject.", "adforest-rest-api"));
        }
        if ($email == "") {
            return $response = array('success' => false, 'data' => '', 'message' => __("Please enter your email.", "adforest-rest-api"));
        }
        if ($message == "") {
            return $response = array('success' => false, 'data' => '', 'message' => __("Please enter your message.", "adforest-rest-api"));
        }
        /* Send feedback email */
        if (ADFOREST_API_REQUEST_FROM == 'ios') {
            $feednack_on = __("IOS", "adforest-rest-api");
        } else {
            $feednack_on = __("Android", "adforest-rest-api");
        }

        $from = get_bloginfo('name');
        if (isset($adforestAPI['sb_app_feedback_from']) && $adforestAPI['sb_app_feedback_from'] != "") {
            $from = $adforestAPI['sb_app_feedback_from'];
        }

        $headers = array('Content-Type: text/html; charset=UTF-8', "From: $from");

        $subject_keywords = array('%site_name%', '%feedback_from%');
        $subject_replaces = array(get_bloginfo('name'), $feednack_on);
        $subject_title = str_replace($subject_keywords, $subject_replaces, $adforestAPI['sb_app_feedback_subject']);

        $msg_keywords = array('%feedback_subject%', '%feedback_email%', '%feedback_message%', '%feedback_from%');
        $msg_replaces = array($subject, $email, $message, $feednack_on);
        $body = str_replace($msg_keywords, $msg_replaces, $adforestAPI['sb_app_feedback_message']);

        $to = $admin_email;

        $mail_sent = wp_mail($to, $subject_title, $body, $headers);
        if ($mail_sent) {
            return $response = array('success' => true, 'data' => '', 'message' => __("Feedback submitted successfully.", "adforest-rest-api"));
        } else {
            return $response = array('success' => false, 'data' => '', 'message' => __("Something went wrong.", "adforest-rest-api"));
        }
    }

}