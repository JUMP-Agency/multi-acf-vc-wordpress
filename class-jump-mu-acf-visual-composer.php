<?php
/**
 * Plugin Name: Multi-Site ACF for Visual Composer
 * Plugin URI: https://github.com/orgs/JUMP-Agency/multi-acf-vc-wordpress
 * Description: Allows you to use shared ACF fields from sub-sites.
 * Version: 1.1.0
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
	 * Get Advanced Custom Fields Groups
	 *
	 * @since 1.1.0
	 *
	 * @param null $id A string containing the ID of the groups to grab
	 *
	 * @return array
	 */
	private function get_acf_groups( $id = null ) {
		if ( function_exists( 'acf_get_field_groups' ) ) {
			return acf_get_field_groups( $id );
		} else {
			return apply_filters( 'acf_get_field_groups', array(), $id );
		}
	}

	/**
	 * Get Advanced Custom Fields Fields
	 *
	 * @since 1.1.0
	 *
	 * @param null $id A string containing the ID of the fields to grab
	 *
	 * @return array
	 */
	private function get_acf_fields( $id = null ) {
		if ( function_exists( 'acf_get_fields' ) ) {
			return acf_get_fields( $id );
		} else {
			return apply_filters( 'acf_field_group_get_fields', array(), $id );
		}
	}

	/**
	 * Set the nomenclature for the ID property.
	 *
	 * @since 1.1.0
	 *
	 * @param string $group The array index to check
	 *
	 * @return string
	 */
	private function get_id_nomenclature( $group ) {
		if ( isset( $group ) ) {
			return 'ID';
		} else {
			return 'id';
		}
	}

	/**
	 * Integrate the plugin with Visual Composer
	 *
	 * @since 1.0.0
	 */
	public function integrate_with_vc() {

		if ( ! defined( 'WPB_VC_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'show_vc_version_notice' ) );
			return;
		}

		$blog_id_param_values = array();
		$groups_param_elements = array();
		$groups_param_values = array();
		$fields_param_elements = array();
		$fields_param_value = array();

		/**
		 * Loop through all of the sites and generate groups and fields for each respective site
		 *
		 * [1] Loop through all sites.
		 * [2] Switch to the current $site context.
		 * [3] Get all groups belonging to site.
		 * [4] Loop through the groups that belong to $site.
		 * [5] Get all fields belonging to the group.
		 * [6] Loop through the fields and push them into the $fields array if they are NOT the 'type' set in options.
		 * [7] Generate the select/dropdown with the groups assigned to the $site_id.
		 * [8] Generate the select/dropdown with the fields assigned to the $group[ $id ].
		 * [9] Restore the current blog context.
		 * [10] Map the elements to Visual Composer.
		 *
		 * @since 1.1.0
		 */

		// [1] Loop through all sites.
		foreach ( get_sites() as $site ) {
			unset( $groups_param_values );
			$groups_param_values = array();
			$site_vars = get_object_vars( $site );
			$site_id = $site_vars['blog_id']; // The Blog ID.
			$site_name = get_blog_details( $site_id )->blogname; // Store key name for displaying in VC.
			$blog_id_param_values[ $site_name ] = $site_id; // Store key values for displaying in VC.

			// [2] Switch to the current $site context.
			switch_to_blog( $site_id );

			// [3] Get all groups belonging to site.
			$groups = $this->get_acf_groups();

			// [4] Loop through the groups that belong to $site.
			foreach ( $groups as $group ) {

				/**
				 *********** BUG ***********
				 *
				 * TODO: Investigate why the array is not clearing after every iteration
				 *
				 * Interestingly, this array was not being wiped after each iteration.
				 * Instead of clearing the array, items would be appended to the end...much like a `push`.
				 *
				 * I tried to recreate this in another test script but was unable to. Some investigation into
				 * this will be necessary to truly understand why it is behaving this way. A 'fix' is to
				 * unset the array at the beginning of every loop iteration.
				 */
				unset( $fields_param_value );
				$fields_param_value = array();

				// I believe this is for backwards compatibility. Some fields may use 'id' while others use 'ID'.
				$id_nomen = $this->get_id_nomenclature( $group['ID'] );

				// Create the key => value pairs for the site select dropdown.
				$groups_param_values[ $group['title'] ] = $group[ $id_nomen ];

				// [5] Get all fields belonging to the group.
				$fields = $this->get_acf_fields( $group[ $id_nomen ] );

				// [6] Loop through the fields and push them into the $fields array if they are NOT the 'type' set in options.
				foreach ( (array) $fields as $field ) {
					if ( 'tab' !== $field['type'] && 'message' !== $field['type']) {
						$fields_param_value[ $field['label'] ] = $field['key'];
					}
				}

				// [7] Generate the select/dropdown with the groups assigned to the $site_id.
				$fields_param_elements[] = array(
					'type'        => 'dropdown',
					'heading'     => __( 'Field name', 'js_composer' ),
					'param_name'  => 'field_' . $group[ $id_nomen ] ,
					'value'       => $fields_param_value,
					'save_always' => true,
					'description' => __( 'Which field?', 'js_composer' ),
					'dependency'  => array(
						'element' => 'group_' . $site_id,
						'value'  => (string) $group[ $id_nomen ],
					),
				);
			} // End foreach().

			// [8] Generate the select/dropdown with the fields assigned to the $group[ $id ].
			$groups_param_elements[] = array(
				'type'        => 'dropdown',
				'heading'     => __( 'Field group', 'js_composer' ),
				'param_name'  => 'group_' . $site_id, // Unique ID for the field.
				'value'       => $groups_param_values,
				'save_always' => true,
				'description' => __( 'Select field group.', 'js_composer' ),
				'dependency'  => array(
					'element' => 'blog_group',
					'value'   => $site_id,
				),
			);

			// [9] Restore the current blog context.
			restore_current_blog();

		} // End foreach().

		// [10] Map the elements to Visual Composer.
		vc_map( array(
			'name'        => __( 'Multi-Site Advanced Custom Field', 'js_composer' ),
			'base'        => 'jump_acf',
			'icon'        => 'vc_icon-acf',
			'category'    => __( 'Content', 'js_composer' ),
			'description' => __( 'Advanced Custom Field from another blog site', 'js_composer' ),
			'params'      => array_merge( array(
				array(
					'type'        => 'dropdown',
					'heading'     => __( 'Blog', 'js_composer' ),
					'param_name'  => 'blog_group',
					'value'       => $blog_id_param_values,
					'save_always' => true,
					'description' => __( 'Select blog.', 'js_composer' ),
				),
			), $groups_param_elements, $fields_param_elements, array(
				array(
					'type'        => 'textfield',
					'heading'     => __( 'Extra class name', 'js_composer' ),
					'param_name'  => 'el_class',
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
	 *
	 * @return string
	 */
	public function render_field( $atts ) {

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
			'el_class' => '',
		), $atts );

		$field_key = '';
		$field_group = $atts['field_group'];

		// Switch the context to the 'master' blog where the desired fields live.
		switch_to_blog( $atts['blog_group'] );

		if ( 0 === strlen( $field_group ) ) {

			// Get all of the groups from the site context.
			$groups = $this->get_acf_groups();

			if ( is_array( $groups ) && isset( $groups[0] ) ) {
				$key = $this->get_id_nomenclature( $groups[0]['ID'] );

				$field_group = $groups[0][ $key ];
			}
		}

		if ( ! empty( $field_group ) ) {

			if ( ! empty( $atts[ 'field_from_' . $field_group ] ) ) {
				$field_key = $atts[ 'field_from_' . $field_group ];
			} else {
				$field_key = $atts[ 'field_' . $field_group ];
			}
		}

		// Retrieve the field from ACF.
		$field = get_field_object( $field_key, 'option' );

		// Restore the context to the current blog/site.
		restore_current_blog();

		return '<div class="' . $shortcode_atts['el_class'] . '">' . $field['value'] . '</div>';
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
