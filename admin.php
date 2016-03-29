<?php

class Devb_Conditional_Profile_Admin{
    
    private static $instance;
    
    private $fields_info;
    
   
    private $operators = array(
        
        'multi' => array(
                '=' => 'Is'
            ),
        'single' => array(
            '=' => '=',
            '!=' => '!=',
            '<' => 'Less Than',
            '>' => 'Greater than',
            '<=' => 'Less than or equal to',
            '>=' => 'Greater than or equal to',
            )
    );
    private function __construct() {
       
        $this->path = plugin_dir_path( __FILE__ );
        $this->url  = plugin_dir_url( __FILE__ );
        
        add_action( 'xprofile_field_after_save', array( $this, 'save_field_condition' ) );
        
        add_action( 'xprofile_field_additional_options', array( $this, 'render_condition') );
        
                //load css/js for admin page
        add_action( 'bp_admin_enqueue_scripts', array( $this, 'load_admin_js' ) );
        add_action( 'bp_admin_enqueue_scripts', array( $this, 'load_admin_css' ) );
        
        add_action( 'admin_footer', array(  $this, 'to_js_objects' ) );
    }
    
    public static function get_instance() {
        
        if( !isset( self::$instance ) )
            self::$instance = new self();
        
        return self::$instance;
    }
    
    /**
	 * Save condition information when a Field is added/edited
	 * 
	 * @param type $field
	 * @return type
	 */
    public function save_field_condition( $field ) {
        
        if( isset( $_POST['xprofile-condition-display'] ) ) {
            
            if( empty( $_POST['xprofile-condition-display'] ) ) {
				
                $this->delete_condition( $field->id );
				
                return;
            }
			
           if( !wp_verify_nonce( $_POST['xprofile-condition-edit-nonce'], 'xprofile-condition-edit-action' ) )
				return;
		
            //if we are here, we need to set the condition
            $visibility = $_POST['xprofile-condition-display']; //no need to worry about it, we will explicitly check visibility after .000001ms from here
            
            $other_field_id = absint( $_POST['xprofile-condition-other-field'] );//field id must be an integer
            
            $operator = $this->validate_operator( $_POST['xprofile-condition-operator'], $other_field_id ) ;
			
			//check for valid operator
            
			//sanitize the field value
			 $value = $_POST['xprofile-condition-other-field-value'];
			
			$value = $this->sanitize_value( $value, $other_field_id );
			
			
            
            if( in_array( $visibility,  array( 'show', 'hide' ) ) && $other_field_id && $operator  ) {
                //make sure that all the fields are set
                 //what about empty value?
                
                //let us update it then
                bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_display', $visibility );
                bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_other_field', $other_field_id );
                bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_operator', $operator );
                bp_xprofile_update_field_meta( $field->id, 'xprofile_condition_other_field_value', $value );
                
                
            }
            
        }
        //we need to check if the condition was save, if yes, let us keep that condition in the meta
        
    }
	/**
	 * Returns operator if valid, else false
	 * 
	 * @param type $operator
	 * @param type $field_id
	 */
	public function validate_operator( $operator, $field_id ) {
		
		$operators = array_keys( $this->operators['single'] );
			
			
	
		if( !in_array( $operator, $operators ) )
			return false;
		
		return trim( $operator );
	}
	
	
	
	public function sanitize_value( $value, $field_id ) {
		
		$field  = new BP_XProfile_Field( $field_id );
		//incase of textarea/textbox the value needs to be sanitized, else just int?
		if( $field->type == 'textbox' || $field->type = 'textarea' )
			return esc_attr ( $value );;//should we really use esc_attr or esc_html or esc_js?
			
		//in all other cases
	
		if( is_numeric( $value ) )
			return $value;
		
		//otherwise cast to number type
		
		return (float) $value;//or should we only go with int?
		
	}
    /**
     * Deletes the condition associated with a profile field
     * 
     * @param type $field_id
     */
    public function delete_condition( $field_id ) {
        
        bp_xprofile_delete_meta( $field_id, 'field', 'xprofile_condition_display' );
        bp_xprofile_delete_meta( $field_id, 'field', 'xprofile_condition_other_field' );
        bp_xprofile_delete_meta( $field_id, 'field', 'xprofile_condition_operator' );
        bp_xprofile_delete_meta( $field_id, 'field', 'xprofile_condition_other_field_value' );
                
    }
    
    /**
     * Get visibility of the given field
     * @param type $field_id
     * @return string show|hide
     */
    public function get_visibility( $field_id ){
    
        return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_display' );
    }
    /**
     * Get the related field id which controls the condition
     * 
     * @param type $field_id
     * @return type
     */
    public function get_other_field_id( $field_id ){
    
        return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field' );
    }
    
    
    public function get_operator( $field_id ){
    
        return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_operator' );
    }
    
    
    public function get_other_field_value( $field_id ){
    
        return bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field_value' );
    }
    
    /**
     * render Condition UI on Manage/Add new field page
     * 
     * @param BP_XProfile_Field $field
     */
    
