<?php

/**
 * This class provides templates for displaying and
 * validating data.
 */
class Capsule_CRM_Data {

	public static function party() {
		return array(
			'default' => array(
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

	/**
	 *
	 */
	public static function opportunity() {
		return array(
			'default' => array(
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

	/**
	 *
	 */
	public static function get_form_data( $template, $entry, $form ) {
		$data = array();

		foreach ( $template as $group_name => $group ) {
			foreach ( $group['fields'] as $field_name => $field ) {
				if ( 'default' === $group_name ) {

					$data[ $field_name ] = $entry[ $form['default'][ $field_name ] ];
					if ( is_null( $data[ $field_name ] ) ) {
						$data[ $field_name ] = '';
					}
				} else {
					// @todo: this should be generic and not hard code "contacts"
					$data['contacts'][ $group_name ][ $field_name ] = $entry[ $form[ $group_name ][ $field_name ] ];
					if ( is_null( $data[ $group_name ][ $field_name ] ) ) {
						$data[ $group_name ][ $field_name ] = '';
					}
				}
			}
		}

		return $data;
	}
}

class Capsule_CRM_Form {
	/**
	 * Get the Capsule Form associated with a Gravity Form
	 * @param $gf_id - Gravity Form id
	 * @return bool|int
	 */
	public static function get_id( $gf_id ) {
		$query = new WP_Query( array(
			'post_type' => 'capsule_form',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'meta_query' => array(
				array(
					'key' => 'cap_gravity_form',
					'value' => array( $gf_id ),
					'compare' => 'IN',
				),
			),
		) );

		if ( ! $query->have_posts() ) {
			return false;
		}

		return $query->post->ID;
	}

	/**
	 * Get the Capsule Form meta
	 * @param $gf_id - Gravity Form id
	 * @return array
	 */
	public static function get_meta( $gf_id ) {
		$post_id = self::get_id( $gf_id );

		$meta = get_post_meta( $post_id, 'cap_form_data' );
		if ( ! isset( $meta[0] ) ) {
			return array();
		}

		return $meta[0];
	}

	/**
	 * Get the Capsule Form type
	 * @param $gf_id - Gravity Form id
	 * @return string
	 */
	public static function get_type( $gf_id ) {
		$post_id = self::get_id( $gf_id );
		return self::get_the_type( $post_id );
	}

	public static function get_the_type( $post_id ) {
		if ( isset( $_GET['view'] ) ) {
			return $_GET['view'];
		}

		return get_post_meta( $post_id, 'cap_form_type', true );
	}
}

class Capsule_CRM_GF {
	/**
	 *
	 * @param $form_id
	 * @return array
	 */
	public static function get_fields( $form_id ) {
		// Get the Gravity Form meta
		$form_info = RGFormsModel::get_form_meta_by_id( $form_id );
		if ( ! isset( $form_info[0]['fields'] ) ) {
			return array();
		}

		$ret = array();
		foreach ( $form_info[0]['fields'] as $input ) {
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
}