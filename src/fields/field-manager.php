<?php
/**
 * Field_Manager class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\Fields;

use Leaves_And_Love\Plugin_Lib\Service;
use Leaves_And_Love\Plugin_Lib\Traits\Container_Service_Trait;
use Leaves_And_Love\Plugin_Lib\Traits\Args_Service_Trait;
use Leaves_And_Love\Plugin_Lib\Traits\Translations_Service_Trait;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\Fields\Field_Manager' ) ) :

/**
 * Manager class for fields
 *
 * @since 1.0.0
 */
class Field_Manager extends Service {
	use Container_Service_Trait, Args_Service_Trait, Translations_Service_Trait;

	/**
	 * Array of fields that are part of this manager, grouped by their `$section`.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $field_instances = array();

	/**
	 * Array of registered field types, as `$type => $class_name` pairs.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 * @var array
	 */
	protected static $field_types = array();

	/**
	 * Internal flag whether default types have been registered.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 * @var bool
	 */
	protected static $defaults_registered = false;

	/**
	 * Internal flags for enqueueing field assets.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 * @var array
	 */
	protected static $enqueued = array();

	/**
	 * The Assets API service definition.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 * @var string
	 */
	protected static $service_assets = 'Leaves_And_Love\Plugin_Lib\Assets';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string                                                             $prefix       Prefix.
	 * @param array                                                              $services     {
	 *     Array of service instances.
	 *
	 *     @type Leaves_And_Love\Plugin_Lib\Assets        $assets        The Assets API class instance.
	 *     @type Leaves_And_Love\Plugin_Lib\Error_Handler $error_handler The error handler instance.
	 * }
	 * @param array                                                              $args         {
	 *     Array of arguments.
	 *
	 *     @type callable $get_value_callback         Callback to get current values.
	 *     @type array    $get_value_callback_args    Arguments to pass to the `$get_value_callback`.
	 *                                                A placeholder `{id}` can be used to indicate that
	 *                                                this argument should be replaced by the field ID.
	 *     @type callable $update_value_callback      Callback to update the current values with new ones.
	 *     @type array    $update_value_callback_args Arguments to pass to the `$update_value_callback`.
	 *                                                One of these arguments must be a placeholder `{value}`.
	 *                                                Another placeholder `{id}` can also be used to indicate
	 *                                                that this argument should be replaced by the field ID.
	 *     @type string   $name_prefix                The name prefix to create name attributes for fields.
	 *     @type string   $render_mode                Render mode. Default 'form-table'.
	 * }
	 * @param Leaves_And_Love\Plugin_Lib\Translations\Translations_Field_Manager $translations Translations instance.
	 */
	public function __construct( $prefix, $services, $args, $translations ) {
		$this->set_prefix( $prefix );
		$this->set_services( $services );
		$this->set_args( $args );
		$this->set_translations( $translations );

		self::register_default_field_types();
	}

	/**
	 * Enqueues the necessary assets for a list of fields.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array|null $sections Optional. Section identifier(s), to only enqueue for
	 *                                    fields that belong to this section. Default null.
	 */
	public function enqueue( $sections = null ) {
		$field_instances = $this->get_fields( $sections );

		foreach ( $field_instances as $id => $field_instance ) {
			$type = array_search( get_class( $field_instance ), self::$field_types, true );
			if ( isset( self::$enqueued[ $type ] ) && self::$enqueued[ $type ] ) {
				continue;
			}

			$field_instance->enqueue();

			self::$enqueued[ $type ] = true;
		}
	}

	/**
	 * Renders a list of fields.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array|null $sections Optional. Section identifier(s), to only render
	 *                                    fields that belong to this section. Default null.
	 */
	public function render( $sections = null ) {
		$field_instances = $this->get_fields( $sections );

		$render_callback = array( $this, 'render_form_table_row' );
		//TODO: Implement more render callbacks.

		$values = $this->get_values( $sections );

		foreach ( $field_instances as $id => $field_instance ) {
			$value = isset( $values[ $id ] ) ? $values[ $id ] : $field_instance->default;

			call_user_func( $render_callback, $field_instance, $value );
		}
	}

