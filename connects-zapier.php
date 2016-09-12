<?php
/**
* Plugin Name: Connects - Zapier Addon
* Plugin URI:
* Description: Use this plugin to integrate Zapier with Connects..
* Version: 0.0.1
* Author: Bryce Hamrick
* Author URI: https://bhamrick.com/
*/

if(!class_exists('Smile_Mailer_Zapier')){
	class Smile_Mailer_Zapier{


		//Class variables
		private $slug;
		private $setting;

		/*
		 * Function Name: __construct
		 * Function Description: Constructor
		 */

		function __construct(){
			add_action( 'admin_init', array( $this, 'enqueue_scripts' ) );

			// Actions to perform get lists, add subscriber, disconnect mailer, etc.
			add_action( 'wp_ajax_get_zapier_data', array($this,'get_zapier_data' ));
			add_action( 'wp_ajax_update_zapier_authentication', array($this,'update_zapier_authentication' ));
			add_action( 'wp_ajax_disconnect_zapier', array($this,'disconnect_zapier' ));
			add_action( 'wp_ajax_zapier_add_subscriber', array($this,'zapier_add_subscriber' ));
			add_action( 'wp_ajax_nopriv_zapier_add_subscriber', array($this,'zapier_add_subscriber' ));

			// Settings for mailer
			$this->setting  = array(
				'name' => 'Zapier', //Display name
				'parameters' => array(),
				'where_to_find_url' => 'https://zapier.com/',
				'logo_url' => plugins_url('images/logo.png', __FILE__)
			);

			// Mandatory to specify.
			// See to it this slug matches the slug in all function names.
			// Ex. zapier => wp_ajax_update_zapier_authentication
			$this->slug = 'zapier';
		}


		/*
		 * Function Name: enqueue_scripts
		 * Function Description: Add custon scripts
		 */

		function enqueue_scripts() {
			if( function_exists( 'cp_register_addon' ) ) {
				cp_register_addon( $this->slug, $this->setting );
			}
			wp_register_script( $this->slug.'-script', plugins_url('js/' . $this->slug . '-script.js', __FILE__), array('jquery'), '1.1', true );
			wp_enqueue_script( $this->slug.'-script' );
			add_action( 'admin_head', array( $this, 'hook_css' ) );
		}

		/*
		 * Function Name: hook_css
		 * Function Description: Adds background style script for mailer logo.
		 */


		function hook_css() {
			if( isset( $this->setting['logo_url'] ) ) {
				if( $this->setting['logo_url'] != '' ) {
					$style = '<style>table.bsf-connect-optins td.column-provider.'.$this->slug.'::after {background-image: url("'.$this->setting['logo_url'].'");}.bend-heading-section.bsf-connect-list-header .bend-head-logo.'.$this->slug.'::before {background-image: url("'.$this->setting['logo_url'].'");}</style>';
					echo $style;
				}
			}

		}

		/*
		 * Function Name: get_zapier_data
		 * Function Description: Get Zapier input fields
		 */

		function get_zapier_data(){
			$connected = true;

			ob_start();
            ?>

						<div class="bsf-cnlist-form-row <?php echo $this->slug; ?>-list">
						  <label for="<?php echo $this->slug; ?>-list"><?php echo __( "Zapier Webook URL", "smile" ); ?></label>
						  <input type="text" id="<?php echo $this->slug; ?>-list" name="<?php echo $this->slug; ?>-list" />
						</div>

            <?php
            $content = ob_get_clean();

            $result['data'] = $content;
            $result['helplink'] = $this->setting['where_to_find_url'];
            $result['isconnected'] = $connected;
            echo json_encode($result);
            exit();
        }


		/*
		 * Function Name: update_zapier_authentication
		 * Function Description: Update Zapier values to ConvertPlug
		 */

		function update_zapier_authentication(){
			ob_start();
					$query = '';
				?>
				<label for="<?php echo $this->slug; ?>-list"><?php echo __( "Zapier Webook URL", "smile" ); ?></label>
				<input type="text" id="<?php echo $this->slug; ?>-list" name="<?php echo $this->slug; ?>-list" />
				<input type="hidden" id="mailer-all-lists" value="<?php echo $query; ?>"/>
				<input type="hidden" id="mailer-list-action" value="update_<?php echo $this->slug; ?>_list"/>
				<?php

			$html = ob_get_clean();

			print_r(json_encode(array(
				'status' => "success",
				'message' => $html
			)));

			exit();
		}


		/*
		 * Function Name: zapier_add_subscriber
		 * Function Description: Add subscriber
		 */

		function zapier_add_subscriber(){
			$ret = true;
			$email_status = false;
            $style_id = isset( $_POST['style_id'] ) ? $_POST['style_id'] : '';
            $contact = $_POST['param'];
            $contact['source'] = ( isset( $_POST['source'] ) ) ? $_POST['source'] : '';
            $msg = isset( $_POST['message'] ) ? $_POST['message'] : __( 'Thanks for subscribing. Please check your mail and confirm the subscription.', 'smile' );

            if ( is_user_logged_in() && current_user_can( 'access_cp' ) ) {
                $default_error_msg = __( 'THERE APPEARS TO BE AN ERROR WITH THE CONFIGURATION.', 'smile' );
            } else {
                $default_error_msg = __( 'THERE WAS AN ISSUE WITH YOUR REQUEST. Administrator has been notified already!', 'smile' );
            }

			$zapier_list_id = $_POST['list_id'];

			//	Check Email in MX records
			if( isset( $_POST['param']['email'] ) ) {
                $email_status = ( !( isset( $_POST['only_conversion'] ) ? true : false ) ) ? apply_filters('cp_valid_mx_email', $_POST['param']['email'] ) : false;
            }

			if( $email_status ) {
				if( function_exists( "cp_add_subscriber_contact" ) ){
					$isuserupdated = cp_add_subscriber_contact( $_POST['option'] , $contact );
				}

				if ( !$isuserupdated ) {  // if user is updated dont count as a conversion
					// update conversions
					smile_update_conversions($style_id);
				}
				if( isset( $_POST['param']['email'] ) ) {
					$status = 'success';
					$errorMsg =  '';

					$ch = curl_init( $zapier_list_id );

					curl_setopt( $ch,CURLOPT_POST, 2 );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $_POST['param'] ) );
					curl_setopt( $ch, CURLOPT_FAILONERROR, 1 );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
					curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

					$response = curl_exec($ch);
					$http_response_error = curl_error($ch);

					if( $http_response_error != '' )  {
						if( isset( $_POST['source'] ) ) {
			        		return false;
			        	} else {
			        		//var_dump($http_response_error);
			        		if(strpos($http_response_error, '404')){
			        			$errorMsg =  __('ListId is not present', 'smile' );
			        		}else{
			        			$errorMsg = $http_response_error;
			        		}

			        		if ( is_user_logged_in() && current_user_can( 'access_cp' ) ) {
				                $detailed_msg = $errorMsg;
				            } else {
				                $detailed_msg = '';
				            }
				            if( $detailed_msg !== '' & $detailed_msg !== null ) {
				                $page_url = isset( $_POST['cp-page-url'] ) ? $_POST['cp-page-url'] : '';

				                // notify error message to admin
				                if( function_exists('cp_notify_error_to_admin') ) {
				                    $result   = cp_notify_error_to_admin($page_url);
				                }
				            }

			        		print_r(json_encode(array(
								'action' => ( isset( $_POST['message'] ) ) ? 'message' : 'redirect',
								'email_status' => $email_status,
								'status' => 'error',
								'message' => $default_error_msg,
								'detailed_msg' => $detailed_msg,
								'url' => ( isset( $_POST['message'] ) ) ? 'none' : $_POST['redirect'],
							)));
							exit();
			        	}

					}
				}

			} else {
				if( isset( $_POST['only_conversion'] ) ? true : false ){
					// update conversions
					$status = 'success';
					smile_update_conversions( $style_id );
					$ret = true;
				} else if( isset( $_POST['param']['email'] ) ) {
                    $msg = ( isset( $_POST['msg_wrong_email']  )  && $_POST['msg_wrong_email'] !== '' ) ? $_POST['msg_wrong_email'] : __( 'Please enter correct email address.', 'smile' );
                    $status = 'error';
                    $ret = false;
                } else if( !isset( $_POST['param']['email'] ) ) {
                    //$msg = __( 'Something went wrong. Please try again.', 'smile' );
                    $msg  = $default_error_msg;
                    $errorMsg = __( 'Email field is mandatory to set in form.', 'smile' );
                    $status = 'error';
                }
			}

			if ( is_user_logged_in() && current_user_can( 'access_cp' ) ) {
                $detailed_msg = $errorMsg;
            } else {
                $detailed_msg = '';
            }

            if( $detailed_msg !== '' & $detailed_msg !== null ) {
                $page_url = isset( $_POST['cp-page-url'] ) ? $_POST['cp-page-url'] : '';

                // notify error message to admin
                if( function_exists('cp_notify_error_to_admin') ) {
                    $result   = cp_notify_error_to_admin($page_url);
                }
            }

			if( isset( $_POST['source'] ) ) {
        		return $ret;
        	} else {
        		print_r(json_encode(array(
					'action' => ( isset( $_POST['message'] ) ) ? 'message' : 'redirect',
					'email_status' => $email_status,
					'status' => $status,
					'message' => $msg,
					'detailed_msg' => $detailed_msg,
					'url' => ( isset( $_POST['message'] ) ) ? 'none' : $_POST['redirect'],
				)));
				exit();
        	}
		}

		/*
		* Function Name: disconnect_zapier
		* Function Description: Disconnect current Zapier from wp instance
		*/

		function disconnect_zapier(){
			$smile_lists = get_option('smile_lists');
			if( !empty( $smile_lists ) ){
				foreach( $smile_lists as $key => $list ) {
					$provider = $list['list-provider'];
					if( strtolower( $provider ) == strtolower( $this->slug ) ){
						$smile_lists[$key]['list-provider'] = "Convert Plug";
						$contacts_option = "cp_" . $this->slug . "_" . preg_replace( '#[ _]+#', '_', strtolower( $list['list-name'] ) );
                        $contact_list = get_option( $contacts_option );
                        $deleted = delete_option( $contacts_option );
                        $status = update_option( "cp_connects_" . preg_replace( '#[ _]+#', '_', strtolower( $list['list-name'] ) ), $contact_list );
					}
				}
				update_option( 'smile_lists', $smile_lists );
			}

			print_r(json_encode(array(
                'message' => "disconnected",
			)));
			die();
		}
	}
	new Smile_Mailer_Zapier;
}
?>
