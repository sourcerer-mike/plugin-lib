<?php
/**
 * Query class for Core objects
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects\Queries;

use Leaves_And_Love\Plugin_Lib\DB_Objects\Query;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\Queries\Core_Query' ) ) :

/**
 * Base class for a core query
 *
 * This class represents a general core query.
 *
 * @since 1.0.0
 */
abstract class Core_Query extends Query {
	/**
	 * The original Core object for this query.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var object
	 */
	protected $original;

	/**
	 * Constructor.
	 *
	 * Sets the manager instance and assigns the defaults.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Managers\Core_Manager $manager The manager instance for the model query.
	 */
	public function __construct( $manager ) {
		$this->manager = $manager;

		$this->original = $this->instantiate_query_object();
	}

	/**
	 * Magic isset-er.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $property Property to check for.
	 * @return bool True if property is set, false otherwise.
	 */
	public function __isset( $property ) {
		switch ( $property ) {
			case 'request':
			case 'query_vars':
			case 'results':
				return true;
			case 'query_var_defaults':
			case 'meta_query':
			case 'tax_query':
			case 'date_query':
				return isset( $this->original->$property );
		}

		return false;
	}

	/**
	 * Magic getter.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $property Property to get.
	 * @return mixed Property value.
	 */
	public function __get( $property ) {
		switch ( $property ) {
			case 'request':
			case 'query_vars':
				return $this->original->$property;
			case 'results':
				return $this->results;
			case 'query_var_defaults':
			case 'meta_query':
			case 'tax_query':
			case 'date_query':
				if ( ! isset( $this->original->$property ) ) {
					return null;
				}
				return $this->original->$property;
		}

		return null;
	}

	/**
	 * Sets up the query for retrieving models.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string|array $query Array or query string of query arguments.
	 * @return Leaves_And_Love\Plugin_Lib\DB_Objects\Collections\Core_Collection Collection of models.
	 */
	public function query( $query ) {
		$this->original->query( $query );

		$this->results = $this->parse_results_collection();

		return $this->results;
	}

	/**
	 * Returns the original Core object for this query.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return object WordPress Core object.
	 */
	public function get_original() {
		return $this->original;
	}

	/**
	 * Instantiates the internal Core query object.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return object Internal Core query object.
	 */
	protected abstract function instantiate_query_object();

	/**
	 * Parses the results of the internal Core query into a collection.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return Leaves_And_Love\Plugin_Lib\DB_Objects\Collections\Core_Collection Results as a collection.
	 */
	protected abstract function parse_results_collection();
}

endif;
