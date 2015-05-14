jQuery(function() {
    createTemplateFields('body');

    jQuery('body').trigger('inputFieldsReady');
    InitAutocompleteText();
});

function createTemplateFields(parentEle) {
    jQuery('.insertTextfield', parentEle).each(function(index, value) {
        var ele = jQuery(value);
        if(ele.data('parsed') == '1') {
            return;
        }

        ele.data('parsed', '1');

        var fieldName = ele.data('name');
        var fieldId = ele.data('id');

        if(typeof fieldId == 'undefined') {
            fieldId = fieldName.replace(/[^a-zA-Z0-9]/g, '');
        }

        var options = ele.data('options');
        var datalist = ele.data('datalist');
        var placeholder = ele.data('placeholder');

        if(typeof options != 'undefined' && options.length > 4) {
            try {
                var options = jQuery.parseJSON(options);
            } catch(e) {
                var options = {};
            }
        }
        if(typeof options == 'undefined' || options == '') {
            options = {};
        }
        var value = ele.html();
        if(typeof datalist != 'undefined') {
            options['datalist'] = datalist
        }
        if(typeof options['module'] == 'undefined') {
            options['module'] = moduleName;
        }
        if(typeof options['style'] == 'undefined' && typeof ele.data('style') != 'undefined') {
            options['style'] = ele.data('style');
        }
        if(placeholder != null) {
            options['placeholder'] = placeholder;
        }

        if(ele.attr('readonly')) {
            options['readonly'] = true;
        }

        value = value.replace(/\<\!--\?/g, '<?').replace(/\?--\>/g, '?>');

        var html = createTemplateTextfield(fieldName, fieldId, value, options);
        ele.html(html);
    });
    jQuery('.insertTextarea', parentEle).each(function(index, value) {
        var ele = jQuery(value);
        if(ele.data('parsed') == '1') {
            return;
        }

        ele.data('parsed', '1');

        var fieldName = ele.data('name');
        var fieldId = ele.data('id');

        if(typeof fieldId == 'undefined') {
            fieldId = fieldName.replace(/[^a-zA-Z0-9]/g, '');
        }
        var options = ele.data('options');

        if(typeof options != 'undefined' && options.length > 4) {
            try {
                var options = jQuery.parseJSON(options);
            } catch(e) {
                console.log(e.message, options);
                var options = {};
            }
        }
        if(typeof options == 'undefined' || options == '') {
            options = {};
        }
        var value = ele.html();

        value = value.replace(/\<\!--\?/g, '<?').replace(/\?--\>/g, '?>');

        var html = createTemplateTextarea(fieldName, fieldId, value, options);
        ele.html(html);
    });

    InitAutocompleteText();
}

