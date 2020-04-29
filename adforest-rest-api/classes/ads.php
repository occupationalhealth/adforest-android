<?php

/* * * Add REST API support to an already registered post type. */
add_action('init', 'my_custom_post_type_rest_support', 25);

function my_custom_post_type_rest_support() {
    global $wp_post_types;

    //be sure to set this to the name of your post type!
    $post_type_name = 'ad_post';
    if (isset($wp_post_types[$post_type_name])) {
        $wp_post_types[$post_type_name]->show_in_rest = true;
        $wp_post_types[$post_type_name]->rest_base = $post_type_name;
        $wp_post_types[$post_type_name]->rest_controller_class = 'WP_REST_Posts_Controller';
    }
}

add_action('rest_api_init', 'adforestAPI_profile_api_ads_hooks_get', 0);

function adforestAPI_profile_api_ads_hooks_get() {
    register_rest_route(
            'adforest/v1', '/ad_post/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_ad_posts_get',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );
}

if (!function_exists('adforestAPI_ad_posts_get')) {

    function adforestAPI_ad_posts_get($request) {
        global $adforestAPI;

        $json_data = $request->get_json_params();
        $ad_id = (isset($json_data['ad_id'])) ? $json_data['ad_id'] : '';
        $user = wp_get_current_user();
        $user_id = ( @$user ) ? @$user->data->ID : '';
        $post = get_post($ad_id);
        $ad_post_author = get_post_field('post_author', $post->ID);


        $api_owner_deal_text = isset($adforestAPI['api_owner_deal_text']) && !empty($adforestAPI['api_owner_deal_text']) ? $adforestAPI['api_owner_deal_text'] : '';

        /* Expiration of ad starts */
        $has_ad_expired = false;
        if (isset($adforestAPI['simple_ad_removal']) && $adforestAPI['simple_ad_removal'] != '-1') {
            $now = strtotime(current_time('mysql')); /* time(); // or your date as well */
            $simple_date = strtotime(get_the_date('Y-m-d', $post->ID));
            $simple_days = adforestAPI_days_diff($now, $simple_date);
            $expiry_days = $adforestAPI['simple_ad_removal'];
            if ($simple_days > $expiry_days) {
                $has_ad_expired = true;
                wp_trash_post($ad_id);
            }
        }
        if (get_post_meta($ad_id, '_adforest_is_feature', true) == '1' && $adforestAPI['featured_expiry'] != '-1') {
            if (isset($adforestAPI['featured_expiry']) && $adforestAPI['featured_expiry'] != '-1') {
                $now = strtotime(current_time('mysql')); /* time(); // or your date as well */
                $featured_date = strtotime(get_post_meta($ad_id, '_adforest_is_feature_date', true));
                $featured_days = adforestAPI_days_diff($now, $featured_date);
                $expiry_days = $adforestAPI['featured_expiry'];
                if ($featured_days > $expiry_days) {
                    update_post_meta($ad_id, '_adforest_is_feature', 0);
                }
            }
        }
        /* Expiration of ad ends */

        $data = '';
        if (!$post && @count($post) == 0)
            $response = array('success' => false, 'data' => $data, 'message' => __("'Invalid post id'", "adforest-rest-api"));

        $description = trim(preg_replace('/\s+/', ' ', wpautop($post->post_content)));
        $ad_detail['ad_author_id'] = get_post_field('post_author', $post->ID);
        $ad_detail['ad_id'] = $post->ID;
        $ad_detail['ad_title'] = $post->post_title;
        $ad_detail['ad_desc'] = $description . $api_owner_deal_text;
        $ad_detail['ad_date'] = get_the_date("", $post->ID);
        $ad_detail['ad_price'] = adforestAPI_get_price('', $post->ID);
        $ad_detail['phone'] = $poster_phone = get_post_meta($post->ID, '_adforest_poster_contact', true);
        $ad_detail['name'] = $poster_name = get_post_meta($post->ID, '_adforest_poster_name', true);
        $ad_detail['ad_bidding'] = get_post_meta($post->ID, '_adforest_ad_bidding', true);
        $ad_detail['featured_ads'] = get_post_meta($post->ID, '_sb_featured_ads', true);
        $ad_detail['expire_date'] = get_post_meta($post->ID, '_sb_expire_ads', true);
        $ad_detail['ad_status'] = get_post_meta($post->ID, '_adforest_ad_status_', true);
        $ad_detail['ad_timer'] = adforestAPI_get_adTimer($post->ID);

        $ad_detail['ad_type_bar']['is_show'] = false;
        if (get_post_meta($post->ID, '_adforest_ad_type', true) != "") {
            $ad_detail['ad_type_bar']['is_show'] = true;
            $ad_detail['ad_type_bar']['text'] = get_post_meta($post->ID, '_adforest_ad_type', true);
        }

        $is_feature_ads = get_post_meta($post->ID, '_adforest_is_feature', true);
        $ad_detail['is_feature'] = ( $is_feature_ads == 1 ) ? true : false;
        $ad_detail['is_feature_text'] = ( $is_feature_ads == 1 ) ? __("Featured", "adforest-rest-api") : '';
        /* setPostViews */
        adforestAPI_setPostViews($post->ID);
        $viewCount = get_post_meta($post->ID, "sb_post_views_count", true);
        $viewCount = ( $viewCount != "" ) ? $viewCount : 0;
        $ad_detail['ad_view_count'] = $viewCount;

        $ad_currency_count = wp_count_terms('ad_currency');
        if (isset($ad_currency_count) && $ad_currency_count > 0) {
            $ad_detail['ad_currency'] = adforestAPI_get_ad_terms($post->ID, 'ad_currency', '', __("Ad Currency", "adforest-rest-api"));
        }
        $ad_detail['ad_cats'] = adforestAPI_get_ad_terms($post->ID, 'ad_cats', '', __("Categories", "adforest-rest-api"));
        $ad_detail['ad_tags'] = adforestAPI_get_ad_terms($post->ID, 'ad_tags', '', __("Tags", "adforest-rest-api"));

        $ad_tags_show = adforestAPI_get_ad_terms_names($ad_id, 'ad_tags', '', '', $separator = ',');
        $ad_tags_show_name = ( $ad_tags_show != "" ) ? __("Tags", "adforest-rest-api") : "";
        $ad_detail['ad_tags_show'] = array("name" => $ad_tags_show_name, "value" => $ad_tags_show);

        $ad_detail['ad_video'] = adforestAPI_get_adVideo($post->ID);
        $myAdLocation = adforestAPI_get_adAddress($post->ID);
        $ad_detail['location'] = $myAdLocation;

        $ad_myCountry1 = (isset($myAdLocation['address']) && $myAdLocation['address'] != "" ) ? $myAdLocation['address'] : '';
        $is_show_location = wp_count_terms('ad_country');
        $ad_myCountry2 = '';
        if (isset($is_show_location) && $is_show_location > 0) {
            /* Some Location Code Goes Here */
            $ad_myCountry2 = adforestAPI_get_ad_terms_names($ad_id, 'ad_country', '', '', $separator = ',');
            //adforestAPI_terms_seprates_by($ad_id , 'ad_cats',  ', ');		
            //$dynamicData[] =  array("key" => __("Location", "adforest-rest-api"), "value" => $ad_country, "type" => '');	
        }
        $ad_myCountry = ( $ad_myCountry2 != "" ) ? $ad_myCountry2 : $ad_myCountry1;
        $ad_detail['location_top'] = $ad_myCountry;
        $ad_detail['fieldsData_column'] = (isset($adforestAPI['api_ad_details_info_column'])) ? $adforestAPI['api_ad_details_info_column'] : 2;
        $ad_detail['fieldsData'] = adforestAPI_get_customFields((int) $post->ID);

        /* Ad Owner Id */
        $ad_detail['author_id'] = get_post_field('post_author', $post->ID);

        /* Get ads images */
        $ad_detail['images'] = adforestAPI_get_ad_image($post->ID);
        $ad_detail['slider_images'] = adforestAPI_get_ad_image_slider($post->ID);
        /* Related Articles Started */
        $ad_detail['related_ads'] = array();
        $static_text['related_posts_title'] = '';
        $getSimilar = adforestApi_related_ads($post->ID, 1);
        if (isset($adforestAPI['related_ads_on']) && $adforestAPI['related_ads_on'] == true && count($getSimilar) > 0) {
            $rtitle = ($adforestAPI['sb_related_ads_title'] != "" ) ? $adforestAPI['sb_related_ads_title'] : __("Related Posts", "adforest-rest-api");
            $static_text['related_posts_title'] = $rtitle;
            $relatedAds = (isset($adforestAPI['api_ad_details_related_posts'])) ? $adforestAPI['api_ad_details_related_posts'] : 5;
            $getSimilar = adforestApi_related_ads($post->ID, $relatedAds);
            $ad_detail['related_ads'] = $getSimilar;
        }
        /* Related Articles Ends */
        /* adforestAPI_bidding_stats($ad_id) */
        $profile_detail = adforestAPI_basic_profile_data(get_post_field('post_author', $post->ID), $poster_name);
        $static_text['share_btn'] = __("Share", "adforest-rest-api");
        $static_text['fav_btn'] = __("Add To Favourites", "adforest-rest-api");
        $static_text['report_btn'] = __("Report", "adforest-rest-api");

        $send_msg_btn_type = ( $user_id == get_post_field('post_author', $post->ID)) ? 'receive' : 'sent';
        $send_msg_btn = ( $user_id == get_post_field('post_author', $post->ID)) ? __("View Messages", "adforest-rest-api") : __("Send Message", "adforest-rest-api");
        $static_text['send_msg_btn_type'] = $send_msg_btn_type;
        $static_text['send_msg_btn'] = $send_msg_btn;
        $static_text['call_now_btn'] = __("Call Now", "adforest-rest-api");
        $communication_mode = (isset($adforestAPI['communication_mode'])) ? $adforestAPI['communication_mode'] : 'both';
        if ($communication_mode == 'phone') {
            $show_call_btn = true;
            $show_megs_btn = false;
        } else if ($communication_mode == 'message') {
            $show_call_btn = false;
            $show_megs_btn = true;
        } else {
            $show_call_btn = true;
            $show_megs_btn = true;
        }

        $static_text['show_call_btn'] = $show_call_btn;
        $static_text['show_megs_btn'] = $show_megs_btn;
        $bid_now_txt = ($user_id != get_post_field('post_author', $post->ID)) ? __("Bid Now", "adforest-rest-api") : __("View Bids", "adforest-rest-api");
        $static_text['bid_now_btn'] = $bid_now_txt;
        $static_text['bid_stats_btn'] = __("Bid Statistics", "adforest-rest-api");
        $static_text['bid_tabs']['bid'] = __("Bidding", "adforest-rest-api");
        $static_text['bid_tabs']['stats'] = __("Bid Statistics", "adforest-rest-api");
        $static_text['get_direction'] = __("Get Direction", "adforest-rest-api");
        $static_text['description_title'] = __("Description", "adforest-rest-api");
        $allow_block = (isset($adforestAPI['sb_user_allow_block']) && $adforestAPI['sb_user_allow_block']) ? true : false;
        $static_text['block_user']['is_show'] = $allow_block;
        if ($allow_block) {
            $static_text['block_user']['text'] = __("Block User", "adforest-rest-api");
            $static_text['block_user']['popup_title'] = __("Block User?", "adforest-rest-api");
            $static_text['block_user']['popup_text'] = __("Are you sure you want to block user. You will not see this user ads anywhere.", "adforest-rest-api");
            $static_text['block_user']['popup_cancel'] = __("Cancel", "adforest-rest-api");
            $static_text['block_user']['popup_confirm'] = __("Confrim", "adforest-rest-api");
        }
        /* Bids */
        //$ad_detail['ad_bids'] = array("stats" => adforestAPI_bid_stat($post->ID), "offers" => adforestAPI_bids($post->ID));
        $is_bid_enabled = false;
        if (isset($adforestAPI['sb_enable_comments_offer']) && $adforestAPI['sb_enable_comments_offer']) {
            $is_bid_enabled = true;
            if (isset($adforestAPI['sb_enable_comments_offer_user']) && $adforestAPI['sb_enable_comments_offer_user']) {
                $is_bid_enabled = true;
                $is_exist = get_post_meta($post->ID, "_adforest_ad_bidding", true);
                $is_bid_enabled = ( $is_exist == 1 ) ? true : false;
            }
        }

        $static_text['ad_bids_enable'] = $is_bid_enabled;
        $static_text['ad_bids'] = adforestAPI_bid_stat($post->ID);
        //$static_text['ad_bids_btn'] = __("Make A Bid", "adforest-rest-api");
        $bid_popup['input_text'] = __("Bid Amount", "adforest-rest-api");
        $bid_popup['input_textarea'] = __("Bid description here", "adforest-rest-api");
        $bid_popup['btn_send'] = __("Send", "adforest-rest-api");
        $bid_popup['btn_cancel'] = __("Cancel", "adforest-rest-api");
        $report_popup['select']['key'] = __("Select Option", "adforest-rest-api");
        $r_names = array();
        $r_values = array();
        if (isset($adforestAPI['report_options']) && $adforestAPI['report_options'] != "") {
            $options = @explode('|', $adforestAPI['report_options']);
            foreach ($options as $option) {
                $r_names[] = $option;
                $r_values[] = ($option);
            }
        } else {
            $r_names = array(__("Offensive", "adforest-rest-api"), __("Spam", "adforest-rest-api"), __("Duplicate", "adforest-rest-api"),);
            $r_values = array("offensive", "Spam", "Duplicate",);
        }

        $report_popup['select']['name'] = $r_names;
        $report_popup['select']['value'] = $r_values;
        $report_popup['input_textarea'] = __("You message here.", "adforest-rest-api");
        $report_popup['btn_send'] = __("Send", "adforest-rest-api");
        $report_popup['btn_cancel'] = __("Cancel", "adforest-rest-api");
        $send_message['input_textarea'] = __("You message here.", "adforest-rest-api");
        $send_message['btn_send'] = __("Send", "adforest-rest-api");
        $send_message['btn_cancel'] = __("Cancel", "adforest-rest-api");
        $call_now['text'] = __("Call Now", "adforest-rest-api");
        $call_now['btn_send'] = __("Call Now", "adforest-rest-api");
        $call_now['btn_cancel'] = __("Cancel", "adforest-rest-api");
        $phone_verification = (isset($adforestAPI['sb_phone_verification']) && $adforestAPI['sb_phone_verification'] ) ? true : false;
        $call_now['phone_verification'] = $phone_verification;
        if ($phone_verification) {
            $is_phone_verified = false;
            $verified_text = __("Not verified", "adforest-rest-api");
            $ad_post_author_id = get_post_field('post_author', $post->ID);
            $saved_ph = get_user_meta($ad_post_author_id, '_sb_contact', true);
            $adNum = get_user_meta($ad_post_author_id, '_sb_is_ph_verified', true);
            $adNumV = ( $adNum == 1 ) ? true : false;
            if ($saved_ph == $poster_phone && $adNum == 1) {
                $is_phone_verified = true;
                $verified_text = __("verified", "adforest-rest-api");
            }
            $call_now['is_phone_verified'] = $is_phone_verified;
            $call_now['is_phone_verified_text'] = $verified_text;
        }
        $share_info['title'] = $post->post_title;
        $share_info['link'] = get_the_permalink($post->ID);
        $share_info['text'] = __("Share this", "adforest-rest-api");
        $post_status = ( get_post_status($post->ID) != "publish" ) ? __("Waiting for admin approval.", "adforest-rest-api") : "";
        $featured_notify = adforestAPI_adFeatured_notify($post->ID);
        $is_featured_ad['is_show'] = ( isset($featured_notify) && count($featured_notify) > 0 ) ? true : false;
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            /* SomeTHingNew */
        } else {
            $is_featured_ad['is_show'] = false;
        }
        $is_featured_ad['notification'] = $featured_notify;
        //$rating_data = adforestAPI_adDetails_rating_get( $post->ID, 1, false );
        $ad_rating = adforestAPI_adDetails_rating_get($ad_id, 1, false);
        $info_link_text = (isset($adforestAPI['api_ad_details_info_link_text']) && $adforestAPI['api_ad_details_info_link_text'] ) ? $adforestAPI['api_ad_details_info_link_text'] : __("Click Here", "adforest-rest-api");
        $showPhone_to_users = adforestAPI_showPhone_to_users();
        $data = array(
            "notification" => $post_status,
            "is_featured" => $is_featured_ad,
            "page_title" => __("Ad Details", "adforest-rest-api"),
            "ad_detail" => $ad_detail,
            "profile_detail" => $profile_detail,
            "static_text" => $static_text,
            "bid_popup" => $bid_popup,
            "report_popup" => $report_popup,
            "message_popup" => $send_message,
            "call_now_popup" => $call_now,
            "share_info" => $share_info,
            "ad_ratting" => $ad_rating,
            "click_here_text" => $info_link_text,
            "cant_report_txt" => __("You can't report your own ad", "adforest-rest-api"),
            "edit_txt" => __("Edit", "adforest-rest-api"),
            "show_phone_to_login" => $showPhone_to_users,
        );

        $message_text = '';
        $success_typle = true;
        if (get_post_status($post->ID) != "publish" && $ad_post_author != $user_id && $has_ad_expired) {
            $success_typle = false;
            $message_text = __("This ad is expired.", "adforest-rest-api");
        }

        $response = array('success' => $success_typle, 'data' => $data, 'message' => $message_text);

        return $response;
    }

}
/* add_filter( 'rest_prepare_ad_post', 'adforestAPI_ad_posts_get', 10, 3 ); */
/* Fav Ad */
add_action('rest_api_init', 'adforestAPI_ad_favourite_hook', 0);

