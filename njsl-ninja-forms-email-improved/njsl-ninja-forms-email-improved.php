<?php
/*
Plugin Name: Ninja Forms email improved
Plugin URI: http://www.njstatelib.org
Description: Add support for Drupal Webform-style placeholders in Ninja Forms emails
Version: 1.1
Author: David Dean for NJSL
Author URI: http://www.njstatelib.org
*/

/**
 * Note: this plugin uses eval for laziness and speed of development -- under no circumstances
 *   should anonymous visitor input be fed into eval()
 */

add_action( 'ninja_forms_email_user',  array( 'NJSL_Ninja_Forms_Enhanced', 'do_shortcodes') );
add_action( 'ninja_forms_email_admin', array( 'NJSL_Ninja_Forms_Enhanced', 'do_shortcodes') );

add_filter( 'nf_email_notification_process_setting', array( 'NJSL_Ninja_Forms_Enhanced', 'filter_message_fields' ), 10, 3 );

class NJSL_Ninja_Forms_Enhanced {
	
	// DO NOT allow this to be filtered without removing the eval() code
	private static $field_map = array(
		'%date'         => 'date( "M d, Y" )',
		'%title'        => '$ninja_forms_processing->data["form"]["form_title"]',
		'%email_values' => 'NJSL_Ninja_Forms_Enhanced::print_value_list()'
	);
	
	private static $fields_to_scan = array(
		'admin_email_msg',
		'admin_subject',
		'user_email_msg',
		'user_subject'
	);
	
	private static $settings_to_scan = array(
		'email_subject',
		'email_message'
	);
	
	/**
	 * Replace any placeholders with dynamic data
	 */
	public static function do_shortcodes() {
		
		global $ninja_forms_processing;
		
		// Retrieve user email message
		
		foreach( self::$fields_to_scan as $field ) {
			$ninja_forms_processing->data['form'][$field] = apply_filters(
				'njsl_ninja_forms_enhance_' . $field,
				self::map_fields( $ninja_forms_processing->data['form'][$field] )
			);
		}
		
	}
	
	private static function map_fields( $content ) {
		
		global $ninja_forms_processing;
		
		foreach( self::$field_map as $field => $expr ) {
			
			$content = str_replace(
				$field,
				eval( 'return ' . $expr . ';' ),
				$content
			);
			
		}
		
		return $content;
	}
	
	public static function filter_message_fields( $setting, $setting_name, $id ) {
		
		if( in_array( $setting_name, self::$settings_to_scan ) ) {
			return apply_filters(
				'njsl_ninja_forms_enhance_' . $setting_name,
				self::map_fields( $setting )
			);
		}
		return $setting;
	}
	
	/**
	 * Print a formatted list of form fields and values
	 */
	public static function print_value_list() {
		
		global $ninja_forms_processing;
		
		$result = array();
		
		foreach( $ninja_forms_processing->data['fields'] as $field_id => $value ) {
			
			if( '_submit' == $ninja_forms_processing->data['field_data'][$field_id]['type'] )
				continue;
			if( '_desc' == $ninja_forms_processing->data['field_data'][$field_id]['type'] )
				continue;
			
			if( is_array( $value ) ) {
				
				$values = array();
				
				foreach( $ninja_forms_processing->data['field_data'][$field_id]['data']['list']['options'] as $option ) {
					
					if( in_array( $option['value'], $value ) )
						$values[] = $option['label'];
					
				}
				
				$value = join( ', ', $values );
			}
			
			$result[] = $ninja_forms_processing->data['field_data'][$field_id]['data']['label'] . ': ' . $value;
			
		}
		
		return join( "\n", $result );
		
	}
	
}

?>