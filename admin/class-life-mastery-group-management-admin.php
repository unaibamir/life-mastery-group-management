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
					<input type="checkbox" id="lm_group_zoom" name="lm_group_zoom" value="yes" >
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

	public function remove_zoom_plugin_cron_job() {

		wp_clear_scheduled_hook( 'zoom_wp_main_cron_sync_data_hook' );

		if ( ! wp_next_scheduled( 'lm_zoom_wp_main_cron_sync_data_hook' ) ) {
			wp_schedule_event( time() + ( MINUTE_IN_SECONDS * 2 ), 'sixhours', 'lm_zoom_wp_main_cron_sync_data_hook' );
		}

		if ( ! wp_next_scheduled( 'lm_cron_sync_infusionsoft_group_users_hook' ) ) {
			wp_schedule_event( time() + ( MINUTE_IN_SECONDS * 2 ), 'sixhours', 'lm_cron_sync_infusionsoft_group_users_hook' );
		}

		add_action( 'lm_zoom_wp_main_cron_sync_data_hook', array( $this, 'lm_zoom_wp_main_cron_sync_data_execute' ) );

		add_action( 'lm_cron_sync_infusionsoft_group_users_hook', array( $this, 'lm_cron_sync_infusionsoft_group_users_callback' ) );

	}

	public function lm_zoom_wp_main_cron_sync_data_execute() {

		if ( ! defined( 'DOING_CRON' ) ) {
			return;
		}

		// If Recordings enabled then refresh cached past recordings
		if ( 1 != get_option( 'zoom_hide_recordings' ) ) {
			$this->zoom_wp_refresh_all_recordings_vimeo();
		}

		delete_transient( 'zoom_wp_license_eu6y5Tg26PTrYCkh' );
		delete_option( 'zoom_wp_license_expire' );
		video_conferencing_zoom_api_check_customer_status();
	}

	public function zoom_wp_refresh_all_recordings_vimeo() {

		//$past_meetings = zoom_conference()->listUserRecordings( $user->id );
		$past_meetings = $this->get_group_meetings_recordings();
		
		if ( !empty($past_meetings) ) {
			foreach ( $past_meetings as $zoom_meeting_id => $past_meeting_arr ) {

				$recordings = array();

				foreach ($past_meeting_arr as $past_meeting) {

					//dd($past_meeting, false);

					// If recording exist for a meeting
					if ( isset( $past_meeting->recording_files ) && $past_meeting->recording_files ) {
						if ( in_array( $past_meeting->type, array( 5, 6, 9 ) ) ) {
							$type = 'zoom_api_webinar_options';
						} else {
							$type = 'zoom_api_meeting_options';
						}

						$zoom_map_array[ $past_meeting->id ] = get_post_meta( $past_meeting->id, $type, true );
						// No record found for meeting or webinar in WP so skip it
						if ( ! $zoom_map_array[ $past_meeting->id ] ) {
							continue;
						}

						//$zoom_map_array[ $past_meeting->id ]['past_recordings_all'] = array();

						$zoom_map_array[ $past_meeting->id ]['type'] = $type;

						$past_meeting->recording_files = array_values($past_meeting->recording_files);

						$this_recording = video_conferencing_recording_get_params_by_sequence( (array) $past_meeting->recording_files, array() );

						

						if ( isset( $this_recording['recording_url'] ) && $this_recording['recording_url'] ) {

							// Associate Vimeo ID if video exists
							$recording_key = array_search( $this_recording['recording_id'], array_column( $zoom_map_array[ $past_meeting->id ]['past_recordings'], 'recording_id' ) );

							//dd( var_export($recording_key, true), false );

							if ( false !== $recording_key ) {

								$vimeo_record = $zoom_map_array[ $past_meeting->id ]['past_recordings'][ $recording_key ];
								if ( isset( $vimeo_record['vimeo_id'] ) ) {
									$exist = video_conferencing_zoom_check_vimeo_exist( $vimeo_record['vimeo_id'] );
									// Video deleted from Vimeo so remove from WP as well
									if ( ! $exist ) {
										$this_recording['vimeo_removed'] = 1;
									} else {
										$this_recording['vimeo_id'] = $vimeo_record['vimeo_id'];
									}
								}
							}

							//dd($this_recording, false);
							
							$recording_date = $this_recording['recording_end'];
							$recording_date = explode('T', $recording_date);
							$this_recording = $this->upload_recording_to_vimeo( $this_recording, $past_meeting->topic . ' - ' . $recording_date[0], false, true);

							//array_push($recordings, $this_recording);
							$recordings[] = $this_recording;
							
							//$zoom_map_array[ $past_meeting->id ]['past_recordings_all'][] = $this_recording;
							//array_push( $zoom_map_array[ $past_meeting->id ]['past_recordings_all'][], $this_recording);
							
							$zoom_map_array[ $past_meeting->id ]['past_recordings_all'][] = $recordings;
							//dd($zoom_map_array[ $past_meeting->id ]['past_recordings_all'][0], false);
						}

					}
					
				}
				
				// Update DB will all past recordings for a meeting
				if ( $zoom_map_array ) {
					foreach ( $zoom_map_array as $meeting_id => $options ) {
						$type = $options['type'];
						if ( isset( $options['past_recordings_all'] ) && $options['past_recordings_all'] ) {
							$options['past_recordings'] = $options['past_recordings_all'][0];
						}
						if ( isset( $options['type'] ) ) {
							unset( $options['type'] );
						}
						if ( isset( $options['past_recordings_all'] ) ) {
							unset( $options['past_recordings_all'] );
						}
						update_post_meta( $meeting_id, $type, $options );
					}
				}

			}

		}
	}

	public function get_group_meetings() {

		global $wpdb;
		
		$groups_meeting_ids 	= array();
		$groups_zoom_meeting 	= array();
		$groups_meeting_data 	= $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'lm_group_zoom_meeting_id'");
		
		if( !empty($groups_meeting_data) ) {
			foreach ($groups_meeting_data as $group_meeting_data) {
				$groups_meeting_ids[] = $group_meeting_data->meta_value;
			}
		}

		if( !empty($groups_meeting_ids) ) {
			foreach ($groups_meeting_ids as $zoom_meeting_id) {
				$meeting_data = !empty($zoom_meeting_id) ? json_decode( zoom_conference()->getMeetingInfo( $zoom_meeting_id ) ) : false;

				if( !isset( $meeting_data->code ) || empty($meeting_data->code) ) {
					$groups_zoom_meeting[] = $meeting_data;
				}
			}
		}

		return $groups_zoom_meeting;

	}

	public function get_group_meetings_recordings() {
		
		global $wpdb;
		
		$groups_meeting_ids 	= array();
		$groups_zoom_meeting 	= array();
		$groups_meeting_data 	= $wpdb->get_results("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'lm_group_zoom_meeting_id'");
		
		if( !empty($groups_meeting_data) ) {
			foreach ($groups_meeting_data as $group_meeting_data) {
				$groups_meeting_ids[] = $group_meeting_data->meta_value;
			}
		}

		array_push($groups_meeting_ids, 702375183);
		
		$users = video_conferencing_zoom_api_get_users();
		
		if ( ! $users ) {
			return;
		}
		
		// Get recordings for all users in the account
		foreach ( $users as $user ) {
			$past_meetings = zoom_conference()->listUserRecordings( $user->id );
			if ( $past_meetings ) {
				foreach ( $past_meetings as $past_meeting ) {
					if( in_array($past_meeting->id, $groups_meeting_ids )  ) {
						$groups_zoom_meeting[$past_meeting->id][] = $past_meeting;
					}
				}
			}
		}

		// filter zoom recordings to only video ones. 
		// we do not want audio files to be uploaded on Vimeo.
		if( !empty($groups_zoom_meeting) ) {
			foreach ( $groups_zoom_meeting as $meeting_id => $meeting_recordings ) {

				foreach ( $meeting_recordings as $recording_key => $meeting_recording ) {
					$recording_files = $meeting_recording->recording_files;

					foreach ($recording_files as $file_key => $recording_file) {

						if( $recording_file->recording_type != 'shared_screen_with_gallery_view' ) {
							unset( $groups_zoom_meeting[ $meeting_id ][ $recording_key ]->recording_files[$file_key] );
							continue;
						}
					}
				}

				/*foreach ($group_zoom_meeting->recording_files as $recording_key => $recording) {
					if( $recording->recording_type != 'shared_screen_with_gallery_view' ) {
						unset( $groups_zoom_meeting[$meeting_id][$recording_key]->recording_files[$recording_key] );
						continue;
					}
				}*/
			}
		}
		
		return $groups_zoom_meeting;
	}

	public function modify_zoom_vimeo_hooks() {
		remove_all_actions("wp_ajax_zoom_wp_show_all_recordings");
		remove_all_actions("wp_ajax_nopriv_zoom_wp_show_all_recordings");

		add_action( "wp_ajax_zoom_wp_show_all_recordings", array( $this, "lm_zoom_wp_show_all_recordings" ) );
		add_action( "wp_ajax_nopriv_zoom_wp_show_all_recordings", array( $this, "lm_zoom_wp_show_all_recordings" ) );

		remove_shortcode( 'zoom_api_link' );

		add_shortcode( "zoom_api_link", array( $this, 'zoom_api_link_shortcode' ) );

		if( isset($_GET["unaib_meetings"]) ) {		
			$past_meetings = $this->zoom_wp_refresh_all_recordings_vimeo();
			dd($past_meetings);
		}
		
	}

	// Render All recordings for a meeting on frontend
	function lm_zoom_wp_show_all_recordings( $meeting_id = false, $type = false ) {
		lm_debug_log( "LM Meeting ID via AJAX: " . $meeting_id );
		
		check_ajax_referer( 'show_recording', 'security' );
		if ( ! isset( $_GET['mn'] ) ) {
			exit();
		}
		$meeting_id = (float) $_GET['mn'];
		$type       = (string) $_GET['type'];

		$zoom_map_array = get_post_meta( $meeting_id, $type, true );
		if ( ! $zoom_map_array ) {
			$zoom_map_array = array();
		}

		$recordings = new StdClass();

		// Append JWT access token for private recording
		$jwt = zoom_conference()->generateJWTKey();

		if ( ! isset( $zoom_map_array['past_recordings'] ) && $zoom_map_array['host_id'] ) {
			//$past_meetings = zoom_conference()->listUserRecordings( $zoom_map_array['host_id'] );
			$past_meetings = $this->get_group_meetings_recordings();
			if ( !empty($past_meetings) ) {
				foreach ( $past_meetings as $past_meeting ) {
					// Recording not for this meeting
					if ( $meeting_id != $past_meeting->id ) {
						continue;
					}

					if ( isset( $past_meeting->recording_files ) && $past_meeting->recording_files ) {
						$this_recording = video_conferencing_recording_get_params_by_sequence( (array) $past_meeting->recording_files, array() );
						if ( isset( $this_recording['recording_url'] ) && $this_recording['recording_url'] ) {
							$this_recording                      = $this->upload_recording_to_vimeo( $this_recording, $past_meeting->topic, $jwt );
							$zoom_map_array['past_recordings'][] = $this_recording;
						}
					}
				}

				update_post_meta( $meeting_id, $type, $zoom_map_array );
			}
		}
		
		if ( isset( $zoom_map_array['past_recordings'] ) && $zoom_map_array['past_recordings'] ) {
			$recordings->data = $zoom_map_array['past_recordings'];
		}
		
		// Convert into Datatable format
		foreach ( $recordings->data as $key => $recording ) {
			$vimeo_rec_id = '';
			if ( isset( $recording['vimeo_id'] ) ) {
				$vimeo_rec_id = $recording['vimeo_id'];
			}

			if ( $recording['recording_url'] ) {
				$recording_url = $recording['recording_url'] . '?access_token=' . $jwt;
			}

			$recordings->data[ $key ]['no']            = $key + 1;
			$recordings->data[ $key ]['recording_end'] = video_conferencing_zoom_convert_time_to_local_readable( esc_attr( $recording['recording_end'] ), false, $zoom_map_array['time'] );
			$recordings->data[ $key ]['recording_url'] = '<form method="post"><input type="hidden" name="vimeo_rec_id" value="' . esc_attr( $vimeo_rec_id ) . '"/><input type="hidden" name="play_recording_url" value="' . esc_attr( $recording_url ) . '"/><button class="zoom-link play-recoding-btn" name="play_recording" type="submit">' . __( 'Watch Recording', 'video-conferencing-with-zoom-api' ) . '</button></form>';
		}

		if ( ! isset( $recordings->data ) ) {
			$recordings->data = array();
		}

		echo json_encode( $recordings );
		wp_die();
	}

	public function upload_recording_to_vimeo( $this_recording, $meeting_topic, $jwt = false, $upload = true ) {
		// Param only needed for Vimeo API
		if ( isset( $this_recording['file_size'] ) ) {
			$recording_size = $this_recording['file_size'];
			unset( $this_recording['file_size'] );
		}


		# If already uploaded or deleted from vimeo then don't upload
		if ( isset( $this_recording['vimeo_id'] ) || isset( $this_recording['vimeo_removed'] ) ) {
			return $this_recording;
		}

		// Append JWT access token for private recording
		if ( ! $jwt ) {
			$jwt = zoom_conference()->generateJWTKey();
		}

		# Set dependent Vimeo upload classes
		$api = new WP_DGV_Api_Helper();
		$db  = new WP_DGV_Db_Helper();

		# Upload remote cloud recording to Vimeo if not uploaded already
		$url    = $this_recording['recording_url'] . '?access_token=' . $jwt;
		$params = array(
			'name'        => $meeting_topic,
			'description' => $meeting_topic,
			'size'        => $recording_size,
		);

		// If plus or above vimeo plan then set video privacy
		if ( 'basic' != $api->user_type ) {
			$params['privacy'] = array(
				'add'      => 0, // prevent adding add the video to a showcase, channel, or group
				'view'     => 'disable', // make video unavailable from vimeo
				'download' => false, // prevent downloads
				'embed'    => 'whitelist', // Only allow embed to this site
			);

			$params['embed'] = array(
				'buttons' => array(
					'share'      => 0,
					'embed'      => 0,
					'watchlater' => 0,
					'fullscreen' => 1,
					'hd'         => 1,

				),
				'logos'   => array(
					'vimeo' => 0,
				),
			);
		} else {
			$params['privacy'] = array(
				'add'      => 0, //prevent adding add the video to a showcase, channel, or group
				'comments' => 'nobody',
			);
		}

		if( $upload ) {
			$vimeo_upload = $api->upload_pull( $url, $params );
			
			if ( isset( $vimeo_upload['response']['body']['uri'] )
				&& $vimeo_upload['response']['body']['uri'] ) {
				// If plus or above vimeo plan then set domain whitelist
				if ( 'basic' != $api->user_type ) {
					// Whitelist all domains on this WP install
					if ( function_exists( 'get_sites' ) ) {
						$sites = get_sites( array( 'number' => 0 ) );
						foreach ( $sites as $key => $site ) {
							$sites[ $key ] = $site->domain;
						}
						$sites = array_unique( $sites );
					} else {
						$sites[0] = get_site_url();
					}

					foreach ( $sites as $domain ) {
						$domain = str_replace( array( 'http://', 'https://' ), '', $domain );
						$api->whitelist_domain_add( $vimeo_upload['response']['body']['uri'], $domain );
					}
					$domain = 'schooloflifemastery.com';
					$api->whitelist_domain_add( $vimeo_upload['response']['body']['uri'], $domain );
				}

				$vimeo_id = str_replace( '/videos/', '', $vimeo_upload['response']['body']['uri'] );

				// Add video to Media -> Vimeo
				$this->create_local_video( $params['name'], $params['description'], $vimeo_id );
				$this_recording['vimeo_id'] = $vimeo_id;

				$vimeo_ids = get_option( 'lm_vimeo_ids', '' );
				$vimeo_ids .= ', ' .$vimeo_id;
				update_option( 'lm_vimeo_ids', $vimeo_ids );
				
			} elseif ( isset( $vimeo_upload['response']['body']['error'] ) ) {
				$dev_error = ( isset( $vimeo_upload['response']['body']['developer_message'] ) ? $vimeo_upload['response']['body']['developer_message'] : '' );
				video_conferencing_zoom_log_error( 'Vimeo Error: ' . $vimeo_upload['response']['body']['error'] . ' ' . $dev_error );
			}
		} else {
			$this_recording['vimeo_id'] = rand(564565646, 56456589798);
		}


		return $this_recording;
	}

	/**
     * Returns the local video
     *
     * @param $title
     * @param $description
     * @param $vimeo_id  - (eg. 18281821)
     * @param  string  $context
     *
     * @return int|WP_Error
     */
	public function create_local_video( $title, $description, $vimeo_id, $context = 'admin' ) {

	    $args = array(
            'post_title'   => wp_strip_all_tags( $title ),
            'post_content' => wp_strip_all_tags( $description ),
            'post_status'  => 'publish',
            'post_type'    => WP_DGV_Db_Helper::POST_TYPE_UPLOADS,
            //'post_author'  => is_user_logged_in() ? get_current_user_id() : 0,
            'post_author'  => 3,
        );

	    $args = apply_filters('dgv_insert_video_args', $args, $context);

		$postID = wp_insert_post( $args );

		if ( ! is_wp_error( $postID ) ) {
			update_post_meta( $postID, 'dgv_response', $vimeo_id );
		}

		update_post_meta($postID, 'dgv_context', $context);

		return $postID;
	}

	public function lm_cron_sync_infusionsoft_group_users_callback() {

		global $i4w, $wpdb;

		$returnFields = array('ContactId');

		$args = array(
			'post_type' 		=> 	'groups',
			'post_status' 		=> 	'publish',
			'posts_per_page'    => 	-1,
		);
		
		$query = new WP_Query( $args );

		if( is_array($query->posts) && !empty($query->posts) ) {
			$group_ids = wp_list_pluck( $query->posts, 'ID' );

			if( empty( $group_ids ) ) {
				return;
			}

			foreach ($group_ids as $key => $group_id) {
				$group_tag_id = get_post_meta( $group_id, 'lm_group_tag', true );
				if( empty($group_tag_id) ) { continue; }

				$contacts = $i4w->myApp->dsFind('ContactGroupAssign', 1000, 0, 'GroupId', $group_tag_id, $returnFields);
				
				if( $contacts && !empty($contacts) ) {
					foreach ($contacts as $contact) {
						$contact_id = $contact["ContactId"];

						$db_query 	= "SELECT user_id FROM `{$wpdb->usermeta}` WHERE `meta_key` = 'infusion4wp_user_id' AND meta_value = $contact_id";
						
						$data 		= $wpdb->get_col($db_query);
						if( empty($data) || empty($data[0]) ){ continue; }
						$user_id 	= $data[0];

						$is_member = learndash_is_user_in_group( $user_id, $group_id );

						if( !$is_member ) {
						
							$group_users 	= learndash_get_groups_user_ids( $group_id );

							if( !in_array($user_id, $group_users) ) {
								array_push($group_users, $user_id);
							}
							
							learndash_set_groups_users( $group_id, $group_users );

							lm_debug_log( sprintf('LM Note: user id: %s has been added into to group: %s', $user_id, $group_id )  );
						}
					}
				}

			}
		}

	}


	public function modify_zoom_recording_via_option( $value, $option ) {
		if( wp_doing_ajax() || wp_doing_cron() ) {
			return "zoom_cloud";
		} else {
			return $value;
		}
	}


	public function lm_meeting_recordings_shortcode_callback( $atts, $content ) {
		$args = shortcode_atts(
			array(
				'meeting_id' => '',
				'is_webinar' => '',
				'play_url'   => '',
				'start_time' => '',
				'end_time'   => '',
			),
			$atts
		);

		$args['zoom_not_show_recordings'] = get_option( 'zoom_hide_recordings' );
		$args['zoom_window_size']         = get_option( 'zoom_window_size' );
		$args['meeting_id']               = (float) str_replace( '-', '', str_replace( ' ', '', $args['meeting_id'] ) );
		$args['is_only_recording']        = 1;
		$args['is_meeting_view']          = 0;

		if ( $args['is_webinar'] ) {
			$args['option'] = 'zoom_api_webinar_options';
		} else {
			$args['option'] = 'zoom_api_meeting_options';
		}

		if ( $args['meeting_id'] ) {
			$args['zoom_map_array'] = get_post_meta( $args['meeting_id'], $args['option'], true );
			if ( ! $args['zoom_map_array'] ) {
				$args['zoom_map_array'] = array();
			}
		}

		$past_recordings 	= $args['zoom_map_array']['past_recordings'];
		$meeging_id 		= $args['meeting_id'];
		$meeting_title 		= $args['zoom_map_array']['topic'];

		//add_thickbox();

		ob_start();
		
		if( !empty( $past_recordings ) ) {
			?>
			<ul>
			<?php
			foreach ($past_recordings as $past_recording) {
				$recording_date 	= $past_recording['recording_end'];
				$recording_date 	= explode('T', $recording_date);
				$recording_title 	= $meeting_title . ' - ' . $recording_date[0];
				
				?>
				<li>
					<div id="lm-zoom-recording-<?php echo $past_recording['vimeo_id']; ?>" style="display:none;" class="lm-zoom-recording">
						<div class="recording-container">
							<?php echo do_shortcode( '[dgv_vimeo_video id="' . esc_attr( $past_recording['vimeo_id'] ) . '"]' ); ?>
						</div>
						
					</div>
					<!-- <a href="#TB_inline?&width=800&height=450&inlineId=lm-zoom-recording-<?php echo $past_recording['vimeo_id']; ?>" class="thickbox">
						<?php echo $recording_title; ?>
					</a> <br> -->

					<a href="#lm-zoom-recording-<?php echo $past_recording['vimeo_id']; ?>"  class="various">
						<?php echo $recording_title; ?>
					</a>

				</li>
				<?php
			}
			?>
			</ul>
			<?php
		}


		$output = ob_get_contents();
        ob_end_clean();
        return $output;
		
	}

	public function zoom_api_link_shortcode( $atts, $content = null ) {
		if ( ! video_conferencing_zoom_is_valid_request() ) {
			return;
		}
		ob_start();

		/* Forced redirect disabled in in v4.11.4
		// Serve a non cached version of the shortcode page
		if ( video_conferencing_zoom_redirect_nocache() ) {
			wp_enqueue_script( 'video-conferencing-with-zoom-api-nocache' );
		}
		*/

		// If styles fail to enqueue earlier then add them with the shortcode
		if ( ! wp_style_is( 'video-conferencing-with-zoom-api-iframe' ) ) {
			wp_enqueue_style( 'video-conferencing-with-zoom-api-iframe' );

			if ( is_rtl() ) {
				wp_add_inline_style( 'video-conferencing-with-zoom-api-iframe', 'body ul.zoom-meeting-countdown{ direction: ltr; }' );
			}
		}

		$args = shortcode_atts(
			array(
				'meeting_id'             => '',
				'is_webinar'             => '',
				'title'                  => '',
				'countdown_title'        => '',
				'join_via_app'           => '',
				'display_name'           => '',
				'user_email'             => '',
				'cloud_recording_button' => '',
				'recording_url'          => '',
				'display_back_btn'       => '',
				'id'                     => 'zoom-meeting-window',
				'class'                  => 'zoom-meeting-window',
				'hide_form'              => 0,
				'meeting_role'           => 0,
				'show_countdown'         => 0,
				'join_meeting'           => 0,
				'auto_join'              => 0,
				'is_meeting_view'        => 0,
				'show_join_web'          => 0,
				'show_join_app'          => 0,
			),
			$atts
		);

		$args['type_id'] = (float) str_replace( '-', '', str_replace( ' ', '', $args['meeting_id'] ) );

		if ( ! $args['type_id'] ) {
			$content = '<h4 class="no-meeting-id"><strong style="color:red;">' . __( 'ERROR: ', 'video-conferencing-with-zoom-api' ) . '</strong>' . __( 'Missing meeting or webinar id', 'video-conferencing-with-zoom-api' ) . '</h4>';
			return;
		}

		$content = '<div class="zoom-window-wrap"><div style="display:none;" class="loader"></div>';

		if ( $args['is_webinar'] ) {
			$args['option'] = 'zoom_api_webinar_options';
		} else {
			$args['option'] = 'zoom_api_meeting_options';
		}

		$args['zoom_map_array'] = get_post_meta( $args['type_id'], $args['option'], true );
		if ( ! $args['zoom_map_array'] ) {
			$args['zoom_map_array'] = array();
		}

		$args = video_conferencing_zoom_prepare_args( $args );

		if ( isset( $args['zoom_map_array']['enforce_login'] ) && ! is_user_logged_in() ) {
			$content .= video_conferencing_zoom_show_is_login();
		} elseif ( video_conferencing_zoom_is_countdown( $args ) ) {
			$content .= video_conferencing_zoom_show_countdown( $args );
		} else {
			$args['zoom_not_show_recordings'] = 1;
			$content .= video_conferencing_zoom_load_meeting( $args );
		}

		$content .= '</div>'; // Close zoom-window-wrap

		// Allow addon devs to perform filter before window rendering
		echo apply_filters( 'zoom_wp_before_window_content', $content );

		$display = ob_get_clean();

		return $display;
	}
}
