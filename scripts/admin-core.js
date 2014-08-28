var notificationCount = 0;

// Adds notification to client-side buffer
function wp_lib_add_notification( array ) {
	if (typeof window.wp_lib_notification_buffer === 'undefined') {
		window.wp_lib_notification_buffer = [];
	}
	window.wp_lib_notification_buffer.push( array );
}

// Collects of a submitted form's parameters, removes the blank ones, then returns the result as an object
function wp_lib_collect_form_params( selector ) {

	// Fetches all form elements and creates an array of objects
	var objects = jQuery( selector ).serializeArray();
	
	// Initialises results object
	var result = {};
	
	// Iterates through each object, if the value is set, it is added to the results
	objects.forEach(function(object) {
		if ( object.value ) {
			result[object.name] = object.value;
		}
	});

	return result;
}

// Formats a notification with appropriate tags to utilise WordPress and Plugin CSS
function wp_lib_format_notification( notification ) {
	// Creates unique ID for notification (to keep track of it)
	var uID = 'wp_lib_nid_' + notificationCount++;
	
	// Initialises variables
	var classes = uID + ' ';
	var message = '';
	var onClick = " onclick='wp_lib_hide_notification(this)";
	
	// If notification has no error code (defaulted to 0 )
	if ( notification[0] == 0 ) {
		// Uses notification classes, which displays a green flared box
		classes += 'wp-lib-notification updated';
		message = notification[1];
	} else if ( notification[0] == 1 ) {
		// Uses error classes, which displays a red flared box
		classes += 'wp-lib-error error';
		message = "<strong style='color: red;'>WP-Librarian Error: " + notification[1] + "</strong>";
	} else {
		// Uses error classes, which displays a red flared box
		classes += 'wp-lib-error error';
		message = "<strong style='color: red;'>WP-Librarian Error " + notification[0] + ": " + notification[1] + "</strong>";
	}
	
	// Returns HTML formatted notification
	return [ uID, "<div onclick='wp_lib_hide_notification(this)' class='" + classes + "'><p>" + message + "</p></div>" ];
}

// Fetches and displays any notifications waiting in the buffer
function wp_lib_display_notifications() {
	// Initialises client-side notifications array
	var notifications = [];
	
	// Fetches client-side notifications from global buffer
	if (typeof window.wp_lib_notification_buffer != 'undefined') {
		notifications = window.wp_lib_notification_buffer;
		delete window.wp_lib_notification_buffer;
	}

	// Selects div that holds notifications
	var holder = jQuery( '#notifications-holder' );
	
	// Clears previous notifications
	holder.empty();
	
	// Checks server for notifications
	jQuery.post( ajaxurl, { 'action' : 'wp_lib_fetch_notifications' } )
	.done( function( response ) {
		// Parses response
		var serverNotifications = JSON.parse( response );
		
		// If there are any server-side notifications, merge them into client-side notifications
		if ( jQuery.isArray( serverNotifications ) ) {
			notifications = notifications.concat( serverNotifications );
		}
	})
	.always( function() {
		// Iterates through notifications, rendering them to the notification holder
		notifications.forEach(function(notificationText){
			// Formats notification inside div and gives notification ID (to keep track of it)
			var result = wp_lib_format_notification( notificationText );
			
			// Adds notification to the notification holder
			holder.append( result[1] ).hide().fadeIn( 500 );
			
			// Selects the notification using its ID
			var notification = jQuery( '.' + result[0] );
			
			// Sets notification to fade away after 5 seconds then get deleted
			setTimeout(function(){
				wp_lib_hide_notification( notification );
			}, 6000)
			
		});
	});
}

// Hides then deletes notification
function wp_lib_hide_notification(element) {
	var notification = jQuery( element );
	
	notification.fadeOut("slow");
	
	delete notification;
}

// Wrapper for wp_lib_add_notification
function wp_lib_add_error( error ) {
	wp_lib_add_notification( [ 1, error ] );
}

















