<?php

/* -----
  Home Screen Starts Here
  ----- */
add_action('rest_api_init', 'adforestAPI_homescreen_api_hooks_get', 0);

function adforestAPI_homescreen_api_hooks_get() {
    register_rest_route('adforest/v1', '/home/', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'adforestAPI_homeScreen_get',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );
}

if (!function_exists('adforestAPI_homeScreen_get')) {

    function adforestAPI_homeScreen_get() {
        global $adforestAPI;
        $user = wp_get_current_user();
        $user_id = @$user->data->ID;

        $screenTitle = (isset($adforestAPI['sb_home_screen_title']) && $adforestAPI['sb_home_screen_title'] != "" ) ? $adforestAPI['sb_home_screen_title'] : __("Home Screen", "adforest-rest-api");
        $data['page_title'] = $screenTitle;
        $data['field_type_name'] = 'ad_cats1';
        $catData = array();
        $data['ads_position_sorter'] = false;
        $is_show_homeSearch = (isset($adforestAPI['search_section_show']) && $adforestAPI['search_section_show'] ) ? true : false;
        $data['search_section']['is_show'] = $is_show_homeSearch;
        if ($is_show_homeSearch) {
            $data['search_section']['main_title'] = $adforestAPI['search_section_show_title'];
            $data['search_section']['sub_title'] = $adforestAPI['search_section_show_subtitle'];
            $data['search_section']['placeholder'] = $adforestAPI['search_section_show_placeholder'];
            $s_img = (isset($adforestAPI['search_section_show_bg_image']['url']) && $adforestAPI['search_section_show_bg_image']['url'] != "") ? $adforestAPI['search_section_show_bg_image']['url'] : ADFOREST_API_PLUGIN_URL . "images/search-bg-img.png";
            $data['search_section']['image'] = $s_img;
        }

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

        if (isset($adforestAPI['home-screen-sortable-enable']) && $adforestAPI['home-screen-sortable-enable'] && $has_value == true) {
            if (isset($array_sortable) && $array_sortable > 0) {
                $arrays = $array_sortable;
                $positions = array();
                $position_sorter = false;
                foreach ($arrays as $key => $val) {
                    if (isset($val) && $val != "") {
                        $position_sorter = true;
                        $position[] = $key;
                    }
                    if ($key == "cat_icons" && $val != "") {

                        $cat_btn = (isset($adforestAPI['adforest-api-ad-cats-show-text']) && $adforestAPI['adforest-api-ad-cats-show-text'] != "" ) ? $adforestAPI['adforest-api-ad-cats-show-text'] : __("View All Categories", "adforest-rest-api");

                        /* Cats With icons */
                        $data['cat_icons_column'] = (isset($adforestAPI['api_cat_columns']) ) ? $adforestAPI['api_cat_columns'] : 3;
                        $data['cat_icons_column_btn']['is_show'] = (isset($adforestAPI['adforest-api-ad-cats-show-btn']) && $adforestAPI['adforest-api-ad-cats-show-btn'] == true ) ? true : false;
                        $data['cat_icons_column_btn']['text'] = $cat_btn;

                        $data['cat_icons'] = adforestAPI_home_adsLayouts('cat_icons');
                        /* Cats With icons */
                    }
                    if ($key == "featured_ads" && $val != "") {
                        /* Featured Ads Settings Starts Here */
                        $featured = adforestAPI_home_adsLayouts('featured');
                        $data['featured_ads'] = $featured['featured_ads'];
                        $data['is_show_featured'] = $featured['is_show_featured'];
                        $data['featured_position'] = $featured['featured_position'];
                        /* Featured Ads Settings Ends Here */
                    }
                    if ($key == "sliders" && $val != "") {
                        $data['sliders'] = adforestAPI_home_adsLayouts('multi_slider');
                    }
                    if ($key == "latest_ads" && $val != "") {
                        /* Latest Ads Settings Starts Here */
                        $latestData = adforestAPI_home_adsLayouts('latest');
                        $data['is_show_latest'] = $latestData['is_show_latest'];
                        $data['latest_ads'] = $latestData['latest_ads'];
                        /* Latest Ads Settings Ends Here */
                    }
                    if ($key == "nearby" && $val != "") {
                        /* nearby Ads Settings Starts Here */
                        $latestData = adforestAPI_home_adsLayouts('nearby');
                        $data['is_show_nearby'] = $latestData['is_show_nearby'];
                        $data['nearby_ads'] = $latestData['nearby_ads'];
                        /* nearby Ads Settings Ends Here */
                    }

                    if ($key == "cat_locations" && $val != "") {
                        /* Locations Settings */
                        $data['cat_locations_title'] = (isset($adforestAPI['api_location_title']) ) ? $adforestAPI['api_location_title'] : __("Locations", "adforest-rest-api");

                        $location_btn = (isset($adforestAPI['adforest-api-ad-location-show-text']) && $adforestAPI['adforest-api-ad-location-show-text'] != "" ) ? $adforestAPI['adforest-api-ad-location-show-text'] : __("View All Locations", "adforest-rest-api");

                        $data['cat_locations_btn']['is_show'] = (isset($adforestAPI['adforest-api-ad-location-show-btn']) && $adforestAPI['adforest-api-ad-location-show-btn'] == true ) ? true : false;
                        ;
                        $data['cat_locations_btn']['text'] = $location_btn;
                        $data['cat_locations_column'] = (isset($adforestAPI['api_location_columns']) ) ? $adforestAPI['api_location_columns'] : 2;
                        $data['cat_locations_type'] = 'ad_locations';
                        $data['cat_locations'] = adforestAPI_home_adsLayouts('locations');
                    }

                    if ($key == "blogNews" && $val != "") {
                        /* Latest Ads Settings Starts Here */
                        $latestData = adforestAPI_home_adsLayouts('blogNews');
                        $data['is_show_blog'] = $latestData['is_show_blog'];
                        $data['latest_blog'] = $latestData['latest_blog'];
                        /* Latest Ads Settings Ends Here */
                    }
                    /* adforestAPI_home_adsLayouts */
                }
                $data['ads_position_sorter'] = $position_sorter;
                $data['ads_position'] = $position;
            }
        } else {
            /* Featured Ads Settings Starts Here */
            $featured = adforestAPI_home_adsLayouts('featured');
            $data['featured_ads'] = $featured['featured_ads'];
            $data['is_show_featured'] = $featured['is_show_featured'];
            $data['featured_position'] = $featured['featured_position'];
            /* Featured Ads Settings Ends Here */

            /* Latest Ads Settings Starts Here *-/
              $latestData = adforestAPI_home_adsLayouts( 'latest' );
              $data['is_show_latest']  = $latestData['is_show_latest'];
              $data['latest_ads']	   	 = $latestData['latest_ads'];
              /*Latest Ads Settings Ends Here */

            /* Cats With icons */


            $cat_btn = (isset($adforestAPI['adforest-api-ad-cats-show-text']) && $adforestAPI['adforest-api-ad-cats-show-text'] != "" ) ? $adforestAPI['adforest-api-ad-cats-show-text'] : __("View All Categories", "adforest-rest-api");

            $data['cat_icons_column'] = (isset($adforestAPI['api_cat_columns']) ) ? $adforestAPI['api_cat_columns'] : 3;
            $data['cat_icons_column_btn']['is_show'] = (isset($adforestAPI['adforest-api-ad-cats-show-btn']) && $adforestAPI['adforest-api-ad-cats-show-btn'] == true) ? true : false;
            $data['cat_icons_column_btn']['text'] = $cat_btn;

            $data['cat_icons'] = adforestAPI_home_adsLayouts('cat_icons');
            /* Cats With icons */

            /* Locations Settings*-/
              $data['cat_locations_title'] = (isset( $adforestAPI['api_location_title'] ) ) ? $adforestAPI['api_location_title'] : __("Locations", "adforest-rest-api");
              $data['cat_locations_column'] = (isset( $adforestAPI['api_location_columns'] ) ) ? $adforestAPI['api_location_columns'] : 2;
              $data['cat_locations_type']	= 'ad_locations';
              $data['cat_locations']	= adforestAPI_home_adsLayouts( 'locations' );
             * Locations Settings */

            $data['sliders'] = adforestAPI_home_adsLayouts('multi_slider');
        }
        $data['view_all'] = __("View All", "adforest-rest-api");
        $data['menu'] = adforestAPI_appMenu_settings();
        $data['ad_post']['can_post'] = true;
        $data['ad_post']['message'] = __("Your don't any any ads to post or you package has been expired.", "adforest-rest-api");
        $settings = adforestAPI_settings_data($user_id);
        return $response = array('success' => true, 'data' => $data, 'settings' => $settings, 'message' => '');
    }

}


