<?php
/**
 * Plugin Name: WP Librarian
 * Plugin URI: http://nerdverse.co.uk/wp-librarian
 * Description: Use WP-Librarian to manage a library of books/media and track who they're lent to and when they're due back.
 * Version: 0.0.1
 * Author: Kit Maywood
 * Text Domain: wp-librarian
 * Author URI: http://nerdverse.co.uk/wp-librarian
 * License: GPL2
 */

	/* -- External Files used -- */
	
require_once (plugin_dir_path(__FILE__) . '/wp-librarian-functions.php');
require_once (plugin_dir_path(__FILE__) . '/wp-librarian-helpers.php');
require_once (plugin_dir_path(__FILE__) . '/wp-librarian-ajax.php');


	/* -- Custom Post Types and Taxonomies -- */
	/* Registers custom post types and taxonomies */
	
// Creates custom post types to store library items and loans
add_action( 'init', 'wp_lib_register_post_and_tax' );
function wp_lib_register_post_and_tax() {

	// Creates post type for library items
	$args =	array(
		'labels' => array(
			'name'					=> 'Library',
			'singular_name'			=> 'Library Item',
			'name_admin_bar'		=> 'Add New Item',
			'all_items'				=> 'All Items',
			'add_new'				=> 'Add New',
			'add_new_item'			=> 'Add New',
			'edit'					=> 'Edit',
			'edit_item'				=> 'Edit Item',
			'new_item'				=> 'New Item',
			'view_item'				=> 'View Library Item',
			'search_items'			=> 'Search Library Items',
			'not_found'				=> 'No Items found',
			'not_found_in_trash'	=> 'No Items found in Trash',
			'parent_item_colon'		=> 'Parent Library Item'
		),

		'public'				=> true,
		'menu_position'			=> 15,
		'supports'				=> array( 'title', 'editor', 'thumbnail'),
		'taxonomies'			=> array( '' ),
		'menu_icon'				=> 'dashicons-book-alt',
		'has_archive'			=> true,
		'rewrite'				=> array('slug' => get_option( 'wp_lib_main_slug', 'wp-librarian' ) ),
		'register_meta_box_cb'	=> 'wp_lib_setup_meta_box'
	);
	register_post_type( 'wp_lib_items', $args );
	
	// Creates post type to manage loaned items
	$args =	array(
		'labels' => array(
			'name'				=> 'Loans',
			'singular_name'		=> 'Loan',
			'add_new'			=> 'Add New Loan',
			'add_new_item'		=> 'Add New Loan',
			'edit'				=> 'Edit',
			'edit_item'			=> 'Edit Loan',
			'new_item'			=> 'New Loan',
			'view_item'			=> 'View Loan',
			'search_items'		=> 'Search Loans',
			'not_found'			=> 'No Loans found',
			'not_found_in_trash'=> 'No Loans found in Trash',
			'parent'			=> 'Parent Loan',
		),

		'public'		=> true,
		'show_in_menu' 	=> 'edit.php?post_type=wp_lib_items',
		'supports'		=> array( '' ),
		'taxonomies'	=> array( '' ),
		'menu_icon'		=> 'dashicons-book-alt',
		'has_archive'	=> true,
		'rewrite'		=> array('slug' => get_option( 'wp_lib_loans_slug', 'loans' ) ),
	);
	register_post_type( 'wp_lib_loans', $args );
	
	// Creates post type to manage fines
	$args =	array(
		'labels' => array(
			'name'				=> 'Fines',
			'singular_name'		=> 'Fine',
			'add_new'			=> 'Add New Fine',
			'add_new_item'		=> 'Add New Fine',
			'edit'				=> 'Edit',
			'edit_item'			=> 'Edit Fine',
			'new_item'			=> 'New Fine',
			'view_item'			=> 'View Fine',
			'search_items'		=> 'Search Fines',
			'not_found'			=> 'No Fines found',
			'not_found_in_trash'=> 'No Fines found in Trash',
			'parent'			=> 'Parent Fine',
		),

		'public'		=> true,
		'show_in_menu'	=> 'edit.php?post_type=wp_lib_items',
		'supports'		=> array( '' ),
		'taxonomies'	=> array( '' ),
		'menu_icon'		=> 'dashicons-book-alt',
		'has_archive'	=> true,
		'rewrite'		=> array('slug' => get_option( 'wp_lib_fines_slug', 'fines' ) ),
	);
	register_post_type( 'wp_lib_fines', $args );
}

