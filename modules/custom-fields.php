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


add_action( 'admin_footer', function() {
	?>
	<script type="text/javascript">
	jQuery(document).ready(function($) {
		if ( pagenow == 'edit-capsule_form' ) {
			$('.wrap h2').append('<a href="post-new.php?post_type=capsule_form&view=registration" class="add-new-h2">Registration Form</a>')
			$('.wrap h2').append('<a href="post-new.php?post_type=capsule_form&view=party" class="add-new-h2">Party Form</a>')
		}
	});
	</script>
	<?php
} );


add_shortcode( 'cap-login', function() {
	wp_login_form( array(
		'redirect' => site_url( '/my-bluedynasty/' ),
	) );
} );




add_action( 'cap_party_submission', function( $entry, $form ) {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}

	$party_id = get_user_meta( $user_id, 'cap-partyid', true );
	if ( ! $party_id ) {
		return;
	}

	$custom_fields = cap_get_custom_field_data( $entry, $form['id'] );

	$url = cap_get_url() . '/api/party/' . $party_id . '/customfields';

	$response = Capsule_CRM_API::instance()->put( $url, $custom_fields );

}, 10, 2 );


