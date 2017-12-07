jQuery.fn.bindFirst = function(name, fn) {
    // bind as you normally would
    // don't want to miss out on any jQuery magic
    this.on(name, fn);

    // Thanks to a comment by @Martin, adding support for
    // namespaced events too.
    this.each(function() {
        var handlers = $._data(this, 'events')[name.split('.')[0]];
        // take out the handler we just inserted from the end
        var handler = handlers.pop();
        // move it at the beginning
        handlers.splice(0, 0, handler);
    });
};
jQuery(function() {
	jQuery('[name="listPrice"][data-decimal-seperator=","]').bindFirst('keyup', function() {
        var start = this.selectionStart;
		var end = this.selectionEnd;

		jQuery(this).val(jQuery(this).val().replace('.',','));

		this.setSelectionRange(start, end);

	});
	jQuery('.listPrice, .qty, .lineItemInputBox, .currencyField, .discountVal, .groupTaxPercentage, .chargeTaxPercentage').bindFirst('keyup', function() {
	  // store current positions in variables
		var start = this.selectionStart;
		var end = this.selectionEnd;

        if(jQuery(this).data('decimalSeperator') != ',') {
		    jQuery(this).val(jQuery(this).val().replace(/,/,'.'));
        }

		this.setSelectionRange(start, end);
	});
});
/*
jQuery('.input-large[data-fieldinfo]').bindFirst('keyup', function() {
    var fieldData = jQuery(this).data();
    var fieldInfo = fieldData.fieldinfo;
    if(typeof fieldInfo == 'string') {
        fieldInfo = JSON.parse(fieldInfo);
    }

    if(typeof fieldInfo != 'undefined' &&
      (fieldInfo.type == 'double' || fieldInfo.type == 'integer')) {
            var oldContent = jQuery(this).val();
            var newContent = oldContent.replace(/,/,'.');

            if(oldContent != newContent) {

                var start = this.selectionStart;
                var end = this.selectionEnd;

                jQuery(this).val(newContent);

                this.setSelectionRange(start, end);
            }

    }

});
*/