<?php
/* 
 * WP-LIBRARIAN HELPERS
 * These are a collection of various useful functions used by WP-Librarian to operate
 */

	/* -- File Management -- */

// Loads file containing requested helper classes. Will handle failure less extremely after greater use of classes
function wp_lib_add_helper( $file_name ) {
	require_once( dirname( __FILE__ ) . '/helpers/' . $file_name . '.php' );
}

	/* -- Sanitising Functions -- */

// Sanitizes phone number
function wp_lib_sanitize_phone_number( $string ) {
	// Strips every character from the string that is not a number, a space, + or -
	return preg_replace('/[^0-9|^\+|^\s|^-]/', '', $string );
}

// Sanitizes input to
function wp_lib_sanitize_number( $raw ) {
	return ereg_replace('[^0-9]', '', $raw );
}

// Sanitizes item cover type
function wp_lib_sanitize_item_cover( $raw ) {
	if ( $raw == 'hardcover' )
		return 2;
	elseif ( $raw == 'softcover' )
		return 3;
	else
		return '';
}

// Sanitizes HTML POST data from a checkbox
function wp_lib_sanitize_checkbox( $raw ) {
	if ( $raw == 'true' )
		return true;
	else
		return false;
}

// Sanitizes HTML POST data from checkbox from options page
function wp_lib_sanitize_option_checkbox( $raw ) {
	return ( $raw === '3' ? 3 : 2 );
}

// Converts database checkbox value to boolean
function wp_lib_prep_boolean_option( $option ) {
	return ( $option === 3 ? true : false );
}

// Sanitizes string then checks if it is a valid ISBN, returns sanitized ISBN on success or empty string on failure.
function wp_lib_sanitize_isbn( $raw ) {
	// Strips all non-numeric characters, excluding x
	$isbn = ereg_replace('[^0-9.x]', '', strtolower( $raw ) );
	
	// Checks ISBN validity
	return wp_lib_valid_isbn( $isbn ) ? $isbn : '';
}

// Checks if given string is a valid ISBN, returns ISBN type (10/13) on success or false on failure
// Uses Cryptic's (http://stackoverflow.com/users/1592648) answer to a question on ISBN validation (http://stackoverflow.com/questions/14095778) on Stack Overflow
function wp_lib_valid_isbn( $isbn ) {
	// Sets check digit
	$check = 0;
	
	// Performs ISBN validity checks based on ISBN length
	switch ( strlen( $isbn ) ) {
		case 10:
			for ($i = 0; $i < 10; $i++) {
				if ('x' === $isbn[$i]) {
					$check += 10 * (10 - $i);
				} elseif (is_numeric($isbn[$i])) {
					$check += (int)$isbn[$i] * (10 - $i);
				} else {
					return false;
				}
			}

			return (0 === ($check % 11)) ? 10 : false;
		break;
		
		case 13:
			for ($i = 0; $i < 13; $i += 2) {
				$check += (int)$isbn[$i];
			}

			for ($i = 1; $i < 12; $i += 2) {
				$check += 3 * $isbn[$i];
			}

			return (0 === ($check % 10)) ? 13 : false;
		break;
	}
	// If string is not the length of a valid ISBN, return false
	return false;
}

// If member exists, return member ID, otherwise return empty string
function wp_lib_sanitize_donor( $member_id ) {
	return ( get_post_type( $member_id ) === 'wp_lib_members' ) ? $member_id : '';
}

	/* -- Data Validation Functions -- */

// Checks if ID belongs to valid Library item, returns object type if true
function wp_lib_get_object_type( $post_id ) {
	// Checks if item ID exists
	if ( !$post_id ) {
		wp_lib_error( 300, false, 'Library Object' );
		return false;
	}

	// Checks if ID is a number
	if ( !is_numeric( $post_id ) ) {
		wp_lib_error( 301, false, 'Library Object' );
		return false;
	}
	
	// Gets object's post type
	$post_type = get_post_type( $post_id );
	
	// Returns Library object type
	switch ( get_post_type( $post_id ) ) {
		case 'wp_lib_items':
			return 'item';
		break;
		
		case 'wp_lib_members':
			return 'member';
		break;
		
		case 'wp_lib_loans':
			return 'loan';
		break;
		
		case 'wp_lib_fines':
			return 'fine';
		break;
		
		default:
			// Otherwise object does not belong to the Library
			wp_lib_error( 317 );
			return false;
		break;
	}
}

	/* -- URLs and Slugs -- */

function wp_lib_prep_manage_item_params( $item_id ) {
	return array(
		'dash_page'	=> 'manage-item',
		'item_id'	=> $item_id
	);
}

