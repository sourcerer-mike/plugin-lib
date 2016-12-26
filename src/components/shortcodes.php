<?php
/**
 * Shortcodes abstraction class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\Components;

use Leaves_And_Love\Plugin_Lib\Service;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\Components\Shortcodes' ) ) :

/**
 * Class for Shortcodes API
 *
 * The class is a wrapper for the WordPress Shortcodes API.
 *
 * @since 1.0.0
 *
 * @method Leaves_And_Love\Plugin_Lib\Cache    cache()
 * @method Leaves_And_Love\Plugin_Lib\Template template()
 */
class Shortcodes extends Service {
	/**
	 * Cache instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\Cache
	 */
	protected $cache;

	/**
	 * Template instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\Template
	 */
	protected $template;

	/**
	 * Added shortcodes.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $shortcode_tags = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string                              $prefix   The prefix for all shortcodes.
	 * @param Leaves_And_Love\Plugin_Lib\Cache    $cache    The Cache API instance.
	 * @param Leaves_And_Love\Plugin_Lib\Template $template The Template API instance.
	 */
	public function __construct( $prefix, $cache, $template ) {
		$this->prefix = $prefix;
		$this->cache = $cache;
		$this->template = $template;

		$this->set_services( array( 'cache', 'template' ) );
	}

	/**
	 * Adds a shortcode tag.
	 *
	 * Compared to regular WordPress shortcodes, the callback will receive an additional fourth parameter,
	 * the Template API instance. This allows the shortcode to use it for rendering.
	 *
	 * The shortcode tag will automatically be prefixed with the plugin-wide prefix.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string                                           $tag     Shortcode tag to be searched in content.
	 * @param callable                                         $func    Hook to run when shortcode is found.
	 * @param array|string                                     $args    {
	 *     Array or string of additional shortcode arguments.
	 *
	 *     @type callable $enqueue_callback Function to enqueue scripts and stylesheets this shortcode requires.
	 *                                      Default null.
	 *     @type array    $defaults         Array of default attribute values. If passed, the shortcode attributes
	 *                                      will be parsed with these before executing the callback hook so that
	 *                                      you do not need to take care of that in the shortcode hook. Default
	 *                                      false.
	 *     @type bool     $cache            Whether to cache the output of this shortcode. Default false.
	 *     @type int      $cache_expiration Time in seconds for which the shortcode should be cached. This only
	 *                                      takes effect if $cache is true. Default is 86400 (one day).
	 * }
	 * @return bool True on success, false on failure.
	 */
	public function add( $tag, $func, $args = array() ) {
		if ( empty( $tag ) ) {
			return false;
		}

		$tag = $this->prefix . $tag;

		$this->shortcode_tags[ $tag ] = new Shortcode( $tag, $func, $args, $this );
		add_shortcode( $tag, array( $this->shortcode_tags[ $tag ], 'run' ) );

		return true;
	}

	/**
	 * Checks whether a specific shortcode tag exists.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag Shortcode tag to check for.
	 * @return bool True if the shortcode tag exists, otherwise false.
	 */
	public function has( $tag ) {
		$tag = $this->prefix . $tag;

		return isset( $this->shortcode_tags[ $tag ] ) && shortcode_exists( $tag );
	}

	/**
	 * Retrieves a shortcode object.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag Shortcode tag to retrieve object for.
	 * @return Leaves_And_Love\Plugin_Lib\Components\Shortcode|null Shortcode object, or null if not exists.
	 */
	public function get( $tag ) {
		if ( ! $this->has( $tag ) ) {
			return null;
		}

		$tag = $this->prefix . $tag;

		return $this->shortcode_tags[ $tag ];
	}

	/**
	 * Removes a shortcode tag.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $tag Shortcode tag to remove.
	 * @return bool True on success, false on failure.
	 */
	public function remove( $tag ) {
		$tag = $this->prefix . $tag;

		if ( ! isset( $this->shortcode_tags[ $tag ] ) ) {
			return false;
		}

		remove_shortcode( $tag );
		unset( $this->shortcode_tags[ $tag ] );

		return true;
	}
}

endif;