if (!function_exists('adforestAPI_home_adsLayouts')) {

    function adforestAPI_home_adsLayouts($type = '') {
        global $adforestAPI;
        /* Cat icons Starts */
        if ($type == 'cat_icons') {
            $catData = array();
            if (isset($adforestAPI['adforest-api-ad-cats-multi'])) {
                $cats = $adforestAPI['adforest-api-ad-cats-multi'];
                if (isset($cats) && is_array($cats) && count($cats) > 0) {
                    foreach ($cats as $cat) {
                        $term = get_term($cat, 'ad_cats');
                        if (isset($term->term_id) && !empty($term->term_id)) {
                            $name = htmlspecialchars_decode($term->name, ENT_NOQUOTES);
                            $imgUrl = adforestAPI_taxonomy_image_url($cat, NULL, TRUE);
                            $catData[] = array("cat_id" => $term->term_id, "name" => $name, "img" => $imgUrl);
                        }
                    }
                }
            }
            return $catData;
        }
        /* Cat icons ends */
        /* Multi Slider ads options sortable starts */
        if ($type == 'multi_slider') {
            $sliderData = array();
            if (isset($adforestAPI['adforest-api-ad-cats-slider'])) {
                $slider_ad_limit = (isset($adforestAPI['slider_ad_limit']) ) ? $adforestAPI['slider_ad_limit'] : 5;
                $cats = $adforestAPI['adforest-api-ad-cats-slider'];
                foreach ($cats as $cat) {
                    $term = get_term($cat, 'ad_cats');
                    if (isset($term->term_id) && !empty($term->term_id)) {
                        $name = htmlspecialchars_decode($term->name, ENT_NOQUOTES);
                        $adData = adforestApi_catSpecific_ads($cat, $slider_ad_limit, false);
                        if (isset($adData) && count($adData) > 0) {
                            $sliderData[] = array("cat_id" => $term->term_id, "name" => $name, "data" => $adData);
                        }
                    }
                }
            }
            return $sliderData;
        }

        /* Multi Slider ads options sortable ends */
        /* Featured Ads Starts Here */
        if ($type == 'featured') {
            $fads = array();
            $fads['text'] = array();
            $fads['ads'] = array();
            $data = array();
            $data['featured_ads'] = array();
            $data['featured_position'] = (isset($adforestAPI['home_featured_position']) && $adforestAPI['home_featured_position'] != "" ) ? $adforestAPI['home_featured_position'] : "1";
            $data['is_show_featured'] = (isset($adforestAPI['feature_on_home']) && $adforestAPI['feature_on_home'] == 1 ) ? true : false;

            if (isset($adforestAPI['feature_on_home']) && $adforestAPI['feature_on_home'] == 1) {
                $featuredAdsCount = ( $adforestAPI['home_related_posts_count'] != "" ) ? $adforestAPI['home_related_posts_count'] : 5;
                $featuredAdsTitle = ( $adforestAPI['sb_home_ads_title'] != "" ) ? $adforestAPI['sb_home_ads_title'] : __("Featured Ads", "adforest-rest-api");

                $featured_termID = ( isset($json_data['ad_cats1']) && $json_data['ad_cats1'] != "" ) ? $json_data['ad_cats1'] : '';
                $featuredAds = adforestApi_featuredAds_slider('', 'active', '1', $featuredAdsCount, $featured_termID, 'publish');
                if (isset($featuredAds) && count($featuredAds) > 0) {
                    $fads['text'] = $featuredAdsTitle;
                    $fads['ads'] = $featuredAds;
                    $data['is_show_featured'] = true;
                } else {
                    $data['is_show_featured'] = false;
                }

                $data['featured_ads'] = $fads;
            }
            return $data;
        }

        /* Featured ads ends here */
        /* Latest Ads Start Here */
        if ($type == 'latest') {
            /* latest Layout */
            $latest['text'] = array();
            $latest['ads'] = array();
            $data = array();
            $data['latest_ads'] = array();
            $data['is_show_latest'] = (isset($adforestAPI['latest_on_home']) && $adforestAPI['latest_on_home'] == 1 ) ? true : false;
            if (isset($adforestAPI['latest_on_home']) && $adforestAPI['latest_on_home'] == 1) {
                $latestAdsCount = ( $adforestAPI['home_latest_posts_count'] != "" ) ? $adforestAPI['home_latest_posts_count'] : 5;
                $latestAdsAdsTitle = ( $adforestAPI['sb_home_latest_ads_title'] != "" ) ? $adforestAPI['sb_home_latest_ads_title'] : __("Latest Ads", "adforest-rest-api");

                $latest_termID = ( isset($json_data['ad_cats1']) && $json_data['ad_cats1'] != "" ) ? $json_data['ad_cats1'] : '';
                $latestAds = adforestApi_featuredAds_slider('', 'active', '', $latestAdsCount, $latest_termID, 'publish');
                if (isset($latestAds) && count($latestAds) > 0) {
                    $latest['text'] = $latestAdsAdsTitle;
                    $latest['ads'] = $latestAds;
                    $data['is_show_latest'] = true;
                } else {
                    $data['is_show_latest'] = false;
                }

                $data['latest_ads'] = $latest;
            }
            return $data;
        }
        /* Latest Ads Ends Here */

        /* nearby Ads Start Here */
        if ($type == 'nearby') {
            /* latest Layout */
            $latest['text'] = array();
            $latest['ads'] = array();
            $data = array();
            $data['nearby_ads'] = array();
            $data['is_show_nearby'] = (isset($adforestAPI['nearby_on_home']) && $adforestAPI['nearby_on_home'] ) ? true : false;
            if (isset($adforestAPI['nearby_on_home']) && $adforestAPI['nearby_on_home'] == 1) {
                $latestAdsCount = ( $adforestAPI['home_nearby_posts_count'] != "" ) ? $adforestAPI['home_nearby_posts_count'] : 5;
                $latestAdsAdsTitle = ( $adforestAPI['sb_home_nearby_ads_title'] != "" ) ? $adforestAPI['sb_home_nearby_ads_title'] : __("Nearby Ads", "adforest-rest-api");

                $latest_termID = ( isset($json_data['ad_cats1']) && $json_data['ad_cats1'] != "" ) ? $json_data['ad_cats1'] : '';
                $latestAds = adforestApi_featuredAds_slider('', 'active', '', $latestAdsCount, $latest_termID, 'publish', 'nearby');
                if (isset($latestAds) && count($latestAds) > 0) {
                    $latest['text'] = $latestAdsAdsTitle;
                    $latest['ads'] = $latestAds;
                    $data['is_show_nearby'] = true;
                } else {
                    $data['is_show_nearby'] = false;
                }

                $data['nearby_ads'] = $latest;
            }
            return $data;
        }
        /* nearby Ads Ends Here */
        /* Cat locations */
        if ($type == 'locations') {
            $loctData = array();
            if (isset($adforestAPI['adforest-api-ad-loc-multi']) && count($adforestAPI['adforest-api-ad-loc-multi']) > 0) {
                $loctData = array();
                $cats = $adforestAPI['adforest-api-ad-loc-multi'];
                if (count($cats) > 0) {
                    foreach ($cats as $cat) {
                        $term = get_term($cat, 'ad_country');
                        if ($term) {
                            $name = htmlspecialchars_decode($term->name, ENT_NOQUOTES);
                            $imgUrl = adforestAPI_taxonomy_image_url($cat, NULL, TRUE);
                            $count = "($term->count " . __("Ads", "adforest-rest-api") . ")";
                            $loctData[] = array("cat_id" => $term->term_id, "name" => $name, "img" => $imgUrl, "count" => $count);
                        }
                    }
                }
            }
            return $loctData;
        }
        /* Cat locations ends here */
        /* blogNews starts */

        if ($type == 'blogNews') {
            /* latest Layout */
            $blogs['text'] = array();
            $blogs['blogs'] = array();
            $data = array();
            $data['latest_blog'] = array();
            $data['is_show_blog'] = (isset($adforestAPI['posts_blogNews_home']) && $adforestAPI['posts_blogNews_home'] ) ? true : false;
            if (isset($adforestAPI['posts_blogNews_home']) && $adforestAPI['posts_blogNews_home']) {
                $latestPostsCount = ( $adforestAPI['home_blogNews_posts_count'] != "" ) ? $adforestAPI['home_blogNews_posts_count'] : 5;
                $latestPostsAdsTitle = ( $adforestAPI['api_blogNews_title'] != "" ) ? $adforestAPI['api_blogNews_title'] : __("Blog/News", "adforest-rest-api");

                $latest_termID = ( isset($adforestAPI['adforest-api-blogNews-multi']) && $adforestAPI['adforest-api-blogNews-multi'] ) ? $adforestAPI['adforest-api-blogNews-multi'] : array();

                $latestPosts = adforestAPI_blogPosts($latest_termID, $latestPostsCount);
                if (isset($latestPosts) && count($latestPosts) > 0) {
                    $blogs['text'] = $latestPostsAdsTitle;
                    $blogs['blogs'] = $latestPosts;
                    $data['is_show_blog'] = true;
                } else {
                    $data['is_show_blog'] = false;
                }
                $data['latest_blog'] = $blogs;
            }

            return $data;
        }
        /* blogNews ends */
    }

}

