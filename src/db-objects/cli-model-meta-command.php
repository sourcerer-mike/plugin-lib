<?php
/**
 * CLI model meta command class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\CLI_Model_Meta_Command' ) ) :

/**
 * Class to access model meta via WP-CLI.
 *
 * @since 1.0.0
 */
class CLI_Model_Meta_Command extends \WP_CLI\CommandWithMeta {
	/**
	 * The manager instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\DB_Objects\Manager
	 */
	protected $manager;

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

		$this->meta_type = $this->manager->meta()->get_prefix() . $this->manager->get_meta_type();
	}

	/**
	 * Checks that the model with a specific ID exists.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int $object_id Model ID.
	 * @return int Model ID.
	 */
	protected function check_object_id( $object_id ) {
		$fetcher = new CLI_Model_Fetcher( $this->manager );

		$model = $fetcher->get_check( $object_id );

		$primary_property = $this->manager->get_primary_property();
		return $model->$primary_property;
	}
}

endif;
