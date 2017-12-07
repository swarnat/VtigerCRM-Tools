var VtigerTools = {
    'initLayoutEditor': function() {
        var container = jQuery('#layoutEditorContainer');

        jQuery('.editFields', container).on('dblclick', function(e) {
            e.stopPropagation();
            var params = {
                module: 'SwVtTools',
                action: 'LayoutEditorFieldGet',
                parent: 'Settings',
                fieldid: jQuery(this).data('field-id')
            };

            this.fieldID = jQuery(this).data('field-id');
            AppConnector.request(params).then(jQuery.proxy(function(data) {
                var newLabel = prompt('Please enter new Fieldlabel.\n\nYou must create the translations manually in the corresponding files!', data.fieldLabel);

                if(newLabel !== null) {
                    var params = {
                        module: 'SwVtTools',
                        action: 'LayoutEditorFieldSet',
                        parent: 'Settings',
                        fieldid: this.fieldID,
                        fieldLabel: newLabel
                    };

                    AppConnector.request(params);

                    var fieldLabel = jQuery(jQuery('.fieldLabel', jQuery(this))[0]);
                    if(jQuery('.redColor', fieldLabel).length > 0) {
                        newLabel = '<span class="redColor">*</span>' + newLabel;
                    }
                    fieldLabel.html(newLabel + '&nbsp;');

                }
            }, this));
        });

        jQuery('.editFieldsTable', container).on('dblclick', function(e) {
            e.stopPropagation();
            var params = {
                module: 'SwVtTools',
                action: 'LayoutEditorBlockGet',
                parent: 'Settings',
                blockid: jQuery(this).data('block-id')
            };

            this.blockID = jQuery(this).data('block-id');
            AppConnector.request(params).then(jQuery.proxy(function(data) {
                var newLabel = prompt('Please enter new Blocklabel.\n\nYou must create the translations manually in the corresponding files!', data.blockLabel);

                if(newLabel !== null) {
                    var params = {
                        module: 'SwVtTools',
                        action: 'LayoutEditorBlockSet',
                        parent: 'Settings',
                        blockid: this.blockID,
                        blockLabel: newLabel
                    };

                    AppConnector.request(params);

                    var fieldLabel = jQuery(jQuery('.blockLabel strong', jQuery(this))[0]);
                    fieldLabel.html(newLabel);

                }
            }, this));
        });
    }
};

var SWVtigerTools = {
    registeredFilter: {},
    registerFilter: function(key, callback) {
        if(typeof SWVtigerTools.registeredFilter[key] === 'undefined') {
            SWVtigerTools.registeredFilter[key] = [];
        }

        SWVtigerTools.registeredFilter[key].push(callback);
    },
    filter: function(key, param) {
        if(typeof SWVtigerTools.registeredFilter[key] === 'undefined') {
            return param;
        }

        var params = [param];
        if(arguments.length > 2) {
            for(var i = 2;i < arguments.length;i++) {
                params.push(arguments[i]);
            }
        }

        jQuery.each(SWVtigerTools.registeredFilter[key], function(index, value) {
            var newParam = value.apply({}, params);
            params[0] = newParam;
            if(typeof newParam !== 'undefined') {
                param = newParam;
            }
        });
        return param;
    }
};

jQuery(function() {

    if(jQuery('#layoutEditorContainer').length > 0) {
        VtigerTools.initLayoutEditor();
        var container = jQuery('#layoutEditorContainer');
        container.on('change', '[name="layoutEditorModules"]', function(e) {
            window.setTimeout('VtigerTools.initLayoutEditor();', 2000);
        });
    }

});

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

/** ACTIONHANDLER START **/