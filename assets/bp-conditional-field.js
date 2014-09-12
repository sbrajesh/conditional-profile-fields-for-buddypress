jQuery( document ).ready( function(){
	var jq = jQuery;
    
    
    //all bp fields info
	var all_fields = xpfields.fields;
	
	//only fields which are used to trigger conditions
	var conditional_fields = xpfields.conditional_fields;
	//building a list of normal fields(except checkboxes/radio) that trigger a visibility condition	
    var fields = [];
	//list of the fields which is either radio or checkbox and trigger some condition
	var multifields = [];
	
		//Build the fields, multifields array
		
    for( var field_id in conditional_fields ){
		
		if( all_fields[field_id]['type'] == 'checkbox' || all_fields[field_id]['type'] == 'radiobutton' ) {
			
			multifields.push(field_id);
			
		}else{
        
			fields.push( '#'+field_id );
		
		}
		
    }
   //try to see if any condition matches and sho hide/show appropriate field on page load
    for( var j = 0; j< fields.length; j++ ) {
        
        apply_condition( fields[j] );
        
    }
	
    //bind the change event for the elemnts in fields array
    
    jq(document). on( 'change', fields.join(','), function() {
        
       apply_condition( this );
        
    });

	//for multifields, this block does two things
	//1. adds an event listener
	//2. Check the fields on initial page load for a match
	
	for( var j=0; j<multifields.length; j++ ) {
		var selector = '';
		
		if( all_fields[multifields[j]]['type'] =='radiobutton' ){
			selector = '#'+multifields[j] + ' input';
			
			
		}else{
			var identifier = multifields[j]+'\[\]';
			selector =  "[name='"+identifier +"']";
			
			
		}
		if( selector ) {
			
			add_condition( selector );
			
			
			apply_condition( jq(selector) );
			
			
		}
		
	}
	
	/**
	 * Binds change event to the elemens based on given  selector
	 * We use it for multifields binding
	 * 
	 * @param {type} selector
	 * @returns {undefined}
	 */
	function add_condition( selector ) {
		
		jq( document ).on( 'change', selector, function() {
       
			apply_condition( this );
        
		});
	}
    //we need to bind for checkbox, radio box & datebox too
    /**
     * Applies a condition to the field
     * 
     * @param {type} element
     * @returns {undefined}
     */
    function apply_condition( element ) {
        
        var done = false;
		
		var id = '';
		//find the element to hide
		var type = jq(element).attr( 'type' );
		var current_val = '';
		
		//I am not happy with the way we need to handle checkboxes her, It could be much better
		
		if(  type == 'checkbox' ) {
		
		
			id = jq( element ).attr('name');
			id = id.replace('\[\]', ''); //field_n[] to field_n
			
			//if( jq(element).is(':checked') )
				current_val = jq( element).parents('.checkbox').find('input:checked').val();
			
			done = true;
		}
		
		if( !done && type == 'radio' ) {
			
			id = jq(element).attr('name');
			current_val = jq( element).parents('.radio').find('input:checked').val();
			done = true;
			
		}
		//we do not support datebox yet
		if( !done &&  type =='datebox' ){
			
			
			done = true;
		}
		if( !done ){
			//it is neither of the above,
			
			id = jq( element ).attr('id');
			current_val = jq( element ).val();
		}
        
		
		//we know id
       if( ! id )
		   return;
        
        //get the field associated with this condition
        var field = conditional_fields[id];
		
		//is there really a condition associated with field, if not, do not proceed
        if( field == undefined)
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
        
		//we have the field id
		//so we can understand the behaviour of this field
		var field = all_fields['field_'+field_id];
		var done = false;
		var element = '';
		
		//find the element to hide
		
		if(  field['type'] == 'checkbox' ) {
			
			var identifier = '';
			identifier = 'field_' + field_id+'\[\]';
			
			element = jq( "[name='"+identifier +"']");
			

			done = true;
		
			
		}
		
		if( !done && field['type'] == 'radiobutton' ) {
			
			element = jq('#field_'+field_id);
			
			done = true;
			
		}
		if( !done &&  field['type'] =='datebox' ){
			
			element = jq('#field_' + field_id + '_day' );
			done = true;
		}
		if( !done ){
			//it is neither of the above,
			
			element = jq( '#field_'+field_id );
			done = true;
		}
		
		 if( !element )
           console.log( 'Conditional Profile Fields:There seems to be some html issue and I am not able to fix it, Please tell that to the developer: field_id:'+field_id );
       
		
        var element = element.get(0);
        //if there does not exist
      
       
        
        //make sure that the field is not datebox, in case of  datebox, the element does not exist
        
        var parent_div = jq( jq( element ).parents( '.editfield' ).get(0) );
	
       
		
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
