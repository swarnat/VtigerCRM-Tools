<table class="table table-condensed">
{foreach from=$referencefilter item=filter}
    <tr data-id="{$filter.id}">
        <td>{vtranslate($filter.modulename, $filter.modulename)}</td>
        <td>{vtranslate($filter.field.fieldlabel, $filter.modulename)}</td>
        <td>{vtranslate($filter.tomodule, $filter.tomodule)}</td>
        <td>{$filter.condition|nl2br}</td>
        <td><a href="#" class="EditReferenceFilter">edit</a> | <a href="#" class="DeleteReferenceFilter">del</a></td>
    </tr>
{/foreach}
</table>
