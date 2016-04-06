<?php
/**
 * Plugin Name: Conditional Profile Fields for BuddyPress
 * Version: 1.1.9
 * Author: Brajesh Singh, Anu Sharma
 * Plugin URI: http://buddydev.com/plugins/conditional-profile-fields-for-buddypress/
 * Author URI: http://buddydev.com
 * Description: Show/Hide profile fields depending on user data matching.
 * */

/**
 * Class name explanation
 * 'Devb' is the string that we have started using for any code form BuddyDev( DevB in reverseorder)
 */
class Devb_Conditional_Xprofile_Field_Helper {
	/**
	 *
	 * @var Devb_Conditional_Xprofile_Field_Helper singleton instance
	 */
	private static $instance;
	/**
	 * file system absolute path to this plugin directory (Has a trailing / at the end)
	 * 
	 * @var type 
	 */
	private $path;
	/**
	 * Absolute url to this plugin directory (has a trailing slash)
	 * 
	 * @var type 
	 */
	private $url;
	
	/**
	 * Keeps info about all the BuddyPress Xprofile fields.
	 * 
	 * Multi dimensional array of the type
	 *  array( 'field_1'=> array( 
	 *				'conditions'=> array( 'field_id' => 2, 'value'=>'something', 'operator'=> 'any of the allowed operator' ),
	 *				'type'=> multiselect|select|datebox|checkbox|radiobutton etc (The last name in lowercase of the profile field type),
	 *				'children'=> array() //array of all the children
	 *			),
	 * 
	 *			'field_x'=> array( 
	 *				'conditions'=> array( 'field_id' => 5, 'value'=>'something', 'operator'=> 'any of the allowed operator' ),
	 *				'type'=> multiselect|select|datebox|checkbox|radiobutton etc (The last name in lowercase of the profile field type),
	 *				'children'=> array() //array of all the children
	 *			),
	 * 
	 * 
	 * 
	 * )
	 * @var type 
	 */
	private $fields = array();
	
	private $data = array();//keep a copy of the field data
	//
	//info about the fields  which cause conditions to be applied on other fields
	private $conditional_fields = array();
	
	private function __construct() {

		$this->path = plugin_dir_path( __FILE__ );
		$this->url  = plugin_dir_url( __FILE__ );


		//load required files
		add_action( 'bp_loaded', array( $this, 'load' ) );

		//load css/js for the front end
		add_action( 'bp_enqueue_scripts', array( $this, 'load_js' ) );
		//we don't dd any css at the moment
		//add_action( 'bp_enqueue_scripts', array( $this, 'load_css' ) );
		//inject the conditions as javascript object 
		add_action( 'wp_head', array( $this, 'to_js_objects' ), 100 );
		
		//when the user account is activated, do not save the fields trigerred by the condition
		add_action( 'bp_core_activated_user', array( $this, 'update_saved_fields' ) );
		
		//when user profile is updated, check and update for condition
		add_action( 'xprofile_updated_profile', array( $this, 'update_saved_fields' ) );
		
	}

	/**
	 * Creates/returns Singleton instance 
	 * 
	 * @return Devb_Conditional_Xprofile_Field_Helper
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) )
			self::$instance = new self();

		return self::$instance;
	}

	/**
	 * Load required files
	 */
	public function load() {

		$files = array();

		if ( is_admin() ) {

			$files[] = 'admin.php';
		}

		foreach ( $files as $file )
			require_once $this->path . $file;
	}

