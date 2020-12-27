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
		
		if( empty($students) ) {
			$students 			= 	learndash_get_groups_users( $group_id );
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
							<td>
								<span style="min-width: 140px;display: block;"><?php echo $user->display_name; ?></span>
							</td>
							<?php 
							if( !empty($attendance_dates) ) {
								foreach ($attendance_dates as $date) {
									
									$date = new DateTime( $date );
									
									$query = $wpdb->prepare( "SELECT * FROM $table WHERE user_id = %d AND group_id = %d AND `date` = %s", $user->ID, $group_id, $date->format('Y-m-d') );

									$has_row = $wpdb->get_row( $query );

									if( $current_date <= $date ) {
										$status = '';
									} else {
										$status = $has_row !== null && $has_row->attendance_type == 'present' ? strtoupper($has_row->attendance_type) : __('X');
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
		), admin_url( 'admin-ajax.php' ));

		$tab_ajax_attendance_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'attendance',
		), admin_url( 'admin-ajax.php' ));

		$tab_ajax_schedule_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'schedule',
		), admin_url( 'admin-ajax.php' ));

		$tab_ajax_zoom_url = add_query_arg(array(
			'action'	=>	'lm_load_group_data',
			'group_id'	=>	$group_id,
			'data'		=>	'zoom',
		), admin_url( 'admin-ajax.php' ));

		?>
		<div class="lm-group-details tabs ui-tabs ui-widget ui-widget-content ui-corner-all">
			<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_roster_url; ?>">Roster</a></li>
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_attendance_url; ?>">Attendance</a></li>
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_schedule_url; ?>">Class Schedule</a></li>
				<li class="ui-state-default ui-corner-top"><a class="ui-tabs-anchor" href="<?php echo $tab_ajax_zoom_url; ?>">Zoom</a></li>
			</ul>

			
			<?php /* ?>
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
			<?php */ ?>
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
		$students 			= 	learndash_get_groups_users( $group_id );
		$leaders 			= 	learndash_get_groups_administrators( $group_id );

		ob_start();
		?>

		<div style="overflow-x:auto; width: 100%;">
			<table class="profile-fields group-attendance">
				<thead>
					<tr>
						<td>First Name</td>
						<td>Last Name</td>
						<td>Name</td>
						<td>Email</td>
						<td>Phone 1</td>
						<td>State</td>
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
								<td><?php echo $user->first_name; ?></td>
								<td><?php echo $user->last_name; ?></td>
								<td><?php echo $user->display_name; ?></td>
								<td><?php echo $user->user_email; ?></td>
								<td><?php echo !empty($contact_id) && isset($phone[0]) ? $phone[0] : ""; ?></td>
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

		$course_lessons = learndash_get_course_lessons_list( $course_id, array( 'sfwd-lessons' ) );
		$total_rows 	= ceil( count($course_lessons) / 2 );
		//$total_rows 	= $total_rows + count( $sections );
		$lessons 		= array();
		array_unshift($lessons , array(
			'post' => (object) array(
				'ID'			=>	9999999,
				'post_title' 	=>	__('Introductions & Tech Check!')
			)
		));
		
		foreach ($course_lessons as $lesson) {
			$lessons[] 	= array(
				'post' => (object) array(
					'ID'			=>	$lesson['post']->ID,
					//'post_title' 	=>	sprintf(__('Lesson %s'), $lesson['sno'] )
					'post_title' 	=>	$lesson['post']->post_title
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
			<table class="profile-fields group-attendance">
				<thead>
					<tr>
						<th class="" style="width: 40px;"><?php echo __('Call #');?></th>
						<th class="">&nbsp;</th>
						<th class="" style="width: 110px;"><?php echo __('Class Date');?></th>
						<th class="" style="width: 110px;"><?php echo __('Discuss Date');?></th>
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
											$users_info[] 	= $user->display_name . " (".$user->user_email.")";
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
										if( !empty($sections) && isset($group_data['s_users'][array_key_first($sections)]) && !empty($group_data['s_users'][array_key_first($sections)]) ) {
											foreach ($group_data['s_users'][array_key_first($sections)] as $user_id) {
												$user 			= get_user_by( 'ID', $user_id );
												$users_info[] 	= $user->display_name . " (".$user->user_email.")";
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
										if( !empty($sections) && isset($group_data['s_users'][array_key_last($sections)]) && !empty($group_data['s_users'][array_key_last($sections)]) ) {
											foreach ($group_data['s_users'][array_key_last($sections)] as $user_id) {
												$user 			= get_user_by( 'ID', $user_id );
												$users_info[] 	= $user->display_name . " (".$user->user_email.")";
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

}
