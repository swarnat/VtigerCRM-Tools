/**
 * Created by Stefan on 03.12.2016.
 */
(function($) {
    $(function() {
        if(typeof Vtiger_Edit_Js != 'undefined') {
            if(typeof VtigerTools !== "undefined") {
                SWVtigerTools.registerFilter("BasicSearchParams", function(params, element) {
                    params.src_field = $(element).attr('name').replace("_display", "");

                    return params;
                });
            }
        }
    });
})(jQuery);