function adforestAPI_ad_favourite_hook() {
    register_rest_route(
            'adforest/v1', '/ad_post/favourite/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_ad_favourite',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );
}

if (!function_exists('adforestAPI_ad_favourite')) {

    function adforestAPI_ad_favourite($request) {
        $json_data = $request->get_json_params();
        $ad_id = (isset($json_data['ad_id'])) ? $json_data['ad_id'] : '';
        $current_user = wp_get_current_user();
        $current_user_id = $current_user->data->ID;
        if (get_user_meta($current_user_id, '_sb_fav_id_' . $ad_id, true) == $ad_id) {
            return array('success' => false, 'data' => '', 'message' => __("You have added already.", "adforest-rest-api"));
        } else {
            update_user_meta($current_user_id, '_sb_fav_id_' . $ad_id, $ad_id);
            return array('success' => false, 'data' => '', 'message' => __("Added to your favourites.", "adforest-rest-api"));
        }
    }

}

/* Report ad */
add_action('rest_api_init', 'adforestAPI_ad_report_hook', 0);

function adforestAPI_ad_report_hook() {
    register_rest_route(
            'adforest/v1', '/ad_post/report/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_ad_report',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );
}

if (!function_exists('adforestAPI_ad_report')) {

    function adforestAPI_ad_report($request) {
        global $adforestAPI;
        $json_data = $request->get_json_params();
        $ad_id = (isset($json_data['ad_id'])) ? $json_data['ad_id'] : '';
        $option = (isset($json_data['option'])) ? $json_data['option'] : '';
        $comments = (isset($json_data['comments'])) ? $json_data['comments'] : '';
        $ad_owser = get_post_field('post_author', $ad_id);
        $current_user = wp_get_current_user();
        $current_user_id = $current_user->data->ID;

        if ($ad_owser == $current_user_id) {
            return array('success' => false, 'data' => '', 'message' => __("You can't report your own ad", "adforest-rest-api"));
        }

        if (get_post_meta($ad_id, '_sb_user_id_' . $current_user_id, true) == $current_user_id) {
            return array('success' => false, 'data' => '', 'message' => __("You have reported already.", "adforest-rest-api"));
        } else {
            update_post_meta($ad_id, '_sb_user_id_' . $current_user_id, $current_user_id);
            update_post_meta($ad_id, '_sb_report_option_' . $current_user_id, $option);
            update_post_meta($ad_id, '_sb_report_comments_' . $current_user_id, $comments);

            $count = get_post_meta($ad_id, '_sb_count_report', true);
            $count = (int) $count + 1;
            update_post_meta($ad_id, '_sb_count_report', $count);
            $message = __("Reported successfully.", "adforest-rest-api");
            if ($count >= $adforestAPI['report_limit']) {
                $message = __("Reported successfully.", "adforest-rest-api");
                if ($adforestAPI['report_action'] == '1') {
                    $my_post = array('ID' => $ad_id, 'post_status' => 'pending',);
                    wp_update_post($my_post);
                    $message = __("The ad you have reported has been removed.", "adforest-rest-api");
                } else {
                    /* Send Email Function */
                    adforestAPI_sb_report_ad($ad_id, $option, $comments, $current_user_id);
                }
            }
            return array('success' => true, 'data' => '', 'message' => $message);
        }
    }

}

