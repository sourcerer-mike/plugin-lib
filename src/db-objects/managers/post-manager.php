<?php
/**
 * Manager class for posts
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects\Managers;

use Leaves_And_Love\Plugin_Lib\Traits\Meta_Manager_Trait;
use Leaves_And_Love\Plugin_Lib\Traits\Type_Manager_Trait;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\Managers\Post_Manager' ) ) :

/**
 * Class for a posts manager
 *
 * This class represents a posts manager.
 *
 * @since 1.0.0
 */
class Post_Manager extends Core_Manager {
	use Meta_Manager_Trait, Type_Manager_Trait;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB                        $db                  The database instance.
	 * @param Leaves_And_Love\Plugin_Lib\Cache                     $cache               The cache instance.
	 * @param Leaves_And_Love\Plugin_Lib\Translations\Translations $translations        Translations instance.
	 * @param array                                                $additional_services Optional. Further services. Default empty.
	 */
	public function __construct( $db, $cache, $translations, $additional_services = array() ) {
		$this->class_name            = 'Leaves_And_Love\Plugin_Lib\DB_Objects\Models\Post';
		$this->collection_class_name = 'Leaves_And_Love\Plugin_Lib\DB_Objects\Collections\Post_Collection';
		$this->query_class_name      = 'Leaves_And_Love\Plugin_Lib\DB_Objects\Queries\Post_Query';

		$this->table_name     = 'posts';
		$this->cache_group    = 'posts';
		$this->meta_type      = 'post';
		$this->fetch_callback = 'get_post';

		parent::__construct( $db, $cache, $translations, $additional_services );
	}

	/**
	 * Internal method to insert a new post into the database.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array $args Array of column => value pairs for the new database row.
	 * @return int|false The ID of the new post, or false on failure.
	 */
	protected function insert_into_db( $args ) {
		$result = wp_insert_post( $args, true );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return $result;
	}

	/**
	 * Internal method to update an existing post in the database.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int   $post_id ID of the post to update.
	 * @param array $args    Array of column => value pairs to update in the database row.
	 * @return bool True on success, or false on failure.
	 */
	protected function update_in_db( $post_id, $args ) {
		$args['ID'] = $post_id;

		$result = wp_update_post( $args, true );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Internal method to delete a post from the database.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int $post_id ID of the post to delete.
	 * @return bool True on success, or false on failure.
	 */
	protected function delete_from_db( $post_id ) {
		return (bool) wp_delete_post( $post_id, true );
	}
}

endif;