function wp_lib_prep_manage_member_params( $member_id ) {
	return array(
		'dash_page'	=> 'manage-member',
		'member_id'	=> $member_id
	);
}

function wp_lib_prep_manage_loan_params( $loan_id ) {
	return array(
		'dash_page'	=> 'manage-loan',
		'loan_id'	=> $loan_id
	);
}

function wp_lib_prep_manage_fine_params( $fine_id ) {
	return array(
		'dash_page'	=> 'manage-fine',
		'fine_id'	=> $fine_id
	);
}

// Formats a URL to manage item with the given ID
function wp_lib_manage_item_url( $item_id ) {
	return wp_lib_format_dash_url( wp_lib_prep_manage_item_params( $item_id ) );
}

// Formats a URL to manage member with the given ID
function wp_lib_manage_member_url( $member_id ) {
	return wp_lib_format_dash_url( wp_lib_prep_manage_member_params( $member_id ) );
}

// Formats a URL to manage loan with the given ID
function wp_lib_manage_loan_url( $loan_id ) {
	return wp_lib_format_dash_url( wp_lib_prep_manage_loan_params( $loan_id ) );
}

// Formats a URL to manage fine with the given ID
function wp_lib_manage_fine_url( $fine_id ) {
	return wp_lib_format_dash_url( wp_lib_prep_manage_fine_params( $fine_id ) );
}

// Formats and returns a Library Dashboard URL with any desired variables formatted as GET parameters
function wp_lib_format_dash_url( $params = array() ) {
	// Constructs base Library Dashboard URL
	$url = admin_url( 'edit.php?post_type=wp_lib_items&page=dashboard' );
	
	// Adds any additional parameters to the base URL
	foreach ( $params as $key => $value ) {
		$url .= '&' . $key . '=' . $value;		
	}

	return $url;
}

// Formats script's URL using its name. Presumes default script dir is used
function wp_lib_script_url( $name ) {
	return plugins_url( '/scripts/'. $name . '.js', __FILE__ );
}

// Formats CSS file's URL using its name. Presumes default css dir is used
function wp_lib_style_url( $name ) {
	return plugins_url( '/styles/'. $name . '.css', __FILE__ );
}

// Returns item's title formatted as a hyperlink to manage that item
function wp_lib_manage_item_hyperlink( $item_id ) {
	return wp_lib_hyperlink( wp_lib_manage_item_url( $item_id ), get_the_title( $item_id ) );
}

// Returns member's name formatted as a hyperlink to manage that member
function wp_lib_manage_member_hyperlink( $member_id ) {
	return wp_lib_hyperlink( wp_lib_manage_member_url( $member_id ), get_the_title( $member_id ) );
}

// Creates a hyperlink given a url and some text
function wp_lib_hyperlink( $link, $text ) {
	return '<a href="' . $link . '">' . $text . '</a>';
}

// Creates dash URL element given a set of parameters
function wp_lib_prep_dash_hyperlink( $name, $params ) {
	return array(
		'type'	=> 'dash-url',
		'params'=> $params,
		'html'	=> $name
	);
}

// Creates Dash page element that appears as a hyperlink and dynamically loads item management page
function wp_lib_manage_item_dash_hyperlink( $item_id ) {
	return wp_lib_prep_dash_hyperlink(
		get_the_title( $item_id ),
		wp_lib_prep_manage_item_params( $item_id )
	);
}

// Creates Dash page element that appears as a hyperlink and dynamically loads member management page
function wp_lib_manage_member_dash_hyperlink( $member_id ) {
	return wp_lib_prep_dash_hyperlink(
		get_the_title( $member_id ),
		wp_lib_prep_manage_member_params( $member_id )
	);
}

// Creates Dash page element that appears as a hyperlink and dynamically loads loan management page
function wp_lib_manage_loan_dash_hyperlink( $loan_id ) {
	return wp_lib_prep_dash_hyperlink(
		'#' . $loan_id,
		wp_lib_prep_manage_loan_params( $loan_id )
	);
}

// Creates Dash page element that appears as a hyperlink and dynamically loads fine management page
function wp_lib_manage_fine_dash_hyperlink( $fine_id ) {
	return wp_lib_prep_dash_hyperlink(
		'#' . $fine_id,
		wp_lib_prep_manage_fine_params( $fine_id )
	);
}

// Creates URL for items archive
function wp_lib_item_archive_url() {
	return site_url( get_option( 'wp_lib_slugs', array('wp-librarian'))[0] );
}

	/* -- Dates and times -- */

// Validates given date, checking if it meets any given requirements
function wp_lib_convert_date( &$date ) {
	// Attempts to convert date into Unix timestamp
	$date = strtotime( $date );
}

