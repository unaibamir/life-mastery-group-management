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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/life-mastery-group-management-admin.js', array( 'jquery' ), $this->version, false );

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

		$tags_query = "SELECT TagId, GroupName FROM {$wpdb->_isContactGroup} WHERE GroupName=TRIM(GroupName) ORDER BY GroupName ASC";
		$tags = $wpdb->get_results( $tags_query );
		$group_tag = get_post_meta( $post->ID, 'lm_group_tag', true );
		
		?>
		<table class="form-table">
			<tr>
				<th><label for="lm_group_tag"><?php echo __('Select Group Tag'); ?></label></th></th>
				<td>
					<select name="lm_group_tag" id="lm_group_tag" class="lm_group_tag mbr-wc-integration-panel-select2">
						<?php foreach ($tags as $tag) {
							echo '<option value="'. $tag->TagId .'" '. selected( $group_tag, $tag->TagId ) .' >'. $tag->GroupName .'</option>';
						} ?>
					</select>
				</td>
			</tr>
		</table>
		<?php
		
	}

	public function ld_group_save_post( $post_id, $post, $update ) {

		// Only set for post_type = post!
	    if ( 'groups' !== $post->post_type ) {
	        return;
	    }

	    if ( wp_is_post_revision( $post_id ) ) {
        	return;
        }

        update_post_meta( $post_id, 'lm_group_tag', $_POST['lm_group_tag'] );

	}

}
