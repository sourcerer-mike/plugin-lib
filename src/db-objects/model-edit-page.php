<?php
/**
 * Edit page class
 *
 * @package LeavesAndLovePluginLib
 * @since 1.0.0
 */

namespace Leaves_And_Love\Plugin_Lib\DB_Objects;

use Leaves_And_Love\Plugin_Lib\Fields\Field_Manager;
use Leaves_And_Love\Plugin_Lib\Assets;
use WP_Error;

if ( ! class_exists( 'Leaves_And_Love\Plugin_Lib\DB_Objects\Model_Edit_Page' ) ) :

/**
 * Class for a model edit page.
 *
 * @since 1.0.0
 */
abstract class Model_Edit_Page extends Manager_Page {
	/**
	 * The current model.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\DB_Objects\Model
	 */
	protected $model;

	/**
	 * Whether the page is currently in update scope.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var bool
	 */
	protected $is_update = false;

	/**
	 * The slug of the admin page to list models.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var string
	 */
	protected $list_page_slug = '';

	/**
	 * Array of tabs as `$id => $args` pairs.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $tabs = array();

	/**
	 * Array of sections as `$id => $args` pairs.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $sections = array();

	/**
	 * Field manager instance.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var Leaves_And_Love\Plugin_Lib\Fields\Field_Manager
	 */
	protected $field_manager = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string                                            $slug          Page slug.
	 * @param Leaves_And_Love\Plugin_Lib\Components\Admin_Pages $manager       Admin page manager instance.
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager     $model_manager Model manager instance.
	 */
	public function __construct( $slug, $manager, $model_manager ) {
		parent::__construct( $slug, $manager, $model_manager );

		if ( empty( $this->title ) ) {
			$this->title = $this->model_manager->get_message( 'edit_page_item' );
		}

		if ( empty( $this->menu_title ) ) {
			$this->menu_title = $this->model_manager->get_message( 'edit_page_add_new' );
		}

		if ( empty( $this->capability ) ) {
			$capabilities = $this->model_manager->capabilities();
			if ( $capabilities ) {
				$base_capabilities = $capabilities->get_capabilities( 'base' );

				$this->capability = $base_capabilities['create_items'];
			}
		}

		if ( empty( $this->list_page_slug ) ) {
			$this->list_page_slug = $this->manager->get_prefix() . 'list_' . $this->model_manager->get_plural_slug();
		}

		$this->field_manager = new Field_Manager( $this->manager->get_prefix(), array(
			'ajax'          => $this->manager->ajax(),
			'assets'        => $this->manager->assets(),
			'error_handler' => $this->manager->error_handler(),
		), array(
			'get_value_callback'         => array( $this, 'get_model_field_value' ),
			'get_value_callback_args'    => array( '{id}' ),
			'update_value_callback'      => array( $this, 'update_model_field_value' ),
			'update_value_callback_args' => array( '{id}', '{value}' ),
			'name_prefix'                => '',
		) );

		if ( method_exists( $this->model_manager, 'get_slug_property' ) ) {
			$this->manager->ajax()->register_action( 'model_generate_slug', array( $this, 'ajax_model_generate_slug' ) );
			$this->manager->ajax()->register_action( 'model_verify_slug', array( $this, 'ajax_model_verify_slug' ) );
		}

		$this->add_page_content();
	}

	/**
	 * Adds a tab to the model edit page.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $id   Section identifier.
	 * @param array  $args {
	 *     Optional. Section arguments.
	 *
	 *     @type string $title       Section title.
	 *     @type string $description Section description. Default empty.
	 * }
	 */
	public function add_tab( $id, $args = array() ) {
		$this->tabs[ $id ] = wp_parse_args( $args, array(
			'title'       => '',
			'description' => '',
		) );
	}

	/**
	 * Adds a section to the model edit page.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $id   Section identifier.
	 * @param array  $args {
	 *     Optional. Section arguments.
	 *
	 *     @type string $tab         Tab identifier this field belongs to. Default empty.
	 *     @type string $title       Section title.
	 *     @type string $description Section description. Default empty.
	 * }
	 */
	public function add_section( $id, $args = array() ) {
		$this->sections[ $id ] = wp_parse_args( $args, array(
			'tab'         => '',
			'title'       => '',
			'description' => '',
		) );
	}

