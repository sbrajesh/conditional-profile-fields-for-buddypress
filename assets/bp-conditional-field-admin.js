jQuery( document ).ready( function() {
    
    var jq = jQuery;
    //move the condition box to the bottom of the screen,
    //buddypress does not have an appropriate hook, so we do it via js
    jq('#postbox-container-2').append(jq('#xprofile-field-condition'));
    
    //var xpfields;
    jq(document).on( 'change', '#xprofile-condition-other-field', function ( evt ){
        var selected = jq(':selected', this).val();//the field that was selected
        
        //1. check for the type of the field
        
        var field = xpfields['field_'+selected];
        if( field == undefined )
            return;
        
        var list = '';
        
        if( field.type =='multi'){
            
            jq('#xprofile-condition-operator option.condition-single').hide();
            jq('#xprofile-condition-operator option.condition-multi').show();
            
            for( var i =0; i< field.options.length; i++ ){
                list +="<label><input type='radio' name='xprofile-condition-other-field-value' value='"+field.options[i].id+"'>"+field.options[i].name +"</label>"
            }
            
        }else{
            
           jq('#xprofile-condition-operator option.condition-multi').hide(); 
           jq('#xprofile-condition-operator option.condition-single').show(); 
           list ='<input type="text" name="xprofile-condition-other-field-value" id="xprofile-condition-other-field-value">';
           
        }
        
        jq('#xprofile-condition-other-field-value-container').html(list);
       
        
    })
} );