// Fetches date from post meta and, if it exists, formats it
function wp_lib_prep_date_column( $post_id, $key ) {
	// Fetches date from post meta using given key
	$date = get_post_meta( $post_id, $key, true );
	
	// If date is valid returns formatted date
	if ( is_numeric( $date ) )
		return wp_lib_format_unix_timestamp( $date );
	// Otherwise return dash to indicate missing/unknown information
	else
		return '-';
}

function wp_lib_format_unix_timestamp( $timestamp ) {
	// If date is valid returns formatted date
	if ( is_numeric( $timestamp ) )
		return '<abbr title="' . date( 'Y/m/d g:i:s A', $timestamp ) . '">' . date( 'Y/m/d', $timestamp ) . '</abbr>';
	// Otherwise return dash to indicate missing/unknown information
	else
		return '-';
}

// If a date is false, sets to current date
function wp_lib_prep_date( &$date ) {
	if ( !$date )
		$date = current_time( 'timestamp' );
}

	/* Language */

// Makes string plural if needed, returns un-pluralised string otherwise
function wp_lib_plural( $value, $string, $plural = 's' ) {
	// If string does not need pluralising, return string with value only
	if ( $value == 1 || $value == -1 ) {
		$string = str_replace( '\v', $value, $string );
		$string = str_replace( '\p', '', $string );
	}
	// If string needs pluralising, replace \p with chosen plural ('s' by default)
	else {
		$string = str_replace( '\v', $value, $string );
		$string = str_replace( '\p', $plural, $string );
	}
	
	return $string;
}

	/* -- Localisation -- */

// Formats money according to user's preferences
function wp_lib_format_money( $value, $html_ent = true ) {
	// Fetches user's preferred currency symbol and currency position (symbol before or after number)
	$settings = get_option( 'wp_lib_currency', array('&pound;',2) );
	
	// Sets friendly variable names
	$symbol = $settings[0];
	$position = wp_lib_prep_boolean_option( $settings[1] );
	
	// If output doesn't need to use html entities (e.g. &pound; ), converts to actual characters (e.g. £ )
	if ( !$html_ent ) {
		$symbol = html_entity_decode( $symbol );
	}
	
	// Ensures number has correct number of decimal places
	$value = number_format( $value, 2 );
	
	// Formats $value with currency symbol at preferred position
	if ( $position )
		return $value . $symbol; // 0.40EUR
	else
		return $symbol . $value; // £0.40
}

	/* -- AJAX -- */
	/* Functions that assist preparing data for the client/server */


// Given item ID, returns header for management pages containing useful information on the item
function wp_lib_prep_item_meta_box( $item_id ) {
	// Fetches title of item e.g. 'Moby-Dick'
	$title = get_the_title( $item_id );
	
	// Fetches post meta
	$meta = get_post_meta( $item_id );
	
	// Item meta fields to be displayed in management header
	$meta_fields = array(
		array( 'Item ID', $item_id),
		array( 'Condition', wp_lib_format_item_condition( $meta['wp_lib_item_condition'][0] ) )
	);
	
	// If item has a donor and the ID matches an existing member
	if ( isset( $meta['wp_lib_item_donor'] ) && wp_lib_sanitize_donor( $meta['wp_lib_item_donor'][0] ) ) {
		// Adds meta field, displaying donor with a link to manage the member
		$meta_fields[] = array(
			'Donor',
			wp_lib_manage_member_dash_hyperlink( $meta['wp_lib_item_donor'][0] )
		);
	}
	
	// Taxonomy terms to be fetched
	$tax_terms = array(
		'Media Type'=> 'wp_lib_media_type',
		'Author'	=> 'wp_lib_author'
	);
	
	// Iterates through taxonomies, fetching their terms and adding them to the meta field array
	foreach ( $tax_terms as $tax_name => $tax_key ) {
		// Fetches terms for given taxonomy
		$terms = get_the_terms( $item_id, $tax_key );
		
		// If no terms or an error were returned, skip
		if ( !$terms || is_wp_error( $terms ) )
			continue;
			
		// Iterates through tax terms, formatting them
		foreach ( $terms as $term ) {
			// Adds tax term to term array
			$terms_array[] = array( $term->name, get_term_link( $term ) );
		}
		
		// Adds tax terms to meta fields
		$meta_fields[] = array( $tax_name, $terms_array );
		
		unset( $terms_array );
	}
	
	// Adds item status as last meta field
	$meta_fields[] = array( 'Status', wp_lib_prep_item_status( $item_id, true ) );
	
	// Finalises and returns management header
	return array(
		'type'		=> 'metabox',
		'title'		=> 'Details',
		'classes'	=> 'item-man',
		'fields'	=> $meta_fields
	);
}

