<?php

/**
 * Helper methods & functions
 *
 * @link       https://unaibamir.com
 * @since      1.0.0
 *
 * @package    Life_Mastery_Group_Management
 * @subpackage Life_Mastery_Group_Management/includes
 */

/**
 *
 * This class defines all code necessary to run during the plugin's process
 *
 * @since      1.0.0
 * @package    Life_Mastery_Group_Management
 * @subpackage Life_Mastery_Group_Management/includes
 * @author     Unaib Amir <unaibamiraziz@gmail.com>
 */
class LM_Helper {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */


	public static function get_group_course( $group_id ) {

		$group_courses 	=	learndash_group_enrolled_courses( $group_id );
		$course_id 		= 	$group_courses[0];
		return $course_id;
	}


	public static function get_course_lessons( $course_id ) {
		$course_lessons = 	learndash_get_course_lessons_list( $course_id, array( 'sfwd-lessons' ) );
		return $course_lessons;
	}


	public static function generate_lesson_dates( $group_id, $weeks, $start_date ) {
		//dd($weeks);

		$dates 		= array();
		$dates[] 	= $start_date;
		$date 		= '';

		for ($i = 0; $i < $weeks['total_weeks'] + 1; $i++) {

			$date 	 = date( 'Y-m-d', strtotime( "+1 week", strtotime( !empty($date) ? $date : $start_date ) ) );

			$dates[] = $date;
			continue;
		}

		unset($dates[8]);
		$dates = array_values($dates);
		
		return $dates;
	}

	public static function generate_lesson_discuss_dates( $group_id, $weeks, $start_date ) {
		
		$lesson_dates 	= LM_Helper::generate_lesson_dates( $group_id, $weeks, $start_date );

		$dates 		= array();
		$date 		= '';

		foreach ($lesson_dates as $key => $lesson_date) {
			$date 	 = date( 'Y-m-d', strtotime( "-3 week monday", strtotime( $lesson_date ) ) );
			$dates[] 	= $date;
			continue;
		}
		
		$dates[0] = $dates[1] = '';
		$dates = array_values($dates);
		return $dates;
	}


	public static function get_group_course_weeks( $group_id )
	{

		$course_id 		= 	LM_Helper::get_group_course( $group_id );
		$course_lessons = 	LM_Helper::get_course_lessons( $course_id );

		$total_lessons 	= 	count($course_lessons);
		$total_weeks 	= 	ceil($total_lessons / 2) + 1; // one additional week to get all lessons divided equally
		$weeks_array	= 	$data = array();
		
		for ($i = 0; $i < $total_weeks; $i++) {
			$weeks_array[ $i ] = array();
		}

		$data['total_weeks'] = $total_weeks;
		$data['weeks_array'] = $weeks_array;
		
		return $data;
	}

	public static function get_group_course_lesson_weeks( $group_id )
	{
		$course_id 		= 	LM_Helper::get_group_course( $group_id );
		$course_lessons = 	LM_Helper::get_course_lessons( $course_id );
		$weeks 			= 	LM_Helper::get_group_course_weeks( $group_id );
		$total_weeks 	= 	$weeks['total_weeks'];
				
		$filtered_lessons = $data = array();
		foreach ($course_lessons as $key => $lesson) {
			$filtered_lessons[$key] = $lesson['post']->ID;
		}

		$lessons = array_slice( $filtered_lessons, 0, 1 );
		$data[0] = $lessons;
		array_shift($filtered_lessons);

		$slice_num  = 0;
		foreach ( $weeks['weeks_array'] as $week_num => $week_array) {

			if( $week_num == 0 ) {
				continue;
			}

			$lessons = array_slice( $filtered_lessons, $slice_num * 2, $slice_num == 0 ? 2 : 2 );
			$data[$week_num] = $lessons;

			$slice_num++;
		}

		$last_index = array_key_last($data);
		$last_lesson_id = $data[$last_index][0];

		array_push( $data[$last_index - 1], $last_lesson_id);
		unset($data[$last_index]);

		return $data;
	}