// Registers all Taxonomies used by the plugin
add_action( 'init', function() {
	
	// Registers Taxonomy: Media Type - The type of item in the library (book, CD, DVD, etc.)
	$labels = array(
		'name'				=> 'Media Types',
		'singular_name'		=> 'Media Type',
		'search_items'		=> 'Search Media Types',
		'all_items'			=> 'All Media Types',
		'parent_item'		=> 'Parent Media Type',
		'parent_item_colon'	=> 'Parent Media Type:',
		'edit_item'			=> 'Edit Media Type',
		'update_item'		=> 'Update Media Type',
		'add_new_item'		=> 'Add New Media Type',
		'new_item_name'		=> 'New Media Type Name',
		'menu_name'			=> 'Media Type'
	);

	$args = array(
		'hierarchical'		=> true,
		'labels'			=> $labels,
		'show_ui'			=> true,
		'show_admin_column'	=> true,
		'query_var'			=> true,
		'rewrite'			=> array('slug' => wp_lib_prefix_url( 'wp_lib_media_type_slug', 'type' ) ),
	);

	register_taxonomy( 'wp_lib_media_type', 'wp_lib_items', $args );
	
	// Creates Default Media Type entries if they don't already exist, unless configured otherwise
	if ( get_option( 'wp_lib_default_media_types', true ) ) {
		$default_media_types = array(
			array(
				'name' => 'Book',
				'slug' => 'books',
			),
			array(
				'name' => 'DVD',
				'slug' => 'dvds',
			),
			array(
				'name' => 'Graphic Novel',
				'slug' => 'graphic-novels',
			),
		);
		// Iterates through default media types and creates them if they do not already exist
		foreach ( $default_media_types as $type ) {
			if ( get_term_by( 'name', $type['name'], 'wp_lib_media_type' ) == false){
				wp_insert_term(
					$type['name'],
					'wp_lib_media_type',
					array( 'slug' => $type['slug'] )
				);
			}
		}
	}

	// Registers Taxonomy: Authors - For the people who created the physical library item e.g. H.G. Wells or Terry Pratchett
	$labels = array(
		'name'						=> 'Authors',
		'singular_name'				=> 'Author',
		'search_items'				=> 'Search Authors',
		'popular_items'				=> 'Popular Authors',
		'all_items'					=> 'All Authors',
		'edit_item'					=> 'Edit Author',
		'update_item'				=> 'Update Author',
		'add_new_item'				=> 'Add New Author',
		'new_item_name'				=> 'New Author Name',
		'separate_items_with_commas'=> 'Separate authors with commas',
		'add_or_remove_items'		=> 'Add or remove authors',
		'choose_from_most_used'		=> 'Choose from the most used authors',
		'not_found'					=> 'No authors found.',
		'menu_name'					=> 'Authors',
	);
	
	$args = array(
		'hierarchical'			=> false,
		'labels'				=> $labels,
		'show_ui'				=> true,
		'show_admin_column'		=> true,
		'update_count_callback'	=> '_update_post_term_count',
		'query_var'				=> true,
		'rewrite'				=> array('slug' => wp_lib_prefix_url( 'wp_lib_authors_slug', 'authors' ) ),
	);

	register_taxonomy( 'wp_lib_author', 'wp_lib_items', $args );

	// Registers Taxonomy: Donors - The people who donated the library item to the library
	$labels = array(
		'name'						=> 'Donors',
		'singular_name'				=> 'Donor',
		'search_items'				=> 'Search Donors',
		'popular_items'				=> 'Popular Donors',
		'all_items'					=> 'All Donors',
		'edit_item'					=> 'Edit Donor',
		'update_item'				=> 'Update Donor',
		'add_new_item'				=> 'Add New Donor',
		'new_item_name'				=> 'New Donor Name',
		'separate_items_with_commas'=> 'Separate donors with commas',
		'add_or_remove_items'		=> 'Add or remove donors',
		'choose_from_most_used'		=> 'Choose from the most used donors',
		'not_found'					=> 'No donors found.',
		'menu_name'					=> 'Donors',
	);
	
	$args = array(
		'hierarchical'			=> false,
		'labels'				=> $labels,
		'show_ui'				=> true,
		'show_admin_column'		=> true,
		'update_count_callback'	=> '_update_post_term_count',
		'query_var'				=> true,
		'rewrite'				=> array('slug' => wp_lib_prefix_url( 'wp_lib_donors_slug', 'donors' ) ),
	);

	register_taxonomy( 'wp_lib_donor', 'wp_lib_items', $args );

	// Registers Taxonomy: Members - People who borrow items from the library
	$labels = array(
		'name'						=> 'Members',
		'singular_name'				=> 'Member',
		'search_items'				=> 'Search Members',
		'popular_items'				=> 'Popular Members',
		'all_items'					=> 'All Members',
		'edit_item'					=> 'Edit Member',
		'update_item'				=> 'Update Member',
		'add_new_item'				=> 'Add New Member',
		'new_item_name'				=> 'New Member Name',
		'separate_items_with_commas'=> 'Separate members with commas',
		'add_or_remove_items'		=> 'Add or remove members',
		'choose_from_most_used'		=> 'Choose from the most used members',
		'not_found'					=> 'No members found.',
		'menu_name'					=> 'Members',
	);
	
	$args = array(
		'hierarchical'			=> false,
		'labels'				=> $labels,
		'show_ui'				=> true,
		'show_admin_column'		=> true,
		'update_count_callback'	=> '_update_post_term_count',
		'query_var'				=> true,
		'rewrite'				=> array('slug' => wp_lib_prefix_url( 'wp_lib_members_slug', 'members' ) ),
	);

	register_taxonomy( 'wp_lib_member', 'wp_lib_items', $args );
	
});

	/* -- Custom Post Table Columns -- */
	/* Adds/removes columns from item/loan/fine post tables then populates said columns */