    public function render_condition( BP_XProfile_Field $field ){
        
        //it can be either manage field or add new field
        ?>

        <div class="postbox" id="xprofile-field-condition">
            <h3> <?php _ex( 'Visibility Condition', 'Condition section title in the admin', 'conditional-profile-fields-for-bp' );?></h3>
            <div class="inside">
                <?php  

                $visibility = $this->get_visibility( $field->id );

                $other_field_id = $this->get_other_field_id( $field->id );

                $operator = $this->get_operator( $field->id );

                $other_field_value = $this->get_other_field_value( $field->id );

            ?>
            <select name="xprofile-condition-display" id="xprofile-condition-display">
                <option value="0"><?php _ex( 'N/A', 'Show hide field option', 'conditional-profile-fields-for-bp' );?></option>
                <option value="show" <?php selected( 'show', $visibility ) ;?>><?php _ex( 'Show', 'Show hide field option', 'conditional-profile-fields-for-bp' );?></option>
                <option value="hide" <?php selected( 'hide', $visibility ) ;?> ><?php _ex( 'Hide', 'Show hide field option', 'conditional-profile-fields-for-bp' );?></option>
            </select>
            <?php _e('current field If', 'conditional-profile-fields-for-bp' ); ?>

            <select name="xprofile-condition-other-field" id='xprofile-condition-other-field'>
                 <?php  $this->build_field_dd( $field, $other_field_id );?>
            </select>

            <select name="xprofile-condition-operator" id='xprofile-condition-operator'>
                <option value="=" class="condition-single condition-multi" <?php selected( '=', $operator ) ;?>> = </option>
                <option value="!=" class='condition-single condition-multi' <?php selected( '!=', $operator ) ;?>> != </option>
                <option value="<=" class='condition-single' <?php selected( '<=', $operator ) ;?>> <= </option>
                <option value=">=" class='condition-single' <?php selected( '>=', $operator ) ;?>> >= </option>
                <option value="<" class='condition-single' <?php selected( '<', $operator ) ;?> > < </option>
                <option value=">"class='condition-single' <?php selected( '>', $operator ) ;?> > > </option>

            </select>
            <div class='xprofile-condition-other-field-value-container' id='xprofile-condition-other-field-value-container'>
                <?php

                $options = '';
                if( $other_field_id ) {

                    $other_field = new BP_XProfile_Field( $other_field_id );
                    
                    $children = $other_field->get_children();

                    if( $children  ){
                        //multi field
                        foreach( $children as $child_field ) 
                            $options .= "<label><input type='radio' value='{$child_field->id}'" .  checked( $other_field_value, $child_field->id, false ) ." name='xprofile-condition-other-field-value' />{$child_field->name}</label>";

                    }else{
                        $options =  "<input type='text' name='xprofile-condition-other-field-value' id='xprofile-condition-other-field-value' class='xprofile-condition-other-field-value-single' value ='{$other_field_value}'; />";

                    }

                }else{

                    $options =  "<input type='text' name='xprofile-condition-other-field-value' id='xprofile-condition-other-field-value' class='xprofile-condition-other-field-value-single' value =''; />";


                }

                ?>
                <?php echo $options;?>
				
            </div>
            
            </div> 
			<?php				wp_nonce_field( 'xprofile-condition-edit-action', 'xprofile-condition-edit-nonce' );?>
        </div>
		
        <?php
    }
    
    public function build_field_dd( $current_field, $selected_field_id ) {
        
        $groups = BP_XProfile_Group::get( array(
				'fetch_fields' => true
		));
        
        $html = '';
        
        foreach( $groups as $group ) {
            //if there are no fields in this group, no need to proceed further
            if( empty( $group->fields ) )
                continue;
            
            $html .= "<option value='0'> ". _x( 'Select Field', 'Fild selection title in admin', 'conditional-profile-fields-for-bp' ) . "</option>";
            $html .= "<optgroup label ='{ $group->name }'>";
           
            
            foreach( $group->fields as $field ) {
                
                //can not have condition for itself
                if( $field->id == $current_field->id )
                    continue;
               
                $field = new BP_XProfile_Field( $field->id, false, false );
                
               // $field->type_obj->supports_options;
                //$field->type_obj->supports_multiple_defaults;
                
                $html .="<option value='{$field->id}'". selected( $field->id, $selected_field_id, false ) ." >{$field->name}</option>";
                
                if( $field->type_obj->supports_options ) {
                    
                    $this->fields_info['field_' . $field->id]['type'] = 'multi';
                    
                    $children = $field->get_children();
                    
                    $this->fields_info['field_' . $field->id]['options'] = $children;
                    
                    //get all children and we will render the view to select one of these children
                    
                } else {
                    
                    $this->fields_info['field_' . $field->id]['type'] = 'single';
                }
                //now, let us build an optgroup
            }
            
            $html .= "</optgroup>"; 
            
            //$this->fields_info[]
            
        }

        echo $html;
    }
    
	/**
	 * Converts Fields info to js object and prints to xpfields
	 * 
	 * @return type
	 */
    public function to_js_objects() {
        
        if( ! $this->is_admin() )
            return;
       ?>
        <script type='text/javascript'>

        var xpfields = <?php echo json_encode( $this->fields_info );?>
        </script>
    <?php 
    
    }
	
	/**
	 * Load the required JS file for the admin
	 * 
	 * @return type
	 */
    public function load_admin_js() {
        
      
                
        if( !$this->is_admin() )
            return;
        
        wp_enqueue_script( 'bp-conditional-profile-admin-js', $this->url . 'assets/bp-conditional-field-admin.js' , array( 'jquery' ) );
        
        
    }
    	
	/**
	 * Load css file on the add/edit field
	 * 
	 * @return type
	 */
    public function load_admin_css() {
        
  
        if( ! $this->is_admin() )
            return;
        
        wp_enqueue_style( 'bp-conditional-profile-admin-css', $this->url . 'assets/bp-conditional-field-admin.css' );
    }
    
	/**
	 * Check if we are on the Add/edit field page
	 * 
	 * @return boolean
	 */
    public function is_admin() {
        
        if( !defined( 'DOING_AJAX' ) && is_admin() && ( get_current_screen()->id == 'users_page_bp-profile-setup' || get_current_screen()->id == 'users_page_bp-profile-setup-network' ) )
            return true;
        
        return false;
        
    }
}

Devb_Conditional_Profile_Admin::get_instance();