	/**
	 * Builds the condition details
	 */
	public function build_conditions() {

		$groups = BP_XProfile_Group::get(
					array(
						'fetch_fields' => true,
					)
				);


		foreach ( $groups as $group ) {

			// skip if group has no profile fields
			if ( empty( $group->fields ) ) {
				continue;
			} 

			foreach ( $group->fields as $field ) {
				
				
				$this->fields['field_'. $field->id]['data'] = $field;

				$field = new BP_XProfile_Field( $field->id );
				
				//READ IT PLEASE
				//Now, I need type to handle the event binding on client side
				////the problem is there are inconsistency in the way id/class are applied on generated view, That's why I need type info
				//BuddyPress does not give explicit type, except for the class name,
				// now, It is bad but I am stil going to do it anyway
				//Can we improve this in future?
				$class_name = explode( '_', get_class( $field->type_obj ) );
				$class_name = strtolower( array_pop( $class_name ) );
				
				$this->fields['field_' . $field->id ]['type'] = $class_name;
				//we got type+data
				//let us get the children
				$children = $field->get_children();

				if ( ! empty( $children ) ) {
					
					$this->fields['field_' . $field->id]['children']= $children;
								
				}
				
				$related_id = $this->get_related_field_id( $field->id );
				//if this field has no condition set, let us not worry
				if ( ! $related_id )
					continue;
				//if we have condition set on this field, let us get info
				$this->conditional_fields['field_' . $related_id]['conditions'][] = $this->get_field_condition( $field->id );
				
				
			}
		}
		
		
			
		$this->data = $this->get_data();
			
		
		
	}

	private function get_data( $user_id = false ) {
		
		$data = array();
		if( ! is_user_logged_in() )
			return $data;
		
		if( ! $user_id )
			$user_id = get_current_user_id ();
		
		//incase we are on someone's profile, override user id
		
		if( bp_is_user() )
			$user_id = bp_displayed_user_id ();
		
		
		$groups = bp_xprofile_get_groups( array(
			'user_id'                => $user_id,
			'hide_empty_groups'      => true,
			'hide_empty_fields'      => true,
			'fetch_fields'           => true,
			'fetch_field_data'       => true,
		) );
		
		
		foreach ( (array) $groups as $group ) {
			
			if ( empty( $group->fields ) ) {
				continue;
			}

			foreach ( (array) $group->fields as $field ) {
				$data[ 'field_'. $field->id ] = array(
					//'group_id'		=> $group->id,
					//'field_id'		=> $field->id,
					'field_type'	=> $field->type,
					'value'			=> maybe_unserialize( $field->data->value ),
				);
			}
		}
		
		return $data;
	}
	/**
	 * Get the condition applied on a field
	 * 
	 * @param type $field_id
	 * @return array('field_id', 'visibility', 'operator', 'value' )
	 */
	public function get_field_condition( $field_id ) {

		$visibility = bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_display' );


		$operator = bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_operator' );

		$value = bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field_value' );

		//check if it was a muti type field, then we need to send the name as value instead of id

		$related_field_id = $this->get_related_field_id( $field_id );

		$related_field = new BP_XProfile_Field( $related_field_id );

		//is the related field having multi type?
		$children = $related_field->get_children();

		if ( ! empty( $children ) ) {
			//if yes, we need to replace the value(as the value is id of the child option) with the name of the child option
			foreach ( $children as $child ) {

				if ( $child->id == $value ) {

					$value = stripslashes( $child->name );
					break;
				}
			}
		}

		return compact( 'field_id', 'visibility', 'operator', 'value' );
	}

	/**
	 * Get the related field that triggers condition for the give field
	 * 
	 * @param type $field_id
	 * @return type
	 */
	public function get_related_field_id( $field_id ) {

		$related_field = bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field' );

		return $related_field;
	}

