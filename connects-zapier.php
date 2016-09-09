<?php
/**
* Plugin Name: Connects - MyAddon Addon
* Plugin URI: 
* Description: Use this plugin to integrate MyAddon with Connects..
* Version: 1.0
* Author: Your Name
* Author URI: https://www.example.com/
* License:
*/

if(!class_exists('Smile_Mailer_Myaddon')){
	class Smile_Mailer_Myaddon{

	
		//Class variables
		private $slug;
		private $setting;
		
		/*
		 * Function Name: __construct
		 * Function Description: Constructor
		 */
		
		function __construct(){
			add_action( 'admin_init', array( $this, 'enqueue_scripts' ) );

			// API libraries if any
			require_once('lib/api.class.php');

			// Actions to perform get lists, add subscriber, disconnect mailer, etc.
			add_action( 'wp_ajax_get_myaddon_data', array($this,'get_myaddon_data' ));
			add_action( 'wp_ajax_update_myaddon_authentication', array($this,'update_myaddon_authentication' ));
			add_action( 'wp_ajax_disconnect_myaddon', array($this,'disconnect_myaddon' ));
			add_action( 'wp_ajax_myaddon_add_subscriber', array($this,'myaddon_add_subscriber' ));
			add_action( 'wp_ajax_nopriv_myaddon_add_subscriber', array($this,'myaddon_add_subscriber' ));

			// Settings for mailer
			$this->setting  = array(
				'name' => 'MyAddon', //Display name
				'parameters' => array( 'api_key' ), // Credentials input parameters
				'where_to_find_url' => 'https://app.myaddon.com/account/api_keys',
				'logo_url' => plugins_url('images/logo.png', __FILE__)
			);
			
			// Mandatory to specify.
			// See to it this slug matches the slug in all function names.
			// Ex. myaddon => wp_ajax_update_myaddon_authentication
			$this->slug = 'myaddon';
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
		 * Function Name: get_myaddon_data
		 * Function Description: Get MyAddon input fields
		 */
		 
		function get_myaddon_data(){
			$isKeyChanged = false;
			$connected = false;

			ob_start();
			
			$myaddon_api_key = get_option($this->slug.'_api_key');
			
			if( $myaddon_api_key != '' ) {

				// Your API call to make connection goes here
            	// Make connection to MyAddon account
            	// $result will have connection details
            	// This piece of code checks if API key/ credentials are changed

				if( $result == false ) {
					$formstyle = '';
					$isKeyChanged = true;	
				} else {
					$formstyle = 'style="display:none;"';
				}
			} else {
            	$formstyle = '';
			}
            ?>
            <div class="bsf-cnlist-form-row" <?php echo $formstyle; ?> >
				<label for="<?php echo $this->slug; ?>_api_key"><?php _e( $this->setting['name']." API Key", "smile" ); ?></label>            
	            <input type="text" autocomplete="off" id="<?php echo $this->slug; ?>_api_key" name="<?php echo $this->slug; ?>-api-key" value="<?php echo esc_attr( $myaddon_api_key ); ?>"/>
	        </div>
			
			<div class="bsf-cnlist-form-row <?php echo $this->slug; ?>-list">
                <?php
                if( $myaddon_api_key != '' && !$isKeyChanged ) {
                    $myaddon_lists = $this->get_myaddon_lists( $myaddon_api_key );

                    if( !empty( $myaddon_lists ) ){
                        $connected = true;
                    ?>
                    <label for="<?php echo $this->slug; ?>-list"><?php echo __( "Select List", "smile" ); ?></label>
                        <select id="<?php echo $this->slug; ?>-list" class="bsf-cnlist-select" name="<?php echo $this->slug; ?>-list">
                        <?php
                        foreach($myaddon_lists as $id => $name) {
                        ?>
                        <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                        <?php
                        }
                        ?>
                        </select>
                        <?php
                    } else {
                    ?>
                        <label for="<?php echo $this->slug; ?>-list"><?php echo __( "You need at least one list added in " . $this->setting['name'] . " before proceeding.", "smile" ); ?></label>
                    <?php
                    }
                }
                ?>
            </div>

            <div class="bsf-cnlist-form-row">
                <?php if( $myaddon_api_key == "" ) { ?>
                    <button id="auth-<?php echo $this->slug; ?>" class="button button-secondary auth-button" disabled><?php _e( "Authenticate " . $this->setting['name'], "smile" ); ?></button><span class="spinner" style="float: none;"></span>
                <?php } else {
                        if( $isKeyChanged ) {
                ?>
                    <div id="update-<?php echo $this->slug; ?>" class="update-mailer" data-mailerslug="<?php echo $this->setting['name']; ?>" data-mailer="<?php echo $this->slug; ?>"><span><?php _e( "Your credentials seems to be changed.</br>Use different '". $this->setting['name'] ."' credentials?", "smile" ); ?></span></div><span class="spinner" style="float: none;"></span>
                <?php
                        } else {
                ?>
                    <div id="disconnect-<?php echo $this->slug; ?>" class="button button-secondary" data-mailerslug="<?php echo $this->setting['name']; ?>" data-mailer="<?php echo $this->slug; ?>"><span><?php _e( "Use different '".$this->setting['name']."' account?", "smile" ); ?></span></div><span class="spinner" style="float: none;"></span>
                <?php
                        }
                ?>
                <?php } ?>
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
		 * Function Name: update_myaddon_authentication
		 * Function Description: Update MyAddon values to ConvertPlug
		 */
		
		function update_myaddon_authentication(){
			$myaddon_api_key = $_POST[$this->slug.'_api_key'];
			if( $_POST[$this->slug.'_api_key'] == "" ){
				print_r(json_encode(array(
					'status' => "error",
					'message' => __( "Please provide valid API Key for your " . $this->setting['name'] . " account.", "smile" )
				)));
				exit();
			}
			
			ob_start();
			try{

				// Your bussiness logic / API call goes here.
				// $campaigns has the list array/object returned from API call.

			} catch( Exception $ex ){
				print_r(json_encode(array(
					'status' => "error",
					'message' => 'Error message goes here.'
				)));
				exit();
			}
			

			if( /*success*/ )  {
				if( $campaigns == '' ) {
					 print_r(json_encode(array(
	                    'status' => "error",
	                    'message' => __( "You have zero lists in your " . $this->setting['name'] . " account. You must have at least one list before integration." , "smile" )
	                )));
	                exit();
				}

				if( $campaigns != '' ) {
					$query = '';
				?>
				<label for="<?php echo $this->slug; ?>-list">Select List</label>
				<select id="<?php echo $this->slug; ?>-list" class="bsf-cnlist-select" name="<?php echo $this->slug; ?>-list">
				<?php
					foreach ($campaigns as $key => $cm) {
						$query .= $cm['id'].'|'.$cm['name'].',';
						$constContact_lists[$cm['id']] = $cm['name'];
				?>
					<option value="<?php echo $cm['id']; ?>"><?php echo $cm['name']; ?></option>
				<?php
					}
				?>
				</select>
				<input type="hidden" id="mailer-all-lists" value="<?php echo $query; ?>"/>
				<input type="hidden" id="mailer-list-action" value="update_<?php echo $this->slug; ?>_list"/>
				<div class="bsf-cnlist-form-row">
					<div id="disconnect-<?php echo $this->slug; ?>" class="" data-mailerslug="<?php echo $this->setting['name']; ?>" data-mailer="<?php echo $this->slug; ?>">
						<span>
							<?php _e( "Use different '" . $this->setting['name'] . "' account?", "smile" ); ?>
						</span>
					</div>
					<span class="spinner" style="float: none;"></span>
				</div>
				<?php
				} else {
				?>
					<label for="<?php echo $this->slug; ?>-list"><?php echo __( "You need at least one list added in " . $this->setting['name'] . " before proceeding.", "smile" ); ?></label>
				<?php
				}

			} else {
				print_r(json_encode(array(
					'status' => "error",
					'message' => "Error message goes here."
				)));
				exit();
			}
			
			$html = ob_get_clean();

			update_option( $this->slug.'_api_key', $myaddon_api );
			update_option( $this->slug.'_lists', $myaddon_lists );

			print_r(json_encode(array(
				'status' => "success",
				'message' => $html
			)));

			exit();
		}
		
		
		/*
		 * Function Name: myaddon_add_subscriber
		 * Function Description: Add subscriber
		 */
		
		function myaddon_add_subscriber(){
			$ret = true;
			$email_status = false;
            $style_id = isset( $_POST['style_id'] ) ? $_POST['style_id'] : '';
            $contact = $_POST['param'];
            $contact['source'] = ( isset( $_POST['source'] ) ) ? $_POST['source'] : '';
            $msg = isset( $_POST['message'] ) ? $_POST['message'] : __( 'Thanks for subscribing. Please check your mail and confirm the subscription.', 'smile' );
			
			//	Check Email in MX records
			if( isset( $_POST['param']['email'] ) ) {
                $email_status = ( !( isset( $_POST['only_conversion'] ) ? true : false ) ) ? apply_filters('cp_valid_mx_email', $_POST['param']['email'] ) : false;
            }

			$myaddon_api_key = $_POST[$this->slug.'_api_key'];
			
			if( $email_status ) {
				if( function_exists( "cp_add_subscriber_contact" ) ){
					$isuserupdated = cp_add_subscriber_contact( $_POST['option'] , $contact );
				}

				if ( !$isuserupdated ) {  // if user is updated dont count as a conversion
					// update conversions 
					smile_update_conversions( $style_id );
				}
				if( isset( $_POST['param']['email'] ) ) {
					$status = 'success';
					try {
						
						// Your API call to add a subscriber to mailer goes here.
						// Your API call to assign given list_id to the contact goes here.
						// list_id is alloted to $_POST['list_id'] variable

					} catch ( Exception $ex ) {
						if( isset( $_POST['source'] ) ) {
			        		return false;
			        	} else {
			        		print_r(json_encode(array(
								'action' => ( isset( $_POST['message'] ) ) ? 'message' : 'redirect',
								'email_status' => $email_status,
								'status' => 'error',
								'message' => __( "Something went wrong. Please try again.", "smile" ),
								'url' => ( isset( $_POST['message'] ) ) ? 'none' : $_POST['redirect'],
							)));
							exit();
			        	}
					}

					if( /* Failes */ )  {
						if( isset( $_POST['source'] ) ) {
			        		return false;
			        	} else {
			        		print_r(json_encode(array(
								'action' => ( isset( $_POST['message'] ) ) ? 'message' : 'redirect',
								'email_status' => $email_status,
								'status' => 'error',
								'message' => __( "Something went wrong. Please try again.", "smile" ),
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
				} else {
					$msg = ( isset( $_POST['msg_wrong_email']  )  && $_POST['msg_wrong_email'] !== '' ) ? $_POST['msg_wrong_email'] : __( 'Please enter correct email address.', 'smile' );
					$status = 'error';
					$ret = false;
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
					'url' => ( isset( $_POST['message'] ) ) ? 'none' : $_POST['redirect'],
				)));

				exit();
        	}
		}
		
		/*
		* Function Name: disconnect_myaddon
		* Function Description: Disconnect current TotalSend from wp instance
		*/
		
		function disconnect_myaddon(){
			delete_option( $this->slug.'_api_key' );


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

		/*
		 * Function Name: get_myaddon_lists
		 * Function Description: Get MyAddon Mailer Campaign list
		 */

		function get_myaddon_lists( $myaddon_api_key = '' ) {
			if( $myaddon_api_key != '' ) {

				// Your API call to get all lists from mailer goes here.
				// The array/object of mailer list is stored in $campaigns variable
	        	
				if( /*Success*/ )  {
					$lists = array();
					foreach($campaigns as $offset => $cm) {
						$lists[$cm['id']] = $cm['name'];
					}
					return $lists;
				} else {
					return array();
				}
			} else {
				return array();
			}
		}
	}
	new Smile_Mailer_Myaddon;	
}
?>