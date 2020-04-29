<?php
/* ----	Register Starts Here ----*/
add_action( 'rest_api_init', 'adforestAPI_register_api_hooks_post', 0 );
function adforestAPI_register_api_hooks_post() {
	register_rest_route( 'adforest/v1', '/register/',
		array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => 'adforestAPI_register_me_post',
				/*'permission_callback' => function () { return adforestAPI_basic_auth();  },*/
			)
	);
}

    function adforestAPI_register_me_post( $request ) {		
		$json_data = $request->get_json_params();		
		if( empty( $json_data ) || !is_array( $json_data ) )
		{
			$response = array( 'success' => false, 'data' => '' , 'message' => __("Please fill out all fields.", "adforest-rest-api") );
			return rest_ensure_response( $response );

		}		
        $output = array();
		
		$from	 	= (isset($json_data['from'])) ? $json_data['from'] : '';
		$name 		= (isset($json_data['name'])) ? $json_data['name'] : '';
		$email 		= (isset($json_data['email'])) ? $json_data['email'] : '';
		$phone 		= (isset($json_data['phone'])) ? $json_data['phone'] : '';
		$password 	= (isset($json_data['password'])) ? $json_data['password'] : '';
		
		if( $name == "" )
		{
			$response = array( 'success' => false, 'data' => '' , 'message' => __("Please enter name.", "adforest-rest-api") );
			return $response;
		}
		if( $email == "" )
		{
			$response = array( 'success' => false, 'data' => '' , 'message'  => __("Please enter email.", "adforest-rest-api") );
			return $response;
		}
		if( $password == "" )
		{
			$response = array( 'success' => false, 'data' => '' , 'message'  => __("Please enter password.", "adforest-rest-api") );
			return $response;
		}		
		if( email_exists( $email ) == true )
		{
			$response = array( 'success' => false, 'data' => '' , 'message'  => __("Email Already Exists.", "adforest-rest-api") );
			return $response;
		}
		
		$username	=	stristr($email, "@", true);
		/*Generate Username*/
		$u_name		= 	adforestAPI_check_username($username);
		/* Register User With WP */
		$uid =	wp_create_user( $u_name, $password, $email );
		
		global $adforestAPI;

		if( isset( $adforestAPI['sb_phone_verification'] ) && $adforestAPI['sb_phone_verification'] && in_array( 'wp-twilio-core/core.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
		{
			update_user_meta( $uid, '_sb_is_ph_verified', '0' );
		}
		
			wp_update_user( array( 'ID' => $uid, 'display_name' => $name ) );
			update_user_meta($uid, '_sb_contact', $phone );
			if( isset( $adforestAPI['sb_allow_ads'] ) && $adforestAPI['sb_allow_ads'] )
			{
				
				$freeAds 	= adforestAPI_getReduxValue('sb_free_ads_limit', '', false);
				$freeAds 	= ( isset( $adforestAPI['sb_allow_ads'] ) && $adforestAPI['sb_allow_ads'] ) ? $freeAds : 0;
				$featured 	= adforestAPI_getReduxValue('sb_featured_ads_limit', '', false);
				$featured 	= ( isset( $adforestAPI['sb_allow_featured_ads'] ) && $adforestAPI['sb_allow_featured_ads'] ) ? $featured : 0;
				$bump 		= adforestAPI_getReduxValue('sb_bump_ads_limit', '', false);
				$bump 		= ( isset( $adforestAPI['sb_allow_bump_ads'] ) && $adforestAPI['sb_allow_bump_ads'] ) ? $bump : 0;
				$validity 	= adforestAPI_getReduxValue('sb_package_validity', '', false);

				update_user_meta( $uid, '_sb_simple_ads',   $freeAds );
				update_user_meta( $uid, '_sb_featured_ads', $featured );
				update_user_meta( $uid, '_sb_bump_ads', $bump );
				
				if( $validity == '-1' )
				{
					update_user_meta( $uid, '_sb_expire_ads', $validity );
				}
				else
				{
					$expiry_date	=	date('Y-m-d', strtotime("+$validity days"));
					update_user_meta( $uid, '_sb_expire_ads', $expiry_date );		
				}
			}
			else
			{
				update_user_meta( $uid, '_sb_simple_ads', 0 );
				update_user_meta( $uid, '_sb_featured_ads', 0 );
				update_user_meta( $uid, '_sb_bump_ads', 0 );
				update_user_meta( $uid, '_sb_expire_ads', date('Y-m-d') );
			}
			
			update_user_meta( $uid, '_sb_pkg_type', 'free' );	
		
			$user_info 						= get_userdata( $uid );		
			$profile_arr = array();
			$profile_arr['id']				= $user_info->ID;
			$profile_arr['user_email']		= $user_info->user_email;
			$profile_arr['display_name']	= $user_info->display_name;
			$profile_arr['phone']			= get_user_meta($user_info->ID, '_sb_contact', true );
			$profile_arr['profile_img']		= adforestAPI_user_dp( $user_info->ID);
					
			$message_text = __("Registered successfully.", "adforest-rest-api");
				adforestAPI_email_on_new_user($uid, '');	
			if( isset( $adforestAPI['sb_new_user_email_verification'] ) && $adforestAPI['sb_new_user_email_verification'] )
			{											
				$message_text = __("Registered successfully. Please verify your email address.", "adforest-rest-api");
				/*Remove User Role For Email Verifications*/
				$user = new WP_User($uid);
				foreach($user->roles as $role){ $user->remove_role($role); }				
			}
								
			$response = array( 'success' => true, 'data' => $profile_arr, 'message' => $message_text );	
			return $response;		
        
    }
	
add_action( 'rest_api_init', 'adforestAPI_register_api_hooks_get', 0 );
function adforestAPI_register_api_hooks_get() {
    register_rest_route( 'adforest/v1', '/register/',
        array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => 'adforestAPI_register_me_get',
				/*'permission_callback' => function () { return adforestAPI_basic_auth();  },*/
        	)
    );
}

if( !function_exists('adforestAPI_register_me_get' ) )
{
	function adforestAPI_register_me_get()
	{
		global $adforestAPI;
		$data['bg_color']				= 	'#000';
		$data['logo']					= 	adforestAPI_appLogo();
		$data['heading']				=  __("Register With Us!", "adforest-rest-api");		
		$data['name_placeholder']		=  __("Full Name", "adforest-rest-api");
		$data['email_placeholder']		=  __("Email Address", "adforest-rest-api");
		$data['phone_placeholder']		=  __("Phone Number", "adforest-rest-api");
		$data['password_placeholder']	=  __("Password", "adforest-rest-api");
		$data['form_btn']				=  __("Register", "adforest-rest-api");
		$data['separator']				=  __("OR", "adforest-rest-api");
		$data['facebook_btn']			=  __("Facebook", "adforest-rest-api");
		$data['google_btn']				=  __("Google+", "adforest-rest-api");
		$data['login_text']				=  __("Already Have Account? Login Here", "adforest-rest-api");
		$verified = (isset($adforestAPI['sb_new_user_email_verification']) && $adforestAPI['sb_new_user_email_verification'] == false) ? false : true;
		$data['is_verify_on']			=  $verified;
		
		$data['term_page_id'] = (isset($adforestAPI['sb_new_user_register_policy'])) ? $adforestAPI['sb_new_user_register_policy'] : '';
		$checkbox_text = (isset($adforestAPI['sb_new_user_register_checkbox_text']) && $adforestAPI['sb_new_user_register_checkbox_text'] != "") ? $adforestAPI['sb_new_user_register_checkbox_text'] : __("Agree With Our Term and Conditions.", "adforest-rest-api");
		$data['terms_text']				=  $checkbox_text;
		return $response = array( 'success' => true, 'data' => $data, 'message'  => ''  );				
	}
}

/*Forgot*/
add_action( 'rest_api_init', 'adforestAPI_forgot_api_hooks_get', 0 );
function adforestAPI_forgot_api_hooks_get() {

    register_rest_route( 'adforest/v1', '/forgot/',
        array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => 'adforestAPI_forgot_me_get',
				/*'permission_callback' => function () { return adforestAPI_basic_auth();  },*/
        	)
    );
}
if( !function_exists('adforestAPI_forgot_me_get' ) )
{
	function adforestAPI_forgot_me_get()
	{		
		$data['bg_color']			= '#000';
		$data['logo']				= adforestAPI_appLogo();
		$data['heading']			=  __("Forgot Password?", "adforest-rest-api");
		$data['text']				=  __("Please enter your email address below.", "adforest-rest-api");
		$data['email_placeholder']	=  __("Email Address", "adforest-rest-api");		
		$data['submit_text']		=  __("Submit", "adforest-rest-api");
		$data['back_text']			=  __("Back", "adforest-rest-api");
		return $response = array( 'success' => true, 'data' => $data, 'message'  => ''  );		
	}
}