// Adds custom columns to wp-admin item table and removes unneeded ones
add_filter( 'manage_wp_lib_items_posts_columns', function ( $columns ) {
	// Removes 'Members' column
	unset(
		$columns['taxonomy-wp_lib_member']
	);
	
	// Adds item status and item condition columns
	$new_columns = array(
		'item_status'		=> 'Loan Status',
		'item_condition'	=> 'Item Condition'
	);
	
	// Adds new columns between existing ones
	$columns = array_slice( $columns, 0, 5, true ) + $new_columns + array_slice( $columns, 5, NULL, true );
	
	return $columns;
});

// Adds data to custom columns in item table
add_action( 'manage_wp_lib_items_posts_custom_column' , function ( $column, $post_id ) {
	switch ( $column ) {
		// Displays the current status of the item (On Loan/Available/Late)
		case 'item_status':
			echo wp_lib_prep_item_available( $post_id, false, true );
		break;
		
		// Fetches and formats the condition the item is in, using a placeholder if the condition is unspecified
		case 'item_condition':
			echo wp_lib_format_item_condition( get_post_meta( $post_id, 'wp_lib_item_condition', true ) );
		break;
	}
}, 10, 2 );

// Add custom columns to wp-admin loans table and removes unneeded ones
add_filter( 'manage_wp_lib_loans_posts_columns', function ( $columns ) {
	// Removes 'Date' and 'Title' columns
	unset( $columns['date'], $columns['title'] );

	// Adds useful loan meta to loan table
	$new_columns = array(
		'loan_item'			=> 'Item',
		'loan_member'		=> 'Member',
		'loan_status'		=> 'Status',
		'loan_start'		=> 'Loaned',
		'loan_end'			=> 'Expected',
		'loan_returned'		=> 'Returned',
	);
	
	$columns = array_slice( $columns, 0, 2, true ) + $new_columns + array_slice( $columns, 2, NULL, true );
	
	return $columns;
});

// Adds data to custom columns in loans table
add_action( 'manage_wp_lib_loans_posts_custom_column' , function ( $column, $loan_id ) {
	switch ( $column ) {
		// Displays title of loaned item with link to view item
		case 'loan_item':
			// Fetches item ID from loan meta
			$item_id = get_post_meta( $loan_id, 'wp_lib_item', true );
			
			// Fetches item title
			$title = get_the_title( $item_id );
			
			// If item has been deleted, fetch item title from loan meta
			if ( !$title ) {
				echo get_post_meta( $loan_id, 'wp_lib_archive', true )['item-name'] . ' (item deleted)';
				return;
			}
			
			// Fetches link to item
			$url = wp_lib_format_manage_item( $item_id );
			
			echo "<a href=\"{$url}\">{$title}</a>";
		break;
		
		// Displays member that item has been loaned to
		case 'loan_member':
			// Fetches member object from loan taxonomy
			$member = get_the_terms( $loan_id, 'wp_lib_member' )[0];
			
			// If member does not exist (has been deleted), fetch archived member data from loan
			if ( !$member )
				echo get_post_meta( $loan_id, 'wp_lib_archive', true )['member-name'] . ' (member deleted)';
			// If $member isn't False, member has been found with that ID
			else {
				// Constructs url to view/manage the member
				$url = wp_lib_format_manage_member( $member->term_id );
				
				// Displays member name with link to view member in Library Dashboard
				echo "<a href=\"{$url}\">{$member->name}</a>";
			}
		break;
		
		// Displays loan status (Open/Closed)
		case 'loan_status':
			// Fetches status from loan meta
			$status = get_post_meta( $loan_id, 'wp_lib_status', true );
			
			// Formats status
			$status_formatted = wp_lib_format_loan_status( $status );
			
			// If loan status indicates a fine was charged
			if ( $status == 4 ){
				// Fetches fine ID from loan meta
				$fine_id = get_post_meta( $loan_id, 'wp_lib_fine', true );
				
				// Composes url to manage fine
				$url = wp_lib_format_manage_fine( $fine_id );
				
				// Creates and displays hyperlink
				echo "<a href=\"{$url}\">{$status_formatted}</a>";
			}
			else {
				// Otherwise displays status (no hyperlink)
				echo $status_formatted;
			}
		break;
		
		// Displays date item was loaned
		case 'loan_start':
			echo wp_lib_prep_date_column( $loan_id, 'wp_lib_start_date' );
		break;
		
		// Displays date item should be returned by
		case 'loan_end':
			echo wp_lib_prep_date_column( $loan_id, 'wp_lib_end_date' );
		break;
		
		// Displays date item was actually returned
		case 'loan_returned':
			echo wp_lib_prep_date_column( $loan_id, 'wp_lib_returned_date' );
		break;
	}
}, 10, 2 );