add_action('rest_api_init', 'adforestAPI_ads_hooks_ad_search_template_get', 0);

function adforestAPI_ads_hooks_ad_search_template_get() {
    register_rest_route(
            'adforest/v1', '/ad_post/dynamic_widget/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_ad_search_get1',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
    ));
}

if (!function_exists('adforestAPI_ad_search_get1')) {

    function adforestAPI_ad_search_get1($request) {
        global $adforestAPI;
        $showcatData = false;
        $arrays = array();
        if (isset($adforestAPI['adpost_cat_template']) && $adforestAPI['adpost_cat_template'] == true) {
            $showcatData = true;
        }
        if (isset($adforestAPI['adpost_cat_template']) && $adforestAPI['adpost_cat_template'] == false) {
            return $response = array('success' => true, 'data' => $arrays, 'message' => '');
        }
        $json_data = $request->get_json_params();
        $term_id = (isset($json_data['cat_id'])) ? $json_data['cat_id'] : '';
        $result = adforest_dynamic_templateID($term_id);
        $templateID = get_term_meta($result, '_sb_dynamic_form_fields', true);
        $templateID = ( $showcatData == true ) ? $templateID : '';
        /* New Code Starts Here */
        $type = sb_custom_form_data($templateID, '_sb_default_cat_ad_type_show');
        $price = sb_custom_form_data($templateID, '_sb_default_cat_price_show');
        $priceType = sb_custom_form_data($templateID, '_sb_default_cat_price_type_show');
        $condition = sb_custom_form_data($templateID, '_sb_default_cat_condition_show');
        $warranty = sb_custom_form_data($templateID, '_sb_default_cat_warranty_show');
        //$tags 		= 	sb_custom_form_data($templateID, '_sb_default_cat_tags_show');
        //$video 		= 	sb_custom_form_data($templateID, '_sb_default_cat_video_show');
        /* New Code Ends Here */
        if (isset($templateID) && $templateID != "") {
            $formData = sb_dynamic_form_data($templateID);
            foreach ($formData as $r) {
                if (isset($r['types']) && trim($r['types']) != "") {
                    $in_search = (isset($r['in_search']) && $r['in_search'] == "yes") ? 1 : 0;
                    if ($r['titles'] != "" && $r['slugs'] != "" && $in_search == 1) {
                        $mainTitle = $name = $r['titles'];
                        $fieldName = $r['slugs'];
                        $fieldValue = (isset($_GET["custom"]) && isset($_GET['custom'][$r['slugs']])) ? $_GET['custom'][$r['slugs']] : '';
                        /* Inputs */
                        if (isset($r['types']) && $r['types'] == 1) {
                            $arrays[] = array("main_title" => $mainTitle, "field_type" => 'textfield', "field_type_name" => $fieldName, "field_val" => "", "field_name" => "", "title" => $name, "values" => $fieldValue);
                        }
                        /* select option */
                        if (isset($r['types']) && $r['types'] == 2 || isset($r['types']) && $r['types'] == 3) {

                            $varArrs = @explode("|", $r['values']);
                            $termsArr = array();
                            if ($r['types'] == 2) {
                                $termsArr[] = array
                                    (
                                    "id" => "",
                                    "name" => __("Select Option", "adforest-rest-api"),
                                    "has_sub" => false,
                                    "has_template" => false,
                                );
                            }
                            foreach ($varArrs as $v) {
                                $termsArr[] = array
                                    (
                                    "id" => $v,
                                    "name" => $v,
                                    "has_sub" => false,
                                    "has_template" => false,
                                );
                            }

                            $ftype = ($r['types'] == 2 ) ? 'select' : 'radio';
                            $arrays[] = array("main_title" => $mainTitle, "field_type" => $ftype, "field_type_name" => $fieldName, "field_val" => "", "field_name" => "", "title" => $name, "values" => $termsArr);
                        }

                        /* For Input Date Section */
                        if (isset($r['types']) && $r['types'] == 4) {
                            $fieldName = '_adforest_min_and_max_date_' . $fieldName;
                            $arrays[] = array("main_title" => $mainTitle, "field_type" => 'textfield_date', "field_type_name" => $fieldName, "field_val" => "", "field_name" => "", "title" => $name, "values" => $fieldValue);
                        }

                        /* for number range */
                        if (isset($r['types']) && $r['types'] == 6) {

                            $varArrs = @explode("|", $r['values']);
                            $hiddenMin = ( isset($varArrs[0]) && (int) $varArrs[0] ) ? $varArrs[0] : 0;
                            $hiddenMax = ( isset($varArrs[1]) && (int) $varArrs[1] ) ? $varArrs[1] : 100000;
                            $hiddenStp = ( isset($varArrs[2]) && (int) $varArrs[2] ) ? $varArrs[2] : 1;
                            $varArrs = @explode("|", $r['values']);
                            $termsArr = array("min_val" => $hiddenMin, "max_val" => $hiddenMax, "steps" => $hiddenStp);
                            $fieldName = '_adforest_min_and_max_number_' . $fieldName;
                            $arrays[] = array("main_title" => $mainTitle, "field_type" => 'number_range', "field_type_name" => $fieldName, "field_val" => "", "field_name" => "", "title" => $name, "values" => $termsArr);
                        }

                        /* select colors option */
                        if (isset($r['types']) && $r['types'] == 7) {

                            $varArrs = @explode("|", $r['values']);
                            $termsArr = array();

                            foreach ($varArrs as $v) {
                                $colors = @explode(":", $v);
                                $code = ( isset($colors[0]) && $colors[0] != "" ) ? $colors[0] : '';
                                $name = ( isset($colors[1]) && $colors[1] != "" ) ? $colors[1] : '';
                                if ($code != "" && $name != "") {
                                    $termsArr[] = array
                                        (
                                        "id" => $code,
                                        "name" => $name,
                                        "has_sub" => false,
                                        "has_template" => false,
                                    );
                                }
                            }

                            $arrays[] = array("main_title" => $mainTitle, "field_type" => 'radio_color', "field_type_name" => $fieldName, "field_val" => "", "field_name" => "", "title" => $mainTitle, "values" => $termsArr);
                        }

                        /* select radio button option */
                        if (isset($r['types']) && $r['types'] == 8) {
                            $varArrs = @explode("|", $r['values']);
                            $termsArr = array();
                            foreach ($varArrs as $v) {
                                $termsArr[] = array
                                    (
                                    "id" => $v,
                                    "name" => $v,
                                    "has_sub" => false,
                                    "has_template" => false,
                                );
                            }

                            $arrays[] = array("main_title" => $mainTitle, "field_type" => 'radio', "field_type_name" => $fieldName, "field_val" => "", "field_name" => "", "title" => $name, "values" => $termsArr);
                        }
                        /* select chec2b6xes */
                        if (isset($r['types']) && $r['types'] == 9) {

                            $varArrs = @explode("|", $r['values']);
                            $termsArr = array();

                            foreach ($varArrs as $v) {
                                $termsArr[] = array
                                    (
                                    "id" => $v,
                                    "name" => $v,
                                    "has_sub" => false,
                                    "has_template" => false,
                                );
                            }

                            $arrays[] = array("main_title" => $mainTitle, "field_type" => 'checkbox', "field_type_name" => $fieldName, "field_val" => "", "field_name" => "", "title" => $name, "values" => $termsArr);
                        }
                    }
                }
            }
        }
        /* return $arrays; */
        if ($condition == 1 && $templateID != "" && $showcatData == true) {
            $arrays[] = adforestAPI_getSearchFields('select', 'ad_condition', 'ad_condition', 0, __("Condition", "adforest-rest-api"), '', '', false);
        } else if ($templateID != "" && $condition == 0) {
            
        } else if ($templateID == "" || $showcatData == true) {
            $arrays[] = adforestAPI_getSearchFields('select', 'ad_condition', 'ad_condition', 0, __("Condition", "adforest-rest-api"), '', '', false);
        }

        if ($warranty == 1 && $templateID != "" && $showcatData == true) {
            $arrays[] = adforestAPI_getSearchFields('select', 'ad_warranty', 'ad_warranty', 0, __("Warranty", "adforest-rest-api"), '', '', false);
        } else if ($templateID != "" && $warranty == 0) {
            
        } else if ($templateID == "" || $showcatData == true) {
            $arrays[] = adforestAPI_getSearchFields('select', 'ad_warranty', 'ad_warranty', 0, __("Warranty", "adforest-rest-api"), '', '', false);
        }
        /* Add Type */
        if ($type == 1 && $templateID != "" && $showcatData == true) {
            $arrays[] = adforestAPI_getSearchFields('select', 'ad_type', 'ad_type', 0, __("Ad Type", "adforest-rest-api"), '', '', false);
        } else if ($templateID != "" && $type == 0) {
            
        } else if ($templateID == "" || $showcatData == true) {
            $arrays[] = adforestAPI_getSearchFields('select', 'ad_type', 'ad_type', 0, __("Ad Type", "adforest-rest-api"), '', '', false);
        }
        /* Add Price */
        if ($priceType == 1 && $templateID != "" && $showcatData == true) {
            $fieldTitle = array(__("Min Price", "adforest-rest-api"), __("Max Price", "adforest-rest-api"));
            $arrays[] = adforestAPI_getSearchFields('select', 'ad_currency', 'ad_currency', 0, __("Currency", "adforest-rest-api"), '', '', false);
            $arrays[] = adforestAPI_getSearchFields('range_textfield', 'ad_price', '', 0, $fieldTitle, __("Price", "adforest-rest-api"));
        } else if ($templateID != "" && $priceType == 0) {
            
        } else if ($templateID == "" || $showcatData == true) {
            $fieldTitle = array(__("Min Price", "adforest-rest-api"), __("Max Price", "adforest-rest-api"));
            $arrays[] = adforestAPI_getSearchFields('select', 'ad_currency', 'ad_currency', 0, __("Currency", "adforest-rest-api"), '', '', false);
            $arrays[] = adforestAPI_getSearchFields('range_textfield', 'ad_price', '', 0, $fieldTitle, __("Price", "adforest-rest-api"));
        }
        return $response = array('success' => true, 'data' => $arrays, 'message' => '');
    }

}

