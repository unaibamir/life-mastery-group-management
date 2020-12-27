<?php

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/life-mastery-group-management-admin.js', array( 'jquery' ), time(), false );

	}


	public function add_meta_boxes() {
		add_meta_box(
            'lm-group-tag-bos',
            __( 'InfusionSoft Memebr Tag', 'textdomain' ),
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
					<select name="lm_group_tag" id="lm_group_tag" class="lm_group_tag lm-select2">
						<?php foreach ($tags as $tag) {
							echo '<option value="'. $tag->TagId .'" '. selected( $group_tag, $tag->TagId ) .' >'. $tag->GroupName .'</option>';
						} ?>
					</select>
				</td>
			</tr>

			<tr>
				<th><label for="lm_group_tag"><?php echo __('Drop Course Lessons'); ?></label></th></th>
				<td>
					<input type="checkbox" name="lm_drip_lessons" value="yes">
					<p class="description">Automatically generate group course dates and drip the course lessons based on the generated dates.</p>
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

        $tag_id 		= $_POST['lm_group_tag'];
        $tag_query 		= "SELECT GroupName FROM _isContactGroup WHERE TagId={$tag_id}";
        $tag_name 		= $wpdb->get_col( $tag_query );
        $tag_name 		= $tag_name[0];
        
        $tag_info 		= explode(' - ', $tag_name);
        $start_date 	= $tag_info[1];

        update_post_meta( $group_id, 'lm_course_start_date', $start_date );

        $weeks 			= LM_Helper::get_group_course_weeks( $group_id );

        $lesson_dates 	= LM_Helper::generate_lesson_dates( $group_id, $weeks, $start_date );
        
        $discuss_dates 	= LM_Helper::generate_lesson_discuss_dates( $group_id, $weeks, $start_date );
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
        		continue;
        	} 
        	$old_date = explode('-', $discuss_date);
        	$new_date = $old_date[1] . '/' . $old_date[2] . '/' . $old_date[0];
        	$discuss_dates[ $key ] = $new_date;
        }
        
        $data = array(
        	'lesson_dates'			=>	$lesson_dates,
        	'lesson_review_dates'	=>	$discuss_dates,
        	'lm_lessons'			=>	$course_lesson_weeks,
        	'users'					=>	array(),
        	's_users'				=>	array()
        );


        update_post_meta( $group_id, 'lm_group_data', $data, '' );

        update_post_meta( $group_id, 'lm_group_attendance_dates', $lesson_dates, '' );

	}

}
