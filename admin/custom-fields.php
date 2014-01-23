<?php if ( $fields ) : ?>
<div id="capsule-custom-fields" class="capsule">
	<?php for ( $i = 0; $i < count( $data['custom']['a'] ) || $i < 1; $i++ ) : ?>
	<div class="custom-fields margin_virtical_10">
		<select style="width:220px;margin-right:35px;" name="cap[custom][a][]">
		<?php foreach ( $fields as $field ) : ?>
			<option value="<?php echo esc_attr( $field->label ); ?>" <?php selected( $data['custom']['a'][ $i ], $field->label, true ); ?>><?php echo esc_html( $field->label ); ?><?php echo isset( $field->tag ) ? ' (' . esc_html( $field->tag ) . ')' : ''; ?></option>
		<?php endforeach; ?>
		</select>

		<select class="form-fields" name="cap[custom][b][]">
			<option value="0"></option>
		<?php foreach ( $gf_form_fields as $key => $value ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $data['custom']['b'][ $i ], $key, true ); ?>><?php echo esc_html( $value ); ?></option>
		<?php endforeach; ?>
		</select>
		<button class="add-field">+</button><button class="remove-field">-</button>
	</div>
	<?php endfor; ?>
</div>
<?php else : ?>
	<p>You have not created any custom fields in Capsule CRM.</p>
<?php endif; ?>

<script>
	jQuery( document ).ready( function( $ ) {
		$( '#capsule-custom-fields' ).on( 'click', 'button.add-field', function( e ) {
			e.preventDefault();
			var $parent = $( this ).parent(),
				newField = $parent.clone();
			$parent.after( newField );
		} );

		$( '#capsule-custom-fields' ).on( 'click', 'button.remove-field', function( e ) {
			e.preventDefault();
			// @todo: do not allow removal of all the fields
			$( this ).parent().remove();
		} );

	} );
</script>