<?php
/**
 * Extension class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\Components;

use Leaves_And_Love\Plugin_Lib\Service;
use Leaves_And_Love\Plugin_Lib\Traits\Hook_Service_Trait;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\Components\Extension' ) ) :

/**
 * Class for an extension
 *
 * This class represents an extension.
 *
 * @since 1.0.0
 */
abstract class Extension extends Service {
	use Hook_Service_Trait;

	/**
	 * Path to the extension's main file.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $main_file;

	/**
	 * Relative base path to the other files of this extension.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $basedir_relative;

	/**
	 * The extension vendor name.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $vendor_name;

	/**
	 * The extension project name.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $project_name;

	/**
	 * Parent plugin instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love_Plugin
	 */
	protected $parent_plugin = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string                 $name             Extension name.
	 * @param Leaves_And_Love_Plugin $plugin           Parent plugin instance.
	 * @param string                 $main file        Path to the extension's main file.
	 * @param string                 $basedir_relative Optional. The relative base path to the other
	 *                                                 files of this extension. Default empty.
	 */
	public function __construct( $name, $plugin, $main_file, $basedir_relative = '' ) {
		$this->parent_plugin = $plugin;
		$this->main_file = $main_file;
		$this->basedir_relative = $basedir_relative;

		$this->set_prefix( $name . '_' );

		$this->load_base_properties();
		$this->load_textdomain();
	}

	/**
	 * Returns the full path to a relative path for an extension file or directory.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $rel_path Relative path.
	 * @return string Full path.
	 */
	public function path( $rel_path ) {
		return plugin_dir_path( $this->main_file ) . $this->basedir_relative . ltrim( $rel_path, '/' );
	}

	/**
	 * Returns the full URL to a relative path for an extension file or directory.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $rel_path Relative path.
	 * @return string Full URL.
	 */
	public function url( $rel_path ) {
		return plugin_dir_url( $this->main_file ) . $this->basedir_relative . ltrim( $rel_path, '/' );
	}

	/**
	 * Loads the plugin by registering the autoloader and instantiating the general classes.
	 *
	 * This method can only be executed once.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function load() {
		if ( did_action( $this->get_prefix() . 'loaded' ) ) {
			return;
		}

		if ( ! $this->dependencies_loaded() ) {
			$vendor_autoload = $this->path( 'vendor/autoload.php' );
			if ( file_exists( $vendor_autoload ) ) {
				require_once $vendor_autoload;
			}
		}

		Leaves_And_Love_Autoloader::register_namespace( $this->vendor_name, $this->project_name, $this->path( 'src/' ) );

		$this->instantiate_services();

		$this->setup_hooks();

		/**
		 * Fires after the plugin has loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param Leaves_And_Love_Plugin $plugin The plugin instance.
		 */
		do_action( $this->get_prefix() . 'loaded', $this );
	}

	/**
	 * Checks whether the extension can run on this setup.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return WP_Error|null Error object if the extension cannot run on this setup, null otherwise.
	 */
	public function check() {
		return null;
	}

	/**
	 * Loads the base properties of the class.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected abstract function load_base_properties();

	/**
	 * Loads the extension's textdomain.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected abstract function load_textdomain();

	/**
	 * Instantiates the extension services.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected abstract function instantiate_services();

	/**
	 * Checks whether the dependencies have been loaded.
	 *
	 * If this method returns false, the extension will attempt to require the composer-generated
	 * autoloader script. If your extension uses additional dependencies, override this method with
	 * a check whether these dependencies already exist.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return bool True if the dependencies are loaded, false otherwise.
	 */
	protected function dependencies_loaded() {
		return true;
	}
}

endif;
