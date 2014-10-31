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
    <br />
    <fieldset style="border: 1px solid #cccccc; padding:10px;">
        <legend>Numbers with comma</legend>
    {if $comma_numbers_enabled eq true}
        You could enter numbers with the use of the comma. <button class="btn btn-warning" type="button" onclick="return SwVtTools.commaNumbers(false);">deactivate this function</button><br/>
    {else}
        You could <strong>NOT</strong> enter numbers with the use of the comma. <button class="btn btn-primary" type="button" onclick="return SwVtTools.commaNumbers(true);">activate this function</button><br/>
    {/if}
    </fieldset>
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

    <br/>
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

</div>