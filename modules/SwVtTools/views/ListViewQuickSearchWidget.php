<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
global $root_directory;
require_once($root_directory."/modules/SwVtTools/autoload_wf.php");

class SwVtTools_ListViewQuickSearchWidget_View extends Vtiger_BasicAjax_View {

    public function process(Vtiger_Request $request) {
        $current_user = $cu_model = Users_Record_Model::getCurrentUserModel();
        $currentLanguage = Vtiger_Language_Handler::getLanguage();

        $widgetId = $request->get('widgetid');
        $widgetModule = $request->get('widgetmodule');
        $tabId = getTabid($widgetModule);

        $adb = \PearDatabase::getInstance();

        $sql = 'SELECT * FROM vtiger_tools_listwidget WHERE id = ?';
        $result = $adb->pquery($sql, array($widgetId));
        $data = $adb->fetchByAssoc($result);
        $settings = json_decode(html_entity_decode($data['settings']), true);

        $containerId = md5(microtime(true));
        ob_start();
?>
<div class="QuickFilterWidget" style="padding:5px;overflow:hidden;" id="<?php echo $containerId; ?>">
    <h4><?php echo vtranslate('Quick Filter of Records', 'SwVtTools'); ?></h4>

    <?php foreach($settings['fields'] as $field) {
        $fieldInfo = \SwVtTools\VtUtils::getFieldInfo($field, $tabId);
        $fieldType = \SwVtTools\VtUtils::getFieldTypeName($fieldInfo['uitype'], $fieldInfo['typeofdata']);


        echo '<br/><br/><strong>'.vtranslate($fieldInfo['fieldlabel'], $widgetModule).'</strong>';

        switch($fieldType) {
            case 'integer':
            case 'string':
            case 'text':
                $sql = 'SELECT DISTINCT '.$fieldInfo['columnname'].' as value FROM '.$fieldInfo['tablename'];
                $result = $adb->query($sql);
                $values = array();
                while($row = $adb->fetchByAssoc($result)) {
                    $values[] = html_entity_decode($row['value']);
                }
                sort($values);
                echo '<select data-field="' . $field . '" data-type="'.$fieldType.'" class="LVQuickFilter Select2" data-placeholder="'.vtranslate('LBL_SELECT').'" style="width:100%">';
                echo '<option value=""></option>';
                foreach($values as $value) {
                    echo '<option value="'.$value.'">'.$value.'</option>';
                }
                echo '</select>';

                break;
            case 'picklist':
                $values = getAllPickListValues($field, array($currentLanguage));

                echo '<select data-type="'.$fieldType.'" data-field="' . $field . '" class="LVQuickFilter Select2" data-placeholder="'.vtranslate('LBL_SELECT').'" style="width:100%">';
                    echo '<option value=""></option>';
                    foreach($values as $value => $label) {
                        echo '<option value="'.$value.'">'.$label.'</option>';
                    }
                echo '</select>';
                break;
            case 'reference':
                $fieldId = 'randField'.mt_rand(100000,999999).$field;
                echo '<input data-type="'.$fieldType.'" type="hidden"  name="' . $data['name'] . '" id="'.$fieldId.'" data-field="' . $field . '" class="LVQuickFilter" style="width:100%" />';
                echo '<script type="text/javascript">jQuery("#' . $fieldId . '").select2({
            placeholder: "Auswahl",
            minimumInputLength: 0,
            width:"100%",
            allowClear:true,
            query: function (query) {
                var data = {
                    query: query.term,
                    page: query.page,
                    pageLimit: 25,
                    recordmodule: "'.$widgetModule.'"
                };

                jQuery.post("index.php?module=SwVtTools&action=RecordsByCondition", data, function (results) {
                    if(typeof results.results == \'undefined\') {
                        var results = { results:[] };
                    }
                    query.callback(results);
                }, \'json\');

            }
        }); </script>';

                break;
        }
        //var_dump($fieldType);

    } ?>
    <input type="button" name="search" class="BtnLoadQuickFilter pull-right btn btn-default" value="<?php echo vtranslate('LBL_SEARCH') ?>" />

    <script type="text/javascript">
        function refreshQuickFilterCondition(containerId) {
            var totalHtml = '';
            jQuery('table.listViewEntriesTable tbody tr:first-child td:first-child .SWAdvancedSearchHidden').remove();
            jQuery('.listSearchContributor').val('');

            jQuery('.LVQuickFilter:not(.select2-container)', '#<?php echo $containerId; ?>').each(function(index, ele) {
                var field = jQuery(ele).data('field');
                var value = jQuery(ele).val();

                var type = jQuery(ele).data('type');

                if(jQuery('.listSearchContributor[name="' + field + '"]').length == 0) {
                    totalHtml += '<input type="hidden" name="' + field.toLowerCase() + '" data-fieldinfo=\'{"type":"' + type + '","name":"' + field + '","label":"Field Label"}\' class="SWAdvancedSearchHidden listSearchContributor" value="' + value +'" />';
                } else {
                    jQuery('.listSearchContributor[name="' + field + '"]').val(value);
                }

            });

            jQuery('table.listViewEntriesTable tbody tr:first-child td:first-child').append(totalHtml);
            jQuery('.listViewEntriesTable button[data-trigger="listSearch"]').trigger('click');
        }
        jQuery('.BtnLoadQuickFilter', '#<?php echo $containerId; ?>').on('click', function(e) {
            var target = jQuery(e.currentTarget);
            refreshQuickFilterCondition(target.closest('.QuickFilterWidget').attr('id'));
        });
        jQuery('.Select2', '#<?php echo $containerId; ?>').select2({
            allowClear:true
        });
    </script>
</div>
<?php
        $content = ob_get_clean();

        //var_dump($widgetId);
        echo $content;

    }
}