// Given loan ID, returns header for management pages containing useful information on the loan
function wp_lib_prep_loan_meta_box( $loan_id ) {
	// Fetches loan meta
	$meta = get_post_meta( $loan_id );
	
	// Adds basic loan meta fields
	$meta_fields = array(
		array( 'Loan ID', $loan_id ),
		array( 'Item', wp_lib_manage_item_dash_hyperlink( $meta['wp_lib_item'][0] ) ),
		array( 'Member', wp_lib_manage_member_dash_hyperlink( $meta['wp_lib_member'][0] ) ),
		array( 'Expected Start', wp_lib_format_unix_timestamp( $meta['wp_lib_start_date'][0] ) ),
		array( 'Expected End', wp_lib_format_unix_timestamp( $meta['wp_lib_end_date'][0] ) ),
		array( 'Actual Start', ( isset( $meta['wp_lib_loaned_date'] ) ? wp_lib_format_unix_timestamp( $meta['wp_lib_loaned_date'][0] ) : 'N/A' ) ),
		array( 'Actual End', ( isset( $meta['wp_lib_returned_date'] ) ? wp_lib_format_unix_timestamp( $meta['wp_lib_returned_date'][0] ) : 'N/A' ) ),
		array( 'Status', wp_lib_format_loan_status( $meta['wp_lib_status'][0] ) )
	);
	
	// If fine was incurred for loan, fetch details and add to metabox
	if ( isset( $meta['wp_lib_fine'] ) ) {
		$meta_fields[] = array( 'Fine ID', wp_lib_prep_dash_hyperlink( $meta['wp_lib_fine'][0], wp_lib_prep_manage_fine_params( $meta['wp_lib_fine'][0] ) ) );
	}
	
	// Finalises and returns management header
	return array(
		'type'		=> 'metabox',
		'title'		=> 'Details',
		'classes'	=> 'loan-man',
		'fields'	=> $meta_fields
	);
}

// Given fine ID, returns header for management pages containing useful information on the fine
function wp_lib_prep_fine_meta_box( $fine_id ) {
	$meta = get_post_meta( $fine_id );
	
	// Creates and returns fine management header
	return array(
		'type'		=> 'metabox',
		'title'		=> 'Details',
		'classes'	=> 'fine-man',
		'fields'	=> array(
			array( 'Fine ID', $fine_id ),
			array( 'Loan ID', wp_lib_prep_dash_hyperlink( $meta['wp_lib_loan'][0], wp_lib_prep_manage_loan_params( $meta['wp_lib_loan'][0] ) ) ),
			array( 'Item', wp_lib_manage_item_dash_hyperlink( $meta['wp_lib_item'][0] ) ),
			array( 'Member', wp_lib_manage_member_dash_hyperlink( $meta['wp_lib_member'][0] ) ),
			array( 'Amount', wp_lib_format_money( $meta['wp_lib_fine'][0] ) ),
			array( 'Status', wp_lib_format_fine_status( $meta['wp_lib_status'][0] ) ),
			array( 'Created', get_the_date( '', $fine_id ) )
		)
	);
}

// Given member ID, returns header for management pages containing useful information on the member
function wp_lib_prep_member_meta_box( $member_id ) {
	// Fetches member meta
	$meta = get_post_meta( $member_id );
	
	// Sets up header's meta fields
	$meta_fields = array(
		array( 'Member ID', $member_id ),
		array( 'Email', $meta['wp_lib_member_email'][0] ),
		array( 'Phone', $meta['wp_lib_member_phone'][0] ),
		array( 'Mobile', $meta['wp_lib_member_mobile'][0] ),
		array( 'Owed', wp_lib_format_money( wp_lib_fetch_member_owed( $member_id ) ) ),
		array( 'On Loan', wp_lib_prep_members_items_out( $member_id ) )
	);
	
	// Finalises and returns management header
	return array(
		'type'		=> 'metabox',
		'title'		=> 'Details',
		'classes'	=> 'member-man',
		'fields'	=> $meta_fields
	);
}

// Creates option element for each member in the library, stored as an array
function wp_lib_prep_member_options( $default_option = true ) {
	// Initialises options
	$option = array();
	
	// Adds default option, if specified
	if ( $default_option ) {
		$options[] = array(
			'value'	=> '',
			'html'	=> 'Member'
		);
	}
	
	$args = array(
		'post_type'			=> 'wp_lib_members',
		'post_status'		=> 'publish'
	);
	
	// Fetches all, if any, members
	$query = NEW WP_Query( $args );
	
	// Checks for any loans attached to member
	if ( $query->have_posts() ){
		// Iterates through loans
		while ( $query->have_posts() ) {
			$query->the_post();
			
			// Fetches member ID
			$member_id = get_the_ID();
			
			// Skips displaying member if member has been archived
			if ( get_post_meta( $member_id, 'wp_lib_member_archive', true ) )
				continue;
			
			// Adds member's details to the options array
			$options[] = array(
				'value'	=> get_the_ID(),
				'html'	=> get_the_title()
			);
		}
	}
	
	return $options;
}

