<?php

namespace elancer;
/* add CPT */
function construct_recipients_cpt(): void {
	add_action( 'init', function () {
		register_post_type( 'edg-donation-recipie', array(
			'labels'             => array(
				'name'                     => 'ChariGame Donation Recipients',
				'singular_name'            => 'ChariGame Donation Recipient',
				'menu_name'                => 'ChariGame Donation Recipients ',
				'all_items'                => 'Donation Recipients',
				'edit_item'                => 'Edit ChariGame Donation Recipient',
				'view_item'                => 'View ChariGame Donation Recipient',
				'view_items'               => 'View ChariGame Donation Recipients ',
				'add_new_item'             => 'Add New ChariGame Donation Recipient',
				'add_new'                  => 'Add New ChariGame Donation Recipient',
				'new_item'                 => 'New ChariGame Donation Recipient',
				'parent_item_colon'        => 'Parent ChariGame Donation Recipient:',
				'search_items'             => 'Search ChariGame Donation Recipients ',
				'not_found'                => 'No ChariGame Donation Recipient found',
				'not_found_in_trash'       => 'No ChariGame Donation Recipient found in Trash',
				'archives'                 => 'EDG Donation Recipient Archives',
				'attributes'               => 'EDG Donation Recipient Attributes',
				'insert_into_item'         => 'Insert into edg donation recipient',
				'uploaded_to_this_item'    => 'Uploaded to this edg donation recipient',
				'filter_items_list'        => 'Filter EDG Donation Recipients list',
				'filter_by_date'           => 'Filter EDG Donation Recipients by date',
				'items_list_navigation'    => 'EDG Donation Recipients	list navigation',
				'items_list'               => 'EDG Donation Recipients	list',
				'item_published'           => 'EDG Donation Recipient published.',
				'item_published_privately' => 'EDG Donation Recipient published privately.',
				'item_reverted_to_draft'   => 'EDG Donation Recipient reverted to draft.',
				'item_scheduled'           => 'EDG Donation Recipient scheduled.',
				'item_updated'             => 'EDG Donation Recipient updated.',
				'item_link'                => 'EDG Donation Recipient Link',
				'item_link_description'    => 'A link to a edg donation recipient.',
			),
			'public'             => true,
			'show_in_menu'       => 'edg-types',
			'publicly_queryable' => false,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => false,
			'menu_position'      => 1000,
			'menu_icon'          => 'dashicons-games',
			'supports'           => array(
				0 => 'title',
			),
			'rewrite'            => false,
			'delete_with_user'   => false,
		) );
	} );

	/* add CPT Attributes */
	add_action( 'acf/include_fields', function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group( array(
			'key'                   => 'group_65e734dc669a3',
			'title'                 => 'EDG Donation Recipients Attributes',
			'fields'                => array(
				array(
					'key'               => 'field_65e734dc32e7c',
					'label'             => 'Logo',
					'name'              => 'logo',
					'aria-label'        => '',
					'type'              => 'image',
					'instructions'      => '',
					'required'          => 0,
					'conditional_logic' => 0,
					'wrapper'           => array(
						'width' => '',
						'class' => '',
						'id'    => '',
					),
					'return_format'     => 'url',
					'library'           => 'all',
					'min_width'         => '',
					'min_height'        => '',
					'min_size'          => '',
					'max_width'         => '',
					'max_height'        => '',
					'max_size'          => '',
					'mime_types'        => '',
					'preview_size'      => 'medium',
				),
				array(
					'key'               => 'field_65e734fd32e7d',
					'label'             => 'Name',
					'name'              => 'name',
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
					'key'               => 'field_65e7350932e7e',
					'label'             => 'Description',
					'name'              => 'description',
					'aria-label'        => '',
					'type'              => 'textarea',
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
					'rows'              => '',
					'placeholder'       => '',
					'new_lines'         => '',
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'edg-donation-recipie',
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
}

