<?php
/*
Plugin Name: BuddyPress Admin Global Profile Fields
Description: Introduce global BuddyPress profiles fields, allowing only admin users to edit and and allow all users to view them.
Version: 1.0
Author: Garrett Hyder
Author URI: http://nightbook.ca
License: GPL2

Copyright 2015  Garrett Hyder (email : nightbook.g.hyder@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BuddyPress Admin Global Profile Fields
 *
 * @package  BuddyPress Admin Global Profile Fields
 * @since    1.0
 */
class BP_Admin_Global_Profile_Fields {

	/**
	 * Instance of this class.
	 *
	 * @since  1.0
	 */
	private static $instance = null;

	/**
	 * Initialize the plugin.
	 *
	 * @since  1.0
	 */
	private function __construct() {

		// Setup plugin constants
		self::setup_constants();

		// Load plugin text domain
		self::load_plugin_textdomain();

		// Actions
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'xprofile_field_before_contentbox', array( $this, 'bp_xprofile_global_field_value' ) );
		add_action( 'xprofile_fields_saved_field', array( $this, 'bp_xprofile_save_global_field_value' ) );
		add_action( 'bp_profile_field_item', array( $this, 'display_global_profile_fields' ) );

		// Filters
		add_filter( 'bp_xprofile_get_visibility_levels', array( $this, 'global_visibility_level' ) );
		add_filter( 'bp_xprofile_get_hidden_field_types_for_user', array( $this, 'append_global_visibility_level' ), 10, 3 );
		add_filter( 'bp_profile_get_visibility_radio_buttons', array( $this, 'filter_global_visibility_from_radio_buttons' ), 10, 3 );
		add_filter( 'bp_after_has_profile_parse_args', array( $this, 'show_global_fields' ) );

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since  1.0
	 *
	 * @return BP_Admin_Global_Profile_Fields
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;

	}

	/**
	 * Setup plugin constants.
	 *
	 * @since  1.0
	 */
	private function setup_constants() {

		if ( ! defined( 'BPAGPF_VERSION' ) ) {
			define( 'BPAGPF_VERSION', '1.0' );
		}

		if ( ! defined( 'BPAGPF_PLUGIN_URL' ) ) {
			define( 'BPAGPF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'BPAGPF_PLUGIN_DIR' ) ) {
			define( 'BPAGPF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

	}

	/**
	 * Load the plugin text domain.
	 *
	 * @since  1.0
	 */
	private function load_plugin_textdomain() {

		load_plugin_textdomain( 'bp_admin_global_profile_fields', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since  1.0
	 */
	public function enqueue_scripts() {

		$min     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$jssrc   = plugins_url( 'js/script' . $min . '.js', __FILE__ );
		$csssrc  = plugins_url( 'css/style' . $min . '.css', __FILE__ );
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : BPAGPF_VERSION;

		wp_register_script( 'bp_admin_global_profile_fields', $jssrc, array( 'jquery' ), $version, true );
        wp_register_style( 'bp_admin_global_profile_fields', $csssrc, false, $version );

		if ( ! empty( $_GET['page'] ) && false !== strpos( $_GET['page'], 'bp-profile-setup' ) ) {
			wp_enqueue_script( 'bp_admin_global_profile_fields' );
	        wp_enqueue_style( 'bp_admin_global_profile_fields' );
		}

	}

	/**
	 * Output the metabox for setting the field value on admin global fields.
	 *
	 * @since 1.0
	 *
	 * @param object 	$field
	 *
	 * @return void If default field
	 */
	private function value_metabox( $field ) {

		// Default field cannot change type.
		if ( ! empty( $field->id ) && 1 === (int) $field->id ) {
			return;
		} ?>

		<div class="postbox" style="display:none;">
			<h3><label for="fieldvalue"><?php esc_html_e( 'Value', 'bp_admin_global_profile_fields'); ?></label></h3>
			<div class="inside">
				<?php $field->type_obj->admin_field_html( array( 'name' => 'fieldvalue', 'id' => 'fieldvalue', 'value' => bp_xprofile_get_meta( $field->id, 'field', 'global_value' ) ) ); ?>
				<small id="type-change-notice" style="display:none;">Note: The Field Type has changed, save the field and re-edit to render the Value field appropriately.</small>
			</div>
		</div>

	<?php		
	}

	/**
	 * Add the global fields value input to the xprofile edit screen.
	 *
	 * @since  1.0
	 *
	 * @param object 	$field
	 */
	public function bp_xprofile_global_field_value( $field ) {

		// Output the field value metabox.
		$this->value_metabox( $field );

	}

	/**
	 * Save the global field value.
	 *
	 * @since  1.0
	 *
	 * @param object 	$field
	 */
	public function bp_xprofile_save_global_field_value( $field ) {

		if ( ! empty( $_POST['saveField'] ) ) {

			if ( BP_XProfile_Field::admin_validate() ) {

				$field_id = $field->id;
				if ( empty( $field_id ) ) {
					$field_id = BP_XProfile_Field::get_id_from_name( $field->name );
				}

				$this->__update_xprofile_meta( $field_id, 'field', 'global_value', $_POST['fieldvalue'] );
			
			}

		}

	}

	/**
	 * Add our global visibility level.
	 *
	 * @since  1.0
	 *
	 * @param array 	$levels
	 *
	 * @return array
	 */
	public function global_visibility_level( $levels ) {

		$levels['global'] = array(
			'id'    => 'global',
			'label' => __( 'Global', 'bp_admin_global_profile_fields' )
		);

		return $levels;

	}

	/**
	 * Append 'global' to the visibility levels for all users.
	 *
	 * @since  1.0
	 *
	 * @param array 	$hidden_levels
	 * @param int   	$displayed_user_id
	 * @param int   	$current_user_id
	 *
	 * @return array
	 */
	public function append_global_visibility_level( $hidden_levels, $displayed_user_id, $current_user_id ) {

		if ( bp_is_user_profile_edit() ) {

			// Editing profile
			$hidden_levels[] = 'global';

		}

		return $hidden_levels;

	}

	/**
	 * Filter 'global' visibility levels from radio buttons.
	 *
	 * @since  1.0
	 *
	 * @param string 	$retval
	 * @param array   	$request
	 * @param array   	$args
	 *
	 * @return array
	 */
	public function filter_global_visibility_from_radio_buttons( $retval, $r, $args ) {

		// Empty return value, filled in below if a valid field ID is found
		$retval = '';

		// Only do-the-do if there's a valid field ID
		if ( ! empty( $r['field_id'] ) ) :

			// Start the output buffer
			ob_start();

			// Output anything before
			echo $r['before']; ?>

			<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

				<?php foreach( bp_xprofile_get_visibility_levels() as $level ) : ?>

					<?php if ( $level['id'] != 'global' ) : ?>

						<?php printf( $r['before_radio'], esc_attr( $level['id'] ) ); ?>

						<label for="<?php echo esc_attr( 'see-field_' . $r['field_id'] . '_' . $level['id'] ); ?>">
							<input type="radio" id="<?php echo esc_attr( 'see-field_' . $r['field_id'] . '_' . $level['id'] ); ?>" name="<?php echo esc_attr( 'field_' . $r['field_id'] . '_visibility' ); ?>" value="<?php echo esc_attr( $level['id'] ); ?>" <?php checked( $level['id'], bp_get_the_profile_field_visibility_level() ); ?> />
							<span class="field-visibility-text"><?php echo esc_html( $level['label'] ); ?></span>
						</label>

						<?php echo $r['after_radio']; ?>

					<?php endif; ?>

				<?php endforeach; ?>

			<?php endif;

			// Output anything after
			echo $r['after'];

			// Get the output buffer and empty it
			$retval = ob_get_clean();

		endif;

		return $retval;

	}

	/**
	 * Display the global fields on the users public profile.
	 *
	 * @since  1.0
	 *
	 */
	public function display_global_profile_fields() {

		global $profile_template;

		$this->write_log($profile_template);

		if ($profile_template->field->visibility_level == 'global') {

			$this->write_log($profile_template->field->visibility_level);

			$global_field_value = bp_xprofile_get_meta( $profile_template->field->id, 'field', 'global_value' );

			if ( ! empty( $global_field_value ) || ( '0' === $global_field_value ) ) {
				$value = maybe_unserialize( $global_field_value );
			} else {
				$value = false;
			}

			if ( ! empty( $value ) || ( '0' === $value ) ) { ?>

				<tr<?php bp_field_css_class(); ?>>

					<td class="label"><?php bp_the_profile_field_name(); ?></td>

					<td class="data"><?php echo $value; ?></td>

				</tr>

			<?php }

		}
									
	}

	/**
	 * Update has_profile args to not filter the empty fields
	 *
	 * @since  1.0
	 *
	 * @param array 	$r
	 *
	 * @return array
	 */
	public function show_global_fields( $r ) {

		$r['hide_empty_fields'] = false;

		return $r;

	}

	/**
	 * Helper function for handling xProfile Meta
	 *
	 * @since  1.0
	 *
	 * @param int 		$object_id
	 * @param string   	$object_type
	 * @param string   	$meta_key
	 * @param string   	$meta_value
	 *
	 */
    private function __update_xprofile_meta( $object_id, $object_type, $meta_key, $meta_value = '' ) {
        if ( empty( $meta_value ) || ! $meta_value ) {
            bp_xprofile_delete_meta( $object_id, $object_type, $meta_key );
        } elseif ( ! bp_xprofile_get_meta( $object_id, $object_type, $meta_key ) ) {
            bp_xprofile_add_meta( $object_id, $object_type, $meta_key, $meta_value );
        } else {
            bp_xprofile_update_meta( $object_id, $object_type, $meta_key, $meta_value );
        }
    }

    private function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}

$bp_admin_global_profile_fields = BP_Admin_Global_Profile_Fields::get_instance();