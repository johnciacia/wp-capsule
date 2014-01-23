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
require_once( __DIR__ . '/modules/registration.php' );
require_once( __DIR__ . '/modules/opportunity.php' );
require_once( __DIR__ . '/modules/party.php' );
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
 * Get the Gravity Forms' meta
 */
add_action( 'wp_ajax_cap_get_gravity_form', function() {
	if ( ! isset( $_POST['form_id'], $_POST['_wpnonce'] ) ) {
		wp_sent_json( array( 'success' => false ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_sent_json( array( 'success' => false ) );
	}

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'cap_get_gravity_form' ) ) {
		wp_sent_json( array( 'success' => false ) );
	}

	$inputs = Capsule_CRM_GF::get_fields( (int)$_POST['form_id'] );

	wp_send_json( array(
		'success' => true,
		'fields' => $inputs,
	) );
} );

/**
 * Create a post type to store our form.
 */
add_action( 'init', function() {
	register_taxonomy_for_object_type( 'post_tag', 'page' );

	register_post_type( 'capsule_form', array(
		'label' => 'Capsule CRM',
		'labels' => array(
			'add_new' => 'New Party',
		),
		'description' => 'Capsule CRM Forms',
		'public' => true,
		'exclude_from_search' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'register_meta_box_cb' => 'capsule_metabox_cb',
		'show_in_nav_menus' => true,
		'show_in_admin_bar' => false,
		'hierarchical' => false,
		'supports' => array( 'title' ),
		'taxonomies' => array( 'post_tag' ),
		'rewrite' => array( 'slug' => 'form' ),
	) );
} );

/**
 *
 */
add_action( 'save_post', function( $post_id, $post ) {
	if ( 'capsule_form' !== $post->post_type ) {
		return;
	}

	if ( ! isset( $_POST['cap-user-registration'], $_POST['gravity_form'] ) ) {
		return;
	}

	if ( ! GFCommon::current_user_can_any( 'gravityforms_create_form' ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['cap-user-registration'], 'cap-user-registration' ) ) {
		return;
	}

	update_post_meta( get_the_ID(), 'cap_gravity_form', (int)$_POST['gravity_form'] );

	if ( isset( $_POST['cap'] ) ) {
		update_post_meta( get_the_ID(), 'cap_form_data', $_POST['cap'] );
	}

	if ( isset( $_POST['cap_form_type'] ) ) {
		update_post_meta( get_the_ID(), 'cap_form_type', $_POST['cap_form_type'] );
	}

}, 10, 2 );

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

/**
 * When a Gravity Form is submitted perform the correct actions
 * based on the form type.
 */
add_action( 'gform_after_submission', function( $entry, $form ) {

	$type = Capsule_CRM_Form::get_type( $form['id'] );
	$form = Capsule_CRM_Form::get_meta( $form['id']  );

	error_log( var_export( $type, 1) );

	switch ( $type ) {
		case 'party':
			do_action( 'cap_party_submission', $entry, $form );
			break;
		case 'registration':
			do_action( 'cap_registration_submission', $entry, $form );
			break;
		case 'opportunity':
			do_action( 'cap_opportunity_submission', $entry, $form );
			break;
	}
}, 10, 2 );

function capsule_metabox_cb() {

	add_meta_box( 'formdiv', 'Capsule User Registration', function( $post ) {
		$type = Capsule_CRM_Form::get_the_type( $post->ID );

		switch ( $type ) {
			case 'party':
				$groups = Capsule_CRM_Data::party();
				unset( $groups['user']['fields']['username'] );
				unset( $groups['user']['fields']['password'] );
				break;
			case 'opportunity':
				$groups = Capsule_CRM_Data::opportunity();
				break;
			default:
				$groups = Capsule_CRM_Data::party();
				break;
		}

		$data = get_post_meta( get_the_ID(), 'cap_form_data' );
		$data = $data[0];
		$data['gravity_form'] = get_post_meta( $post->ID, 'cap_gravity_form', true );

		$gf_form_fields = Capsule_CRM_GF::get_fields( $data['gravity_form'] );

		require_once( __DIR__ . '/admin/default-fields.php' );
	}, 'capsule_form', 'normal', 'high' );

	add_meta_box( 'customdiv', 'Custom Fields', function( $post ) {
		$type = Capsule_CRM_Form::get_the_type( $post->ID );
		$fields = Capsule_CRM_API::get_custom_fields( $type );

		$data = get_post_meta( get_the_ID(), 'cap_form_data' );
		$data = $data[0];
		$data['gravity_form'] = get_post_meta( $post->ID, 'cap_gravity_form', true );
		$gf_form_fields = Capsule_CRM_GF::get_fields( $data['gravity_form'] );
		require_once( __DIR__ . '/admin/custom-fields.php' );
	}, 'capsule_form' );
}

/**
 *
 */
add_filter( 'manage_edit-capsule_form_columns', function( $columns ) {
	return array(
		'cb' => '<input id="cb-select-all-1" type="checkbox">',
		'form' => __( 'Form', 'cap' ),
		'type' => __( 'Type', 'cap' ),
		'tags' => __( 'Tags', 'cap') );
} );

/**
 * Display the gravity form name associated with each post.
 */
add_action( 'manage_capsule_form_posts_custom_column', function( $column, $post_id ) {
	switch ( $column ) {
		case 'form':
			$gf_id = get_post_meta( $post_id, 'cap_gravity_form', true );
			$form_info = RGFormsModel::get_form_meta_by_id( $gf_id );
			if ( isset( $form_info[0]['title'] ) ) {
				printf( '<a href="%s">%s</a>', get_edit_post_link( $post_id ), esc_html( $form_info[0]['title'] ) );
			}
			break;
	}
}, 10, 2 );

add_action( 'admin_footer', function() {
	?>
	<script type="text/javascript">
	jQuery( document ).ready( function( $ ) {
		if ( pagenow == 'edit-capsule_form' ) {
			$( '.wrap h2' ).append( '<a href="post-new.php?post_type=capsule_form&view=opportunity" class="add-new-h2">Opportunity</a>' );
		}
	});
	</script>
	<?php
} );

/**
 * For "capsule_form" posts, display the gravity form
 * associated with it.
 */
add_filter( 'the_content', function( $content ) {
	if ( 'capsule_form' != get_post_type() ) {
		return $content;
	}

	$form_id = get_post_meta( get_the_ID(), 'cap_gravity_form', true );
	if ( ! $form_id ) {
		return;
	}

	return do_shortcode( '[gravityform id="' . (int)$form_id . '" title="false" description="false"]' );
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