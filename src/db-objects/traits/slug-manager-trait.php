<?php
/**
 * Trait for managers that support slugs
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects\Traits;

if ( ! trait_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\Traits\Slug_Manager_Trait' ) ) :

/**
 * Trait for managers.
 *
 * Include this trait for managers that support slugs.
 *
 * @since 1.0.0
 */
trait Slug_Manager_Trait {
	/**
	 * The slug property of the model.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $slug_property = 'slug';

	/**
	 * Returns the name of the slug property in a model.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Name of the slug property.
	 */
	public function get_slug_property() {
		return $this->slug_property;
	}

	/**
	 * Sets a unique slug on the model or verifies that the model slug is unique.
	 *
	 * If another model with a similar slug is already present in the database, a suffix
	 * number will be appended to the slug until it is unique.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model $model The model to set the slug on.
	 * @param string                                      $slug  Optional. Slug to set. If not
	 *                                                           provided, the model slug will
	 *                                                           be used. Default empty.
	 */
	public function set_unique_slug( $model, $slug = '' ) {
		$primary_property = $this->get_primary_property();
		$slug_property    = $this->get_slug_property();

		if ( empty( $slug ) ) {
			$slug = $model->$slug_property;
		}

		$id = $model->$primary_property;

		$id_check = '';
		$args = array( $slug );
		if ( $id ) {
			$id_check = " AND $primary_property != %d";
			$args[] = $id;
		}
		$query = "SELECT $slug_property FROM %{$this->table_name}% WHERE $slug_property = %s $id_check LIMIT 1";

		$result = $this->db()->get_var( $query, $args );
		if ( $result ) {
			$suffix = 2;

			do {
				$alt_slug = _truncate_post_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
				$args[0] = $alt_slug;
				$result = $this->db()->get_var( $query, $args );
				$suffix++;
			} while ( $result );

			$slug = $alt_slug;
		}

		$model->$slug_property = $slug;
	}

	/**
	 * Sets the slug property on a model if it isn't set already.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param null                                        $ret   Return value from the filter.
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model $model The model to modify.
	 * @return null The unmodified pre-filter value.
	 */
	public function maybe_set_slug_property( $ret, $model ) {
		if ( ! method_exists( $this, 'get_title_property' ) ) {
			return $ret;
		}

		$slug_property  = $this->get_slug_property();
		$title_property = $this->get_title_property();

		if ( empty( $model->$slug_property ) && ! empty( $model->$title_property ) ) {
			$this->set_unique_slug( $model, sanitize_title( $model->$title_property ) );
		} else {
			$this->set_unique_slug( $model );
		}

		return $ret;
	}
}

endif;