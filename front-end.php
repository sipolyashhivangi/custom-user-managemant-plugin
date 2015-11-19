<?php
/*
Plugin Name: Front End Registration and Login
Plugin URI: https://pippinsplugins.com/creating-custom-front-end-registration-and-login-forms
Description: Provides simple front end registration and login forms
Version: 1.0
Author: Pippin Williamson
Author URI: https://pippinsplugins.com
*/

add_filter( 'wp_nav_menu_args', 'my_wp_nav_menu_args' );
function my_wp_nav_menu_args( $args = '' ) {

if( is_user_logged_in() ) { 
	$args['menu'] = 'Loggedin_user_menu';
	?>
	<a href="<?php echo wp_logout_url( home_url() ); ?>">Logout</a>
	
	<?
} else { 
	$args['menu'] = 'menus';
} 
	return $args;
}
function pippin_registration_form() {
 
	// only show the registration form to non-logged-in members
	if(!is_user_logged_in()) {
 
		global $pippin_load_css;
 
		// set this to true so the CSS is loaded
		$pippin_load_css = true;
 
		// check to make sure user registration is enabled
		$registration_enabled = get_option('users_can_register');
 
		// only show the registration form if allowed
		if($registration_enabled) {
			$output = pippin_registration_form_fields();
		} else {
			$output = __('User registration is not enabled');
		}
		return $output;
	}
}
add_shortcode('register_form', 'pippin_registration_form');

// registration form fields
function pippin_registration_form_fields() {
 
	ob_start(); ?>	
		<h3 class="pippin_header"><?php _e('Register New Account'); ?></h3>
 
		<?php 
		// show any error messages after form submission
		pippin_show_error_messages(); ?>
 
		<form id="pippin_registration_form" class="pippin_form" action="" method="POST">
			<fieldset>
				<p>
					<label for="pippin_user_Login"><?php _e('Username'); ?></label>
					<input name="pippin_user_login" id="pippin_user_login" value="<?php 
					echo $_POST["pippin_user_login"];?>" class="required" type="text"/>
				</p>
				<p>
					<label for="pippin_user_email"><?php _e('Email'); ?></label>
					<input name="pippin_user_email" id="pippin_user_email" value="<?php 
					echo $_POST["pippin_user_email"];?>"
					class="required" type="email"/>
				</p>
				<p>
					<label for="pippin_user_first"><?php _e('First Name'); ?></label>
					<input name="pippin_user_first" id="pippin_user_first" class="required" value="<?php 
					echo $_POST["pippin_user_first"];?>" type="text"/>
				</p>
				<p>
					<label for="pippin_user_last"><?php _e('Last Name'); ?></label>
					<input name="pippin_user_last" id="pippin_user_last" class="required" value="<?php 
					echo $_POST["pippin_user_last"];?>" type="text"/>
				</p>
				<p>
					<label for="password"><?php _e('Password'); ?></label>
					<input name="pippin_user_pass" id="password" class="required" type="password" />
				</p>
				<p>
					<label for="password_again"><?php _e('Confirm Password'); ?></label>
					<input name="pippin_user_pass_confirm" id="password_again" class="required" type="password"/>
				</p>
				<p>
					<input type="hidden" name="pippin_register_nonce" value="<?php echo wp_create_nonce('pippin-register-nonce'); ?>"/>
					<input type="submit" value="<?php _e('Register Your Account'); ?>"/>
				</p>
			</fieldset>
		</form>
	<?php
	
	return ob_get_clean();
}

