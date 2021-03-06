<?php
/**
 * CLI models command class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects;

use WP_Error;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\CLI_Models_Command' ) ) :

/**
 * Class to access models via WP-CLI.
 *
 * @since 1.0.0
 */
abstract class CLI_Models_Command extends \WP_CLI\CommandWithDBObject {
	/**
	 * The manager instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\DB_Objects\Manager
	 */
	protected $manager;

	/**
	 * Object type plural.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $obj_type_plural = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager The manager instance.
	 */
	public function __construct( $manager ) {
		$this->manager = $manager;

		$this->fetcher = new CLI_Model_Fetcher( $this->manager );

		$this->obj_type = $this->manager->get_singular_slug();
		$this->obj_type_plural = $this->manager->get_plural_slug();

		$this->obj_id_key = $this->manager->get_primary_property();

		$this->obj_fields = array( $this->obj_id_key );
		if ( method_exists( $this->manager, 'get_title_property' ) ) {
			$this->obj_fields[] = $this->manager->get_title_property();
		}
		if ( method_exists( $this->manager, 'get_date_property' ) ) {
			$this->obj_fields[] = $this->manager->get_date_property();
		}
		if ( method_exists( $this->manager, 'get_status_property' ) ) {
			$this->obj_fields[] = $this->manager->get_status_property();
		}
	}

	/**
	 * Adds the command to WP-CLI.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $name Base command name.
	 */
	public function add( $name ) {
		\WP_CLI::add_command( "$name create", array( $this, 'create' ), $this->get_create_args( $name ) );
		\WP_CLI::add_command( "$name update", array( $this, 'update' ), $this->get_update_args( $name ) );

		if ( method_exists( $this->manager, 'get_content_property' ) ) {
			\WP_CLI::add_command( "$name edit", array( $this, 'edit' ), $this->get_edit_args( $name ) );
		}

		\WP_CLI::add_command( "$name get", array( $this, 'get' ), $this->get_get_args( $name ) );
		\WP_CLI::add_command( "$name delete", array( $this, 'delete' ), $this->get_delete_args( $name ) );
		\WP_CLI::add_command( "$name list", array( $this, 'list_' ), $this->get_list_args( $name ) );
		//TODO:
		//\WP_CLI::add_command( "$name generate", array( $this, 'generate' ), $this->get_generate_args( $name ) );

		if ( method_exists( $this->manager, 'get_meta_type' ) ) {
			\WP_CLI::add_command( "$name meta", new CLI_Model_Meta_Command( $this->manager ) );
		}
	}

