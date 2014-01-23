<?php

/**
 * Get the opportunities for a party.
 *
 * @param $party_id
 * @return array
 */
function cap_get_opportunities( $party_id ) {
	$response = Capsule_CRM_API::instance()->get( 'api/party/' . (int)$party_id . '/opportunity' );
	$json = json_decode( $response['body'] );
	if ( isset( $json->opportunities ) ) {
		return $json->opportunities;
	}

	return false;
}

/**
 * Get the capsule party id for a user.
 *
 * @param $user_id
 * @return int|false
 */
function cap_get_partyid( $user_id = null ) {
	if ( is_null( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	return get_user_meta( $user_id, 'cap-partyid', true );
}

function cap_get_additional_forms( $tags ) {
	$terms = array();
	foreach ( $tags as $tag ) {
		$term = get_term_by( 'name', $tag->name, 'post_tag' );
		$terms[] = $term->slug;
	}

	return new WP_Query( array(
		'post_type' => 'capsule_form',
		'post_status' => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'post_tag',
				'field' => 'slug',
				'terms' => $terms,
			),
		),
	) );
}

function cap_get_additional_posts( $tags ) {
	$terms = array();
	foreach ( $tags as $tag ) {
		$term = get_term_by( 'name', $tag->name, 'post_tag' );
		$terms[] = $term->slug;
	}

	return new WP_Query( array(
		'post_type' => array( 'post', 'page' ),
		'post_status' => 'publish',
		'tax_query' => array(
			array(
				'taxonomy' => 'post_tag',
				'field' => 'slug',
				'terms' => $terms,
			),
		),
	) );
}