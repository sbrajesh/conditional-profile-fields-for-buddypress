<?php
defined('ABSPATH' ) || exit(0);

function bpc_profile_field_sanitize_child_options( $options ) {

	return $options;
	/*
	if ( ! is_array( $options ) ) {
		return $options;
	}

	foreach ( $options as &$option ) {
		$option->bpcf_val = esc_attr( stripslashes( $option->name ) );
		$option->esc_val = html_entity_decode( $option->name, ENT_QUOTES, 'UTF-8'  );
	}

	return $options;
	*/
}