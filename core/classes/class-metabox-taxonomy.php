<?php
/**
 * Odin_Metabox_Taxonomy class.
 *
 * Inherit the Odin_Metabox class to built custom Metaboxes for taxonomy terms input, changing the way user insert terms (tags and categories).
 *
 * @category Metabox
 * @author   Haste Design
 * @author   Allyson Souza
 * @link     https://github.com/HasteDesign/TaxonomyMetabox
 * @version  0.3
 */
namespace Odin;

//Change the directory if you are not using Odin Framework, or moved your core classes to another local
require_once plugin_dir_path( __FILE__ ) . '/core/classes/class-metabox.php';

class Taxonomy_Metabox extends Odin_Metabox {
    /**
	 * Metaboxs fields.
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Metaboxs construct.
	 *
	 * @param string $id        HTML 'id' attribute of the edit screen section.
	 * @param string $title     Title of the edit screen section, visible to user.
	 * @param string $post_type The type of Write screen on which to show the edit screen section.
	 * @param string $context   The part of the page where the edit screen section should be shown ('normal', 'advanced', or 'side').
	 * @param string $priority  The priority within the context where the boxes should show ('high', 'core', 'default' or 'low').
	 * @param string $taxonomy  Whic taxonomy to change the metabox (slug).
	 *
	 * @return void
	 */
	public function __construct( $id, $title, $post_type = 'post', $context = 'normal', $priority = 'high' , $taxonomy, $remove = true ){
		$this->id        = $id;
		$this->title     = $title;
		$this->post_type = $post_type;
		$this->context   = $context;
		$this->priority  = $priority;
		$this->nonce     = $id .'_nonce';
		$this->taxonomy  = $taxonomy;
		$this->remove	 = $remove;

		// Add Metabox.
		add_action( 'add_meta_boxes', array( &$this, 'add' ) );

		// Save Metabox.
		add_action( 'save_post', array( &$this, 'save' ) );

		// Load scripts.
		add_action( 'admin_enqueue_scripts', array( &$this, 'scripts' ) );
	}

    /**
	 * Add the metabox in edit screens.
	 *
	 * @return void
	 */
	public function add() {
		foreach ( $this->get_post_type() as $post_type ) {
			add_meta_box(
				$this->id,
				$this->title,
				array( $this, 'metabox' ),
				$post_type,
				$this->context,
				$this->priority
			);
		}

		//Check if remove parameter is set to true, and then remove the default taxonomy metabox
		if( isset( $this->remove ) && $this->remove ) {
			if( $this->taxonomy === 'tags' ) {
				remove_meta_box( 'tagsdiv-'.$this->taxonomy, $this->post_type, 'side' );
			} else if ( $this->taxonomy === 'category' ) {
				remove_meta_box( 'categorydiv', $this->post_type, 'side' );
			} else {
				remove_meta_box( $this->taxonomy . 'div', $this->post_type, 'side' );
			}
		}
	}

	/**
	 * Override the Odin_Metabox save method. Uses wp_set_object_terms() to save metabox data.
	 *
	 * @param  int $post_id Current post type ID.
	 *
	 * @return void
	 */
    public function save( $post_id ) {
		// Verify nonce.
		if ( !isset( $_POST[ $this->nonce ] ) || !wp_verify_nonce( $_POST[ $this->nonce ], $this->get_base_filename() ))
		{
			return $post_id;
		}

		// Verify if this is an auto save routine.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check permissions.
		if ( $this->post_type == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return $post_id;
			}
		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		foreach ( $this->fields as $field ) {
			$name  = $field['id'];
			$value = isset( $_POST[ $name ] ) ? $_POST[ $name ] : null;

			if ( ! in_array( $field['type'], array( 'separator', 'title' ) ) ) {
				//Check if is a multiple(array) field or a single field to make the correct save
				if( is_array( $_POST[ $name ] ) ){
					$terms = [];
					//Loop through all the submitted itens and add it to terms
					foreach( $_POST[ $name ] as $item ) {
						$terms[] = $item;
					}

					//Replace all post terms
					wp_set_object_terms( $post_id, $terms, $this->taxonomy, false );
				} else {
					//Replace all post terms
					wp_set_object_terms( $post_id, $value, $this->taxonomy, false );
				}
			}
		}
    }

