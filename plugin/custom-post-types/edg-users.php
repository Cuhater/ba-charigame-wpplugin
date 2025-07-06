<?php

namespace elancer;
/* add CPT */
function construct_user_cpt(): void {
	add_action( 'init', function () {
		register_post_type( 'edg-user', array(
			'labels'              => array(
				'name'                     => 'EDG Users',
				'singular_name'            => 'EDG User',
				'menu_name'                => 'EDG Users',
				'all_items'                => 'Users',
				'edit_item'                => 'Edit EDG User',
				'view_item'                => 'View EDG User',
				'view_items'               => 'View EDG User',
				'add_new_item'             => 'Add New EDG User',
				'add_new'                  => 'Add New EDG User',
				'new_item'                 => 'New EDG User',
				'parent_item_colon'        => 'Parent EDG User:',
				'search_items'             => 'Search EDG User',
				'not_found'                => 'No EDG User found',
				'not_found_in_trash'       => 'No EDG User found in Trash',
				'archives'                 => 'EDG User Archives',
				'attributes'               => 'EDG User Attributes',
				'insert_into_item'         => 'Insert into edg user',
				'uploaded_to_this_item'    => 'Uploaded to this edg user',
				'filter_items_list'        => 'Filter EDG User list',
				'filter_by_date'           => 'Filter EDG User by date',
				'items_list_navigation'    => 'EDG User list navigation',
				'items_list'               => 'EDG User list',
				'item_published'           => 'EDG User published.',
				'item_published_privately' => 'EDG User published privately.',
				'item_reverted_to_draft'   => 'EDG User reverted to draft.',
				'item_scheduled'           => 'EDG User scheduled.',
				'item_updated'             => 'EDG User updated.',
				'item_link'                => 'EDG User Link',
				'item_link_description'    => 'A link to a edg user.',
			),
			'public'              => true,
			'show_in_menu'        => 'edg-types',
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'menu_position'       => 1001,
			'menu_icon'           => 'dashicons-games',
			'supports'            => array(
				0 => 'title',
			),
			'rewrite'             => false,
			'delete_with_user'    => false,
		) );
	} );

	add_action( 'acf/include_fields', function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group( array(
			'key'                   => 'group_65e725a337f93',
			'title'                 => 'EDG User Attributes',
			'fields'                => array(
				array(
					'key'               => 'field_65e732eb3cfa9',
					'label'             => 'First Name',
					'name'              => 'first-name',
					'aria-label'        => '',
					'type'              => 'text',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'maxlength'         => '',
					'placeholder'       => '',
					'prepend'           => '',
					'append'            => '',
				),
				array(
					'key'               => 'field_65e732ff3cfaa',
					'label'             => 'Last Name',
					'name'              => 'last-name',
					'aria-label'        => '',
					'type'              => 'text',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'maxlength'         => '',
					'placeholder'       => '',
					'prepend'           => '',
					'append'            => '',
				),
				array(
					'key'               => 'field_65e733173cfab',
					'label'             => 'E-Mail',
					'name'              => 'email',
					'aria-label'        => '',
					'type'              => 'email',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'default_value'     => '',
					'placeholder'       => '',
					'prepend'           => '',
					'append'            => '',
				),
				array(
					'key'               => 'field_65e725a37714a',
					'label'             => 'Next Birthday',
					'name'              => 'birthday',
					'aria-label'        => '',
					'type'              => 'date_picker',
					'instructions'      => 'As the year of the individual user is not used, the date is set to the current year for data protection reasons.',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'display_format'    => 'd/m',
					'return_format'     => 'md',
					'first_day'         => 1,
				),
				array(
					'key'               => 'field_666aadbac8905',
					'label'             => 'Imported',
					'name'              => 'imported',
					'aria-label'        => '',
					'type'              => 'true_false',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'message'           => '',
					'default_value'     => 0,
					'ui'                => 0,
					'ui_on_text'        => '',
					'ui_off_text'       => '',
				),
				array(
					'key'               => 'field_67fe6033b43c7',
					'label'             => 'Email sent',
					'name'              => 'email_sent',
					'aria-label'        => '',
					'type'              => 'true_false',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'message'           => '',
					'default_value'     => 0,
					'ui'                => 0,
					'ui_on_text'        => '',
					'ui_off_text'       => '',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'edg-user',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'show_in_rest'          => 0,
		) );
	} );

	add_action( 'pre_get_posts', function( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Nur für das Custom Post Type "edg-user"
		if ( $query->get( 'post_type' ) === 'edg-user' && $query->get( 'orderby' ) === 'birthday' ) {
			$query->set( 'meta_key', 'birthday' );
			$query->set( 'orderby', 'meta_value' ); // meta_value_num geht auch, wenn birthday rein numerisch ist (YYYYMMDD)
		}
	} );


	/* Add custom columns to the custom post type list table */
	function custom_post_type_columns( $columns ) {
		// Add a new column for the custom metadata
		$columns['first_name'] = 'First Name';
		$columns['last_name']  = 'Last Name';
		$columns['email']      = 'Email';
		$columns['birthday']   = 'Next Birthday';
		$columns['imported']   = 'Imported';
		$columns['email_sent'] = 'Email Sent';

		return $columns;
	}

	add_filter( 'manage_edg-user_posts_columns', 'elancer\custom_post_type_columns' );

	// Make custom columns sortable
	function custom_post_type_sortable_columns( $columns ) {
		$columns['first_name'] = 'first_name';
		$columns['last_name']  = 'last_name';
		$columns['email']      = 'email';
		$columns['birthday']   = 'birthday';
		$columns['imported']   = 'imported';
		$columns['email_sent'] = 'Email Sent';

		return $columns;
	}

	add_filter( 'manage_edit-edg-user_sortable_columns', 'elancer\custom_post_type_sortable_columns' );

	/* Populate custom post type list table with custom metadata */
	function custom_post_type_column_data( $column, $post_id ) {
		// Check if the column is the one we added
		switch ( $column ) {
			case 'first_name':
				echo get_post_meta( $post_id, 'first-name', true );
				break;
			case 'last_name':
				echo get_post_meta( $post_id, 'last-name', true );
				break;
			case 'email':
				echo get_post_meta( $post_id, 'email', true );
				break;
			case 'birthday':
				$birthday = get_post_meta( $post_id, 'birthday', true );
				if ( is_array( $birthday ) ) {
					$birthday = reset( $birthday );
				}
				if ( ! empty( $birthday ) ) {
					$date  = new \DateTime( $birthday );
					$today = new \DateTime( current_time( 'Y-m-d' ) );
					if ( $date < $today ) {
						$date->modify( '+1 year' );
					}
					echo esc_html( $date->format( 'd.m.Y' ) );
				}

				break;
			case 'imported':
				echo get_post_meta( $post_id, 'imported', true );
				break;
			case 'email_sent':
				echo get_post_meta( $post_id, 'email_sent', true );
				break;
		}
	}

	add_action( 'manage_edg-user_posts_custom_column', 'elancer\custom_post_type_column_data', 10, 2 );

}




