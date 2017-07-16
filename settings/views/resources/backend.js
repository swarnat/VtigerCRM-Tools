/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 10.09.14 17:27
 * You must not use this file without permission.
 */
var SwVtTools = {
    commaNumbers:function (value) {
        jQuery.post('index.php?module=SwVtTools&parent=Settings&action=SetCommaNumbers', {value:value}, function() {
            window.location.reload();
        });
        return false;
    },
    GCalSync:function (value) {
        jQuery.post('index.php?module=SwVtTools&parent=Settings&action=SetGCalSync', {value:value}, function() {
            window.location.reload();
        });
        return false;
    },
    initGCalSync:function () {
        jQuery.post('index.php', { module:'SwVtTools', parent:'Settings', action: 'InitGCalSync' }, function() {
            alert('Tables sucessfully created!');
        });
        return false;
    },
    GeneralOptions:function(option, value, output) {
        if(typeof output === 'undefined') output = false;

        jQuery.post('index.php', { module:'SwVtTools', parent:'Settings', action: 'GeneralOptions', option:option, value:value }, function(response) {
            if(output === true) {
                alert('Successfully Applied.\n' + response);
            } else {
                alert('Successfully Applied.');
            }

        });
        return false;

    },

    AdvancedOptions:function(option, value) {
        var accessKey = prompt('Your AccessKey:');
        if(accessKey === null) return;
        jQuery.post('index.php', { module:'SwVtTools', parent:'Settings', action: 'AdvancedOptions', access: accessKey, option:option, value:value }, function() {
            alert('Successfully Applied');
        });
    }
};

(function($) {
    $(function() {
        if($('.addreferencefield').val() == '') return;

        $('.addReferenceFilter').on('click', function() {
            RedooAjax('SwVtTools').postAction('GeneralOptions', {
                'mode':'ReferenceFilterAdd',
                'field': $('#addreferencefield').val()
            }, true).then(function() {
                refreshReferenceList();
            });
        });

        function refreshReferenceList() {
            RedooAjax('SwVtTools').refreshContainer('#ReferenceFilterList').then(function () {
                $('.EditReferenceFilter').on('click', function (e) {
                    var id = $(e.currentTarget).closest('tr').data('id');
                    e.preventDefault();

                    RedooAjax('SwVtTools').postView('ReferenceFilterEditor', {
                        'id': id
                    }, true).then(function(response) {
                        $('#ReferenceFilterId').val(id);

                        $('#ReferenceFilterCondition').html(response);
                        $('#ReferenceFilterEditor').show();

                    });
                });

                $('.DeleteReferenceFilter').on('click', function (e) {
                    var id = $(e.currentTarget).closest('tr').data('id');

                    RedooAjax('SwVtTools').postAction('GeneralOptions',
                        {
                            'mode'  : 'ReferenceFilterDelete',
                            'id'    : id
                        }, true).then(refreshReferenceList);
                    e.preventDefault();
                });
            });
        }

        $('.SaveReferenceFilter').on('click', function (e) {
            RedooAjax('SwVtTools').postAction('GeneralOptions', {
                'mode':'ReferenceFilterSave',
                'id': $('#ReferenceFilterId').val(),
                'condition' : $('#ReferenceFilterCondition').val()
            }, true);
        });

        refreshReferenceList();

        $('.ClearLogBtn').on('click', function(e) {
            var type = $(e.currentTarget).data('type');
            RedooAjax('SwVtTools').postAction('ClearLog', {
                'type': type
            }, true).then(function(response) {
                alert('Log was cleared');
            });
        });

        $('.Select2ForRelTabSelection').each(function(index, ele) {
            $(ele).select2({
                width:'100%',
                'multiple':true,
                data:relTabAvailable[$(ele).data('module')],
                initSelection: function(element, callback) {
                    var moduleName = $(element).data('module');

                    if (typeof relTabs[moduleName] !== "undefined") {

                        var selection = [];
                        $.each(relTabs[moduleName].relations.ids, function(index, value) {

                            var blockData = relTabAvailable[moduleName][relTabIndex[moduleName][value]];

                            if(typeof blockData != 'undefined') {
                                selection.push(blockData);
                            }
                        });

                        callback(selection);
                    }
                }
            }).select2('val', []);
            $(ele).select2("container").find("ul.select2-choices").sortable({
                containment: 'parent',
                start: function() { $(ele).select2("onSortStart"); },
                update: function() { $(ele).select2("onSortEnd"); }
            });
        });

        $('.Select2ForBlockSelection').each(function(index, ele) {
            $(ele).select2({
                width:'100%',
                'multiple':true,
                data:blocks[$(ele).data('module')],
                initSelection: function(element, callback) {
                    var ids = $(element).val();
                    var moduleName = $(element).data('module');

                    if (ids !== "") {
                        var parts = ids.split(',');

                        var selection = [];
                        $.each(parts, function(index, value) {

                            var blockData = blocks[moduleName][blockIndex[moduleName][value]];
                            if(typeof blockData != 'undefined') {
                                selection.push(blockData);
                            }
                        });
                        callback(selection);
                    }
                }
            });
            $(ele).select2("container").find("ul.select2-choices").sortable({
                containment: 'parent',
                start: function() { $(ele).select2("onSortStart"); },
                update: function() { $(ele).select2("onSortEnd"); }
            });
        });

        $('.trashPartialDetailView').on('click', function(e) {
            if(confirm('Really delete this view?') == false) return;
            var id = $(e.currentTarget).data('id');

            $.post('index.php', { module:'SwVtTools', parent:'Settings', action: 'GeneralOptions', option:'DeletePartialDetailView', id:id }, function(response) {
                $(e.currentTarget).closest('tr').remove();

                window.location.href = "index.php?module=SwVtTools&view=Index&parent=Settings&tab=tab2";
            });

        });
        $('.trashRelTabOrder').on('click', function(e) {
            if(confirm('Really delete this order?') == false) return;
            var id = $(e.currentTarget).data('id');

            $.post('index.php', { module:'SwVtTools', parent:'Settings', action: 'GeneralOptions', option:'DeleteRelatedTabOrder', id:id }, function(response) {
                $(e.currentTarget).closest('tr').remove();

                window.location.href = "index.php?module=SwVtTools&view=Index&parent=Settings&tab=tab3";
            });

        });
    });
})(jQuery);
