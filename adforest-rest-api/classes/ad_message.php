<?php
/*-----
	Ad Messages Inbox
-----*/
add_action( 'rest_api_init', 'adforestAPI_messages_inbox_api_hooks_get', 0 );
function adforestAPI_messages_inbox_api_hooks_get() {

    register_rest_route( 'adforest/v1', '/message/inbox/',
        array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => 'adforestAPI_messages_inbox_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );
    register_rest_route( 'adforest/v1', '/message/inbox/',
        array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => 'adforestAPI_messages_inbox_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );	
}

if (!function_exists('adforestAPI_has_ads_messages'))
{
	function adforestAPI_has_ads_messages( $where ) {
		$where .= ' AND comment_count > 0 ';
		return $where;
	}
}

if (!function_exists('adforestAPI_messages_inbox_get'))
{
	function adforestAPI_messages_inbox_get( $request )
	{
		
		$verifed_phone_number = adforestAPI_check_if_phoneVerified();	
        if ($verifed_phone_number){ 
			$message2  = __("Please verify your phone number to send message.", "adforest-rest-api");
			return  array( 'success' => false, 'data' => '', 'message'  => $message2  );	
		}		
		$json_data = $request->get_json_params();
		$receiver_id = (isset( $json_data['receiver_id'] ) && $json_data['receiver_id'] != "" ) ? $json_data['receiver_id'] : '';		
		global $adforestAPI;/*For Redux*/
		global $wpdb;
		$user = wp_get_current_user();	
		$user_id = @$user->data->ID;	
		/*Offers on my ads starts */
		
		if ( get_query_var( 'paged' ) ) { $paged = get_query_var( 'paged' ); } else if ( isset( $json_data['page_number'] ) ) { $paged = $json_data['page_number']; } else { $paged = 1; }		
		
		$posts_per_page = get_option( 'posts_per_page' );
		$args	=	array( 'post_type' => 'ad_post', 'author' => $user_id, 'post_status' => 'publish', 'posts_per_page' => get_option( 'posts_per_page' ), 'paged' => $paged, 'order'=> 'DESC', 'orderby' => 'date','adforestAPI_post_has_comments' => array(
        'comment_type' => 'ad_post',
        'comment_status' => '1'
    ) );
	
		add_filter( 'posts_where', 'adforestAPI_has_ads_messages' );
		$ads = new WP_Query( $args );
		$myOfferAds = array();
		if ( $ads->have_posts() )
		{
			while ( $ads->have_posts() )
			{
				$ads->the_post();
				$ad_id	=	get_the_ID();				
				$args = array( 'number' => '1', 'post_id' => $ad_id, 'post_type' => 'ad_post' );
				$comments = get_comments($args);	
							
				//if(count($comments) > 0 ){
					$offerAds['ad_id'] 		= 	$ad_id;
					$offerAds['message_ad_title'] 	= 	esc_html( adforestAPI_convert_uniText(get_the_title( $ad_id ) ));
					$offerAds['message_ad_img'] 	= 	adforestAPI_get_ad_image($ad_id, 1, 'thumb');				
					$is_unread_msgs = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->commentmeta WHERE comment_id = '".get_current_user_id()."' AND meta_value = '0' AND meta_key like '".$ad_id."_%' "  );
					$offerAds['message_read_status'] 	= 	( $is_unread_msgs > 0 ) ? false : true;
					$myOfferAds[] = $offerAds;
				//}
			}
		}
		// Don't filter future queries.
		remove_filter( 'posts_where', 'adforestAPI_has_ads_messages' );
		$data['received_offers']['items'] = $myOfferAds;
		/*Offers on my ads ends */
		$data['title']['main']    = __("Messages", "adforest-rest-api");
		$data['title']['sent']    = __("Sent Offers", "adforest-rest-api");
		$data['title']['receive'] = __("Offers on Ads", "adforest-rest-api");		
		$nextPaged = $paged + 1;
		$has_next_page = ( $nextPaged <= (int)$ads->max_num_pages ) ? true : false;
	
	$data['pagination'] = array("max_num_pages" => (int)$ads->max_num_pages,"current_page" => (int)$paged, "next_page" => (int)$nextPaged, "increment" => (int)$posts_per_page , "current_no_of_ads" =>  (int)count($ads->posts), "has_next_page" => $has_next_page );		
		
		return $response = array( 'success' => true, 'data' => $data, 'message'  => '');
	}
}	
/*-----
	Ad Messages Main
-----*/
add_action( 'rest_api_init', 'adforestAPI_messages_api_hooks_get', 0 );
function adforestAPI_messages_api_hooks_get() {
    register_rest_route( 'adforest/v1', '/message/',
        array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => 'adforestAPI_messages_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );
    register_rest_route( 'adforest/v1', '/message_post/',
        array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => 'adforestAPI_messages_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );	
}

if (!function_exists('adforestAPI_messages_get'))
{
	function adforestAPI_messages_get( $request )
	{
		
			
		$json_data = $request->get_json_params();
		$receiver_id = (isset( $json_data['receiver_id'] ) && $json_data['receiver_id'] != "" ) ? $json_data['receiver_id'] : '';		
		$user = wp_get_current_user();	
		$user_id = @$user->data->ID;	
		
		global $adforestAPI;/*For Redux*/
		global $wpdb;
		
		if ( get_query_var( 'paged' ) ) { $paged = get_query_var( 'paged' ); } else if ( isset( $json_data['page_number'] ) ) { $paged = $json_data['page_number']; } else { $paged = 1; }	
			
		$posts_per_page = get_option( 'posts_per_page' );
		$start = ($paged-1) * $posts_per_page;
		
		$rows = $wpdb->get_results(   "SELECT comment_ID FROM $wpdb->comments WHERE comment_type = 'ad_post' AND user_id = '$user_id' AND comment_parent = '$user_id' GROUP BY comment_post_ID ORDER BY comment_ID DESC" );
		
		$total_posts = $wpdb->num_rows; 
		$max_num_pages = ceil($total_posts/$posts_per_page);
		$max_num_pages = ( $max_num_pages < 1 ) ? 1 : $max_num_pages;
		
		$rows = $wpdb->get_results(   "SELECT * FROM $wpdb->comments WHERE comment_type = 'ad_post' AND user_id = '$user_id' AND comment_parent = '$user_id' GROUP BY comment_post_ID ORDER BY comment_ID DESC LIMIT $start, $posts_per_page" );		
		
	  $message = array();
	  $sentMessageData = array();
  	  foreach( $rows as $row )
	  {
				$ad_id 								=  $row->comment_post_ID;		 
				$message_receiver_id 				=  get_post_field( 'post_author', $row->comment_post_ID );
				$comment_author						=	@get_userdata( $message_receiver_id );
				$msg_status							=	get_comment_meta( $user_id, $ad_id ."_" .$message_receiver_id  , true );
				$msg_status_r = ( (int)$msg_status == 0 &&  $msg_status != "") ? false : true;
				$message['ad_id'] 					= $ad_id;
				$message['message_author_name']		= @$comment_author->display_name;
				$message['message_ad_img'] 			= adforestAPI_get_ad_image($ad_id, 1, 'thumb');	
				$message['message_ad_title'] 		= esc_html( adforestAPI_convert_uniText(get_the_title( $ad_id )) );
				$message['message_read_status'] 	= $msg_status_r;
				$message['message_sender_id'] 		= $user_id;
				$message['message_receiver_id'] 	= $message_receiver_id;
				$message['message_date'] 			= $row->comment_date;
				$sentMessageData[] = $message;
	  }
		$data['sent_offers']['items'] = $sentMessageData;
		/*Messgae sent offer ends */
		$data['title']['main'] = __("Messages", "adforest-rest-api");
		$data['title']['sent'] = __("Sent Offers", "adforest-rest-api");
		$data['title']['receive'] = __("Offers on Ads", "adforest-rest-api");
		$nextPaged = $paged + 1;
		$has_next_page = ( $nextPaged <= (int)$max_num_pages ) ? true : false;
	$data['pagination'] = array("max_num_pages" => (int)$max_num_pages, "current_page" => (int)$paged, "next_page" => (int)$nextPaged, "increment" => (int)$posts_per_page , "current_no_of_ads" =>  (int)($total_posts), "has_next_page" => $has_next_page );	

		return $response = array( 'success' => true, 'data' => $data, 'message'  => $message2  );
		
	}
}
/*-----
	Ad Messages Get offers on ads
-----*/
add_action( 'rest_api_init', 'adforestAPI_messages_offers_api_hooks_get', 0 );
function adforestAPI_messages_offers_api_hooks_get() {
    register_rest_route( 'adforest/v1', '/message/offers/',
        array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => 'adforestAPI_messages_offers_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );
	
    register_rest_route( 'adforest/v1', '/message/offers/',
        array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => 'adforestAPI_messages_offers_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );
}

if (!function_exists('adforestAPI_messages_offers_get'))
{
	function adforestAPI_messages_offers_get( $request )
	{
		$json_data = $request->get_json_params();		
		$ad_id   = (isset( $json_data['ad_id'] ) && $json_data['ad_id'] != "" ) ? $json_data['ad_id'] : '';
		$user = wp_get_current_user();	
		$user_id = $user->data->ID;	
	  	global $wpdb;
		if ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		} else if ( isset( $json_data['page_number'] ) ) {
			// This will occur if on front page.
			$paged = $json_data['page_number'];
		} else {
			$paged = 1;
		}		
		$posts_per_page = get_option( 'posts_per_page' );
		$start = ($paged-1) * $posts_per_page;
		
		$rows = $wpdb->get_results( "SELECT comment_author, user_id, comment_date FROM $wpdb->comments WHERE comment_post_ID = '$ad_id'  GROUP BY user_id ORDER BY MAX(comment_date) DESC" );
		
		$total_posts = $wpdb->num_rows; 
		$max_num_pages = ceil($total_posts/$posts_per_page);
	  
		$rows = $wpdb->get_results( "SELECT comment_author, user_id, comment_date FROM $wpdb->comments WHERE comment_post_ID = '$ad_id'  GROUP BY user_id ORDER BY MAX(comment_date) DESC LIMIT $start, $posts_per_page" );		
		
		$message = array();
		$myOfferAds = array();
			$success = false;
			if( count( $rows ) > 0 ){
				$success = true;
				foreach( $rows as $r )
				{
					if( $user_id == $r->user_id ) continue;
					$msg_status	=	get_comment_meta( get_current_user_id(), $ad_id."_" . $r->user_id, true );
					$message['ad_id'] 			= 	$ad_id;
					$message['message_author_name']		= 	$r->comment_author;
					$message['message_ad_img'] 			= 	adforestAPI_user_dp( $r->user_id);
					$message['message_ad_title'] 		= 	esc_html( adforestAPI_convert_uniText(get_the_title( $ad_id ) ) );
					$message['message_read_status'] 	= 	( $msg_status == 0 || $msg_status == '0' ) ? false : true;
					$message['message_sender_id'] 		= 	$r->user_id;	
					$message['message_receiver_id'] 	= 	$user_id;
					$message['message_date'] 			= 	$r->comment_date;
					$myOfferAds[] = $message;
				}
			}
			$data['received_offers']['items'] = $myOfferAds;
			$nextPaged = $paged + 1;
			$has_next_page = ( $nextPaged <= (int)$max_num_pages ) ? true : false;
		$data['pagination'] = array("max_num_pages" => (int)$max_num_pages, "current_page" => (int)$paged, "next_page" => (int)$nextPaged, "increment" => (int)$posts_per_page , "current_no_of_ads" =>  (int)count((array)$total_posts), "has_next_page" => $has_next_page );	
			$extra['page_title'] = esc_html( get_the_title( $ad_id ) );
			$message = ( $success == false ) ? __("No Message Found", "adforest-rest-api") : '';
			return $response = array( 'success' => $success, 'data' => $data, 'message'  => $message, "extra" => $extra  );	
		}	
}
/*-----
	Ad Messages Users Chat
-----*/
add_action( 'rest_api_init', 'adforestAPI_messages_chat_api_hooks_get', 0 );
function adforestAPI_messages_chat_api_hooks_get() {
    register_rest_route( 'adforest/v1', '/message/chat/',
        array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => 'adforestAPI_messages_chat_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );

    register_rest_route( 'adforest/v1', '/message/chat/',
        array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => 'adforestAPI_messages_chat_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );		
    register_rest_route( 'adforest/v1', '/message/chat/post/',
        array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => 'adforestAPI_messages_chat_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );		
}

if (!function_exists('adforestAPI_messages_chat_get'))
{
	function adforestAPI_messages_chat_get( $request )
	{
		
	 
		
		$json_data = $request->get_json_params();		
		$ad_id   		= (isset( $json_data['ad_id'] ) && $json_data['ad_id'] != "" ) ? $json_data['ad_id'] : '';
		$sender_id     	= (isset( $json_data['sender_id'] ) && $json_data['ad_id'] != "" ) ? (int)$json_data['sender_id'] : '';
		$receiver_id   	= (isset( $json_data['receiver_id'] ) && $json_data['receiver_id'] != "" ) ? (int)$json_data['receiver_id'] : '';
		$type   		= (isset( $json_data['type'] ) && $json_data['type'] != "" ) ? $json_data['type'] : 'sent';		
		$message   		= (isset( $json_data['message'] ) && $json_data['message'] != "" ) ? $json_data['message'] : '';
		$message_by_user  = $message;
		
		
			
			
			
		$user = wp_get_current_user();	
		$user_id = (int)$user->data->ID;	
		$authors	=	array( $sender_id, $user_id );
		if ( get_query_var( 'paged' ) ) { $paged = get_query_var( 'paged' ); } else if ( isset( $json_data['page_number'] ) ) { $paged = $json_data['page_number']; } else { $paged = 1; }	
			
		/*get_option( 'posts_per_page' );*/	
		$posts_per_page = 10;
		$start = ($paged-1) * $posts_per_page;
		if( $type == 'sent' )
		{
			$authors	=	array( $receiver_id, $user_id );
			$queryID = $user_id;
		}
		else
		{
			$authors	=	array( $sender_id, $user_id );
			$queryID = $sender_id;			
		}
		
		$message2 = '';
		$verifed_phone_number = adforestAPI_check_if_phoneVerified();	
		
			if( $ad_id != "" && $sender_id != "" && $receiver_id != "" &&  $message != "" )
			{
				if (function_exists('adforestAPI_add_messages_get')){
					if($verifed_phone_number == false){
						$message2 = adforestAPI_add_messages_get( $ad_id, $queryID,  $sender_id, $receiver_id,$type, $message );
					}
				}
			}
	
		
		$cArgs = array( 'author__in' => $authors, 'post_id' => $ad_id, 'parent' => $queryID, 'orderby' => 'comment_date', 'order' => 'DESC', );
		$commentsData	=	get_comments( $cArgs );	
		$total_posts = count( $commentsData ); 
		$max_num_pages = ceil($total_posts/$posts_per_page);					
		$args = array(
						'author__in' => $authors,
						'post_id' => $ad_id,
						'parent' => $queryID,
						'orderby' => 'comment_date',
						'order' => 'DESC',
						'paged' => $paged,
						'offset' => $start,
						'number' => $posts_per_page,
					);
						
		$comments	=	get_comments( $args );
		$chat = array();
		$chatHistory = array();
		$success = false;
		
		$get_other_user_name = ( $type == 'sent' ) ? $receiver_id : $sender_id;
		$author_obj = @get_user_by('id', $get_other_user_name);
		$page_title = ($author_obj) ? $author_obj->display_name : __("Chat Box", "adforest-rest-api");
		$data['page_title'] 	= $page_title;
		$data['ad_title'] 		= get_the_title($ad_id);
		$data['ad_img']   		= adforestAPI_get_ad_image($ad_id, 1, 'thumb');
		$data['ad_date']  		= get_the_date("", $ad_id);
		$sender_img    			= adforestAPI_user_dp( $sender_id);
		$receiver_img  			= adforestAPI_user_dp( $receiver_id);
		$data['ad_price']	   	= adforestAPI_get_price( '', $ad_id );
		/*Add Read Status Here Starts*/	
		update_comment_meta( get_current_user_id(), $ad_id."_".$get_other_user_name, 1 );
		/*Add Read Status Here Ends*/	
		if( count( $comments ) > 0 )
		{
			$success = true;
			foreach( $comments as $comment)
			{
				if( $type == 'sent' )
				{
					$messageType 	= ( $comment->comment_parent != $comment->user_id ) ? 'reply' : 'message';						
				}
				else
				{
					$messageType 	= ( $comment->comment_parent != $comment->user_id ) ? 'message' : 'reply';
				}
				$chat['img'] 	= ( $comment->comment_parent != $comment->user_id ) ? $receiver_img : $sender_img;
				$chat['id'] 	= $comment->comment_ID;
				$chat['ad_id'] 	= $comment->comment_post_ID;
				$chat['text'] 	= $comment->comment_content;
				$chat['date'] 	= adforestAPI_timeago( $comment->comment_date );
				$chat['type'] 	= $messageType;	
				$chatHistory[] 	= $chat;				
			}
		}
		$data['chat'] = $chatHistory;
		$data['is_typing'] = __("is typing", "adforest-rest-api");
			/*array_reverse*/
		$nextPaged = $paged + 1;
		$has_next_page = ( $nextPaged <= (int)$max_num_pages ) ? true : false;
		$data['pagination'] = array("max_num_pages" => (int)$max_num_pages, "current_page" => (int)$paged, "next_page" => (int)$nextPaged, "increment" => (int)$posts_per_page , "current_no_of_ads" =>  (int)count($commentsData), "has_next_page" => $has_next_page );				
			
			$message = ( $success == false ) ? __("No Chat Found", "adforest-rest-api") : $message2;

			
			if ($verifed_phone_number ){ 
				if(  $message_by_user != "" )
				{
					$message  = __("Please verify your phone number to send message.", "adforest-rest-api");
					return $response = array( 'success' => false, 'data' => $data, 'message'  => $message  );
				}
					
			}			
			
			return $response = array( 'success' => $success, 'data' => $data, 'message'  => $message  );	
	}
}

add_action( 'rest_api_init', 'adforestAPI_messages_chat_api_hooks_popup', 0 );
function adforestAPI_messages_chat_api_hooks_popup() {
    register_rest_route( 'adforest/v1', '/message/popup/',
        array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => 'adforestAPI_messages_chat_submit_popup',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );
    register_rest_route( 'adforest/v1', '/message/popup/',
        array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => 'adforestAPI_messages_chat_submit_popup',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );	
}

if (!function_exists('adforestAPI_messages_chat_submit_popup'))
{
	function adforestAPI_messages_chat_submit_popup( $request )
	{
		
		
			
		$verifed_phone_number = adforestAPI_check_if_phoneVerified();	
        if ($verifed_phone_number){ 
			$message2  = __("Please verify your phone number to send message.", "adforest-rest-api");
			return  array( 'success' => false, 'data' => '', 'message'  => $message2  );	
		}
		
		$json_data = $request->get_json_params();		
		$ad_id = (isset( $json_data['ad_id'] ) && $json_data['ad_id'] != "" ) ? $json_data['ad_id'] : '';
		$message = (isset( $json_data['message'] ) && $json_data['message'] != "" ) ? $json_data['message'] : '';
		$user 		= wp_get_current_user();	
		$sender_id 	= $user->data->ID;		
		$receiver_id = get_post_field('post_author', $ad_id );
		$queryID	= $sender_id;
		$message2 = __("Something went wrong", "adforest-rest-api");
		$success = false;
		if( $ad_id != "" && $sender_id != "" && $receiver_id != "" &&  $message != "" )
		{
			if (function_exists('adforestAPI_add_messages_get'))
			$message2  = adforestAPI_add_messages_get( $ad_id, $queryID,  $sender_id, $receiver_id, 'sent', $message );
			$success  = true;
		}
		return $response = array( 'success' => $success, 'data' => '', 'message'  => $message2  );		
	}
}

if (!function_exists('adforestAPI_add_messages_get'))
{
	function adforestAPI_add_messages_get( $ad_id = '', $queryID = '', $sender_id = '', $receiver_id = '', $type = 'sent', $message = '' )
	{
		$user = wp_get_current_user();	
		$user_id = (int)$user->data->ID;	
		$user_email = $user->data->user_email;
		$display_name = $user->data->display_name;
		$time = current_time('mysql');
		$data = array(
			'comment_post_ID' => $ad_id,
			'comment_author' => $display_name,
			'comment_author_email' => $user_email,
			'comment_author_url' => '',
			'comment_content' => $message,
			'comment_type' => 'ad_post',
			'comment_parent' => $queryID,
			'user_id' => $user_id,
			'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
			'comment_date' => $time,
			'comment_approved' => 1,
		);		
				
		$comment_id	=	wp_insert_comment($data);
		if( $comment_id )
		{
			$typeData = ( $type != "sent" ) ? $sender_id : $receiver_id;
			update_comment_meta( $typeData, $ad_id."_".$user_id, 0 );
			/*Send Email When Message On Ad*/
			adforestAPI_get_notify_on_ad_message($ad_id, $typeData, $message, $display_name );
			
			adforestAPI_messages_sent_func( $type,  $receiver_id, $sender_id, $user_id, $comment_id, $ad_id, $message, $time );
			$messageString =  __( "Message sent successfully .", 'adforest-rest-api' );	
		}
		else
		{
			$messageString =  __( "Message not sent, please try again later.", 'adforest-rest-api' );
		}
		return $messageString;	
	}
}

if (!function_exists('adforestAPI_messages_sent_func'))
{
	function adforestAPI_messages_sent_func( $type,  $receiver_id, $sender_id, $user_id, $comment_id, $ad_id, $message, $time)
	{
		
			global $adforestAPI;
			if( isset( $adforestAPI['app_settings_message_firebase'] ) && $adforestAPI['app_settings_message_firebase'] == true )
			{
				$chat = array();
				$fbuserid = ( $type == "sent" ) ? $receiver_id : $sender_id;
				$queryID = ( $type == 'sent' ) ? $user_id : $sender_id;
				$firebase_meta_key = ( ADFOREST_API_REQUEST_FROM == 'ios' ) ? '_sb_user_firebase_id_ios' : '_sb_user_firebase_id';
				$f_reg_id  = get_user_meta($fbuserid, $firebase_meta_key, true );
				$fregidios  	 = get_user_meta($fbuserid, '_sb_user_firebase_id_ios', true );
				$fregidandroid   = get_user_meta($fbuserid, '_sb_user_firebase_id', true );
				if( $fregidios != "" || $fregidandroid != "" )
				{
					$fbuserid_message_type = ( $type == "sent" ) ? "receive" : "sent";					
					$messager_img  	= ( $type == "sent" ) ?  adforestAPI_user_dp( $sender_id) :  adforestAPI_user_dp( $receiver_id);
					if( $type == 'sent' )
					{
						$messageType 	= ( $queryID != $user_id  ) ? 'message' : 'reply';						
					}
					else
					{
						$messageType 	= ( $queryID != $user_id  ) ? 'reply' : 'message';
					}							
									
					
					$chat['img'] 				= $messager_img;
					$chat['id'] 				= $comment_id;
					$chat['ad_id'] 				= $ad_id;
					$chat['text'] 				= $message;
					$chat['date'] 				= adforestAPI_timeago( $time );
					$chat['type'] 				= $messageType;
					
		$request_from = adforestAPI_getSpecific_headerVal('Adforest-Request-From');
		if( $request_from == 'ios' )
		{
				$f_reg_id  = get_user_meta($receiver_id, '_sb_user_firebase_id_ios', true );
			
					$chat['sound'] 				= 'default';
					$chat['content-available'] 	= true;
					$chat['priority'] 			= "high";
					$chat['body'] 				= $message;
					$chat['title'] 				= get_the_title($ad_id);
			
					$message_data = array
						(
							"to" 			=> $f_reg_id,
							'topic' 		=> 'chat',
							'message' 		=> $message,
							'title'			=> get_the_title($ad_id),
							'adId' 			=> $ad_id,
							'senderId' 		=> $sender_id,
							'recieverId' 	=> $receiver_id,
							'type' 			=> $fbuserid_message_type,
							'chat' 			=> $chat,
							'notification'  => $chat,
						);	

		}
		else
		{
					$message_data = array
						(
							'topic' 		=> 'chat',
							'message' 		=> $message,
							'title'			=> get_the_title($ad_id),
							'adId' 			=> $ad_id,
							'senderId' 		=> $sender_id,
							'recieverId' 	=> $receiver_id,
							'type' 			=> $fbuserid_message_type,
							'chat' 			=> $chat,
						);				
		}
					

					/*Added new support on 6 sep 2018*/	
					$f_reg_id_ios  		= get_user_meta($fbuserid, '_sb_user_firebase_id_ios', true );
					$f_reg_id_android   = get_user_meta($fbuserid, '_sb_user_firebase_id', true );
						
					if($f_reg_id_ios != "" )
					{
						adforestAPI_firebase_notify_func($f_reg_id_ios, $message_data);	
					}
					if( $f_reg_id_android != "" )
					{
						
						adforestAPI_firebase_notify_func($f_reg_id_android, $message_data);
					}
				}
			}
	}	
}

/*-----
	Ad Messages Users Chat
-----*/
add_action( 'rest_api_init', 'adforestAPI_messages_sent_api_hooks_get', 0 );
function adforestAPI_messages_sent_api_hooks_get() {
    register_rest_route( 'adforest/v1', '/message/sent/',
        array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => 'adforestAPI_messages_sent_get',
				'permission_callback' => function () {  return adforestAPI_basic_auth();  },
        	)
    );	
}

if (!function_exists('adforestAPI_messages_sent_get'))
{
	function adforestAPI_messages_sent_get( $request )
	{

		$json_data = $request->get_json_params();		
		$ad_id         = (isset( $json_data['ad_id'] ) && $json_data['ad_id'] != "" ) ? $json_data['ad_id'] : '';
		$sender_id     = (isset( $json_data['sender_id'] ) && $json_data['ad_id'] != "" ) ? (int)$json_data['sender_id'] : '';
		$receiver_id   = (isset( $json_data['receiver_id'] ) && $json_data['receiver_id'] != "" ) ? (int)$json_data['receiver_id'] : '';		
		$message   = (isset( $json_data['message'] ) && $json_data['message'] != "" ) ? (int)$json_data['message'] : '';

		$user = wp_get_current_user();	
		$user_id = (int)$user->data->ID;	
		$authors	=	array( $sender_id, $user_id );
		
		if ( get_query_var( 'paged' ) ) { $paged = get_query_var( 'paged' ); } else if ( isset( $json_data['page_number'] ) ) { $paged = $json_data['page_number']; } else { $paged = 1; }	
			
		$posts_per_page = 10;
		$start = ($paged-1) * $posts_per_page;		
		$cArgs = array( 'author__in' => $authors, 'post_id' => $ad_id, 'parent' => $user_id, 'orderby' => 'comment_date', 'order' => 'ASC', );
		$commentsData	=	get_comments( $cArgs );	
		$total_posts = count( $commentsData ); 
		$max_num_pages = ceil($total_posts/$posts_per_page);			
		
		$args = array(
			'author__in' => $authors,
			'post_id' => $ad_id,
			'parent' => $user_id,
			'orderby' => 'comment_date',
			'order' => 'ASC',
			'paged' => $paged,
			'offset' => $start,
			'number' => $posts_per_page,
		);
			$comments	=	get_comments( $args );			
			$chat = array();
			$chatHistory = array();
			$success = false;
				
			if( count( $comments ) > 0 )
			{
				$success = true;
				foreach( $comments as $comment)
				{
					$messageType = ( $comment->comment_parent != $comment->user_id ) ? 'reply' : 'message';
					$chat['id'] = $comment->comment_ID;
					$chat['ad_id'] = $comment->comment_post_ID;
					$chat['text'] = $comment->comment_content;
					$chat['date'] = $comment->comment_date;
					$chat['type'] = $messageType;
					$chatHistory[] = $chat;
				}
			}

			$data['chat'] = $chatHistory;
			$nextPaged = $paged + 1;
			$has_next_page = ( $nextPaged <= (int)$max_num_pages ) ? true : false;
			$data['pagination'] = array("max_num_pages" => (int)$max_num_pages, "current_page" => (int)$paged, "next_page" => (int)$nextPaged, "increment" => (int)$posts_per_page , "current_no_of_ads" =>  (int)count($commentsData), "has_next_page" => $has_next_page );				
			
			$message = ( $success == false ) ? __("No Chat Found", "adforest-rest-api") : '';
			return $response = array( 'success' => $success, 'data' => $data, 'message'  => $message  );	
	}
}

if (!function_exists('adforestAPI_messages_get'))
{
	function adforestAPI_count_ad_messages( $ad_id = '', $user_id = '' )
	{
		global $wpdb;
		$total  = 0;
		if( $ad_id != '' && $user_id != '' )
		{		
			$total = $wpdb->get_var("SELECT COUNT(DISTINCT(comment_author)) as total FROM $wpdb->comments WHERE comment_post_ID = '".$ad_id."' AND user_id != '".$user_id."'");
		}
		return $total;
	}
}