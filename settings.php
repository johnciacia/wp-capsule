<?php

/**
 * Add Capsule CRM subview to the Gravity Forms settings page.
 */
add_action( 'admin_init', function() {
	if( RGForms::get( 'page' ) == 'gf_settings' ) {
		RGForms::add_settings_page( 'Capsule CRM', function() {
			if ( $url = RGForms::post( 'gf_capsulecrm_url' ) ) {
				update_option( 'gf_capsulecrm_url', $url );
			}

			if ( $key = RGForms::post( 'gf_capsulecrm_key' ) ) {
				update_option( 'gf_capsulecrm_key', $key );
			}

			?>
			<form method="post">
				<h3>Capsule Authentication Settings</h3>
				<p>API requests are made via HTTPS using your accounts unique 
				Capsule URL just like you would using your web browser 
				(e.g. https://sample.capsulecrm.com). Each request must be 
				authenticated with a users API authentication token using HTTP 
				Basic Authentication. You can find your API token by selecting 
				<em>My Preferences</em> from your user menu in the Capsule navigation 
				bar then using the <em>API Authentication Token</em> link.</p>
				<table class="form-table">					
					<tr>
						<th scope="row">URL</th>
						<td>
							<input type="text" name="gf_capsulecrm_url" size="50" value="<?php echo esc_url( cap_get_url() ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">Authentication Token</th>
						<td>
							<input type="text" name="gf_capsulecrm_key" size="50" value="<?php echo esc_attr( cap_get_key() ); ?>" />
						</td>
					</tr>
				</table>
				<input type="submit" value="Save Settings" class="button-primary" />
			</form>
			<?php if ( $response = get_option( 'cap_response', false ) ) : ?>
				<hr /><br />
				<?php $response['body'] = esc_html( $response['body'] ); ?>
				<h3>Response: <?php echo esc_html( $response['response']['message'] ); ?></h3>
				<div style="overflow:scroll; height: 200px;"><?php echo '<pre>' . print_r( $response, true ) . '</pre>'; ?></div>
			<?php endif; ?>

			<?php
		}, null );
	}
} );