// register a new user
function pippin_add_new_member() {
  	if (isset( $_POST["pippin_user_login"] ) && wp_verify_nonce($_POST['pippin_register_nonce'], 'pippin-register-nonce')) {
		$user_login		= $_POST["pippin_user_login"];	
		$user_email		= $_POST["pippin_user_email"];
		$user_first 	= $_POST["pippin_user_first"];
		$user_last	 	= $_POST["pippin_user_last"];
		$user_pass		= $_POST["pippin_user_pass"];
		$pass_confirm 	= $_POST["pippin_user_pass_confirm"];
 
		// this is required for username checks
		require_once(ABSPATH . WPINC . '/registration.php');
 
		if(username_exists($user_login)) {
			// Username already registered
			pippin_errors()->add('username_unavailable', __('Username already taken'));
		}
		if(!validate_username($user_login)) {
			// invalid username
			pippin_errors()->add('username_invalid', __('Invalid username'));
		}
		if($user_login == '') {
			// empty username
			pippin_errors()->add('username_empty', __('Please enter a username'));
		}
		if($user_first == '') {
			// empty first name
			pippin_errors()->add('username_empty', __('Please enter a First Name'));
		}
		if($user_last == '') {
			// empty Last name
			pippin_errors()->add('username_empty', __('Please enter a Last Name'));
		}
		if(!is_email($user_email)) {
			//invalid email
			pippin_errors()->add('email_invalid', __('Invalid email'));
		}
		if(email_exists($user_email)) {
			//Email address already registered
			pippin_errors()->add('email_used', __('Email already registered'));
		}
		if($user_pass == '') {
			// passwords do not match
			pippin_errors()->add('password_empty', __('Please enter a password'));
		}
		if($user_pass != $pass_confirm) {
			// passwords do not match
			pippin_errors()->add('password_mismatch', __('Passwords do not match'));
		}
 
		$errors = pippin_errors()->get_error_messages();
 
		// only create the user in if there are no errors
		if(empty($errors)) {
 
			$new_user_id = wp_insert_user(array(
					'user_login'		=> $user_login,
					'user_pass'	 		=> $user_pass,
					'user_email'		=> $user_email,
					'first_name'		=> $user_first,
					'last_name'			=> $user_last,
					'user_registered'	=> date('Y-m-d H:i:s'),
					'role'				=> 'subscriber'
				)
			);
			global $wpdb;
			$table_name = $wpdb->prefix . "user_image";
			$wpdb->insert($table_name, array(
							'username' => $user_login	
							),array(
							'%s',
							'%s') 
			);
			if($new_user_id) {
				wp_new_user_notification($new_user_id);
 
				// log the new user in
				wp_setcookie($user_login, $user_pass, true);
				wp_set_current_user($new_user_id, $user_login);	
				do_action('wp_login', $user_login);
 
				// send the newly created user to the home page after logging them in
				wp_redirect(home_url()); exit;
			}
 
		}
 
	}
}
add_action('init', 'pippin_add_new_member');

