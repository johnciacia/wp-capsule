<div class="wrap capsule">
	<?php $data = cap_get_form_values(); ?>
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

	<?php $inputs = _cap_get_rgform_fields(); ?>
	<?php foreach ( cap_get_form_fields() as $group ) : ?>
	<div class="form-options">
		<h3><?php echo esc_html( $group['label'] ); ?></h3>
		<?php foreach ( $group['fields'] as $key => $field ) : ?>
		<div class="margin_virtical_10">
			<label class="left_header">
				<?php echo esc_html( $field['label'] ); ?>
				<?php if ( $field['required'] ) : ?>
					<span class="description"> (required)</span>
				<?php endif; ?>
			</label>
			<?php $name = strtolower( str_replace( ' ', '_', $group['label'] ) . '_' . $key ); ?>
			<select name="<?php echo esc_attr( $name ); ?>">
			<?php foreach ( $inputs as $key => $value ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $data[ $name ], true ); ?>><?php echo esc_html( $value ); ?></option>
			<?php endforeach; ?>
			</select>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endforeach; ?>

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
				var select = $( '<select></select>' );
				$( '<option />', { value: 0, text: '' }).appendTo( select );

				for ( var property in data.fields ) {
					$( '<option />', { value: property, text: data.fields[property] }).appendTo( select );
				}

				$( '.form-options select' ).each( function() {
					select.attr( 'name', this.name );
					$( this ).replaceWith( select[0].outerHTML );
				} );
			}
		);
	} );
} );
</script>