add_action('rest_api_init', 'adforestAPI_ads_hooks_ad_search_get', 0);

function adforestAPI_ads_hooks_ad_search_get() {
    register_rest_route(
            'adforest/v1', '/ad_post/search/', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'adforestAPI_ad_search_get',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
    ));
}

if (!function_exists('adforestAPI_ad_search_get')) {

    function adforestAPI_ad_search_get() {
        global $adforestAPI;
        $is_featured_data['-1'] = __("Select Option", "adforest-rest-api");
        $is_featured_data['0'] = __("Simple", "adforest-rest-api");
        $is_featured_data['1'] = __("Featured", "adforest-rest-api");
        if (isset($adforestAPI['adpost_cat_template']) && $adforestAPI['adpost_cat_template'] == false) {
            $data[] = adforestAPI_getSearchFields('select', 'is_featured', $is_featured_data, 0, __("Ad Type", "adforest-rest-api"), '');
        }

        $data[] = adforestAPI_getSearchFields('textfield', 'ad_title', '', 0, __("Search", "adforest-rest-api"), '');
        $data[] = adforestAPI_getSearchFields('select', 'ad_cats1', 'ad_cats', 0, __("Categories", "adforest-rest-api"), '');

        if (isset($adforestAPI['adpost_cat_template']) && $adforestAPI['adpost_cat_template'] == false) {
            $data[] = adforestAPI_getSearchFields('select', 'ad_condition', 'ad_condition', 0, __("Condition", "adforest-rest-api"), '', '', false);
            $data[] = adforestAPI_getSearchFields('select', 'ad_warranty', 'ad_warranty', 0, __("Warranty", "adforest-rest-api"), '', '', false);
            //$data[] = adforestAPI_getSearchFields('select'	 , 'ad_type', 'ad_type', 0, __("Ad Type", "adforest-rest-api"),'', '', false);
            /* $data[] = adforestAPI_getSearchFields('glocation_textfield', 'ad_location', '', 0, __("Location", "adforest-rest-api"), ''); */
            $fieldTitle = array(__("Min Price", "adforest-rest-api"), __("Max Price", "adforest-rest-api"));

            $is_show_ad_currency = wp_count_terms('ad_currency');
            if (isset($is_show_ad_currency) && $is_show_ad_currency > 0) {
                $data[] = adforestAPI_getSearchFields('select', 'ad_currency', 'ad_currency', 0, __("Currency", "adforest-rest-api"), '', '', false);
            }
            $data[] = adforestAPI_getSearchFields('range_textfield', 'ad_price', '', 0, $fieldTitle, __("Price", "adforest-rest-api"));
        }
        $is_show_location = wp_count_terms('ad_country');
        if (isset($is_show_location) && $is_show_location > 0) {
            $data[] = adforestAPI_getSearchFields('select', 'ad_country', 'ad_country', 0, __("Location", "adforest-rest-api"), '');
        }
        $data[] = adforestAPI_getSearchFields('glocation_textfield', 'ad_location', '', 0, __("Address", "adforest-rest-api"), '');
        /* For radious search only */
        $data[] = adforestAPI_getSearchFields('seekbar', 'ad_seekbar', '', 0, __("Select Distance (KM)", "adforest-rest-api"), '');
        /* fields name will be sort */
        $topbar['sort_arr'][] = array("key" => "desc", "value" => __("DESC", "adforest-rest-api"));
        $topbar['sort_arr'][] = array("key" => "asc", "value" => __("ASC", "adforest-rest-api"));
        $topbar['sort_arr'][] = array("key" => "price_desc", "value" => __("Price: High to Low", "adforest-rest-api"));
        $topbar['sort_arr'][] = array("key" => "price_asc", "value" => __("Price: Low to High", "adforest-rest-api"));
        $extra['field_type_name'] = 'ad_cats1';
        $extra['title'] = __("Search Here", "adforest-rest-api");
        $extra['search_btn'] = __("Search Now", "adforest-rest-api");
        $extra['dialog_send'] = __("Submit", "adforest-rest-api");
        $extra['dialg_cancel'] = __("Cancel", "adforest-rest-api");
        return $response = array('success' => true, 'data' => $data, 'message' => '', 'topbar' => $topbar, 'extra' => $extra);
    }

}