	/**
	 * Adds a field control to the model edit page.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $id      Field identifier.
	 * @param string $type    Identifier of the type.
	 * @param array  $args    {
	 *     Optional. Field arguments. See the field class constructor for further arguments.
	 *
	 *     @type string $section       Section identifier this field belongs to. The section must be
	 *                                 already added prior to adding the field. Default empty.
	 *     @type string $label         Field label. Default empty.
	 *     @type string $description   Field description. Default empty.
	 *     @type mixed  $default       Default value for the field. Default null.
	 *     @type array  $input_classes Array of CSS classes for the field input. Default empty array.
	 *     @type array  $label_classes Array of CSS classes for the field label. Default empty array.
	 *     @type array  $input_attrs   Array of additional input attributes as `$key => $value` pairs.
	 *                                 Default empty array.
	 * }
	 */
	public function add_field( $id, $type, $args = array() ) {
		if ( empty( $args['section'] ) || ! isset( $this->sections[ $args['section'] ] ) ) {
			return;
		}

		if ( 0 !== strpos( $args['section'], $this->sections[ $args['section'] ]['tab'] . '-' ) ) {
			$args['section'] = $this->sections[ $args['section'] ]['tab'] . '-' . $args['section'];
		}

		$this->field_manager->add( $id, $type, $args );
	}

	/**
	 * Returns a specific field value of the current model.
	 *
	 * Used as callback for the field manager.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $field_slug Field slug to retrieve its value.
	 * @return mixed Field value, or null if not set.
	 */
	public function get_model_field_value( $field_slug ) {
		return $this->model->$field_slug;
	}

	/**
	 * Updates a specific field value of the current model.
	 *
	 * Used as callback for the field manager.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $field_slug Field slug to update its value.
	 * @param mixed  $value      Field value to set.
	 */
	public function update_model_field_value( $field_slug, $value ) {
		$this->model->$field_slug = $value;
	}

	/**
	 * Handles a request to the page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function handle_request() {
		$capabilities = $this->model_manager->capabilities();
		$primary_property = $this->model_manager->get_primary_property();

		if ( isset( $_REQUEST[ $primary_property ] ) ) {
			$id = absint( $_REQUEST[ $primary_property ] );

			$this->model = $this->model_manager->get( $id );
			if ( null === $this->model ) {
				wp_die( $this->model_manager->get_message( 'edit_page_invalid_id' ), 400 );
			}

			if ( ! $capabilities || ! $capabilities->user_can_edit( null, $id ) ) {
				wp_die( $this->model_manager->get_message( 'edit_page_cannot_edit_item' ), 403 );
			}

			$this->is_update = true;
		} else {
			if ( ! $capabilities || ! $capabilities->user_can_create() ) {
				wp_die( $this->model_manager->get_message( 'edit_page_cannot_create_item' ), 403 );
			}

			$this->model = $this->model_manager->create();

			if ( method_exists( $this->model_manager, 'get_type_property' ) ) {
				$type_property = $this->model_manager->get_type_property();
				$this->model->$type_property = $this->model_manager->types()->get_default();
			}

			if ( method_exists( $this->model_manager, 'get_status_property' ) ) {
				$status_property = $this->model_manager->get_status_property();
				$this->model->$status_property = $this->model_manager->statuses()->get_default();
			}
		}

		$this->handle_actions();
		$this->clean_referer();
		$this->setup_screen( get_current_screen() );
	}

	/**
	 * Enqueues assets to load on the page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function enqueue_assets() {
		$this->field_manager->enqueue();

		$assets = Assets::get_library_instance();

		$primary_property = $this->model_manager->get_primary_property();

		$data = array(
			'ajax_prefix'            => $this->manager->ajax()->get_prefix(),
			'primary_property'       => $primary_property,
			'primary_property_value' => $this->model->$primary_property,
			'i18n'                   => array(
				'ok'     => $this->model_manager->get_message( 'edit_page_ok' ),
				'cancel' => $this->model_manager->get_message( 'edit_page_cancel' ),
			),
		);
		if ( method_exists( $this->model_manager, 'get_slug_property' ) ) {
			$data['generate_slug_nonce'] = $this->manager->ajax()->get_nonce( 'model_generate_slug' );
			$data['verify_slug_nonce']   = $this->manager->ajax()->get_nonce( 'model_verify_slug' );
			$data['slug_property']       = $this->model_manager->get_slug_property();
			$data['slug_dependencies']   = $this->model_manager->get_slug_generator_dependencies();
		}

		$assets->register_style( 'edit-model', 'assets/dist/css/edit-model.css', array(
			'ver'     => \Leaves_And_Love_Plugin_Loader::VERSION,
			'enqueue' => true,
		) );

		$assets->register_script( 'edit-model', 'assets/dist/js/edit-model.js', array(
			'deps'          => array( 'jquery' ),
			'ver'           => \Leaves_And_Love_Plugin_Loader::VERSION,
			'in_footer'     => true,
			'enqueue'       => true,
			'localize_name' => 'pluginLibEditModelData',
			'localize_data' => $data,
		) );
	}

	/**
	 * Renders buttons for frontend viewing and previewing.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param int|null                                      $id      Current model ID, or null if new model.
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model   $model   Current model object.
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager Model manager instance.
	 */
	public function view_buttons( $id, $model, $manager ) {
		$view_routing = $manager->view_routing();
		if ( ! $view_routing ) {
			return;
		}

		$preview_text = $id ? $manager->get_message( 'edit_page_preview_changes' ) : $manager->get_message( 'edit_page_preview' );

		$permalink = $view_routing->get_model_permalink( $model );

		if ( ! empty( $permalink ) ) : ?>
			<div id="view-action">
				<a class="button" href="<?php echo esc_url( $permalink ); ?>" target="_blank"><?php echo $manager->get_message( 'edit_page_view' ); ?></a>
			</div>
		<?php endif; ?>
		<div id="preview-action">
			<button type="button" id="post-preview" class="preview button"><?php echo $preview_text; ?></button>
		</div>
		<?php
	}

