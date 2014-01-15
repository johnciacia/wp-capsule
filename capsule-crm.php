<?php
/*
Plugin Name: Capsule CRM
Plugin URI: http://www.onnolia.com
Description:
Version: 1.0
Author: johnciacia
Author URI: http://www.onnolia.com

------------------------------------------------------------------------
Copyright 2013 johnciacia

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

require_once( __DIR__ . '/api.php' );
require_once( __DIR__ . '/data.php' );
require_once( __DIR__ . '/template-tags.php' );
require_once( __DIR__ . '/modules/party.php' );
require_once( __DIR__ . '/new.php' );

require_once( __DIR__ . '/settings.php' );

/**
 * Initialize the API
 */
Capsule_CRM_API::instance()->set_key( cap_get_key() )->set_url( cap_get_url() );

/**
 * Load the styles for the admin page.
 */
add_action( 'admin_enqueue_scripts', function() {
	wp_enqueue_style( 'capsule-admin', plugins_url( 'capsule-crm/admin/style.css' ), __FILE__ );
} );

/**
 * Get the Capsule CRM authentication URL
 */
function cap_get_url() {
	return get_option( 'gf_capsulecrm_url', '' );
}

/**
 * Get the Capsule CRM authentication key
 */
function cap_get_key() {
	return get_option( 'gf_capsulecrm_key', '' );
}

/**
 * Easily allow fields to be added to the user profile page.
 */
add_action( 'show_user_profile', 'cap_user_profile_fields' );
add_action( 'edit_user_profile', 'cap_user_profile_fields' );
function cap_user_profile_fields( $user ) {
	$fields = apply_filters( 'cap_user_profile_fields', array(), $user );
	if ( empty( $fields ) ) {
		return;
	}
	?>
	<h3>Capsule CRM</h3>
	<table class="form-table">
		<?php foreach ( $fields as $field ) : ?>
		<tr>
			<th><?php echo esc_html( $field['label'] ); ?></th>
			<td><input type="text" class="regular-text" name="cap-partyid" value="<?php echo esc_attr( $field['value'] ); ?>" /></td>
		</tr>
		<?php endforeach; ?>
	</table>
	<?php
	wp_nonce_field( 'cap-partyid-nonce', 'cap-nonce' );
}

/**
 * Easily allow profile fields to be validated and added
 * to the database.
 */
add_action( 'personal_options_update', 'cap_update_user_profile_fields' );
add_action( 'edit_user_profile_update', 'cap_update_user_profile_fields' );
function cap_update_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	if ( ! isset( $_POST['cap-nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['cap-nonce'], 'cap-partyid-nonce' ) ) {
		return;
	}

	$fields = apply_filters( 'cap_user_profile_fields', array(), $user );
	foreach ( $fields as $field ) {
		if ( isset( $_POST[ $field['name'] ] ) ) {
			update_user_meta( $user_id, sanitize_key( $field['name'] ), sanitize_text_field( $_POST[ $field['name'] ] ) );
		}
	}

	do_action( 'cap_update_user_profile_fields', $user_id );
}