	/**
	 * Gets the current values for a list of fields.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array|null $sections Optional. Section identifier(s), to only get values for
	 *                                    fields that belong to this section. Default null.
	 * @return array Array of values as `$id => $current_value` pairs.
	 */
	public function get_values( $sections = null ) {
		$field_instances = $this->get_fields( $sections );

		$id_key = array_search( '{id}', $this->get_value_callback_args, true );
		if ( false !== $id_key ) {
			$values = array();

			foreach ( $field_instances as $id => $field_instance ) {
				$args = $this->get_value_callback_args;
				$args[ $id_key ] = $id;

				$values[ $id ] = call_user_func_array( $this->get_value_callback, $args );
			}

			return $values;
		}

		return call_user_func_array( $this->get_value_callback, $this->get_value_callback_args );
	}

	/**
	 * Updates the current values for a list of fields.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array             $values   New values to validate and store, as `$id => $new_value` pairs.
	 * @param string|array|null $sections Optional. Section identifier(s), to only update values for
	 *                                    fields that belong to this section. Default null.
	 * @return bool|WP_Error True on success, or an error object if some fields produced validation errors.
	 *                       All fields that are not part of this error object have been updated successfully.
	 */
	public function update_values( $values, $sections = null ) {
		$field_instances = $this->get_fields( $sections );

		$errors = new WP_Error();

		$value_key = array_search( '{value}', $this->update_value_callback_args, true );

		$id_key = array_search( '{id}', $this->update_value_callback_args, true );
		if ( false !== $id_key ) {
			foreach ( $field_instances as $id => $field_instance ) {
				$value = isset( $values[ $id ] ) ? $values[ $id ] : null;
				$validated = $field_instance->validate( $value );
				if ( is_wp_error( $validated ) ) {
					$this->merge_errors( $errors, $validated );
					continue;
				}

				$args = $this->update_value_callback_args;
				$args[ $id_key ] = $id;
				$args[ $value_key ] = $validated;

				call_user_func_array( $this->update_value_callback, $args );
			}
		} else {
			$old_values = $this->get_values( $sections );

			foreach ( $field_instances as $id => $field_instance ) {
				$value = isset( $values[ $id ] ) ? $values[ $id ] : null;
				$validated = $field_instance->validate( $value );
				if ( is_wp_error( $validated ) ) {
					$this->merge_errors( $errors, $validated );
					continue;
				}

				$old_values[ $id ] = $validated;
			}

			$args = $this->update_value_callback_args;
			$args[ $value_key ] = $old_values;

			call_user_func_array( $this->update_value_callback, $args );
		}

		if ( ! empty( $errors->errors ) ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Returns an array of fields that are part of this field manager.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array|null $sections Optional. Section identifier(s), to only return
	 *                                    fields that belong to this section. Default null.
	 * @return array Array of fields as `$id => $instance` pairs.
	 */
	public function get_fields( $sections = null ) {
		if ( null !== $sections ) {
			$sections = (array) $sections;
		} else {
			$sections = array_keys( $this->field_instances );
		}

		$all_field_instances = array();
		foreach ( $this->field_instances as $section => $field_instances ) {
			if ( ! in_array( $section, $sections, true ) ) {
				continue;
			}

			$all_field_instances = array_merge( $all_field_instances, $field_instances );
		}

		return $all_field_instances;
	}

	/**
	 * Creates the id attribute for a given field identifier.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $id Field identifier.
	 * @return string Field id attribute.
	 */
	public function make_id( $id ) {
		return str_replace( '_', '-', $id );
	}

	/**
	 * Creates the name attribute for a given field identifier.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $id Field identifier.
	 * @return string Field name attribute.
	 */
	public function make_name( $id ) {
		$name_prefix = $this->name_prefix;

		if ( empty( $name_prefix ) ) {
			return $id;
		}

		return $this->name_prefix . '[' . $id . ']';
	}

	/**
	 * Renders a field in form table mode.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param Leaves_And_Love\Plugin_Lib\Fields\Field $field Field instance.
	 * @param mixed                                   $value Current field value.
	 */
	protected function render_form_table_row( $field, $value ) {
		?>
		<tr>
			<th scope="row">
				<?php $field->render_label(); ?>
			</th>
			<td>
				<?php $field->render_content( $value ); ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Merges an error object into another error object.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Error $errors The main error object.
	 * @param WP_Error $error  The error object to merge into the other one.
	 */
	protected function merge_errors( $errors, $error ) {
		$errors->add( $error->get_error_code(), $error->get_error_message(), $error->get_error_data() );
	}

	/**
	 * Registers a field type.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $type       Identifier of the type.
	 * @param string $class_name Name of the field type class.
	 * @return bool True on success, false on failure.
	 */
	public static function register_field_type( $type, $class_name ) {
		self::register_default_field_types();

		if ( self::is_field_type_registered( $type ) ) {
			return false;
		}

		// Do not allow registration of an existing class as a different type.
		if ( in_array( $class_name, self::$field_types, true ) ) {
			return false;
		}

		self::$field_types[ $type ] = $class_name;

		return true;
	}

	/**
	 * Retrieves the class name for a registered field type.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $type Identifier of the type.
	 * @return string Class name, or empty string if the type is not registered.
	 */
	public static function get_registered_field_type( $type ) {
		self::register_default_field_types();

		if ( ! self::is_field_type_registered( $type ) ) {
			return '';
		}

		return self::$field_types[ $type ];
	}

	/**
	 * Checks whether a field type is registered.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $type Identifier of the type.
	 * @return bool True if the type is registered, false otherwise.
	 */
	public static function is_field_type_registered( $type ) {
		self::register_default_field_types();

		return isset( self::$field_types[ $type ] );
	}

	/**
	 * Unregisters a field type.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $type Identifier of the type.
	 * @return bool True on success, false on failure.
	 */
	public static function unregister_field_type( $type ) {
		self::register_default_field_types();

		if ( ! self::is_field_type_registered( $type ) ) {
			return false;
		}

		unset( self::$field_types[ $type ] );

		return true;
	}

	/**
	 * Registers the default field types.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 */
	protected static function register_default_field_types() {
		if ( self::$defaults_registered ) {
			return;
		}

		$default_field_types = array();

		foreach ( $default_field_types as $type => $class_name ) {
			self::register_field_type( $type, $class_name );
		}

		self::$defaults_registered = true;
	}

	/**
	 * Gets an option.
	 *
	 * Default callback used for the `$get_value_callback` argument.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param string $id Field identifier.
	 * @return mixed Current value, or null if not set.
	 */
	protected static function get_option( $id ) {
		return get_option( $id, null );
	}

	/**
	 * Updates an option.
	 *
	 * Default callback used for the `$update_value_callback` argument.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param string $id    Field identifier.
	 * @param mixed  $value New value to set.
	 * @return bool True on success, false on failure.
	 */
	protected static function update_option( $id, $value ) {
		return update_option( $id, $value );
	}

	/**
	 * Parses the get value callback.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed $value The input value.
	 * @return callable The parsed value.
	 */
	protected static function parse_arg_get_value_callback( $value ) {
		if ( ! is_callable( $value ) ) {
			return array( __CLASS__, 'get_option' );
		}

		return $value;
	}

	/**
	 * Parses the get value callback args.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed $value The input value.
	 * @return array The parsed value.
	 */
	protected static function parse_arg_get_value_callback_args( $value ) {
		if ( ! is_array( $value ) ) {
			return array( '{id}' );
		}

		return $value;
	}

	/**
	 * Parses the update value callback.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed $value The input value.
	 * @return callable The parsed value.
	 */
	protected static function parse_arg_update_value_callback( $value ) {
		if ( ! is_callable( $value ) ) {
			return array( __CLASS__, 'update_option' );
		}

		return $value;
	}

	/**
	 * Parses the update value callback args.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed $value The input value.
	 * @return array The parsed value.
	 */
	protected static function parse_arg_update_value_callback_args( $value ) {
		if ( ! is_array( $value ) ) {
			return array( '{id}', '{value}' );
		}

		/* A '{value}' element must always be present. This is the worst way to verify it. */
		if ( ! in_array( '{value}', $value, true ) ) {
			$value[] = '{value}';
		}

		return $value;
	}

	/**
	 * Parses the name prefix.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed $value The input value.
	 * @return string The parsed value.
	 */
	protected static function parse_arg_name_prefix( $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Parses the render mode.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param mixed $value The input value.
	 * @return string The parsed value.
	 */
	protected static function parse_arg_render_mode( $value ) {
		$valid_modes = array( 'form-table' );

		if ( ! is_string( $value ) || ! in_array( $value, $valid_modes, true ) ) {
			return 'form-table';
		}

		return $value;
	}
}

endif;