if (!function_exists('adforestAPI_blogPosts')) {

    function adforestAPI_blogPosts($cats = array(), $latestPostsCount = 5) {
        $trmArry = array();
        if (count($cats) > 0) {
            $trmArry = $cats; //array('taxonomy' => 'category', 'field' => 'id',  'terms' => $cats);
        }
        $posts_per_page = $latestPostsCount;
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'order' => 'DESC',
            'orderby' => 'date',
            'cat' => $trmArry
        );

        $message = '';
        $posts = new WP_Query($args);
        $data = array();
        $arr = array();
        $post_data = array();
        if ($posts->have_posts()) {

            while ($posts->have_posts()) {
                $posts->the_post();
                $post_id = get_the_ID();
                $arr['post_id'] = $post_id;
                $arr['title'] = get_the_title();
                $arr['date'] = get_the_date("", $post_id);

                $list = array();
                $term_lists = wp_get_post_terms($post_id, 'category', array('fields' => 'all'));
                foreach ($term_lists as $term_list)
                    $list[] = array('id' => $term_list->term_id, 'name' => $term_list->name);
                $arr['cats'] = $list;
                $image = get_the_post_thumbnail_url($post_id, 'medium');
                if (!$image)
                    $image = '';

                $arr['has_image'] = ( $image ) ? true : false;
                $arr['image'] = $image;
                $comments = wp_count_comments($post_id);
                $arr['comments'] = $comments->approved;
                $arr['read_more'] = __("Read More", "adforest-rest-api");
                $post_data[] = $arr;
            }
            /* Restore original Post Data */
            wp_reset_postdata();
        }
        return $post_data;
    }

}
if (!function_exists('adforestAPI_appMenu_settings')) {

    function adforestAPI_appMenu_settings() {
        global $adforestAPI;
        $is_show_message_count = ( isset($adforestAPI['api-menu-message-count']) && $adforestAPI['api-menu-message-count'] ) ? true : false;
        $number_of_messages = '';
        if ($is_show_message_count) {
            $number_of_messages = ' (' . adforestAPI_getUnreadMessageCount() . ')';
        }
        $data_menu = array();

        $data_menu['menu_is_show_packages'] = ( isset($adforestAPI['api_woo_products_multi']) && $adforestAPI['api_woo_products_multi'] && count($adforestAPI['api_woo_products_multi']) > 0 ) ? true : false;


        $data_menu['is_show_menu']['blog'] = (isset($adforestAPI['api-menu-hide-blog-menu']) && $adforestAPI['api-menu-hide-blog-menu']) ? true : false;
        $data_menu['is_show_menu']['message'] = (isset($adforestAPI['api-menu-hide-message-menu']) && $adforestAPI['api-menu-hide-message-menu']) ? true : false;
        $data_menu['is_show_menu']['package'] = (isset($adforestAPI['api-menu-hide-package-menu']) && $adforestAPI['api-menu-hide-package-menu']) ? true : false;
        $data_menu['is_show_menu']['shop'] = (isset($adforestAPI['shop-show-menu']) && $adforestAPI['shop-show-menu']) ? true : false;
        $data_menu['is_show_menu']['sellers'] = (isset($adforestAPI['sellers-show-menu']) && $adforestAPI['sellers-show-menu']) ? true : false;
        $data_menu['is_show_menu']['settings'] = (isset($adforestAPI['settings-show-menu']) && $adforestAPI['settings-show-menu']) ? true : false;
        $data_menu['is_show_menu']['is_wpml_active'] = (isset($adforestAPI['sb_api_wpml_anable']) && $adforestAPI['sb_api_wpml_anable']) ? true : false;
        $page_icon_url = '';
        if (isset($adforestAPI['app_settings_pages_default_icon']['url']) && $adforestAPI['app_settings_pages_default_icon']['url'] != "") {
            $page_icon_url = $adforestAPI['app_settings_pages_default_icon']['url'];
        }

        if (isset($adforestAPI['api-sortable-app-switch']) && $adforestAPI['api-sortable-app-switch']) {
            $menus = $adforestAPI['api-sortable-app-menu'];
            
            
            
            foreach ($menus as $m_key => $m_val) {
                if ($m_key != "pages") {
                    $append_text = ( $m_key == "messages" ) ? $number_of_messages : '';
                    $data_menu[$m_key] = $m_val . $append_text;
                } else {
                    $pages = '';
                    $data_menu['submenu']['has_page'] = false;
                    $data_menu['submenu']['title'] = $m_val;

                    $count = 1;
                    $subMenus = array();

                    if (isset($adforestAPI['app_settings_pages'])) {
                        $pages = $adforestAPI['app_settings_pages'];
                        $data_menu['submenu']['has_page'] = true;
                        foreach ($pages as $page) {

                            if (function_exists('icl_object_id')) {
                                $my_current_lang = apply_filters('wpml_current_language', NULL); //Store current language    
                                $lang_page_id = icl_object_id($page, 'page', false, $my_current_lang);
                                $page = $lang_page_id;
                            }

                            $title = adforestAPI_convert_uniText(get_the_title($page));
                            $get_the_permalink = get_the_permalink($page);
                            $db_value = get_post_meta($page, 'app_page_icons_' . 'app-page-menu-icon', true);
                            $page_icon = ( $db_value != "" ) ? $db_value : $page_icon_url;
                            $subMenus[] = array("page_id" => (int) $page, "page_title" => $title, "icon" => $count, "url" => $page_icon, "type" => "simple", "page_url" => $get_the_permalink);
                            /* 0 help,1 about ,2 terms ,3 page */
                            $count++;
                        }

                        $data_menu['submenu']['pages'] = $subMenus;
                    }

                    if (isset($adforestAPI['app_settings_pages_webview'])) {
                        $pages = $adforestAPI['app_settings_pages_webview'];
                        $data_menu['submenu']['has_page'] = true;
                        foreach ($pages as $page) {

                            if (function_exists('icl_object_id')) {
                                $my_current_lang = apply_filters('wpml_current_language', NULL); //Store current language    
                                $lang_page_id = icl_object_id($page, 'page', false, $my_current_lang);
                                $page = $lang_page_id;
                            }


                            $title = adforestAPI_convert_uniText(get_the_title($page));
                            $get_the_permalink = get_the_permalink($page);
                            $db_value = get_post_meta($page, 'app_page_icons_' . 'app-page-menu-icon', true);
                            $page_icon = ( $db_value != "" ) ? $db_value : $page_icon_url;
                            $subMenus[] = array("page_id" => (int) $page, "page_title" => $title, "icon" => $count, "url" => $page_icon, "type" => "webview", "page_url" => $get_the_permalink);
                            /* 0 help,1 about ,2 terms ,3 page */
                            $count++;
                        }

                        $data_menu['submenu']['pages'] = $subMenus;
                    }
                }
            }
        } else {


            $data_menu['home'] = __("Home", "adforest-rest-api");
            $data_menu['profile'] = __("Profile", "adforest-rest-api");
            $data_menu['search'] = __("Advance Search", "adforest-rest-api");
            $data_menu['messages'] = __("Messages", "adforest-rest-api") . $number_of_messages;
            $data_menu['packages'] = __("Packages", "adforest-rest-api");
            $data_menu['my_ads'] = __("My Ads", "adforest-rest-api");
            $data_menu['inactive_ads'] = __("Inactive Ads", "adforest-rest-api");
            $data_menu['featured_ads'] = __("Featured Ads", "adforest-rest-api");
            $data_menu['fav_ads'] = __("Fav Ads", "adforest-rest-api");
            $data_menu['shop'] = __("Shop", "adforest-rest-api");
            $data_menu['sellers'] = __("Sellers", "adforest-rest-api");
            $data_menu['wpml_menu_text'] = isset($adforestAPI['wpml_menu_text']) && !empty($adforestAPI['wpml_menu_text']) ? $adforestAPI['wpml_menu_text'] : 'Languages';

            $pages = '';
            $data_menu['submenu']['has_page'] = false;
            $data_menu['submenu']['title'] = __("Pages", "adforest-rest-api");

            $count = 1;
            $subMenus = array();

            if (isset($adforestAPI['app_settings_pages'])) {
                $pages = $adforestAPI['app_settings_pages'];
                $data_menu['submenu']['has_page'] = true;
                foreach ($pages as $page) {

                    if (function_exists('icl_object_id')) {
                        $my_current_lang = apply_filters('wpml_current_language', NULL); //Store current language    
                        $lang_page_id = icl_object_id($page, 'page', false, $my_current_lang);
                        $page = $lang_page_id;
                    }

                    $title = adforestAPI_convert_uniText(get_the_title($page));
                    $get_the_permalink = get_the_permalink($page);
                    $db_value = get_post_meta($page, 'app_page_icons_' . 'app-page-menu-icon', true);
                    $page_icon = ( $db_value != "" ) ? $db_value : $page_icon_url;
                    $subMenus[] = array("page_id" => (int) $page, "page_title" => $title, "icon" => $count, "url" => $page_icon, "type" => "simple", "page_url" => $get_the_permalink);
                    /* 0 help,1 about ,2 terms ,3 page */
                    $count++;
                }

                $data_menu['submenu']['pages'] = $subMenus;
            }

            if (isset($adforestAPI['app_settings_pages_webview'])) {
                $pages = $adforestAPI['app_settings_pages_webview'];
                $data_menu['submenu']['has_page'] = true;
                foreach ($pages as $page) {

                    if (function_exists('icl_object_id')) {
                        $my_current_lang = apply_filters('wpml_current_language', NULL); //Store current language    
                        $lang_page_id = icl_object_id($page, 'page', false, $my_current_lang);
                        $page = $lang_page_id;
                    }
                    $title = adforestAPI_convert_uniText(get_the_title($page));
                    $get_the_permalink = get_the_permalink($page);
                    $db_value = get_post_meta($page, 'app_page_icons_' . 'app-page-menu-icon', true);
                    $page_icon = ( $db_value != "" ) ? $db_value : $page_icon_url;
                    $subMenus[] = array("page_id" => (int) $page, "page_title" => $title, "icon" => $count, "url" => $page_icon, "type" => "webview", "page_url" => $get_the_permalink);
                    /* 0 help,1 about ,2 terms ,3 page */
                    $count++;
                }

                $data_menu['submenu']['pages'] = $subMenus;
            }
            $data_menu['others'] = __("Others", "adforest-rest-api");
            $data_menu['blog'] = __("Blog", "adforest-rest-api");
            $data_menu['app_settings'] = __("Settings", "adforest-rest-api");
            $data_menu['logout'] = __("Logout", "adforest-rest-api");
            $data_menu['login'] = __("Login", "adforest-rest-api");
            $data_menu['register'] = __("Register", "adforest-rest-api");
        }

        $dMenu = array();
        $remove_from_menu = array('menu_is_show_packages', 'is_show_menu', 'submenu', 'others', 'blog', 'logout', 'login', 'register', 'app_settings');
        foreach ($data_menu as $key => $val) {
            if (in_array($key, $remove_from_menu)) {
                continue;
            }

            if ($key == "packages") {
                if (isset($adforestAPI['api_woo_products_multi']) && $adforestAPI['api_woo_products_multi'] && count($adforestAPI['api_woo_products_multi']) > 0) {
                    
                } else {
                    continue;
                }
                if (isset($adforestAPI['api-menu-hide-package-menu']) && $adforestAPI['api-menu-hide-package-menu']) {
                    
                } else {
                    continue;
                }
            }
            if ($key == "shop") {
                if (isset($adforestAPI['shop-show-menu']) && $adforestAPI['shop-show-menu']) {
                    
                } else {
                    continue;
                }
            }
            if ($key == "blog") {
                if (isset($adforestAPI['api-menu-hide-blog-menu']) && $adforestAPI['api-menu-hide-blog-menu']) {
                    
                } else {
                    continue;
                }
            }
            if ($key == "messages") {
                if (isset($adforestAPI['api-menu-hide-message-menu']) && $adforestAPI['api-menu-hide-message-menu']) {
                    
                } else {
                    continue;
                }
            }
            if ($key == "sellers") {
                if (isset($adforestAPI['sellers-show-menu']) && $adforestAPI['sellers-show-menu']) {
                    
                } else {
                    continue;
                }
            }
            if ($key == "app_settings") {
                if (isset($adforestAPI['settings-show-menu']) && $adforestAPI['settings-show-menu']) {
                    
                } else {
                    continue;
                }
            }
            //$dMenu["$key"] = $val;
            $dMenu[] = $val;
            $dkey[] = strtolower($key);
            $dIcns[] = $key;
        }
        $data_menu['dynamic_menu']['array'] = $dMenu;
        $data_menu['dynamic_menu']['keys'] = $dkey;
        $data_menu['dynamic_menu']['icons'] = $dIcns;
        return $data_menu;
    }

}