// Returns the number of items currently on loan by the member
function wp_lib_prep_members_items_out( $member_id ) {
	// Sets up meta query arguments
	$args = array(
		'post_type'		=> 'wp_lib_items',
		'post_status'	=> 'publish',
		'meta_query'	=> array(
			array(
				'key'		=> 'wp_lib_member',
				'value'		=> $member_id,
				'compare'	=> 'IN'
			)
		)
	);
	
	// Queries post table for all items marked as currently in member's possession
	$query = NEW WP_Query( $args );

	// Returns number of items to post table
	return $query->post_count;
}

// Displays list of loans associated with an item
function wp_lib_prep_loans_table( $item_id ) {
	// Sets up loan history query arguments
	$args = array(
		'post_type' 	=> 'wp_lib_loans',
		'post_status'	=> 'publish',
		'meta_query'	=> array(
			array(
				'key'		=> 'wp_lib_item',
				'value'		=> $item_id,
				'compare'	=> 'IN'
			)
		)
	);
	
	// Creates query of all loans of this item
	$loan_query = new WP_Query( $args );
	
	// Checks for any loans in query
	if ( $loan_query->have_posts() ){
		// Initialises loans array
		$loans = array();
		
		// Iterates through loans
		while ( $loan_query->have_posts() ) {
			// Selects current post (loan)
			$loan_query->the_post();
			
			// Fetches loan ID
			$loan_id = get_the_ID();
			
			// Fetches all loan's meta
			$meta = get_post_meta( $loan_id );
			
			$loan_status = wp_lib_format_loan_status( $meta['wp_lib_status'][0] );
			
			// If loan incurred fine
			if ( $meta['wp_lib_status'][0] === '4' ) {
				// Loan status will act as a hyperlink to manage the fine
				$loan_status = wp_lib_prep_dash_hyperlink( wp_lib_format_loan_status( $meta['wp_lib_status'][0] ), wp_lib_prep_manage_fine_params( $meta['wp_lib_fine'][0] ) );
			} else {
				// Loan status displays as formatted string e.g. 'Returned'
				$loan_status = wp_lib_format_loan_status( $meta['wp_lib_status'][0] );
			}
			
			$loans[] = array(
				'loan'		=> wp_lib_manage_loan_dash_hyperlink( $loan_id ),
				'member'	=> wp_lib_manage_member_dash_hyperlink( $meta['wp_lib_member'][0] ),
				'status'	=> $loan_status,
				'loaned'	=> wp_lib_format_unix_timestamp( ( isset( $meta['wp_lib_loaned_date'] ) ? $meta['wp_lib_loaned_date'][0] : $meta['wp_lib_start_date'][0] ) ), // Uses loaned date if loan has one, otherwise uses expected start date
				'expected'	=> wp_lib_format_unix_timestamp( $meta['wp_lib_end_date'][0] ),
				'returned'	=> wp_lib_format_unix_timestamp( $meta['wp_lib_returned_date'][0] )
			);
		}
		
		// Adds loans (rows) to table
		$table = array(
			'type'		=> 'dtable',
			'id'		=> 'member-loans',
			'headers'	=> array(
				'Loan',
				'Member',
				'Status',
				'Loaned',
				'Expected',
				'Returned'
			),
			'data'		=> $loans,
			'labels'	=> array(
				'records'	=> 'loans'
			)
		);
	} else {
		$table = array(
			'type'		=> 'paras',
			'content'	=> array( 'No loans to display' )
		);
	}
	
	return $table;
}

	/* -- Debugging -- */

// Renders current plugin's version, update channel and similar information
function wp_lib_render_plugin_version() {
	// Fetches plugin's current version/update channel/build
	$version = get_option( 'wp_lib_version' );
	
	?>
	<div id="version-wrap">
		<span>
			<?php echo 'Running WP-Librarian ' .  $version['channel']; ?>
		</span>
		<span>
			<?php echo 'Version: ' . $version['version'] . ' (' . $version['nickname'] . ') Build: ' . $version['subversion']; ?>
		</span>
		<?php
			if ( WP_LIB_DEBUG_MODE === true ) {
				?>
					<span>
						<?php echo 'DEBUGGING MODE ON'; ?>
					</span>
				<?php
			}
		?>
	</div>
	<?php
}

