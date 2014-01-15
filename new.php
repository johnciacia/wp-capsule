<?php

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


	$form_meta = RGFormsModel::get_form_meta_by_id( (int)$_POST['form_id'] );
	$inputs = cap_get_form_inputs( $form_meta[0]['fields'] );

	wp_send_json( array(
		'success' => true,
		'fields' => $inputs,
	) );
} );

/**
 *
 */
add_action( 'init', function() {
	register_post_type( 'capsule_form', array(
		'label' => 'Capsule CRM',
		'description' => 'Capsule CRM Forms',
		'public' => true,
		'exclude_from_search' => true,
		'publicly_queryable' => false,
		'show_ui' => true,
		'register_meta_box_cb' => 'capsule_mb',
		'show_in_nav_menus' => true,
		'show_in_admin_bar' => false,
		'hierarchical' => false,
		'supports' => false,
	) );
} );

function capsule_mb() {
	add_meta_box( 'formdiv', 'Capsule User Registration', function() {
		require_once( __DIR__ . '/admin/registration.php' );
	}, 'capsule_form', 'normal', 'high' );

	add_meta_box( 'customdiv', 'Custom Fields', function() {
		require_once( __DIR__ . '/admin/custom-fields.php' );
	}, 'capsule_form' );
}

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
	update_post_meta( get_the_ID(), 'cap_form_data', $_POST );
}, 10, 2 );


function cap_get_form_values( $post_id = null ) {
	if ( is_null( $post_id ) ) {
		$post_id = get_the_ID();
	}

	$forms = get_post_meta( $post_id, 'cap_form_data' );
	$forms = maybe_unserialize( $forms[0] );

	if ( ! is_array( $forms ) ) {
		return array();
	}

	return $forms;
}

function _cap_get_rgform_fields( $post_id = null ) {
	if ( is_null( $post_id ) ) {
		$post_id = get_the_ID();
	}

	$forms = cap_get_form_values( $post_id );

	if ( ! isset( $forms['gravity_form'] ) ) {
		return array();
	}

	$form_info = RGFormsModel::get_form_meta_by_id( $forms['gravity_form'] );


	$inputs = cap_get_form_inputs( $form_info[0]['fields'] );

	// @todo: there should be a better way to do this...
	$outputs = array( '' );
	foreach ( $inputs as $key => $value ) {
		$outputs[ (string)$key ] = $value;
	}

	return $outputs;
}

function _cap_get_registraion_form( $form_id ) {
	$query = new WP_Query( array(
		'post_type' => 'capsule_form',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'meta_query' => array(
			array(
				'key' => 'cap_gravity_form',
				'value' => array( $form_id ),
				'compare' => 'IN',
			),
		),
	) );

	if ( ! $query->have_posts() ) {
		return false;
	}

	$_form = get_post_meta( $query->post->ID, 'cap_form_data' );
	return $_form[0];
}


function cap_get_form_inputs( $inputs ) {
	$ret = array();
	foreach ( $inputs as $input ) {
		if ( isset( $input['inputs'] ) ) {
			foreach ( $input['inputs'] as $_input ) {
				$ret[ $_input['id'] . '' ] = $input['label'] . ' (' . $_input['label'] . ')';
			}
		} else {
			$ret[ $input['id'] ] = $input['label'];
		}
	}

	return $ret;
}

function cap_get_custom_fields() {
	$response = Capsule_CRM_API::instance()->get( 'api/party/customfield/definitions' );

	$json = json_decode( $response['body'] );
	if ( isset( $json->customFieldDefinitions->customFieldDefinition ) ) {
		array_unshift( $json->customFieldDefinitions->customFieldDefinition, '' );
		return $json->customFieldDefinitions->customFieldDefinition;
	}

	return array();
}