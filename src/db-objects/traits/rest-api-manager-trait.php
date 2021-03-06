<?php
/**
 * Trait for managers that support the REST API
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects\Traits;

if ( ! trait_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\Traits\REST_API_Manager_Trait' ) ) :

/**
 * Trait for managers.
 *
 * Include this trait for managers that support the REST API.
 *
 * @since 1.0.0
 */
trait REST_API_Manager_Trait {
	/**
	 * The REST controller class name.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $rest_controller_class_name = 'Leaves_And_Love\Plugin_Lib\DB_Objects\REST_Models_Controller';

	/**
	 * Registers the routes for the REST controller.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_rest_routes() {
		$class_name = $this->rest_controller_class_name;

		$controller = new $class_name( $this );
		$controller->register_routes();
	}
}

endif;
