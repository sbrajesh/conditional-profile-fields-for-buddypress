jQuery( document ).ready( function ( $ ) {
    // all bp fields info.
    var all_fields = xpfields.fields;
    // only fields which are used to trigger conditions.
    var conditional_fields = xpfields.conditional_fields;
    // only for logged user, this is fetched if the user is logged in and depends on whether viewing a profile or not
    var data = xpfields.data;

    var has_data = !jQuery.isEmptyObject( data );

    // building a list of normal fields(except checkboxes/radio) that trigger a visibility condition.
    var fields = [];
    // list of the fields which is either radio or checkbox and trigger some condition
    var multi_fields = []; // radio, checkboxes
    // fields having select boxes.
    var select_fields = [];

    //Build the fields, multi fields array
    for ( var field_id in conditional_fields ) {

        if ( ! conditional_fields.hasOwnProperty( field_id ) ) {
            continue;
        }

        var $field = $( '.' + field_id );

        if ( $field.get( 0 ) ) {
            //continue;// the field does not exist on this page.
            // store the field id as a data attribute.
            $field.data( 'cpfb-field-id', field_id );
        }


        var field = all_fields[field_id];

        // must be a valid existing field and supported too.
        if ( ! field || field['type'] == 'datebox' || field['type'] == 'birthdate') {
            continue;
        }

        var field_type = field['type'];

        var found = true;// assume we found the field type.

        if ( $field.find( '.input-options' ).get( 0 ) || $.inArray( field_type, ['radiobutton', 'checkbox'] ) !== -1 ) {
            // radio or checkbox.
            multi_fields.push( field_id );
        } else if ( $field.find( 'select' ).get( 0 ) || $.inArray( field_type, ['selectbox', 'multiselectbox'] ) !== -1 ) {
            // select or multi select field.
            select_fields.push( field_id );
        } else if ( $.inArray( field_type, [ 'textbox', 'textarea', 'url', 'web', 'number', 'decimal_number', 'number_minmax', 'email', 'color' ] ) !== -1 ) {
            fields.push( field_id );
        } else {
            found = false;
        }

        if ( ! found ) {
            // detect field type if possible.

            console.log( "Unable to understand field id:" + field_id );
            // we were unable to understand the field type and field.
            // need to check extra. here?
            // @todo in future.
        }
    }
    // We have separated the triggers into 3 group.
    // now we will need to setup triggers and apply initial condition.

    // Step 1: Setup triggers.
    // Set trigger for simple fields.
    var simple_field_selectors = [];
    for ( var i = 0; i < fields.length; i++) {
        simple_field_selectors.push( '#' + fields[i] );
    }

    // trigger for normal fields.'text', 'number' etc.
    if ( simple_field_selectors.length > 0 ) {
        $( document ).on( 'change', simple_field_selectors.join( ',' ), function () {
            apply_condition( this );
        } );
    }

    // 1.B Set trigger for radio, checkboxes.
    var multifield_selectors = [];// reset.
    for ( var i = 0; i < multi_fields.length; i++ ) {
        multifield_selectors.push( '.' + multi_fields[i] + ' .input-options input' );
    }

    if ( multifield_selectors.length > 0 ) {
        $( document ).on( 'click', multifield_selectors.join( ',' ), function () {
            apply_condition( this );
        } );
    }

    // 1.C Set trigger for select fields.
    var select_fields_selectors = [];
    for ( var i = 0; i < select_fields.length; i++ ) {
        select_fields_selectors.push( '.' + select_fields[i] + ' select' );
    }

    if ( select_fields_selectors.length > 0 ) {
        $( document ).on( 'change', select_fields_selectors.join( ',' ), function () {
            apply_condition( this );
        } );
    }

    // Apply initial conditions on page load.
    // try to see if any condition matches and sho hide/show appropriate field on page load

    // 2.A simple field.
    // test only after has_data??
    for ( var i = 0; i < fields.length; i++ ) {
        apply_initial_condition( fields[ i ] );
    }

    // 2.B Multi Field.
    for ( var i = 0; i < multi_fields.length; i++ ) {
        apply_initial_condition( multi_fields[ i ] );
    }

    // 2.C Select fields.
    for ( var i = 0; i < select_fields.length; i++ ) {
        apply_initial_condition( select_fields[ i ] );
    }


    /**
     * Applies a condition to the field
     *
     * @param {type} element
     * @returns {undefined}
     */
    function apply_condition( element ) {

        var $el = $( element );
        var $field = $el.parents( '.editfield' );

        if ( ! $field.get( 0 ) ) {
            // log error, return.
        }

        var id = $field.data( 'cpfb-field-id' );

        if ( ! id ) {
            // log error
            // return
        }

        //get the field associated with this condition
        var trigger_field = conditional_fields[ id ];

        //is there really a condition associated with field, if not, do not proceed
        if ( trigger_field === undefined ) {
            return;
        }

        var current_val = '';

        if ( $.inArray( id, multi_fields ) === -1 ) {
            // not a multi field.
            current_val = $el.val();
        } else {
            // multi field.
            current_val = [];
            $field.find( '.input-options input:checked' ).each( function (){
                current_val.push( $(this).val());
            });
        }
        apply_trigger_change( current_val, trigger_field );
    }

    /**
     * Apply initial condition.
     *
     * @param {string} field_id
     */
    function apply_initial_condition( field_id ) {

        var current_val = data[ field_id ];
        // if no value, let us check the dom for selected value
            if ( current_val ) {
                current_val = current_val.value;
            } else {
            //check dom for the value
            var $field = $( '.' + field_id );

            if ( $field.get( 0 ) ) {

                if ($.inArray(field_id, multi_fields) === -1) {
                    // not a multi field.
                    current_val = $('#' + field_id).val();
                } else {
                    // multi field.
                    current_val = $field.find('.input-options input:checked').val();
                }
            } else{
                current_val = '';
            }
        }

        if ( current_val === undefined ) {
            current_val = '';
        }

        apply_trigger_change( current_val, conditional_fields[ field_id ] );
    }

    /**
     * Apply the change based on trigger field.
     *
     * @param val
     * @param trigger_field
     */
    function apply_trigger_change( val, trigger_field ) {
        //if we are here, process the conditions
        for ( var i = 0; i < trigger_field.conditions.length; i++ ) {
            // apply each condition which depend on this field.
            var condition = trigger_field.conditions[ i ];
            var matched = is_match( val, condition.value, condition.operator );

            show_hide_field(condition.field_id, condition.visibility, matched);
        }
    }

    /**
     * Sow or Hide an entry in the form, hides whole editable div
     * It is based on the assumption that BuddyPress profile edit field parent div have a class 'editfield' and another class 'field_id'
     * This should work for 98% of the themes
     * For the rest 2 % blame their developers :D
     *
     *
     * @param {int} field_id
     * @param {type} visibility
     * @param {boolean} match reverses visibility condition
     * @returns {undefined}
     */
    function show_hide_field( field_id, visibility, match ) {
        // we have the field id
        // so we can understand the behaviour of this field
        var $el = $( '.field_' + field_id );

        if ( ! $el.get( 0 ) ) {
            console.log( 'Conditional Profile Fields: There seems to be some html issue and I am not able to fix it, Please tell that to the developer: field_id:' + field_id );
            return;
        }

        if ( ! match ) {
            // if the condition did not match, reverse visibility condition
            if ( visibility == 'show' ) {
                visibility = 'hide';
            } else {
                visibility = 'show';
            }
        }

        if ( visibility == 'show' ) {
            $el.show();
        } else {
            $el.hide();
        }
    }

    /**
     * Check if the current value matches the conditional value
     *
     * @param {type} selected_val
     * @param {type} val
     * @param {type} operator
     * @returns {Boolean}
     */
    function is_match( selected_val, val, operator ) {
        var values = [];

        if ( ! jQuery.isArray( selected_val ) ) {
            values.push( selected_val );
        } else {
            values = selected_val;//it is array
        }

        for ( var i = 0; i < values.length; i++) {
            if ( match_condition( values[i], val, operator ) ) {
                return true; //bad coding I know
            }
        }

        return false;
    }

    /**
     * Check if the current value and the actual value satisfies the condition imposed by 'operator'
     * @param current_val
     * @param val
     * @param operator
     * @returns {boolean}
     */
    function match_condition( current_val, val, operator ) {

        var condition_matched = false;
        switch ( operator ) {

            case '=':

                if ( current_val == val ) {
                    condition_matched = true;
                }

                break;

            case '!=':

                if ( current_val != val ) {
                    condition_matched = true;
                }

                break;

            case '<=':

                if ( current_val <= val ) {
                    condition_matched = true;
                }

                break;

            case '>=':

                if ( current_val >= val ) {
                    condition_matched = true;
                }

                break;

            case '<':

                if ( current_val < val ) {
                    condition_matched = true;
                }

                break;

            case '>':

                if ( current_val > val ) {
                    condition_matched = true;
                }

                break;
        }

        return condition_matched;
    }
});