// Add custom columns to wp-admin fines table and removes unneeded ones
add_filter( 'manage_wp_lib_fines_posts_columns', function ( $columns ) {
	// Removes 'Date' and 'Title' columns
	unset( $columns['date'], $columns['title'] );

	// Adds useful loan meta to loan table
	$new_columns = array(
		'fine_item'			=> 'Item',
		'fine_member'		=> 'Member',
		'fine_amount'		=> 'Charge',
		'fine_status'		=> 'Status',
	);
	
	$columns = array_slice( $columns, 0, 2, true ) + $new_columns + array_slice( $columns, 2, NULL, true );
	
	return $columns;
});

// Adds data to custom columns in fines table
add_action( 'manage_wp_lib_fines_posts_custom_column', function ( $column, $fine_id ) {
	switch ( $column ) {
		// Displays title of item fine is for with link to view item
		case 'fine_item':
			// Fetches item ID from loan meta
			$item_id = get_post_meta( $fine_id, 'wp_lib_item', true );
			
			// Fetches item title
			$title = get_the_title( $item_id );
			
			// If item has been deleted, fetch item title from fine meta
			if ( !$title ) {
				echo get_post_meta( $fine_id, 'wp_lib_archive', true )['item-name'] . ' (item deleted)';
				return;
			}
			
			// Fetches link to item
			$url = wp_lib_format_manage_item( $item_id );
			
			echo "<a href=\"{$url}\">{$title}</a>";
		break;
		
		// Displays member that has been fined
		case 'fine_member':
			// Fetches member object from fine taxonomy
			$member_id = get_the_terms( $fine_id, 'wp_lib_member' )[0];
			
			// If member does not exist (has been deleted), fetch archived member data from fine
			if ( !$member )
				echo get_post_meta( $fine_id, 'wp_lib_archive', true )['member-name'] . ' (member deleted)';
			else {
				// Constructs url to view/manage the member
				$url = wp_lib_format_manage_member( $member->term_id );
				
				// Displays member name with link to view member in Library Dashboard
				echo "<a href=\"{$url}\">{$member->name}</a>";
			}
		break;

		case 'fine_amount':
			// Fetches fine amount from fine's post meta
			$fine = get_post_meta( $fine_id, 'wp_lib_fine', true );
			
			// Formats fine with local currency and displays it
			echo wp_lib_format_money( $fine );
		break;
		
		
		case 'fine_status':
			// Fetches fine status from post meta
			$status = get_post_meta( $fine_id, 'wp_lib_status', true );
			
			// Composes url to manage the fine
			$url = wp_lib_format_manage_fine( $fine_id );
			
			// Turns numerical status into readable string e.g. 1 -> 'Unpaid'
			$status = wp_lib_format_fine_status( $status );
			
			// Gets fine status in a readable format and displays it
			echo "<a href=\"{$url}\">{$status}</a>";
		break;
	}
}, 10, 2 );

	/* -- Meta boxes -- */
	/* Adds and populates new meta boxes and removes unneeded ones from item edit page */

// Creates meta box below Library Item description, hooked during post type registration
function wp_lib_setup_meta_box() {
	add_meta_box(
		'library_meta_box',
		'Library Item Details',
		'wp_lib_render_meta_box',
		'wp_lib_items',
		'normal',
		'high'
	);
}