jQuery.assocArraySize = function(obj) {
    // http://stackoverflow.com/a/6700/11236
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

jQuery.loadScript = function (url, arg1, arg2) {
  var cache = false, callback = null;
  //arg1 and arg2 can be interchangable
  if ($.isFunction(arg1)){
    callback = arg1;
    cache = arg2 || cache;
  } else {
    cache = arg1 || cache;
    callback = arg2 || callback;
  }

  var load = true;
  //check all existing script tags in the page for the url
  jQuery('script[type="text/javascript"]')
    .each(function () {
      return load = (url != $(this).attr('src'));
    });
  if (load){
    //didn't find it in the page, so load it
    jQuery.ajax({
      type: 'GET',
      url: url,
      success: callback,
      dataType: 'script',
      cache: cache
    });
  } else {
    //already loaded so just call the callback
    if (jQuery.isFunction(callback)) {
      callback.call(this);
    };
  };
};

function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function createTemplateTextarea(fieldName, fieldId, currentValue, options, withTemplateButton) {
    if(typeof currentValue == "undefined" || currentValue == null) {
        currentValue = "";
    }
    if(fieldId == false) {
        fieldId = fieldName.replace(/[^a-zA-Z0-9_]+/g, "_");
    }
    if(options === undefined) {
        options = {};
    }

    if(withTemplateButton === undefined) {
        withTemplateButton = true;
    }
    if(options["style"] === undefined)  options["style"] = "";
    if(options["width"] === undefined)  options["width"] = "350px";
    if(options["height"] === undefined)  options["height"] = "150px";

    var html = "";
    html += "<textarea id='" + fieldId + "' style='width:" + options["width"] + "; height: " + options["height"] + "; " + options["style"] + "' name='" + fieldName + "' style=''>" + htmlEntities(currentValue) + "</textarea>";
    if(withTemplateButton) {
        html += "<img src='modules/Workflow2/icons/templatefield.png' id='templateIcon_" + fieldId + "'  style='margin-bottom:-8px;cursor:pointer;' onclick=\"insertTemplateField('" + fieldId + "','([source]: ([module]) [destination])', true)\">";
    }

    return html;
}
function createTemplateTextfield(fieldName, fieldId, currentValue, options) {
    if(typeof currentValue == "undefined" || currentValue == null) {
        currentValue = "";
    }
    if(fieldId == false) {
        fieldId = fieldName.replace(/[^a-zA-Z0-9_]+/g, "_");
    }
    if(typeof options == 'undefined') {
        options = {};
    }

    if(options["refFields"] === undefined)  options["refFields"] = false;
    if(options["class"] === undefined)  options["class"] = "";
    if(options["style"] === undefined)  options["style"] = "";
    if(options["disabled"] === undefined || options["disabled"] == false) { options["disabled"] = ""; } else { options["disabled"] = "disabled='disabled'" }
    if(options["type"] === undefined)  options["type"] = "0";
    if(options["delimiter"] === undefined)  options["delimiter"] = "";
    if(options["title"] === undefined)  options["title"] = "";
    if(options["datalist"] === undefined)  options["datalist"] = "";
    if(options["fieldType"] === undefined)  options["fieldType"] = "text";
    if(options["readonly"] === undefined)  options["readonly"] = false;
    if(options["placeholder"] === undefined)  options["placeholder"] = '';

    options["class"] += "condition_value text textfield templateField";

    html = "<input type='" + options["fieldType"] + "' " + ( options['datalist'] !== '' ? 'list="' + options['datalist'] + '"' : '' ) + " title='"+options["title"]+"' placeholder='" + options["placeholder"] + "' alt='"+options["title"]+"' data-delimiter='"+options["delimiter"]+"' ondblclick='dblClickTextfield(\"" + fieldId + "\");' " + options["disabled"] + "style='border-right:none;float:left;" + options["style"] + "' data-module='" + options["module"] + "' class='" + options["class"] + "' name='" + fieldName + "' id='" + fieldId + "' " + (options['readonly']===true?'readonly="readonly"':'') + " value=\"" + (currentValue != false ? htmlEntities(currentValue) : "") + "\">";
    html += "<span class='templateFieldResetButton' style='float:left;'><img src='modules/Workflow2/icons/reset.png'  alt='" + MOD.LBL_VALUE_RESET + "' title='" + MOD.LBL_VALUE_RESET + "' style='margin-bottom:-8px;cursor:pointer;' onclick=\"if(!jQuery('#"+fieldId +"').attr('readonly')) jQuery('#"+fieldId +"').val('"+htmlEntities(currentValue)+"');\"></span>";
    html += "<span class='templateFieldResetButton' style='float:left;padding:0;'><img src='modules/Workflow2/icons/clear.png' alt='" + MOD.LBL_VALUE_CLEAR + "' title='" + MOD.LBL_VALUE_CLEAR + "' style='margin-bottom:-8px;cursor:pointer;' onclick=\"if(!jQuery('#"+fieldId +"').attr('readonly')) jQuery('#"+fieldId +"').val('');\"></span>";
    html += "<span class='templateFieldButton' style='float:left;'><img src='modules/Workflow2/icons/templatefield23.png' id='templateIcon_" + fieldId + "'  style='margin-bottom:-8px;cursor:pointer;' onclick=\"insertTemplateField('"+fieldId +"','([source]: ([module]) [destination])', true, false, { refFields: '"+options["refFields"]+"', module: '" + options["module"] + "', type:'"+options["type"]+"'})\"></span>";
    addToAutoCompleter(fieldId);
    return "<span style='display:inline-block;vertical-align: middle;'>" + html + "</span>";
}

function createTemplateDatefield(fieldName, fieldId, currentValue, options) {
    if(typeof currentValue == "undefined" || currentValue == null) {
        currentValue = "";
    }
    if(options === undefined) {
        options = {};
    }
    if(options["class"] === undefined)  options["class"] = "";
    if(options["style"] === undefined)  options["style"] = "";
    if(options["format"] === undefined)  options["format"] = "%Y-%m-%d";
    if(options["showTime"] === undefined)  options["showTime"] = false;

    options["class"] += "condition_value text textfield datefield templateField";

    html = '<input id="' + fieldId + '" type="text" class="dateField ' + options["class"] + '" style="border-right:none;float:left;' + options["style"] + '" name="' + fieldName + '" data-fieldinfo=\'{"mandatory":false,"presence":false,"quickcreate":false,"masseditable":true,"defaultvalue":false,"type":"date","name":"' + fieldName + '","label":"Date","date-format":"' + dateFormat + '"}\' data-date-format="' + dateFormat + '" type="text" value="' + (currentValue != false ? htmlEntities(currentValue) : "") + '" />';

    //html = "<input type='text' style='float:left;border-right:none;" + options["style"] + "' class='dateField " + options["class"] + "' name='" + fieldName + "' id='" + fieldId + "' value=\"" + (currentValue != false ? htmlEntities(currentValue) : "") + "\">";
    html += '<span class="calendarFieldButton" style="float:left;"><img src="modules/Workflow2/icons/calenderButton.png" style="margin-bottom:-6px;" id="jscal_trigger_' + fieldId + '"></span>';

    html += "<span class='templateFieldResetButton' style='float:left;'><img src='modules/Workflow2/icons/reset.png'  alt='" + MOD.LBL_VALUE_RESET + "' title='" + MOD.LBL_VALUE_RESET + "' style='margin-bottom:-8px;cursor:pointer;' onclick=\"if(!jQuery('#"+fieldId +"').attr('readonly')) jQuery('#"+fieldId +"').val('"+htmlEntities(currentValue)+"');\"></span>";
    html += "<span class='templateFieldResetButton' style='float:left;padding:0;'><img src='modules/Workflow2/icons/clear.png' alt='" + MOD.LBL_VALUE_CLEAR + "' title='" + MOD.LBL_VALUE_CLEAR + "' style='margin-bottom:-8px;cursor:pointer;' onclick=\"if(!jQuery('#"+fieldId +"').attr('readonly')) jQuery('#"+fieldId +"').val('');\"></span>";
    html += "<span class='templateFieldButton' style='float:left;'><img src='modules/Workflow2/icons/templatefield23.png' id='templateIcon_" + fieldId + "'  style='margin-bottom:-8px;cursor:pointer;' onclick=\"insertTemplateField('"+fieldId +"','([source]: ([module]) [destination])', true)\"></span>";

    //html += '<script type="text/javascript">Calendar.setup ({inputField : "' + fieldId + '", ifFormat : "' + options["format"] + '", showsTime : ' + options["showTime"] + ', button : "jscal_trigger_' + fieldId + '", singleClick : true, step : 1});</script>';

//    html += "<img src='modules/Workflow2/icons/templatefield.png' style='margin-bottom:-8px;cursor:pointer;' onclick=\"insertTemplateField('"+fieldId +"','([source]: ([module]) [destination])', true)\">";

    return "<span style='display:inline-block;vertical-align: middle;'>" + html + "</span>";
}
function dblClickTextfield(field_id) {
    return;
    if(jQuery("#" + field_id).attr("type") == "password") {
        return;
    }

    var current_value = jQuery("#" + field_id).val();

    var htmlTextArea = createTemplateTextarea("pageOverlayTextArea", "pageOverlayTextArea", current_value, {width:'470px',height: '50px'}, false) + "<div id='templateFieldsChooser'></div>";


    openPageOverlay(htmlTextArea + '<br><div class="btn-group" style="text-align:center;margin-top:5px;"><button class="btn btn-warning" onclick="closePageOverlay(\'' + field_id + '\');">s' + MOD.LBL_CANCEL + '</button><input type="btn" class="btn green" onclick="rewriteBiggerTextarea(\'' + field_id + '\');" value="' + MOD.LBL_SAVE + '"></div>', 500, field_id);

    jQuery.ajaxSetup({async:false});
    jQuery("#templateFieldsChooser").load("index.php?module=Workflow2&action=Workflow2Ajax&file=gettemplatefields", {functions: 1,workflow_module:workflowModuleName, workflowID:workflowID});
    jQuery.ajaxSetup({async:true});
    jQuery("#templateFieldsChooser select").on("change", function(e) {
        customExpressionEditor["pageOverlayTextArea"].replaceSelection("$" + jQuery(this).val(), "start");
        // insertTextIntoField("pageOverlayTextArea", "$" + jQuery(this).val(), false, false);
    });
    jQuery("#templateFieldsChooser select.chzn-select").select2();

    enable_customexpression("pageOverlayTextArea", true);

}
var insertTemplateFieldId = false;
var insertTemplateFieldCache = {};
var workflowModuleName;
var insertVariableCallback = null;
function insertTemplateField(id, format, functions, replace, options) {
//    if(workflowModuleName === undefined && moduleName == undefined && (typeof options == "undefined" || options["module"] === undefined || options["module"] == "undefined"))
//        return;

    if(jQuery('#' + id).attr('readonly')) {
        return;
    }
    var templateModule = workflowModuleName;

    if(typeof options != "undefined" && options["module"] !== undefined && options["module"] != "undefined") {
        templateModule = options["module"];
    }

    if(typeof templateModule == "undefined") {
        templateModule = moduleName;
    }
    if(typeof templateModule == "undefined") {
        return;
    }

    if(options === undefined) {
        options = {};
    }

    if(typeof options.callback == 'function') {
        insertVariableCallback = options.callback;
    } else {
        insertVariableCallback = null;
    }

    if(format == undefined) {
        format = "[source]->[module]->[destination]";
    }
    if(functions == undefined) {
        functions = false;
    }
    if(replace == undefined) {
        replace = false;
    }
    if(functions == false) {
        functions = 0;
    } else {
        functions = 1;
    }
    if(options["type"] === undefined)  options["type"] = "0";
    if(options["refFields"] === undefined)  options["refFields"] = "false";

    cacheKey = format + ";;" + templateModule + ";;" + options["type"] + ";;" + options["refFields"] + ";;functions" + functions;

    insertTemplateFieldId = id;

    if(jQuery("#insertTemplateFieldContainer").length == 0) {
        jQuery("body").append("<div id='insertTemplateFieldContainer' style='display:none;'></div>");
    }
    if(insertTemplateFieldCache[cacheKey] == undefined) {
        jQuery.ajaxSetup({async:false});
        jQuery("#insertTemplateFieldContainer").load("index.php?module=Workflow2&action=GetTemplateFields", {functions: functions, workflowID:workflowID, type:options["type"],refFields:options["refFields"],reftemplate:format,workflow_module:templateModule});
        jQuery.ajaxSetup({async:true});
        insertTemplateFieldCache[cacheKey] = jQuery("#insertTemplateFieldContainer").html();
    } else {
        jQuery("#insertTemplateFieldContainer").html(insertTemplateFieldCache[cacheKey]);
    }

//    jQuery("#insertTemplateFieldContainer select.chzn-select").chosen();
    //openPageOverlay(htmlTextArea + '<br><div class="btn-group" style="text-align:center;margin-top:5px;"><button class="btn btn-warning" onclick="closePageOverlay(\'templateIcon_' + id + '\');">' + MOD.LBL_CANCEL + '</button><input type="btn" class="btn green" onclick="rewriteBiggerTextarea(\'' + field_id + '\');" value="' + MOD.LBL_SAVE + '"></div>', 500, field_id);

    openPageOverlay(jQuery("#insertTemplateFieldContainer").html() + '<br><div class="btn-group pull-right" style="text-align:center;margin-top:5px;"><button class="btn btn-warning" onclick="closePageOverlay(\'templateIcon_' + id + '\');">' + MOD.LBL_CANCEL + '</button><button class="btn btn-success" onclick="insertTextIntoField(\''+id+'\',\'$\' + jQuery(\'#insertTemplateField_Select\',\'#pageOverlayContent\').val(),' + (replace?"true":"false") + ', true);">' + MOD.LBL_SAVE + '</button></div>', 500, 'templateIcon_' + id);

    jQuery("#pageOverlayContent select.chzn-select").select2();
    jQuery("#pageOverlayContent").on("click", function(e) {
        e.stopPropagation();
    });


/*
    jQuery("#insertTemplateFieldContainer").dialog({
        width:400,
        modal: true,
        buttons: {
            "Insert Field": function() {

                jQuery( this ).dialog( "close" );
            },
            "Cancel": function() {
                jQuery( this ).dialog( "close" );
            }
        }
    }); */
}
function insertTextIntoField(field_id, text, replace, closeWindow) {
    var delimiter = jQuery("#" + field_id).data("delimiter");

    if(typeof insertVariableCallback == 'function') {
        var returnVar = insertVariableCallback(text, {field: field_id, replace:replace });
        if(returnVar === false) {
            return;
        }
    }

    if(replace == true) {
        jQuery('#' + field_id).val(text);
    } else {
        var oldVal = jQuery('#' + field_id).val();
        if(delimiter !== undefined && oldVal.length > 0) {
            oldVal = oldVal + delimiter;
        }
        jQuery('#' + field_id).val(oldVal + text);
    }
    jQuery("#" + field_id).trigger("insertText", [text]);

    if(closeWindow) {
        closePageOverlay('templateIcon_' + field_id, true);
    }
}
var autoCompleterQueue = [];
var autoCompleterFields = {};

function addToAutoCompleter(id) {
    autoCompleterQueue.push(id);
}
function loadAutoCompleteFields(module) {
    autoCompleterFields[module] = 999;

    jQuery.post('index.php?module=Workflow2&action=GetModuleFields', {moduleName: module}, function(response) {
        var tmp = [];
        jQuery.each(response, function(index, value) {
            tmp.push({ label:value.label, value:value.name, category: value.group} );
        });
        autoCompleterFields[module] = tmp;
    }, 'json');
}

function GetCaretPosition(ctrl) {
    var CaretPos = 0;   // IE Support
    if (document.selection) {
        ctrl.focus();
        var Sel = document.selection.createRange();
        Sel.moveStart('character', -ctrl.value.length);
        CaretPos = Sel.text.length;
    }
    // Firefox support
    else if (ctrl.selectionStart || ctrl.selectionStart == '0')
        CaretPos = ctrl.selectionStart;
    return (CaretPos);
}

function ReturnWord(text, caretPos) {
    var index = text.indexOf(caretPos);
    var preText = text.substring(0, caretPos);
    var postText = text.substring(caretPos);

    if (postText.indexOf(" ") != 0) {
        var words = postText.split(" ");
        preText += words[0]; //return last word
    }

    if (preText.indexOf(" ") > 0) {
        var words = preText.split(" ");
        return words[words.length - 1]; //return last word
    }
    else {
        return preText;
    }
}

function getCurrentWord(ele) {
    var text = ele;
    var caretPos = GetCaretPosition(text);
    var word = ReturnWord(text.value, caretPos);

    if (word != null) {
        return word;
    }
    return '';
}
var oldScroll;
function ReplaceCurrentWord(ele, term) {
    var text = ele;

    var caretPos = GetCaretPosition(text);
    var value = text.value;
    var index = value.indexOf(caretPos);
    var preText = value.substring(0, caretPos);
    var postText = value.substring(caretPos);

    var words = '';

    term = term + '<|cursor|>';
    if(preText.indexOf(" ") == -1) {
        words = term;
    } else if(preText.indexOf(" ") > 0) {
        var tmp = preText.split(" ");
        tmp.pop();
        words = tmp.join(' ');
        words += ' ' + term;
    }

    if (postText.indexOf(" ") <= 0) {
        words += postText;
    } else if (postText.indexOf(" ") > 0) {
        var tmp = postText.split(" ");
        tmp.shift();
        words += ' ' + tmp.join(' ');
    }

    var cursorPos = words.indexOf('<|cursor|>');
    words = words.replace('<|cursor|>', '');


    jQuery(ele).val(words);

    setCaretPosition(ele,cursorPos);
    ele.scrollLeft = oldScroll + term.length * 2;
    character = ' ';
    jQuery(ele).trigger({ type: 'keypress', which: character.charCodeAt(0) });
}
function doGetCaretPosition (ctrl) {

	var CaretPos = 0;
	// IE Support
	if (document.selection) {

		ctrl.focus ();
		var Sel = document.selection.createRange ();

		Sel.moveStart ('character', -ctrl.value.length);

		CaretPos = Sel.text.length;
	}
	// Firefox support
	else if (ctrl.selectionStart || ctrl.selectionStart == '0')
		CaretPos = ctrl.selectionStart;

	return (CaretPos);

}


function setCaretPosition(ctrl, pos)
{

	if(ctrl.setSelectionRange)
	{
		ctrl.focus();
		ctrl.setSelectionRange(pos,pos);
	}
	else if (ctrl.createTextRange) {
		var range = ctrl.createTextRange();
		range.collapse(true);
		range.moveEnd('character', pos);
		range.moveStart('character', pos);
		range.select();
	}

}

$.widget("custom.catcomplete", jQuery.ui.autocomplete, {
    _renderMenu: function(ul, items) {
        var self = this,
        currentCategory = "";
        categoryCounter = 0;
        ul.append("<li class='ui-autocomplete-category search-dropdown-headline'>Options for '" + this.term + "'</li>");

        jQuery.each(items, function(index, item) {
            if (item.category != currentCategory) {
                ul.append("<li class='ui-autocomplete-category search-dropdown-category'>" + item.category + "</li>");
                currentCategory = item.category;
                categoryCounter = 0;
            }
            if(8 == categoryCounter) {
                return;
            }
            self._renderItem(ul, item);
            categoryCounter++;
        });
    }
});

function InitAutocompleteText() {
    if(typeof autoCompleterFields == 'undefined') {
        autoCompleterFields = {};
    }

    autoCompleterQueue = jQuery.unique(autoCompleterQueue);

    jQuery.each(autoCompleterQueue, function(index, id) {
        if(jQuery("#" + id).length == 0) {
            return;
        }

        if(typeof autoCompleterFields[jQuery("#" + id).data('module')] == 'undefined') {
            loadAutoCompleteFields(jQuery("#" + id).data('module'));
        }
        //console.log('AutoComplete Init ' + id);
        var autocompleter = jQuery("#" + id).catcomplete({
            source: function(request, response) {
                var start = new Date().getTime();
                var module = jQuery(this.element).data('module');

                // muss geladen werden
                if(typeof autoCompleterFields[module] == 'undefined') {
                    loadAutoCompleteFields(module);
                    response([]);
                    return;
                }
                // wird gerade geladen
                if(999 == autoCompleterFields[module]) {
                    response([]);
                    return;
                }

                var text = getCurrentWord(this.element[0]); //AutocompleterSplit(request.term).pop();

                if(text.substr(0, 1) == '$') {
                    text = text.substr(1);
                }
                var length = text.length;

                if(length == 0) {
                    response([]);
                    return;
                }
                var result = [];
                jQuery.each(autoCompleterFields[module], function(index, value) {
                    //console.log(index.substr(0, length - 0), text);
                    if(value.label.indexOf(text) != -1 || value.value.indexOf(text) != -1) {
                        result.push(value);
                    }
                });

                response(result);
    /*            jQuery.ajax({
                    type: "POST",
                    contentType: "application/json; charset=utf-8",
                    url: "Default.aspx/GetAutoCompleteData",
                    data: "{'username':'" + extractLast(request.term) + "'}",
                    dataType: "json",
                    success: function(data) {
                        response(data.d);
                    },
                    error: function(result) {

                    }
                }); */
                var elapsed = new Date().getTime() - start;
                oldScroll = this.element[0].scrollLeft;
                //console.log('autocomplete time: ' + elapsed + ' ms');
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function(event, ui) {
                ReplaceCurrentWord(this, '$' + ui.item.value);
                return false;
                var terms = AutocompleterSplit(this.value);
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push('$' + ui.item.value);
                // add placeholder to get the comma-and-space at the end
                terms.push("");
                this.value = terms.join(" ");
                return false;
            }
        });

        jQuery("#" + id).bind("keydown", function(event) {
            if (event.keyCode === jQuery.ui.keyCode.TAB && jQuery(this).data("autocomplete").menu.active) {
                event.preventDefault();
            }
        });

    });

    autoCompleterQueue = [];
}
function AutocompleterSplit(val) {
    return val.split(/ +/);
}

function closePageOverlay(button, instant) {
    if(typeof instant == "undefined") {
        instant = false;
    }
    if(typeof button == "undefined" || jQuery("#" + button).length == 0) {
        jQuery("#pageOverlay").hide("fast");
        return;
    }

    var ele = jQuery("#pageOverlay");
    var eleContent = jQuery("#pageOverlayContent");

    if(instant == true) {
        ele.hide();
        return;
    }

    ele.animate({ opacity:0 });
    eleContent.effect( "transfer", { to: jQuery("#" + button) }, 250, function() {
        eleContent.css('display', 'none');
        ele.css('display', 'none');
    });
}
jQuery(function() {
    jQuery("#pageOverlayContent").bind("click", function(e) {
        e.stopPropagation();
    });
});
function openPageOverlay(html, width, button) {
    if(typeof button == "undefined") {
        button = false;
    }

    var ele = jQuery("#pageOverlay");
    var eleContent = jQuery("#pageOverlayContent");
    html = '<img src="modules/SwVtTools/icons/cross-button.png" style="position:absolute;right:-5px;top:-5px;cursor:pointer;" onclick="closePageOverlay();">' + html;

    if(ele.css('display') == 'none') {
        eleContent.css("width", width + "px");
        eleContent.css("marginLeft", (-1 * (width / 2)) + "px");

        eleContent.css('visibility', 'hidden');
        ele.css('opacity', '0');

        eleContent.show();
        ele.show();

        eleContent.html(html);
        //eleContent.slideDown("fast");

        if(button != false && jQuery("#" + button).length > 0) {
            ele.animate({ opacity:1 });
            jQuery("#" + button).effect( "transfer", { to: eleContent }, 250, function() {
                eleContent.css('visibility', 'visible');
                // ele.css('visibility', 'visible');
            });
        } else {
            eleContent.css('visibility', 'visible');
            ele.css('opacity', '1');

            ele.show();
        }

    } else {
        eleContent.html(html);
        eleContent.animate({
            width:width + "px",
            marginLeft: (-1 * (width / 2)) + "px"
        }, "fast", function() {

        });
    }

    jQuery('#pageOverlayContent').on('click.select2', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    jQuery('#pageOverlayContent').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });

}