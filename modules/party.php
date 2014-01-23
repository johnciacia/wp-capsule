<?php

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