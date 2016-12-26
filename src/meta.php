<?php
/**
 * Meta abstraction class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\Meta' ) ) :

/**
 * Class for Metadata API
 *
 * The class is a wrapper for the WordPress Metadata API.
 *
 * In a multisite setup this class also supports site options and network options as if they were meta.
 * The only difference between options and meta is that an option cannot have multiple values for one key,
 * therefore the method parameters related to uniqueness of values are ignored when using them with a
 * $meta_type of either 'site' or 'network'.
 *
 * @since 1.0.0
 *
 * @method Leaves_And_Love\Plugin_Lib\DB db()
 */
class Meta extends Service {
	/**
	 * The database class instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\DB
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB $db The database class instance.
	 */
	public function __construct( $db ) {
		$this->db = $db;

		$this->set_services( array( 'db' ) );
	}

	/**
	 * Adds metadata for the specified object.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $meta_type  Type of object metadata is for (e.g. row, column or module).
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param bool   $unique     Optional, default is false.
	 *                           Whether the specified metadata key should be unique for the object.
	 *                           If true, and the object already has a value for the specified metadata key,
	 *                           no change will be made.
	 * @return int|bool The meta ID or true on success, false on failure.
	 */
	public function add( $meta_type, $object_id, $meta_key, $meta_value, $unique = false ) {
		if ( is_multisite() ) {
			if ( 'site' === $meta_type ) {
				return add_blog_option( $object_id, $meta_key, $meta_value );
			} elseif ( 'network' === $meta_type ) {
				return add_network_option( $object_id, $meta_key, $meta_value );
			}
		}

		if ( $this->is_prefixed_type( $meta_type ) ) {
			$meta_type = $this->db->get_prefix() . $meta_type;
		}
		return add_metadata( $meta_type, $object_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Updates metadata for the specified object. If no value already exists for the specified object
	 * ID and metadata key, the metadata will be added.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $meta_type  Type of object metadata is for (e.g. row, column or module).
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. If specified, only update existing metadata entries with
	 * 		                     the specified value. Otherwise, update all entries.
	 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
	 */
	public function update( $meta_type, $object_id, $meta_key, $meta_value, $prev_value = '' ) {
		if ( is_multisite() ) {
			if ( 'site' === $meta_type ) {
				return update_blog_option( $object_id, $meta_key, $meta_value );
			} elseif ( 'network' === $meta_type ) {
				return update_network_option( $object_id, $meta_key, $meta_value );
			}
		}

		if ( $this->is_prefixed_type( $meta_type ) ) {
			$meta_type = $this->db->get_prefix() . $meta_type;
		}
		return update_metadata( $meta_type, $object_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Deletes metadata for the specified object.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $meta_type  Type of object metadata is for (e.g. row, column or module).
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if non-scalar. If specified, only delete
	 *                           metadata entries with this value. Otherwise, delete all entries with the specified meta_key.
	 *                           Pass `null, `false`, or an empty string to skip this check. (For backward compatibility,
	 *                           it is not possible to pass an empty string to delete those entries with an empty string
	 *                           for a value.)
	 * @param bool   $delete_all Optional, default is false. If true, delete matching metadata entries for all objects,
	 *                           ignoring the specified object_id. Otherwise, only delete matching metadata entries for
	 *                           the specified object_id.
	 * @return bool True on successful delete, false on failure.
	 */
	public function delete( $meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
		if ( is_multisite() ) {
			if ( 'site' === $meta_type ) {
				return delete_blog_option( $object_id, $meta_key );
			} elseif ( 'network' === $meta_type ) {
				return delete_network_option( $object_id, $meta_key );
			}
		}

		if ( $this->is_prefixed_type( $meta_type ) ) {
			$meta_type = $this->db->get_prefix() . $meta_type;
		}
		return delete_metadata( $meta_type, $object_id, $meta_key, $meta_value, $delete_all );
	}

	/**
	 * Retrieves metadata for the specified object.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $meta_type Type of object metadata is for (e.g. row, column or module).
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
	 * 		                    the specified object.
	 * @param bool   $single    Optional, default is false.
	 *                          If true, return only the first value of the specified meta_key.
	 *                          This parameter has no effect if meta_key is not specified.
	 * @return mixed Single metadata value, or array of values.
	 */
	public function get( $meta_type, $object_id, $meta_key = '', $single = false ) {
		if ( is_multisite() ) {
			if ( 'site' === $meta_type ) {
				return get_blog_option( $object_id, $meta_key );
			} elseif ( 'network' === $meta_type ) {
				return get_network_option( $object_id, $meta_key );
			}
		}

		if ( $this->is_prefixed_type( $meta_type ) ) {
			$meta_type = $this->db->get_prefix() . $meta_type;
		}
		$values = get_metadata( $meta_type, $object_id, $meta_key, false );

		// Return false if a single value does not exist.
		if ( $meta_key && $single ) {
			if ( isset( $values[0] ) ) {
				return $values[0];
			}
			return false;
		}

		return $values;
	}

	/**
	 * Determines if a meta key is set for a given object
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $meta_type Type of object metadata is for (e.g. row, column or module).
	 * @param int    $object_id ID of the object metadata is for.
	 * @param string $meta_key  Metadata key.
	 * @return bool True of the key is set, false if not.
	 */
	public function exists( $meta_type, $object_id, $meta_key ) {
		if ( is_multisite() ) {
			if ( 'site' === $meta_type ) {
				if ( false === get_blog_option( $object_id, $meta_key ) ) {
					return false;
				}
				return true;
			} elseif ( 'network' === $meta_type ) {
				if ( false === get_network_option( $object_id, $meta_key ) ) {
					return false;
				}
				return true;
			}
		}

		if ( $this->is_prefixed_type( $meta_type ) ) {
			$meta_type = $this->db->get_prefix() . $meta_type;
		}
		return metadata_exists( $meta_type, $object_id, $meta_key );
	}

	/**
	 * Deletes all metadata for the specified object.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $meta_type  Type of object metadata is for (e.g. row, column or module).
	 * @param int    $object_id  ID of the object metadata is for.
	 * @return bool True on successful delete, false on failure.
	 */
	public function delete_all( $meta_type, $object_id ) {
		if ( ! $this->is_prefixed_type( $meta_type ) ) {
			return false;
		}

		$prefixed_meta_type = $this->db->get_prefix() . $meta_type;

		$meta_ids = $this->db->get_col( "SELECT meta_id FROM %{$meta_type}meta% WHERE {$prefixed_meta_type}_id = %d", $object_id );
		foreach ( $meta_ids as $meta_id ) {
			delete_metadata_by_mid( $prefixed_meta_type, $meta_id );
		}

		return true;
	}

	/**
	 * Checks whether a meta type must be prefixed.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $meta_type Type of object metadata is for (e.g. row, column or module).
	 * @return bool True if the meta type must be prefixed, false otherwise.
	 */
	protected function is_prefixed_type( $meta_type ) {
		return $this->db->table_exists( $meta_type . 'meta' );
	}
}

endif;