// displays error messages from form submissions
function pippin_show_error_messages() {
	if($codes = pippin_errors()->get_error_codes()) {
		echo '<div class="pippin_errors">';
		    // Loop error codes and display errors
		   foreach($codes as $code){
		        $message = pippin_errors()->get_error_message($code);
		        echo '<span class="error"><strong>' . __('Error') . '</strong>: ' . $message . '</span><br/>';
		    }
		echo '</div>';
	}	
}
function pippin_errors(){
    static $wp_error; // Will hold global variable safely
    return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
}
function pippin_print_css() {
	global $pippin_load_css;
 
	// this variable is set to TRUE if the short code is used on a page/post
	if ( ! $pippin_load_css )
		return; // this means that neither short code is present, so we get out of here
 
	wp_print_styles('pippin-form-css');
}
add_action('wp_footer', 'pippin_print_css');
// register our form css
function pippin_register_css() {
	wp_register_style('pippin-form-css', plugin_dir_url( __FILE__ ) . '/css/forms.css');
}
add_action('init', 'pippin_register_css');
// user login form
function pippin_login_form() {
 
	if(!is_user_logged_in()) {
 
		global $pippin_load_css;
 
		// set this to true so the CSS is loaded
		$pippin_load_css = true;
 
		$output = pippin_login_form_fields();
	} else {
		// could show some logged in user info here
		// $output = 'user info here';
	}
	return $output;
}
add_shortcode('login_form', 'pippin_login_form');
// login form fields
function pippin_login_form_fields() {
 
	ob_start(); ?>
		<h3 class="pippin_header"><?php _e('Login'); ?></h3>
 
		<?php
		// show any error messages after form submission
		pippin_show_error_messages(); ?>
 
		<form id="pippin_login_form"  class="pippin_form"action="" method="post">
			<fieldset>
				<p>
					<label for="pippin_user_Login">Username</label>
					<input name="pippin_user_login" id="pippin_user_login" class="required" type="text"/>
				</p>
				<p>
					<label for="pippin_user_pass">Password</label>
					<input name="pippin_user_pass" id="pippin_user_pass" class="required" type="password"/>
				</p>
				<p>
					<input type="hidden" name="pippin_login_nonce" value="<?php echo wp_create_nonce('pippin-login-nonce'); ?>"/>
					<input id="pippin_login_submit" type="submit" value="Login"/>
				</p>
			</fieldset>
		</form>
	<?php
	return ob_get_clean();
}
// logs a member in after submitting a form
add_action('init', 'pippin_login_member');
function pippin_login_member() {
 
	if(isset($_POST['pippin_user_login']) && wp_verify_nonce($_POST['pippin_login_nonce'], 'pippin-login-nonce')) {
 
		// this returns the user ID and other info from the user name
		$user = get_userdatabylogin($_POST['pippin_user_login']);
 
		if(!$user) {
			// if the user name doesn't exist
			pippin_errors()->add('empty_username', __('Invalid username'));
		}
 
		if(!isset($_POST['pippin_user_pass']) || $_POST['pippin_user_pass'] == '') {
			// if no password was entered
			pippin_errors()->add('empty_password', __('Please enter a password'));
		}
 
		// check the user's login with their password
		if(!wp_check_password($_POST['pippin_user_pass'], $user->user_pass, $user->ID)) {
			// if the password is incorrect for the specified user
			pippin_errors()->add('empty_password', __('Incorrect password'));
		}
 
		// retrieve all error messages
		$errors = pippin_errors()->get_error_messages();
 
		// only log the user in if there are no errors
		if(empty($errors)) {
 
			wp_setcookie($_POST['pippin_user_login'], $_POST['pippin_user_pass'], true);
			wp_set_current_user($user->ID, $_POST['pippin_user_login']);	
			do_action('wp_login', $_POST['pippin_user_login']);
 
			wp_redirect(home_url()); exit;
		}
	}
}

// Create custom table for saving image in database

register_activation_hook( __FILE__, 'create_plugin_tables' );
function create_plugin_tables()
{
    global $wpdb;
	$table_name = $wpdb->prefix . 'user_image';
	$sqlQry = "CREATE TABLE $table_name (
      id int(11) NOT NULL AUTO_INCREMENT,
      username varchar(45) NOT NULL,
		image varchar(45) NOT NULL,
      UNIQUE KEY id (id)
    )";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sqlQry);
}