// Dumps any number of given variables between <pre> tags
function wp_lib_var_dump() {
	// Gets all given params
	$args = func_get_args();
	
	// Wraps all dumped variables in a div
	echo '<div class="wp-lib-debug-wrap">';
	
	// For each param, var_dump between <pre> tags to format code properly in browser
	foreach ( $args as $arg ){
		echo '<pre style="background:white;">';
		var_dump( $arg );
		echo '</pre>';
	}
	
	echo '</div>';
}

	/* -- Statuses -- */

// Turns numeric loan status into readable string e.g. 1 -> 'On Loan'
function wp_lib_format_loan_status( $status ) {
	// Array of all possible states of the loan
	$strings = array(
		0	=> '',
		1	=> 'On Loan',
		2	=> 'Returned',
		3	=> 'Returned Late',
		4	=> 'Returned Late (with fine)',
		5	=> 'Scheduled'
	);
	
	// If given number refers to a status that doesn't exist, throw error
	if ( empty( $strings[$status] ) )
		wp_lib_error( 201, true, 'Loan' );
	
	// State is looked up in the array and returned
	return $strings[$status];
}

// Turns numeric fine status into readable string e.g. 1 -> 'Unpaid'
function wp_lib_format_fine_status( $status ) {
	// Array of all possible states of the fine
	$strings = array(
		0	=> '',
		1	=> 'Active',
		2	=> 'Cancelled'
	);
	
	// If given number refers to a status that doesn't exist, throw error
	if ( empty( $strings[$status] ) )
		wp_lib_error( 201, true, 'Fine' );
	
	// State is looked up in the array and returned
	return $strings[$status];
}

// Fetches list of all possible user roles within the plugin
function wp_lib_fetch_user_roles() {
	return array(
		0	=> '',
		1	=> '',
		5	=> 'Librarian',
		10	=> 'Administrator'
	);
}

// Turns numeric user status into readable string e.g. 5 -> Librarian
function wp_lib_format_user_permission_status( $status ) {
	// Array of all possible user permissions
	$strings = wp_lib_fetch_user_roles();
	
	// If given number refers to a status that doesn't exist, throw error
	if ( !array_key_exists( $status, $strings ) )
		wp_lib_error( 201, true, 'User' );
	
	// State is looked up in the array and returned
	return $strings[$status];
}

// Fetches and formats user permission status
function wp_lib_fetch_user_permission_status( $user_id ) {
	// Fetches status
	$status = get_user_meta( $user_id, 'wp_lib_role', true );
	
	if ( !$status )
		return '';
	else
		return wp_lib_format_user_permission_status( $status );
}

// Returns formatted item condition given item number
function wp_lib_format_item_condition( $number, $full = true ) {
	// All possible conditions item can be in
	$states = array(
		4 => 'Excellent',
		3 => 'Good',
		2 => 'Fair',
		1 => 'Poor',
		0 => 'Very Poor'
	);
	
	// If item has not been given a state, return placeholder
	if ( !array_key_exists( $number, $states ) )
		return '-';
	
	if ( $full )
		return $number . ' - ' . $states[$number];
	else
		return $states[$number];	
}

	/* -- Miscellaneous -- */

// Fetches amount member owes Library in fines
function wp_lib_fetch_member_owed( $member_id ) {
	// Fetches total money owed by member to Library
	$owed = get_post_meta( $member_id, 'wp_lib_owed', true );
	
	// If blank, assumes nothing is owed
	if ( $owed == '' )
		$owed = 0;
	
	return $owed;
}

// Fetches member's name given a connected Library object (item/loan/fine)
function wp_lib_fetch_member_name( $post_id, $hyperlink = false ) {
	// Fetches member ID from given object's meta
	$member_id = get_post_meta( $post_id, 'wp_lib_member', true );
	
	// Fetches Member's name
	$member_name = get_the_title( $member_id );
	
	if ( $hyperlink ) {
		return wp_lib_hyperlink( wp_lib_manage_member_url( $member_id ), $member_name );
	} else {
		return $member_name;
	}
}

// Cancels loan of item that has a since corrupted loan attached to it
// This function should not be called under regular operation and should definitely not used to return an item
function wp_lib_clean_item( $item_id ){
	// Checks if given ID is valid
	wp_lib_check_item_id( $item_id );
	
	$loan_id = get_post_meta( $item_id, 'wp_lib_loan', true );
	$member_id = get_post_meta( $item_id, 'wp_lib_member', true );

	// Deletes loan related meta from item's meta
	delete_post_meta( $item_id, 'wp_lib_loan' );
	
	if ( $member_id ) {
		delete_post_meta( $item_id, 'wp_lib_member' );
		wp_lib_add_notification( 'Member ' . get_the_title( $member_id ) . ' has been removed from item' );
	}
	
	if ( $loan_id ) {
		wp_lib_add_notification( 'Loan ' . $loan_id . ' has been removed from item' );
	}
	
	return true;
}