add_action('rest_api_init', 'adforestAPI_ad_subcats_get', 0);

function adforestAPI_ad_subcats_get() {
    register_rest_route(
            'adforest/v1', '/ad_post/subcats/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_ad_subcats',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
    ));
}

if (!function_exists('adforestAPI_ad_subcats')) {

    function adforestAPI_ad_subcats($request) {
        $json_data = $request->get_json_params();
        $subcat = (isset($json_data['subcat'])) ? $json_data['subcat'] : '';
        $mainTermName = '';
        if ($subcat != "") {
            $mainTerm = get_term($subcat);
            $mainTermName = htmlspecialchars_decode($mainTerm->name, ENT_NOQUOTES);
        }
        $data = adforestAPI_getSubCats('select', 'ad_cats1', 'ad_cats', $subcat, $mainTermName, '', false);
        return $response = array('success' => true, 'data' => $data, 'message' => '');
    }

}

add_action('rest_api_init', 'adforestAPI_ad_sublocations_get', 0);

function adforestAPI_ad_sublocations_get() {
    register_rest_route(
            'adforest/v1', '/ad_post/sublocations/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_ad_sublocations',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
    ));
}

if (!function_exists('adforestAPI_ad_sublocations')) {

    function adforestAPI_ad_sublocations($request) {
        $json_data = $request->get_json_params();
        $subcat = (isset($json_data['ad_country'])) ? $json_data['ad_country'] : '';
        $mainTermName = '';
        if ($subcat != "") {
            $mainTerm = get_term($subcat);
            $mainTermName = htmlspecialchars_decode($mainTerm->name, ENT_NOQUOTES);
        }
        $data = adforestAPI_getSubCats('select', 'ad_country', 'ad_country', $subcat, $mainTermName, '', false);
        return $response = array('success' => true, 'data' => $data, 'message' => '');
    }

}

add_action('rest_api_init', 'adforestAPI_ads_hooks_get_all', 0);

function adforestAPI_ads_hooks_get_all() {
    register_rest_route(
            'adforest/v1', '/ad_post/search/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_ad_posts_get_all',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
    ));

    register_rest_route(
            'adforest/v1', '/ad_post/category/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_ad_posts_get_all',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
    ));
}

