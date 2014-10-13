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
    <fieldset>
        <legend>Numbers with comma</legend>
    {if $comma_numbers_enabled eq true}
        You could enter numbers with the use of the comma. <button class="btn btn-warning" type="button" onclick="return SwVtTools.commaNumbers(false);">deactivate this function</button><br/>
    {else}
        You could <strong>NOT</strong> enter numbers with the use of the comma. <button class="btn btn-primary" type="button" onclick="return SwVtTools.commaNumbers(true);">activate this function</button><br/>
    {/if}
    </fieldset>
    <br/>
    <fieldset>
        <legend>create default Related Lists</legend>
    </fieldset>

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
</div>