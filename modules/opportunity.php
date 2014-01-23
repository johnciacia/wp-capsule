<?php

/**
 * Display the opportunities for the current user.
 */
add_shortcode( 'cap-opportunities', function() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return '<p>' . _e( 'You must be logged in to view your opportunities.', 'cap' ) . '</p>';
	}

	// Get the party ID for the current user.
	$party_id = cap_get_partyid();
	if ( ! $party_id ) {
		return '<p>' . _e( 'Oops! We could not find your party.', 'cap' ) . '</p>';
	}

	// The the opportunities for the current user.
	$opportunities = Capsule_CRM_API::get_opportunities( $party_id );
	if ( false === $opportunities ) {
		return '<p>' . _e( 'Oops! There appears to have been an error.', 'cap' ) . '</p>';
	}

	if ( empty( $opportunities ) ) {
		return '<p>' . _e( 'You have no opportunities listed.', 'cap' ) . '</p>';
	}

	?>
	<table>
		<tr>
			<th>Opportunity</th>
			<th>Milestone</th>
			<th>Value</th>
			<th>Owner</th>
			<th>Close Date</th>
		</tr>


	<?php foreach ( $opportunities as $opportunity ) : ?>
		<tr>
			<td>
				<p><?php echo esc_html( $opportunity->name ); ?></p>
				<p><?php echo esc_html( $opportunity->description ); ?></p>
				<?php do_action( 'cap_opportunity_meta', $opportunity ); ?>
			</td>

			<td>
				<?php echo esc_html( $opportunity->milestone ); ?>
				&nbsp;(<?php echo esc_html( $opportunity->probability ); ?>%)
			</td>

			<td>
				<?php echo esc_html( $opportunity->value ); ?>
				&nbsp;<?php echo esc_html( $opportunity->currency ); ?>
			</td>

			<td>
				<?php echo esc_html( $opportunity->owner ); ?>
			</td>

			<td>
				<?php echo esc_html( $opportunity->expectedCloseDate ); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>

	<?php
} );



/**
 * Display additional forms with the same tags as the oportunity.
 */
add_action( 'cap_opportunity_meta', function( $opportunity ) {
	$tags = Capsule_CRM_API::get_opportunity_tags( $opportunity->id );
	$forms = cap_get_additional_forms( $tags );
	if ( $forms->have_posts() ) {
		echo '<strong>Additional Forms</strong>';
		while ( $forms->have_posts() ) { $forms->the_post();
			echo '<p><a href="' . get_permalink() . '">' . get_the_title() . '</a></p>';
		}
		wp_reset_query();
	}
} );

/**
 * Display posts and pages with the same tags as the opportunity.
 */
add_action( 'cap_opportunity_meta', function( $opportunity ) {
	$tags = Capsule_CRM_API::get_opportunity_tags( $opportunity->id );
	$posts = cap_get_additional_posts( $tags );
	if ( $posts->have_posts() ) {
		echo '<strong>Additional Information</strong>';
		while ( $posts->have_posts() ) { $posts->the_post();
			echo '<p><a href="' . get_permalink() . '">' . get_the_title() . '</a></p>';
		}
		wp_reset_query();
	}
} );