if (!function_exists('adforestAPI_ad_posts_get_all')) {

    function adforestAPI_ad_posts_get_all($request) {

        global $adforestAPI;
        $json_data = $request->get_json_params();
        $ad_id = (isset($json_data['ad_id'])) ? $json_data['ad_id'] : '';
        $meta = array('key' => 'post_id', 'value' => '0', 'compare' => '!=',);
        /* For Near By Ads */
        $allow_near_by = (isset($adforestAPI['allow_near_by']) && $adforestAPI['allow_near_by'] ) ? true : false;
        $lat_lng_meta_query = array();
        if ($allow_near_by) {
            $latitude = (isset($json_data['nearby_latitude'])) ? $json_data['nearby_latitude'] : '';
            $longitude = (isset($json_data['nearby_longitude'])) ? $json_data['nearby_longitude'] : '';
            $distance = (isset($json_data['nearby_distance'])) ? $json_data['nearby_distance'] : '20';
            $data_array = array("latitude" => $latitude, "longitude" => $longitude, "distance" => $distance);
            if ($latitude != "" && $longitude != "" && (int) $distance > 0) {
                $lats_longs = adforestAPI_determine_minMax_latLong($data_array, false);
                if (isset($lats_longs) && count($lats_longs) > 0) {
                    $lat_lng_meta_query[] = array(
                        'key' => '_adforest_ad_map_lat',
                        'value' => array($lats_longs['lat']['min'], $lats_longs['lat']['max']),
                        'compare' => 'BETWEEN',
                        'type' => 'DECIMAL',
                    );

                    $lat_lng_meta_query[] = array(
                        'key' => '_adforest_ad_map_long',
                        'value' => array($lats_longs['long']['min'], $lats_longs['long']['max']),
                        'compare' => 'BETWEEN',
                        'type' => 'DECIMAL',
                    );

                    add_filter('get_meta_sql', 'adforestAPI_cast_decimal_precision');
                    if (!function_exists('adforestAPI_cast_decimal_precision')) {

                        function adforestAPI_cast_decimal_precision($array) {
                            $array['where'] = str_replace('DECIMAL', 'DECIMAL(10,3)', $array['where']);
                            return $array;
                        }

                    }
                }
            }
        }
        /* For Near By Ads Ends */
        /* Done Stars */
        $title = '';
        if (isset($json_data['ad_title']) && $json_data['ad_title'] != "") {
            $title = $json_data['ad_title'];
        }
        $price = array();

        $priceVal = array();
        if (isset($json_data['ad_price']) && $json_data['ad_price'] != "") {
            $priceVal = ( isset($json_data['ad_price']) && $json_data['ad_price'] != "" ) ? $json_data['ad_price'] : '';
        } else if (isset($json_data['custom_fields']['ad_price']) && $json_data['custom_fields']['ad_price'] != "") {
            $priceVal = ( isset($json_data['custom_fields']['ad_price']) && $json_data['custom_fields']['ad_price'] != "" ) ? $json_data['custom_fields']['ad_price'] : '';
        }

        $priceValue = @explode("-", $priceVal);
        $minPrice = ( isset($priceValue[0]) && $priceValue[0] != "" ) ? (int) $priceValue[0] : "";
        $maxPrice = ( isset($priceValue[1]) && $priceValue[1] != "" ) ? (int) $priceValue[1] : "";
        if ($minPrice != "") {
            $price = array(
                'key' => '_adforest_ad_price',
                'value' => array($minPrice, $maxPrice),
                'type' => 'numeric',
                'compare' => 'BETWEEN',
            );
        }

        $location = array();
        if (isset($json_data['ad_location']) && $json_data['ad_location'] != "") {
            $location = array(
                'key' => '_adforest_ad_location',
                'value' => @trim($json_data['ad_location']),
                'compare' => "LIKE",
            );
        }
        /* ad_country Starts Here */
        $ad_currency_val = '';
        if (isset($json_data['ad_currency']) && $json_data['ad_currency'] != "") {
            $ad_currency_val = @trim($json_data['ad_currency']);
        } else if (isset($json_data['custom_fields']['ad_currency']) && $json_data['custom_fields']['ad_currency'] != "") {
            $ad_currency_val = @trim($json_data['custom_fields']['ad_currency']);
        }
        $ad_currency = array();
        if (isset($ad_currency_val) && $ad_currency_val != "") {
            $ad_currency = array(
                'key' => '_adforest_ad_currency',
                'value' => $ad_currency_val,
                'compare' => '=',
            );
        }

        $category = array();
        if (isset($json_data['ad_cats1']) && $json_data['ad_cats1'] != "") {
            $category = array(array('taxonomy' => 'ad_cats', 'field' => 'term_id', 'terms' => (int) $json_data['ad_cats1'],),);
        }
        $category = (isset($category) && count($category) > 0 ) ? $category : '';
        /* ad_country Starts Here */
        $ad_country_val = '';
        if (isset($json_data['ad_country']) && $json_data['ad_country'] != "") {
            $ad_country_val = @trim($json_data['ad_country']);
        } else if (isset($json_data['custom_fields']['ad_country']) && $json_data['custom_fields']['ad_country'] != "") {
            $ad_country_val = @trim($json_data['custom_fields']['ad_country']);
        }
        $ad_country = array();
        if (isset($ad_country_val) && $ad_country_val != "") {
            $ad_country = array(array('taxonomy' => 'ad_country', 'field' => 'term_id', 'terms' => (int) $ad_country_val,),);
        }
        $ad_country = (isset($ad_country) && count($ad_country) > 0 ) ? $ad_country : '';

        $feature_or_simple = array();
        if (isset($json_data['is_featured']) && $json_data['is_featured'] != "" && $json_data['is_featured'] != -1) {
            $feature_or_simple = array('key' => '_adforest_is_feature', 'value' => (int) $json_data['is_featured'], 'compare' => '=',);
        }
        /* ad_type Starts Here */
        $ad_type_val = '';
        if (isset($json_data['ad_type']) && $json_data['ad_type'] != "") {
            $ad_type_val = @trim($json_data['ad_type']);
        } else if (isset($json_data['custom_fields']['ad_type']) && $json_data['custom_fields']['ad_type'] != "") {
            $ad_type_val = @trim($json_data['custom_fields']['ad_type']);
        }

        $ad_type = array();
        if (isset($ad_type_val) && $ad_type_val != "") {
            $ad_type = array('key' => '_adforest_ad_type', 'value' => $ad_type_val, 'compare' => '=',);
        }
        /* ad_type Ends Here */
        /* Condition Starts Here */
        $condition_val = '';
        if (isset($json_data['ad_condition']) && $json_data['ad_condition'] != "") {
            $condition_val = @trim($json_data['ad_condition']);
        } else if (isset($json_data['custom_fields']['ad_condition']) && $json_data['custom_fields']['ad_condition'] != "") {
            $condition_val = @trim($json_data['custom_fields']['ad_condition']);
        }

        $condition = array();
        if (isset($condition_val) && $condition_val != "") {
            $condition = array('key' => '_adforest_ad_condition', 'value' => $condition_val, 'compare' => '=',);
        }
        /* Condition Ends Here */
        /* Warranty Starts Here */
        $warranty_val = '';
        if (isset($json_data['ad_warranty']) && $json_data['ad_warranty'] != "") {
            $warranty_val = @trim($json_data['ad_warranty']);
        } else if (isset($json_data['custom_fields']['ad_warranty']) && $json_data['custom_fields']['ad_warranty'] != "") {
            $warranty_val = @trim($json_data['custom_fields']['ad_warranty']);
        }

        $warranty = array();
        if (isset($warranty_val) && $warranty_val != "") {
            $warranty = array('key' => '_adforest_ad_warranty', 'value' => $warranty_val, 'compare' => '=',);
        }
        /* Warranty Ends Here */
        $custom_search = array();

        if (isset($json_data['custom_fields'])) {
            $custom_fields_json = $json_data['custom_fields'];
            $request_from = adforestAPI_getSpecific_headerVal('Adforest-Request-From');
            if ($request_from == 'ios') {
                $custom_fields_json = json_decode(@$json_data['custom_fields'], true);
            }

            $nyKey = array();
            foreach ((array) $custom_fields_json as $key => $val) {
                if ($key == 'ad_price' || $key == 'ad_warranty' || $key == 'ad_condition' || $key == 'ad_type' || $key == 'ad_currency') {
                    continue;
                }
                if (is_array($val)) {
                    $arr = array();
                    $metaKey = '_adforest_tpl_field_' . $key;
                    foreach ($val as $v) {
                        if ($v != "") {
                            $custom_search[] = array(
                                'key' => $metaKey,
                                'value' => $v,
                                'compare' => 'LIKE',
                            );
                        }
                    }
                } else {
                    if (trim($val) == "0") {
                        continue;
                    }
                    if ($val != "") {
                        $minMaxKey = '_adforest_min_and_max_number_';
                        $min_max_number = strpos($key, $minMaxKey);

                        $minMaxKeyDate = '_adforest_min_and_max_date_';
                        $min_max_date = strpos($key, $minMaxKeyDate);

                        if ($min_max_number !== false) {
                            $key = str_replace($minMaxKey, "", $key);
                            $key = '_adforest_tpl_field_' . $key;
                            $keyVal = @explode("-", $val);
                            $get_minVal = ( isset($keyVal[0]) && $keyVal[0] != "" ) ? $keyVal[0] : "";
                            $get_maxVal = ( isset($keyVal[1]) && $keyVal[1] != "" ) ? $keyVal[1] : "";
                            if ($get_minVal != "" && $get_minVal != 0) {
                                $custom_search[] = array(
                                    'key' => $key,
                                    'value' => array($get_minVal, $get_maxVal),
                                    'type' => 'numeric',
                                    'compare' => 'BETWEEN',
                                );
                            }
                        } else if ($min_max_date !== false) {
                            $key = str_replace($minMaxKeyDate, "", $key);
                            $key = '_adforest_tpl_field_' . $key;
                            $keyVal = @explode("|", $val);
                            $get_minVal = ( isset($keyVal[0]) && $keyVal[0] != "" ) ? $keyVal[0] : "";
                            $get_maxVal = ( isset($keyVal[1]) && $keyVal[1] != "" ) ? $keyVal[1] : "";
                            if ($get_minVal != "" && $get_minVal != 0) {

                                $custom_search[] = array(
                                    'key' => $key,
                                    'value' => array($get_minVal, $get_maxVal),
                                    'compare' => 'BETWEEN',
                                );
                            }
                        } else {

                            $val = stripslashes_deep($val);

                            $metaKey = '_adforest_tpl_field_' . $key;
                            $custom_search[] = array(
                                'key' => $metaKey,
                                'value' => $val,
                                'compare' => 'LIKE',
                            );
                        }
                    }
                }
            }
        }
        /* Done Ends , ,  */

        if (get_query_var('paged')) {
            $paged = get_query_var('paged');
        } else if (isset($json_data['page_number'])) {
            $paged = $json_data['page_number'];
        } else {
            $paged = 1;
        }

        $is_active = array('key' => '_adforest_ad_status_', 'value' => 'active', 'compare' => '=',);
        $order = 'desc';
        $orderBy = 'date';

        if (isset($json_data['sort']) && $json_data['sort'] != "") {
            $order_val = $json_data['sort'];
            if ($order_val == 'asc' || $order_val == 'price_asc') {
                $order = 'asc';
            }
            if ($order_val == 'price_desc' || $order_val == 'price_asc') {
                $orderBy = 'meta_value_num';
            }
        }
        $author_not_in = adforestAPI_get_authors_notIn_list();
        $args = array(
            's' => $title,
            'post_type' => 'ad_post',
            'post_status' => 'publish',
            'posts_per_page' => get_option('posts_per_page'),
            'tax_query' => array(
                $category,
                $ad_country,
            ),
            'meta_key' => '_adforest_ad_price',
            'meta_query' => array(
                $is_active,
                $condition,
                $ad_type,
                $warranty,
                $feature_or_simple,
                $price,
                $location,
                $custom_search,
                $ad_currency,
                $lat_lng_meta_query,
            ),
            'order' => $order,
            'orderby' => $orderBy,
            'paged' => $paged,
            'author__not_in' => $author_not_in
        );

        $results = new WP_Query($args);
        $count = 0;
        $ad_detail = array();
        foreach ($results->posts as $r) {
            $ad_detail[$count]['ad_id'] = $r->ID;
            $ad_detail[$count]['ad_title'] = $r->post_title;
            $ad_detail[$count]['ad_author_id'] = get_post_field('post_author', $r->ID);
            $ad_detail[$count]['ad_date'] = get_the_date("", $r->ID);
            $ad_detail[$count]['ad_price'] = adforestAPI_get_price('', $r->ID);
            $ad_detail[$count]['images'] = adforestAPI_get_ad_image($r->ID, 1, 'thumb');
            $ad_detail[$count]['ad_video'] = adforestAPI_get_adVideo($r->ID);
            $ad_detail[$count]['location'] = adforestAPI_get_adAddress($r->ID);
            $ad_detail[$count]['ad_cats_name'] = adforestAPI_get_ad_terms_names($r->ID, 'ad_cats');
            $ad_detail[$count]['ad_cats'] = adforestAPI_get_ad_terms($r->ID, 'ad_cats', '', __("Categories", "adforest-rest-api"));
            $ad_detail[$count]['ad_status'] = adforestAPI_adStatus($r->ID);
            $ad_detail[$count]['ad_views'] = get_post_meta($r->ID, "sb_post_views_count", true);
            $ad_detail[$count]['ad_saved'] = array("is_saved" => 0, "text" => __("Save Ad", "adforest-rest-api"));
            $ad_detail[$count]['ad_timer'] = adforestAPI_get_adTimer($r->ID);
            $count++;
        }
        wp_reset_postdata();

        $fads['text'] = '';
        $fads['ads'] = array();
        $extra['is_show_featured'] = (isset($adforestAPI['feature_on_search']) && $adforestAPI['feature_on_search'] == 1 ) ? true : false;
        if (isset($adforestAPI['feature_on_search']) && $adforestAPI['feature_on_search'] == 1) {
            $featuredAdsCount = ( $adforestAPI['search_related_posts_count'] != "" ) ? $adforestAPI['search_related_posts_count'] : 5;
            $featuredAdsTitle = ( $adforestAPI['sb_search_ads_title'] != "" ) ? $adforestAPI['sb_search_ads_title'] : __("Featured Ads", "adforest-rest-api");

            $featured_termID = ( isset($json_data['ad_cats1']) && $json_data['ad_cats1'] != "" ) ? $json_data['ad_cats1'] : '';
            $featuredAds = adforestApi_featuredAds_slider('', 'active', '1', $featuredAdsCount, $featured_termID, 'publish');
            if (isset($featuredAds) && count($featuredAds) > 0) {
                $fads['text'] = $featuredAdsTitle;
                $fads['ads'] = $featuredAds;
                $extra['is_show_featured'] = true;
            } else {
                $extra['is_show_featured'] = false;
            }
        }
        $topbar['count_ads'] = __("No of Ads Found", "adforest-rest-api") . ': ' . $results->found_posts;

        $nextPaged = $paged + 1;

        $has_next_page = ( $nextPaged <= (int) $results->max_num_pages ) ? true : false;

        $pagination = array("max_num_pages" => (int) $results->max_num_pages, "current_page" => (int) $paged, "next_page" => (int) $nextPaged, "increment" => (int) get_option('posts_per_page'), "current_no_of_ads" => (int) count($results->posts), "has_next_page" => $has_next_page);

        $data = array("featured_ads" => $fads, "ads" => $ad_detail, "sidebar" => "");

        /* fields name will be sort */
        $sort_arr_desc = array("key" => "desc", "value" => __("DESC", "adforest-rest-api"));
        $sort_arr_asc = array("key" => "asc", "value" => __("ASC", "adforest-rest-api"));

        $sort_arr_price_desc = array("key" => "price_desc", "value" => __("Price: High to Low", "adforest-rest-api"));
        $sort_arr_price_asc = array("key" => "price_asc", "value" => __("Price: Low to High", "adforest-rest-api"));
        if ($order == 'desc') {
            $topbar['sort_arr_key'] = $sort_arr_desc;
            $topbar['sort_arr'][] = $sort_arr_desc;
            $topbar['sort_arr'][] = $sort_arr_asc;
            $topbar['sort_arr'][] = $sort_arr_price_desc;
            $topbar['sort_arr'][] = $sort_arr_price_asc;
        }
        if ($order == 'asc') {
            $topbar['sort_arr_key'] = $sort_arr_asc;
            $topbar['sort_arr'][] = $sort_arr_asc;
            $topbar['sort_arr'][] = $sort_arr_desc;
            $topbar['sort_arr'][] = $sort_arr_price_desc;
            $topbar['sort_arr'][] = $sort_arr_price_asc;
        }
        if ($order == 'price_desc') {
            $topbar['sort_arr_key'] = $sort_arr_price_desc;
            $topbar['sort_arr'][] = $sort_arr_price_desc;
            $topbar['sort_arr'][] = $sort_arr_asc;
            $topbar['sort_arr'][] = $sort_arr_desc;
            $topbar['sort_arr'][] = $sort_arr_price_asc;
        }
        if ($order == 'price_asc') {
            $topbar['sort_arr_key'] = $sort_arr_price_asc;
            $topbar['sort_arr'][] = $sort_arr_price_asc;
            $topbar['sort_arr'][] = $sort_arr_asc;
            $topbar['sort_arr'][] = $sort_arr_desc;
            $topbar['sort_arr'][] = $sort_arr_price_desc;
        }

        $get_route = $request->get_route();
        $get_route2 = explode("/", $get_route);
        $final_name = (end($get_route2));
        if ($final_name == 'search') {
            $searchTitle = __("Search Here", "adforest-rest-api");
        } else {
            $searchTitle = __("Category", "adforest-rest-api");
            if (isset($json_data['ad_cats1']) && $json_data['ad_cats1'] != "") {
                $term = get_term($json_data['ad_cats1'], 'ad_cats');
                $searchTitle = htmlspecialchars_decode(@$term->name, ENT_NOQUOTES);
            }
        }

        $extra['field_type_name'] = 'ad_cats1';
        $extra['title'] = $searchTitle;


        return $response = array('success' => true, 'data' => $data, 'message' => '', 'extra' => $extra, "topbar" => $topbar, "pagination" => $pagination, 'humayun' => 'test');
    }

}