	public static function drip_admin_group_lessons($group_id)
	{
		$weeks 			= 	LM_Helper::get_group_course_weeks( $group_id );
		$start_date 	=	get_post_meta( $group_id, 'lm_course_start_date', true );
		$lesson_dates 	= 	LM_Helper::generate_lesson_dates( $group_id, $weeks, $start_date );
        $discuss_dates 	= 	LM_Helper::generate_lesson_discuss_dates( $group_id, $weeks, $start_date );
        $course_lesson_weeks = LM_Helper::get_group_course_lesson_weeks( $group_id );
        array_unshift($course_lesson_weeks, array(9999999));

        $gmt_offset  	= get_option( 'gmt_offset' );
		if ( empty( $gmt_offset ) ) {
			$gmt_offset = 0;
		}
		$offset      	= ( $gmt_offset * ( 60 * 60 ) ) * - 1;

        $data 			=	array();
        $format 		= 'Y-m-d H:01:s';

        foreach ($course_lesson_weeks as $week_num => $lesson) {

        	$data[ $week_num ] = array(
        		'lesson_date'	=>	$lesson_dates[$week_num],
        		'lesson_data'	=>	$lesson
        	);
        }

        if( empty($data) ) {
        	return;
        }

        $meta_key = 'uncanny_pro_toolkitUncannyDripLessonsByGroup-' . $group_id;
        
        foreach ($data as $week_num => $lesson_info) {
        	
        	$date 	 	= date( $format, strtotime( $lesson_info['lesson_date'] ) );
        	$drip_date 	= strtotime($date);
        	$drip_date = (int) $drip_date + $offset;

        	foreach ( $lesson_info['lesson_data'] as $lesson_id) {
        		update_post_meta( $lesson_id, $meta_key, $drip_date, '' );
        	}
        }
	}


	public static function drip_public_group_lessons( $group_id, $posted_data ) {

		//dd($posted_data, false);

		$gmt_offset  	= get_option( 'gmt_offset' );
		if ( empty( $gmt_offset ) ) {
			$gmt_offset = 0;
		}
		$offset      	= ( $gmt_offset * ( 60 * 60 ) ) * - 1;

        $data 			=	array();
        $format 		= 'Y-m-d H:01:s';

        $meta_key = 'uncanny_pro_toolkitUncannyDripLessonsByGroup-' . $group_id;

        foreach ($posted_data['lm_lessons'] as $week_num => $lesson_arr) {

        	$data[ $week_num ] = array(
        		'lesson_date'	=>	$posted_data['lesson_dates'][$week_num],
        		'lesson_data'	=>	$lesson_arr
        	);
        }

        foreach ($data as $week_num => $lesson_info) {
        	
        	$date 	 	= date( $format, strtotime( $lesson_info['lesson_date'] ) );
        	$drip_date 	= strtotime($date);
        	$drip_date = (int) $drip_date + $offset;

        	foreach ( $lesson_info['lesson_data'] as $lesson_id) {
        		update_post_meta( $lesson_id, $meta_key, $drip_date, '' );
        	}
        }

	}


