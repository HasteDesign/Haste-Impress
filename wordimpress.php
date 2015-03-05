<?php

	/**
	* Plugin Name: WordImPress
	* Plugin URI:
	* Description: A Plugin to create Impress.js presentations based on WordPress custom post type Steps.
	* Version: 1.0
	* Author: Anyssa Ferreira, Allyson Souza
	* Author URI: http://www.hastedesign.com.br
	* License: GPL2
	*/
namespace WordImPress;

if( !defined( 'ABSPATH' ) ) {
	exit;
}

if( !class_exists( 'WordImPress' ) ) {
	require_once plugin_dir_path( __FILE__ ) . '/core/classes/class-metabox.php';
	
	
	class WordImPress
	{
		protected static $instance;
		private $plugin_path;
		private $plugin_url;
		private $headers = array( 'Name' => 'Skin Name' );
		private $skins = array();
		
		public function __construct()
		{
			$this->plugin_path = plugin_dir_path( __FILE__ );
			$this->plugin_url  = plugin_dir_url( __FILE__ );
			
			// Load plugin text domain.
			add_action( 'init', array(
				 &$this,
				'load_plugin_textdomain' 
			) );
			add_action( 'init', array(
				 &$this,
				'create_post_type' 
			), 80 );
			add_action( 'init', array(
				 &$this,
				'create_taxionomies' 
			), 90 );
			add_action( 'init', array(
				 &$this,
				'create_metaboxes' 
			), 100 );
			add_action( 'wp_enqueue_scripts', array(
				 &$this,
				'wordimpress_scripts' 
			), 200 );
			add_action( 'admin_enqueue_scripts', array(
				 &$this,
				'register_skins' 
			) );
			add_action( 'pre_get_posts', array(
				 &$this,
				'presentation_loops' 
			) );
			// Add the fields to the "presenters" taxonomy, using our callback function
			add_action( 'presentations_edit_form_fields', array(
				 &$this,
				'presentations_taxonomy_custom_fields' 
			), 10, 2 );
			// Save the changes made on the "presenters" taxonomy, using our callback function
			add_action( 'edited_presentations', array(
				 &$this,
				'save_taxonomy_custom_fields' 
			), 10, 2 );
			add_filter( 'template_include', array(
				 &$this,
				'impress_templates' 
			), 99 );
			//Register our callback to the appropriate filter
			add_filter( 'mce_buttons_2', array(
				 &$this,
				'add_editor_style_select' 
			) );
		}
		
		public static function init()
		{
			is_null( self::$instance ) AND self::$instance = new self;
			return self::$instance;
		}
		/**
		
		* Creates the Archive Content post type
		
		*
		
		* @return void
		
		*/
		public function create_post_type()
		{
			$labels = array(
				 'name' => __( 'Impress Steps', 'wordimpress' ),
				'singular_name' => __( 'Impress Step', 'wordimpress' ),
				'menu_name' => __( 'Impress Steps', 'wordimpress' ),
				'name_admin_bar' => __( 'Impress', 'wordimpress' ),
				'add_new' => __( 'Add New', 'wordimpress' ),
				'add_new_item' => __( 'Add New Step', 'wordimpress' ),
				'new_item' => __( 'New Step', 'wordimpress' ),
				'edit_item' => __( 'Edit Step', 'wordimpress' ),
				'view_item' => __( 'View Step', 'wordimpress' ),
				'all_items' => __( 'All Steps', 'wordimpress' ),
				'search_items' => __( 'Search Steps', 'wordimpress' ),
				'parent_item_colon' => '',
				'not_found' => __( 'No steps found.', 'wordimpress' ),
				'not_found_in_trash' => __( 'No steps found in trash.', 'wordimpress' ) 
			);
			$args   = array(
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
			//flush_rewrite_rules();
		}
		/**
		
		* Creates the plugin taxionomies [presentation]
		
		*
		
		* @return void
		
		*/
		public function create_taxionomies()
		{
			$labels = array(
				 'name' => __( 'Presentations', 'wordimpress' ),
				'singular_name' => __( 'Presentation', 'wordimpress' ),
				'search_items' => __( 'Search Presentations', 'wordimpress' ),
				'popular_items' => __( 'Popular Presentations', 'wordimpress' ),
				'all_items' => __( 'All Presentations', 'wordimpress' ),
				'parent_item' => null,
				'parent_item_colon' => null,
				'edit_item' => __( 'Edit Presentations', 'wordimpress' ),
				'update_item' => __( 'Update Presentation', 'wordimpress' ),
				'add_new_item' => __( 'Add New Presentation', 'wordimpress' ),
				'new_item_name' => __( 'New Presentation Name', 'wordimpress' ),
				'separate_items_with_commas' => __( 'Separate Presentations with commas', 'wordimpress' ),
				'add_or_remove_items' => __( 'Add or remove presentations', 'wordimpress' ),
				'choose_from_most_used' => __( 'Choose from the most used presentations', 'wordimpress' ),
				'not_found' => __( 'No presentations found.', 'wordimpress' ),
				'menu_name' => __( 'Presentations', 'wordimpress' ) 
			);
			$args   = array(
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
		
		* Create impress configuration meta boxes.
		
		*
		
		* @return void
		
		*/
		public function create_metaboxes()
		{
			$impress_metabox = new \Odin\Odin_Metabox( 'impress_config', // Slug/ID of the Metabox (Required)
				__( 'Step Configuration', 'wordimpress' ), // Metabox name (Required)
				'steps', // Slug of Post Type (Optional)
				'normal', // Context (options: normal, advanced, or side) (Optional)
				'high' // Priority (options: high, core, default or low) (Optional)
				);
			$impress_metabox->set_fields( array(
				 array(
					 'id' => 'head-text', // Required
					'label' => __( 'Head Text', 'wordimpress' ), // Required
					'type' => 'input', // Required
					'description' => __( 'Text that is shown before the title.', 'wordimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'text' 
					) 
				),
				array(
					 'id' => 'data-x', // Required
					'label' => __( 'Data-x', 'wordimpress' ), // Required
					'type' => 'input', // Required
					'default' => 0, // Optional
					'description' => __( 'Enter the x position of this step.', 'wordimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'number',
						'max' => 99999,
						'min' => -99999 
					) 
				),
				array(
					 'id' => 'data-y', // Required
					'label' => __( 'Data-y', 'wordimpress' ), // Required
					'type' => 'input', // Required
					'default' => 0, // Optional
					'description' => __( 'Enter the y position of this step.', 'wordimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'number',
						'max' => 99999,
						'min' => -99999 
					) 
				),
				array(
					 'id' => 'data-z', // Required
					'label' => __( 'Data-z', 'wordimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the z position of this step.', 'wordimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'number',
						'max' => 99999,
						'min' => -99999 
					) 
				),
				array(
					 'id' => 'data-rotate-x', // Required
					'label' => __( 'Data-Rotate-x', 'wordimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the x rotation of this step.', 'wordimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'number',
						'max' => 360,
						'min' => -360 
					) 
				),
				array(
					 'id' => 'data-rotate-y', // Required
					'label' => __( 'Data-Rotate-y', 'wordimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the y rotation of this step.', 'wordimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'number',
						'max' => 360,
						'min' => -360 
					) 
				),
				array(
					 'id' => 'data-rotate-z', // Required
					'label' => __( 'Data-Rotate-z', 'wordimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the z rotation of this step.', 'wordimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'number',
						'max' => 360,
						'min' => -360 
					) 
				),
				array(
					 'id' => 'data-scale', // Required
					'label' => __( 'Data-Scale', 'wordimpress' ), // Required
					'type' => 'input', // Required
					//'default'   => 0, // Optional
					'description' => __( 'Enter the scale of this step.', 'wordimpress' ), // Optional
					'attributes' => array( // Optional (html input elements)
						 'type' => 'number',
						'step' => 'any',
						'max' => 9999,
						'min' => -9999 
					) 
				),
				array(
					 'id' => 'step-class', // Required
					'label' => __( 'Step Class', 'wordimpress' ), // Required
					'type' => 'text', // Required
					'description' => __( 'Enter the class to be added to step.', 'wordimpress' ) // Optional
				),
				array(
					 'id' => 'step-format', // Obrigatório
					'label' => __( 'Step Format <br/>', 'wordimpress' ), // Obrigatório
					'type' => 'radio', // Obrigatório
					// 'attributes' => array(), // Opcional (atributos para input HTML/HTML5)
					'default' => 'default', // Opcional
					'description' => __( 'Select the step format.', 'wordimpress' ), // Opcional
					'options' => array( // Obrigatório (adicione aque os ids e títulos)
						'default' => __( 'Default', 'wordimpress' ),
						'image' => __( 'Image', 'wordimpress' ),
						'gallery' => __( 'Gallery', 'wordimpress' ),
						'video' => __( 'Video', 'wordimpress' ),
						'quote' => __( 'Quote', 'wordimpress' ),
						'title' => __( 'Title only', 'wordimpress' ) 
					) 
				),
				array(
					 'id' => 'step-empty', // Required
					'label' => __( 'Empty Step', 'wordimpress' ), // Required
					'type' => 'checkbox', // Required
					// 'attributes' => array(), // Optional (html input elements)
					// 'default'    => '', // Optional (1 for checked)
					'description' => __( 'Mark this if this step will be an empty step of your presentation.', 'wordimpress' ) // Optional
				), 
				array(
				    'id'          => 'step_bgimage', // Obrigatório
				    'label'       => __( 'Background Image', 'odin' ), // Obrigatório
				    'type'        => 'checkbox', // Obrigatório
				    'default'     => '', // Opcional (deve ser o id de uma imagem em mídia)
				    'description' => __( 'Show featured image as a background image for this step.', 'wordimpress' ), // Opcional
				)
			) );
		}
		
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
		
		public function wordimpress_scripts()
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
				wp_enqueue_script( 'wordimpress', plugins_url( 'assets/js/libs/wordimpress.js', __FILE__ ), array(
					 'impress' 
				), null, 'all' );
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
			// Add editor atyles
			function wip_add_editor_styles()
			{
				add_editor_style( '/assets/css/editor-style.css' );
			}
			add_action( 'init', 'wip_add_editor_styles' );
		}
		
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
		
		* Overrides the themes template for plugin created post types.
		
		*
		
		* @return void
		
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
		
		public function presentations_taxonomy_custom_fields( $term )
		{
			// Check for existing taxonomy meta for the term you're editing
			$t_id      = $term->term_id; // Get the ID of the term you're editing
			$term_meta = get_option( "presentations_term_" . $t_id ); // Do the check
			echo $term_meta;
			include( $this->plugin_path . 'includes/skin-select.php' );
		}
		
		public function save_taxonomy_custom_fields( $term_id )
		{
			if( isset( $_POST[ 'term_meta' ] ) ) {
				$t_id      = $term_id;
				$term_meta = get_option( "presentations_term_" . $t_id );
				/* --- Saving Associative Arrays in Database ---
				
				$keys = array_keys( $_POST['term_meta'] );
				
				
				
				foreach( $keys as $key ){
				
				if ( isset( $_POST['term_meta'][$key] ) ){
				
				$term_meta[$key] = $_POST['term_meta'][$key];
				
				}
				
				}
				
				*/
				//save the option array
				update_option( "presentations_term_" . $t_id, $_POST[ 'term_meta' ] );
			}
		}
		
		public function load_plugin_textdomain()
		{
			load_plugin_textdomain( 'wordimpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
		/**
		
		* Delete the Archives posts from database
		
		*
		
		* @return void
		
		*/
		public static function on_uninstall()
		{
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->posts WHERE post_type = %s", 'steps' ) );
		}
		
		public function add_editor_style_select( $buttons )
		{
			//Callback function to insert 'styleselect' into the $buttons array
			array_unshift( $buttons, 'styleselect' );
			return $buttons;
		}
	}
}

register_uninstall_hook( __FILE__, array(
	 'WordImPress',
	'on_uninstall' 
) );

add_action( 'plugins_loaded', array(
	 '\WordImPress\WordImPress',
	'init' 
) );

include plugin_dir_path( __FILE__ ) . 'includes/duplicate.php';	
?>