if (!function_exists('adforestAPI_settings_data')) {

    function adforestAPI_settings_data($user_id) {
        global $adforestAPI;
        /* Some App Keys From Theme Options */
        $data['appKey']['stripe'] = (isset($adforestAPI['appKey_stripeKey']) ) ? $adforestAPI['appKey_stripeKey'] : '';
        $data['appKey']['paypal'] = (isset($adforestAPI['appKey_paypalKey']) ) ? $adforestAPI['appKey_paypalKey'] : '';
        $data['appKey']['youtube'] = (isset($adforestAPI['appKey_youtubeKey']) ) ? $adforestAPI['appKey_youtubeKey'] : '';
        $data['ads']['show'] = false;
        $data['appKey']['payu']['mode'] = (isset($adforestAPI['appKey_payuMode']) ) ? $adforestAPI['appKey_payuMode'] : 'sandbox';
        $data['appKey']['payu']['key'] = (isset($adforestAPI['appKey_payumarchantKey']) ) ? $adforestAPI['appKey_payumarchantKey'] : '';
        $data['appKey']['payu']['salt'] = (isset($adforestAPI['payu_salt_id']) ) ? $adforestAPI['payu_salt_id'] : '';

        if (isset($adforestAPI['api_ad_show']) && $adforestAPI['api_ad_show'] == true) {
            $data['ads']['show'] = true;
            $data['ads']['type'] = 'banner';
            $is_show_banner = (isset($adforestAPI['api_ad_type_banner']) && $adforestAPI['api_ad_type_banner']) ? true : false;
            $data['ads']['is_show_banner'] = $is_show_banner;
            if ($is_show_banner) {
                $ad_position = (isset($adforestAPI['api_ad_position']) && $adforestAPI['api_ad_position'] != "") ? $adforestAPI['api_ad_position'] : 'top';
                $data['ads']['position'] = $ad_position;
                /* For New Version > 1.5.0 */

                $api_ad_key_banner = ( ADFOREST_API_REQUEST_FROM == 'ios' ) ? $adforestAPI['api_ad_key_banner_ios'] : $adforestAPI['api_ad_key_banner'];
                $data['ads']['banner_id'] = $api_ad_key_banner;
            }

            $is_show_initial = (isset($adforestAPI['api_ad_type_initial']) && $adforestAPI['api_ad_type_initial']) ? true : false;

            $api_ad_key_var = ( ADFOREST_API_REQUEST_FROM == 'ios' ) ? $adforestAPI['api_ad_key_ios'] : $adforestAPI['api_ad_key'];
            $data['ads']['is_show_initial'] = $is_show_initial;
            if ($is_show_initial) {
                $data['ads']['time_initial'] = ($adforestAPI['api_ad_time_initial'] != "" ) ? $adforestAPI['api_ad_time_initial'] : 30;
                $data['ads']['time'] = ($adforestAPI['api_ad_time'] != "" ) ? $adforestAPI['api_ad_time'] : 30;
                /* For New Version > 1.5.0 */
                $data['ads']['interstital_id'] = $api_ad_key_var;
            }
            $data['ads']['ad_id'] = $api_ad_key_var;
        }

        $data['analytics']['show'] = false;
        if (isset($adforestAPI['api_analytics_show']) && $adforestAPI['api_analytics_show'] == true) {
            $data['analytics']['show'] = true;
            $data['analytics']['id'] = ($adforestAPI['api_analytics_id'] != "" ) ? $adforestAPI['api_analytics_id'] : '';
        }
        //$f_reg_id = '';
        $firebase_meta_key = ( ADFOREST_API_REQUEST_FROM == 'ios' ) ? '_sb_user_firebase_id_ios' : '_sb_user_firebase_id';
        $f_reg_id = get_user_meta($user_id, $firebase_meta_key, true);
        $data['firebase']['reg_id'] = ( $f_reg_id != "" ) ? $f_reg_id : '';
        return $data;
    }

}