	/**
	 * Creates a new model.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function create( $args, $assoc_args ) {
		if ( method_exists( $this->manager, 'get_content_property' ) ) {
			$content_property = $this->manager->get_content_property();

			if ( ! empty( $args[0] ) ) {
				$assoc_args[ $content_property ] = $this->read_from_file_or_stdin( $args[0] );
			}

			if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'edit' ) ) {
				$input = \WP_CLI\Utils\get_flag_value( $assoc_args, $content_property, '' );

				if ( $output = $this->_edit( $input, 'WP-CLI: New ' . $this->manager->get_singular_slug() ) ) {
					$assoc_args[ $content_property ] = $output;
				} else {
					$assoc_args[ $content_property ] = $input;
				}
			}
		}

		parent::_create( $args, $assoc_args, array( $this, 'create_callback' ) );
	}

	/**
	 * Updates one or more existing models.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function update( $args, $assoc_args ) {
		if ( method_exists( $this->manager, 'get_content_property' ) ) {
			$content_property = $this->manager->get_content_property();

			foreach ( $args as $key => $arg ) {
				if ( is_numeric( $arg ) ) {
					continue;
				}

				$assoc_args[ $content_property ] = $this->read_from_file_or_stdin( $arg );
				unset( $args[ $key ] );
				break;
			}
		}

		parent::_update( $args, $assoc_args, array( $this, 'update_callback' ) );
	}

	/**
	 * Launches the system editor to edit model content.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function edit( $args, $assoc_args ) {
		$content_property = $this->manager->get_content_property();

		$model = $this->fetcher->get_check( $args[0] );

		$content = $this->_edit( $model->$content_property, sprintf( 'WP-CLI %s %d', $this->obj_type, $args[0] ) );

		if ( false === $content ) {
			\WP_CLI::warning( sprintf( 'No change made to %s content.', $this->obj_type ), 'Aborted' );
		} else {
			$this->update( $args, array( $content_property => $content ) );
		}
	}

	/**
	 * Gets details about a model.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$model = $this->fetcher->get_check( $args[0] );

		$include_meta = false;
		if ( method_exists( $this->manager, 'get_meta' ) && \WP_CLI\Utils\get_flag_value( $assoc_args, 'include_meta' ) ) {
			$include_meta = true;
		}

		$data = $model->to_json( $include_meta );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $data );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $data );
	}

	/**
	 * Deletes one or more existing models.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function delete( $args, $assoc_args ) {
		parent::_delete( $args, $assoc_args, array( $this, 'delete_callback' ) );
	}

	/**
	 * Gets a list of models.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$query_args = array_merge( array( 'number' => -1 ), $assoc_args );
		$query_args = self::process_csv_arguments_to_arrays( $query_args );

		if ( isset( $query_args['include'] ) && ! is_array( $query_args['include'] ) ) {
			$query_args['include'] = wp_parse_id_list( $query_args['include'] );
		}

		if ( isset( $query_args['exclude'] ) && ! is_array( $query_args['exclude'] ) ) {
			$query_args['exclude'] = wp_parse_id_list( $query_args['exclude'] );
		}

		if ( isset( $query_args['orderby'] ) && isset( $query_args['order'] ) ) {
			$query_args['orderby'] = array( $query_args['orderby'] => $query_args['order'] );
		} elseif ( isset( $query_args['orderby'] ) && is_string( $query_args['orderby'] ) ) {
			$query_args['orderby'] = array( $query_args['orderby'] => 'ASC' );
		} elseif ( isset( $query_args['order'] ) ) {
			$query_args['orderby'] = array( $this->obj_id_key => $query_args['order'] );
		}

		if ( method_exists( $this->manager, 'get_type_property' ) ) {
			$type_property = $this->manager->get_type_property();

			if ( isset( $query_args[ $type_property ] ) && ! is_array( $query_args[ $type_property ] ) ) {
				$query_args[ $type_property ] = wp_parse_slug_list( $query_args[ $type_property ] );
			}
		}

		if ( method_exists( $this->manager, 'get_status_property' ) ) {
			$status_property = $this->manager->get_status_property();

			if ( isset( $query_args[ $status_property ] ) && ! is_array( $query_args[ $status_property ] ) ) {
				$query_args[ $status_property ] = wp_parse_slug_list( $query_args[ $status_property ] );
			}
		}

		if ( 'ids' === $formatter->format || 'count' === $formatter->format ) {
			$query_args['fields'] = 'ids';
		} else {
			$query_args['fields'] = 'objects';
		}

		$collection = $this->manager->query( $query_args );

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', $collection->get_raw() );
		} else {
			$include_meta = false;
			if ( method_exists( $this->manager, 'get_meta' ) && \WP_CLI\Utils\get_flag_value( $assoc_args, 'include_meta' ) ) {
				$include_meta = true;
			}

			$data = $collection->to_json( $include_meta );
			$formatter->display_items( $data['models'] );
		}
	}

	/**
	 * Returns command information for the 'create' command.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $name Base command name.
	 * @return array Command information.
	 */
	protected function get_create_args( $name ) {
		$synopsis = array();

		if ( method_exists( $this->manager, 'get_content_property' ) ) {
			$synopsis[] = array(
				'name'        => 'file',
				'type'        => 'positional',
				'description' => sprintf( 'Read %1$s content from <file>. If this value is present, the `%2$s` argument will be ignored.', $this->obj_type, $this->manager->get_content_property() ),
				'optional'    => true,
			);
		}

		$synopsis[] = array(
			'type'        => 'generic',
			'description' => sprintf( 'Associative args for the new %s.', $this->obj_type ),
			'optional'    => true,
		);

		if ( method_exists( $this->manager, 'get_content_property' ) ) {
			$synopsis[] = array(
				'name'        => 'edit',
				'type'        => 'flag',
				'description' => sprintf( 'Immediately open the system editor to write or edit %s content.', $this->obj_type ),
				'optional'    => true,
			);
		}

		$synopsis[] = array(
			'name'        => 'porcelain',
			'type'        => 'flag',
			'description' => sprintf( 'Output just the new %s ID.', $this->obj_type ),
			'optional'    => true,
		);

		return array(
			'shortdesc' => sprintf( 'Create a new %s.', $this->obj_type ),
			'synopsis'  => $synopsis,
		);
	}