// Updates multiple meta values of a post
function wp_lib_update_meta( $post_id, $meta_array ) {
	foreach ( $meta_array as $key => $value ) {
		update_post_meta( $post_id, $key, $value );
	}
}

// Adds multiple meta values of a post
function wp_lib_add_meta( $post_id, $meta_array ) {
	foreach ( $meta_array as $key => $value ) {
		add_post_meta( $post_id, $key, $value );
	}
}

// Fetches post's meta and then fetches the desired meta values from the meta array
function wp_lib_prep_admin_meta( $post_id, $formatting ) {
	// Fetches all post meta
	$all_meta = get_post_meta( $post_id );
	
	// Initialises output
	$meta = array();

	// Iterates through meta formatting and fetches needed meta values for all item meta
	foreach ( $formatting as $meta_area ) {
		foreach ( $meta_area['fields'] as $field ) {
			$meta[$field['name']] = $all_meta[ $field['name'] ][0];
		}
	}
	
	// Returns prepared meta
	return $meta;
}

// Returns "Available" or "Unavailable" string depending on if item if available to loan
function wp_lib_prep_item_status( $item_id, $no_url = false, $short = false ) {
	// Checks if the current user was the permissions of a Librarian
	$is_librarian = wp_lib_is_librarian();
	
	// Fetches if item is currently on loan
	$on_loan = wp_lib_on_loan( $item_id );
	
	// If item can be loaned and is available, url is made to take user to loans Dashboard to loan item
	if ( wp_lib_loan_allowed( $item_id ) && !$on_loan )
		$status = 'Available';
	
	// If item is on loan link is composed to return item
	elseif ( $on_loan ) {
		// Sets item status accordingly
		$status = 'On Loan';
		
		// Checks if user has permission to see full details of current loan
		if ( $is_librarian ) {
			// If user wants full item status, member that item is loaned to is fetched
			if ( !$short ) {
				$status .= ' to ' . wp_lib_fetch_member_name( $item_id );
			}
			$args = array(
			'due'	=> 'due in \d day\p',
			'today'	=> 'due today',
			'late'	=> '\d day\p late',
			);
			$status .= ' (' . wp_lib_prep_item_due( $item_id, false, $args ) . ')';
		}
	}
	
	// If item isn't allowed to be loaned item is marked as unavailable
	else {
		$use_url = false;
		$status = 'Unavailable';
	}
	
	// If user has the relevant permissions, availability will contain link to manage item
	if ( $is_librarian && !$no_url ) {
		return wp_lib_hyperlink( wp_lib_manage_item_url( $item_id ), $status );
	}
	
	// String is concatenated and returned
	return $status;
}

// Renders item's tax terms and public meta to the page
function wp_lib_display_item_meta( $item_id, $item_permalink = true ) {
	// Fetches default taxonomy spacer
	$spacer = get_option( 'wp_lib_taxonomy_spacer', array(', ') )[0];
	
	// If user is librarian (or higher), or if the donor is set to be displayed, fetches item donor
	// If user isn't a librarian, or there is no listed donor, returns false
	$donor_id = ( wp_lib_is_librarian() || get_post_meta( $item_id, 'wp_lib_display_donor', true ) ? get_post_meta( $item_id, 'wp_lib_item_donor', true ) : false );
	
	// If donor ID belongs to a valid donor, fetch donor's name
	$donor = ( is_numeric( $donor_id ) ? get_the_title( $donor_id ) : false );
	
	// Creates array of raw item meta
	$raw_meta = array(
		array( 'Title',		get_the_title( $item_id )),
		array( 'Media Type',get_the_terms( $item_id, 'wp_lib_media_type' )),
		array( 'Author',	get_the_terms( $item_id, 'wp_lib_author' )),
		array( 'Donor',		$donor ),
		array( 'ISBN',		get_post_meta( $item_id, 'wp_lib_item_isbn', true )),
		array( 'Status',	wp_lib_prep_item_status( $item_id ))
	);
	
	// If item title should be a link to manage the item
	if ( $item_permalink )
		$raw_meta[0][2] = get_permalink( $item_id );
	
	// Initialises formatted meta output
	$meta_output = array();
	
	// Iterates over raw taxonomy 
	foreach ( $raw_meta as $key => $meta ) {
		// If meta value is a tax term
		if ( is_array( $meta[1] ) ) {
			// Initilises output for tax terms
			$tax_terms_output = array();
			
			// Iterates through tax terms
			foreach ( $meta[1] as $tax_key => $tax_term ) {
				// Gets tax term's URL
				$tax_url = get_term_link( $tax_term );
				
				// Deletes term if error occurred
				if ( is_wp_error( $tax_url ) )
					continue;
				
				// Formats tax item as link
				$tax_terms_output[] = '<a href="' . esc_url( $tax_url ) . '">' . $tax_term->name . '</a>';
			}
			
			// Overwrites tax term objects with formatted tax terms
			$meta[1] = $tax_terms_output;
			
			// Counts number of valid tax terms
			$count = count( $meta[1] );
			
			// If all tax terms were invalid, remove meta value
			if ( $count === 0 ) {
				unset( $tax_array[$key] );
				continue;
			// If there is one than one of a taxonomy item it makes the term plural (Author -> Authors)
			} elseif ( $count > 1 ) {
				$meta[0] .= 's';
			}
			
			// Implodes array into string separated by users preferred spacer
			$meta[1] = implode( $spacer, $meta[1] );
		}
		
		// If output is a string with a URL, create hyperlink
		if ( isset( $meta[2] ) )
			$meta[1] = '<a href="' . $meta[2] . '">' . $meta[1] . '</a>';
		
		// If meta output is valid, add to output
		if ( $meta[1] !== false && $meta[1] !== '' )
			$meta_output[] = $meta;
	}
	
	// If there are any remaining valid meta fields
	if ( count( $meta_output ) > 0 ) {
		// Renders description list
		echo '<table class="item-metabox"><tbody>';
		
		// Iterates over meta fields, rendering them to details list
		foreach ( $meta_output as $meta_field ) {
			echo '<tr class="meta-row"><th>' . $meta_field[0] . ':</th><td>' . $meta_field[1] . '</td></tr>';
		}
		
		// Ends Description list
		echo '</tbody></table>';
	}
}