// Updates item's meta using new values from meta box
add_action( 'save_post', function ( $item_id, $item ) {

	// Verify the nonce before proceeding
	if ( !isset( $_POST['wp_lib_item_nonce'] ) || !wp_verify_nonce( $_POST['wp_lib_item_nonce'], "Updating item {$item_id} meta" ) )
		return $item_id;

	// Get the post type object
	$post_type = get_post_type_object( $item->post_type );

	// Check if the current user has permission to edit the post
	if ( !current_user_can( $post_type->cap->edit_post, $item_id ) )
		return $item_id;
	
	// Stores all meta box fields and sanitization methods
	$meta_array = array(
		array(
			'key'		=> 'wp_lib_item_isbn',
			'sanitize'	=> 'sanitize_html_class'
		),
		array(
			'key'		=> 'wp_lib_item_loanable',
			'sanitize'	=> 'wp_lib_sanitize_checkbox'
		),
		array(
			'key'		=> 'wp_lib_item_condition',
			'sanitize'	=> 'wp_lib_sanitize_number'
		),
		array(
			'key'		=> 'wp_lib_item_barcode',
			'sanitize'	=> 'wp_lib_sanitize_number'
		),
		array(
			'key'		=> 'wp_lib_item_cover_type',
			'sanitize'	=> 'wp_lib_sanitize_number'
		),
		array(
			'key'		=> 'wp_lib_media_type',
			'sanitize'	=> 'sanitize_title',
			'tax'		=> true
		)
	);
	
	// Iterates through each meta field, fetching and sanitizing it then saving/updating/deleting as appropriate
	foreach ( $meta_array as $meta ) {
		// Checks if sanitizing function exists
		if ( !is_callable( $meta['sanitize'] ) )
			return $item_id;
	
		// Get the posted data and sanitize it for use as an HTML class
		$new_meta_value = ( isset( $_POST[$meta['key']] ) ? $meta['sanitize']( $_POST[$meta['key']] ) : '' );
		
		// If data is a taxonomy term and not a meta value
		if ( $meta['tax'] ) {
			// If new meta value is not empty
			if ( $new_meta_value ) {
				// Attempts to fetch term from its respective taxonomy
				$term = get_term_by( 'slug', $new_meta_value, $meta['key'] );
				
				// If term exists
				if ( $term )
					// Updates item's term for that taxonomy
					wp_set_object_terms( $item_id, $term->name, $meta['key'] );
			} else {
				// Removes any term(s) attached to item for current taxonomy
				wp_delete_object_term_relationships( $item_id, $meta['key'] );
			}
			// Skips rest of current loop
			continue;
		}
		
		// Get the meta key
		$meta_key = $meta['key'];

		// Get the meta value of the custom field key
		$meta_value = get_post_meta( $item_id, $meta_key, true );

		// If a new meta value was added and there was no previous value, add it
		if ( $new_meta_value && '' == $meta_value )
			add_post_meta( $item_id, $meta_key, $new_meta_value, true );

		// If the new meta value does not match the old value, update it
		elseif ( $new_meta_value && $new_meta_value != $meta_value )
			update_post_meta( $item_id, $meta_key, $new_meta_value );

		// If there is no new meta value but an old value exists, delete it
		elseif ( '' == $new_meta_value && $meta_value )
			delete_post_meta( $item_id, $meta_key, $meta_value );
	}
}, 10, 2 );

// Hooks member creation/editing to member meta updating
add_action( 'edited_wp_lib_member', 'wp_lib_process_member_meta_box', 10, 2 );  
add_action( 'create_wp_lib_member', 'wp_lib_process_member_meta_box', 10, 2 );

// Updates member's meta values when relevant pages are also updated
function wp_lib_process_member_meta_box( $member_id ) {

	// Stores all meta box fields and sanitization methods
	$meta_index = array(
		array(
			'post'		=> 'wp_lib_member_phone',
			'sanitize'	=> 'wp_lib_sanitize_phone_number',
			'key'		=> 'member_phone',
		),
		array(
			'post'		=> 'wp_lib_member_mobile',
			'sanitize'	=> 'wp_lib_sanitize_phone_number',
			'key'		=> 'member_mobile',
		),
	);
	
	$meta_array = get_option( "wp_lib_yax_{$member_id}", false );
	
	if ( !$meta_array )
		$meta_array = array();
	
	foreach ( $meta_index as $meta ) {
		// Checks if sanitizing function exists
		if ( !is_callable( $meta['sanitize'] ) )
			return $item_id;
	
		// Get the posted data and sanitize it for use as an HTML class
		$new_meta_value = ( isset( $_POST[$meta['post']] ) ? $meta['sanitize']( $_POST[$meta['post']] ) : '' );

		// Adds new meta value to array to be saved
		$meta_array[$meta['key']] = $new_meta_value;
	}
	
	// Saves meta array
	wp_lib_update_member_meta( $member_id, $meta_array );
}

