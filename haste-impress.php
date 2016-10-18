<?php
/**
* Plugin Name: Haste Impress
* Plugin URI: https://wordpress.org/plugins/haste-impress/
* Description: A Plugin to create Impress.js presentations based on custom post type Steps.
* Version: 1.0
* Author: Haste - design and technology, Anyssa Ferreira, Allyson Souza
* Author URI: http://www.hastedesign.com.br
* License: GPL2
* Text Domain: hasteimpress
*/

namespace HasteImpress;

if( !defined( 'ABSPATH' ) ) {
	exit;
}

if( !class_exists( 'HasteImpress' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '/core/classes/class-metabox.php';

	class HasteImpress
	{
		/**
		 * The single instance of the class
		 * @var HasteImpress
		 */
		protected static $instance;

		/**
		 * The plugin path
		 * @var string
		 */
		private $plugin_path;

		/**
		 * The plugin url
		 * @var string
		 */
		private $plugin_url;

		/**
		 * Headers
		 * @var array
		 */
		private $headers = array( 'Name' => 'Skin Name' );

		/**
		 * Skins
		 * @var array
		 */
		private $skins = array();

		/**
		 * HasteImpress constructor
		 */
		public function __construct()
		{
			$this->plugin_path = plugin_dir_path( __FILE__ );
			$this->plugin_url  = plugin_dir_url( __FILE__ );

			add_action( 'init', array( &$this, 'load_plugin_textdomain' ) );
			add_action( 'init', array( &$this, 'create_post_type' ), 80 );
			add_action( 'init', array( &$this, 'create_taxionomies' ), 90 );
			add_action( 'init', array( &$this, 'create_metaboxes' ), 100 );
			add_action( 'init', array( &$this, 'wip_add_editor_styles' ), 100 );
			add_action( 'wp_enqueue_scripts', array( &$this, 'hasteimpress_scripts' ), 200 );
			add_action( 'admin_enqueue_scripts', array( &$this, 'register_skins' ) );
			add_action( 'pre_get_posts', array( &$this, 'presentation_loops' ) );
			add_action( 'presentations_edit_form_fields', array( &$this, 'presentations_taxonomy_custom_fields' ), 10, 2 );
			add_action( 'edited_presentations', array( &$this, 'save_taxonomy_custom_fields' ), 10, 2 );
			add_filter( 'template_include', array( &$this, 'impress_templates' ), 99 );
			add_filter( 'mce_buttons_2', array( &$this, 'add_editor_style_select' ) );
		}

		/**
		 * Return HasteImpress instance
		 *
		 * @return HasteImpress	Main instance.
		 */
		public static function init()
		{
			is_null( self::$instance ) AND self::$instance = new self;
			return self::$instance;
		}

		/**
		 * Creates the steps post type
		 */
		public function create_post_type()
		{
			$labels = array(
				'name' => __( 'Impress Steps', 'hasteimpress' ),
				'singular_name' => __( 'Impress Step', 'hasteimpress' ),
				'menu_name' => __( 'Impress Steps', 'hasteimpress' ),
				'name_admin_bar' => __( 'Impress', 'hasteimpress' ),
				'add_new' => __( 'Add New', 'hasteimpress' ),
				'add_new_item' => __( 'Add New Step', 'hasteimpress' ),
				'new_item' => __( 'New Step', 'hasteimpress' ),
				'edit_item' => __( 'Edit Step', 'hasteimpress' ),
				'view_item' => __( 'View Step', 'hasteimpress' ),
				'all_items' => __( 'All Steps', 'hasteimpress' ),
				'search_items' => __( 'Search Steps', 'hasteimpress' ),
				'parent_item_colon' => '',
				'not_found' => __( 'No steps found.', 'hasteimpress' ),
				'not_found_in_trash' => __( 'No steps found in trash.', 'hasteimpress' )
			);

			$args = array(
				'labels' => $labels,
				'public' => true,
				'exclude_from_search' => false,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_nav_menus' => false,
				'show_in_menu' => true,
				'query_var' => true,
				'capability_type' => 'post',
				'has_archive' => true,
				'hierarchical' => false,
				'menu_position' => 60,
				'has_archive' => true,
				'can_export' => false,
				'menu_icon' => 'dashicons-slides',
				'supports' => array(
					'title',
					'editor',
					'author',
					'thumbnail',
					'page-attributes',
					'post-formats'
				)
			);

			register_post_type( 'steps', $args );
		}

		/**
		 * Creates the plugin taxionomies [presentation]
		 */
		public function create_taxionomies()
		{
			$labels = array(
				 'name' => __( 'Presentations', 'hasteimpress' ),
				'singular_name' => __( 'Presentation', 'hasteimpress' ),
				'search_items' => __( 'Search Presentations', 'hasteimpress' ),
				'popular_items' => __( 'Popular Presentations', 'hasteimpress' ),
				'all_items' => __( 'All Presentations', 'hasteimpress' ),
				'parent_item' => null,
				'parent_item_colon' => null,
				'edit_item' => __( 'Edit Presentations', 'hasteimpress' ),
				'update_item' => __( 'Update Presentation', 'hasteimpress' ),
				'add_new_item' => __( 'Add New Presentation', 'hasteimpress' ),
				'new_item_name' => __( 'New Presentation Name', 'hasteimpress' ),
				'separate_items_with_commas' => __( 'Separate Presentations with commas', 'hasteimpress' ),
				'add_or_remove_items' => __( 'Add or remove presentations', 'hasteimpress' ),
				'choose_from_most_used' => __( 'Choose from the most used presentations', 'hasteimpress' ),
				'not_found' => __( 'No presentations found.', 'hasteimpress' ),
				'menu_name' => __( 'Presentations', 'hasteimpress' )
			);
			$args = array(
				'hierarchical' => false,
				'labels' => $labels,
				'show_ui' => true,
				'show_admin_column' => true,
				'query_var' => true,
				'rewrite' => array(
					 'slug' => 'presentations'
				)
			);
			register_taxonomy( 'presentations', 'steps', $args );
		}

		/**
		 * Create impress configuration meta boxes
		 */
		public function create_metaboxes()
		{
			$impress_metabox = new \Odin\Odin_Metabox( 'impress_config', // Slug/ID of the Metabox (Required)
				__( 'Step Configuration', 'hasteimpress' ), // Metabox name (Required)
				'steps', // Slug of Post Type (Optional)
				'normal', // Context (options: normal, advanced, or side) (Optional)
				'high' // Priority (options: high, core, default or low) (Optional)
			);

			$impress_metabox->set_fields( array(
				 array(
					'id' => 'head-text', // Required
					'label' => __( 'Head Text', 'hasteimpress' ), // Required
					'type' => 'input', // Required
					'description' => __( 'Text that is shown before the title.', 'hasteimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'text'
					)
				),
				array(
					'id' => 'data-x', // Required
					'label' => __( 'Data-x', 'hasteimpress' ), // Required
					'type' => 'input', // Required
					'default' => 0, // Optional
					'description' => __( 'Enter the x position of this step.', 'hasteimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						'type' => 'number',
						'max' => 99999,
						'min' => -99999
					)
				),
				array(
					'id' => 'data-y', // Required
					'label' => __( 'Data-y', 'hasteimpress' ), // Required
					'type' => 'input', // Required
					'default' => 0, // Optional
					'description' => __( 'Enter the y position of this step.', 'hasteimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						'type' => 'number',
						'max' => 99999,
						'min' => -99999
					)
				),
				array(
					'id' => 'data-z', // Required
					'label' => __( 'Data-z', 'hasteimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the z position of this step.', 'hasteimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						'type' => 'number',
						'max' => 99999,
						'min' => -99999
					)
				),
				array(
					'id' => 'data-rotate-x', // Required
					'label' => __( 'Data-Rotate-x', 'hasteimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the x rotation of this step.', 'hasteimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						'type' => 'number',
						'max' => 360,
						'min' => -360
					)
				),
				array(
					'id' => 'data-rotate-y', // Required
					'label' => __( 'Data-Rotate-y', 'hasteimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the y rotation of this step.', 'hasteimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						'type' => 'number',
						'max' => 360,
						'min' => -360
					)
				),
				array(
					'id' => 'data-rotate-z', // Required
					'label' => __( 'Data-Rotate-z', 'hasteimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the z rotation of this step.', 'hasteimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						'type' => 'number',
						'max' => 360,
						'min' => -360
					)
				),
				array(
					'id' => 'data-scale', // Required
					'label' => __( 'Data-Scale', 'hasteimpress' ), // Required
					'type' => 'input', // Required
					'description' => __( 'Enter the scale of this step.', 'hasteimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						'type' => 'number',
						'step' => 'any',
						'max' => 9999,
						'min' => -9999
					)
				),
				array(
					'id' => 'step-class', // Required
					'label' => __( 'Step Class', 'hasteimpress' ), // Required
					'type' => 'text', // Required
					'description' => __( 'Enter the class to be added to step.', 'hasteimpress' ) // Optional
				),
				array(
					'id' => 'step-format', // Obrigatório
					'label' => __( 'Step Format <br/>', 'hasteimpress' ), // Obrigatório
					'type' => 'radio', // Obrigatório
					// 'attributes' => array(), // Opcional (atributos para input HTML/HTML5)
					'default' => 'default', // Opcional
					'description' => __( 'Select the step format.', 'hasteimpress' ), // Opcional
					'options' => array( // Obrigatório (adicione aque os ids e títulos)
						'default' => __( 'Default', 'hasteimpress' ),
						'image' => __( 'Image', 'hasteimpress' ),
						'gallery' => __( 'Gallery', 'hasteimpress' ),
						'video' => __( 'Video', 'hasteimpress' ),
						'quote' => __( 'Quote', 'hasteimpress' ),
						'title' => __( 'Title only', 'hasteimpress' )
					)
				),
				array(
					'id' => 'step-empty', // Required
					'label' => __( 'Empty Step', 'hasteimpress' ), // Required
					'type' => 'checkbox', // Required
					// 'attributes' => array(), // Optional (html input elements)
					// 'default'    => '', // Optional (1 for checked)
					'description' => __( 'Mark this if this step will be an empty step of your presentation.', 'hasteimpress' ) // Optional
				),
				array(
				    'id'          => 'step_bgimage', // Obrigatório
				    'label'       => __( 'Background Image', 'odin' ), // Obrigatório
				    'type'        => 'checkbox', // Obrigatório
				    'default'     => '', // Opcional (deve ser o id de uma imagem em mídia)
				    'description' => __( 'Show featured image as a background image for this step.', 'hasteimpress' ), // Opcional
				)
			) );
		}

		/**
		 * Filter presentation loops in pre_get_posts
		 * @param  WP_Query $query current loop query
		 */
		public function presentation_loops( $query )
		{
			if( $query->is_main_query() ) {
				if( $query->is_tax( 'presentations' ) ) {
					$query->set( 'orderby', 'date' );
					$query->set( 'posts_per_page', -1 );
					$query->set( 'order', 'ASC' );
				}
				if( $query->is_post_type_archive( 'steps' ) ) {
					$query->set( 'orderby', 'date' );
					$query->set( 'posts_per_page', -1 );
				}
			}
		}

		/**
		 * Enqueue HasteImpress scripts
		 */
		public function hasteimpress_scripts()
		{
			if( is_tax( 'presentations' ) || ( is_single() && get_post_type() == 'steps' ) || ( is_archive() && get_post_type() == 'steps' ) ) {
				global $wp_styles;
				$num = count( $wp_styles->queue );

				for( $i = 0; $i < $num; $i++ ) {
					if( $wp_styles->queue[ $i ] != 'admin-bar' ) {
						unset( $wp_styles->queue[ $i ] );
					}
				}

				wp_enqueue_script( 'impress', plugins_url( 'assets/js/libs/impress.js', __FILE__ ), array(), null, 'all' );
				wp_enqueue_script( 'hasteimpress', plugins_url( 'assets/js/libs/hasteimpress.js', __FILE__ ), array( 'impress' ), null, 'all' );
				wp_enqueue_style( 'default', $this->plugin_url . '/assets/css/default.css' );

				$queried = get_queried_object();
				$term_id = isset( $queried->term_id ) ? $queried->term_id : '';

				if( !empty( $term_id ) ) {
					$skin = get_option( "presentations_term_" . $term_id );
					wp_enqueue_style( $skin, $this->plugin_url . 'assets/css/skins/' . $skin . '.css' );
				} else {
					$terms = get_terms( 'presentations' );

					if( !empty( $terms ) ) {
						$skin = get_option( 'presentations_term_' . $terms[ 0 ]->term_id );
						wp_enqueue_style( $skin, $this->plugin_url . 'assets/css/skins/' . $skin . '.css' );
					}
				}
			}
		}

		/**
		 * Add editor style
		 */
		function wip_add_editor_styles()
		{
			add_editor_style( '/assets/css/editor-style.css' );
		}

		/**
		 * Register presentations skins
		 */
		public function register_skins()
		{
			wp_register_style( 'default', $this->plugin_url . '/assets/css/default.css' );
			$skin_files = glob( $this->plugin_path . "assets/css/skins/*.css" );

			foreach( $skin_files as $skin ) {
				if( file_exists( $skin ) ) {
					array_push( $this->skins, get_file_data( $skin, $this->headers ) );
				}
			}

			foreach( $this->skins as $skin ) {
				wp_register_style( strtolower( $skin[ 'Name' ] ), $this->plugin_url . '/assets/css/skins/' . strtolower( $skin[ 'Name' ] ) . '.css' );
			}
		}

		/**
		 * Overrides theme templates for given custom post type
		 * @param  string $template
		 */
		public function impress_templates( $template )
		{
			if( is_single() && get_post_type() == 'steps' ) {
				$new_template = plugin_dir_path( __FILE__ ) . '/templates/single-steps.php';
				return $new_template;
			} else if( is_tax( 'presentations' ) ) {
				$new_template = plugin_dir_path( __FILE__ ) . '/templates/taxonomy-presentations.php';
				return $new_template;
			} else if( is_archive() && get_post_type() == 'steps' ) {
				$new_template = plugin_dir_path( __FILE__ ) . '/templates/archive-steps.php';
				return $new_template;
			}
			return $template;
		}

		/**
		 * Add custom fields to presentations custom taxonomy
		 * @param  string $term
		 */
		public function presentations_taxonomy_custom_fields( $term )
		{
			// Check for existing taxonomy meta for the term you're editing
			$t_id      = $term->term_id; // Get the ID of the term you're editing
			$term_meta = get_option( "presentations_term_" . $t_id ); // Do the check
			echo $term_meta;
			include( $this->plugin_path . 'includes/skin-select.php' );
		}

		/**
		 * Saves custom taxonomy fields
		 * @param  int $term_id
		 */
		public function save_taxonomy_custom_fields( $term_id )
		{
			if( isset( $_POST[ 'term_meta' ] ) ) {
				$t_id      = $term_id;
				$term_meta = get_option( "presentations_term_" . $t_id );

				//save the option array
				update_option( "presentations_term_" . $t_id, $_POST[ 'term_meta' ] );
			}
		}

		/**
		 * Load the plugin textdomain for translation
		 */
		public function load_plugin_textdomain()
		{
			load_plugin_textdomain( 'hasteimpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Delete the steps custom post type from database
		 */
		public static function on_uninstall()
		{
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->posts WHERE post_type = %s", 'steps' ) );
			return;
		}

		/**
		 * Add editor style select
		 */
		public function add_editor_style_select( $buttons )
		{
			//Callback function to insert 'styleselect' into the $buttons array
			array_unshift( $buttons, 'styleselect' );
			return $buttons;
		}
	}
}

register_uninstall_hook( __FILE__, array( 'HasteImpress', 'on_uninstall' ) );

add_action( 'plugins_loaded', array( '\HasteImpress\HasteImpress', 'init' ) );
?>
