<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Class Devb_Conditional_Profile_Admin
 */
class Devb_Conditional_Profile_Admin {
	/**
	 * Singleton.
	 *
	 * @var Devb_Conditional_Profile_Admin
	 */
	private static $instance;

	/**
	 * All fields info.
	 *
	 * @var array
	 */
	private $fields_info;

	/**
	 * Operator.
	 *
	 * @var array
	 */
	private $operators = array(

		'multi'  => array(
			'=' => 'Is',
		),
		'single' => array(
			'='  => '=',
			'!=' => '!=',
			'<'  => 'Less Than',
			'>'  => 'Greater than',
			'<=' => 'Less than or equal to',
			'>=' => 'Greater than or equal to',
		),
	);

	/**
	 * Devb_Conditional_Profile_Admin constructor.
	 */
	private function __construct() {

		$this->path = plugin_dir_path( __FILE__ );
		$this->url  = plugin_dir_url( __FILE__ );

		add_action( 'xprofile_field_after_save', array( $this, 'save_field_condition' ) );

		add_action( 'xprofile_field_additional_options', array( $this, 'render_condition' ) );

		// load css/js for admin page.
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'load_admin_js' ) );
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'load_admin_css' ) );

		add_action( 'admin_footer', array( $this, 'to_js_objects' ) );
		add_action( 'xprofile_admin_field_name_legend', array( $this, 'show_field_list_condition' ) );
	}

	/**
	 * Get the singleton.
	 *
	 * @return Devb_Conditional_Profile_Admin
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Save condition information when a Field is added/edited
	 *
	 * @param BP_XProfile_Field $field field object.
	 */
	public function save_field_condition( $field ) {

		if ( isset( $_POST['xprofile-condition-display'] ) ) {

			if ( empty( $_POST['xprofile-condition-display'] ) ) {
				$this->delete_condition( $field->id );
				return;
			}

			if ( ! wp_verify_nonce( $_POST['xprofile-condition-edit-nonce'], 'xprofile-condition-edit-action' ) ) {
				return;
			}

			// if we are here, we need to set the condition
			// no need to worry about it, we will explicitly check visibility after .000001ms from here.
			$visibility = $_POST['xprofile-condition-display'];

			// field id must be an integer.
			$other_field_id = absint( $_POST['xprofile-condition-other-field'] );

			$operator = $this->validate_operator( $_POST['xprofile-condition-operator'], $other_field_id );

			// check for valid operator
			// sanitize the field value.
			$value = $_POST['xprofile-condition-other-field-value'];

			$value = $this->sanitize_value( $value, $other_field_id );

			if ( in_array( $visibility, array( 'show', 'hide' ) ) && $other_field_id && $operator ) {
				// make sure that all the fields are set
				// what about empty value?
				// let us update it then.
				bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_display', $visibility );
				bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_other_field', $other_field_id );
				bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_operator', $operator );
				bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_other_field_value', $value );
				$other_field = new BP_XProfile_Field( $other_field_id );
                $children = $other_field->get_children();

				if ( is_numeric( $value ) && ! empty( $children ) && ! in_array( $field->type, array(
						'membertype',
						'membertypes'
					) ) ) {
					// this is a multi option field, we should store the value.

                    foreach ( $children as $child ) {
                        if( $value == $child->id ) {
	                        bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_other_field_option_name',  $child->name );
                            break;
                        }
                    }
				}
			}
		}

		// we need to check if the condition was save,
        // if yes, let us keep that condition in the meta.
	}

	/**
	 * Returns operator if valid, else false.
	 *
	 * @param string $operator operator.
	 * @param int    $field_id field id.
	 *
	 * @return string
	 */
	public function validate_operator( $operator, $field_id ) {

		$operators = array_keys( $this->operators['single'] );

		if ( ! in_array( $operator, $operators ) ) {
			return false;
		}

		return trim( $operator );
	}

	/**
	 * Sanitize value.
	 *
	 * @param mixed $value value.
	 * @param int   $field_id field id.
	 *
	 * @return float|string|void
	 */
	public function sanitize_value( $value, $field_id ) {

		$field = new BP_XProfile_Field( $field_id );
		// in case of textarea/textbox the value needs to be sanitized, else just int?
		if ( $field->type == 'textbox' || $field->type = 'textarea' ) {
			return esc_attr( $value );
		};

		// in all other cases.
		if ( is_numeric( $value ) ) {
			return $value;
		}

		// otherwise cast to number type
		// or should we only go with int?
		return (float) $value;

	}

	/**
	 * Deletes the condition associated with a profile field
	 *
	 * @param int $field_id field id.
	 */
	public function delete_condition( $field_id ) {

		bp_xprofile_delete_meta( $field_id, 'field', 'xprofile_condition_display' );
		bp_xprofile_delete_meta( $field_id, 'field', 'xprofile_condition_other_field' );
		bp_xprofile_delete_meta( $field_id, 'field', 'xprofile_condition_operator' );
		bp_xprofile_delete_meta( $field_id, 'field', 'xprofile_condition_other_field_value' );

	}

	/**
	 * Get visibility of the given field
	 *
	 * @param int $field_id field id.
	 *
	 * @return string show|hide
	 */
	public function get_visibility( $field_id ) {

		return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_display' );
	}

	/**
	 * Get the related field id which controls the condition
	 *
	 * @param int $field_id field id.
	 *
	 * @return int
	 */
	public function get_other_field_id( $field_id ) {
		return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field' );
	}

	/**
	 * Get operator.
	 *
	 * @param int $field_id field id.
	 *
	 * @return string
	 */
	public function get_operator( $field_id ) {
		return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_operator' );
	}

	/**
	 * Get other field value.
	 *
	 * @param int $field_id field id.
	 *
	 * @return mixed
	 */
	public function get_other_field_value( $field_id ) {
		return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field_value' );
	}

	/**
	 * Get other field value.
	 *
	 * @param int $field_id field id.
	 *
	 * @return mixed
	 */
	public function get_other_field_displayable_value( $field_id ) {
		$option_value = bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field_option_name' );

        if ( $option_value ) {
			return $option_value;
		}

		return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field_value' );
	}

	/**
	 * Render Condition UI on Manage/Add new field page
	 *
	 * @param BP_XProfile_Field $field field object.
	 */
	public function render_condition( BP_XProfile_Field $field ) {

		// it can be either manage field or add new field.
		?>

        <div class="postbox" id="xprofile-field-condition">
            <h3> <?php _ex( 'Visibility Condition', 'Condition section title in the admin', 'conditional-profile-fields-for-bp' ); ?></h3>
            <div class="inside">
				<?php

				$visibility        = $this->get_visibility( $field->id );
				$other_field_id    = $this->get_other_field_id( $field->id );
				$operator          = $this->get_operator( $field->id );
				$other_field_value = $this->get_other_field_value( $field->id );

				?>
                <select name="xprofile-condition-display" id="xprofile-condition-display">
                    <option value="0"><?php _ex( 'N/A', 'Show hide field option', 'conditional-profile-fields-for-bp' ); ?></option>
                    <option value="show" <?php selected( 'show', $visibility ); ?>><?php _ex( 'Show', 'Show hide field option', 'conditional-profile-fields-for-bp' ); ?></option>
                    <option value="hide" <?php selected( 'hide', $visibility ); ?> ><?php _ex( 'Hide', 'Show hide field option', 'conditional-profile-fields-for-bp' ); ?></option>
                </select>
				<?php _e( 'current field If', 'conditional-profile-fields-for-bp' ); ?>

                <select name="xprofile-condition-other-field" id='xprofile-condition-other-field'>
					<?php $this->build_field_dd( $field, $other_field_id ); ?>
                </select>

                <select name="xprofile-condition-operator" id='xprofile-condition-operator'>
                    <option value="=" class="condition-single condition-multi" <?php selected( '=', $operator ); ?>> =
                    </option>
                    <option value="!=" class='condition-single condition-multi' <?php selected( '!=', $operator ); ?>>
                        !=
                    </option>
                    <option value="<=" class='condition-single' <?php selected( '<=', $operator ); ?>> <=</option>
                    <option value=">=" class='condition-single' <?php selected( '>=', $operator ); ?>> >=</option>
                    <option value="<" class='condition-single' <?php selected( '<', $operator ); ?> > <</option>
                    <option value=">" class='condition-single' <?php selected( '>', $operator ); ?> > ></option>

                </select>
                <div class='xprofile-condition-other-field-value-container'
                     id='xprofile-condition-other-field-value-container'>
					<?php

					$options = '';
					if ( $other_field_id ) {
						$other_field = new BP_XProfile_Field( $other_field_id );
						$children    = $other_field->get_children();
                        $children = apply_filters( 'cpffb_admin_field_options', $children, $other_field );

						if ( $children ) {
							$children = bpc_profile_field_sanitize_child_options( $children );
							//multi field
							foreach ( $children as $child_field ) {
								$options .= "<label><input type='radio' value='{$child_field->id}'" . checked( $other_field_value, $child_field->id, false ) . " name='xprofile-condition-other-field-value' />{$child_field->name}</label>";
							}
						} else {
							$options = "<input type='text' name='xprofile-condition-other-field-value' id='xprofile-condition-other-field-value' class='xprofile-condition-other-field-value-single' value ='{$other_field_value}'; />";
						}
					} else {
						$options = "<input type='text' name='xprofile-condition-other-field-value' id='xprofile-condition-other-field-value' class='xprofile-condition-other-field-value-single' value =''; />";
					}

					?>
					<?php echo $options; ?>

                </div>

            </div>
			<?php wp_nonce_field( 'xprofile-condition-edit-action', 'xprofile-condition-edit-nonce' ); ?>
        </div>

		<?php
	}

	/**
     * Build field dropdown.
     *
	 * @param int $current_field current field id.
	 * @param int $selected_field_id select field id.
	 */
	public function build_field_dd( $current_field, $selected_field_id ) {

		$groups = BP_XProfile_Group::get( array(
			'fetch_fields' => true
		) );

		$html = "<option value='0'> " . _x( 'Select Field', 'Fild selection title in admin', 'conditional-profile-fields-for-bp' ) . "</option>";

		foreach ( $groups as $group ) {
			//if there are no fields in this group, no need to proceed further
			if ( empty( $group->fields ) ) {
				continue;
			}

			$html .= "<optgroup label ='{ $group->name }'>";

			foreach ( $group->fields as $field ) {

				//can not have condition for itself
				if ( $field->id == $current_field->id ) {
					continue;
				}

				$field = new BP_XProfile_Field( $field->id, false, false );

				// $field->type_obj->supports_options;
				//$field->type_obj->supports_multiple_defaults;

				$html .= "<option value='{$field->id}'" . selected( $field->id, $selected_field_id, false ) . " >{$field->name}</option>";

				if ( $field->type_obj->supports_options ) {

					$this->fields_info[ 'field_' . $field->id ]['type'] = 'multi';

					$children = $field->get_children();

					$this->fields_info[ 'field_' . $field->id ]['options'] = bpc_profile_field_sanitize_child_options( $children );

					//get all children and we will render the view to select one of these children
				} else {
					$this->fields_info[ 'field_' . $field->id ]['type'] = 'single';
				}
				//now, let us build an optgroup
			}

			$html .= '</optgroup>';

			//$this->fields_info[]
		}

		echo $html;
	}

	/**
	 * Converts Fields info to js object and prints to xpfields
	 */
	public function to_js_objects() {

		if ( ! $this->is_admin() ) {
			return;
		}
		?>
        <script type='text/javascript'>
            var xpfields = <?php echo json_encode( $this->fields_info );?>
        </script>
		<?php

	}

	/**
	 * Load the required JS file for the admin
	 */
	public function load_admin_js() {

		if ( ! $this->is_admin() ) {
			return;
		}

		wp_enqueue_script( 'bp-conditional-profile-admin-js', $this->url . 'assets/bp-conditional-field-admin.js', array( 'jquery' ) );
	}

	/**
	 * Load css file on the add/edit field
	 */
	public function load_admin_css() {


		if ( ! $this->is_admin() ) {
			return;
		}

		wp_enqueue_style( 'bp-conditional-profile-admin-css', $this->url . 'assets/bp-conditional-field-admin.css' );
	}

	/**
	 * Check if we are on the Add/edit field page
	 *
	 * @return boolean
	 */
	public function is_admin() {

		if ( ! defined( 'DOING_AJAX' ) && is_admin() && ( get_current_screen()->id == 'users_page_bp-profile-setup' || get_current_screen()->id == 'users_page_bp-profile-setup-network' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Show the condition alongside the title on Dashboard->Users->Profile Fields screen.
	 *
	 * @param BP_XProfile_Field $field field object.
	 */
	public function show_field_list_condition( $field ) {
		$visibility = $this->get_visibility( $field->id );

		if ( ! $visibility ) {
			return;
		}

		$other_field = $this->get_other_field_id( $field->id );
		$other_field = xprofile_get_field( $other_field );
		if ( $other_field ) {
			$other_field = $other_field->name;
		}
		$operator  = $this->get_operator( $field->id );
		$value     = $this->get_other_field_displayable_value( $field->id );
		$condition = "[ {$visibility} if {{$other_field}}  {$operator} {$value} ]";
		echo '<span class="cpffbp-field-list">&nbsp;&nbsp;' . $condition . '</span>';
	}
}

Devb_Conditional_Profile_Admin::get_instance();