// Removes unneeded meta boxes from item editing page
add_action( 'admin_menu', function() {
	// Removes 'Member' taxonomy box
	remove_meta_box( 'tagsdiv-wp_lib_member', 'wp_lib_items', 'side' );
	
	// Removes 'Donor' taxonomy box
	remove_meta_box( 'tagsdiv-wp_lib_donor', 'wp_lib_items', 'side' );
	
	// Removes 'Media Type' taxonomy box
	remove_meta_box( 'wp_lib_media_typediv', 'wp_lib_items', 'side' );
});

	/* -- Admin Menus -- */
	/* Registers sub-menu items to wp-admin menu */

// Registers Settings page and Management Dashboard
add_action( 'admin_menu', function() {
	if ( current_user_can( 'manage_options' ) )
		// Adds settings page to Library submenu of wp-admin menu
		add_submenu_page('edit.php?post_type=wp_lib_items', 'WP Librarian Settings', 'Settings', 'activate_plugins', 'wp-lib-settings', 'wp_lib_render_settings');

	// Registers Library Dashboard and saves handle to variable
	$page = add_submenu_page('edit.php?post_type=wp_lib_items', 'Library Dashboard', 'Dashboard', 'edit_post', 'dashboard', 'wp_lib_render_dashboard');
});

	/* -- Settings API -- */
	/* Registers WP-Librarian settings fields so WordPress handles their rendering and saving */

// Sets up plugin settings
add_action( 'admin_init', function () {
	// Registers settings groups and their sanitization callbacks
	add_settings_section( 'wp_lib_slug_group', 'Slugs', 'wp_lib_settings_slugs_section_callback', 'wp_lib_items_page_wp-lib-settings' );
	
	// All slugs settings parameters
	$all_slugs = array(
		array(
			'name'	=> 'wp_lib_main_slug',
			'title'	=> 'Main'
		),
		array(
			'name'	=> 'wp_lib_authors_slug',
			'title'	=> 'Authors',
			'end'	=> 'terry-pratchett'
		),
		array(
			'name'	=> 'wp_lib_media_type_slug',
			'title'	=> 'Media Type',
			'end'	=> 'comic-books'
		),
		array(
			'name'	=> 'wp_lib_donors_slug',
			'title'	=> 'Donors',
			'end'	=> 'john-snow'
		)
	
	);
	
	// Fetches site URL
	$site_url = site_url();
	
	// Fetches main slug
	$main_slug = get_option( 'wp_lib_main_slug', 'null' );
	
	// Creates settings field for each slug
	foreach ( $all_slugs as $slug ) {
		$slug['url'] = $site_url;
		$slug['main'] = $main_slug;
		add_settings_field( $slug['name'], $slug['title'], 'wp_lib_render_field_slug', 'wp_lib_items_page_wp-lib-settings', 'wp_lib_slug_group', $slug );
		register_setting( 'wp_lib_slug_group', $slug['name'], 'wp_lib_sanitize_settings_slugs' );
	}
});

// Renders description of slug settings group
function wp_lib_settings_slugs_section_callback() {
	echo '<p>These form the URLs of the front end of your website</p>';
}

// Renders a single settings option for editing a slug
function wp_lib_render_field_slug( $args ) {
	// If the current slug is not the main slug, the slug's current value must be fetched
	if ( $args['name'] != 'wp_lib_main_slug' )
		$current_slug = get_option( $args['name'], 'null' );
	// Otherwise current slug is main slug, so the option has already been fetched
	else {
		$current_slug = $args['main'];
		$is_main = true;
	}
	
	// Fetches site URL
	$site_url = site_url();
	
	// Renders input form and preview of slug, note classes are used by JavaScript on the page to provide a live preview
	echo "<input type=\"text\" id=\"{$args['name']}\" name=\"{$args['name']}\" class=\"slug-input\" value=\"{$current_slug}\" />";
	echo "<label for=\"{$args['name']}\" class=\"slug-label\">";
	if ( $is_main )
		echo "{$args['url']}/<span class=\"wp_lib_main_slug-text\">{$args['main']}</span>/";
	else
		echo "{$args['url']}/<span class=\"wp_lib_main_slug-text\">{$args['main']}</span>/<span class=\"{$args['name']}-text\">{$current_slug}</span>/{$args['end']}/";
	echo '</label>';
}

// Sanitizes updated slugs submitted from the plugin settings page
function wp_lib_sanitize_settings_slugs( $slugs ) {
	return sanitize_title( $slugs );
}

// Renders plugin settings page
function wp_lib_render_settings() {
	require_once( plugin_dir_path(__FILE__) . '/wp-librarian-admin-settings.php' );
}

	/* -- Scripts and Styles -- */
	/* Registers and enqueues JavaScript and CSS files on the relevant pages, including their dependencies */

