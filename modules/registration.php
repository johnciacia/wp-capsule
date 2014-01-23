<?php

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

add_action( 'cap_party_submission', function( $entry, $form ) {
	$party_id = cap_get_partyid();
	$party = Capsule_CRM_API::get_party( $party_id );

	$template = Capsule_CRM_Data::party();
	$data = Capsule_CRM_Data::get_form_data( $template, $entry, $form );

	foreach ( $data as $key => $value ) {
		if ( $key == 'contacts' ) {
			continue;
		}

		if ( isset( $party->$key ) && ! empty( $value ) ) {
			$party->$key = $value;
		}
	}

	foreach ( $data['contacts'] as $group_name => $group ) {
		foreach ( $group as $field_name => $field ) {
			if ( isset( $party->contacts->$group_name->$field_name ) && ! empty( $field ) ) {
				$party->contacts->$group_name->$field_name = $field;
			}
		}
	}

	$response = Capsule_CRM_API::update_party( $party_id, array( 'person' => $party )  );
	update_option( 'cap_response', $response );
}, 10, 2 );

/**
 *
 */
add_action( 'cap_registration_submission', function( $entry, $form ) {
	$template = Capsule_CRM_Data::party();
	$data = Capsule_CRM_Data::get_form_data( $template, $entry, $form );
	// @todo: this should be built into the form
	$data['contacts']['website']['webService'] = 'URL';
	$response = Capsule_CRM_API::add_party( array( 'person' => $data ) );
	update_option( 'cap_response', $response );

	// $custom_fields = cap_get_custom_field_data( $entry, $form['id'] );

	// if ( 'Created' == wp_remote_retrieve_response_message( $response ) ) {
	// 	$headers = wp_remote_retrieve_headers( $response );

	// 	$party_id = end( explode( '/', $headers['location'] ) );

	// 	$user_id = wp_create_user( $data['username'], $data['password'], $data['emailAddress'] );
	// 	update_user_meta( $user_id, 'cap-partyid', $party_id );

	// 	$response = Capsule_CRM_API::instance()->put( $headers['location'] . '/customfields', $custom_fields );
	// }

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

add_shortcode( 'cap-profile', function( $atts ) {
	$atts = shortcode_atts( array(
		'form' => null,
	), $atts );

	if ( is_null( $atts['form'] ) ) {
		return '<p>' . _( 'Oops! We could not locate your form.', 'cap' ) . '</p>';
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return '<p>' . _e( 'You must be logged in to view your opportunities.', 'cap' ) . '</p>';
	}

	// Get the party ID for the current user.
	$party_id = cap_get_partyid();
	if ( ! $party_id ) {
		return '<p>' . _e( 'Invalid Party ID', 'cap' ) . '</p>';
	}

	$party = Capsule_CRM_API::get_party( $party_id );
	$custom_fields = Capsule_CRM_API::get_custom_fields_for( $party_id );
	$meta = Capsule_CRM_Form::get_meta( $atts['form'] );

	// @todo: refactor this...
	add_filter( 'gform_pre_render', function( $form ) use ( $meta, $party, $custom_fields, $form_id ) {
		$filters = array();
		foreach ( $form['fields'] as &$field ) {
			$field['allowsPrepopulate'] = true;
			if ( is_null( $field['inputs'] ) ) {
				$field['inputName'] = cap_get_field_name( $field['id'] );
				$filters[] = array(
					'id' => $field['id'],
					'name' => $field['inputName'],
				);
			} else {
				foreach( $field['inputs'] as &$input ) {
					$input['name'] = cap_get_field_name( $input['id'] );
					$filters[] = array(
						'id' => $input['id'],
						'name' => $input['name'],
					);
				}
			}

			foreach ( $filters as $filter ) {
				$id = $filter['id'];
				add_filter( 'gform_field_value_' . $filter['name'], function( $value ) use ( $id, $meta, $party, $custom_fields, $form_id ) {
					return cap_get_field_value( $id, $meta, $party, $custom_fields, $form_id );
				} );
			}
		}

		return $form;
	} );


	echo do_shortcode( '[gravityform id="' . $atts['form'] . '" name="Profile" title="false" description="false"]' );
} );

function cap_get_field_id( $name ) {
	return str_replace( '-', '.', substr( $name, 4 ) );
}

function cap_get_field_name( $id ) {
	return 'cap-' . str_replace( '.', '-', $id );
}

function cap_get_field_value( $id, $meta, $party, $custom_fields, $form_id ) {
	foreach ( $meta as $group_name => $group ) {
		if ( $group_name == 'custom' ) {
			if ( isset( $group['a'] ) ) {
				for ( $i = 0; $i < count( $group['a'] ); $i++ ) {
					if ( $group['b'][ $i ] == $id ) {
						foreach ( $custom_fields as $custom_field ) {
							// @todo: this should use tag and label combination
							if ( $custom_field->label == $group['a'][ $i ] ) {
								return $custom_field->text;
							}
						}
					}
				}
			}
		}

		foreach ( $group as $field_name => $field_id ) {
			if ( $field_id == $id ) {
				if ( 'default' == $group_name ) {
					return $party->$field_name;
				} else {
					return $party->contacts->$group_name->$field_name;
				}
			}
		}
	}

	return '';

}