if (!function_exists('adforestAPI_ad_dynamic_fields_data')) {

    function adforestAPI_ad_dynamic_fields_data($term_id = '') {
        $result = adforest_dynamic_templateID($term_id);
        $templateID = get_term_meta($result, '_sb_dynamic_form_fields', true);
        $arrays = array();
        if (isset($templateID) && $templateID != "") {
            $formData = sb_dynamic_form_data($templateID);
            foreach ($formData as $r) {
                if (isset($r['types']) && trim($r['types']) != "") {

                    $in_search = (isset($r['in_search']) && $r['in_search'] == "yes") ? 1 : 0;
                    if ($r['titles'] != "" && $r['slugs'] != "" && $in_search == 1) {

                        $mainTitle = $name = $r['titles'];
                        /* $fieldName = "custom[".$r['slugs']."]"; */
                        $fieldName = $r['slugs'];
                        $fieldValue = (isset($_GET["custom"]) && isset($_GET['custom'][$r['slugs']])) ? $_GET['custom'][$r['slugs']] : '';
                        /* Inputs */
                        if (isset($r['types']) && $r['types'] == 1) {
                            $arrays[] = array("key" => $name, "value" => $fieldValue);
                        }
                        /* select option */
                        if (isset($r['types']) && $r['types'] == 2 || isset($r['types']) && $r['types'] == 3) {
                            $varArrs = @explode("|", $r['values']);
                            $termsArr = array();
                            foreach ($varArrs as $v) {
                                $termsArr[] = array("id" => $v, "name" => $v,);
                            }
                            $arrays[] = array("key" => $name, "value" => $termsArr);
                        }
                    }
                }
            }
        }
        return $arrays;
    }

}

/* Single Ad Featured Notification */
if (!function_exists('adforestAPI_adFeatured_notify')) {

    function adforestAPI_adFeatured_notify($ad_id = '', $check_statuc = false) {
        $user = wp_get_current_user();
        $uid = @$user->data->ID;
        $pid = $uid;
        $data = array();
        $isFeature = get_post_meta($ad_id, '_adforest_is_feature', true);
        $isFeature = ( $isFeature ) ? $isFeature : 0;
        if (get_post_meta($ad_id, '_adforest_ad_status_', true) == 'active' && $check_statuc == false) {
            
        }
        /* //&& get_post_meta( $ad_id, '_adforest_ad_status_', true ) == 'active' */
        if ($uid != "" && $isFeature == 0) {
            if (get_post_field('post_author', $ad_id) == $uid) {
                $featured_ads_count = get_user_meta($uid, '_sb_featured_ads', true);
                if ($featured_ads_count != 0) {
                    $expire_ads_time = get_user_meta($uid, '_sb_expire_ads', true);
                    if ($expire_ads_time != '-1') {
                        if ($expire_ads_time < date('Y-m-d')) {
                            $data['text'] = __('Your package has been expired, please subscribe the package to make it feature AD. ', 'adforest-rest-api');
                            $data['link'] = $ad_id;
                            $data['make_feature'] = false;
                            $data['btn'] = __('Buy', 'adforest-rest-api');
                        } else {
                            $data['text'] = __('Click Here To make this ad featured.', 'adforest-rest-api');
                            $data['link'] = $ad_id;
                            $data['make_feature'] = true;
                            $data['btn'] = __('Make Featured', 'adforest-rest-api');
                        }
                    } else {
                        $data['text'] = __('Click Here To make this ad featured.', 'adforest-rest-api');
                        $data['link'] = $ad_id;
                        $data['make_feature'] = true;
                        $data['btn'] = __('Make Featured', 'adforest-rest-api');
                    }
                } else {
                    $data['text'] = __('To make ad featured buy package.', 'adforest-rest-api');
                    $data['link'] = $ad_id;
                    $data['make_feature'] = false;
                    $data['btn'] = __('Buy', 'adforest-rest-api');
                }
            }
        }
        return $data;
    }

}

add_action('rest_api_init', 'adforestAPI_makeAd_featured_hook', 0);

