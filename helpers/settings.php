<?php
$section = array(
	'name'		=> 'wp_lib_library_group',
	'title'		=> 'General Settings',
	'callback'	=> false,
	'page'		=> 'wp_lib_library_group-options',
	'settings'	=> array(
		array(
			'name'			=> 'wp_lib_slugs',
			'sanitize'		=> function($raw){/*Function that sanitizes settings' fields */},
			'field_type'	=> 'textField', // Field callback can be defined at setting or field level
			'fields'		=> array(
				array(
					'name'			=> 'Main Slug',
					'args'			=> array(
						'filter'	=> function( $raw ) {}, // Filters input before field is rendered
						'position'	=> 0, // Position of field value in options array
						'alt'		=> 'This forms the base of all public Library pages',
						'class'		=> 'main-slug'
					)
				)
			)
		),
	)
);
class WP_LIB_SETTINGS {
	function __construct( $section ) {
		// If header to render section description is not provided, passes dummy callback
		if ( !isset( $section['callback'] ) )
			$section['callback'] = false;
		
		// Registers setting section
		add_settings_section( $section['name'], $section['title'], $section['callback'], $section['page'] );
		
		// Iterates over settings, generating fields for each settings' fields 
		foreach( $section['settings'] as $setting ) {
			// Registers setting section's page
			// Uses sanitization callback specific to current setting, if one exists, defaults to section's sanitization callback
			register_setting( $section['name'], $setting['name'], ( isset( $setting['sanitize'] ) ? $setting['sanitize'] : $section['sanitize'] ) );
			
			// If undefined, initialises setting level classes
			if ( !isset( $setting['classes'] ) )
				$setting['classes'] = array();
			
			// Iterates over setting's fields, registering them
			foreach ( $setting['fields'] as $position => $field ) {
				// Initialises field args, if necessary
				if ( !isset( $field['args'] ) )
					$field['args'] = array();
				
				// Adds setting name to arguments to be passed to field rendering callback
				$field['args']['setting_name'] = $setting['name'];
				
				// Iterates over field parameters which can be defined at a setting or field level, applying setting level prams to fields
				// Setting level param will only be inherited if the field hasn't its own specified param
				foreach ( ['field_type'] as $param ) {
					if ( !isset($field[$param]) && isset( $setting[$param] ) )
						$field[$param] = $setting[$param];
				}
				
				// Iterates over field args which can be defined at a setting or field level, applying setting level prams to fields
				foreach ( ['html_filter'] as $arg ) {
					if ( isset( $setting[$arg] ) )
						$field['args'][$arg] = $setting[$arg];
				}
				
				// If undefined, initialises field level classes
				if ( !isset( $field['args']['classes'] ) )
					$field['args']['classes'] = array();
				
				// Merges parent (setting) classes into child (field)
				$field['args']['classes'] = array_merge( $field['args']['classes'], $setting['classes'] );
				
				// Prepares callback to render field for use by WordPress by adding class name
				$field['field_type'] = array( $this, $field['field_type'] );
				
				// Adds field position in setting array to field args
				$field['args']['position'] = $position;
				
				// Registers setting field to setting
				// Passes callback to render field using specific callback for this field, if one exists. Falls back to setting's field rendering callback
				add_settings_field( $setting['name'] . '[' . $position . ']' , $field['name'], $field['field_type'], $section['page'], $section['name'], $field['args'] );
			}
		}
	}
	
	private function addDescription( &$output, $args ) {
		if ( isset( $args['alt'] ) )
			$output[] = '<p class="tooltip description">' . $args['alt'] . '</p>';
	}
	
	// Fetches option and applies any given filters to it
	private function getOption( $args ) {
		// Fetches option value from database. Uses false if option does not exist
		$option = get_option( $args['setting_name'], false );
		
		// If option does not exist or is invalid, call error. This will be handled better in future
		if ( !is_array( $option ) )
			wp_lib_error( 114, true );
		
		// Fetches field value from option array
		if ( isset( $option[$args['position']] ) )
			$option = $option[$args['position']];
		else
			wp_lib_error( 115, true );
		
		// If filter exists, to prep the option for field display, filters
		if ( isset( $args['filter'] ) )
			$option = $args['filter']( $option, $args );
		
		return $option;
	}
	
	// Sets up field's element's properties
	private function setupFieldProperties( $args, $add_prop ) {
		// Merges given field classes with default field classes
		$classes = array_merge(
			$args['classes'],
			array(
				'setting-' . $args['setting_name']
			)
		);
		
		// Merges additional field properties with default field properties
		$prop_array = array_merge(
			$add_prop,
			array(
				'class'	=> implode( ' ', $classes ),
				'name'	=> $args['setting_name'] . '[' . $args['position'] . ']',
				'id'	=> $args['setting_name'] . '[' . $args['position'] . ']'
			)
		);
		
		// Initialises element properties
		$properties = '';
		
		// Iterates over properties, formatting html element properties as a string
		foreach( $prop_array as $key => $value ) {
			$properties .= $key . '="' . $value . '" ';
		}
		
		return $properties;
	}
	
	public function textInput( $args ) {
		$properties = array(
			'type' => 'text',
			'value'=> $this->getOption( $args )
		);
		
		// Sets field output
		$output = array(
			'<input ' . $this->setupFieldProperties( $args, $properties ) . '/>'
		);
		
		// Adds field description, if one exists
		$this->addDescription( $output, $args );
		
		// If hook exists to add html elements to the output, apply
		if ( isset( $args['html_filter'] ) )
			$output = $args['html_filter']( $output, $args );
		
		// Renders output to setting field
		$this->outputLines( $output );
	}
	
	
	public function checkboxInput( $args ) {
		$properties = array(
			'type'	=> 'checkbox',
			'value'	=> 3
		);
		
		if ( $this->getOption( $args ) == 3 )
			$properties['checked'] = 'checked';
		
		// Sets field output
		$output = array(
			'<input ' . $this->setupFieldProperties( $args, $properties ) . '/>'
		);
		
		// Adds field description, if one exists
		$this->addDescription( $output, $args );
		
		// If hook exists to add html elements to the output, apply
		if ( isset( $args['html_filter'] ) )
			$output = $args['html_filter']( $output, $args );
		
		// Renders output to setting field
		$this->outputLines( $output );
	}
	
	private function outputLines( $lines ) {
		foreach ( $lines as $line ) {
			echo $line;
		}
	}
}







?>