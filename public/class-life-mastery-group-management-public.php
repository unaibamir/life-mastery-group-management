<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://unaibamir.com
 * @since      1.0.0
 *
 * @package    Life_Mastery_Group_Management
 * @subpackage Life_Mastery_Group_Management/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Life_Mastery_Group_Management
 * @subpackage Life_Mastery_Group_Management/public
 * @author     Unaib Amir <unaibamiraziz@gmail.com>
 */
class Life_Mastery_Group_Management_Public {

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

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		global $post;

		if( !empty($post->post_content) && !has_shortcode( $post->post_content, 'lm_group_management' ) ) {
			//return;
		}

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

		wp_enqueue_style( 'lm-jquery-ui-style' , plugin_dir_url( __FILE__ ) . 'css/jquery-ui.css');
		wp_enqueue_style( 'lm-select2-css' , plugin_dir_url( __FILE__ ) . 'css/select2.min.css', array(), $this->version, 'all');
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/life-mastery-group-management-public.css', array('lm-select2-css'), time(), 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		global $post;

		if( !empty($post->post_content) && !has_shortcode( $post->post_content, 'lm_group_management' ) ) {
			//return;
		}

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

		$form_id = get_field('student_form', 'option');
		if( !empty( $form_id ) ) {
			gravity_form_enqueue_scripts( $form_id, true );
		}
		