	/**
	 * Returns command information for the 'update' command.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $name Base command name.
	 * @return array Command information.
	 */
	protected function get_update_args( $name ) {
		$synopsis = array(
			array(
				'name'        => $this->obj_id_key,
				'type'        => 'positional',
				'description' => sprintf( 'One or more IDs of %s to update.', $this->obj_type_plural ),
				'repeating'   => true,
			),
		);

		if ( method_exists( $this->manager, 'get_content_property' ) ) {
			$synopsis[] = array(
				'name'        => 'file',
				'type'        => 'positional',
				'description' => sprintf( 'Read %1$s content from <file>. If this value is present, the `%2$s` argument will be ignored.', $this->obj_type, $this->manager->get_content_property() ),
				'optional'    => true,
			);
		}

		$synopsis[] = array(
			'type'        => 'generic',
			'description' => sprintf( 'One or more %s fields to update.', $this->obj_type ),
		);

		return array(
			'shortdesc' => sprintf( 'Update one or more existing %s.', $this->obj_type_plural ),
			'synopsis'  => $synopsis,
		);
	}

	/**
	 * Returns command information for the 'edit' command.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $name Base command name.
	 * @return array Command information.
	 */
	protected function get_edit_args( $name ) {
		$synopsis = array(
			array(
				'name'        => $this->obj_id_key,
				'type'        => 'positional',
				'description' => sprintf( 'The ID of the %s to edit.', $this->obj_type ),
			),
		);

		return array(
			'shortdesc' => sprintf( 'Launch the system editor to edit %s content.', $this->obj_type ),
			'synopsis'  => $synopsis,
		);
	}

	/**
	 * Returns command information for the 'get' command.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $name Base command name.
	 * @return array Command information.
	 */
	public function get_get_args( $name ) {
		$synopsis = array(
			array(
				'name'        => $this->obj_id_key,
				'type'        => 'positional',
				'description' => sprintf( 'The ID of the %s to get.', $this->obj_type ),
			),
			array(
				'name'        => 'field',
				'type'        => 'assoc',
				'description' => sprintf( 'Instead of returning the whole %s, return the value of a single field.', $this->obj_type ),
				'optional'    => true,
			),
			array(
				'name'        => 'fields',
				'type'        => 'assoc',
				'description' => sprintf( 'Limit the output to specific %s fields. Defaults to all fields.', $this->obj_type ),
				'optional'    => true,
			),
			array(
				'name'        => 'format',
				'type'        => 'assoc',
				'description' => sprintf( 'Render output in a particular format.', $this->obj_type ),
				'optional'    => true,
				'default'     => 'table',
				'options'     => array( 'table', 'csv', 'json', 'yaml' ),
			),
		);

		if ( method_exists( $this->manager, 'get_meta' ) ) {
			$synopsis[] = array(
				'name'        => 'include_meta',
				'type'        => 'flag',
				'description' => sprintf( 'Include %s metadata as well.', $this->obj_type ),
				'optional'    => true,
			);
		}

		return array(
			'shortdesc' => sprintf( 'Get details about a %s.', $this->obj_type ),
			'synopsis'  => $synopsis,
		);
	}

	/**
	 * Returns command information for the 'delete' command.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $name Base command name.
	 * @return array Command information.
	 */
	protected function get_delete_args( $name ) {
		$synopsis = array(
			array(
				'name'        => $this->obj_id_key,
				'type'        => 'positional',
				'description' => sprintf( 'One or more IDs of %s to delete.', $this->obj_type_plural ),
				'repeating'   => true,
			),
		);

		return array(
			'shortdesc' => sprintf( 'Delete one or more existing %s.', $this->obj_type_plural ),
			'synopsis'  => $synopsis,
		);
	}

