<div class="container-fluid" id="moduleManagerContents">

    <div class="widget_header row-fluid">
        <div class="span12">
            <h3>
                <b>
                    Stefan Warnat VtigerCRM Tools
                </b>
            </h3>
        </div>
    </div>
    <hr>


    <div>
        <strong>Information:</strong>&nbsp;&nbsp;&nbsp;
        <a target="_blank"  href="https://support.stefanwarnat.de/en:extensions:vtigercrm_tools">Documentation for this module</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <a target="_blank" href="http://vtiger.stefanwarnat.de/">International Blog</a>&nbsp;&nbsp;&nbsp;
        <a target="_blank"  href="http://www.stefanwarnat.de/">German Blog</a>

        <br /><br />
    </div>
    <div class="row-fluid">
        <div class="span6">

            <fieldset style="border: 1px solid #cccccc; padding:10px;">
                <legend>Numbers with comma</legend>
            {if $comma_numbers_enabled eq true}
                You could enter numbers with the use of the comma. <button class="btn btn-warning" type="button" onclick="return SwVtTools.commaNumbers(false);">deactivate this function</button><br/>
            {else}
                You could <strong>NOT</strong> enter numbers with the use of the comma. <button class="btn btn-primary" type="button" onclick="return SwVtTools.commaNumbers(true);">activate this function</button><br/>
            {/if}
            </fieldset>
            <fieldset style="border: 1px solid #cccccc; padding:10px;">
                    <legend>create default Related Lists</legend>

                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="createRelation"/>
                <table>
                    <tr>
                        <td>Create RelatedTab here:</td>
                        <td><select name="tabid">
                                {foreach from=$entityModules key=tabid item=module}
                                    <option value="{$tabid}">{$module.1}</option>
                                {/foreach}
                            </select></td>
                    </tr>
                    <tr>
                        <td>Related module:</td>
                        <td><select name="related_tabid">
                                {foreach from=$entityModules key=tabid item=module}
                                    <option value="{$tabid}">{$module.1}</option>
                                {/foreach}
                            </select></td>
                    </tr>
                    <tr>
                        <td>Label of Relation</td>
                        <td><input type="text" name="label" value=""/></td>
                    </tr>
                    <tr>
                        <td><input type="submit" name="submit" value="create Relation"> </td>
                    </tr>
                </table>
                </form>
                </fieldset>
        </div> <!-- span6 -->

        <div class="span6">
            <fieldset style="border: 1px solid #cccccc; padding:10px;">
                <legend>Google Calendar Sync</legend>
                <p><strong>AutoSync:</strong></p>
                {if $gcal_autosync eq true}
                    Every configured Google Calendar Sync will be automatically executed from Scheduler. <button class="btn btn-warning" type="button" onclick="return SwVtTools.GCalSync(false);">deactivate this function</button><br/>
                {else}
                    This function will automatically sync configured Google Calendar connections. <button class="btn btn-primary" type="button" onclick="return SwVtTools.GCalSync(true);">activate this function</button><br/>
                {/if}
                <p><strong>Init Google Calendar Chooser</strong></p>
                    <p>This will create the required database table to make us of this function. <button class="btn btn-primary" type="button" onclick="return SwVtTools.initGCalSync();">initialize</button></p>

            </fieldset>
            <fieldset style="border: 1px solid #cccccc; padding:10px;">
                <legend>Filter Im-/Export</legend>
                <form method="POST" action="index.php?module=SwVtTools&action=ExportCustomView&parent=Settings" enctype="multipart/form-data">
                    <select name="filterId" class="chzn-select" style="width:350px;">
                        {foreach from=$customViews key=viewId item=filterName}
                            <option value="{$viewId}">{$filterName}</option>
                        {/foreach}
                    </select>
                    <input type="submit" class="btn btn-primary" name="export" value="export filter" />
                </form>
                <hr/>
                <form method="POST" action="#" enctype="multipart/form-data">
                    {if !isset($importKey)}
                        <input type="hidden" name="tool_action" value="cv_import1" />
                        Step 1: Import view: <input type="file" name="customview" />
                        <br/>
                        Select module to Import:
                        <select name="cvImportModule">
                            <option value="">Original one from CustomView</option>
                            {foreach from=$entityModules key=tabid item=module}
                                <option value="{$module.0}">{$module.1}</option>
                            {/foreach}
                        </select>
                        <input type="submit" class="btn btn-primary" name="import" value="import filter" />
                    {else}
                        <input type="hidden" name="tool_action" value="cv_import2" />
                        <input type="hidden" name="cvImportKey" value="{$importKey}" />
                        Step 2: Select corresponding fields
                        <div>
                            <label style="display: inline-block;width:200px;font-weight: bold;">Filter Name</label>
                            <input type="text" name="filterName" value="imported Filter" />
                        </div>
                        <br/>
                        {foreach from=$cvImportColumns item=column}
                            <div>
                                <label style="display: inline-block;width:200px;">{vtranslate($column.2, $column.1)}</label>
                                <select name="column[{$column.0}]">
                                    {foreach from=$import_available_fields item=field}
                                        <option value="{$field->name}" {if $column.5 eq $field->name}selected="selected"{/if}>{$field->label}</option>
                                    {/foreach}
                                </select>
                            </div>
                        {/foreach}
                        <input type="submit" class="btn btn-success" name="import" value="Spalten anwenden" />
                    {/if}
                    {if $showCVImportError neq false}
                    <br/><strong style="color:red;">{$showCVImportError}</strong>
                    {/if}
                </form>
                <div></div>
            </fieldset>
            <!--<fieldset style="border: 1px solid #cccccc; padding:10px;">
                <legend>Make Filter to default for all Users</legend>
                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="makeCvToDefault" />
                    <select multiple="multiple" name="filterIds[]" class="select2" style="width:350px;">
                        {foreach from=$customViews key=viewId item=filterName}
                            <option value="{$viewId}">{$filterName}</option>
                        {/foreach}
                    </select>
                    <br/>
                    Set for Users:<br/>
                    <select multiple="multiple" name="userIds[]" id="setDefaultCVuserIds" class="select2" style="width:350px;">
                        {foreach from=$availableUsers item=userItem}
                            <option value="{$userItem.id}">{$userItem.username} - {$userItem.first_name} {$userItem.last_name}</option>
                        {/foreach}
                    </select> <a href="#" onclick='$("#setDefaultCVuserIds > option").prop("selected","selected");$("#setDefaultCVuserIds").trigger("change");return false;'>Select all</a>&nbsp;&nbsp;&nbsp;
                    </select> <a href="#" onclick='$("#setDefaultCVuserIds > option").removeAttr("selected");$("#setDefaultCVuserIds").trigger("change");return false;'>Deselect all</a><br/>
                    <br/>
                    <input type="submit" class="btn btn-primary" name="export" value="make default" />
                </form>
                </fieldset>-->

        </div> <!-- span6 -->
    </div>



    <br/>


</div>