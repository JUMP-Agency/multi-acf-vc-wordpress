<?php
/**
 * Plugin Name: Multi-Site ACF for Visual Composer
 * Plugin URI: https://github.com/orgs/JUMP-Agency/multi-acf-vc-wordpress
 * Description: Allows you to use shared ACF fields from sub-sites.
 * Version: 1.0.0
 * Author: Aaron Arney
 * Author URI: https://github.com/orgs/JUMP-Agency/
 * License: MIT
 *
 * @package Jump_MU_ACF_Visual_Composer
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Class Jump_MU_ACF_Visual_Composer
 */
class Jump_MU_ACF_Visual_Composer {

	/**
	 * Jump_MU_ACF_Visual_Composer constructor.
	 *
	 * @since 1.0.0
	 */
	function __construct() {
		// We safely integrate with VC with this hook.
		add_action( 'init', array( $this, 'integrate_with_vc' ) );

		// Use this when creating a shortcode addon.
		add_shortcode( 'jump_acf', array( $this, 'render_field' ) );
	}

	/**
	 * Integrate the plugin with Visual Composer
	 *
	 * TODO: We want to be able to select which blog is the "master" in a dropdown. The other fields will populate their data based on this selection. See [1]
	 *
	 * @since 1.0.0
	 */
	public function integrate_with_vc() {

		if ( ! defined( 'WPB_VC_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'show_vc_version_notice' ) );
			return;
		}

		$groups_param_values = array();
		$fields_params = array();

		// [1] Switch the context to the 'master' blog where the desired fields live.
		switch_to_blog( 1 );

		if ( function_exists( 'acf_get_field_groups' ) ) {
			$groups = acf_get_field_groups();
		} else {
			$groups = apply_filters( 'acf_get_field_groups', array() );
		}

		// Loop through all available ACF groups.
		foreach ( (array) $groups as $group ) {
			$id = 'id';

			if ( isset( $group['ID'] ) ) {
				$id = 'ID';
			}

			$groups_param_values[ $group['title'] ] = $group[ $id ];
			$fields_param_value = array();

			if ( function_exists( 'acf_get_fields' ) ) {
				$fields = acf_get_fields( $group[ $id ] );
			} else {
				$fields = apply_filters( 'acf_field_group_get_fields', array(), $group[ $id ] );
			}

			// Don't display the field if it is a type => tab.
			foreach ( (array) $fields as $field ) {
				if ( 'tab' !== $field['type'] ) {
					$fields_param_value[ $field['label'] ] = (string) $field['key'];
				}
			}

			$fields_params[] = array(
				'type'        => 'dropdown',
				'heading'     => __( 'Field name', 'js_composer' ),
				'param_name'  => 'field_from_' . $group[ $id ],
				'value'       => $fields_param_value,
				'save_always' => true,
				'description' => __( 'Which field?', 'js_composer' ),
				'dependency'  => array(
					'element' => 'field_group',
					'value'   => array( (string) $group[ $id ] ),
				),
			);
		} // End foreach().

		// Restore the context to the current blog/site.
		restore_current_blog();

		vc_map( array(
			'name' => __( 'Advanced Custom Field', 'js_composer' ),
			'base' => 'jump_acf',
			'icon' => 'vc_icon-acf',
			'category' => __( 'Content', 'js_composer' ),
			'description' => __( 'Advanced Custom Field', 'js_composer' ),
			'params' => array_merge( array(
					array(
						'type' => 'dropdown',
						'heading' => __( 'Field group', 'js_composer' ),
						'param_name' => 'field_group',
						'value' => $groups_param_values,
						'save_always' => true,
						'description' => __( 'Select field group.', 'js_composer' ),
					),
				), $fields_params, array(
				array(
					'type' => 'textfield',
					'heading' => __( 'Extra class name', 'js_composer' ),
					'param_name' => 'el_class',
					'description' => __( 'Style particular content element differently - add a class name and refer to it in custom CSS.', 'js_composer' ),
				),
			) ),
		) );
	}

	/**
	 * Shortcode logic how it should be rendered.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts An array of attribute values.
	 * @param null  $content The content inside of the shortcode.
	 *
	 * @return string
	 */
	public function render_field( $atts, $content = null ) {
		$field_key = '';

		/**
		 * Extract the shortcode attributes.
		 *
		 * @since 1.0.0
		 *
		 * @var string $el_class
		 * @var string $show_label
		 * @var string $align
		 * @var string $field_group
		 */
		$shortcode_atts = shortcode_atts( array(
			'el_class'    => '',
			'field_group' => '',
		), $atts );

		// Switch the context to the 'master' blog where the desired fields live.
		switch_to_blog( 1 );

		if ( 0 === strlen( $field_group ) ) {

			if ( function_exists( 'acf_get_field_groups' ) ) {
				$groups = acf_get_field_groups();
			} else {
				$groups = apply_filters( 'acf_get_field_groups', array() );
			}

			if ( is_array( $groups ) && isset( $groups[0] ) ) {
				$key = 'id';

				if ( isset( $groups[0]['ID'] ) ) {
					$key = 'ID';
				}

				$field_group = $groups[0][ $key ];
			}
		}

		if ( ! empty( $field_group ) ) {

			if ( ! empty( $shortcode_atts[ 'field_from_' . $field_group ] ) ) {
				$field_key = $shortcode_atts[ 'field_from_' . $field_group ];
			} else {
				$field_key = 'field_from_group_' . $field_group;
			}
		}

		$field = get_field_object( $field_key, 'option' );

		// Restore the context to the current blog/site.
		restore_current_blog();

		return '<div>' . $field['value'] . '</div>';
	}

	/**
	 * Show notice if VC is not installed.
	 *
	 * @since 1.0.0
	 */
	public function show_vc_version_notice() {
		$plugin_data = get_plugin_data( __FILE__ );

		/* translators: Notice to install missing Visual Composer plugin */
		echo '<div class="updated"><p>' . esc_html( sprintf( __( '<strong>%s</strong> requires <strong><a href="http://bit.ly/vcomposer" target="_blank">Visual Composer</a></strong> plugin to be installed and activated on your site.', 'vc_extend' ), $plugin_data['Name'] ) ) . '</p>
        </div>';
	}
}

new Jump_MU_ACF_Visual_Composer();