// Enqueues scripts and styles needed on different wp-admin pages
add_action( 'admin_enqueue_scripts', function( $hook ) {
	// Registers core JavaScript file for WP-Librarian, a collection of various essential functions
	wp_register_script( 'wp_lib_core', wp_lib_script_url( 'admin-core' ), array( 'jquery', 'jquery-ui-datepicker' ), '0.1' );

	// Sets up array of variables to be passed to JavaScript
	$vars = array(
			'siteurl' 	=> get_option( 'siteurl' ),
			'adminurl'	=> admin_url(),
			'pluginsurl'=> plugins_url( '', __FILE__ ),
			'dashurl'	=> wp_lib_format_dash_url(),
			'sitename'	=> get_bloginfo( 'name' ),
			'getparams'	=> $_GET
	);
	
	// Sends variables to user
	wp_localize_script( 'wp_lib_core', 'wp_lib_vars', $vars );
	
	if ( ( $hook == 'post-new.php' || $hook == 'post.php' ) && $GLOBALS['post_type'] == 'wp_lib_items' ) {
		wp_register_style( 'wp_lib_admin_meta', wp_lib_style_url( 'admin-meta-box' ), array(), '0.1' );
		wp_enqueue_script( 'wp_lib_edit_item', wp_lib_script_url( 'admin-edit-item' ), array(), '0.1' );
	}
	
	switch ( $hook ) {
		// Plugin settings page
		case 'wp_lib_items_page_wp-lib-settings':
			wp_enqueue_script( 'wp_lib_settings', wp_lib_script_url( 'admin-settings' ), array( 'wp_lib_core' ), '0.2' );
			wp_register_style( 'wp_lib_admin_settings', wp_lib_style_url( 'admin-settings' ), array(), '0.1' );
		break;
		
		// Library Dashboard
		case 'wp_lib_items_page_dashboard':
			wp_enqueue_script( 'wp_lib_dashboard', wp_lib_script_url( 'admin-dashboard' ), array( 'wp_lib_core' ), '0.1' );
			wp_enqueue_script( 'dynatable', wp_lib_script_url( 'dynatable' ), array(), '0.3.1' );
			wp_enqueue_style( 'wp_lib_dashboard', wp_lib_style_url( 'admin-dashboard' ), array(), '0.1' );
			wp_enqueue_style( 'wp_lib_mellon-datepicker', wp_lib_style_url( 'mellon-datepicker' ), array(), '0.1' ); // Styles Datepicker
			wp_enqueue_style( 'jquery-ui', wp_lib_style_url( 'jquery-ui' ), array(), '1.10.1' ); // Core Datepicker Styles
			wp_enqueue_style( 'dynatable', wp_lib_style_url( 'dynatable' ), array( 'jquery-ui' ), '0.3.1' );
		break;
	}
});

// Render Loans/Returns Page
function wp_lib_render_dashboard() {
	require_once( plugin_dir_path(__FILE__) . '/wp-librarian-dashboard-template.php' );
}


// Modifies title of Featured Image box on item edit page
add_action('admin_head-post-new.php', 'wp_lib_modify_image_box' );
add_action('admin_head-post.php', 'wp_lib_modify_image_box' );
function wp_lib_modify_image_box() {
	if ( $GLOBALS['post_type'] == 'wp_lib_items' ) {
		global $wp_meta_boxes;
		$wp_meta_boxes['wp_lib_items']['side']['low']['postimagediv']['title'] = 'Cover Image';
	}
}

// Flushes permalink rules to avoid 404s, used after plugin activation and any Settings change
function wp_lib_flush_permalinks() {
	// Registers custom post type
	wp_lib_register_post_and_tax();
	
	// Flushes permalinks so new ones work, e.g. mysite.com/wp-librarian/
	flush_rewrite_rules();
}

// Performs necessary checks before an item, loan or fine is deleted
function wp_lib_check_post_pre_trash( $post_id ) {
	if ( !$_POST['deletion_confirmed'] ) {
		switch ( $GLOBALS['post_type'] ) {
			case 'wp_lib_items':
				wp_redirect( wp_lib_format_dash_url(
					array(
						'dash_page' => 'object-deletion',
						'item_id'	=> $post_id,
						'obj_type'	=> 'item'
					)
				));
				exit;
			break;
			
			case 'wp_lib_loans':
				wp_redirect( wp_lib_format_dash_url(
					array(
						'dash_page' => 'object-deletion',
						'loan_id'	=> $post_id,
						'obj_type'	=> 'loan'
					)
				));
				exit;
			break;
			
			case 'wp_lib_fines':
				wp_redirect( wp_lib_format_dash_url(
					array(
						'dash_page' => 'object-deletion',
						'fine_id'	=> $post_id,
						'obj_type'	=> 'fine'
					)
				));
				exit;
			break;
		}
	} elseif ( $GLOBALS['post_type'] == 'wp_lib_items' ) {
		if ( wp_lib_on_loan( $post_id ) ) {
			wp_redirect( wp_lib_format_dash_url(
				array(
					'dash_page' => 'object-deletion',
					'item_id'	=> $post_id,
					'obj_type'	=> 'item'
				)
			));
		}
	}
}