add_action('rest_api_init', 'adforestAPI_homescreen_api_hooks_post', 0);

function adforestAPI_homescreen_api_hooks_post() {
    register_rest_route('adforest/v1', '/home/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_homeScreen_post',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );
}

if (!function_exists('adforestAPI_homeScreen_post')) {

    function adforestAPI_homeScreen_post($request) {
        $user = wp_get_current_user();
        $user_id = @$user->data->ID;
        $json_data = $request->get_json_params();
        $firebase_id = (isset($json_data['firebase_id'])) ? $json_data['firebase_id'] : '';
        $firebase_meta_key = ( ADFOREST_API_REQUEST_FROM == 'ios' ) ? '_sb_user_firebase_id_ios' : '_sb_user_firebase_id';

        $isUpdated = update_user_meta($user_id, $firebase_meta_key, $firebase_id);
        $f_reg_id = get_user_meta($user_id, $firebase_meta_key, true);
        $data['firebase_reg_id'] = ( $f_reg_id != "" ) ? $f_reg_id : '';
        $data['user_id'] = $user_id;
        return $response = array('success' => true, 'data' => $data, 'message' => '');
    }

}

add_action('rest_api_init', 'adforestAPI_page_api_hooks_get', 0);

function adforestAPI_page_api_hooks_get() {
    register_rest_route('adforest/v1', '/page/', array(
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'adforestAPI_page_get',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );

    register_rest_route('adforest/v1', '/page/', array(
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'adforestAPI_page_get',
        'permission_callback' => function () {
            return adforestAPI_basic_auth();
        },
            )
    );
}

if (!function_exists('adforestAPI_page_get')) {

    function adforestAPI_page_get($request) {
        global $sitepress;
        $json_data = $request->get_json_params();
        $page_id = (isset($json_data['page_id']) ) ? $json_data['page_id'] : '';
        if (function_exists('icl_object_id')) {
            $my_current_lang = apply_filters('wpml_current_language', NULL); //Store current language    
            $lang_page_id = icl_object_id($page_id, 'page', false, $my_current_lang);
            $page_id = $lang_page_id;
        }
        $post_content = get_post($page_id);
        $content = '<span style="line-height:24px;">' . wpautop($post_content->post_content) . '</span>';
        //$content = wpautop($post_content->post_content);
        $data['page_title'] = adforestAPI_convert_uniText(get_the_title($page_id));
        $data['page_content'] = do_shortcode($content);



        return $response = array('success' => true, 'data' => $data, 'message' => '');
    }

}