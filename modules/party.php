<?php

/**
 * Display the opportunities for the current user.
 */
add_shortcode( 'cap-opportunities', function() {
	// Get the party ID for the current user.
	$party_id = cap_get_partyid();
	if ( ! $party_id ) {
		return '<p>' . _e( 'Invalid Party ID', 'cap' ) . '</p>';
	}

	// The the opportunities for the current user.
	$opportunities = cap_get_opportunities( $party_id );
	if ( ! $opportunities ) {
		return '<p>' . _e( 'Whoops, there appears to have been an error.', 'cap' ) . '</p>';
	}

	if ( 0 == $opportunities->{'@size'} ) {
		return '<p>' . _e( 'You have no opportunities listed.', 'cap' ) . '</p>';
	}

	//@todo: format the output
	return '<pre>' . print_r( $opportunities, true ) . '</pre>';
} );

/**
 * Add a profile field for the party ID.
 */
add_filter( 'cap_user_profile_fields', function( $fields, $user ) {
	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$party_id = get_user_meta( $user->ID, 'cap-partyid', true );

	$fields[] = array(
		'label' => 'Party ID',
		'value' => (int)$party_id,
		'name' => 'cap-partyid',
	);

	return $fields;
}, 10, 2 );

/**
 * Add "Capsule Registration" item to the "Forms" menu.
 */
add_filter( 'gform_addon_navigation', function( $menus ) {
	$menus[] = array(
		'name' => 'gf_capsule_user_registration',
		'label' => __( 'Capsule Registration', 'capsule-crm'),
		'callback' =>  function() {
			require_once( __DIR__ . '/../admin/settings.php' );
		},
		'permission' => 'gravityforms_edit_entries',
	);

	return $menus;
} );

/**
 *
 */
add_action( 'gform_after_submission', function( $entry, $form ) {
	// @todo: only run on registration field

	$data = cap_get_person_form_data( $entry, $form['id'] );

	$custom_fields = cap_get_custom_field_data( $entry, $form['id'] );

	$_data = cap_format_person_data( $data  );


	$response = Capsule_CRM_API::instance()->post( 'api/person', $_data );
	update_option( 'cap_response', $response );

	if ( 'Created' == wp_remote_retrieve_response_message( $response ) ) {
		$headers = wp_remote_retrieve_headers( $response );

		$party_id = end( explode( '/', $headers['location'] ) );

		$user_id = wp_create_user( $data['username'], $data['password'], $data['emailAddress'] );
		update_user_meta( $user_id, 'cap-partyid', $party_id );

		$response = Capsule_CRM_API::instance()->put( $headers['location'] . '/customfields', $custom_fields );
		error_log( var_export( $response, true ) );
	}

}, 10, 2 );

/**
 *
 */
add_filter( 'gform_validation', function( $validation_result ) {
	return $validation_result;

	// @todo after every form is validated?
	// @todo make sure required items are not empty
	if ( ! isset( $validation_result['form']['id'] ) ) {
		return $validation_result;
	}

	// if ( ! $_form = _cap_get_registration_form( $validation_result['form']['id'] ) ) {
	// 	return $validation_result;
	// }

	$query = new WP_Query( array(
		'post_type' => 'capsule_form',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'meta_query' => array(
			array(
				'key' => 'cap_gravity_form',
				'value' => array( $validation_result['form']['id'] ),
				'compare' => 'IN',
			),
		),
	) );

	if ( ! $query->have_posts() ) {
		return $validation_result;
	}

	$_form = get_post_meta( $query->post->ID, 'cap_form_data' );
	$_form = $_form[0];


	$_get_field_value = function( $field, $form ) {

		// Get the Gravity Forms' field id. For example 3, 6.2, 9.1
		if ( ! $field_id = $form[ $field ] ) {
			return false;
		}

		// Dots and spaces in variable names are converted to underscores.
		// For example <input name="a.b" /> becomes $_REQUEST["a_b"].
		// http://www.php.net/manual/en/language.variables.external.php
		$field_name = 'input_' . str_replace( '.', '_',  $field_id );
		if ( ! $value = $_POST[ $field_name ] ) {
			return false;
		}

		return $value;
	};

	$username = $_get_field_value( 'user_settings_username', $_form );
	$email = $_get_field_value( 'email_emailaddress', $_form );

	$form = $validation_result['form'];

	if ( username_exists( $username ) ) {
		$validation_result['is_valid'] = false;
		foreach ( $form['fields'] as &$field ) {
			if ( $field['id'] == $_form['user_settings_username'] ) {
				$field['failed_validation'] = true;
				$field['validation_message'] = __( 'This username is already registered', 'capsule-crm' );
			}
		}
	}

	if ( ! validate_username( $username ) ) {
		$validation_result['is_valid'] = false;
		foreach ( $form['fields'] as &$field ) {
			if ( $field['id'] == $_form['user_settings_username'] ) {
				$field['failed_validation'] = true;
				$field['validation_message'] = __( 'The username can only contain alphanumeric characters (A-Z, 0-9), underscores, dashes and spaces', 'capsule-crm' );
			}
		}
	}

	if ( email_exists( $email ) ) {
		$validation_result['is_valid'] = false;
		foreach ( $form['fields'] as &$field ) {
			if ( $field['id'] == $_form['email_emailaddress'] ) {
				$field['failed_validation'] = true;
				$field['validation_message'] = __( 'This email address is already registered', 'capsule-crm' );
			}
		}
	}

	$validation_result['form'] = $form;
	return $validation_result;
} );