	/**
	 * Renders a status select field.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param int|null                                      $id      Current model ID, or null if new model.
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model   $model   Current model object.
	 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager Model manager instance.
	 */
	public function status_select( $id, $model, $manager ) {
		if ( ! method_exists( $manager, 'get_status_property' ) ) {
			return;
		}

		$capabilities = $manager->capabilities();

		$status_property = $manager->get_status_property();
		$current_status = $model->$status_property;

		if ( $capabilities && $capabilities->user_can_publish( null, $id ) ) {
			$statuses = $manager->statuses()->query();
		} else {
			$statuses = $manager->statuses()->query( array(
				'public'   => false,
				'slug'     => $current_status,
				'operator' => 'OR',
			) );
		}

		if ( empty( $statuses ) ) {
			return;
		}

		?>
		<div class="misc-pub-section">
			<div id="post-status-select">
				<label for="post-status"><?php echo $manager->get_message( 'edit_page_status_label' ); ?></label>
				<select id="post-status" name="<?php echo esc_attr( $status_property ); ?>">
					<?php foreach ( $statuses as $status ) : ?>
						<option value="<?php echo esc_attr( $status->slug ); ?>"<?php selected( $current_status, $status->slug ); ?>><?php echo esc_html( $status->label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX callback to generate a model slug.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $request_data Request data.
	 * @return array|WP_Error Response data, or error object on failure.
	 */
	public function ajax_model_generate_slug( $request_data ) {
		if ( ! method_exists( $this->model_manager, 'get_slug_property' ) ) {
			return new WP_Error( 'ajax_item_slug_not_supported', $this->model_manager->get_message( 'ajax_item_slug_not_supported' ) );
		}

		$primary_property = $this->model_manager->get_primary_property();
		$slug_property    = $this->model_manager->get_slug_property();

		if ( isset( $request_data[ $primary_property ] ) && absint( $request_data[ $primary_property ] ) > 0 ) {
			$model = $this->model_manager->get( $request_data[ $primary_property ] );
		} else {
			$model = $this->model_manager->create();
		}

		foreach ( $this->model_manager->get_slug_generator_dependencies() as $property ) {
			if ( isset( $request_data[ $property ] ) ) {
				$model->$property = $request_data[ $property ];
			}
		}

		$generated_slug = $this->model_manager->generate_slug( $model );
		if ( empty( $generated_slug ) ) {
			return array(
				'generated' => '',
				'verified'  => '',
			);
		}

		$this->model_manager->set_unique_slug( $model, $generated_slug );

		return array(
			'generated' => $generated_slug,
			'verified'  => $model->$slug_property,
		);
	}

	/**
	 * AJAX callback to verify a model slug.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $request_data Request data.
	 * @return array|WP_Error Response data, or error object on failure.
	 */
	public function ajax_model_verify_slug( $request_data ) {
		if ( ! method_exists( $this->model_manager, 'get_slug_property' ) ) {
			return new WP_Error( 'ajax_item_slug_not_supported', $this->model_manager->get_message( 'ajax_item_slug_not_supported' ) );
		}

		$primary_property = $this->model_manager->get_primary_property();
		$slug_property    = $this->model_manager->get_slug_property();

		if ( ! isset( $request_data[ $slug_property ] ) ) {
			return new WP_Error( 'ajax_item_slug_not_passed', $this->model_manager->get_message( 'ajax_item_slug_not_passed' ) );
		}

		if ( isset( $request_data[ $primary_property ] ) && absint( $request_data[ $primary_property ] ) > 0 ) {
			$model = $this->model_manager->get( $request_data[ $primary_property ] );
		} else {
			$model = $this->model_manager->create();
		}

		$this->model_manager->set_unique_slug( $model, $request_data[ $slug_property ] );

		return array(
			'verified' => $model->$slug_property,
		);
	}

	/**
	 * Renders the edit page header.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render_header() {
		$capabilities = $this->model_manager->capabilities();

		$new_page_url = '';
		if ( $this->is_update ) {
			$new_page_url = $this->url;
		}

		?>
		<h1 class="wp-heading-inline">
			<?php echo $this->title; ?>
		</h1>

		<?php if ( ! empty( $new_page_url ) && $capabilities && $capabilities->user_can_create() ) : ?>
			<a href="<?php echo esc_url( $new_page_url ); ?>" class="page-title-action"><?php echo $this->model_manager->get_message( 'edit_page_add_new' ); ?></a>
		<?php endif; ?>

		<hr class="wp-header-end">

		<?php

		$this->print_current_message( 'action' );
	}

	/**
	 * Renders the edit page form.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render_form() {
		$id = null;
		if ( $this->is_update ) {
			$primary_property = $this->model_manager->get_primary_property();
			$id = $this->model->$primary_property;
		}

		?>
		<form id="post" action="<?php echo esc_url( $this->get_model_edit_url() ); ?>" method="post" novalidate>
			<?php wp_nonce_field( $this->get_nonce_action( 'action', $id ) ); ?>
			<input type="hidden" id="post_action" name="action" value="edit" />
			<?php if ( method_exists( $this->model_manager, 'get_slug_property' ) ) :
				$slug_property = $this->model_manager->get_slug_property();
				?>
				<input type="hidden" id="post_name" name="<?php echo esc_attr( $slug_property ); ?>" value="<?php echo esc_attr( $this->model->$slug_property ); ?>" />
			<?php endif; ?>

			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
					<div id="post-body-content">
						<?php $this->render_form_header(); ?>
						<?php $this->render_form_content(); ?>
					</div>
					<div id="postbox-container-1" class="postbox-container">
						<?php $this->render_submit_box(); ?>
					</div>
					<div id="postbox-container-2" class="postbox-container">
						<?php $this->render_advanced_form_content(); ?>
					</div>
				</div>
				<br class="clear" />
			</div>
		</form>
		<?php
	}

	/**
	 * Renders the edit page main form header.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render_form_header() {
		if ( method_exists( $this->model_manager, 'get_title_property' ) ) {
			$title_property = $this->model_manager->get_title_property();

			?>
			<div id="titlediv">
				<div id="titlewrap">
					<label id="title-prompt-text" class="screen-reader-text" for="title"><?php echo $this->model_manager->get_message( 'edit_page_title_label' ); ?></label>
					<input type="text" id="title" name="<?php echo esc_attr( $title_property ); ?>" value="<?php echo esc_attr( $this->model->$title_property ); ?>" placeholder="<?php echo esc_attr( $this->model_manager->get_message( 'edit_page_title_placeholder' ) ); ?>" size="30" />
				</div>
				<?php if ( method_exists( $this->model_manager, 'get_slug_property' ) ) :
					$slug_property = $this->model_manager->get_slug_property();
					$style = $this->model->$slug_property ? '' : ' style="display:none;"';

					$label = $this->model_manager->get_message( 'edit_page_slug_label' );
					$before_slug = $after_slug = '';

					$view_routing = $this->model_manager->view_routing();
					if ( $view_routing && '' != get_option( 'permalink_structure' ) ) {
						$permalink = $view_routing->get_model_sample_permalink_for_property( $this->model, $slug_property );

						if ( ! empty( $permalink ) ) {
							$label = $this->model_manager->get_message( 'edit_page_permalink_label' );

							list( $before_slug, $after_slug ) = explode( '%' . $slug_property . '%', $permalink, 2 );
						}
					}

					?>
					<div class="inside">
						<div id="edit-slug-box" class="hide-if-no-js"<?php echo $style; ?>>
							<strong><?php printf( '%s:', $label ); ?></strong>
							<?php echo $before_slug; ?><span id="editable-post-name"><?php echo esc_html( $this->model->$slug_property ); ?></span><?php echo $after_slug; ?>
							<span id="edit-slug-buttons">
								<button type="button" class="edit-slug button button-small hide-if-no-js">
									<?php echo $this->model_manager->get_message( 'edit_page_slug_button_label' ); ?>
								</button>
							</span>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Renders the edit page main form content.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render_form_content() {
		if ( empty( $this->tabs ) ) {
			return;
		}

		$current_tab_id = key( $this->tabs );

		$use_tabs = count( $this->tabs ) > 1;
		?>

		<div class="form-content <?php echo $use_tabs ? 'tabbed' : 'no-tabs'; ?>">

			<?php if ( $use_tabs ) : ?>
				<h2 class="nav-tab-wrapper" role="tablist">
					<?php foreach ( $this->tabs as $tab_id => $tab_args ) : ?>
						<a id="<?php echo esc_attr( 'tab-label-' . $tab_id ); ?>" class="nav-tab" href="<?php echo esc_attr( '#tab-' . $tab_id ); ?>" aria-controls="<?php echo esc_attr( 'tab-' . $tab_id ); ?>" aria-selected="<?php echo $tab_id === $current_tab_id ? 'true' : 'false'; ?>" role="tab">
							<?php echo $tab_args['title']; ?>
						</a>
					<?php endforeach; ?>
				</h2>
			<?php else : ?>
				<h2 class="screen-reader-text"><?php echo $this->tabs[ $current_tab_id ]['title']; ?></h2>
			<?php endif; ?>

			<?php foreach ( $this->tabs as $tab_id => $tab_args ) :
				$atts = $use_tabs ? ' aria-labelledby="' . esc_attr( 'tab-label-' . $tab_id ) . '" aria-hidden="' . ( $tab_id === $current_tab_id ? 'false' : 'true' ) . '" role="tabpanel"' : '';
				$sections = wp_list_filter( $this->sections, array( 'tab' => $tab_id ) );
				?>
				<div id="<?php echo esc_attr( 'tab-' . $tab_id ); ?>" class="nav-tab-panel"<?php echo $atts; ?>>

					<?php if ( ! empty( $tab_args['description'] ) ) : ?>
						<p class="description"><?php echo $tab_args['description']; ?></p>
					<?php endif; ?>

					<?php foreach ( $sections as $section_id => $section_args ) : ?>
						<div class="section">
							<h3><?php echo $section_args['title']; ?></h3>

							<?php if ( ! empty( $section_args['description'] ) ) : ?>
								<p class="description"><?php echo $section_args['description']; ?></p>
							<?php endif; ?>

							<table class="form-table">
								<?php $this->field_manager->render( $tab_id . '-' . $section_id ); ?>
							</table>
						</div>
					<?php endforeach; ?>

				</div>
			<?php endforeach; ?>

		</div>
		<?php
	}

	/**
	 * Renders the edit page advanced form content.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render_advanced_form_content() {
		// Empty method body.
	}

	/**
	 * Renders the edit page submit box.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render_submit_box() {
		$capabilities = $this->model_manager->capabilities();

		$id = null;
		$update_text = $this->model_manager->get_message( 'edit_page_create' );
		$delete_url = '';

		if ( $this->is_update ) {
			$primary_property = $this->model_manager->get_primary_property();
			$id = $this->model->$primary_property;

			$update_text = $this->model_manager->get_message( 'edit_page_update' );

			if ( $capabilities && $capabilities->user_can_delete( null, $id ) ) {
				$delete_url = add_query_arg( array(
					'action'   => 'delete',
					'_wpnonce' => wp_create_nonce( $this->get_nonce_action( 'action', $id ) ),
				), $this->get_model_edit_url() );
			}
		}

		?>
		<div id="submitdiv" class="postbox">
			<h2 class="hndle">
				<span><?php echo $this->model_manager->get_message( 'edit_page_submit_box_title' ); ?></span>
			</h2>
			<div class="inside">
				<div id="submitpost" class="submitbox">
					<div id="minor-publishing">
						<div id="minor-publishing-actions">
							<?php

							$prefix        = $this->model_manager->get_prefix();
							$singular_slug = $this->model_manager->get_singular_slug();

							if ( has_action( "{$prefix}_edit_{$singular_slug}_minor_publishing_actions" ) ) {
								/**
								 * Fires when the #minor-publishing-actions content for a model edit page should be rendered.
								 *
								 * The dynamic parts of the hook name refer to the manager's prefix and its singular slug
								 * respectively.
								 *
								 * @since 1.0.0
								 *
								 * @param int|null                                      $id      Current model ID, or null if new model.
								 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model   $model   Current model object.
								 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager Model manager instance.
								 */
								do_action( "{$prefix}_edit_{$singular_slug}_minor_publishing_actions", $id, $this->model, $this->model_manager );
							} else {
								$this->view_buttons( $id, $this->model, $this->model_manager );
							}

							?>
							<div class="clear"></div>
						</div>
						<div id="misc-publishing-actions">
							<?php

							$prefix        = $this->model_manager->get_prefix();
							$singular_slug = $this->model_manager->get_singular_slug();

							if ( has_action( "{$prefix}_edit_{$singular_slug}_misc_publishing_actions" ) ) {
								/**
								 * Fires when the #misc-publishing-actions content for a model edit page should be rendered.
								 *
								 * The dynamic parts of the hook name refer to the manager's prefix and its singular slug
								 * respectively.
								 *
								 * @since 1.0.0
								 *
								 * @param int|null                                      $id      Current model ID, or null if new model.
								 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model   $model   Current model object.
								 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager Model manager instance.
								 */
								do_action( "{$prefix}_edit_{$singular_slug}_misc_publishing_actions", $id, $this->model, $this->model_manager );
							} else {
								$this->status_select( $id, $this->model, $this->model_manager );
							}

							?>
						</div>
						<div class="clear"></div>
					</div>
					<div id="major-publishing-actions">
						<div id="delete-action">
							<?php if ( ! empty( $delete_url ) ) : ?>
								<a class="submitdelete deletion" href="<?php echo esc_url( $delete_url ); ?>"><?php echo $this->model_manager->get_message( 'edit_page_delete' ); ?></a>
							<?php endif; ?>
						</div>
						<div id="publishing-action">
							<?php submit_button( $update_text, 'primary large', 'publish', false ); ?>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handles actions when necessary.
	 *
	 * These actions are usually row actions from the models list page.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function handle_actions() {
		if ( ! isset( $_REQUEST['action'] ) ) {
			return;
		}

		$doaction = wp_unslash( $_REQUEST['action'] );

		$primary_property = $this->model_manager->get_primary_property();
		$id = $this->model->$primary_property;
		if ( ! $id ) {
			$id = null;
		}

		$sendback = $this->get_referer();
		if ( false !== strpos( $sendback, $this->slug ) ) {
			$action_type = 'action';
		} else {
			$action_type = 'row_action';

			$sendback_query = parse_url( $sendback, PHP_URL_QUERY );
			if ( ! empty( $sendback_query ) ) {
				parse_str( $sendback_query, $sendback_query_args );
				if ( ! empty( $sendback_query_args ) && ! empty( $sendback_query_args['paged'] ) ) {
					$sendback = add_query_arg( 'paged', (int) $sendback_query_args['paged'], $sendback );
				}
			}
		}

		$message = '';

		if ( $id || ( 'action' === $action_type && in_array( $doaction, array( 'edit', 'preview' ), true ) ) ) {
			check_admin_referer( $this->get_nonce_action( $action_type, $id ) );

			if ( method_exists( $this, $action_type . '_' . $doaction ) ) {
				$message = call_user_func( array( $this, $action_type . '_' . $doaction ), $id );
			} else {
				$prefix        = $this->model_manager->get_prefix();
				$singular_slug = $this->model_manager->get_singular_slug();

				/**
				 * Fires when a custom action should be handled.
				 *
				 * This is usually one of the row actions from the models list page.
				 *
				 * The hook callback should return a success message or an error object which
				 * will then be used to display feedback to the user.
				 *
				 * The dynamic parts of the hook name refer to the manager's prefix, its singular slug,
				 * one of the terms 'action' or 'row_action', and the slug of the action to handle respectively.
				 *
				 * @since 1.0.0
				 *
				 * @param string                                        $message Empty message to be modified.
				 * @param int                                           $id      Model ID.
				 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager The manager instance.
				 */
				$message = apply_filters( "{$prefix}{$singular_slug}_handle_{$action_type}_{$doaction}", $message, $id, $this->model_manager );
			}
		}

		if ( 'action' === $action_type ) {
			$id = $this->model->$primary_property;
			if ( $id > 0 ) {
				$sendback = add_query_arg( $primary_property, $id, $sendback );
			} else {
				$action_type = 'row_action';
				$sendback = add_query_arg( 'page', $this->list_page_slug, $this->url );
			}
		}

		$sendback = remove_query_arg( array( 'action' ), $sendback );

		if ( $message ) {
			$sendback = $this->redirect_with_message( $sendback, $message, $action_type );
		}

		wp_redirect( $sendback );
		exit;
	}

	/**
	 * Sets up the screen with screen reader content, options and help tabs.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param WP_Screen Current screen.
	 */
	protected function setup_screen( $screen ) {
		add_screen_option( 'layout_columns', array(
			'max'     => 2,
			'default' => 2,
		) );
	}

	/**
	 * Returns the URL to edit the current model.
	 *
	 * This method basically returns the default admin page URL with the
	 * ID of the current model appended in the query string.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return string Model edit URL.
	 */
	protected function get_model_edit_url() {
		if ( $this->is_update ) {
			$primary_property = $this->model_manager->get_primary_property();

			return add_query_arg( $primary_property, $this->model->$primary_property, $this->url );
		}

		return $this->url;
	}

	/**
	 * Handles the 'edit' action.
	 *
	 * This is the general action to update a model.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int|null $id ID of the model to update. Might be empty when creating.
	 * @return string|WP_Error Feedback message, or error object on failure.
	 */
	protected function action_edit( $id ) {
		if ( ! $id ) {
			$id = null;
		}

		$form_data = wp_unslash( $_POST );

		$result = $this->field_manager->update_values( $form_data );
		if ( ! is_wp_error( $result ) ) {
			$result = new WP_Error();
		}

		$this->validate_custom_data( $form_data, $result );

		/**
		 * Fires right before the current model will be updated.
		 *
		 * The dynamic parts of the hook name refer to the manager's prefix and its singular slug
		 * respectively.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Error                                      $result  Current error object. Might be empty.
		 * @param int|null                                      $id      Current model ID, or null if new model.
		 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model   $model   Current model object.
		 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager Model manager instance.
		 */
		do_action( "{$prefix}_edit_{$singular_slug}_before_update", $result, $id, $this->model, $this->model_manager );

		$update_result = $this->model->sync_upstream();
		if ( is_wp_error( $update_result ) ) {
			return new WP_Error( 'action_edit_item_internal_error', $this->model_manager->get_message( 'action_edit_item_internal_error' ) );
		}

		$prefix        = $this->model_manager->get_prefix();
		$singular_slug = $this->model_manager->get_singular_slug();

		/**
		 * Fires after the current model has been updated.
		 *
		 * The dynamic parts of the hook name refer to the manager's prefix and its singular slug
		 * respectively.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Error                                      $result  Current error object. Might be empty.
		 * @param int|null                                      $id      Current model ID, or null if new model.
		 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Model   $model   Current model object.
		 * @param Leaves_And_Love\Plugin_Lib\DB_Objects\Manager $manager Model manager instance.
		 */
		do_action( "{$prefix}_edit_{$singular_slug}_after_update", $result, $id, $this->model, $this->model_manager );

		if ( ! empty( $result->errors ) ) {
			$message = '<p>' . $this->model_manager->get_message( 'action_edit_item_has_errors' ) . '</p>';
			$message .= '<ul>';
			foreach ( $result->get_error_messages() as $error_message ) {
				$message .= '<li>' . $error_message . '</li>';
			}
			$message .= '</ul>';
			$message .= '<p>' . $this->model_manager->get_message( 'action_edit_item_other_fields_success' ) . '</p>';

			return new WP_Error( 'action_edit_item_has_errors', $message );
		}

		return $this->model_manager->get_message( 'action_edit_item_success' );
	}

	/**
	 * Handles the 'preview' action.
	 *
	 * This action is special as it redirects the user to the preview instead of performing an actual operation.
	 * This method always terminates the current request.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int|null $id ID of the model to update. Might be empty when creating.
	 */
	protected function action_preview( $id ) {
		if ( ! $id ) {
			$id = null;
		}

		$form_data = wp_unslash( $_POST );

		$result = $this->field_manager->update_values( $form_data );
		if ( ! is_wp_error( $result ) ) {
			$result = new WP_Error();
		}

		$this->validate_custom_data( $form_data, $result );

		$view_routing = $this->model_manager->view_routing();
		if ( ! $view_routing ) {
			wp_die( $this->model_manager->get_message( 'action_preview_item_internal_error' ) );
		}

		$preview_url = $view_routing->get_model_preview_permalink( $this->model );
		if ( empty( $preview_url ) ) {
			wp_die( $this->model_manager->get_message( 'action_preview_item_internal_error' ) );
		}

		wp_redirect( $preview_url );
		exit;
	}

	/**
	 * Validates custom model data that is not handled by the field manager.
	 *
	 * This method is called from within the 'edit' action.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param array    $form_data Form POST data.
	 * @param WP_Error $error     Error object to add errors to.
	 */
	protected function validate_custom_data( $form_data, $error ) {
		if ( method_exists( $this->model_manager, 'get_type_property' ) ) {
			$type_property = $this->model_manager->get_type_property();

			if ( isset( $form_data[ $type_property ] ) && $form_data[ $type_property ] !== $this->model->$type_property ) {
				if ( ! in_array( $form_data[ $type_property ], array_keys( $this->model_manager->types()->query() ), true ) ) {
					$error->add( 'action_edit_item_invalid_type', $this->model_manager->get_message( 'action_edit_item_invalid_type' ) );
				} else {
					$this->model->$type_property = $form_data[ $type_property ];
				}
			}
		}

		if ( method_exists( $this->model_manager, 'get_status_property' ) ) {
			$status_property = $this->model_manager->get_status_property();

			if ( isset( $form_data[ $status_property ] ) && $form_data[ $status_property ] !== $this->model->$status_property ) {
				if ( ! in_array( $form_data[ $status_property ], array_keys( $this->model_manager->statuses()->query() ), true ) ) {
					$error->add( 'action_edit_item_invalid_status', $this->model_manager->get_message( 'action_edit_item_invalid_status' ) );
				} else {
					$public_statuses = $this->model_manager->statuses()->get_public();

					$capabilities = $this->model_manager->capabilities();
					if ( in_array( $form_data[ $status_property ], $public_statuses, true ) && ( ! $capabilities || ! $capabilities->user_can_publish( null, $id ) ) ) {
						$error->add( 'action_edit_item_cannot_publish', $this->model_manager->get_message( 'action_edit_item_cannot_publish' ) );
					} else {
						$this->model->$status_property = $form_data[ $status_property ];
					}
				}
			}
		}

		if ( method_exists( $this->model_manager, 'get_title_property' ) ) {
			$title_property = $this->model_manager->get_title_property();

			if ( isset( $form_data[ $title_property ] ) ) {
				$this->model->$title_property = strip_tags( $form_data[ $title_property ] );
			}
		}

		if ( method_exists( $this->model_manager, 'get_slug_property' ) ) {
			$slug_property = $this->model_manager->get_slug_property();

			if ( isset( $form_data[ $slug_property ] ) ) {
				$this->model->$slug_property = sanitize_title( $form_data[ $slug_property ] );
			}
		}
	}

	/**
	 * Handles the 'delete' action.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int $id ID of the model to delete.
	 * @return string|WP_Error Feedback message, or error object on failure.
	 */
	protected function action_delete( $id ) {
		/* $id is always the ID of $this->model. */
		$model_name = $id;
		if ( method_exists( $this->model_manager, 'get_title_property' ) ) {
			$title_property = $this->model_manager->get_title_property();
			$model_name = $this->model->$title_property;
		}

		$capabilities = $this->model_manager->capabilities();
		if ( ! $capabilities || ! $capabilities->user_can_delete( null, $id ) ) {
			return new WP_Error( 'action_delete_item_cannot_delete', sprintf( $this->model_manager->get_message( 'action_delete_item_cannot_delete' ), $model_name ) );
		}

		$result = $this->model->delete();
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'action_delete_item_internal_error', sprintf( $this->model_manager->get_message( 'action_delete_item_internal_error' ), $model_name ) );
		}

		return sprintf( $this->model_manager->get_message( 'action_delete_item_success' ), $model_name );
	}

	/**
	 * Handles the 'delete' row action.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param int $id ID of the model to delete.
	 * @return string|WP_Error Feedback message, or error object on failure.
	 */
	protected function row_action_delete( $id ) {
		return $this->action_delete( $id );
	}

	/**
	 * Adds tabs, sections and fields to the model edit page.
	 *
	 * This method should call the methods `add_tabs()`, `add_section()` and
	 * `add_field()` to populate the page.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected abstract function add_page_content();
}

endif;
