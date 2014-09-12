jQuery( document ).ready( function(){
   var jq = jQuery;
    //we need to run it on page load to make sure only the fields which are allowed by the current condition are shown
    
    
    //now let us bind to the change events
    //xpfields
    //find 
    
    //get all field ids
    var fields = [];
    for( var field_id in xpfields ){
        fields.push( '#'+field_id );
    }
   //try to see if any condition matches and sho hide/show appropriate field on page load
    for( var j = 0; j< fields.length; j++ ){
        
        apply_condition( fields[j] );
        
    }
    //bind the change event
    
    jq(document). on( 'change', fields.join(','), function(){
        
       apply_condition( this );
        
    });
    
    /**
     * Applies a condition to the field
     * 
     * @param {type} element
     * @returns {undefined}
     */
    function apply_condition( element ) {
        
        
        var id = jq( element ).attr('id');
        var current_val = jq( element ).val();
        
        
        var field = xpfields[id];
        if( field ==undefined)
            return;
        
        //if we are here, process the conditions
        for( var i =0; i< field.conditions.length; i++ ) {
            
            //apply each condition which depend on this field
            var condition = field.conditions[i];
          
            var matched = is_match( current_val, condition.value, condition.operator );
                        
            show_hide_field( condition.field_id, condition.visibility, matched );
            
            
        }
     
        
        
    }
    /**
     * Check if the current value matches the conditional value
     * 
     * @param {type} current_val
     * @param {type} val
     * @param {type} operator
     * @returns {Boolean}
     */
    function is_match( current_val, val, operator ){
        
        var condition_matched = false;
        
            switch( operator ) {
                
                case '=':
                    
                    if( current_val == val  )
                        condition_matched = true;
                    
                    break;
                    
                case '!=':
                    
                    if( current_val != val  )
                        condition_matched = true;
                    
                    break;
                    
                case '<=':
                    
                    if( current_val <= val  )
                        condition_matched = true;
                    
                    break;
                    
                case '>=':
                    
                    if( current_val >= val  )
                        condition_matched = true;
                    
                    break;
                    
                case '<':
                    
                    if( current_val <val  )
                        condition_matched = true;
                    
                    break;
                    
                
                case '>':
                    
                    if( current_val > val  )
                        condition_matched = true;
                    
                    break;
                    
              
                    
                
                
            }
            
            return condition_matched;
    }
    /**
     * Sow or Hide an entry in the form, hides whole editable div
     * It is based on the assumption that BuddyPress profile edit field parent div have a class 'editfield' and another class 'field_id'
     * This hould work for 98% of the themes
     * For the rest 2 % blame their developers :D
     * 
     * 
     * @param {type} field_id
     * @param {type} visibility
     * @param boolean match reverses visibility condition
     * @returns {undefined}
     */
    function show_hide_field( field_id, visibility, match ){
        
        var identifier = 'field_'+field_id;
        var element = jq("[id^='"+identifier+"']").get(0);
        //if there does not exist
       if( !element )
           console.log('Conditional Profile Fields:There seems to be some html issue and I am not able to fix it, Please tell that to the developer: field_id:'+field_id);
       
       
        
        //make sure that the field is not datebox, in case of  datebox, the element does not exist
        
        var parent_div = jq(jq(element).parents('.editfield').get(0) );
        
        //;find its parent having class edit field
        if( !match ){
            //if the condition did not match, reverse visibility condition
            
            if( visibility == 'show' ) {
                
                visibility = 'hide';
                
            }else {
                
                visibility = 'show';
                
            }    
        }
        
        if( visibility =='show' ) {
        
            parent_div.show();
        
        }else{
         
            parent_div.hide();
            //cler values
           // jq(identifier).val('');
        }    
    }
    
});

//on client side, hide fields
//on server side, do not allow update, just delete fields
