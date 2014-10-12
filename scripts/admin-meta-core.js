jQuery( document ).ready(function($) {
	// Fetches existing item meta and meta fields
	var meta = JSON.parse( document.getElementById( 'meta-raw' ).innerHTML );
	var metaFormat = JSON.parse( document.getElementById( 'meta-formatting' ).innerHTML );
	
	// Selects meta box to be filled with meta sections and fields
	var metaBox = $( '#item-meta' );

	// Iterates over each meta section (basic details/book details/etc.) rendering its meta fields
	metaFormat.forEach( function( metaSection ) {
		// Sets up basic object properties
		metaWrapperArgs = wp_lib_init_object( metaSection );
		
		// Sets meta section class
		metaWrapperArgs.class += wp_lib_add_classes( 'meta-section' );
		
		// If section is specific to item's media type, set class for later use (hiding section)
		if ( metaSection.value ) {
			metaWrapperArgs.class += wp_lib_add_classes( 'meta-media-type-section' );
		}
		
		// Creates wrapper for meta section
		var metaWrapper = $('<div/>', metaWrapperArgs ).appendTo( metaBox );

		// Renders section title inside of a div
		$('<div/>', {
			'class'	: 'meta-section-title'
		})
		.html(
			$('<h3/>', {
				'text'	: metaSection.title
			})
		)
		.appendTo( metaWrapper );
		
		// Renders then selects fields wrapper
		var metaFieldsTable = $('<table/>', {
			'class'	: 'meta-section-fields'
		})
		.appendTo( metaWrapper );
		
		// Iterates over each meta section's fields, rendering to the section wrapper
		metaSection.fields.forEach( function( metaField ) {
			// Fetches previous meta value from meta array
			var currentMeta = meta[metaField.name];
		
			// Creates row that will contain field and adds field title to row
			var metaRow = $('<tr/>', {
				'class'	: 'meta-field-row'
			})
			.append(
				$('<td/>', {
					'text'	: metaField.title,
					'class'	: 'meta-field-title'
				})
			)
			.appendTo( metaFieldsTable );
			
			// Creates field input wrapper
			var metaInputWrapper = $('<td/>', {
				'class'	: 'meta-input-wrapper'
			})
			.appendTo( metaRow );
			
			// If field is a dropdown menu (select), renders with options
			if ( metaField.type === 'select' ) {
				// Initialises select element's properties
				var selectArgs = {
					'class'	: 'meta-select',
					'name'	: metaField.name
				};
				
				// If field has an ID, enter it
				if ( metaField.id ) {
					selectArgs.id = metaField.id;
				}
				
				// Creates select element then selects it
				var metaSelect = $('<select/>', selectArgs ).appendTo( metaInputWrapper );
				
				// Adds default blank option
				$('<option/>', {
					'value'	: '',
					'text'	: 'Select'
				})
				.appendTo( metaSelect );
				
				// Iterates through select field's options, rendering them
				metaField.options.forEach( function( option ) {
					// Initialises option's properties
					var optionObject = {
						'value'	: option.value,
						'text'	: option.text
					};
					
					// If option is the current value, pre-select as that option
					if ( option.value == currentMeta ) {
						optionObject.selected = 'selected';
					}
					
					// Creates option and adds to to select field's options
					$('<option/>', optionObject).appendTo( metaSelect );
				});
			} else {
				// Initialises field input object
				var inputArgs = wp_lib_init_object( metaField );
				inputArgs.type = metaField.type;
				
				// Switch to build field's input element
				switch ( metaField.type ) {
					case 'checkbox':
						if ( currentMeta ) {
							inputArgs.checked = 'checked';
						}
						
						inputArgs.value = 'true';
					break;
					
					default:
						inputArgs.value = currentMeta;
					break;
				}
				$('<input/>', inputArgs).appendTo( metaInputWrapper );
			}
		});
	});
	
	// Checks for post type specific code to run after the meta-box has been rendered
	if (typeof wp_lib_post_render === "function" ) {
		wp_lib_post_render($);
	}
});