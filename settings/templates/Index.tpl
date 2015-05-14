<div id="pageOverlay" onclick="closePageOverlay();" style='cursor:url("modules/SwVtTools/icons/cross-button.png"), auto;position:fixed;z-index:20000;top:0;left:0;display:none;height:100%;width:100%;background-image:url("modules/Workflow2/icons/modal.png");'><div id='pageOverlayContent' style='position:fixed;cursor:default;top:100px;margin:auto;left:50%;padding:10px;background-color:#ffffff;'>&nbsp;</div></div>
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
    {if $show_cron_warning eq true}
        <p class="alert alert-danger"><strong>ERROR:</strong><br/>It looks like you don't activate the Cronjob of this VtigerCRM System. <strong>Please activate!</strong> Otherwise lot's of functions won't work like expected.</p>
    {/if}
    <div class="row-fluid">
        <div class="span6">

            <fieldset class="vtToolBox">
                <legend>Numbers with comma</legend>
            {if $comma_numbers_enabled eq true}
                You could enter numbers with the use of the comma. <button class="btn btn-warning pull-right" type="button" onclick="return SwVtTools.commaNumbers(false);">deactivate this function</button><br/>
            {else}
                You could <strong>NOT</strong> enter numbers with the use of the comma. <button class="btn btn-primary pull-right" type="button" onclick="return SwVtTools.commaNumbers(true);">activate this function</button><br/>
            {/if}
            </fieldset>
            <fieldset class="vtToolBox">
                    <legend>create default Related Lists</legend>

                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="createRelation"/>
                    <input type="radio" name="reltype" value="get_dependents_list" /> Relation against UIType 10 field<br/>
                    <p style="margin-left: 5px;border-left:2px solid #ccc; padding-left:5px;"><strong>Example</strong>: You create a UIType 10 field within Invoices to Projects.<br/>Normally you don't see linked Invoices in a Project. This relation could be activated with this option.<br/><strong>This type require a UIType 10 field in the other direction!</strong></p>
                    <input type="radio" name="reltype" value="get_related_list" checked="checked" /> free Relation without a field<br/>
                    <p style="margin-left: 5px;border-left:2px solid #ccc; padding-left:5px;"><strong>Example</strong>: Works like the relation to documents. You could freely add a link in the direction of this relation. If you want to link one invoice to multiple projects, you couldn't use a UIType 10 field. You need to use this option!</strong></p>
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
                            <td><input type="submit" name="submit" class="btn btn-primary" value="create Relation"> </td>
                        </tr>
                    </table>
                </form>
                </fieldset>

            <fieldset class="vtToolBox">
                    <legend>manage sidebar widgets</legend>
                    {if !empty($editSidebar)}
                        <script type="text/javascript">var MOD = {ldelim}LBL_SAVE:"Save", LBL_CANCEL:"Cancel"};workflowID = 0; workflowModuleName = '{$editSidebar.moduleName}';</script>
                        <form method="POST" action="#" style="border:1px solid #cccccc;padding:10px;margin:10px;">
                            <input type="hidden" name="tool_action" value="saveSidebar"/>

                            <input type="hidden" name="sidebar_id" value="{$editSidebar.id}">
                            <input type="hidden" name="sidebar_tabid" value="{$editSidebar.tabid}">

                        <label style="float:left;width:100px;">Active</label> &nbsp;&nbsp;&nbsp;<input type="checkbox" name="active" value="1" {if $editSidebar.active eq '1'}checked='checked'{/if}><br>
                        <label style="clear:both;float:left;width:100px;">Title</label><input type="text" name="title" value="{$editSidebar.title}"><br>
                        <label style="clear:both;float:left;width:100px;">Content</label><br>
                            <div class="clearfix"></div>
                            <input type="hidden" id="templateVarContainer" value="" />
                            <input type="button" class="btn btn-primary" value="{vtranslate('insert Fieldcontent', 'Settings:Workflow2')}" id="btn_insert_variable">

                            <textarea id="sidebarContent" name="content" class='ckeditor'>{$editSidebar.content|@stripslashes}</textarea>
                            <input type="submit" class="btn btn-primary" value="save Widget" />
                        </form>
                        <script type="text/javascript">
                            jQuery( document ).ready( function() {
                            	jQuery( 'textarea#sidebarContent' ).ckeditor();

                                   jQuery('#btn_insert_variable').on('click',function(e) {
                                       insertTemplateField('templateVarContainer','([source]: ([module]) [destination])', true, true,
                                           {
                                               callback: function(text, param) {
                                                   var textarea = CKEDITOR.instances.sidebarContent;
                                                   textarea.insertHtml(text);
                                               }
                                           }
                                       );
                                   });

                               	});
                        </script>
                    {/if}
                    <form method="POST" action="#">
                        <input type="hidden" name="tool_action" value="createSidebar"/>
                        <select name="sidebar_module" style="margin:0;">
                            {foreach from=$entityModules key=tabid item=module}
                                <option value="{$tabid}">{$module.1}</option>
                            {/foreach}
                        </select>
                        <input type="submit" class="btn btn-primary" value="create Widget" />
                    </form>
                {if count($sidebars) > 0}
                <table style="width:100%;">
                    {foreach from=$sidebars item=sidebar}
                    <tr>
                        <td><strong>{$sidebar.moduleName}</strong></td>
                        <td style="color:{if $sidebar.active eq '1'}#119a01{else}#b8671b{/if}">{$sidebar.title}</td>
                        <td>
                            <a href="index.php?module=SwVtTools&view=Index&parent=Settings&editSidebar={$sidebar.id}" class="btn btn-primary">edit</a>
                            <a href="index.php?module=SwVtTools&view=Index&parent=Settings&delSidebar={$sidebar.id}" class="btn btn-primary">delete</a>
                        </td>
                    </tr>
                    {/foreach}
                </table>
                {/if}
            </fieldset>

        </div> <!-- span6 -->

        <div class="span6">
            <fieldset class="vtToolbox">
                <iframe src="https://shop.stefanwarnat.de/advert.html" width="100%" height="200" frameborder=0 ALLOWTRANSPARENCY="true"></iframe>
            </fieldset>
            <!--<fieldset style="border: 1px solid #cccccc; padding:10px;">
                <legend>activate Comments</legend>
                <form method="POST" action="#">
                    <input type="hidden" name="tool_action" value="addModcomment"/>

                    Activate Comments in this module:
                    <select name="tabid" class="select2" style="width:180px;">
                        {foreach from=$entityModules key=tabid item=module}
                            <option value="{$tabid}">{$module.1}</option>
                        {/foreach}
                    </select>
                    <input type="submit" class="btn btn-primary" name="submit" value="Activate"/>
                </form>
            </fieldset>-->
            <fieldset class="vtToolBox">
                <legend>Google Calendar Sync</legend>
                <p><strong>AutoSync:</strong></p>
                {if $gcal_autosync eq true}
                    Every configured Google Calendar Sync will be automatically executed from Scheduler. <button class="btn btn-warning" type="button" onclick="return SwVtTools.GCalSync(false);">deactivate this function</button><br/>
                {else}
                    This function will automatically sync configured Google Calendar connections. <button class="btn btn-primary" type="button" onclick="return SwVtTools.GCalSync(true);">activate this function</button><br/>
                {/if}
                <!--<p><strong>Init Google Calendar Chooser</strong></p>
                    <p>This will create the required database table to make us of this function. <button class="btn btn-primary" type="button" onclick="return SwVtTools.initGCalSync();">initialize</button></p>