// Edit user profile code
function edit_user_profile() {
 
	// only show the registration form to non-logged-in members
	$output = pippin_edit_form_fields();
	return $output;
	
}
add_shortcode('edit_user', 'edit_user_profile');
function pippin_edit_form_fields(){
	ob_start(); 
 global $current_user;

	 get_currentuserinfo();
		// show any error messages after form submission
		pippin_show_error_messages(); ?>
 
		<form id="pippin_registration_form" class="pippin_form" action="" method="POST" enctype="multipart/form-data">
			<fieldset>
				<p>
					<label for="pippin_user_Login"><?php _e('Username'); ?></label>
					<span><?php 
					echo  $current_user->user_login ;?></span>

				</p>
				<p>
					<label for="pippin_user_email"><?php _e('Email'); ?></label>
					<input name="pippin_user_email" id="pippin_user_email" value="<?php 
					echo  $current_user->user_email;?>"
					class="required" type="email"/>
				</p>
				<p>
					<label for="pippin_user_first"><?php _e('First Name'); ?></label>
					<input name="pippin_user_first" id="pippin_user_first" class="required" value="<?php 
					echo $current_user->user_firstname;?>" type="text"/>
				</p>
				<p>
					<label for="pippin_user_last"><?php _e('Last Name'); ?></label>
					<input name="pippin_user_last" id="pippin_user_last" class="required" value="<?php 
					echo $current_user->user_lastname;?>" 
					type="text"/>
				</p>
				<p><?php 
				
				//echo($_POST['pippin_user_image']);
					?>
					<label for="pippin_user_image"><?php _e('Profile Picture'); ?></label>
					<input name="pippin_user_image" id="pippin_user_image"  value="" 
					type="file"/><?php 
					echo $_FILES['pippin_user_image']['name'];  ?>
				</p>
				
				<p>
					
					<input type="submit"  name="update" value="Update Profile"/>
				</p>
			</fieldset>
		</form>
	<?php
	
	return ob_get_clean();
}
 
 function update_user_profile() {
	global $current_user;
	global $wpdb;
	$username = $current_user->user_login;
  	if (isset( $_POST['update'])) {
		$user_id		= $current_user->ID;
		$user_email		= $_POST["pippin_user_email"];
		$user_first 	= $_POST["pippin_user_first"];
		$user_last	 	= $_POST["pippin_user_last"];
		$user_image		= $_FILES['pippin_user_image']['name'];
		//Image Move script
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		$uploadedfile = $_FILES['pippin_user_image'];
		$upload_overrides = array( 'test_form' => false );
		$movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
		
	//Image upload script
	$table_name = $wpdb->prefix . "user_image";
		
		$post_id = $wpdb->get_results("SELECT * FROM $table_name WHERE username= '".$username."'");

		if($post_id[0]->image==''){
			if(username_exists($username)){
				$wpdb->update($table_name, array(
						'username'	=> $username ,
						'image' 	=> $user_image
						),array( 'id' => $post_id[0]->id),
						array(
						'%s',
						'%s') 
					);
				
			}else{
			$wpdb->insert($table_name, array(
							'username' => $username ,
							'image' => $user_image
							),array(
							'%s',
							'%s') 
			);}
		}else{
			$wpdb->update($table_name, array(
						'username'	=> $username ,
						'image' 	=> $user_image
						),array( 'id' => $post_id[0]->id),
						array(
						'%s',
						'%s') 
					);

		}
		// this is required for username checks
	
		require_once(ABSPATH . WPINC . '/update.php');
 
		if($user_first == '') {
			// empty first name
			pippin_errors()->add('username_empty', __('Please enter a First Name'));
		}
		if($user_last == '') {
			// empty Last name
			pippin_errors()->add('username_empty', __('Please enter a Last Name'));
		}
		if(!is_email($user_email)) {
			//invalid email
			pippin_errors()->add('email_invalid', __('Invalid email'));
		}
if(email_exists($user_email && $user_email=$current_user->user_email)) {
			//Email address already registered
			pippin_errors()->add('email_used', __('Email already registered'));
		}
		
 
		$errors = pippin_errors()->get_error_messages();

		// only create the user in if there are no errors
		if(empty($errors)) {

		$edit_user_id = wp_update_user(array(
					'ID' 				=> $user_id, 
					'user_email'		=> $user_email,
					'first_name'		=> $user_first,
					'last_name'			=> $user_last
				)
			);
		}
 
	}
}
add_action('init', 'update_user_profile');

// Reset password script