// Renders notification to page, to send notification to cl
function wp_lib_add_notification_on_load( $text ) {
	?>
	<script type="text/javascript">
		jQuery(function($){
			wp_lib_local_notification( <?php echo json_encode( $text ); ?> );
		});
	</script>
	<?php
}

// Recursively searches for any Library objects connected directly or indirectly to a given object. Uses depth first searching
function wp_lib_fetch_dependant_objects( $post_id, $post_type = false, $connected_posts = array() ) {
	// If post type has not been given, fetches
	if ( !$post_type )
		$post_type = get_post_type( $post_id );
	
	// If post type requires post query
	if ( $post_type == 'wp_lib_items' || $post_type == 'wp_lib_members' ) {
		// Sets meta key to use in search
		switch ( $post_type ) {
			case 'wp_lib_items':
				$key = 'wp_lib_item';
			break;
			
			case 'wp_lib_members':
				$key = 'wp_lib_member';
			break;
		}
		
		// Sets query args
		$args = array(
			'post_type'		=> 'wp_lib_loans',
			'post_status'	=> 'publish',
			'meta_query'	=> array(
				array(
					'key'		=> $key,
					'value'		=> $post_id,
					'compare'	=> 'IN'
				)
			)
		);
		
		// Queries for connected posts
		$query = NEW WP_Query( $args );
		
		// If any connected loans are found, iterates through them adding them to list then searching the loans themselves
		if ( $query->have_posts() ) {
			while( $query->have_posts() ) {
				$query->the_post();
				
				// Fetches loan's ID
				$loan_id = get_the_ID();
				
				// Adds loan to connected posts list
				$connected_posts[] = array( $loan_id, get_post_type( $loan_id ) );
				
				// Calls function to check loan for connected objects
				$connected_posts = wp_lib_fetch_dependant_objects( $loan_id, 'wp_lib_loans', $connected_posts );
			}
		}
	} elseif ( $post_type == 'wp_lib_loans' ) {
		// Fetches fine ID from loan meta
		$fine_id = get_post_meta( $post_id, 'wp_lib_fine', true );
		
		// If fine ID was found, add to connected posts list
		if ( $fine_id )
			$connected_posts[] = array( $fine_id, get_post_type( $fine_id ) );
	} elseif ( $post_type == 'wp_lib_fines' ) {
		// Fetches loan ID from fine meta
		$loan_id = get_post_meta( $post_id, 'wp_lib_loan', true );
		
		// Checks if loan is already in the array
		if ( !in_array( $loan_id, $connected_posts ) )
			$connected_posts[] = array( $loan_id, get_post_type( $loan_id ) );
	}
	
	return $connected_posts;
}

// Creates a nonce for use on Dashboard pages
function wp_lib_prep_nonce( $action ) {
	// Creates nonce
	$nonce = wp_create_nonce( $action );
	
	// Builds and returns form field
	return array(
		'type'	=> 'nonce',
		'value'	=> $nonce
	);
}
?>