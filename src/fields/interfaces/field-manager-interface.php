<?php
/**
 * Field_Manager interface
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\Fields\Interfaces;

if ( ! interface_exists( 'Leaves_And_Love\Plugin_Lib\Fields\Interfaces\Field_Manager_Interface' ) ) :

/**
 * Manager interface for fields
 *
 * @since 1.0.0
 */
interface Field_Manager_Interface {
	/**
	 * Creates the id attribute for a given field identifier.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string          $id    Field identifier.
	 * @param int|string|null $index Optional. Index of the field, in case it is a repeatable field.
	 *                               Default null.
	 * @return string Field id attribute.
	 */
	public function make_id( $id, $index = null );

	/**
	 * Creates the name attribute for a given field identifier.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string          $id    Field identifier.
	 * @param int|string|null $index Optional. Index of the field, in case it is a repeatable field.
	 *                               Default null.
	 * @return string Field name attribute.
	 */
	public function make_name( $id, $index = null );

	/**
	 * Returns a specific manager message.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $identifier Identifier for the message.
	 * @param bool   $noop       Optional. Whether this is a noop message. Default false.
	 * @return string|array Translated message, or array if $noop, or empty string if
	 *                      invalid identifier.
	 */
	public function get_message( $identifier, $noop = false );
}

endif;
