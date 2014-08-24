<?php

/**
 * Plugin Name: Conditional Profile Feilds for BuddyPress
 * Version: 1.0
 * Author: Brajesh Singh, Anu Sharma
 * Plugin URI: http://buddydev.com/plugins/conditional-profile-fields-for-buddypress/
 * Author URI: http://buddydev.com
 * Description: Show/Hide profile fields depending on user data matching.
 * 
 */
/**
 * Class name explanation
 * 'Devb' is the string that we have started using for any code form BuddyDev( DevB in reverseorder)
 */
class Devb_Conditional_Xprofile_Field_Helper {
    
    
    private static $instance;
    private $path;
    private $url;
    
    private $fields = array();
    
    
    private function __construct() {
    
        $this->path = plugin_dir_path( __FILE__ );
        $this->url = plugin_dir_url( __FILE__ );
        
        
        //load required files
        add_action( 'bp_loaded', array( $this, 'load' ) );
        
        //load css/js for the front end
        add_action( 'bp_enqueue_scripts', array( $this, 'load_js' ) );
        //we don't dd any css at the moment
        //add_action( 'bp_enqueue_scripts', array( $this, 'load_css' ) );
        
        //inject the conditions as javascript object 
        add_action( 'bp_head', array( $this, 'to_js_objects' ) );
        

        
    }
    
    /**
     * Creates/returns Singleton instance 
     * 
     * @return Devb_Conditional_Xprofile_Field_Helper
     */
    public static function get_instance() {
        
        if( ! isset( self::$instance ) )
            self::$instance = new self();
        
        return self::$instance;
    }
    
    /**
     * Load required files
     */
    public function load() {
        
        $files = array();
        
        if( is_admin() ){
            
            $files[] = 'admin.php';
        }
        
        foreach( $files as $file )
            require_once $this->path . $file;
    }
    
    /**
     * Builds the condition details
     */
    public function build_conditions() {
        
        $groups = BP_XProfile_Group::get( array(
				'fetch_fields' => true
		));
        
        
        foreach( $groups as $group ){
            
            foreach( $group->fields as $field ) {
                
                $related_id = $this->get_related_field_id( $field->id );
                //if this field has no condition set, let us not worry
                if( !$related_id )
                    continue;
                
                $this->fields['field_' . $related_id]['conditions'][] = $this->get_field_condition( $field->id );
                
        
                
            }
        }
        
    }
    
    /**
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
       $children =  $related_field->get_children() ;
       
       if( !empty( $children ) ) {
           //if yes, we need to replace the value(as the value is id of the child option) with the name of the child option
           foreach( $children  as $child ){
               
               if( $child->id == $value ) {
                   
                   $value = stripslashes( $child->name );
                   break;
               }
                   
           }
       }
           
       return compact( 'field_id','visibility', 'operator', 'value' );
       
    }
    
    /**
     * Get the related field that triggers condition for the give field
     * 
     * @param type $field_id
     * @return type
     */
    public function  get_related_field_id( $field_id ) {
        
        $related_field = bp_xprofile_get_meta( $field_id, 'field', 'xprofile_condition_other_field' );
        
        return $related_field;
    }

    /**
     * Injects the profile field conditions a js object
     * 
     */
    public function to_js_objects(){
        
       
        if( !$this->is_active() )
            return;
        
        $this->build_conditions();
        
       ?>
        <script type='text/javascript'>

        var xpfields = <?php echo json_encode( $this->fields );?>
        
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
        if( !is_admin() && ( bp_is_register_page() ||  bp_is_user_profile_edit() ) )
            $is_active = true;
        
        return apply_filters( 'bp_is_conditional_profile_field_active', $is_active );
    }
    /**
     * Loads Js required for front end
     */
    public function load_js(){
        
        if( !$this->is_active() )
            return ;
        
        wp_enqueue_script( 'bp-conditional-profile-js', $this->url . 'assets/bp-conditional-field.js', array('jquery') );
        
    }
    /**
     * Loads css required for front end
     * Infact we don't need any at the moment
     * 
     */
    
    public function load_css(){
        
        if( !$this->is_active() )
            return;
        
        wp_enqueue_script( 'bp-conditional-profile-css', $this->url . 'assets/bp-conditional-field.css' );        
        
    }
    
    
    
}

Devb_Conditional_Xprofile_Field_Helper::get_instance();