<div class="wrap capsule">
	<div>
		<h3>Form Settings</h3>
		<div class="margin_virtical_10">
			<label class="left_header">Page</label>
			<?php wp_dropdown_pages(); ?>
		</div>

		<div class="margin_virtical_10">
			<label class="left_header">Gravity Form</label>
			<select name="gravity_form" id="gravity_form">
				<option value="0"></option>
			<?php foreach ( RGFormsModel::get_forms() as $form ) : ?>
				<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $form->id, $data['gravity_form'], true ); ?>><?php echo esc_html( $form->title ); ?></option>
			<?php endforeach; ?>
			</select>
		</div>
	</div>

	<?php foreach ( $groups as $group_name => $group ) : ?>
	<div>
		<h3><?php echo esc_html( $group['label'] ); ?></h3>
		<?php foreach ( $group['fields'] as $field_name => $field ) : ?>
		<div class="margin_virtical_10">
			<label class="left_header">
				<?php echo esc_html( $field['label'] ); ?>
				<?php if ( $field['required'] ) : ?>
					<span class="description"> (required)</span>
				<?php endif; ?>
			</label>

			<select class="form-fields" name="cap[<?php echo esc_attr( $group_name ); ?>][<?php echo esc_attr( $field_name ); ?>]">
				<option value="0"></option>
			<?php foreach ( $gf_form_fields as $gf_field_value => $gf_field_label ) : ?>
				<option value="<?php echo esc_attr( $gf_field_value ); ?>" <?php selected( $gf_field_value, $data[ $group_name ][ $field_name ], true ); ?>><?php echo esc_html( $gf_field_label ); ?></option>
			<?php endforeach; ?>
			</select>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>

	<input type="hidden" name="cap_form_type" value="<?php echo esc_attr( $type ); ?>" />
	<?php echo wp_nonce_field( 'cap-user-registration', 'cap-user-registration' ); ?>
</div>

<script>
jQuery( document ).ready( function($) {
	$( '#gravity_form' ).change( function(e) {
		$.post(
			ajaxurl,
			{
				action: 'cap_get_gravity_form',
				form_id: this.value,
				_wpnonce: '<?php echo wp_create_nonce( 'cap_get_gravity_form' ); ?>'
			},
			function( data ) {
				var select = $( '<select class="form-fields"></select>' );
				$( '<option />', { value: 0, text: '' }).appendTo( select );

				for ( var property in data.fields ) {
					$( '<option />', { value: property, text: data.fields[property] }).appendTo( select );
				}

				$( 'select.form-fields' ).each( function() {
					select.attr( 'name', this.name );
					$( this ).replaceWith( select[0].outerHTML );
				} );
			}
		);
	} );
} );
</script>