add_shortcode('reset_password','reset_password_function');
function reset_password_function(){
	if(is_user_logged_in()){
		$display = reset_password_feild();
		return $display;
	}
}
function reset_password_feild(){
?>
	<h3 class="pippin_header"><?php _e('Register New Account'); ?></h3>
	<?php 
		// show any error messages after form submission
		pippin_show_error_messages(); ?>
 
		<form id="pippin_reset_password_form" class="pippin_form" action="" method="POST">
			<fieldset>
				<p>
					<label for="password"><?php _e('Old Password'); ?></label>
					<input name="pippin_reset_pass" id="password" class="required" type="password" />
				</p>
				<p>
					<label for="password"><?php _e('New Password'); ?></label>
					<input name="pippin_reset_new_pass" id="password" class="required" type="password" />
				</p>
				<p>
					<label for="password_again"><?php _e('Confirm New Password'); ?></label>
					<input name="pippin_reset_new_pass_confirm" id="password_again" class="required" type="password"/>
				</p>
				<p>
					<input type="submit" value="<?php _e('Update Password'); ?>"/>
				</p>
			</fieldset>
		</form>
	<?php
	
	return ob_get_clean();
}
add_action('init','update_new_password');
function update_new_password(){
	global $wpdb;
	if(isset($_POST['pippin_reset_pass'])){
	 global $current_user;
		$old_pass		= $_POST["pippin_reset_pass"];
		$new_pass   	= $_POST["pippin_reset_new_pass"];
		$new_pass_confirm= $_POST["pippin_reset_new_pass_confirm"];
		if($old_pass == '') {
			// passwords do not match
			pippin_errors()->add('password_empty', __('Please enter old password'));
		}
		if($new_pass == '') {
			//passwords do not match
			pippin_errors()->add('password_empty', __('Please enter new password'));
		}
		
		if($new_pass != $new_pass_confirm) {
			//passwords do not match
			pippin_errors()->add('password_mismatch', __('Passwords do not match'));
		}
 
		$errors = pippin_errors()->get_error_messages();
		if(empty($errors)){
		
		$username = $current_user->user_login;
		$table_name = $wpdb->prefix . "users";
		
		$results = $wpdb->get_results("SELECT * FROM $table_name WHERE user_login = '".$username."'");
		
		$old_pass_word = $results[0]->user_pass;
		
		//echo $old_pass_word  = wp_hash_password($old_pass); 

if (wp_check_password( $old_pass, $old_pass_word, $results[0]->ID) ){
		$updated = wp_set_password( $new_pass, $results[0]->ID );
		echo "Password updated";
	}
else
   echo "Your old password is not matched";
		}
	}
}

add_shortcode('forgot_password','forgot_password_func');
function forgot_password_func(){
?>
	<form id="pippin_recovery_password_form" class="pippin_form" action="" method="POST">
	<p>
		<label for="pippin_user_email"><?php _e('Email'); ?></label>
		<input name="pippin_user_email" id="pippin_user_email" value="<?php 
		echo  $current_user->user_email;?>"
		class="required" type="email"/>
	</p>
	<p>
					<input type="submit" value="<?php _e('Get Password'); ?>" name="get_password"/>
				</p>
</form>
<?
}
add_action('init','get_password_by_mail');
function get_password_by_mail(){
global $current_user , $wpdb;
if(isset($_POST['get_password'])){
$success = '';
$error = '';
	$useremail = $current_user->user_email;
	$recover_email		= $_POST["pippin_user_email"];
if( ! empty( $success ) )
            echo '<div class="updated"> '. $success .'</div>';
if( ! empty( $error ) )
            echo '<div class="updated"> '. $error .'</div>';	  
            if( empty( $recover_email ) ) {
               echo 'Enter a username or e-mail address..';
            } else if( ! is_email( $recover_email )) {
                echo 'Invalid username or e-mail address.';
            } else if( !email_exists( $recover_email ) ) {
                echo 'There is no user registered with that email address.';
            }else
	{
		$random_password = wp_generate_password( 12, false );
		//echo $random_password;
		$user = get_user_by( 'email', $recover_email	 );
            //echo $user->ID;

            $update_user = wp_update_user( array (
                    'ID' => $user->ID, 
                    'user_pass' => $random_password
                )
            );
			if( $update_user ) {
                $to = $recover_email;
                $subject = 'Your new password';
                $sender = get_option('name');
                
                $message = 'Your new password is: '.$random_password;
                
                $headers[] = 'MIME-Version: 1.0' . "\r\n";
                $headers[] = 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
                $headers[] = "X-Mailer: PHP \r\n";
                $headers[] = 'From: '.$sender.' < '.$email.'>' . "\r\n";
                
                $mail = wp_mail( $to, $subject, $message, $headers );
				
                if( $mail ){
                   echo 'Check your email address for you new password.';
                    
            } else {
                $error = 'Oops something went wrong updaing your account.';
            }
    }
 
        }
            
	}
}
