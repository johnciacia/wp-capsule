<?php
function cap_get_custom_field_data( $entry, $form ) {
	$data = array();
	$form = _cap_get_registraion_form( $form );
	$custom_fields = cap_get_custom_fields();
	$cf = array();

	for ( $i = 0; $i < count( $form['cap-custom-field']['a'] ); $i++ ) {
		$cf_id = $form['cap-custom-field']['a'][ $i ];

		$gf_id = $form['cap-custom-field']['b'][ $i ];
		$text = $entry[ $gf_id ];

		foreach ( $custom_fields as $_f ) {
			if ( $_f->id == $cf_id ) {
				$cf[] = array(
					'tag' => isset( $_f->tag ) ? $_f->tag : '',
					'label' => $_f->label,
					'text' => $text,
				);
			}
		}
	}

	return array(
		'customFields' => array(
			'customField' => $cf,
		),
	);
}

function cap_get_person_form_data( $entry, $form ) {
	$data = array();
	$form = _cap_get_registraion_form( $form );

	foreach ( cap_get_form_fields() as $group ) {
		foreach ( $group['fields'] as $key => $field ) {
			$name = strtolower( str_replace( ' ', '_', $group['label'] ) . '_' . $key );

			$data[ $key ] = $entry[ $form[ $name ] ];
			if ( is_null( $data[ $key ] ) ) {
				$data[ $key ] = '';
			}
		}
	}

	return $data;
}

function cap_format_custom_fields( $data ) {
	$_data = array();

	foreach ( $data as $datum ) {
		$_data[] = array(
			'tag' => isset( $datum['tag'] ) ? $datum['tag'] : '',
			'label' => $datum['label'],
			'text' => $datum['text'],
		);
	}

	return array(
		'customFields' => array(
			'customField' => $_data,
		),
	);
}

function cap_format_person_data( $data ) {
	return array(
		'person' => array(
			'contacts' => array(
				'address' => array(
					'type' => '',
					'street' => $data['street'],
					'city' => $data['city'],
					'state' => $data['state'],
					'zip' => $data['zip'],
					'country' => $data['country'],
				),
				'email' => array(
					'type' => '',
					'emailAddress' => $data['emailAddress'],
				),
				'phone' => array(
					'type' => '',
					'phoneNumber' => $data['phoneNumber'],
				),
				'website' => array(
					'type' => '',
					'webService' => 'URL',
					'webAddress' => $data['webAddress'],
				),
			),
			'title' => $data['title'],
			'firstName' => $data['firstName'],
			'lastName' => $data['lastName'],
			'jobTitle' => $data['jobTitle'],
			'organisationName' => $data['organisationName'],
			'about' => $data['about'],
		),
	);
}

function cap_get_form_fields() {
	return array(
		'user' => array(
			'label' => 'User Settings',
			'fields' =>  array(
				'username' => array(
					'required' => true,
					'label' => 'Username',
				),
				'password' => array(
					'required' => true,
					'label' => 'Password',
				),
				'title' => array(
					'required' => false,
					'label' => 'Title',
				),
				'firstName' => array(
					'required' => true,
					'label' => 'First Name',
				),
				'lastName' => array(
					'required' => true,
					'label' => 'Last Name',
				),
				'jobTitle' => array(
					'required' => false,
					'label' => 'Job Title',
				),
				'about' => array(
					'required' => false,
					'label' => 'About',
				),
				'organisationName' => array(
					'required' => false,
					'label' => 'Organisation Name',
				),
			),
		),
		'address' => array(
			'label' => 'Address',
			'fields' => array(
				'type' => array(
					'required' => false,
					'label' => 'Type',
				),
				'street' => array(
					'required' => true,
					'label' => 'Street',
				),
				'city' => array(
					'required' => true,
					'label' => 'City',
				),
				'state' => array(
					'required' => true,
					'label' => 'State',
				),
				'zip' => array(
					'required' => true,
					'label' => 'Zip',
				),
				'country' => array(
					'required' => true,
					'label' => 'Country',
				),
			),
		),
		'email' => array(
			'label' => 'Email',
			'fields' => array(
				'type' => array(
					'required' => false,
					'label' => 'Type',
				),
				'emailAddress' => array(
					'required' => true,
					'label' => 'Email Address',
				)
			)
		),
		'phone' => array(
			'label' => 'Phone',
			'fields' => array(
				'type' => array(
					'required' => false,
					'label' => 'Type',
				),
				'phoneNumber' => array(
					'required' => true,
					'label' => 'Phone Number',
				),
			),
		),
		'website' => array(
			'label' => 'Website',
			'fields' => array(
				'type' => array(
					'required' => false,
					'label' => 'Type',
				),
				'webService' => array(
					'required' => true,
					'label' => 'Web Service',
				),
				'webAddress' => array(
					'required' => true,
					'label' => 'Web Address',
				),
			),
		),
	);
}

function cap_get_opportunity_fields() {
	return array(
		'opportunity' => array(
			'label' => 'Opportunity Settings',
			'fields' =>  array(
				'name' => array(
					'required' => true,
					'label' => 'Name',
				),
				'description' => array(
					'required' => false,
					'label' => 'Description',
				),
				'currency' => array(
					'required' => false,
					'label' => 'Currency',
				),
				'value' => array(
					'required' => false,
					'label' => 'Value',
				),
				'durationBasis' => array(
					'required' => false,
					'label' => 'Duration Basis',
				),
				'duration' => array(
					'required' => false,
					'label' => 'Duration',
				),
				'milestoneId' => array(
					'required' => true,
					'label' => 'Milestone',
				),
				'expectedCloseDate' => array(
					'required' => false,
					'label' => 'Expected Close Date',
				),
				'actualCloseDate' => array(
					'required' => false,
					'label' => 'Actual Close Date',
				),
				'owner' => array(
					'required' => false,
					'label' => 'Owner',
				),
			),
		),
	);
}