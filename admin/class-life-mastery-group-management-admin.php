<?php
require ZOOM_VIDEO_CONFERENCE_PLUGIN_DIR_PATH . '/vendor/autoload.php';

use \Firebase\JWT\JWT;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://unaibamir.com
 * @since      1.0.0
 *
 * @package    Life_Mastery_Group_Management
 * @subpackage Life_Mastery_Group_Management/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Life_Mastery_Group_Management
 * @subpackage Life_Mastery_Group_Management/admin
 * @author     Unaib Amir <unaibamiraziz@gmail.com>
 */
class Life_Mastery_Group_Management_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public $zoom_api_key;

	public $zoom_api_secret;

	private $api_url = 'https://api.zoom.us/v2/';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->setup_vars();
	}

	private function setup_vars() {
		$this->zoom_api_key    = esc_html( get_option( 'zoom_api_key' ) );
		$this->zoom_api_secret = esc_html( get_option( 'zoom_api_secret' ) );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Life_Mastery_Group_Management_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Life_Mastery_Group_Management_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/life-mastery-group-management-admin.css', array(), $this->version, 'all' );


		if ( ! wp_style_is( 'video-conferencing-with-zoom-api-iframe' ) ) {
			wp_enqueue_style( 'video-conferencing-with-zoom-api-iframe' );

			if ( is_rtl() ) {
				wp_add_inline_style( 'video-conferencing-with-zoom-api-iframe', 'body ul.zoom-meeting-countdown{ direction: ltr; }' );
			}
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Life_Mastery_Group_Management_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Life_Mastery_Group_Management_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		if ( ! wp_style_is( 'wplms-style' ) && ! wp_script_is( 'bootstrap' ) && ! wp_script_is( 'bootstrap-modal' ) ) {
			wp_register_script( 'video-conferencing-with-zoom-api-modal', ZOOM_VIDEO_CONFERENCE_PLUGIN_FRONTEND_JS_PATH . '/bootstrap-modal.min.js', array( 'jquery' ), ZVCW_ZOOM_PLUGIN_VER, true );
			wp_enqueue_script( 'video-conferencing-with-zoom-api-modal' );
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/life-mastery-group-management-admin.js', array( 'jquery' ), time(), false );

	}


	public function add_meta_boxes() {
		add_meta_box(
            'lm-group-tag-bos',
            __( 'InfusionSoft Member Tag', 'textdomain' ),
            array( $this, 'render_metabox' ),
            'groups',
            'advanced',
            'default'
        );
	}


	public function render_metabox( $post ) {
		global $wpdb;

		$tags_query = "SELECT TagId, GroupName FROM _isContactGroup WHERE GroupName=TRIM(GroupName) ORDER BY GroupName ASC";
		$tags = $wpdb->get_results( $tags_query );
		$group_tag = get_post_meta( $post->ID, 'lm_group_tag', true );
		
		?>
		<table class="form-table">
			<tr>
				<th><label for="lm_group_tag"><?php echo __('Select Group Tag'); ?></label></th></th>
				<td>
					<select name="lm_group_tag" id="lm_group_tag" class="lm_group_tag lm-select2 mbr-select2">
						<?php foreach ($tags as $tag) {
							echo '<option value="'. $tag->TagId .'" '. selected( $group_tag, $tag->TagId ) .' >'. $tag->GroupName .'</option>';
						} ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="lm_drip_lessons"><?php echo __('Drip Course Lessons'); ?></label></th></th>
				<td>
					<input type="checkbox" id="lm_drip_lessons" name="lm_drip_lessons" value="yes">
					<p class="description">Automatically generate group course dates (based on date in the Infusionsoft tag) and drip dates for the course lessons (based on the generated dates).</p>
					<p class="description">NOTE: Checking the box will reset any date changes made by the Group Leader!</p>
				</td>
			</tr>
		</table>
		<?php
		
	}

	public function ld_group_save_post( $post_id, $post, $update ) {

		global $wpdb;

		$group_id = $post_id;

		// Only set for post_type = post!
	    if ( 'groups' !== $post->post_type ) {
	        return;
	    }

	    if ( wp_is_post_revision( $group_id ) ) {
        	return;
        }

        update_post_meta( $group_id, 'lm_group_tag', $_POST['lm_group_tag'] );
        
        if( !isset($_POST['lm_drip_lessons']) ) {
        	return;
        }

        $group_data 	= get_post_meta( $group_id, 'lm_group_data', true );
        $tag_id 		= $_POST['lm_group_tag'];
        $tag_query 		= "SELECT GroupName FROM _isContactGroup WHERE TagId={$tag_id}";
        $tag_name 		= $wpdb->get_col( $tag_query );
        $tag_name 		= $tag_name[0];
        
        $tag_info 		= explode(' - ', $tag_name);
        $start_date 	= $tag_info[1];

        update_post_meta( $group_id, 'lm_course_start_date', $start_date );

        $weeks 			= LM_Helper::get_group_course_weeks( $group_id );

        $discuss_dates 	= LM_Helper::generate_lesson_discuss_dates( $group_id, $weeks, $start_date );
        
        $lesson_dates 	= LM_Helper::generate_lesson_dates( $group_id, $weeks, $start_date );

        $course_lesson_weeks = LM_Helper::get_group_course_lesson_weeks( $group_id );
        
        array_unshift($course_lesson_weeks, array(9999999));

        LM_Helper::drip_admin_group_lessons( $group_id );
        
        foreach ($lesson_dates as $key => $lesson_date) {
        	$old_date = explode('-', $lesson_date);
        	$new_date = $old_date[1] . '/' . $old_date[2] . '/' . $old_date[0];
        	$lesson_dates[ $key ] = $new_date;
        }

        foreach ($discuss_dates as $key => $discuss_date) {
        	if( $key <= 1 ) {
        		//continue;
        	} 
        	$old_date = explode('-', $discuss_date);
        	$new_date = $old_date[1] . '/' . $old_date[2] . '/' . $old_date[0];
        	$discuss_dates[ $key ] = $new_date;
        }
        
        $data = array(
        	'lesson_dates'			=>	$lesson_dates,
        	'lesson_review_dates'	=>	$discuss_dates,
        	'lm_lessons'			=>	$course_lesson_weeks,
        	'users'					=>	isset($group_data['users']) && !empty($group_data['users']) ? $group_data['users'] : array(),
        	's_users'				=>	isset($group_data['s_users']) && !empty($group_data['s_users']) ? $group_data['s_users'] : array()
        );


        update_post_meta( $group_id, 'lm_group_data', $data, '' );

        update_post_meta( $group_id, 'lm_group_attendance_dates', $discuss_dates, '' );

	}


	public function add_zoom_meta_boxes() {
		add_meta_box(
            'lm-group-zoom-bos',
            __( 'Zoom Settings', 'textdomain' ),
            array( $this, 'render_zoom_metabox' ),
            'groups',
            'advanced',
            'default'
        );
	}


	public function render_zoom_metabox( $post ) {
		global $wpdb;
		$zoom_meeting_id 		= get_post_meta( $post->ID, 'lm_group_zoom_meeting_id', true );
		$meeting_data 			= !empty($zoom_meeting_id) ? json_decode( zoom_conference()->getMeetingInfo( $zoom_meeting_id ) ) : false;
		
		?>
		<table class="form-table">
			<tr>
				<th><label for="lm_group_zoom"><?php echo __('Create Group\'s Zoom Meeting'); ?></label></th></th>
				<td>
					<input type="checkbox" id="lm_group_zoom" name="lm_group_zoom" value="yes" <?php echo empty($meeting_data->code) ? 'disabled' : ''; ?>>
					<p class="description">Automatically generate group's zoom meeting. </p>
				</td>
			</tr>

			<?php if( empty($meeting_data->code) ): ?>
				<?php
				$meeting_edit = add_query_arg(array(
					'page'		=>	'zoom-video-conferencing-add-meeting',
					'edit'		=>	$meeting_data->id,
					'host_id'	=>	$meeting_data->host_id
				), admin_url( 'admin.php' ));
				?>
				<tr>
					<th>Zoom Meeting</th>
					<td>
						<a href="<?php echo $meeting_edit; ?>">
							<?php echo $meeting_data->topic; ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
		
	}

	public function ld_group_save_zoom( $post_id, $post, $update ) {
		global $wpdb;

		$group_id = $post_id;

		// Only set for post_type = post!
	    if ( 'groups' !== $post->post_type ) {
	        return;
	    }

	    if ( wp_is_post_revision( $group_id ) ) {
        	return;
        }

        if( !isset($_POST['lm_group_zoom']) ) {
        	return;
        }

        $group_data 		= get_post_meta( $group_id, 'lm_group_data', true );
        $group_title 		= get_the_title( $group_id );
        $meeting_topic 		= $group_title . " Meeting";
        $group_edit_link 	= get_edit_post_link( $group_id );
        $group_admins 		= learndash_get_groups_administrators( $group_id );
        $host_admin 		= get_field('zoom_meeting_host', 'option');
        $zoom_users 		= video_conferencing_zoom_get_possible_hosts();
        $zoom_host 			= isset($host_admin) ? $zoom_users[$host_admin->ID] : false ;
        $zoom_meeting_id 	= get_post_meta( $group_id, 'lm_group_zoom_meeting_id', true );

        $meeting_data 		= !empty($zoom_meeting_id) ? json_decode( zoom_conference()->getMeetingInfo( $zoom_meeting_id ) ) : false;
        if( empty($meeting_data->code) ) {
        	return;
        }
        
        if( !isset($zoom_host['host_id']) || isset($zoom_host['host_id']) && empty($zoom_host['host_id']) ) {
        	$redirect_link = add_query_arg( 'message', 'lm_no_zoom_host', $redirect_link );
        	wp_safe_redirect( $redirect_link );
			exit;
        }

        $timezone 			= "America/Phoenix";
        $group_data  		= get_post_meta( $group_id, 'lm_group_data', true );

        if( empty($group_data) || !isset($group_data['lesson_review_dates']) || empty($group_data['lesson_review_dates'][0]) ) {
        	return;
        }

        //$group_start_date  	= get_post_meta( $group_id, 'lm_course_start_date', true );
        $group_start_date	= $group_data['lesson_review_dates'][0];
        $group_end_date		= $group_data['lesson_review_dates'][13];
		$meeting_time 		= get_option( 'options_zoom_meeting_time', '00:00:00' );

		$date           	= new DateTime( $group_start_date .' '.$meeting_time, new DateTimeZone( $timezone ) );
		$date->setTimezone( new DateTimeZone( 'UTC' ) );
		$start_time 		= $date->format( 'Y-m-d\TH:i:s\Z' );

		$end_date           = new DateTime( $group_end_date.' '.$meeting_time, new DateTimeZone( $timezone ) );
		$end_date->setTimezone( new DateTimeZone( 'UTC' ) );
		$end_date 			= $end_date->format( 'Y-m-d\TH:i:s\Z' );
        
        $zoom_meeting_args 	= array(
        	'timezone'		=>	$timezone,
        	'start_time'	=>	$start_time,
        	'duration'		=>	120,
        	'agenda'		=>	'',
        	'topic'			=>	$meeting_topic,
        	'type'			=>	8,
        	'password'		=>	$group_id,
        	'settings'		=>	array(
				'meeting_authentication'	=>	false,
				'waiting_room'				=>	false,
				'join_before_host'			=>	true,
				'host_video'				=>	false,
				'participant_video'			=>	true,
				'mute_upon_entry'			=>	false,
				'enforce_login'				=>	true,
				'auto_recording'			=>	'cloud',
				'alternative_hosts'			=>	'',
			),
			'recurrence'	=>	array(
				'type'						=>	2,
				//'repeat_interval'			=>	14,
				'end_date_time'				=>	$end_date,
				'weekly_days'				=>	5
			)
        );
        
        
        $zoom_meeting_args 		= $zoom_meeting_args;
        $zoom_meeting_request 	= 'users/' . $zoom_host['host_id'] . '/meetings';
        //$zoom_meeting_args 		= apply_filters( 'zoom_wp_before_create_meeting', $zoom_meeting_args );

        try {
        	
        	$meeting_created 		= json_decode( $this->sendRequest( $zoom_meeting_request, $zoom_meeting_args, 'POST' ) );
        } catch ( Exception $e ) {
			video_conferencing_zoom_log_error( $e->getMessage() );
		}
        
        if ( ! empty( $meeting_created->code ) ) {
        	video_conferencing_zoom_log_error( $meeting_created->message );
        	delete_post_meta( $group_id, 'lm_group_zoom_meeting_id' );
        
		} else {
			/**
			 * Fires after meeting has been Created
			 *
			 * @since  2.0.1
			 *
			 * @param meeting_id , Host_id
			 */
			$meeting_time = '';
			try {
				if ( isset( $meeting_created->start_time ) ) {
					$meeting_time = video_conferencing_zoom_convert_time_to_local( $meeting_created->start_time, $meeting_created->timezone );
				}

				if ( isset( $meeting_created->id ) ) {

					update_user_meta( $host_admin->ID, 'zoom_meeting_id_' . (float) $meeting_created->id, $meeting_created->host_id );

					update_post_meta( $group_id, 'lm_group_zoom_meeting_id', $meeting_created->id );

					video_conferencing_zoom_update_meetings_list( $meeting_created );

					do_action( 'zoom_wp_after_create_meeting', $meeting_created, $meeting_time );

					$this->check_sync_meeting();
					
				}
			} catch ( Exception $e ) {
				video_conferencing_zoom_log_error( $e->getMessage() );
				delete_post_meta( $group_id, 'lm_group_zoom_meeting_id' );
			}

		}

	}
	
	//function to generate JWT
	public function generateJWTKey() {

		$key    = $this->zoom_api_key;
		$secret = $this->zoom_api_secret;

		$token = array(
			'iat' => time(),
			'aud' => null,
			'iss' => $key,
			'exp' => time() + strtotime( '+60 minutes' ),
		);
		
		return JWT::encode( $token, $secret );
	}

	protected function sendRequest( $calledFunction, $data, $request = 'GET', $log = true ) {
		$request_url = $this->api_url . $calledFunction;
		$args        = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->generateJWTKey(),
				'Content-Type'  => 'application/json',
			),
			'timeout' => 60,
		);

		try {
			set_time_limit( 0 );
			$response = new stdClass();
			$response = $this->makeRequest( $request_url, $args, $data, $request, $log );
			// check if response contains multiple pages
			if ( $this->data_list && isset( $response->{$this->return_object} ) ) {
				$response->{$this->return_object} = $this->data_list;
			}
			$response = json_encode( $response );
		} catch ( Exception $e ) {
			video_conferencing_zoom_log_error( $e->getMessage() );
		}

		return $response;
	}

	// Send API request to Zoom
	public function makeRequest( $request_url, $args, $data, $request = 'GET', $log = true ) {
		if ( 'GET' == $request ) {
			$args['body'] = ! empty( $data ) ? $data : array();
			$response     = wp_remote_get( $request_url, $args );
		} elseif ( 'DELETE' == $request ) {
			$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
			$args['method'] = 'DELETE';
			$response       = wp_remote_request( $request_url, $args );
		} elseif ( 'PATCH' == $request ) {
			$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
			$args['method'] = 'PATCH';
			$response       = wp_remote_request( $request_url, $args );
		} elseif ( 'PUT' == $request ) {
			$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
			$args['method'] = 'PUT';
			$response       = wp_remote_post( $request_url, $args );
		} else {
			$args['body']   = ! empty( $data ) ? json_encode( $data ) : array();
			$args['method'] = 'POST';
			$response       = wp_remote_post( $request_url, $args );
		}

		if ( is_wp_error( $response ) ) {
			video_conferencing_zoom_log_error( $response->get_error_message() );
			return false;
		}

		$response = wp_remote_retrieve_body( $response );

		if ( ! $response || '' == $response ) {
			return false;
		}

		$check_response = new stdClass();
		$check_response = json_decode( $response );
		if ( isset( $check_response->code ) || isset( $check_response->error ) ) {
			if ( $log ) {
				video_conferencing_zoom_log_error( $check_response->message );
			}
		}

		// Fetch the next page of the request
		if ( isset( $check_response->next_page_token )
			&& $check_response->next_page_token
			&& $this->return_object
		) {
			$data['next_page_token'] = $check_response->next_page_token;

			// If data received then fetch other pages too
			if ( $check_response->{$this->return_object} ) {
				$this->data_list = array_merge( $this->data_list, $check_response->{$this->return_object} );
			}

			return $this->makeRequest( $request_url, $args, $data, $request, $log );
		} elseif ( $this->data_list ) {
			// Get last page data in a multi request mode
			$this->data_list = array_merge( $this->data_list, $check_response->{$this->return_object} );
		}

		return $check_response;
	}

	


	/**
	 * Sync Meetings List
	 *
	 * @since   4.7.2
	 * @changes in CodeBase
	 * @author  Adeel
	 */
	public static function check_sync_meeting() {		
		global $wpdb;

		$results = $wpdb->get_results(
			"SELECT post_id, meta_key, meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = 'zoom_api_meeting_options' OR meta_key = 'zoom_api_webinar_options'",
			ARRAY_A
		);

		$host_admin 		= get_field('zoom_meeting_host', 'option');
        $zoom_users 		= video_conferencing_zoom_get_possible_hosts();
        $zoom_host 			= isset($host_admin) ? $zoom_users[$host_admin->ID] : false ;

		// Clear Meetings Listing
		$meetings = video_conferencing_zoom_api_get_meeting_list( $zoom_host['host_id'], true );

		// Clear Meeting and Webinar cached post meta values
		foreach ( $results as $result ) {
			$result['meta_value'] = unserialize( $result['meta_value'] );
			video_conferencing_zoom_unset_wp_cache( $result['post_id'], $result['meta_key'], $result['meta_value'] );
		}
	}


	public function initialize_acf_options_page()
	{
		// Check function exists.
		if( function_exists('acf_add_options_page') ) {

			// Register options page.
			$parent = acf_add_options_page(array(
				'page_title'    => __('LM Settings'),
				'menu_title'    => __('LM Settings'),
				'menu_slug'     => 'lm-settings',
				'capability'    => 'manage_options',
				'redirect'      => false,
				'update_button' => __('Save Settings', 'acf'),
				'updated_message' => __("Settings Updated", 'acf'),
				'icon_url' 		=> 'dashicons-admin-settings',
			));

			$child = acf_add_options_sub_page(array(
				'page_title'  => __('Questions List'),
				'menu_title'  => __('Questions List'),
				'parent_slug' => $parent['menu_slug'],
				'menu_slug'   => 'lm-questions-list',
				'capability'    => 'manage_options',
				'update_button' => __('Save Settings', 'acf'),
				'updated_message' => __("Settings Updated", 'acf'),
			));

			$child_2 = acf_add_options_sub_page(array(
				'page_title'  => __('Facilitator Instructions'),
				'menu_title'  => __('Facilitator Instructions'),
				'parent_slug' => $parent['menu_slug'],
				'menu_slug'   => 'lm-facilitator-instructions',
				'capability'    => 'manage_options',
				'update_button' => __('Save Settings', 'acf'),
				'updated_message' => __("Settings Updated", 'acf'),
			));

			$child_3 = acf_add_options_sub_page(array(
				'page_title'  => __('Group Settings'),
				'menu_title'  => __('Group Settings'),
				'parent_slug' => $parent['menu_slug'],
				'menu_slug'   => 'lm-groups-settings',
				'capability'    => 'manage_options',
				'update_button' => __('Save Settings', 'acf'),
				'updated_message' => __("Settings Updated", 'acf'),
			));
		}
	}

	public function populate_student_form_options( $field )
	{

		$forms = GFAPI::get_forms();
		
		$field['choices'] = array();
		
		$field['choices'][] = 'Please Select';

		foreach ($forms as $form) {
			$field['choices'][ $form['id'] ] = $form['title'];
		}

	    return $field;
	}

	public function tuts_mcekit_editor_style( $url ) {

		if ( !empty($url) )
			$url .= ',';
 
		// Retrieves the plugin directory URL and adds editor stylesheet
		// Change the path here if using different directories
		$url .= trailingslashit( plugin_dir_url( __FILE__ ) ) . '/css/editor-styles.css';

		return $url;
	}

	public function populate_ld_groups_acf_select( $field ) {

		$field['choices'] = array();
		
		$groups = learndash_get_groups();

		foreach ($groups as $group) {
			$field['choices'][ $group->ID ] = $group->post_title;
		}

	    return $field;
	}
}