// Sets up WP-Librarian on plugin activation
register_activation_hook( __FILE__, function() {
	// Flushes permalink rules so new URLs don't 404
	wp_lib_flush_permalinks();
	
	// Creates various options used by WP-Librarian
	require_once (plugin_dir_path(__FILE__) . '/wp-librarian-options.php');
});

// Removes traces of plugin on deactivation
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

// Adds row of metadata to taxonomy field
add_action('wp_lib_member_add_form_fields','wp_lib_member_add_form_fields');
add_action( 'wp_lib_member_edit_form_fields', 'wp_lib_member_edit_form_fields', 10, 2 );

// Clears taxonomy options on taxonomy term delete
add_action( 'delete_term_taxonomy', 'wp_lib_clear_tax_options', 1, 2 );

// Clears Description field on unwanted taxonomies
add_action( 'wp_lib_member_add_form_fields', 'wp_lib_no_tax_description' );
add_action( 'wp_lib_donor_add_form', 'wp_lib_no_tax_description' );
add_action( 'wp_lib_member_edit_form_fields', 'wp_lib_no_tax_edit_description' );
add_action( 'wp_lib_donor_edit_form', 'wp_lib_no_tax_edit_description' );

// Checks if item is on loan before it is moved to trash
add_action('before_delete_post', 'wp_lib_check_post_pre_trash');

function wp_lib_member_add_form_fields() {
?>
	<tr class="form-field">
		<th valign="top" scope="row">
			<label for="wp_lib_member_phone">Telephone Number</label>
		</th>
		<td>
			<input type="tel" id="wp_lib_member_phone" name="wp_lib_member_phone"/>
		</td>
	</tr>
	<tr class="form-field">
		<th valign="top" scope="row">
			<label for="wp_lib_member_mobile">Mobile Number</label>
		</th>
		<td>
			<input type="tel" id="wp_lib_member_mobile" name="wp_lib_member_mobile"/>
		</td>
	</tr>
<?php 
}

function wp_lib_member_edit_form_fields( $member ) {
	// Fetches member ID from member object
	$member_id = $member->term_id;
	
	// Uses member ID to fetch any existing meta
	// As taxonomies don't support meta, meta is stored as an option
	$meta_array = wp_lib_fetch_member_meta( $member_id );
?>
	<tr class="form-field">
		<th valign="top" scope="row">
			<label for="wp_lib_member_phone">Telephone Number</label>
		</th>
		<td>
			<input type="tel" id="wp_lib_member_phone" name="wp_lib_member_phone" value="<?php echo esc_attr( $meta_array['member_phone'] ) ? esc_attr( $meta_array['member_phone'] ) : ''; ?>"/>
		</td>
	</tr>
	<tr class="form-field">
		<th valign="top" scope="row">
			<label for="wp_lib_member_mobile">Mobile Number</label>
		</th>
		<td>
			<input type="tel" id="wp_lib_member_mobile" name="wp_lib_member_mobile" value="<?php echo esc_attr( $meta_array['member_mobile'] ) ? esc_attr( $meta_array['member_mobile'] ) : ''; ?>"/>
		</td>
	</tr>
<?php 
}

// Registers CSS Files
wp_register_style( 'wp_lib_template', plugins_url( '/css/templates.css', __FILE__ ), false, '0.1' );

/* Templates */

// Hooks templates to WordPress
add_filter( 'template_include', 'wp_lib_template', 10 );

// Checks for appropriate templates in the current theme and loads the plugin's default templates if that fails
function wp_lib_template( $template ) {
	if ( get_post_type() == 'wp_lib_items' ) {
		// If page is archive of multiple items
		if ( is_archive() ) {
			// Looks for template in current theme
			$theme_file = locate_template( array ( 'archive-wp_lib_items.php' ) );
			
			// If template is found, uses it
			if ($theme_file != ''){
				return $theme_file;
			}
			// Otherwise uses plugin's default template for archives
			else {
				return plugin_dir_path(__FILE__) . '/templates/archive-wp_lib_items.php';
			}
		}
		// If page is a single item
		elseif ( is_single() ) {
			// Looks for template in current theme
			$theme_file = locate_template( array ( 'single-wp_lib_items.php' ) );
			
			// If template is found, uses it
			if ($theme_file != ''){
				return $theme_file;
			}
			// Otherwise uses plugin's default template for single items
			else {
				return plugin_dir_path(__FILE__) . '/templates/single-wp_lib_items.php';
			}
		}
	}
	// If post is not a Library item
	return $template;
}

?>