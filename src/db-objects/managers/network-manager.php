<?php
/**
 * Manager class for networks
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects\Managers;

use Leaves_And_Love\Plugin_Lib\DB_Objects\Storage;
use Leaves_And_Love\Plugin_Lib\DB_Objects\Traits\Meta_Manager_Trait;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\Managers\Network_Manager' ) ) :

/**
 * Class for a networks manager
 *
 * This class represents a networks manager. Must only be used in a multisite setup.
 * Some functionality is only available with the WP Multi Network plugin activated.
 *
 * @since 1.0.0
 */
class Network_Manager extends Core_Manager {
	use Meta_Manager_Trait;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string                                                               $prefix   The instance prefix.
	 * @param array                                                                $services {
	 *     Array of service instances.
	 *
	 *     @type Leaves_And_Love\Plugin_Lib\DB            $db            The database instance.
	 *     @type Leaves_And_Love\Plugin_Lib\Cache         $cache         The cache instance.
	 *     @type Leaves_And_Love\Plugin_Lib\Meta          $meta          The meta instance.
	 *     @type Leaves_And_Love\Plugin_Lib\Error_Handler $error_handler The error handler instance.
	 * }
	 * @param Leaves_And_Love\Plugin_Lib\Translations\Translations_Network_Manager $translations Translations instance.
	 */
	public function __construct( $prefix, $services, $translations ) {
		$this->class_name            = 'Leaves_And_Love\Plugin_Lib\DB_Objects\Models\Network';
		$this->collection_class_name = 'Leaves_And_Love\Plugin_Lib\DB_Objects\Collections\Network_Collection';
		$this->query_class_name      = 'Leaves_And_Love\Plugin_Lib\DB_Objects\Queries\Network_Query';

		$this->singular_slug = 'network';
		$this->plural_slug   = 'networks';

		$this->table_name       = 'site';
		$this->cache_group      = 'networks';
		$this->meta_type        = 'network';
		$this->fetch_callback   = 'get_network';
		$this->primary_property = 'id';

		Storage::register_global_group( $this->cache_group );

		parent::__construct( $prefix, $services, $translations );
	}

	/**
	 * Internal method to insert a new network into the database.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array $args Array of column => value pairs for the new database row.
	 * @return int|false The ID of the new network, or false on failure.
	 */
	protected function insert_into_db( $args ) {
		if ( ! function_exists( 'add_network' ) ) {
			return false;
		}

		if ( ! isset( $args['domain'] ) ) {
			return false;
		}

		if ( ! isset( $args['user_id'] ) ) {
			$args['user_id'] = get_current_user_id();
			if ( ! $args['user_id'] ) {
				$args['user_id'] = 1;
			}
		}

		$result = add_network( $args );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return (int) $result;
	}

	/**
	 * Internal method to update an existing network in the database.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int   $network_id ID of the network to update.
	 * @param array $args       Array of column => value pairs to update in the database row.
	 * @return bool True on success, or false on failure.
	 */
	protected function update_in_db( $network_id, $args ) {
		if ( ! function_exists( 'update_network' ) ) {
			return false;
		}

		$result = update_network( $network_id, $args['domain'], $args['path'] );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Internal method to delete a network from the database.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int $network_id ID of the network to delete.
	 * @return bool True on success, or false on failure.
	 */
	protected function delete_from_db( $network_id ) {
		if ( ! function_exists( 'delete_network' ) ) {
			return false;
		}

		$result = delete_network( $network_id );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return true;
	}
}

endif;
