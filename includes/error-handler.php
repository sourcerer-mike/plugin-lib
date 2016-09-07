<?php
/**
 * Error handler class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\Error_Handler' ) ) :

/**
 * Class for error handling
 *
 * This class handles errors triggered by incorrect plugin usage.
 *
 * @since 1.0.0
 */
class Error_Handler extends Service {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $messages Messages to print to the user.
	 */
	public function __construct( $messages ) {
		$this->set_messages( $messages );
	}

	/**
	 * Marks a function as deprecated and inform when it has been used.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $function    The function that was called.
	 * @param string $version     The version of the plugin that deprecated the function.
	 * @param string $replacement Optional. The function that should have been called. Default null.
	 */
	public function deprecated_function( $function, $version, $replacement = null ) {
		do_action( 'deprecated_function_run', $function, $replacement, $version );

		if ( WP_DEBUG && apply_filters( 'deprecated_function_trigger_error', true ) ) {
			if ( ! is_null( $replacement ) ) {
				trigger_error( sprintf( $this->messages['deprecated_function'], $function, $version, $replacement ) );
			} else {
				trigger_error( sprintf( $this->messages['deprecated_function_no_alt'], $function, $version ) );
			}
		}
	}

	/**
	 * Marks a function argument as deprecated and inform when it has been used.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $function The function that was called.
	 * @param string $version  The version of the plugin that deprecated the argument used.
	 * @param string $message  Optional. A message regarding the change. Default null.
	 */
	public function deprecated_argument( $function, $version, $message = null ) {
		do_action( 'deprecated_argument_run', $function, $message, $version );

		if ( WP_DEBUG && apply_filters( 'deprecated_argument_trigger_error', true ) ) {
			if ( ! is_null( $message ) ) {
				trigger_error( sprintf( $this->messages['deprecated_argument'], $function, $version, $message ) );
			} else {
				trigger_error( sprintf( $this->messages['deprecated_argument_no_alt'], $function, $version ) );
			}
		}
	}

	/**
	 * Marks a deprecated action or filter hook as deprecated and throws a notice.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $hook        The hook that was used.
	 * @param string $version     The version of the plugin that deprecated the hook.
	 * @param string $replacement Optional. The hook that should have been used.
	 * @param string $message     Optional. A message regarding the change.
	 */
	public function deprecated_hook( $hook, $version, $replacement = null, $message = null ) {
		do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

		if ( WP_DEBUG && apply_filters( 'deprecated_hook_trigger_error', true ) ) {
			$message = empty( $message ) ? '' : ' ' . $message;

			if ( ! is_null( $replacement ) ) {
				trigger_error( sprintf( $this->messages['deprecated_hook'], $hook, $version, $replacement ) . $message );
			} else {
				trigger_error( sprintf( $this->messages['deprecated_hook_no_alt'], $hook, $version ) . $message );
			}
		}
	}

	/**
	 * Marks something as being incorrectly called.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $function The function that was called.
	 * @param string $message  A message explaining what has been done incorrectly.
	 * @param string $version  The version of the plugin where the message was added.
	 */
	public function doing_it_wrong( $function, $message, $version ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );

		if ( WP_DEBUG && apply_filters( 'doing_it_wrong_trigger_error', true ) ) {
			if ( is_null( $version ) ) {
				$version = '';
			} else {
				$version = ' ' . sprintf( $this->messages['added_in_version'], $version );
			}

			trigger_error( sprintf( $this->messages['called_incorrectly'], $function, $message . $version ) );
		}
	}
}

endif;