	/**
	 * Delete the fields on new user activation/profile update that do not conform to our condition
	 * and yes, I am the boss here, don' ask me the logic :P
	 * 
	 * @param type $user_id
	 */
	public function update_saved_fields( $user_id ) {
		
		//build all conditions array
		$this->build_conditions();
		
		//get the fields whose value triggers conditions
		$conditional_fields = $this->conditional_fields;
		
		//Now, There can be multiple conditional fields
		foreach ( $conditional_fields as $conditional_field_id => $related_fields ) {
			
			//for each field triggering the condition, get the field data for this field
			$conditional_field_id = (int)str_replace('field_', '', $conditional_field_id );
			
			$data = xprofile_get_field_data( $conditional_field_id, $user_id );
			
			//find all the conditions which are based on the vale of this field
			foreach( $related_fields['conditions'] as $condition ) {
				
				//check if condition is matched
				if( $this->is_match( $data , $condition['value'], $condition['operator'] ) ) {
					
					//if visibility is set to hidden and condition matched, delete data for the field on which this condition is applied
					if( $condition['visibility'] =='hide'){
						
						xprofile_delete_field_data( $condition['field_id'], $user_id );
					}
					
				}else{
					
					//if there is no match and the visibility is set to show on condition, we still need to delete the data for the field on which this condition is applied
					
					if( $condition['visibility'] == 'show' ) {
						
						xprofile_delete_field_data( $condition['field_id'], $user_id );
					}
				}
				
			}
			
		}
		
		
	}
	
	/**
	 * Check if given the value, conditional value and operator, if there is a match?
	 * 
	 * @param type $current_val
	 * @param type $val
	 * @param type $operator
	 * @return boolean
	 */
	public function is_match( $current_val, $val, $operator ) {
        
			$matched = false;
			
            switch( $operator ) {
                
                case '=':
                    
                    if( ! is_array( $current_val ) && $current_val == $val  )
                        $matched = true;
                    elseif( is_array( $current_val ) && in_array( $val, $current_val ) )
						$matched = true;
                    break;
                    
                case '!=':
                    
                    if( !is_array( $current_val ) && $current_val != $val  )
                        $matched = true;
                    elseif( is_array( $current_val ) && !in_array(  $val, $current_val ))
						$matched = true;
					
                    break;
                    
                case '<=':
                    
                    if( $current_val <= $val  )
                        $matched = true;
                    
                    break;
                    
                case '>=':
                    
                    if( $current_val >= $val  )
                        $matched = true;
                    
                    break;
                    
                case '<':
                    
                    if( $current_val < $val  )
                        $matched = true;
                    
                    break;
                    
                
                case '>':
                    
                    if( $current_val > $val  )
                        $matched = true;
                    
                    break;
                    
              
                    
                
                
            }
            
            return $matched;
    }
	
	/**
	 * Injects the profile field conditions a js object
	 * 
	 */
	public function to_js_objects() {

		if ( ! function_exists('buddypress') || ! $this->is_active() ) {
			return ;
		}

		$this->build_conditions();
		$fields = array();

		foreach ( $this->fields as $field_id => $data ) {
			$fields[$field_id] = array( 'type' => $data['type'] );
		}
		//some other day
		$to_json =   array( 'fields'=> $fields, 'conditional_fields'=> $this->conditional_fields, 'data' => $this->data ) ;

		?>
		<script type='text/javascript'>
			var xpfields = <?php echo json_encode( $to_json ); ?>;
		</script>
		<?php
	}

	/**
	 * Check if we should load the assets for conditional profile field on this page or not?
	 * 
	 * @return type
	 */
	public function is_active() {

		$is_active = false;
		//by default we activate only on register page or profile page
		if ( ! is_admin() && ( bp_is_register_page() || bp_is_user_profile_edit() ) )
			$is_active = true;

		return apply_filters( 'bp_is_conditional_profile_field_active', $is_active );
	}

	/**
	 * Loads Js required for front end
	 */
	public function load_js() {

		if ( ! $this->is_active() )
			return;

		
		wp_enqueue_script( 'bp-conditional-profile-js', $this->url . 'assets/bp-conditional-field.js', array( 'jquery' ) );
	}

	/**
	 * Loads css required for front end
	 * Infact we don't need any at the moment
	 * 
	 */
	public function load_css() {

		if ( ! $this->is_active() )
			return;

		wp_enqueue_script( 'bp-conditional-profile-css', $this->url . 'assets/bp-conditional-field.css' );
	}

}

Devb_Conditional_Xprofile_Field_Helper::get_instance();
