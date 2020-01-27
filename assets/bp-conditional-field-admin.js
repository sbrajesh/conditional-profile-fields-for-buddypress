jQuery(document).ready(function () {

    var jq = jQuery;
    //move the condition box to the bottom of the screen,
    //buddypress does not have an appropriate hook, so we do it via js
    jq('#postbox-container-2').append(jq('#xprofile-field-condition'));

    //var xpfields;
    jq(document).on('change', '#xprofile-condition-other-field', function (evt) {
        var selected = jq(':selected', this).val();//the field that was selected

        //1. check for the type of the field

        var field = xpfields['field_' + selected];
        if (field == undefined)
            return;

        var list = '';

        if (field.type == 'multi') {

            jq('#xprofile-condition-operator option.condition-single').hide();
            jq('#xprofile-condition-operator option.condition-multi').show();

            for (var i = 0; i < field.options.length; i++) {
                list += "<label><input type='radio' name='xprofile-condition-other-field-value' value='" + field.options[i].id + "'>" + field.options[i].name + "</label>"
            }

        } else {

            jq('#xprofile-condition-operator option.condition-multi').hide();
            jq('#xprofile-condition-operator option.condition-single').show();
            list = '<input type="text" name="xprofile-condition-other-field-value" id="xprofile-condition-other-field-value">';

        }

        jq('#xprofile-condition-other-field-value-container').html(list);


    });

    // trigger a checkbox<->radio button change whenever the opator is modified (multiple selection only allowed with operator '=')
    jq('#xprofile-condition-operator').on('change', switchOtherFieldButtonType);

    function switchOtherFieldButtonType() {
        var operator = jq('#xprofile-condition-operator');
        var boxes = jq('#xprofile-condition-other-field-value-container input[type="checkbox"], #xprofile-condition-other-field-value-container input[type="radio"]');

        // If the operator is not '=', than change checkboxes into radios
        if (boxes.length > 0 && operator.val() != "=") {

            // keep the first element that is checked
            let checked = jq('xprofile-condition-other-field-value-container').find('input:checked');

            if (checked.length > 1) {
                for (i = 1; i < checked.length; i++) {
                    checked[i].checked = false;
                }
            }
            boxes.attr('type', 'radio');
            boxes.attr('name', 'xprofile-condition-other-field-value');

        }
        else if (boxes.length > 0) {
            // If the operator is the '=' operator, than change all boxes into checkboxes
            boxes.attr('type', 'checkbox');
            boxes.attr('name', 'xprofile-condition-other-field-value[]');
        }
    }

    // Trigger it on page load (if an operator different from '=' was saved)
    switchOtherFieldButtonType();
});