		$dependecy = array( 'jquery', 'jquery-ui-datepicker', 'lm-select2' );
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'lm-select2', plugin_dir_url( __FILE__ ) . 'js/select2.min.js', array(), $this->version, true );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/life-mastery-group-management-public.js', $dependecy, time(), true );

	}

	public function init() {
		
	}


	public function lm_group_management_shortcode_callback() {

		if( !is_user_logged_in() ) {
			$data = $this->get_login_form();
			return $data;
		}
		
		$user = wp_get_current_user();

		ob_start();
		
		if( learndash_is_group_leader_user( $user ) ) {
			echo $this->lm_group_leader_management( $user );
		} else {
			echo $this->lm_group_member_management( $user );
		}

		$output = ob_get_contents();
        ob_end_clean();
        return $output;
	}

	public function lm_group_leader_management( $user ) {
		global $post;

		if( isset($_GET['lm_group_id'], $_GET['lm_action']) && !empty($_GET['lm_group_id']) && $_GET['lm_action'] == 'edit' ) {

			$this->getGroupEditPages( $user );

		} else {

			$user_group_ids     = learndash_get_administrators_group_ids( $user->ID );
			$hidden_groups      = get_user_meta( $user->ID, 'lifemastery_hidden_groups', true);
	        if( !empty( $hidden_groups ) ) {
	            $common_group_ids = array_diff( $user_group_ids, $hidden_groups );
	        }

	        $user_group_ids = array_unique($user_group_ids);
			arsort( $user_group_ids );

			if( !empty( $user_group_ids ) ) {

				?>
			    <div id="buddypress" class="user-groups-area ">

			    <?php

			    foreach ($user_group_ids as $group_id) {

			    	$group_manage_link = add_query_arg(array(
			    		'lm_group_id'	=>	$group_id,
			    		'lm_action'		=>	'edit',
			    		'lm_view'		=>	'attendance'
			    	), get_permalink( $post ));

			    	?>
			    	<h5 class="widgettitle group-title lm-group-title">
			    		<?php echo get_the_title($group_id); ?>
			    	</h5>
			    	<div class="user-groups-table" id="group-<?php echo $group_id; ?>">
			    		<span class="alignright lm-group-manage-link">
			    			<a href="<?php echo esc_url( $group_manage_link ) ?>"><i class="fa fa-edit"></i> <?php _e('Manage Group'); ?></a>
			    		</span>

			    		<?php echo LM_Helper::get_group_details_ajax($group_id); ?>
			    		
                	</div>
			    	<?php
			    }

			    ?>
		        </div>
		        <?php
		    }

    	}
	}

	public function lm_group_member_management( $user ) {

		global $post;

		if( isset($_GET['lm_group_id'], $_GET['lm_action']) && !empty($_GET['lm_group_id']) && $_GET['lm_action'] == 'edit' ) {

			$this->getGroupEditPages( $user );
			return;
		}

		$user_admin_groups  = learndash_get_administrators_group_ids( $user->ID );
		
		$user_group_ids     = learndash_get_users_group_ids( $user->ID );
        $user_group_ids     = !empty($user_admin_groups) ? array_merge($user_admin_groups, $user_group_ids) : $user_group_ids;

        $common_group_ids   = !empty($user_group_ids) ? $user_group_ids : array() ;
        $hidden_groups      = get_user_meta( $user->ID, 'lifemastery_hidden_groups', true);
        if( !empty( $hidden_groups ) ) {
            $common_group_ids = array_diff( $common_group_ids, $hidden_groups );
        }

        $common_group_ids = array_unique($common_group_ids);
        $common_group_ids = array_reverse( $common_group_ids );

        if( !empty( $common_group_ids ) ) {

			?>
		    <div id="buddypress" class="user-groups-area ">

		    <?php

		    foreach ($common_group_ids as $group_id) {

		    	$is_leader = false;
		    	$has_group_leader = learndash_get_groups_administrators( $group_id, true );
				if ( ! empty( $has_group_leader ) ) {
					foreach ( $has_group_leader as $leader ) {
						if ( learndash_is_group_leader_of_user( $leader->ID, $user->ID ) ) {
							$is_leader = true;
							break;
						}
					}
				}

		    	$group_manage_link = add_query_arg(array(
		    		'lm_group_id'	=>	$group_id,
		    		'lm_action'		=>	'edit',
		    		'lm_view'		=>	'attendance'
		    	), get_permalink( $post ));

		    	?>
		    	<h5 class="widgettitle group-title lm-group-title">
		    		<?php echo get_the_title($group_id); ?>
		    	</h5>
		    	<div class="user-groups-table" id="group-<?php echo $group_id; ?>">

			    	<?php
			    	if( $is_leader ) {
			    		?>
			    		<!-- <span class="alignright lm-group-manage-link">
			    			<a href="<?php echo esc_url( $group_manage_link ) ?>"><i class="fa fa-edit"></i> <?php _e('Manage Group'); ?></a>
			    		</span> -->
			    		<?php
			    	}
			    	?>

		    		<?php echo LM_Helper::get_group_details_ajax($group_id); ?>
		    		
            	</div>
		    	<?php
		    }

		    ?>
	        </div>
	        <?php
	    }

	}	


	public function getGroupEditPages( $user ) {

		global $post;

		$tabs = array(
			/*'dates'			=>	array(
				'name' 		=>	__('Lesson Dates'),
				'callback'	=>	'get_group_dates_manage'
			),*/
			'attendance'	=>	array(
				'name' 		=>	__('Attendance'),
				'callback'	=>	'get_group_attendance_manage'
			),
			'schedule'		=>	array(
				'name' 		=>	__('Class Schedule'),
				'callback'	=>	'get_group_schedule_manage'
			),
			'zoom'			=>	array(
				'name' 		=>	__('Zoom'),
				'callback'	=>	'get_group_zoom_manage'
			),
		);

		$current_tab 	=	isset($_GET['lm_view']) ? $_GET['lm_view'] : 'attendance';
		$group_id 		=  	isset($_GET['lm_group_id']) ? $_GET['lm_group_id'] : 0;

		$url 			= 	add_query_arg( array(
			'lm_group_id'	=>	$group_id,
			'lm_action'		=>	'edit'
		), get_permalink( $post ) );

		$back_link 		= get_permalink( $post );

		?>
		<div id="buddypress" class="lm-group-manage-area">
			<h5 class="widgettitle  lm-group-title">
		    		<?php echo get_the_title($group_id); ?>
		    	</h5>
			<div id="item-nav" class="lm-group-manage-nav">
				<div class="item-list-tabs no-ajax">
					<ul class="horizontal-responsive-menu" id="nav-bar-filter" style="padding-left: 0">
						<?php

						foreach ($tabs as $key => $tab) {

							$url = add_query_arg( 'lm_view', $key, $url );

							?>
							<li class="<?php echo $key == $current_tab ? "current selected" : ""; ?>">
								<a href="<?php echo $url; ?>"><?php echo $tab['name']; ?></a>
							</li>
							<?php
						}
						?>
						<li class="right" style="float: right;">
							<a href="<?php echo $back_link; ?>">Back</a>
						</li>
					</ul>
				</div>
			</div>

			<div id="item-body" role="main" class="clearfix lm-group-manage-content" style="padding: 0">
				<?php
				$this->show_notifications();
				call_user_func( array( $this, $tabs[$current_tab]['callback'] ) );
				?>
			</div>
		</div>
		<?php
	}


	public function get_group_schedule_manage() {
		global $post;

		$group_id 		=  	isset($_GET['lm_group_id']) ? $_GET['lm_group_id'] : 0;
		$group_courses 	=	learndash_group_enrolled_courses( $group_id );
		$course_id 		= 	$group_courses[0];
		
		$group_data 	= 	get_post_meta( $group_id, 'lm_group_data', true );
		$group_start_date 	= 	get_post_meta( $group_id, 'lm_course_start_date', true );
		//dd($group_data);

		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
		$lesson_ids = $ld_course_steps_object->get_children_steps( $course_id, 'sfwd-lessons' );
		/*dd($lesson_ids);
		$course_lessons = learndash_get_course_lessons_list( $course );
		dd($course_lessons);*/
		$total_rows 	= ceil( count($lesson_ids) / 2 );
		//$total_rows 	= $total_rows + count( $sections );
		$lessons 		= array();
		array_unshift($lessons , array(
			'post' => (object) array(
				'ID'			=>	9999999,
				'post_title' 	=>	__('Introductions & Tech Check!')
			)
		));
		
		foreach ($lesson_ids as $lesson_id) {
			$lessons[] 	= array(
				'post' => (object) array(
					'ID'			=>	$lesson_id,
					//'post_title' 	=>	sprintf(__('Lesson %s'), $lesson['sno'] )
					'post_title' 	=>	get_the_title( $lesson_id )
				)
			);
		}

		//dd($lessons);
		
		$sections 		= learndash_30_get_course_sections( $course_id );
		$students 		= learndash_get_groups_users( $group_id );
		$leaders 		= learndash_get_groups_administrators( $group_id );
		$members 		= array();
		foreach ($students as $student) {
			$members['students'][] = array(
				'ID' 	=> 	$student->ID,
				'name' 	=> 	$student->data->display_name,
			);
		}

		foreach ($leaders as $leader) {
			$members['leaders'][] = array(
				'ID' 	=> 	$leader->ID,
				'name' 	=> 	$leader->data->display_name,
			);
		}		
		
		
		reset($sections);
		$key = key($sections);
		unset($sections[$key]);

		?>

		<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" class="standard-form base" method="POST" autocomplete="off">

			<input type="hidden" name="action" value="lm_group_schedule_callback">
			
			<div style="overflow-x:auto;">
				<table class="profile-fields">
					<thead>
						<tr>
							<th class="" style="width: 40px;"><?php echo __('Call #');?></th>
							<th class="">&nbsp;</th>
							<th class="" style="width: 110px;"><?php echo __('Availability Date');?></th>
							<th class="" style="width: 110px;"><?php echo __('Review Date');?></th>
							<th class=""><?php echo __('Class Agenda - Review:');?></th>
							<th class=""><?php echo __('Leading the discussion');?></th>
						</tr>
					</thead>

					<tbody>
						<?php
						$counter = 0;
						$call = 1;
						for ($i = 0; $i < $total_rows; $i++) {

							if( $call == 1 ) {
								$call_text = __('Tech Check');
								$i--;
							} else {
								$call_text = sprintf( __('Week %s' ), $i );
							}

							if( $counter == 0 || $counter == 1 ) {
								$group_data['lesson_dates'][$counter] = '';
								//$group_data['lesson_review_dates'][$counter] = '';
							}

							?>
							<tr>
								<td style="width: 40px;"><?php echo $call; ?></td>
								<td style="width: 70px;"><?php echo $call_text; ?></td>
								<td>
									<input type="text" name="lesson_date[<?php echo $counter; ?>]" class="<?php echo $call == 1 ? "": ""; ?>" value="<?php echo $group_data['lesson_dates'][$counter]; ?>" <?php echo $call == 1 ? "readonly": "readonly"; ?> >
								</td>
								<td>
									<input type="text" name="lesson_review_date[<?php echo $counter; ?>]" class="<?php echo $call <= 1 ? "": "lesson_review_date"; ?>" value="<?php echo $group_data['lesson_review_dates'][$counter]; ?>" readonly <?php echo $call <= 1 ? "readonly": ""; ?> >
									
								</td>
								<td>
									<select name="lm_lessons[<?php echo $counter; ?>][]" id="lessons-<?php echo $counter; ?>" class="lesson_select" multiple="multiple" >
										<?php
										foreach ($lessons as $lesson) {
											$selected = '';
											if( isset($group_data['lm_lessons'][$counter]) && in_array($lesson['post']->ID, $group_data['lm_lessons'][$counter]) ) {
												$selected = 'selected="selected"';
											}
											?>
											<option value="<?php echo $lesson['post']->ID; ?>" <?php echo $selected; ?>><?php echo $lesson['post']->post_title; ?></option>
											<?php
										}
										?>
									</select>
								</td>
								<td>
									<select name="users[<?php echo $counter; ?>][]" class="student_select" multiple="multiple">
										<?php if( $counter > 1 ): ?>
											<optgroup label="Students">
												<?php
												foreach ($members['students'] as $member) {
													$selected = '';
													if( isset($group_data['users'][$counter]) && in_array($member['ID'], $group_data['users'][$counter]) ) {
														$selected = 'selected="selected"';
													}
													?>
													<option value="<?php echo $member['ID']; ?>" <?php echo $selected; ?>><?php echo $member['name']; ?></option>
													<?php
												}
												?>
											</optgroup>
										<?php endif; ?>

										<optgroup label="Leaders">
											<?php
											foreach ($members['leaders'] as $member) {
												$selected = '';
												if( isset($group_data['users'][$counter]) && in_array($member['ID'], $group_data['users'][$counter]) ) {
													$selected = 'selected="selected"';
												}
												?>
												<option value="<?php echo $member['ID']; ?>" <?php echo $selected; ?>><?php echo $member['name']; ?></option>
												<?php
											}
											?>
										</optgroup>
									</select>
								</td>
							</tr>

							<?php

							if( $call == 2 ) {
								?>
								<tr>
									<td colspan="5">Phase 1</td>
									<td>
										<input type="hidden" name="section[]" value="0">
										<select name="s_users[0][]" class="student_select" multiple="multiple" >
											

											<optgroup label="Leaders">
												<?php
												foreach ($members['leaders'] as $member) {
													$selected = '';
													if( isset($group_data['s_users'][0]) && in_array($member['ID'], $group_data['s_users'][0]) ) {
														$selected = 'selected="selected"';
													}
													?>
													<option value="<?php echo $member['ID']; ?>" <?php echo $selected; ?>><?php echo $member['name']; ?></option>
													<?php
												}
												?>
											</optgroup>
										</select>
									</td>
								</tr>
								<?php
							}

							if( $call == 8 ) {
								?>
								<tr>
									<td colspan="5">Phase 2</td>
									<td>
										<input type="hidden" name="section[]" value="<?php echo 1; ?>">
										<select name="s_users[<?php echo 1; ?>][]" class="student_select" multiple="multiple" >
											

											<optgroup label="Leaders">
												<?php
												foreach ($members['leaders'] as $member) {
													$selected = '';
													if( isset($group_data['s_users'][1]) && in_array($member['ID'], $group_data['s_users'][1]) ) {
														$selected = 'selected="selected"';
													}
													?>
													<option value="<?php echo $member['ID']; ?>" <?php echo $selected; ?>><?php echo $member['name']; ?></option>
													<?php
												}
												?>
											</optgroup>
										</select>
									</td>
								</tr>
								<?php
							}

							$counter++;
							$call++;
						}
						?>
					</tbody>
				</table>

				<div class="submit">
					<input type="submit" name="lm_group_schedule_save" id="lm_group_schedule_save" value="Save Changes ">
				</div>
			</div>
			<input type="hidden" name="lm_group_id" value="<?php echo $group_id ?>">
			<?php wp_nonce_field( 'lm_group_schedule_save', 'lm_group_schedule_save_wpnonce' ); ?>

		</form>
		<?php
	}


	public function lm_group_schedule_save_callback() {
		global $wpdb;

		if ( ! isset( $_POST['lm_group_schedule_save_wpnonce'] )  || ! wp_verify_nonce( $_POST['lm_group_schedule_save_wpnonce'], 'lm_group_schedule_save' ) ) {
			wp_die( __('Oops, something went wrong with your submission. Please try again.'), __('something went wrong!') );
		}

		$table = _get_meta_table( 'user' );

		$lesson_drip_dates = $group_attendance_dates = $lesson_review_dates = array();

		if( isset($_POST['lesson_review_date']) && !empty($_POST['lesson_review_date']) ) {
			foreach ($_POST['lesson_review_date'] as $lesson_date ) {
				if( empty($lesson_date) ) continue;
				$date 		= date( 'Y-m-d H:i:s', strtotime($lesson_date));
				$group_attendance_dates[] = $date;
			}
		}

		$lessons = array();

		$gmt_offset  	= get_option( 'gmt_offset' );
		if ( empty( $gmt_offset ) ) {
			$gmt_offset = 0;
		}
		$offset      	= ( $gmt_offset * ( 60 * 60 ) ) * - 1;

        $data 			=	array();
        $format 		= 'Y-m-d H:01:s';

		$group_data = array();
		$group_data['group_id'] 		= $_POST['lm_group_id'];
		$group_data['lesson_dates'] 	= $_POST['lesson_date'];
		$group_data['lesson_review_dates'] = $_POST['lesson_review_date'];
		$group_data['lm_lessons'] 		= $_POST['lm_lessons'];
		$group_data['users'] 			= isset($_POST['users']) ? $_POST['users'] : array();
		$group_data['s_users'] 			= isset($_POST['s_users']) ? $_POST['s_users'] : array();
		$group_data['users_data'] 		= array();

		/*foreach ($group_data['lesson_dates'] as $key => $lesson_date) {
			$meta_key = 'lm-lesson-date-' . $_POST['lm_group_id'] . '-'  ;
			dd( $meta_key, false );
		}*/
		//dd($group_data['lesson_dates'], false);
		foreach ( $group_data['lesson_review_dates'] as $discuss_date) {
			//$date 		= date( 'Y-m-d H:i:s', strtotime($lesson_date));
			$date 	 	= date( 'm/d/Y', strtotime( "-3 week monday", strtotime( $discuss_date ) ) );
			
			$dates[] 	= $date;
			continue;			
			
		}

		$dates = array_values($dates);
		$group_data['lesson_dates'] = $dates;
		//dd($group_data['lesson_dates']);
		$query = "DELETE FROM $table WHERE meta_key LIKE 'lm_lesson_group_".$group_data['group_id']."%'";
		$count = $wpdb->query( $query );

		if( !empty($group_data['users']) ){
			$user_data = array();
			foreach ($group_data['users'] as $key => $users) {

				

				$date 	 	= date( $format, strtotime( $group_data['lesson_review_dates'][$key] ) );
	        	$drip_date 	= strtotime($date);
	        	$drip_date 	= (int) $drip_date + $offset;

	        	$week_num 	= $key - 1;
	        	
	        	if( $week_num < 1 ) {
	        		//continue;
	        	}

				$data = array(
					'date'	=>	$drip_date,
					'week'	=>	$week_num
				);

				if( !empty($users) ) {
					foreach ( $users as $user_num => $user_id ) {
						if( !isset( $user_data[$user_id] ) ) {
							$user_data[$user_id] = array();
						}
						array_push( $user_data[$user_id], $data );
					}
				}				
			}

			if( !empty( $user_data ) ) {
				foreach ( $user_data as $user_id => $data ) {
					$user_meta_key = 'lm_lesson_group_' . $group_data['group_id'].'_info';
					update_user_meta( $user_id, $user_meta_key, $data, '' );
				}
			}

		} else {
			
		}
		
		/*dd( $group_data );

		dd('finish user settings first');*/

		LM_Helper::drip_public_group_lessons( $_POST['lm_group_id'], $group_data );

		//dd($group_data);

		update_post_meta( $_POST['lm_group_id'], 'lm_group_attendance_dates', $group_attendance_dates, '' );
		update_post_meta( $_POST['lm_group_id'], 'lm_group_data', $group_data, '' );

		$redirect_url = $_POST['_wp_http_referer'];
		//$redirect_url = add_query_arg( '' );
		wp_safe_redirect( $redirect_url );
		exit;

	}


	public function get_group_zoom_manage() {
		global $post;
		$group_id 		=  	isset($_GET['lm_group_id']) ? $_GET['lm_group_id'] : 0;

		$group_zoom 	= 	get_post_meta( $group_id, 'lm_group_zoom_info', true );
		$settings = array(
			'media_buttons'	=>	false,
			'quicktags'		=>	false,
			'teeny'			=>	true

		);

		?>
		<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" class="standard-form base" method="POST" autocomplete="off">
			<input type="hidden" name="action" value="lm_group_zoom_callback">

			<!-- <textarea name="lm_group_zoom_info" id="message_content" cols="30" rows="30"><?php echo $group_zoom; ?></textarea> -->
			<?php wp_editor( $group_zoom, 'lm_group_zoom_info', $settings ) ?>

			<div class="submit">
				<input type="submit" name="lm_group_attendance_save" id="lm_group_attendance_save" value="Save Changes ">
			</div>

			<input type="hidden" name="lm_group_id" value="<?php echo $group_id ?>">
			<?php wp_nonce_field( 'lm_group_zoom_save', 'lm_group_zoom_save_wpnonce' ); ?>
		</form>
		<?php

	}

	public function lm_group_zoom_save_callback()
	{
		
		if ( ! isset( $_POST['lm_group_zoom_save_wpnonce'] )  || ! wp_verify_nonce( $_POST['lm_group_zoom_save_wpnonce'], 'lm_group_zoom_save' ) ) {
			wp_die( __('Oops, something went wrong with your submission. Please try again.'), __('something went wrong!') );
		}
		
		update_post_meta( $_POST['lm_group_id'], 'lm_group_zoom_info', $_POST['lm_group_zoom_info'], '' );

		$redirect_url = $_POST['_wp_http_referer'];
		//$redirect_url = add_query_arg( array( 'lm-message' => 'saved', 'lm-status' => 'success' ), $redirect_url );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function get_group_attendance_manage_old()
	{
		global $post;
		$group_id 			=  	isset($_GET['lm_group_id']) ? $_GET['lm_group_id'] : 0;
		$attendance_dates 	= 	get_post_meta( $group_id, 'lm_group_attendance_dates', true );
		$students 			= 	learndash_get_groups_users( $group_id );
		$current_date 		= 	new DateTime();
		
		?>

		<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" class="standard-form base" method="POST" autocomplete="off">

			<input type="hidden" name="action" value="lm_group_attendance_callback">
			
			<div style="overflow-x:auto;">
				<table class="profile-fields">
					<thead>
						<tr>
							<th><?php echo __('Name');?></th>
							<?php foreach ($attendance_dates as $date) {
								?>
								<th><?php echo date( 'm/d', strtotime($date) );?></th>
								<?php
							} ?>
						</tr>
					</thead>

					<tbody>

						<?php foreach ($students as $user) {
							?>
							<tr>
								<td><?php echo $user->display_name; ?></td>
								<?php foreach ($attendance_dates as $date) {
									$date    = new DateTime($date);
									?>
									<td>
									<?php
										if( $current_date < $date ) {
											echo '';
										} else {
											?>
											<div class="checkbox" style="width: 80px;">
												<label >
													<input type="checkbox"  name="sdas">
													Present
												</label>
												<label >
													<input type="checkbox"  name="sdas">
													Absent
												</label>
											</div>
											
											<?php
										}
										?>
									</td>
									<?php
								} ?>
							</tr>
							<?php
						} ?>
					</tbody>
				</table>
			</div>

		</form>

		<?php
	}


	public function get_group_attendance_manage(){
		global $post;
		$group_id 			=  	isset($_GET['lm_group_id']) ? $_GET['lm_group_id'] : 0;
		$attendance_dates 	= 	get_post_meta( $group_id, 'lm_group_attendance_dates', true );
		$students 			= learndash_get_groups_users( $group_id );
		$current_date 		= new DateTime();

		?>

		<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" id="signup_form" class="standard-form base" method="POST" autocomplete="off">

			<input type="hidden" name="action" value="lm_group_attendance_callback">

			<table class="profile-fields attendance-form">
				<tr>
					<th>Users</th>
					<td>
						<select name="users[]" class="lm-user-select" multiple="multiple" style="width: 550px;" required="required">
							<?php
							foreach ($students as $user) {
								echo '<option value="'.$user->ID.'">' . $user->display_name . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th>Date</th>
					<td>
						<select name="date" class="lm-user-select" style="width: 550px;" required="required">
							<option value="">Please Select</option>
							<?php
							foreach ($attendance_dates as $date) {
								$date = new DateTime( $date );
								echo '<option value="'.$date->format('Y-m-d').'">' . $date->format('Y-m-d') . '</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th>Status</th>
					<td>
						<div class="input-options radio-button-options">
							<label class="option-label"><input type="radio" name="attendance" value="1" required="required"><strong>Missed</strong></label>
							<label class="option-label"><input type="radio" name="attendance" value="2" required="required"><strong>Present</strong></label>
							<label class="option-label"><input type="radio" name="attendance" value="3" required="required"><strong>Missed but completed the work</strong></label>
						</div>

					</td>
				</tr>

				<tr>
					<th>Comment</th>
					<td>
						<textarea name="comment" placeholder="<?php echo __('Your Comment'); ?>"></textarea>
					</td>
				</tr>
			</table>

			<div class="submit" style="float: left;">
				<input type="submit" name="lm_group_attendance_save" id="lm_group_attendance_save" value="Save Attendance ">
			</div>

			<input type="hidden" name="lm_group_id" value="<?php echo $group_id ?>">
			<?php wp_nonce_field( 'lm_group_attendance_save', 'lm_group_attendance_save_wpnonce' ); ?>

		</form>

		<hr style="float: left; width: 100%;">

		<h4>Attendance Details</h4>
		<?php echo LM_Helper::get_group_attendance_view( $group_id, $attendance_dates, $students ); ?>

		<?php
		
	}

	public function lm_group_attendance_save_callback()
	{
		global $wpdb;

		if ( ! isset( $_POST['lm_group_attendance_save_wpnonce'] )  || ! wp_verify_nonce( $_POST['lm_group_attendance_save_wpnonce'], 'lm_group_attendance_save' ) ) {
			wp_die( __('Oops, something went wrong with your submission. Please try again.'), __('something went wrong!') );
		}

		$table 				= $wpdb->prefix . 'lm_attendance_logs';
		$group_id 			= $_POST['lm_group_id'];
		$date 				= $_POST['date'];
		$attendance_type 	= $_POST['attendance'];
		$current_user_id 	= get_current_user_id();
		$gmt_offset  		= get_option( 'gmt_offset' );
		
		if ( empty( $gmt_offset ) ) {
			$gmt_offset = 0;
		}

		foreach ($_POST['users'] as $user_id) {

			// lets check if there is any already record for the user and the date posted.
			// if yes, we either need to update the existing record with updated info or skip it.
			$query = $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d AND group_id = %d AND `date` = %s", $user_id, $group_id, $date );
			$has_row = $wpdb->get_row( $query );

			if( null !== $has_row ) {
				$wpdb->update( 
					$table,
					array( 
						'attendance_type'	=>	$attendance_type,
						'comment'			=>	$_POST['comment'],
					),
					array(
						'user_id' 			=> 	$user_id,
						'group_id' 			=> 	$group_id,
						'date'				=>	$date,
					), 
				);
			} else {
				$wpdb->insert( 
					$table,
					array( 
						'user_id' 			=> 	$user_id,
						'group_id' 			=> 	$group_id,
						'attendance_type'	=>	$attendance_type,
						'date'				=>	$date,
						'log_by_user_id'	=>	$current_user_id,
						'comment'			=>	$_POST['comment'],
						'created_at'		=>	current_time( 'mysql', $gmt_offset )
					),
				);
			}

		}

		$redirect_url = $_POST['_wp_http_referer'];
		//$redirect_url = add_query_arg( '' );
		wp_safe_redirect( $redirect_url );
		exit;

	}


	public function lm_group_details_ajax_callback()
	{
		$group_id 	= $_GET['group_id'];
		$data_load 	= $_GET['data'];

		switch( $data_load ) {
			case 'roster':
				echo LM_Helper::get_group_roster( $group_id );
				break;

			case 'attendance':
				echo LM_Helper::get_group_attendance_view( $group_id );
				break;

			case 'schedule':
				echo LM_Helper::get_group_schedule_view( $group_id );
				break;

			case 'zoom':
				echo LM_Helper::get_group_zoom_info( $group_id );
				break;

			case 'instructions':
				echo LM_Helper::get_group_lead_instructions( $group_id );
				break;

			case 'form':
				echo LM_Helper::get_group_form( $group_id );
				break;

			case 'leader_instructions_1':
				echo LM_Helper::get_group_lead_instructions_one( $group_id );
				break;

			case 'leader_instructions_2':
				echo LM_Helper::get_group_lead_instructions_two( $group_id );
				break;
		}

		wp_die();
	}

	public function get_login_form()
	{
		ob_start();

		?>
<h3 class="login-text">Please login to get started.</h3>
<div id="login-page-form">
[i4w_login_form label_username='Email' redirect='#use_last_page#']
[i4w_is_logged_in]
  Hi [i4w_db_FirstName], You already logged in.
[/i4w_is_logged_in]
<a href="<?php echo home_url(''); ?>/wp-login.php?action=lostpassword">Lost Your Password?</a>
</div>		
		<?php

		$output = ob_get_contents();
        ob_end_clean();
        return do_shortcode( $output );
	}


	public function lm_infusionsoft_listner_callback() {
		global $lm_logs;
	
		if( !function_exists('lm_debug_log') ) {
			return;
		}

		if( !isset($_GET['lm-listner']) ) {
			return;
		}


		$arr_rh = json_decode( file_get_contents( 'php://input' ), TRUE );

		if ( ! isset( $arr_rh['event_key'] ) OR ! $arr_rh['event_key'] OR ! isset( $arr_rh['object_type'] ) OR ! $arr_rh['object_type'] OR ! isset( $arr_rh['object_keys'] ) OR ! $arr_rh['object_keys'] OR ! isset( $arr_rh['api_url'] ) ) :
			exit;
		endif;

		lm_debug_log( sprintf('LM Note: %s', maybe_serialize( $arr_rh ) )  );

		if( $arr_rh['event_key'] == 'contactGroup.applied' ) {

			foreach ($arr_rh['object_keys'] as $object) {
				$tag_id = $object['tag_id'];
				$contact_ids = array();
				
				foreach ($object['contact_details'] as $contact ) {
					if( !in_array($contact['id'], $contact_ids) ) {
						$contact_ids[] = $contact['id'];
					}
				}

				if( !empty( $tag_id ) && !empty($contact_ids) ) {
					LM_Helper::find_tag_group_assign_user( $tag_id, $contact_ids );
				} else {
					lm_debug_log( sprintf('LM Note: empty data %s', '' )  );
				}
				
			}
		}

	}

	public function show_notifications()
	{
		if( !isset($_GET['lm-message']) || isset($_GET['lm-message']) && empty($_GET['lm-message']) ) {
			return;
		}

		$type 		= $_GET['lm-message'];
		$message 	=	'';

		switch( $type ) {
			case 'saved': 
				$message = __('Successfully Updated!');

			default:
				break;
		}

		?>
		<div class="lm-message success"><?php echo wpautop( $message ); ?></div>
		<?php

	}


	public function load_student_form_details() {
		
		$group_id 	= $_POST['group_id'];
		$user_id 	= $_POST['student_id'];

		$form_id 	= get_field('student_form', 'option');
		$form 		= GFFormsModel::get_form_meta( absint( $form_id ) );
		$form    	= apply_filters( 'gform_admin_pre_render', $form );
		$form    	= apply_filters( 'gform_admin_pre_render_' . $form_id, $form );

		$search_criteria = array(
			'status'        => 'active',
			'field_filters' => array(
				array(
					'key'   => 'created_by',
					'value' => $user_id
				),
				array(
					'key'   => '20',
					'value' => $group_id,
					'operator'	=>	'is'
				)
			)
		);

		$entries    = GFAPI::get_entries( $form_id, $search_criteria );
		
		ob_start();

		if( empty( $entries ) ) {
			$output = 'The student has not submitted the information yet.';
			echo wpautop( $output );
			wp_die();
		}
		
		$lead 	 	= $entry = $entries[0];
		?>
		
		<table class="widefat fixed entry-detail-view">
			<tbody>
				<?php
				$count = 0;
				$field_count = sizeof( $form['fields'] );
				$has_product_fields = false;

				foreach ( $form['fields'] as $field ) {

					if( $field->id == 20 )
						continue;

					$content = $value = '';

					switch ( $field->get_input_type() ) {
						case 'section' :
							if ( ! GFCommon::is_section_empty( $field, $form, $lead ) || $display_empty_fields ) {
								$count ++;
								$is_last = $count >= $field_count ? ' lastrow' : '';

								$content = '
	                                <tr>
	                                    <td colspan="2" class="entry-view-section-break' . $is_last . '">' . esc_html( GFCommon::get_label( $field ) ) . '</td>
	                                </tr>';
							}
							break;

						case 'captcha':
						case 'html':
						case 'password':
						case 'page':
							// Ignore captcha, html, password, page field.
							break;

						default :
							// Ignore product fields as they will be grouped together at the end of the grid.
							if ( GFCommon::is_product_field( $field->type ) ) {
								$has_product_fields = true;
								break;
							}

							$value = RGFormsModel::get_lead_field_value( $lead, $field );

							if ( is_array( $field->fields ) ) {
								// Ensure the top level repeater has the right nesting level so the label is not duplicated.
								$field->nestingLevel = 0;
							}

							$display_value = GFCommon::get_lead_field_display( $field, $value, $lead['currency'] );

							/**
							 * Filters a field value displayed within an entry.
							 *
							 * @since 1.5
							 *
							 * @param string   $display_value The value to be displayed.
							 * @param GF_Field $field         The Field Object.
							 * @param array    $lead          The Entry Object.
							 * @param array    $form          The Form Object.
							 */
							$display_value = apply_filters( 'gform_entry_field_value', $display_value, $field, $lead, $form );

							if ( $display_empty_fields || ! empty( $display_value ) || $display_value === '0' ) {
								$count ++;
								$is_last  = $count >= $field_count && ! $has_product_fields ? true : false;
								$last_row = $is_last ? ' lastrow' : '';

								$display_value = empty( $display_value ) && $display_value !== '0' ? '&nbsp;' : $display_value;

								$content = '
	                                <tr>
	                                    <th class="entry-view-field-name"><strong>' . esc_html( GFCommon::get_label( $field ) ) . '</strong></th>
	                                    <td class="entry-view-field-value' . $last_row . '">' . $display_value . '</td>
	                                </tr>
	                                <tr>';
							}
							break;
					}

					/**
					 * Filters the field content.
					 *
					 * @since 2.1.2.14 Added form and field ID modifiers.
					 *
					 * @param string $content    The field content.
					 * @param array  $field      The Field Object.
					 * @param string $value      The field value.
					 * @param int    $lead['id'] The entry ID.
					 * @param int    $form['id'] The form ID.
					 */
					$content = gf_apply_filters( array( 'gform_field_content', $form['id'], $field->id ), $content, $field, $value, $lead['id'], $form['id'] );

					echo $content;
				}
				?>
			</tbody>
		</table>
		<?php
		$output = ob_get_contents();
        ob_end_clean();
        echo $output;

        wp_die();
	}
}