	public static function get_group_attendance_view( $group_id, $attendance_dates = array(), $students = array() )
	{
		global $wpdb;

		if( empty($attendance_dates) ) {
			$attendance_dates 	= 	get_post_meta( $group_id, 'lm_group_attendance_dates', true );
		}

		if( empty($attendance_dates) ) {
			return __( 'No data available' );
		}

		$user = wp_get_current_user();
		
		if( empty($students) ) {
			$students 			= 	learndash_get_groups_users( $group_id );
		}

		if( !learndash_is_group_leader_user( $user ) ) {
			$students = array();
			$students[] = $user;
		}

		$table 				= $wpdb->prefix . 'lm_attendance_logs';
		$current_date 		= 	new DateTime();
		
		ob_start();
		?>
		<div style="overflow-x:auto;  width: 100%;">
			<table class="profile-fields group-attendance">
				<thead>
					<tr>
						<th><?php echo __('Name');?></th>
						<?php 
						if( !empty($attendance_dates) ) {
							foreach ($attendance_dates as $date) {
								?>
								<th><?php echo date( 'm/d', strtotime($date) );?></th>
								<?php
							}
						}
						?>
					</tr>
				</thead>

				<tbody>

					<?php foreach ($students as $user) {
						?>
						<tr>
							<td id="user-<?php echo $user->ID; ?>">
								<span style="min-width: 140px;display: block;"><?php echo $user->display_name; ?></span>
							</td>
							<?php 
							if( !empty($attendance_dates) ) {
								foreach ($attendance_dates as $date) {
									
									$date = new DateTime( $date );
									
									$query = $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d AND group_id = %d AND `date` = %s", $user->ID, $group_id, $date->format('Y-m-d') );

									$has_row = $wpdb->get_row( $query );

									/*if( $current_date <= $date ) {
										$status = '';
									} else {
										$status = $has_row !== null && $has_row->attendance_type == '1' ? __('X') : __('-');
									}*/

									if( $current_date <= $date ) {
										$status = '';
									} else {
										if( $has_row !== null ) {
											if( $has_row->attendance_type == '2' ) {
												$status = 'X';
											} else if( $has_row->attendance_type == '1' ) {
												$status = '-';
											} else if( $has_row->attendance_type == '3' ) {
												$status = '/';
											} else {
												$status = '';
											}
										}
										else {
											$status = '';
										}
									}

									?>
									<td>
										<?php echo $status;  ?>
									</td>
									<?php
								}
							}
							?>
						</tr>
						<?php
					} ?>
				</tbody>
			</table>
		</div>

		<?php

		$output = ob_get_contents();
        ob_end_clean();
        return $output;
	}

