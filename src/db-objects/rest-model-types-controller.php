<?php
/**
 * REST model types controller class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects;

use WP_REST_Server;
use WP_REST_Controller;
use WP_Error;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\REST_Model_Types_Controller' ) ) :

/**
 * Class to access model types via the REST API.
 *
 * @since 1.0.0
 */
class REST_Model_Types_Controller extends WP_REST_Controller {
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

		$prefix = $this->manager->get_prefix();
		if ( '_' === substr( $prefix, -1 ) ) {
			$prefix = substr( $prefix, 0, -1 );
		}

		$this->namespace = $prefix;
		$this->rest_base = $this->manager->get_plural_slug() . '/types';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<slug>[\w-]+)', array(
			'args' => array(
				'slug' => array(
					'description' => $this->manager->get_message( 'rest_type_slug_description' ),
					'type'        => 'string',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Checks whether a given request has permission to read types.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|true True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$capabilities = $this->manager->capabilities();

		if ( 'edit' === $request['context'] && ( ! $capabilities || ! $capabilities->user_can_edit() ) ) {
			return new WP_Error( 'rest_cannot_edit_types', $this->manager->get_message( 'rest_cannot_edit_types' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( ! $capabilities || ! $capabilities->user_can_read() ) {
			return new WP_Error( 'rest_cannot_read_types', $this->manager->get_message( 'rest_cannot_read_types' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves a collection of model types.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$args = 'edit' === $request['context'] ? array() : array( 'public' => true );

		$data = array();

		foreach ( $this->manager->types()->query( $args ) as $obj ) {
			$type = $this->prepare_item_for_response( $obj );

			$data[ $obj->slug ] = $this->prepare_response_for_collection( $type );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Checks whether a given request has permission to read a type.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|true True if the request has read access, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		$capabilities = $this->manager->capabilities();

		if ( 'edit' === $request['context'] && ( ! $capabilities || ! $capabilities->user_can_edit() ) ) {
			return new WP_Error( 'rest_cannot_edit_type', $this->manager->get_message( 'rest_cannot_edit_type' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( ! $capabilities || ! $capabilities->user_can_read() ) {
			return new WP_Error( 'rest_cannot_read_type', $this->manager->get_message( 'rest_cannot_read_type' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$obj = $this->manager->types()->get( $request['slug'] );

		if ( ! $obj || ( ! $obj->public && 'edit' !== $request['context'] ) ) {
			return new WP_Error( 'rest_invalid_type_slug', $this->manager->get_message( 'rest_invalid_type_slug' ), array( 'status' => 404 ) );
		}

		return true;
	}

	/**
	 * Retrieves a specific model type.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$obj = $this->manager->types()->get( $request['slug'] );

		if ( ! $obj || ( ! $obj->public && 'edit' !== $request['context'] ) ) {
			return new WP_Error( 'rest_invalid_type_slug', $this->manager->get_message( 'rest_invalid_type_slug' ), array( 'status' => 404 ) );
		}

		$data = $this->prepare_item_for_response( $obj, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Prepares a model type for response.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model_Type $model_type Model type object.
	 * @param WP_REST_Request                                  $request    Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $model_type, $request ) {
		$schema = $this->get_item_schema();

		$data = array();

		foreach ( $schema['properties'] as $property => $params ) {
			$data[ $property ] = $model_type->$property;
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $model_type ) );

		return $response;
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model_Type $model_type Model type object.
	 * @return array Links for the given model type.
	 */
	protected function prepare_links( $model_type ) {
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		$links = array(
			'self' => array(
				'href'   => rest_url( trailingslashit( $base ) . $model_type->slug ),
			),
			'collection' => array(
				'href'   => rest_url( $base ),
			),
			'https://api.w.org/items' => array(
				'href' => rest_url( substr( $base, 0, -6 ) . '?' . $this->manager->get_type_property() . '=' . $model_type->slug ),
			),
		);

		return $links;
	}

	/**
	 * Retrieves the model type's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Model type schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/schema#',
			'title'      => $this->manager->get_singular_slug() . '_type',
			'type'       => 'object',
			'properties' => array(
				'slug'    => array(
					'description' => $this->manager->get_message( 'rest_type_slug_description' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'label'   => array(
					'description' => $this->manager->get_message( 'rest_type_label_description' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'public'  => array(
					'description' => $this->manager->get_message( 'rest_type_public_description' ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'default' => array(
					'description' => $this->manager->get_message( 'rest_type_default_description' ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}

endif;