	/**
	 * Returns command information for the 'list' command.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $name Base command name.
	 * @return array Command information.
	 */
	protected function get_list_args( $name ) {
		$synopsis = array(
			array(
				'type'        => 'generic',
				'description' => 'One or more query arguments.',
				'optional'    => true,
			),
			array(
				'name'        => 'field',
				'type'        => 'assoc',
				'description' => sprintf( 'Print the value of a single field for each %s.', $this->obj_type ),
				'optional'    => true,
			),
			array(
				'name'        => 'fields',
				'type'        => 'assoc',
				'description' => sprintf( 'Limit the output to specific %s fields.', $this->obj_type ),
				'optional'    => true,
			),
			array(
				'name'        => 'format',
				'type'        => 'assoc',
				'description' => sprintf( 'Render output in a particular format.', $this->obj_type ),
				'optional'    => true,
				'default'     => 'table',
				'options'     => array( 'table', 'csv', 'json', 'yaml', 'ids', 'count' ),
			),
		);

		if ( method_exists( $this->manager, 'get_meta' ) ) {
			$synopsis[] = array(
				'name'        => 'include_meta',
				'type'        => 'flag',
				'description' => sprintf( 'Include %s metadata as well.', $this->obj_type ),
				'optional'    => true,
			);
		}

		return array(
			'shortdesc' => sprintf( 'Get a list of %s.', $this->obj_type_plural ),
			'synopsis'  => $synopsis,
		);
	}

	/**
	 * Internal callback to create a new model.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array $params Parameters as `$key => $value` pairs.
	 * @return int|WP_Error New model ID, or error object on failure.
	 */
	protected function create_callback( $params ) {
		$model = $this->manager->create();

		$params = array_diff_key( $params, array_flip( array( 'edit', 'porcelain' ) ) );

		foreach ( $params as $key => $value ) {
			$model->$key = $value;
		}

		$result = $model->sync_upstream();
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$primary_property = $this->obj_id_key;

		return $model->$primary_property;
	}

	/**
	 * Internal callback to update a model.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array $params Parameters as `$key => $value` pairs.
	 * @return bool|WP_Error True on success, or error object on failure.
	 */
	protected function update_callback( $params ) {
		$model = $this->manager->get( $params[ $this->obj_id_key ] );
		if ( ! $model ) {
			return new WP_Error( 'cli_item_not_exists', sprintf( 'The %s %d does not exist.', $this->obj_type, $params[ $this->obj_id_key ] ) );
		}

		unset( $params[ $this->obj_id_key ] );

		foreach ( $params as $key => $value ) {
			$model->$key = $value;
		}

		return $model->sync_upstream();
	}

	/**
	 * Internal callback to delete a model.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int   $id         Model ID.
	 * @param array $assoc_args Associative arguments.
	 * @return bool|WP_Error True on success, or error object on failure.
	 */
	protected function delete_callback( $id, $assoc_args ) {
		$model = $this->manager->get( $id );
		if ( ! $model ) {
			$result = new WP_Error( 'cli_item_not_exists', sprintf( 'The %s %d does not exist.', $this->obj_type, $params[ $this->obj_id_key ] ) );
		} else {
			$result = $model->delete();
		}

		return $this->wp_error_to_resp( $result, "Deleted $this->obj_type $id." );
	}

	/**
	 * Opens the system editor to write content.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $content Initial content.
	 * @param string $title   Title.
	 * @return string Content after editing.
	 */
	protected function _edit( $content, $title ) {
		return \WP_CLI\Utils\launch_editor_for_input( $content, $title );
	}

	/**
	 * Reads model content from file or STDIN.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $arg Supplied argument
	 * @return string Model content.
	 */
	protected function read_from_file_or_stdin( $arg ) {
		if ( $arg !== '-' ) {
			$readfile = $arg;
			if ( ! file_exists( $readfile ) || ! is_file( $readfile ) ) {
				\WP_CLI::error( "Unable to read content from '$readfile'." );
			}
		} else {
			$readfile = 'php://stdin';
		}
		return file_get_contents( $readfile );
	}
}

endif;