	public static function get_group_details_ajax( $group_id )
	{
		ob_start();

		$tab_ajax_roster_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'roster',
			'_wpnonce'	=>	wp_create_nonce( 'lm_ajax_tab_nonce' )
		), admin_url( 'admin-ajax.php' ));

		$tab_ajax_attendance_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'attendance',
			'_wpnonce'	=>	wp_create_nonce( 'lm_ajax_tab_nonce' )
		), admin_url( 'admin-ajax.php' ));

		$tab_ajax_schedule_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'schedule',
			'_wpnonce'	=>	wp_create_nonce( 'lm_ajax_tab_nonce' )
		), admin_url( 'admin-ajax.php' ));

		$tab_ajax_zoom_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'zoom',
			'_wpnonce'	=>	wp_create_nonce( 'lm_ajax_tab_nonce' )
		), admin_url( 'admin-ajax.php' ));

		$tab_ajax_instructions_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'instructions',
			'_wpnonce'	=>	wp_create_nonce( 'lm_ajax_tab_nonce' )
		), admin_url( 'admin-ajax.php' ));

		$tab_ajax_form_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'form',
			'_wpnonce'	=>	wp_create_nonce( 'lm_ajax_tab_nonce' )
		), admin_url( 'admin-ajax.php' ));

		$admin_ajax_form_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'admin_form',
			'_wpnonce'	=>	wp_create_nonce( 'lm_ajax_tab_nonce' )
		), admin_url( 'admin-ajax.php' ));


		$show_user_tab		= false;
		$user_id 			= get_current_user_id();

		/*$user_lesson_date 	= get_user_meta( $user_id, "lm_lesson_group_{$group_id}_date", true );
		$user_lesson_week 	= get_user_meta( $user_id, "lm_lesson_group_{$group_id}_week", true );


		// ensure we have something to go forward with
		if( !empty( $user_lesson_date ) ) {
			$current_date 		= new DateTime();
			$lesson_date 		= new DateTime( date('Y-m-d', $user_lesson_date) );
			$date_interval 		= $current_date->diff( $lesson_date );

			if( ($date_interval->format('%R%a') > -1) && ($date_interval->format('%R%a') < 7) ) {
				$show_user_tab 	= true;
			}
		}*/

		$user_lesson_data = get_user_meta( $user_id, "lm_lesson_group_{$group_id}_info", true );
		if( !empty( $user_lesson_data ) && is_array( $user_lesson_data ) ) {
			$current_date 		= new DateTime();
			foreach ($user_lesson_data as $lesson_info ) {
				$lesson_date 		= new DateTime( date('Y-m-d', $lesson_info['date']) );
				$date_interval 		= $current_date->diff( $lesson_date );
				if( ($date_interval->format('%R%a') > -1) && ($date_interval->format('%R%a') < 7) ) {
					$show_user_tab 	= true;
				} else {
					continue;
				}
			}
		}

		?>
		<div id="lm-group-tab-<?php echo $group_id; ?>" data-instructions="<?php echo $show_user_tab === true ? 'displayed' : 'hidden'; ?>"  class="lm-group_member-details tabs ui-tabs ui-widget ui-widget-content ui-corner-all" >
			<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_roster_url; ?>">Roster</a></li>
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_attendance_url; ?>">Attendance</a></li>
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_schedule_url; ?>">Class Schedule</a></li>
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_zoom_url; ?>">Zoom</a></li>
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_form_url; ?>">Promise</a></li>
				<?php
				if( $show_user_tab && !current_user_can( 'manage_options' ) ) {
					?>
					<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_instructions_url; ?>">Lead Instructions</a></li>
					<?php
				}
				if( learndash_is_group_leader_user( $user ) || current_user_can( 'manage_options' ) ) {
					?>
					<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $admin_ajax_form_url; ?>">Lead Instructions</a></li>
					<?php
				}
				?>
			</ul>

		</div>

		<?php
		$output = ob_get_contents();
        ob_end_clean();
        return $output;
	}

	public static function get_group_details( $group_id ) {

		ob_start();

		?>
		<div class="lm-group-details tabs">
			<ul>
				<li><a href="#group-<?php echo $group_id; ?>-roster">Roster</a></li>
				<li><a href="#group-<?php echo $group_id; ?>-attendance">Attendance</a></li>
				<li><a href="#group-<?php echo $group_id; ?>-schedule">Class Schedule</a></li>
				<li><a href="#group-<?php echo $group_id; ?>-zoom">Zoom</a></li>
			</ul>
			<div id="group-<?php echo $group_id; ?>-roster">
				<?php echo LM_Helper::get_group_roster( $group_id ); ?>
			</div>

			<div id="group-<?php echo $group_id; ?>-attendance">
				<?php echo LM_Helper::get_group_attendance_view( $group_id ); ?>
			</div>

			<div id="group-<?php echo $group_id; ?>-schedule">
				<?php echo LM_Helper::get_group_schedule_view( $group_id ); ?>
			</div>

			<div id="group-<?php echo $group_id; ?>-zoom">
				<?php echo LM_Helper::get_group_zoom_info( $group_id ); ?>
			</div>
		</div>
		<?php

		$output = ob_get_contents();
        ob_end_clean();
        return $output;
	}


	public static function get_group_roster( $group_id )
	{
		global $wpdb;
		$table 				= 	'_isContact';
		$curr_user 			= 	wp_get_current_user();
		$students 			= 	learndash_get_groups_users( $group_id );
		$leaders 			= 	learndash_get_groups_administrators( $group_id );

		ob_start();
		?>

		<div style="overflow-x:auto; width: 100%;">
			<table class="profile-fields group-attendance">
				<thead>
					<tr>
						<!-- <th>First Name</th>
						<th>Last Name</th> -->
						<th>Name</th>
						<th>Email</th>
						<?php if( learndash_is_group_leader_user( $curr_user ) ): ?>
							<th>Phone 1</th>
						<?php endif; ?>
						<th>State</th>
					</tr>
				</thead>

				<tbody>
					<?php
					if( !empty($students) ) {
						foreach ($students as $user) {
							$contact_id = get_user_meta( $user->ID, 'infusion4wp_user_id', true );
							if( !empty($contact_id) ) {
								$phone = $wpdb->get_col( "SELECT meta_value FROM $table WHERE id = $contact_id AND meta_field = 'Phone1'" );
								$state = $wpdb->get_col( "SELECT meta_value FROM $table WHERE id = $contact_id AND meta_field = 'State'" );
							}
							?>
							<tr>
								<!-- <td><?php echo $user->first_name; ?></td>
								<td><?php echo $user->last_name; ?></td> -->
								<td><?php echo $user->display_name; ?></td>
								<td><?php echo $user->user_email; ?></td>
								<?php if( learndash_is_group_leader_user( $curr_user ) ): ?>
									<td><?php echo !empty($contact_id) && isset($phone[0]) ? $phone[0] : ""; ?></td>
								<?php endif; ?>
								<td><?php echo !empty($contact_id) && isset($state[0]) ? $state[0] : ""; ?></td>
							</tr>
							<?php
						}
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		$output = ob_get_contents();
        ob_end_clean();
        return $output;
	}


	public static function get_group_zoom_info( $group_id )
	{
		$zoom_info = get_post_meta( $group_id, 'lm_group_zoom_info', true );
		if( !empty($zoom_info) ) {
			$zoom_info = wpautop( $zoom_info );
		} else {
			$zoom_info = __( 'No data available' );
		}
		
		return $zoom_info;
	}

	public static function get_group_lead_instructions( $group_id )
	{
		$user_id 				= get_current_user_id();
		$user_lesson_data 		= get_user_meta( $user_id, "lm_lesson_group_{$group_id}_info", true );	
		//$week_content 			= get_field( "questions_week_" . $user_lesson_week_num, "option" );
		$lead_instructions 		= get_field( "lead_instructions" , "option" );
		$defult_instructions 	= get_field( "default_questions", "option" );
		$content 				= '';
		
		//$content 				.= sprintf('<h4>%s</h4>', __('Lead Instructions'));
		$content 				.= $lead_instructions;
		$content 				.= '<br>';

		if( learndash_is_group_leader_user( $user_id ) ) {

			$group_data = get_post_meta( $group_id, 'lm_group_data', true );
			if( !empty($group_data) && isset($group_data['lesson_dates']) && !empty( $group_data['lesson_dates'] ) ) {
				$current_date 		= new DateTime();
				$week_num = '';
				foreach ($group_data['lesson_dates'] as $key => $lesson_date ) {
					
					if( $key == 0 ) {
						//continue;
					}

					$lesson_date 		= new DateTime( date('Y-m-d', strtotime($lesson_date)) );
					$date_interval 		= $current_date->diff( $lesson_date );

					if( ($date_interval->format('%R%a') > -1) && ($date_interval->format('%R%a') < 7) ) {
						$week_num 	= $key - 1;
						break;
					}
				}

				if( $week_num ) {

					$week_content 	= get_field( "questions_week_" . $week_num, "option" );
					$content 		.= $week_content;
				}

				
			}

		} else {
			if( !empty($user_lesson_data) && is_array($user_lesson_data) ) {
				$week_num = '';
				$current_date 		= new DateTime();
				foreach ($user_lesson_data as $key => $lesson_info ) {
					$lesson_date 		= new DateTime( date('Y-m-d', $lesson_info['date']) );
					$date_interval 		= $current_date->diff( $lesson_date );
					if( ($date_interval->format('%R%a') > -1) && ($date_interval->format('%R%a') < 7) ) {
						$week_num 	= $lesson_info['week'];
						break;
					}
				}

				if( $week_num ) {
					$week_content 	= get_field( "questions_week_" . $week_num, "option" );
					$content 		.= $week_content;
				}
				
			} else {
				$content 			.= $defult_instructions;
				$content 			.= $week_content;
			}

		}
		
		$output 			= wpautop( $content );

		return $output;
	}


	public static function get_group_schedule_view( $group_id )
	{
		$group_courses 	=	learndash_group_enrolled_courses( $group_id );
		if( empty( $group_courses ) ){
			return __( 'No data available' );
		}
		$course_id 		= 	$group_courses[0];
		$group_data 	= 	get_post_meta( $group_id, 'lm_group_data', true );
		$group_start_date 	= 	get_post_meta( $group_id, 'lm_course_start_date', true );
		//dd($group_data);

		if( !isset($group_data['lesson_dates']) || isset($group_data['lesson_dates']) && empty($group_data['lesson_dates']) ){
			return __( 'No data available' );
		}

		$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
		$lesson_ids = $ld_course_steps_object->get_children_steps( $course_id, 'sfwd-lessons' );

		//$course_lessons = learndash_get_course_lessons_list( $course_id, array( 'sfwd-lessons' ) );
		$total_rows 	= ceil( count($lesson_ids) / 2 );
		//$total_rows 	= $total_rows + count( $sections );
		$lessons 		= array();
		array_unshift($lessons , array(
			'post' => (object) array(
				'ID'			=>	9999999,
				'post_title' 	=>	__('Introductions & Tech Check!')
			)
		));
		
		foreach ($lesson_ids as $lesson) {
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
		
		if( !empty( $sections ) ) {

			reset($sections);
			$key = key($sections);
			unset($sections[$key]);
		}


		?>

		<div style="overflow-x:auto;">
			<table class="profile-fields group-attendance group-schedule">
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

							?>
							<tr>
								<td style="width: 40px;"><?php echo $call; ?></td>
								<td style="width: 90px;"><?php echo $call_text; ?></td>
								<td>
									<?php echo isset( $group_data['lesson_dates'][$counter] ) ? $group_data['lesson_dates'][$counter] : '' ; ?>
								</td>
								<td>
									<?php echo isset( $group_data['lesson_review_dates'][$counter] ) ? $group_data['lesson_review_dates'][$counter] : ''; ?>
								</td>
								<td>

									<?php
									$lesson_titles = array();
									if( !empty($group_data['lm_lessons'][$counter]) ) {
										foreach ($group_data['lm_lessons'][$counter] as $lesson_id) {
											if(  $lesson_id == 9999999 ) {
												$lesson_titles[] = __('Introductions & Tech Check!');
											} else {
												$lesson_titles[] = get_the_title( $lesson_id );
											}

											
										}
									}
									echo implode('<br>', $lesson_titles);
									?>
								</td>
								<td>
									<?php
									$users_info = array();
									if( isset($group_data['users'][$counter]) && !empty($group_data['users'][$counter]) ) {
										foreach ($group_data['users'][$counter] as $user_id) {
											$user 			= get_user_by( 'ID', $user_id );
											$users_info[] 	= $user->display_name;
										}
									}
									echo implode('<br>', $users_info);
									?>
								</td>
							</tr>

							<?php

							if( $call == 2 ) {
								?>
								<tr>
									<td colspan="5">Phase 1</td>
									<td>
										<?php
										$users_info = array();
										if( isset($group_data['s_users'][0]) && !empty($group_data['s_users'][0]) ) {
											foreach ($group_data['s_users'][0] as $user_id) {
												$user 			= get_user_by( 'ID', $user_id );
												$users_info[] 	= $user->display_name;
											}
										}
										echo implode('<br>', $users_info);
										?>
									</td>
								</tr>
								<?php
							}

							if( $call == 8 ) {
								?>
								<tr>
									<td colspan="5">Phase 2</td>
									<td>
										<?php
										$users_info = array();
										if( isset($group_data['s_users'][1]) && !empty($group_data['s_users'][1]) ) {
											foreach ($group_data['s_users'][1] as $user_id) {
												$user 			= get_user_by( 'ID', $user_id );
												$users_info[] 	= $user->display_name;
											}
										}
										echo implode('<br>', $users_info);
										?>
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
		</div>

		<?php

	}


	public static function get_group_form( $group_id )
	{
		$user = wp_get_current_user();

		if( learndash_is_group_leader_user( $user ) || current_user_can( 'manage_options' ) ) {
			echo self::lm_group_leader_form_management( $group_id, $user );
		} else {
			echo self::lm_group_member_form_management( $group_id, $user );
		}
		
	}

	public static function lm_group_leader_form_management( $group_id, $user )
	{
		$form_id 			= get_field('student_form', 'option');
		$students 			= 	learndash_get_groups_users( $group_id );

		ob_start();
		?>
		<form action="<?php echo admin_url( 'admin-ajax.php' ) ?>" id="signup_form" class="standard-form base" method="POST" autocomplete="off">
			<table class="profile-fields attendance-form student-form-details">
				<tbody>
					<tr>
						<th><label for="student_view_form"><strong>Select Student</strong></label></th>
						<td>
							<select name="student_view_form" id="student_view_form" class="student_view_form lm-user-select" data-group_id="<?php echo $group_id; ?>">
								<option value="">Please Select</option>
								<?php
								foreach ($students as $user) {
									echo '<option value="'.$user->ID.'">'.$user->display_name.'</option>';
								}
								?>
							</select>
							<p class="description">Please select student to view form submissions</p>
						</td>
					</tr>
					<tr class="load-student-form-wrapper">
						<td colspan="2">
							<div class="load-student-form-details"></div>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
						
		<?php
		$output = ob_get_contents();
        ob_end_clean();
        return $output;
		
	}

	public static function lm_group_member_form_management( $group_id, $user )
	{
		$search_criteria = array(
			'status'        => 'active',
			'field_filters' => array(
				array(
					'key'   => 'created_by',
					'value' => $user->ID
				),
				array(
					'key'   => '20',
					'value' => $group_id,
					'operator'	=>	'is'
				)
			)
		);

		$entries         = GFAPI::get_entries( $form_id, $search_criteria );

		// if user has already submitted the form for the particular group
		if( !empty( $entries ) ) {

			$output = self::get_form_entry_details( $entries[0], $group_id, $user );
			return $output;

		} else {
			
			$form_id = get_field('student_form', 'option');
			$form_shortcode = '[gravityform id="'.$form_id.'" title="false" description="false" ajax="true" field_values="group_id='.$group_id.'"]';
			
			//return apply_filters( 'the_content', do_shortcode( $form_shortcode ) );
			return do_shortcode( $form_shortcode );
		}

	}

	public static function get_form_entry_details( $entry, $group_id, $user )
	{
		$form_id = get_field('student_form', 'option');
		$form = GFFormsModel::get_form_meta( absint( $form_id ) );

		$form_id = absint( $form['id'] );
		$lead 	 = $entry;

		$form    = apply_filters( 'gform_admin_pre_render', $form );
		$form    = apply_filters( 'gform_admin_pre_render_' . $form_id, $form );

		ob_start();
		?>
		<p>You have already submitted the form. Please see below your form submission. </p>
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
	                                    <th class="entry-view-field-name">' . esc_html( GFCommon::get_label( $field ) ) . '</th>
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
        return $output;
	}


	public static function find_tag_group_assign_user( $tag_id, $contact_ids = array() ) {

		lm_debug_log( sprintf('LM Note: infusionSoft data recieved, tagID: %s contact_ids: %s', $tag_id, var_export($contact_ids, true) )  );

		$args = array(
			'post_type' 		=> 	'groups',
			'post_status' 		=> 	'publish',
			'posts_per_page'    => 	-1,
			'meta_key'			=>	'lm_group_tag',
			'meta_value'		=> 	$tag_id,
		);
		
		$query = new WP_Query( $args );

		if( is_array($query->posts) && !empty($query->posts) ) {
			
			$group_ids = wp_list_pluck( $query->posts, 'ID' );

			if( empty( $group_ids ) ) {
				return;
			}

			$user_args = array(
				'meta_query'	=>	array(
					array(
						'key'		=>	'infusion4wp_user_id',
						'value'		=> 	$contact_ids,
						'compare'	=>	'IN'
					)
				),
				'fields'		=>	'ID'		
			);

			$user_query = new WP_User_Query( $user_args );

			$user_ids  = $user_query->get_results();

			if( empty( $user_ids ) ) {
				lm_debug_log( sprintf('LM Note: no users found against contact_ids: %s', $contact_ids )  );
				return;
			}

			foreach ($user_ids as $user_id) {

				foreach ($group_ids as $group_id) {

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