	/**
	 * Override the Odin_Metabox process_fields().
	 *
	 * Uses has_term() to verify the current value for fields. Add the new field choices to switch().
	 *
	 * @param  array $args    Field arguments
	 * @param  int   $post_id ID of the current post type.
	 *
	 * @return string          HTML of the field.
	 */
	protected function process_fields( $args, $post_id ) {
		$id      = $args['id'];
		$type    = $args['type'];
		$options = isset( $args['options'] ) ? $args['options'] : '';
		$attrs   = isset( $args['attributes'] ) ? $args['attributes'] : array();

		if( has_term( $id, $this->taxonomy, $post_id ) ){
			$current = '';
		} else {
			$current = '';
		}

		switch ( $type ) {
			case 'text':
				$this->field_input( $id, $current, array_merge( array( 'class' => 'regular-text' ), $attrs ) );
				break;
			case 'input':
				$this->field_input( $id, $current, $attrs );
				break;
			case 'textarea':
				$this->field_textarea( $id, $current, $attrs );
				break;
			case 'checkbox':
				$this->field_checkbox( $id, $current, $attrs );
				break;
			case 'select':
				$this->field_select( $id, $current, $options, $attrs );
				break;
			case 'radio':
				$this->field_radio( $id, $current, $options, $attrs );
				break;
			case 'editor':
				$this->field_editor( $id, $current, $options );
				break;
			case 'color':
				$this->field_input( $id, $current, array_merge( array( 'class' => 'odin-color-field' ), $attrs ) );
				break;
			case 'upload':
				$this->field_upload( $id, $current, $attrs );
				break;
			case 'image':
				$this->field_image( $id, $current );
				break;
			case 'image_plupload':
				$this->field_image_plupload( $id, $current );
				break;
			case 'tags_select':
				$this->field_tags_select( $id, $current, $options, $attrs, $post_id );
				break;
			case 'tags_checkbox':
				$this->field_tags_checkbox( $id, $current, $options, $attrs, $post_id );
				break;
			default:
				do_action( 'odin_metabox_field_' . $this->id, $type, $id, $current, $options, $attrs );
				break;
		}
	}

	/**
	 * Select tags field.
	 *
	 * @param  string $id      Field id.
	 * @param  string $current Field current value.
	 * @param  array  $options Array with select options.
	 * @param  array  $attrs   Array with field attributes.
	 * @param  int    $post_id ID of the current post type.
	 *
	 * @return string          HTML of the field.
	 */
	function field_tags_select( $id, $current, $options, $attrs, $post_id ) {
		// If multiple add a array in the option.
		$multiple = ( in_array( 'multiple', $attrs ) ) ? '[]' : '';

		$html = sprintf( '<select id="%1$s" name="%1$s%2$s" %3$s>', $id, $multiple, $this->build_field_attributes( $attrs ) );

        if ( !empty( $options ) ) {

            foreach ( $options as $key => $label ) {
                if( has_term( $key , $this->taxonomy, $post_id ) ) {
                    $selected = 'selected';
                } else {
                    $selected = '';
                }

                $html .= sprintf( '<option value="%1$s" %4$s >%3$s</option>', $key, selected( $current, $key, false ), $label, $selected );
            }

        }

		$html .= '</select>';

		echo $html;
	}

	/**
	 * Checkbox tags field.
	 *
	 * @param  string $id      Field id.
	 * @param  string $current Field current value.
	 * @param  array  $attrs   Array with field attributes.
	 * @param  int    $post_id ID of the current post type.
	 *
	 * @return string          HTML of the field.
	 */
	protected function field_tags_checkbox( $id, $current, $options, $attrs, $post_id ) {
		// If multiple add a array in the option.
		$multiple = ( in_array( 'multiple', $attrs ) ) ? '[]' : '';

		foreach ( $options as $key => $label ) {
			if( has_term( $key , $this->taxonomy, $post_id ) ) {
				$checked = 'checked';
			} else {
				$checked = '';
			}

			echo sprintf( '<label><input type="checkbox" id="%4$s" name="%1$s[]" value="%4$s" %2$s%3$s />%5$s</label><br/>', $id, $checked, $this->build_field_attributes( $attrs ), $key, $label );
		}
	}
}
?>