function adforestAPI_makeAd_featured_hook() {
    register_rest_route(
            'adforest/v1', '/ad_post/featured/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_makeAd_featured',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
    ));
}

if (!function_exists('adforestAPI_makeAd_featured')) {

    function adforestAPI_makeAd_featured($request) {

        $json_data = $request->get_json_params();
        $ad_id = (isset($json_data['ad_id'])) ? trim($json_data['ad_id']) : '';

        $user = wp_get_current_user();
        $user_id = $user->data->ID;

        $success = false;
        if (get_post_field('post_author', $ad_id) == $user_id) {
            if (get_post_meta($ad_id, '_adforest_is_feature', true) == 0) {
                if (get_user_meta($user_id, '_sb_featured_ads', true) > 0 || get_user_meta($user_id, '_sb_featured_ads', true) == '-1') {
                    if (get_user_meta($user_id, '_sb_expire_ads', true) != '-1') {
                        if (get_user_meta($user_id, '_sb_expire_ads', true) < date('Y-m-d')) {
                            $message = __("Your package has bee expired.", 'adforest-rest-api');
                        }
                    }
                    $feature_ads = get_user_meta($user_id, '_sb_featured_ads', true);
                    $feature_ads2 = $feature_ads;
                    $feature_ads = $feature_ads - 1;
                    if ($feature_ads2 != "-1") {
                        update_user_meta($user_id, '_sb_featured_ads', $feature_ads);
                    }
                    update_post_meta($ad_id, '_adforest_is_feature', '1');
                    update_post_meta($ad_id, '_adforest_is_feature_date', date('Y-m-d'));
                    $message = __("This ad has been featured successfully.", 'adforest-rest-api');
                    $success = true;
                } else {
                    $message = __("Get package in order to make it feature.", 'adforest-rest-api');
                }
            } else {
                $message = __("Ad already featured.", 'adforest-rest-api');
            }
        } else {
            $message = __("You must be Ad owner tomake it feature.", 'adforest-rest-api');
        }
        $response = array('success' => $success, 'data' => '', 'message' => $message);
        return $response;
    }

}

add_action('rest_api_init', 'adforestAPI_terms_list_hook', 0);

function adforestAPI_terms_list_hook() {
    register_rest_route(
            'adforest/v1', '/terms/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_get_terms_list_func',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
    ));
}

if (!function_exists('adforestAPI_get_terms_list_func')) {

    function adforestAPI_get_terms_list_func($request) {
        global $adforestAPI;
        /* {"term_name":"ad_cats", "term_id": 0, "page_number":1} */
        $json_data = $request->get_json_params();
        $term_name = (isset($json_data['term_name'])) ? trim($json_data['term_name']) : '';
        $cTerm_id = (isset($json_data['term_id']) && $json_data['term_id'] != "") ? trim($json_data['term_id']) : 0;

        if (isset($json_data['page_number'])) {
            $paged = $json_data['page_number'];
        } else {
            $paged = 1;
        }
        $show_term_counts = false;
        if ($term_name == 'ad_cats' || $term_name == 'ad_country') {
            
        } else {
            $message = __("Something wrong, Not a valid item selected.", 'adforest-rest-api');
            return array('success' => false, 'data' => '', 'message' => $message);
        }

        if ($term_name == '') {
            $message = __("No term name is selected.", 'adforest-rest-api');
            return array('success' => false, 'data' => '', 'message' => $message);
        }

        $total_terms = wp_count_terms($term_name, array('hide_empty' => false, 'parent' => $cTerm_id,));
        $posts_per_page = 20; //get_option( 'posts_per_page' );
        $start = ($paged - 1) * $posts_per_page;
        $max_num_pages = ceil((int) $total_terms / $posts_per_page);
        $max_num_pages = ( $max_num_pages < 1 ) ? 1 : $max_num_pages;
        $taxonomy = $term_name;
        $order = 'asc'; //desc
        $offset = ( $paged > 0 ) ? $posts_per_page * ( $paged - 1 ) : 1;

        // Setup the arguments
        $args = array(
            'taxonomy' => $term_name,
            'offset' => $offset,
            'number' => $posts_per_page,
            /* 'orderby'      => 'count', */
            'order' => $order,
            'hide_empty' => false,
            'parent' => $cTerm_id,
        );

        $taxonomies = get_terms($args);
        $count = 0;
        $output = array();
        $message = '';
        $success = true;
        if (!empty($taxonomies)) {

            if ($term_name == 'ad_cats') {
                $show_term_counts = (isset($adforestAPI['adforest-api-ad-cats-show-count']) && $adforestAPI['adforest-api-ad-cats-show-count']) ? true : false;
            } else if ($term_name == 'ad_country') {
                //$show_term_counts = (isset( $adforestAPI['adforest-api-ad-location-show-count'] ) && $adforestAPI['adforest-api-ad-location-show-count'] ) ? true : false;
            }

            foreach ($taxonomies as $category) {
                $has_children = adforestAPI_terms_has_children($category->term_id, $term_name);
                $imgUrl = adforestAPI_taxonomy_image_url($category->term_id, NULL, TRUE);
                if ($show_term_counts) {
                    $termNameTitle = adforestAPI_convert_uniText($category->name) . ' (' . $category->count . ')';
                } else {
                    $termNameTitle = adforestAPI_convert_uniText($category->name);
                }
                $output[$count] = array(
                    "term_id" => $category->term_id,
                    "name" => $termNameTitle,
                    "has_children" => $has_children,
                    "parent" => $category->parent,
                    "count" => $category->count,
                    "term_img" => $imgUrl,
                );
                $count++;
            }
        } else {
            $success = false;
            $message = __("No Record Found", "adforest-rest-api");
        }

        $nextPaged = $paged + 1;
        $has_next_page = ( $nextPaged <= (int) $max_num_pages ) ? true : false;
        $search_here = __("Search Here", "adforest-rest-api");
        if ($cTerm_id > 0 && !empty($taxonomies)) {
            $term = get_term($cTerm_id, $term_name);
            $page_title = $term->name;
        } else {
            if ($term_name == 'ad_cats') {
                $page_title = __("All Categories", "adforest-rest-api");
                $search_here = __("Search Categories", "adforest-rest-api");
            } else if ($term_name == 'ad_country') {
                $page_title = __("All Locations", "adforest-rest-api");
                $search_here = __("Search Locations", "adforest-rest-api");
            } else {
                $page_title = __("All", "adforest-rest-api");
            }
        }
        $data['page_title'] = $page_title;
        $data['terms'] = $output;
        $data['load_more'] = __("Load More", "adforest-rest-api");
        $data['search_here'] = $search_here;

        $data['pagination'] = array("max_num_pages" => (int) $max_num_pages, "current_page" => (int) $paged, "next_page" => (int) $nextPaged, "increment" => (int) $posts_per_page, "current_no_of_ads" => (int) $total_terms, "has_next_page" => $has_next_page);

        return array('success' => $success, 'data' => $data, 'message' => $message);
    }

}

if (!function_exists('adforestAPI_terms_has_children')) {

    function adforestAPI_terms_has_children($term_id = 0, $taxonomy = '') {
        $children = get_terms(
                array(
                    'child_of' => $term_id,
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                    'fields' => 'ids',
                    'number' => 1,
                )
        );
        return ( isset($children) && count($children) > 0 ) ? true : false;
    }

}

if (!function_exists('adforestAPI_get_term_post_count')) {

    function adforestAPI_get_term_post_count($taxonomy = 'category', $term = '', $args = array()) {
        // Lets first validate and sanitize our parameters, on failure, just return false
        if (!$term)
            return false;

        if ($term !== 'all') {
            if (!is_array($term)) {
                $term = filter_var($term, FILTER_VALIDATE_INT);
            } else {
                $term = filter_var_array($term, FILTER_VALIDATE_INT);
            }
        }

        if ($taxonomy !== 'category') {
            $taxonomy = filter_var($taxonomy, FILTER_SANITIZE_STRING);
            if (!taxonomy_exists($taxonomy))
                return false;
        }

        if ($args) {
            if (!is_array)
                return false;
        }
        // Now that we have come this far, lets continue and wrap it up
        // Set our default args
        $defaults = [
            'posts_per_page' => 1,
            'fields' => 'ids'
        ];

        if ($term !== 'all') {
            $defaults['tax_query'] = [
                [
                    'taxonomy' => $taxonomy,
                    'terms' => $term
                ]
            ];
        }
        $combined_args = wp_parse_args($args, $defaults);
        $combined_args = apply_filters('AdforestAPI_wpml_show_all_posts', $args);
        $q = new WP_Query($combined_args);
        // Return the post count
        return $q->found_posts;
    }

}