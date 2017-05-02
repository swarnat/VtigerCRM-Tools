/**
 * RedooUtils V1.0.11
 * 1.0.11 - Add refreshContainer function
 * 1.0.10 - Add getRecordLabels function
 * 1.0.9  - Add fillFieldSelect, loadStyles functions
 * 1.0.8  - Add returnInput Parameter to getFieldElement function
 * 1.0.7  - Add RedooUtils.loadScript
 */
(function($) {
    var ScopeName = 'SwVtTools';
    var Version = '1.0.11';

    var _RedooCache = {
        'FieldCache': {},
        'FieldLoadQueue': {},
        'viewMode':false,
        'popUp':false
    };
    var RedooCache = {
        get: function(key, defaultValue) {
            if(typeof _RedooCache[key] != 'undefined') {
                return _RedooCache[key];
            }
            return defaultValue;
        },
        set: function(key, value) {
            _RedooCache[key] = value;
        }
    };

    var RedooUtils = {
        layout:'vlayout',
        currentLVRow:null,
        getRecordLabels: function(ids) {
            var aDeferred = jQuery.Deferred();

            var newIds = [];
            var LabelCache = RedooCache.get('LabelCache', {});
            jQuery.each(ids, function(index, value) {
                if(typeof LabelCache[value] == 'undefined') {
                    newIds.push(value);
                }
            });

            if(newIds.length > 0) {
                RedooAjax.postAction('RecordLabel', {
                    ids         : newIds,
                    'dataType'  :'json'
                }).then(function(response) {
                    jQuery.each(response.result, function(id, value) {
                        LabelCache[id] = value;
                    });
                    RedooCache.set('LabelCache', LabelCache);

                    aDeferred.resolveWith({}, [LabelCache]);
                });
            } else {
                aDeferred.resolveWith({}, [LabelCache]);
            }


            return aDeferred.promise();
        },
        getFieldList: function (moduleName) {
            var aDeferred = jQuery.Deferred();

            if(typeof _RedooCache['FieldLoadQueue'][moduleName] != 'undefined') {
                return _RedooCache['FieldLoadQueue'][moduleName];
            }
            _RedooCache['FieldLoadQueue'][moduleName] = aDeferred;

            if(typeof _RedooCache.FieldCache[moduleName] != 'undefined') {
                aDeferred.resolve(_RedooCache.FieldCache[moduleName]);
                return aDeferred.promise();
            }

            RedooAjax.post('index.php', {
                'module': ScopeName,
                'parent': 'Settings',
                'action': 'GetFieldList',
                'module_name': moduleName
            }, 'json').then(function (data) {
                _RedooCache.FieldCache[moduleName] = data;

                aDeferred.resolve(data.fields);
            });

            return aDeferred.promise();
        },
        fillFieldSelect:function(fieldId, selected, module) {
            if(typeof module == 'undefined') module = moduleName;
            if(typeof selected == 'string') selected = [selected];

            RedooUtils.getFieldList(module).then(function(fields) {
                var html = '';
                jQuery.each(fields, function(blockLabel, fields) {
                    html += '<optgroup label="' + blockLabel + '">';
                    jQuery.each(fields, function(index, field) {

                        html += '<option value="' + field.name + '" ' + (jQuery.inArray(field.name, selected) != -1 ? 'selected="selected"' : '') + '>' + field.label + '</option>';
                    });
                    html += '</optgroup>';

                    jQuery('#' + fieldId).html(html);
                    if(jQuery('#' + fieldId).hasClass('select2')) {
                        jQuery('#' + fieldId).select2('val', selected);
                    }
                });
            });
        },
        _getDefaultParentEle: function() {
            return 'div#page';
        },
        getMainModule:function (parentEle) {
            if(typeof parentEle == 'undefined') parentEle = RedooUtils._getDefaultParentEle();
            var viewMode = RedooUtils.getViewMode(parentEle);

            if (viewMode == 'detailview' || viewMode == 'summaryview') {
                return $('#module', parentEle).val();
            } else if (viewMode == 'editview' || viewMode == 'quickcreate') {
                return $('[name="module"]', parentEle).val();
            } else if (viewMode == 'listview') {
                return $('#module', parentEle).val();
            } else if (viewMode == 'relatedview') {
                if ($('[name="relatedModuleName"]', parentEle).length > 0) {
                    return $('[name="relatedModuleName"]', parentEle).val();
                }
                if ($('#module', parentEle).length > 0) {
                    return $('#module', parentEle).val();
                }
            }
            return '';
        },
        getRecordIds: function(parentEle) {
            if(typeof parentEle == 'undefined') parentEle = RedooUtils._getDefaultParentEle();
            var recordIds = [];
            var viewMode = RedooUtils.getViewMode(parentEle);

            if(viewMode == 'detailview' || viewMode == 'summaryview') {
                recordIds.push($('#recordId', parentEle).val());
            } else if(viewMode == 'quickcreate') {
                // do nothing
            } else if(viewMode == 'editview') {
                recordIds.push($('[name="record"]').val());
            } else if(viewMode == 'listview') {
                $('.listViewEntries').each(function(index, value) {
                    recordIds.push($(value).data('id'));
                });
            } else if(viewMode == 'relatedview'){
                $('.listViewEntries').each(function(index, value) {
                    recordIds.push($(value).data('id'));
                });
            }

            return recordIds;
        },
        getViewMode: function(parentEle) {
            if(typeof parentEle == 'undefined') parentEle = RedooUtils._getDefaultParentEle();

            var viewEle = $("#view", parentEle);

            _RedooCache.viewMode = false;

            if(viewEle.length > 0 && viewEle[0].value == "List") {
                _RedooCache.viewMode = "listview";
            }

            if($(".detailview-table", parentEle).length > 0) {
                _RedooCache.viewMode = "detailview";
            } else if($(".summaryView", parentEle).length > 0) {
                _RedooCache.viewMode = "summaryview";
            } else if($(".recordEditView", parentEle).length > 0) {
                if($('.quickCreateContent', parentEle).length == 0) {
                    _RedooCache.viewMode = "editview";
                } else {
                    _RedooCache.viewMode = "quickcreate";
                }
            }

            if($('.relatedContents', parentEle).length > 0) {
                _RedooCache.viewMode = "relatedview";

                if($('td[data-field-type]', parentEle).length > 0) {
                    _RedooCache.popUp = false;
                } else {
                    _RedooCache.popUp = true;
                }
            }

            if(_RedooCache.viewMode === false) {
                if($('#view', parentEle).length > 0) {
                    if($('#view', parentEle).val() == 'Detail') {
                        _RedooCache.viewMode = 'detailview';
                    }
                }
            }

            return _RedooCache.viewMode;
        },
        getFieldElement:  function(fieldName, parentEle, returnInput) {
            if(typeof parentEle == 'undefined' || parentEle == null) parentEle = RedooUtils._getDefaultParentEle();
            if(typeof returnInput == 'undefined') returnInput = false;

            if(typeof fieldName == "object") {
                return fieldName;
            }
            var fieldElement = false;

            if(RedooUtils.getViewMode(parentEle) == "detailview") {
                if($('#' + RedooUtils.getMainModule(parentEle) + '_detailView_fieldValue_' + fieldName, parentEle).length > 0 || $('#Events_detailView_fieldValue_' + fieldName, parentEle).length > 0) {
                    fieldElement = $('#' + RedooUtils.getMainModule(parentEle) + '_detailView_fieldValue_' + fieldName);

                    if(RedooUtils.getMainModule(parentEle) == 'Calendar' && fieldElement.length == 0) {
                        fieldElement = $('#Events_detailView_fieldValue_' + fieldName, parentEle);
                    }
                } else if($('#_detailView_fieldValue_' + fieldName, parentEle).length > 0) {
                    fieldElement = $('#_detailView_fieldValue_' + fieldName, parentEle);
                }
            } else if(RedooUtils.getViewMode(parentEle) == "summaryview") {
                var ele = $('[name="'+fieldName+'"]', parentEle);

                /*if(ele.length == 0) {
                 if(typeof this.summaryFields[fieldName] != 'undefined') {
                 fieldElement = $($(RedooUtils.layout == 'vlayout' ? '.summary-table td.fieldValue' : '.summary-table div.mycdivfield')[this.summaryFields[fieldName] - 1]);
                 } else {
                 return false;
                 }
                 } else {*/
                fieldElement = $(ele[0]).closest(RedooUtils.layout == 'vlayout' ? 'td' : 'div.mycdivfield');
                //}
            } else if(RedooUtils.getViewMode(parentEle) == "editview" || RedooUtils.getViewMode(parentEle) == 'quickcreate') {
                var ele = $('[name="' + fieldName + '"]', parentEle);

                if(ele.length == 0) {
                    return false;
                }

                if(returnInput == true) {
                    return ele;
                }

                fieldElement = $(ele[0]).closest(RedooUtils.layout == 'vlayout' ? '.fieldValue' : 'div.mycdivfield');
            } else if(RedooUtils.getViewMode(parentEle) == 'listview') {
                if(RedooUtils.listViewFields === false) {
                    RedooUtils.listViewFields = {};
                    var cols = jQuery(jQuery(".listViewEntriesTable .listViewHeaders", parentEle)[0]).find("th a");

                    for(var colIndex in cols ) {
                        if (cols.hasOwnProperty(colIndex) && jQuery.isNumeric(colIndex)) {
                            var value = cols[colIndex];

                            if(jQuery(value).data("columnname") == undefined) {
                                RedooUtils.listViewFields[jQuery(value).data("fieldname")] = colIndex;
                            } else {
                                RedooUtils.listViewFields[jQuery(value).data("columnname")] = colIndex;
                            }
                        }
                    }
                }

                if (RedooUtils.currentLVRow !== null) {
                    if(typeof RedooUtils.listViewFields[fieldName] != 'undefined') {
                        if (RedooUtils.listViewFields[fieldName] >= 0) {
                            fieldElement = $($('td.listViewEntryValue', RedooUtils.currentLVRow)[RedooUtils.listViewFields[fieldName]]);
                        } else {
                            fieldElement = $($('td.listViewEntryValue', RedooUtils.currentLVRow)[Number(RedooUtils.listViewFields[fieldName] + 100) * -1]);
                        }

                    } else {
                        return false;
                    }
                } else {
                    return false;
                }

            } else if(RedooUtils.getViewMode() == 'relatedview') {
                if($('td[data-field-type]', RedooUtils.currentLVRow).length > 0) {
                    fieldElement = $($('td[data-field-type]', RedooUtils.currentLVRow)[RedooUtils.listViewFields[fieldName]]);
                } else {
                    fieldElement = $($('td.listViewEntryValue', RedooUtils.currentLVRow)[RedooUtils.listViewFields[fieldName]]);
                }
            }

            return fieldElement;
        },
        loadStyles:function(urls, nocache) {
            if(typeof urls == 'string') urls = [urls];
            var aDeferred = jQuery.Deferred();
            if (typeof nocache=='undefined') nocache=false; // default don't refresh
            $.when.apply($,
                $.map(urls, function(url){
                    if (nocache) url += '?_ts=' + new Date().getTime(); // refresh?
                    return $.get(url, function(){
                        $('<link>', {rel:'stylesheet', type:'text/css', 'href':url}).appendTo('head');
                    });
                })
            ).then(function(){
                aDeferred.resolve();
            });

            return aDeferred.promise();
        },
        loadScript:function(url, options) {
            var aDeferred = jQuery.Deferred();
            if(typeof RedooCache.loadedScript == 'undefined') {
                RedooCache.loadedScript = {};
            }
            if(typeof RedooCache.loadedScript[url] != 'undefined') {
                aDeferred.resolve();
                return aDeferred;
            }

            // Allow user to set any option except for dataType, cache, and url
            options = jQuery.extend( options || {}, {
                dataType: "script",
                cache: true,
                url: url
            });

            // Use $.ajax() since it is more flexible than $.getScript
            // Return the jqXHR object so we can chain callbacks
            return jQuery.ajax( options );
        }
    };

    var RedooAjax = {
        refreshContainer: function(container) {
            var url = $(container).data('url');

            var result = RedooAjax.get(url).then(function(response) {
                $(container).html(response);
            });
            return result;
        },
        postAction: function(actionName, params, settings, dataType) {
            params.module = ScopeName;
            params.action = actionName;

            if(typeof settings != 'undefined' && settings == true) {
                params.parent = 'Settings';
            }

            return RedooAjax.post('index.php', params, dataType);
        },
        postView: function(viewName, params, settings, dataType) {
            params.module = ScopeName;
            params.view = viewName;

            if(typeof settings != 'undefined' && settings == true) {
                params.parent = 'Settings';
            }

            return RedooAjax.post('index.php', params, dataType);
        },
        /**
         *
         * @param url URL to call
         * @param params Object with POST parameters
         * @param dataType Single value of datatype if not set in params
         * @returns {*}
         */
        post: function (url, params, dataType) {
            var aDeferred = jQuery.Deferred();

            if (typeof url == 'object') {
                params = url;
                url = 'index.php';
            }

            if (typeof callback != 'undefined') {
                aDeferred.then(callback)
                //callback = function(data) { };
            }
            if (typeof params == 'undefined') {
                params = {};
            }
            if (typeof dataType == 'undefined' && typeof params.dataType != 'undefined') {
                dataType = params.dataType;
            }

            var options = {
                url: url,
                data: params,
            };

            if (typeof dataType != 'undefined') {
                options.dataType = dataType;
            }

            options.type = 'POST';

            jQuery.ajax(options)
                .always(function (data) {
                    if (typeof data.success != 'undefined') {
                        if (data.success == false && (data.error.code.indexOf('request') != -1)) {
                            if(confirm('Request Error. Reload of Page is required.')) {
                                window.location.reload();
                            }
                            return;
                        }
                    }

                    aDeferred.resolve(data);
                    //callback(data)
                });

            return aDeferred.promise();
        },
        get: function (url, params, dataType) {
            //console.error('Vtiger do not support GET Requests');
            //return;
            var aDeferred = jQuery.Deferred();

            if (typeof url == 'object') {
                params = url;
                url = 'index.php';
            }

            if (typeof params == 'undefined') {
                params = {};
            }
            if (typeof dataType == 'undefined' && typeof params.dataType != 'undefined') {
                dataType = params.dataType;
            }

            var options = {
                url: url,
                data: params
            };

            if (typeof datatype != 'undefined') {
                options.dataType = dataType;
            }

            options.type = 'GET';

            jQuery.ajax(options)
                .always(function (data) {
                    if (typeof data.success != 'undefined') {
                        if (data.success == false && (data.error.code.indexOf('request') != -1)) {
                            if(confirm('Request Error. Reload of Page is required.')) {
                                window.location.reload();
                            }
                            return;
                        }
                    }

                    aDeferred.resolve(data);
                    //callback(data)
                });

            return aDeferred.promise();
        },
        /**
         * Drop In Replacement for AppConnector.request
         *
         * @param params object
         * @returns {*}
         */
        request: function (params) {
            return RedooAjax.post('index.php', params);
        }
    };

    if(typeof window.RedooStore == 'undefined') {
        window.RedooStore = {};
    }

    window.RedooStore[ScopeName] = {
        'Ajax': RedooAjax,
        'Utils': RedooUtils,
        'Cache': RedooCache
    };

    if(typeof window.RedooAjax == 'undefined') {
        /**
         *
         * @param ScopeName
         * @returns RedooAjax
         * @constructor
         */
        window.RedooAjax = function(ScopeName) {
            if(typeof window.RedooStore[ScopeName] != 'undefined') {
                return window.RedooStore[ScopeName]['Ajax'];
            }
            console.error('RedooAjax ' + ScopeName + ' Scope not found');
        }
    }
    if(typeof window.RedooUtils == 'undefined') {
        /**
         *
         * @param ScopeName
         * @returns RedooUtils
         * @constructor
         */
        window.RedooUtils = function(ScopeName) {
            if(typeof window.RedooStore[ScopeName] != 'undefined') {
                return window.RedooStore[ScopeName]['Utils'];
            }
            console.error('RedooUtils ' + ScopeName + ' Scope not found');
        }
    }
    if(typeof window.RedooCache == 'undefined') {
        /**
         *
         * @param ScopeName
         * @returns RedooUtils
         * @constructor
         */
        window.RedooCache = function(ScopeName) {
            if(typeof window.RedooStore[ScopeName] != 'undefined') {
                return window.RedooStore[ScopeName]['Cache'];
            }
            console.error('RedooCache ' + ScopeName + ' Scope not found');
        }
    }
})(jQuery);
