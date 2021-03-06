<?php
/**
 * Range field class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\Fields;

use Leaves_And_Love\Plugin_Lib\Fields\Number;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\Fields\Range' ) ) :

/**
 * Class for a range field.
 *
 * @since 1.0.0
 */
class Range extends Number {
	/**
	 * Field type identifier.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $slug = 'range';

	/**
	 * Type attribute for the input.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $type = 'range';
}

endif;