-->
            </fieldset>
            <fieldset class="vtToolBox">
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
            <fieldset class="vtToolBox">
                <legend>General Options</legend>
                <table style="width:100%;">
                    <tr>
                        <td><strong>Enable ModComments in all Modules:</strong></td>
                        <td><button class="btn" type="button" onclick="return SwVtTools.GeneralOptions('enableModComments', true);">apply this</button></td>
                    </tr>
                    <tr>
                        <td colspan="2"><button class="btn" type="button" onclick="return SwVtTools.GeneralOptions('recreateUserPrivilegs', true);">Recreate all User Privilege Files</button></td>
                    </tr>
                    <!--<tr>
                        <td><strong>copy mailscanner mailbox:</strong></td>
                        <td><button class="btn btn-warning" type="button" onclick="return SwVtTools.GeneralOptions('initFilterSortOrder', true);">Initialize</button></td>
                    </tr>-->
                </table>
            </fieldset>
            <fieldset class="vtToolBox">
                <legend>EventHandler Functions</legend>

                {if $EventHandlerActive eq true}<span class="pull-right" style="color:green;font-weight;bold;">ACTIVE</span>{else}<span class="pull-right" style="color:red;font-weight;bold;">INACTIVE</span>{/if}
                <p>The following features require the <a href="https://github.com/swarnat/vtigerCRM-EventHandler/tree/vtiger6">free EventHandler Module</a>.</p><br/>
                <table style="width:100%;">
                    <tr>
                        <td><strong>Initialize sortable CustomViews:</strong></td>
                        <td><button class="btn" type="button" onclick="return SwVtTools.GeneralOptions('initFilterSortOrder', true);">Initialize</button></td>
                    </tr>
                </table>
            </fieldset>
            <a class="btn pull-right" href="index.php?module=SwVtTools&parent=Settings&view=Upgrade">Check for Update</a>
        </div> <!-- span6 -->
    </div>



    <br/>


</div>